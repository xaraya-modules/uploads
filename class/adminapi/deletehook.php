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

use Xaraya\Modules\MethodClass;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads adminapi deletehook function
 */
class DeletehookMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * delete file associations for an item - hook for ('item','delete','API')
     * Note: this will only be called if uploads is hooked to that module (e.g.
     * not for Upload properties)
     * @param mixed $args ['objectid'] ID of the object
     * @param mixed $args ['extrainfo'] extra information
     * @return array
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($extrainfo)) {
            $extrainfo = [];
        }

        if (!isset($objectid) || !is_numeric($objectid)) {
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID', 'admin', 'deletehook', 'uploads');
            //throw new BadParameterException(null, $msg);
            // Return the extra info
            return $extrainfo;
        }

        // When called via hooks, the module name may be empty, so we get it from
        // the current module
        if (empty($extrainfo['module'])) {
            $modname = xarMod::getName();
        } else {
            $modname = $extrainfo['module'];
        }

        $modid = xarMod::getRegID($modname);
        if (empty($modid)) {
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'module name', 'admin', 'deletehook', 'uploads');
            //throw new BadParameterException(null, $msg);
            // Return the extra info
            return $extrainfo;
        }
        if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
            $itemtype = $extrainfo['itemtype'];
        } else {
            $itemtype = 0;
        }

        if (!xarMod::apiFunc(
            'uploads',
            'admin',
            'db_delete_association',
            ['itemid' => $objectid,
                'itemtype' => $itemtype,
                'modid' => $modid, ]
        )) {
            // Return the extra info
            return $extrainfo;
        }

        // Return the extra info
        return $extrainfo;
    }
}
