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

use Xaraya\Modules\Uploads\AdminGui;
use Xaraya\Modules\MethodClass;
use xarModHooks;

/**
 * uploads admin updateconfig function
 * @extends MethodClass<AdminGui>
 */
class UpdateconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update the configuration
     * @return bool|string|void
     * @see AdminGui::updateconfig()
     */
    public function __invoke(array $args = [])
    {
        // Get parameters
        $this->var()->find('file', $file, 'list:str:1:', '');
        $this->var()->find('imports_directory', $imports_directory, 'str:1:', '');
        $this->var()->find('uploads_directory', $uploads_directory, 'str:1:', '');
        $this->var()->find('view', $view, 'list:str:1:', '');
        $this->var()->find('ddprop', $ddprop, 'array:1:', '');
        $this->var()->find('permit_download', $permit_download, 'int', 0);
        $this->var()->find('permit_download_function', $permit_download_function, 'str', '');

        // Confirm authorisation code.
        if (!$this->sec()->confirmAuthKey()) {
            return;
        }

        $this->mod()->setVar('uploads_directory', $uploads_directory);
        $this->mod()->setVar('imports_directory', $imports_directory);

        $this->mod()->setVar('permit_download', $permit_download);
        $this->mod()->setVar('permit_download_function', $permit_download_function);

        if (isset($file) && is_array($file)) {
            foreach ($file as $varname => $value) {
                // if working on maxsize, remove all commas
                if ($varname == 'maxsize') {
                    $value = str_replace(',', '', $value);
                }
                // check to make sure that the value passed in is
                // a real uploads module variable
                if (null !== $this->mod()->getVar('file.' . $varname)) {
                    $this->mod()->setVar('file.' . $varname, $value);
                }
            }
        }

        $data['module_settings'] = $this->mod()->apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'uploads']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons');
        $data['module_settings']->getItem();
        $isvalid = $data['module_settings']->checkInput();
        if (!$isvalid) {
            $data['context'] ??= $this->getContext();
            return $this->tpl()->module('dynamicdata', 'admin', 'modifyconfig', $data);
        } else {
            $itemid = $data['module_settings']->updateItem();
        }

        if (isset($ddprop['trusted'])) {
            $this->mod()->setVar('dd.fileupload.trusted', 1);
        } else {
            $this->mod()->setVar('dd.fileupload.trusted', 0);
        }

        if (isset($ddprop['external'])) {
            $this->mod()->setVar('dd.fileupload.external', 1);
        } else {
            $this->mod()->setVar('dd.fileupload.external', 0);
        }

        if (isset($ddprop['stored'])) {
            $this->mod()->setVar('dd.fileupload.stored', 1);
        } else {
            $this->mod()->setVar('dd.fileupload.stored', 0);
        }

        if (isset($ddprop['upload'])) {
            $this->mod()->setVar('dd.fileupload.upload', 1);
        } else {
            $this->mod()->setVar('dd.fileupload.upload', 0);
        }

        // FIXME: change only if the imports_directory was changed? <rabbitt>
        // Now update the 'current working imports directory' in case the
        // imports directory was changed. We do this by first deleting the modvar
        // and then recreating it to ensure that the user's version is cleared
        // $this->mod()->delVar('path.imports-cwd');
        $this->mod()->setVar('path.imports-cwd', $this->mod()->getVar('imports_directory'));

        xarModHooks::call(
            'module',
            'updateconfig',
            'uploads',
            ['module'   => 'uploads',
                'itemtype' => 1, ]
        ); // Files

        $this->ctl()->redirect($this->mod()->getURL('admin', 'modifyconfig'));

        // Return
        return true;
    }
}
