<?php
/**
 * Uploads Module
 *
 * @package modules
 * @subpackage uploads module
 * @category Third Party Xaraya Module
 * @version 1.1.0
 * @copyright see the html/credits.html file in this Xaraya release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com/index.php/release/eid/666
 * @author Uploads Module Development Team
 */

/**
 *  Retrieves an external file using the FTP scheme
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   array  uri         the array containing the broken down url information
 *  @param   boolean obfuscate  whether or not to obfuscate the filename
 *  @param   string  savePath   Complete path to directory in which we want to save this file
 *  @return array|void          FALSE on error, otherwise an array containing the fileInformation
 */

function uploads_userapi_import_external_ftp(array $args = [], $context = null)
{
    extract($args);

    if (!isset($uri)) {
        return; // error
    }

    /**
     *  Initial variable checking / setup
     */
    if (isset($obfuscate) && $obfuscate) {
        $obfuscate_fileName = true;
    } else {
        $obfuscate_fileName = xarModVars::get('uploads', 'file.obfuscate-on-upload');
    }

    if (!isset($savePath)) {
        $savePath = xarMod::apiFunc('uploads', 'user', 'db_get_dir', ['directory' => 'uploads_directory']);
    }

    // if no port, use the default port (21)
    if (!isset($uri['port'])) {
        $uri['port'] = 21;
    }

    // if user is not set, set it to anonymous and make a best guess
    // at the password based on the user's email address
    if (!isset($uri['user'])) {
        $uri['user'] = 'anonymous';
        $uri['pass'] = xarUser::getVar('email');
        if (empty($uri['pass'])) {
            $uri['pass'] = xarModVars::get('mail', 'adminmail');
        }
    } else {
        // otherwise, if the uname is there but the
        // pass isn't, try to use the user's email address
        if (!isset($uri['pass'])) {
            xarUser::getVar('email');
        }
    }

    // TODO: handle duplicates - cfr. prepare_uploads()

    // Attempt to 'best guess' the mimeType
    $mimeType = xarMod::apiFunc('mime', 'user', 'extension_to_mime', ['fileName' => basename($uri['path'])]);

    // create the URI in the event we don't have the FTP library
    $ftpURI = "$uri[scheme]://$uri[user]:" . urlencode($uri['pass']) . "@$uri[host]:$uri[port]$uri[path]";

    // Set the connection up to not terminate
    // the script if the user aborts
    ignore_user_abort(true);

    // Create a temporary file for storing
    // the contents of this new file
    $tmpName = tempnam(null, 'xarul');

    // Set up the fileInfo array
    $fileInfo['fileName']     = basename($uri['path']);
    $fileInfo['fileType']     = $mimeType;
    $fileInfo['fileLocation'] = $tmpName;
    $fileInfo['fileSize']     = 0;


    if (!extension_loaded('ftp')) {
        if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN')) {
            if (!@dl('php_ftp.dll')) {
                $ftpLoaded = false;
            }
        } else {
            if (!@dl('ftp.so')) {
                $ftpLoaded = false;
            }
        }
    } else {
        $ftpLoaded = true;
    }

    // TODO: <rabbitt> Add fileSize checking for imported files. For those using the ftp extension
    // this can be accomplished using ftp_size() - otherwise, it could be done by keeping track
    // of the amount of data that has been written to disk and comparing it to the max allowable size.

    if ($ftpLoaded) {
        // Conect to the Server and Log in using the credentials we set up
        $ftpId = ftp_connect($uri['host'], $uri['port']);
        $result = ftp_login($ftpId, $uri['user'], $uri['pass']);

        if (!$ftpId || !$result) {
            // if the connection failed unlink
            // the temporary file and log and return an exception
            $msg = xarML(
                'Unable to connect to host [#(1):#(2)] to retrieve file [#(3)]',
                $uri['host'],
                $uri['port'],
                basename($uri['path'])
            );
            throw new Exception($msg);
        } else {
            if (($tmpId = fopen($tmpName, 'wb')) === false) {
                $msg = xarML(
                    'Unable to open temp file to store remote host [#(1):#(2)] file [#(3)]',
                    $uri['host'],
                    $uri['port'],
                    basename($uri['path'])
                );
                throw new Exception($msg);
            } else {
                if (!empty($mimeType) && substr($mimeType, 0, 4) == 'text') {
                    $ftpMode = FTP_ASCII;
                } else {
                    $ftpMode = FTP_BINARY;
                }

                // Note: this is a -blocking- process - the connection will NOT resume
                // until the file transfer has finished - hence, the
                // much needed 'ignore_user_abort()' up above
                if (!ftp_fget($ftpId, $tmpId, $uri['path'], $ftpMode)) {
                    $msg = xarML(
                        'Unable to connect to host [#(1):#(2)] to retrieve file [#(3)]',
                        $uri['host'],
                        $uri['port'],
                        basename($uri['path'])
                    );
                    throw new Exception($msg);
                } else {
                    if (is_resource($tmpId)) {
                        @fclose($tmpId);
                    }
                    $fileInfo['fileType'] = xarMod::apiFunc(
                        'mime',
                        'user',
                        'analyze_file',
                        ['fileName' => $fileInfo['fileLocation']]
                    );
                    $fileInfo['size'] = filesize($tmpName);
                }
            }
        }
        // Otherwise we have to do it the "hard" way ;-)
    } else {
        if (($ftpId = fopen($ftpURI, 'rb')) === false) {
            $msg = xarML(
                'Unable to connect to host [#(1):#(2)] to retrieve file [#(3)]',
                $uri['host'],
                $uri['port'],
                basename($uri['path'])
            );
            throw new Exception($msg);
        } else {
            if (($tmpId = fopen($tmpName, 'wb')) === false) {
                $msg = xarML(
                    'Unable to open temp file to store remote host [#(1):#(2)] file [#(3)]',
                    $uri['host'],
                    $uri['port'],
                    basename($uri['path'])
                );
                throw new Exception($msg);
            } else {
                // Note that this is a -blocking- process - the connection will
                // NOT resume until the file transfer has finished - hence, the
                // much needed  'ignore_user_abort()' up above
                do {
                    $data = fread($ftpId, 65536);
                    if (0 == strlen($data)) {
                        break;
                    } else {
                        if (fwrite($tmpId, $data, strlen($data)) !== strlen($data)) {
                            $msg = xarML('Unable to write to temp file!');
                            throw new Exception($msg);
                        }
                    }
                } while (true);

                // if we haven't hit an exception, then go ahead and close everything up
                if (is_resource($tmpId)) {
                    @fclose($tmpId);
                }
                $fileInfo['fileType'] = xarMod::apiFunc(
                    'mime',
                    'user',
                    'analyze_file',
                    ['fileName' => $fileInfo['fileLocation']]
                );
                $fileInfo['fileSize'] = filesize($tmpName);
            }
        }
    }

    if (is_resource($tmpId)) {
        fclose($tmpId);
    }

    if (is_resource($ftpId)) {
        if ($ftpLoaded) {
            ftp_close($ftpId);
        } else {
            fclose($ftpId);
        }
    }

    unlink($tmpName);

    $fileInfo['fileSrc'] = $fileInfo['fileLocation'];

    // remoe any trailing slash from the Save Path
    $savePath = preg_replace('/\/$/', '', $savePath);

    if ($obfuscate_fileName) {
        $obf_fileName = xarMod::apiFunc(
            'uploads',
            'user',
            'file_obfuscate_name',
            ['fileName' => $fileInfo['fileName']]
        );
        $fileInfo['fileDest'] = $savePath . '/' . $obf_fileName;
    } else {
        // if we're not obfuscating it,
        // just use the name of the uploaded file
        $fileInfo['fileDest'] = $savePath . '/' . xarVar::prepForOS($fileInfo['fileName']);
    }
    $fileInfo['fileLocation'] = $fileInfo['fileDest'];

    return [$fileInfo['fileLocation'] => $fileInfo];
}
