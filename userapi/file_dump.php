<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserApi;

use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi file_dump function
 * @extends MethodClass<UserApi>
 */
class FileDumpMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Dump a files contents into the database.
     * @author  Carl P. corliss
     * @access public
     * @param array<mixed> $args
     * @var string $fileSrc   The location of the file whose contents we want to dump into the database
     * @var integer $fileId    The file entry id of the file's meta data in the database
     * @return  integer           The total bytes stored or boolean FALSE on error
     * @see UserApi::fileDump()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($unlink)) {
            $unlink = true;
        }
        if (!isset($fileSrc)) {
            $msg = $this->ml(
                'Missing parameter [#(1)] for API function [#(2)] in module [#(3)].',
                'fileSrc',
                'file_dump',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!isset($fileId)) {
            $msg = $this->ml(
                'Missing parameter [#(1)] for API function [#(2)] in module [#(3)].',
                'fileId',
                'file_dump',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!file_exists($fileSrc)) {
            $msg = $this->ml('Unable to locate file [#(1)]. Are you sure it\'s there??', $fileSrc);
            throw new Exception($msg);
        }

        if (!is_readable($fileSrc) || !is_writable($fileSrc)) {
            $msg = $this->ml('Cannot read and/or write to file [#(1)]. File will be read from and deleted afterwards. Please ensure that this application has sufficient access to do so.', $fileSrc);
            throw new Exception($msg);
        }
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        $fileInfo = $userapi->dbGetFile(['fileId' => $fileId]);
        $fileInfo = end($fileInfo);

        if (!count($fileInfo) || empty($fileInfo)) {
            $msg = $this->ml(
                'FileId [#(1)] does not exist. File [#(2)] does not have a corresponding metadata entry in the databsae.',
                $fileId,
                $fileSrc
            );
            throw new Exception($msg);
        } else {
            $dataBlocks = $userapi->dbCountData(['fileId' => $fileId]);

            if ($dataBlocks > 0) {
                // we don't support non-truncated overwrites nor appends
                // so truncate the file and then save it
                if (!$userapi->dbDeleteFileData(['fileId' => $fileId])) {
                    $msg = $this->ml('Unable to truncate file [#(1)] in database.', $fileInfo['fileName']);
                    throw new Exception($msg);
                }
            }

            // Now we copy the contents of the file into the database
            if (($srcId = fopen($fileSrc, 'rb')) !== false) {
                do {
                    // Read 16K in at a time
                    $data = fread($srcId, (64 * 1024));
                    if (0 == strlen($data)) {
                        fclose($srcId);
                        break;
                    }
                    if (!$userapi->dbAddFileData(['fileId' => $fileId, 'fileData' => $data])) {
                        // there was an error, so close the input file and delete any blocks
                        // we may have written, unlink the file (if specified), and return an exception
                        fclose($srcId);
                        if ($unlink) {
                            @unlink($fileSrc); // fail silently
                        }
                        $userapi->dbDeleteFileData(['fileId' => $fileId]);
                        $msg = $this->ml('Unable to save file contents to database.');
                        throw new Exception($msg);
                    }
                } while (true);
            } else {
                $msg = $this->ml('Cannot read and/or write to file [#(1)]. File will be read from and deleted afterwards. Please ensure that this application has sufficient access to do so.', $fileSrc);
                throw new Exception($msg);
            }
        }

        if ($unlink) {
            @unlink($fileSrc);
        }
        return true;
    }
}
