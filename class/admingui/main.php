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

use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarModVars;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads admin main function
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * The main administration function
     * This function redirects the user to the view function
     * @return bool true
     */
    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('EditUploads')) {
            return;
        }

        if (xarModVars::get('modules', 'disableoverview') == 0) {
            return [];
        } else {
            xarController::redirect(xarController::URL('uploads', 'admin', 'view'), null, $this->getContext());
        }
        // success
        return true;
    }
}
