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
use xarMod;
use xarSession;
use xarSecurity;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi file_store function
 */
class FileStoreMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileInfo)) {
            $msg = xarML(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'fileInfo',
                'file_store',
                'uploads'
            );
            throw new Exception($msg);
        }

        $typeInfo = xarMod::apiFunc('mime', 'user', 'get_rev_mimetype', ['mimeType' => $fileInfo['fileType']]);
        $instance = [];
        $instance[0] = $typeInfo['typeId'];
        $instance[1] = $typeInfo['subtypeId'];
        $instance[2] = xarSession::getVar('uid');
        $instance[3] = 'All';

        $instance = implode(':', $instance);

        if ((isset($fileInfo['fileStatus']) && $fileInfo['fileStatus'] == _UPLOADS_STATUS_APPROVED) ||
             xarSecurity::check('AddUploads', 1, 'File', $instance)) {
            if (!isset($storeType)) {
                $storeType = _UPLOADS_STORE_FSDB;
            }

            if (!empty($fileInfo['isDuplicate']) && $fileInfo['isDuplicate'] == 2) {
                // we *want* to overwrite a duplicate here
            } else {
                // first, make sure the file isn't already stored in the db/filesystem
                // if it is, then don't add it.
                $fInfo = xarMod::apiFunc(
                    'uploads',
                    'user',
                    'db_get_file',
                    ['fileLocation' => $fileInfo['fileLocation'],
                        'fileSize' => $fileInfo['fileSize'], ]
                );

                // If we already have the file, then return the info we have on it
                if (is_array($fInfo) && count($fInfo)) {
                    // Remember, db_get_file returns the files it finds (even if just one)
                    // as an array of files, so - considering we are only expecting one file
                    // return the first one in the list - indice 0
                    return end($fInfo);
                }
            }

            // If this is just a file dump, return the dump
            if ($storeType & _UPLOADS_STORE_TEXT) {
                $fileInfo['fileData'] = xarMod::apiFunc('uploads', 'user', 'file_dump', $fileInfo);
            }
            // If the store db_entry bit is set, then go ahead
            // and set up the database meta information for the file
            if ($storeType & _UPLOADS_STORE_DB_ENTRY) {
                $fileInfo['store_type'] = $storeType;

                if (!empty($fileInfo['isDuplicate']) && $fileInfo['isDuplicate'] == 2 &&
                    !empty($fileInfo['fileId'])) {
                    // we *want* to overwrite a duplicate here
                    xarMod::apiFunc('uploads', 'user', 'db_modify_file', $fileInfo);

                    $fileId = $fileInfo['fileId'];
                } else {
                    $fileId = xarMod::apiFunc('uploads', 'user', 'db_add_file', $fileInfo);

                    if ($fileId) {
                        $fileInfo['fileId'] = $fileId;
                    }
                }
            }

            if ($storeType & _UPLOADS_STORE_FILESYSTEM) {
                if ($fileInfo['fileSrc'] != $fileInfo['fileDest']) {
                    $result = xarMod::apiFunc('uploads', 'user', 'file_move', $fileInfo);
                } else {
                    $result = true;
                }

                if ($result) {
                    $fileInfo['fileLocation'] = & $fileInfo['fileDest'];
                } else {
                    // if it wasn't moved successfully, then we should remove
                    // the database entry (if there is one) so that we don't have
                    // a corrupted file entry
                    if (isset($fileId) && !empty($fileId)) {
                        xarMod::apiFunc('uploads', 'user', 'db_delete_file', ['fileId' => $fileId]);

                        // Don't forget to remove the fileId from fileInfo
                        // because it's non existant now ;-)
                        if (isset($fileInfo['fileId'])) {
                            unset($fileInfo['fileId']);
                        }
                    }

                    $fileInfo['fileLocation'] = & $fileInfo['fileSrc'];
                }
            }

            if ($storeType & _UPLOADS_STORE_DB_DATA) {
                if (!xarMod::apiFunc('uploads', 'user', 'file_dump', $fileInfo)) {
                    // If we couldn't add the files contents to the database,
                    // then remove the file metadata as well
                    if (isset($fileId) && !empty($fileId)) {
                        xarMod::apiFunc('uploads', 'user', 'db_delete_file', ['fileId' => $fileId]);
                    }
                } else {
                    // if it was successfully added, then change the stored fileLocation
                    // to DATABASE instead of uploads/blahblahblah
                    xarMod::apiFunc('uploads', 'user', 'db_modify_file', ['fileId' => $fileId, 'fileLocation' => xarML('DATABASE')]);
                }
            }
        }

        return $fileInfo;
    }
}
