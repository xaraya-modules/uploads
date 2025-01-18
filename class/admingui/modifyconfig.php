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
use xarMod;
use xarSecurity;
use xarModVars;
use xarSec;
use xarModHooks;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads admin modifyconfig function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the configuration for the Uploads module
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        xarMod::apiLoad('uploads', 'user');

        // Security check
        if (!$this->sec()->checkAccess('AdminUploads')) {
            return;
        }

        // Generate a one-time authorisation code for this operation

        // get the current module variables for display
        // *********************************************
        // Global
        $data['file']['maxsize']                = number_format($this->mod()->getVar('file.maxsize'));
        $data['file']['delete-confirmation']    = $this->mod()->getVar('file.delete-confirmation');
        $data['file']['auto-purge']             = $this->mod()->getVar('file.auto-purge');
        $data['file']['auto-approve']           = $this->mod()->getVar('file.auto-approve');
        $data['file']['obfuscate-on-import']    = $this->mod()->getVar('file.obfuscate-on-import');
        $data['file']['obfuscate-on-upload']    = $this->mod()->getVar('file.obfuscate-on-upload');
        $data['file']['cache-expire']           = $this->mod()->getVar('file.cache-expire');
        if (!isset($data['file']['cache-expire'])) {
            $this->mod()->setVar('file.cache-expire', 0);
        }
        $data['file']['allow-duplicate-upload'] = $this->mod()->getVar('file.allow-duplicate-upload');
        if (!isset($data['file']['allow-duplicate-upload'])) {
            $this->mod()->setVar('file.allow-duplicate-upload', 0);
            $data['file']['allow-duplicate-upload'] = 0;
        }
        $data['ddprop']['trusted']              = $this->mod()->getVar('dd.fileupload.trusted');
        $data['ddprop']['external']             = $this->mod()->getVar('dd.fileupload.external');
        $data['ddprop']['stored']               = $this->mod()->getVar('dd.fileupload.stored');
        $data['ddprop']['upload']               = $this->mod()->getVar('dd.fileupload.upload');
        $data['authid']                         = $this->sec()->genAuthKey();

        $data['approveList']['noone']      = Defines::APPROVE_NOONE;
        $data['approveList']['admin']      = Defines::APPROVE_ADMIN;
        $data['approveList']['everyone']   = Defines::APPROVE_EVERYONE;

        if ($data['file']['auto-approve'] != Defines::APPROVE_NOONE &&
            $data['file']['auto-approve'] != Defines::APPROVE_ADMIN &&
            $data['file']['auto-approve'] != Defines::APPROVE_EVERYONE) {
            $data['file']['auto-approve'] = Defines::APPROVE_NOONE;
        }

        $hooks = xarModHooks::call(
            'module',
            'modifyconfig',
            'uploads',
            ['module'   => 'uploads',
                'itemtype' => 1, ]
        ); // Files

        if (empty($hooks)) {
            $data['hooks'] = [];
        } else {
            $data['hooks'] = $hooks;
        }
        $admingui = $this->getParent();

        /** @var UserApi $userapi */
        $userapi = $admingui->getAPI();

        // Check the validaty of directories
        $location = $userapi->dbGetDir(['directory' => 'uploads_directory']);
        $data['uploads_directory_message'] = "";
        if (!file_exists($location) || !is_dir($location)) {
            $data['uploads_directory_message'] = $this->ml('Not a valid directory');
        } elseif (!is_writable($location)) {
            $data['uploads_directory_message'] = $this->ml('Not a writable directory');
        }

        $location = $userapi->dbGetDir(['directory' => 'imports_directory']);
        $data['imports_directory_message'] = "";
        if (!file_exists($location) || !is_dir($location)) {
            $data['imports_directory_message'] = $this->ml('Not a valid directory');
        } elseif (!is_writable($location)) {
            $data['imports_directory_message'] = $this->ml('Not a writable directory');
        }

        // Define the module settings
        $data['module_settings'] = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'uploads']);
        $data['module_settings']->setFieldList('items_per_page, use_module_alias, use_module_icons');
        $data['module_settings']->getItem();

        // Return the template variables defined in this function
        return $data;
    }
}
