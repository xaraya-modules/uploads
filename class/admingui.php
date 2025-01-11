<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 **/

namespace Xaraya\Modules\Uploads;

use Xaraya\Modules\AdminGuiClass;
use sys;

sys::import('xaraya.modules.admingui');
sys::import('modules.uploads.class.adminapi');

/**
 * Handle the uploads admin GUI
 *
 * @method mixed assoc(array $args)
 * @method mixed getFiles(array $args)
 * @method mixed main(array $args)
 * @method mixed modifyconfig(array $args)
 * @method mixed newhook(array $args)
 * @method mixed overview(array $args)
 * @method mixed privileges(array $args)
 * @method mixed purgeRejected(array $args)
 * @method mixed updateconfig(array $args)
 * @method mixed view(array $args)
 * @method mixed waitingcontent(array $args)
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    // ...
}
