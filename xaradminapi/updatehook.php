<?php
/**
 * Uploads Module
 *
 * @package modules
 * @subpackage uploads module
 * @category Third Party Xaraya Module
 * @version 1.1.0
 * @copyright see the html/credits.html file in this Xaraya release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com/index.php/release/eid/666
 * @author Uploads Module Development Team
 */

/**
 * update file associations for an item - hook for ('item','update','API')
 * Note: this will only be called if uploads is hooked to that module (e.g.
 *       not for Upload properties)
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return bool
 * @return true on success, false on failure
 */
function uploads_adminapi_updatehook(array $args = [], $context = null)
{
    extract($args);

    if (!isset($extrainfo)) {
        $extrainfo = [];
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID', 'admin', 'createhook', 'uploads');
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
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 'module name', 'admin', 'createhook', 'uploads');
        //throw new BadParameterException(null, $msg);
        // Return the extra info
        return $extrainfo;
    }

    if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = 0;
    }

    // TODO: rescan ?

    // Return the extra info
    return $extrainfo;
}
