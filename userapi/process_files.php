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

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use xarModVars;
use xarModUserVars;
use sys;
use BadParameterException;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi process_files function
 * @extends MethodClass<UserApi>
 */
class ProcessFilesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * @see UserApi::processFiles()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $storeList = [];

        if (!isset($action)) {
            $msg = $this->ml("Missing parameter [#(1)] to API function [#(2)] in module [#(3)].", 'action', 'process_files', 'uploads');
            throw new Exception($msg);
        }

        // If not store type defined, default to DB ENTRY AND FILESYSTEM STORE
        if (!isset($storeType)) {
            // this is the same as Defines::STORE_DB_ENTRY OR'd with Defines::STORE_FILESYSTEM
            $storeType = Defines::STORE_FSDB;
        }
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        // If there is an override['upload']['path'], try to use that
        if (!empty($override['upload']['path'])) {
            $upload_directory = $override['upload']['path'];
            if (!file_exists($upload_directory)) {
                // Note: the parent directory must already exist
                $result = @mkdir($upload_directory);
                if ($result) {
                    // create dummy index.html in case it's web-accessible
                    @touch($upload_directory . '/index.html');
                } else {
                    // CHECKME: fall back to common uploads directory, or fail ?
                    $upload_directory = $userapi->dbGetDir(['directory' => 'uploads_directory']);
                }
            }
        } else {
            $upload_directory = $userapi->dbGetDir(['directory' => 'uploads_directory']);
        }

        // Check for override of upload obfuscation and set accordingly
        if (isset($override['upload']['obfuscate']) && $override['upload']['obfuscate']) {
            $upload_obfuscate = true;
        } else {
            $upload_obfuscate = false;
        }

        switch ($action) {
            case Defines::GET_UPLOAD:
                if (!isset($upload) || empty($upload)) {
                    $msg = $this->ml('Missing parameter [#(1)] to API function [#(2)] in module [#(3)].', 'upload', 'process_files', 'uploads');
                    throw new Exception($msg);
                }

                // Set in the uploads method
                //$allow_duplicate = $this->mod()->getVar('file.allow-duplicate-upload');

                // Rearange the uploads array so we can pass the uploads one by one
                $uploadarray = [];
                foreach ($upload['name'] as $key => $value) {
                    $uploadarray[$key]['name'] = $value;
                }
                foreach ($upload['type'] as $key => $value) {
                    $uploadarray[$key]['type'] = $value;
                }
                foreach ($upload['tmp_name'] as $key => $value) {
                    $uploadarray[$key]['tmp_name'] = $value;
                }
                foreach ($upload['error'] as $key => $value) {
                    $uploadarray[$key]['error'] = $value;
                }
                foreach ($upload['size'] as $key => $value) {
                    $uploadarray[$key]['size'] = $value;
                }

                $fileList = [];
                foreach ($uploadarray as $upload) {
                    if (isset($upload['name']) && !empty($upload['name'])) {
                        // make sure we look in the right directory :-)
                        if ($storeType & Defines::STORE_FILESYSTEM) {
                            $dirfilter = $upload_directory . '/%';
                        } else {
                            $dirfilter = null;
                        }
                        // Note: we don't check on fileSize here (it wasn't taken into account before)
                        $fileTest = $userapi->dbGetFile(['fileName' => $upload['name'],
                            // make sure we look in the right directory :-)
                            'fileLocation' => $dirfilter, ]);
                        if (count($fileTest)) {
                            $file = end($fileTest);
                            // if we don't allow duplicates
                            if (empty($allow_duplicate)) {
                                // specify the error message
                                $file['errors'] = [];
                                $file['errors'][] = ['errorMesg' => $this->ml('Filename already exists'),
                                    'errorId'   => Defines::ERROR_BAD_FORMAT, ];
                                // set the fileId to null for templates etc.
                                $file['fileId'] = null;
                                // add the existing file to the list and break off
                                $fileList[0] = $file;
                                break;

                                // if we want to replace duplicate files
                            } elseif ($allow_duplicate == 2) {
                                // pass original fileId and fileLocation to $upload,
                                // and do something special in prepare_uploads / file_store ?
                                $upload['fileId'] = $file['fileId'];
                                $upload['fileLocation'] = $file['fileLocation'];
                                $upload['isDuplicate'] = 2;
                            } else {
                                // new version for duplicate files - continue as usual
                                $upload['isDuplicate'] = 1;
                            }
                        }

                        $fileList = array_merge($fileList, $userapi->prepareUploads([
                            'savePath'  => $upload_directory,
                            'obfuscate' => $upload_obfuscate,
                            'fileInfo'  => $upload,
                        ]));
                    }
                }
                break;
            case Defines::GET_LOCAL:

                $storeType = Defines::STORE_DB_ENTRY;

                if (isset($getAll) && !empty($getAll)) {
                    // current working directory for the user, set by import_chdir() when using the get_files() GUI
                    $cwd = $this->mod()->getUserVar('path.imports-cwd');

                    $fileList = $userapi->importGetFilelist(['fileLocation' => $cwd, 'descend' => true]);
                } else {
                    $list = [];
                    // file list coming from validatevalue() or the get_files() GUI
                    foreach ($fileList as $location => $fileInfo) {
                        if ($fileInfo['inodeType'] == Defines::TYPE_DIRECTORY) {
                            $list += $userapi->importGetFilelist([
                                'fileLocation' => $location,
                                'descend' => true,
                            ]);
                            unset($fileList[$location]);
                        }
                    }

                    $fileList += $list;

                    // files in the trusted directory are automatically approved
                    foreach ($fileList as $key => $fileInfo) {
                        $fileList[$key]['fileStatus'] = Defines::STATUS_APPROVED;
                    }
                    unset($list);
                }
                break;
            case Defines::GET_EXTERNAL:

                if (!isset($import)) {
                    $msg = $this->ml('Missing parameter [#(1)] to API function [#(2)] in module [#(3)].', 'import', 'process_files', 'uploads');
                    throw new Exception($msg);
                }

                // Setup the uri structure so we have defaults if parse_url() doesn't create them
                $uri = parse_url($import);

                if (!isset($uri['scheme']) || empty($uri['scheme'])) {
                    $uri['scheme'] = $this->ml('unknown');
                }

                switch ($uri['scheme']) {
                    case 'ftp':
                        $fileList = $userapi->importExternalFtp([
                            'savePath'  => $upload_directory,
                            'obfuscate' => $upload_obfuscate,
                            'uri'       => $uri,
                        ]);
                        break;
                    case 'https':
                    case 'http':
                        $fileList = $userapi->importExternalHttp([
                            'savePath'  => $upload_directory,
                            'obfuscate' => $upload_obfuscate,
                            'uri'       => $uri,
                        ]);
                        break;
                    case 'file':
                        // If we'ere using the file scheme then just store a db entry only
                        // as there is really no sense in moving the file around
                        $storeType = Defines::STORE_DB_ENTRY;
                        $fileList = $userapi->importExternalFile([
                            'uri'       => $uri,
                        ]);
                        break;
                    case 'gopher':
                    case 'wais':
                    case 'news':
                    case 'nntp':
                    case 'prospero':
                    default:
                        // ERROR
                        $msg = $this->ml('Import via scheme \'#(1)\' is not currently supported', $uri['scheme']);
                        throw new Exception($msg);
                }
                break;
            default:
                $msg = $this->ml("Invalid parameter [#(1)] to API function [#(2)] in module [#(3)].", 'action', 'process_files', 'uploads');
                throw new Exception($msg);
        }
        foreach ($fileList as $fileInfo) {
            // If the file has errors, add the file to the storeList (with it's errors intact),
            // and continue to the next file in the list. Note: it's up to the calling function
            // to deal with the error (or not) - however, we won't be adding the file with errors :-)
            if (isset($fileInfo['errors'])) {
                $storeList[] = $fileInfo;
                continue;
            }
            $storeList[] = $userapi->fileStore([
                'fileInfo'  => $fileInfo,
                'storeType' => $storeType,
            ]);
        }
        return $storeList;
    }
}
