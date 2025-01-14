<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
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
 * uploads adminapi createhook function
 * @extends MethodClass<AdminApi>
 */
class CreatehookMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * create file associations for an item - hook for ('item','create','API')
     * Note: this will only be called if uploads is hooked to that module (e.g.
     * not for Upload properties)
     * @param array<mixed> $args
     * @var int|string $objectid ID of the object
     * @var array $extrainfo extra information
     * @return array $extrainfo
     * @see AdminApi::createhook()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($extrainfo) || !is_array($extrainfo)) {
            $extrainfo = [];
        }

        if (!isset($objectid) || !is_numeric($objectid)) {
            $msg = $this->translate('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID', 'admin', 'createhook', 'uploads');
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
            $msg = $this->translate('Invalid #(1) for #(2) function #(3)() in module #(4)', 'module name', 'admin', 'createhook', 'uploads');
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
}
