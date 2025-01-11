<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserApi;

use Xaraya\Modules\MethodClass;
use xarModVars;
use xarMod;
use xarVar;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi import_external_http function
 */
class ImportExternalHttpMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieves an external file using the http scheme
     *  @author  Carl P. Corliss
     * @access public
     * @param   array  uri         the array containing the broken down url information
     * @param   boolean obfuscate  whether or not to obfuscate the filename
     * @param   string  savePath   Complete path to directory in which we want to save this file
     * @return array|void          FALSE on error, otherwise an array containing the fileInformation
     */
    public function __invoke(array $args = [])
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
            if ($uri['scheme'] === 'https') {
                $uri['port'] = 443;
            } else {
                $uri['port'] = 80;
            }
        }

        if (!isset($uri['path'])) {
            $uri['path'] = '';
        }

        if (!isset($uri['query'])) {
            $uri['query'] = '';
        }

        if (!isset($uri['fragment'])) {
            $uri['fragment'] = '';
        }
        $total = 0;
        $maxSize = xarModVars::get('uploads', 'file.maxsize');

        // create the URI in the event we don't have the http library
        $httpURI = "$uri[scheme]://$uri[host]:$uri[port]$uri[path]$uri[query]$uri[fragment]";

        // Set the connection up to not terminate
        // the script if the user aborts
        ignore_user_abort(true);

        // Create a temporary file for storing
        // the contents of this new file
        $tmpName = tempnam(null, 'xarul');

        // TODO: handle duplicates - cfr. prepare_uploads()

        // Set up the fileInfo array
        $fileInfo['fileName']     = basename($uri['path']);
        if (empty($fileInfo['fileName'])) {
            $fileInfo['fileName'] = str_replace('.', '_', $uri['host']) . '.html';
        }
        $fileInfo['fileType']     = 'text/plain';
        $fileInfo['fileLocation'] = $tmpName;
        $fileInfo['fileSize']     = 0;

        if (($httpId = fopen($httpURI, 'rb')) === false) {
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
                    $data = fread($httpId, 65536);
                    if (0 == strlen($data)) {
                        break;
                    } else {
                        $total += strlen($data);
                        if ($total > $maxSize) {
                            $msg = xarML('File size is greater than the maximum allowable.');
                            throw new Exception($msg);
                        } elseif (fwrite($tmpId, $data, strlen($data)) !== strlen($data)) {
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

        if (is_resource($tmpId)) {
            @fclose($tmpId);
        }

        if (is_resource($httpId)) {
            @fclose($httpId);
        }

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
}
