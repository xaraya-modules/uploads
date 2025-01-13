<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads;

use Xaraya\Modules\AdminApiClass;
use sys;

sys::import('xaraya.modules.adminapi');

/**
 * Handle the uploads admin API
 *
 * @method mixed checkAssociations(array $args = []) Check if files defined in associations still exist
 * @method mixed createhook(array $args) create file associations for an item - hook for ('item','create','API') - Note: this will only be called if uploads is hooked to that module (e.g.
 *  array{objectid: int|string, extrainfo: array}
 * @method mixed ddConfigure(array $args = [])
 * @method mixed ddConvertValue(array $args = [])
 * @method mixed ddValueNeedsConversion(array $args = [])
 * @method mixed deleteAssociations(array $args) Delete all file associations for a specific module, itemtype [and itemid] [and fileId] - Caution : this tries to remove the file references in the module items too
 *  array{modid: int, itemtype: int, itemid: int, fileId: int}
 * @method mixed deletehook(array $args) delete file associations for an item - hook for ('item','delete','API') - Note: this will only be called if uploads is hooked to that module (e.g.
 *  array{objectid: int|string, extrainfo: mixed}
 * @method mixed getmenulinks(array $args = []) utility function pass individual menu items to the main menu
 * @method mixed removehook(array $args) delete all file associations for a module - hook for ('module','remove','API') - Note: this will only be called if uploads is hooked to that module (e.g.
 *  array{objectid: string, extrainfo: mixed}
 * @method mixed rescanAssociations(array $args) Re-scan all file associations (possibly for a specific module, itemtype and itemid)
 *  array{modid: int, itemtype: int, itemid: int, fileId: int}
 * @method mixed showinput(array $args) show input fields for uploads module (used in DD properties)
 *  array{id: string, value: string, format: string, multiple: bool, methods: array, override?: array, invalid: string}
 * @method mixed updatehook(array $args = []) update file associations for an item - hook for ('item','update','API') - Note: this will only be called if uploads is hooked to that module (e.g.
 * @method mixed validatevalue(array $args) validate input values for uploads module (used in DD properties)
 *  array{id: string, value: string, format: string, multiple: bool, maxsize: int, methods: array, override?: array, moduleid?: int, itemtype?: int, itemid?: int}
 * @extends AdminApiClass<Module>
 */
class AdminApi extends AdminApiClass
{
    // ...
}
