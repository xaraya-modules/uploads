<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserGui;


use Xaraya\Modules\Uploads\UserGui;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads user errors function
 * @extends MethodClass<UserGui>
 */
class ErrorsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Uploads Module
     * @package modules
     * @subpackage uploads module
     * @category Third Party Xaraya Module
     * @version 1.1.0
     * @copyright see the html/credits.html file in this Xaraya release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://www.xaraya.com/index.php/release/eid/666
     * @author Uploads Module Development Team
     */
    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('ViewUploads')) {
            return;
        }

        if (!xarVar::fetch('layout', 'str:1:100', $data['layout'], '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('maxallowed', 'str:1:100', $data['maxallowed'], '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('location', 'str:1:100', $data['location'], '', xarVar::NOT_REQUIRED)) {
            return;
        }

        return $data;
    }
}
