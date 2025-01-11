<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\AdminGui;

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\AdminGui;
use Xaraya\Modules\MethodClass;
use xarMod;
use xarSecurity;
use xarVar;
use xarModVars;
use xarModUserVars;
use xarController;
use xarSec;
use xarTpl;
use DataPropertyMaster;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads admin get_files function
 * @extends MethodClass<AdminGui>
 */
class GetFilesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('AddUploads')) {
            return;
        }

        $actionList[] = Defines::GET_UPLOAD;
        $actionList[] = Defines::GET_EXTERNAL;
        $actionList[] = Defines::GET_LOCAL;
        $actionList[] = Defines::GET_REFRESH_LOCAL;
        $actionList = 'enum:' . implode(':', $actionList);

        // What action are we performing?
        if (!xarVar::fetch('action', $actionList, $args['action'], null, xarVar::NOT_REQUIRED)) {
            return;
        }

        // StoreType can -only- be one of FSDB or DB_FULL
        $storeTypes = Defines::STORE_FSDB . ':' . Defines::STORE_DB_FULL;
        if (!xarVar::fetch('storeType', "enum:$storeTypes", $storeType, '', xarVar::NOT_REQUIRED)) {
            return;
        }

        // now make sure someone hasn't tried to change our maxsize on us ;-)
        $file_maxsize = xarModVars::get('uploads', 'file.maxsize');

        switch ($args['action']) {
            case Defines::GET_UPLOAD:
                $uploads = DataPropertyMaster::getProperty(['name' => 'uploads']);
                $uploads->initialization_initial_method = $args['action'];
                $uploads->checkInput('upload');
                $args['upload'] = $uploads->propertydata;
                break;
            case Defines::GET_EXTERNAL:
                // minimum external import link must be: ftp://a.ws  <-- 10 characters total
                if (!xarVar::fetch('import', 'regexp:/^([a-z]*).\/\/(.{7,})/', $import, 'NULL', xarVar::NOT_REQUIRED)) {
                    return;
                }
                $args['import'] = $import;
                break;
            case Defines::GET_LOCAL:
                if (!xarVar::fetch('fileList', 'list:regexp:/(?<!\.{2,2}\/)[\w\d]*/', $fileList)) {
                    return;
                }
                if (!xarVar::fetch('file_all', 'checkbox', $file_all, '', xarVar::NOT_REQUIRED)) {
                    return;
                }
                if (!xarVar::fetch('addbutton', 'str:1', $addbutton, '', xarVar::NOT_REQUIRED)) {
                    return;
                }
                if (!xarVar::fetch('delbutton', 'str:1', $delbutton, '', xarVar::NOT_REQUIRED)) {
                    return;
                }

                if (empty($addbutton) && empty($delbutton)) {
                    $msg = xarML('Unsure how to proceed - missing button action!');
                    throw new Exception($msg);
                } else {
                    $args['bAction'] = (!empty($addbutton)) ? $addbutton : $delbutton;
                }

                $cwd = xarModUserVars::get('uploads', 'path.imports-cwd');
                foreach ($fileList as $file) {
                    $args['fileList']["$cwd/$file"] = xarMod::apiFunc(
                        'uploads',
                        'user',
                        'file_get_metadata',
                        ['fileLocation' => "$cwd/$file"]
                    );
                }
                $args['getAll'] = $file_all;

                break;
            default:
            case Defines::GET_REFRESH_LOCAL:
                if (!xarVar::fetch('inode', 'regexp:/(?<!\.{2,2}\/)[\w\d]*/', $inode, '', xarVar::NOT_REQUIRED)) {
                    return;
                }

                $cwd = xarMod::apiFunc('uploads', 'user', 'import_chdir', ['dirName' => $inode ?? null]);

                $data['storeType']['DB_FULL']     = Defines::STORE_DB_FULL;
                $data['storeType']['FSDB']        = Defines::STORE_FSDB;
                $data['inodeType']['DIRECTORY']   = Defines::TYPE_DIRECTORY;
                $data['inodeType']['FILE']        = Defines::TYPE_FILE;
                $data['getAction']['LOCAL']       = Defines::GET_LOCAL;
                $data['getAction']['EXTERNAL']    = Defines::GET_EXTERNAL;
                $data['getAction']['UPLOAD']      = Defines::GET_UPLOAD;
                $data['getAction']['REFRESH']     = Defines::GET_REFRESH_LOCAL;
                $data['local_import_post_url']    = xarController::URL('uploads', 'admin', 'get_files');
                $data['external_import_post_url'] = xarController::URL('uploads', 'admin', 'get_files');
                $data['fileList'] = xarMod::apiFunc(
                    'uploads',
                    'user',
                    'import_get_filelist',
                    ['fileLocation' => $cwd, 'onlyNew' => true]
                );

                $data['curDir'] = str_replace(xarModVars::get('uploads', 'imports_directory'), '', $cwd);
                $data['noPrevDir'] = (xarModVars::get('uploads', 'imports_directory') == $cwd) ? true : false;
                // reset the CWD for the local import
                // then only display the: 'check for new imports' button
                $data['authid'] = xarSec::genAuthKey();
                $data['file_maxsize'] = $file_maxsize;
                return $data;
        }
        if (isset($storeType)) {
            $args['storeType'] = $storeType;
        }
        $list = xarMod::apiFunc('uploads', 'user', 'process_files', $args);
        if (is_array($list) && count($list)) {
            return xarTpl::module('uploads', 'admin', 'addfile-status', ['fileList' => $list], null);
        } else {
            xarController::redirect(xarController::URL('uploads', 'admin', 'get_files'), null, $this->getContext());
            return;
        }
    }
}
