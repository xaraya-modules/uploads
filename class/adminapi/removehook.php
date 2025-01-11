<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\AdminApi;


use Xaraya\Modules\Uploads\AdminApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads adminapi removehook function
 * @extends MethodClass<AdminApi>
 */
class RemovehookMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * delete all file associations for a module - hook for ('module','remove','API')
     * Note: this will only be called if uploads is hooked to that module (e.g.
     * not for Upload properties)
     * @param mixed $args ['objectid'] ID of the object (must be the module name here !!)
     * @param mixed $args ['extrainfo'] extra information
     * @return bool
     * @return true on success, false on failure
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($extrainfo)) {
            $extrainfo = [];
        }

        // When called via hooks, we should get the real module name from objectid
        // here, because the current module is probably going to be 'modules' !!!
        if (!isset($objectid) || !is_string($objectid)) {
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID (= module name)', 'admin', 'removehook', 'uploads');
            //throw new BadParameterException(null, $msg);
            // Return the extra info
            return $extrainfo;
        }

        $modid = xarMod::getRegID($objectid);
        if (empty($modid)) {
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'module ID', 'admin', 'removehook', 'uploads');
            //throw new BadParameterException(null, $msg);
            // Return the extra info
            return $extrainfo;
        }

        if (!xarMod::apiFunc(
            'uploads',
            'admin',
            'db_delete_association',
            ['modid' => $modid]
        )) {
            // Return the extra info
            return $extrainfo;
        }

        // Return the extra info
        return $extrainfo;
    }
}
