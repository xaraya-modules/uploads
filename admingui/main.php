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
use xarSecurity;
use xarModVars;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads admin main function
 * @extends MethodClass<AdminGui>
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * The main administration function
     * This function redirects the user to the view function
     * @return array|bool|void true
     * @see AdminGui::main()
     */
    public function __invoke(array $args = [])
    {
        if (!$this->sec()->checkAccess('EditUploads')) {
            return;
        }

        if (!$this->mod()->disableOverview()) {
            return [];
        } else {
            $this->ctl()->redirect($this->mod()->getURL('admin', 'view'));
        }
        // success
        return true;
    }
}
