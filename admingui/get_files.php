<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\AdminGui;

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\AdminGui;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use Exception;

/**
 * uploads admin get_files function
 * @extends MethodClass<AdminGui>
 */
class GetFilesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * @see AdminGui::getFiles()
     */
    public function __invoke(array $args = [])
    {
        if (!$this->sec()->checkAccess('AddUploads')) {
            return;
        }

        $actionList[] = Defines::GET_UPLOAD;
        $actionList[] = Defines::GET_EXTERNAL;
        $actionList[] = Defines::GET_LOCAL;
        $actionList[] = Defines::GET_REFRESH_LOCAL;
        $actionList = 'enum:' . implode(':', $actionList);

        // What action are we performing?
        $this->var()->find('action', $args['action'], $actionList);

        // StoreType can -only- be one of FSDB or DB_FULL
        $storeTypes = Defines::STORE_FSDB . ':' . Defines::STORE_DB_FULL;
        $this->var()->find('storeType', $storeType, "enum:$storeTypes", '');

        // now make sure someone hasn't tried to change our maxsize on us ;-)
        $file_maxsize = $this->mod()->getVar('file.maxsize');

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        switch ($args['action']) {
            case Defines::GET_UPLOAD:
                $uploads = $this->prop()->getProperty(['name' => 'uploads']);
                $uploads->initialization_initial_method = $args['action'];
                $uploads->checkInput('upload');
                // UploadProperty()->propertydata contains ['action' => ..., 'upload' => [...]]
                if (is_array($uploads->propertydata) && array_key_exists('upload', $uploads->propertydata)) {
                    $args['upload'] = $uploads->propertydata['upload'];
                } else {
                    $args['upload'] = $uploads->propertydata;
                }
                break;
            case Defines::GET_EXTERNAL:
                // minimum external import link must be: ftp://a.ws  <-- 10 characters total
                $this->var()->get('import', $import, 'regexp:/^([a-z]*).\/\/(.{7,})/');
                $args['import'] = $import;
                break;
            case Defines::GET_LOCAL:
                $this->var()->get('fileList', $fileList, 'list:regexp:/(?<!\.{2,2}\/)[\w\d]*/');
                $this->var()->find('file_all', $file_all, 'checkbox', '');
                $this->var()->find('addbutton', $addbutton, 'str:1', '');
                $this->var()->find('delbutton', $delbutton, 'str:1', '');

                if (empty($addbutton) && empty($delbutton)) {
                    $msg = $this->ml('Unsure how to proceed - missing button action!');
                    throw new Exception($msg);
                } else {
                    $args['bAction'] = (!empty($addbutton)) ? $addbutton : $delbutton;
                }

                $cwd = $this->mod()->getUserVar('path.imports-cwd');
                foreach ($fileList as $file) {
                    $args['fileList']["$cwd/$file"] = $userapi->fileGetMetadata([
                        'fileLocation' => "$cwd/$file",
                    ]);
                }
                $args['getAll'] = $file_all;

                break;
            default:
            case Defines::GET_REFRESH_LOCAL:
                $this->var()->get('inode', $inode, 'regexp:/(?<!\.{2,2}\/)[\w\d]*/');

                $cwd = $userapi->importChdir(['dirName' => $inode ?? null]);

                $data['storeType']['DB_FULL']     = Defines::STORE_DB_FULL;
                $data['storeType']['FSDB']        = Defines::STORE_FSDB;
                $data['inodeType']['DIRECTORY']   = Defines::TYPE_DIRECTORY;
                $data['inodeType']['FILE']        = Defines::TYPE_FILE;
                $data['getAction']['LOCAL']       = Defines::GET_LOCAL;
                $data['getAction']['EXTERNAL']    = Defines::GET_EXTERNAL;
                $data['getAction']['UPLOAD']      = Defines::GET_UPLOAD;
                $data['getAction']['REFRESH']     = Defines::GET_REFRESH_LOCAL;
                $data['local_import_post_url']    = $this->mod()->getURL('admin', 'get_files');
                $data['external_import_post_url'] = $this->mod()->getURL('admin', 'get_files');
                $data['fileList'] = $userapi->importGetFilelist([
                    'fileLocation' => $cwd,
                    'onlyNew' => true,
                ]);

                $data['curDir'] = str_replace($this->mod()->getVar('imports_directory'), '', $cwd);
                $data['noPrevDir'] = ($this->mod()->getVar('imports_directory') == $cwd) ? true : false;
                // reset the CWD for the local import
                // then only display the: 'check for new imports' button
                $data['authid'] = $this->sec()->genAuthKey();
                $data['file_maxsize'] = $file_maxsize;
                return $data;
        }
        if (isset($storeType)) {
            $args['storeType'] = $storeType;
        }
        $list = $userapi->processFiles($args);
        if (is_array($list) && count($list)) {
            return $this->render('addfile-status', ['fileList' => $list], null);
        } else {
            $this->ctl()->redirect($this->mod()->getURL('admin', 'get_files'));
            return;
        }
    }
}
