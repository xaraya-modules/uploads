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
 * @method mixed checkAssociations(array $args = [])
 * @method mixed createhook(array $args)
 * @method mixed ddConfigure(array $args)
 * @method mixed ddConvertValue(array $args)
 * @method mixed ddValueNeedsConversion(array $args)
 * @method mixed deleteAssociations(array $args)
 * @method mixed deletehook(array $args)
 * @method mixed getmenulinks(array $args)
 * @method mixed removehook(array $args)
 * @method mixed rescanAssociations(array $args)
 * @method mixed showinput(array $args)
 * @method mixed updatehook(array $args)
 * @method mixed validatevalue(array $args)
 * @extends AdminApiClass<Module>
 */
class AdminApi extends AdminApiClass
{
    // ...
}
