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

use Xaraya\Modules\MethodClass;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads user main function
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Return to the download function
     * @param array $args
     */
    public function __invoke(array $args = [])
    {
        return xarMod::guiFunc('uploads', 'user', 'download', $args);
    }
}
