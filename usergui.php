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

use Xaraya\Modules\UserGuiClass;
use sys;

sys::import('xaraya.modules.usergui');
sys::import('modules.uploads.userapi');

/**
 * Handle the uploads user GUI
 *
 * @method mixed displayAttachments(array $args) display rating for a specific item, and request rating
 *  array{objectid: mixed, extrainfo: mixed, style?: mixed, itemtype: mixed}
 * @method mixed download(array $args = [])
 * @method mixed errors(array $args = [])
 * @method mixed fileProperties(array $args = [])
 * @method mixed main(array $args = []) Return to the download function
 * @method mixed purgeRejected(array $args = [])
 * @method mixed saveAttachments(array $args = []) Save attachments
 * @method mixed upload(array $args) Import a file
 *  array{importFrom: string}
 * @method mixed uploadform(array $args = []) Show the uploads form
 * @extends UserGuiClass<Module>
 */
class UserGui extends UserGuiClass
{
    // ...
}
