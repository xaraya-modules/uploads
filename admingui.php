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

use Xaraya\Modules\AdminGuiClass;

/**
 * Handle the uploads admin GUI
 *
 * @method mixed assoc(array $args = []) View statistics about file associations (adapted from categories stats)
 * @method mixed getFiles(array $args = [])
 * @method mixed main(array $args = []) The main administration function - This function redirects the user to the view function
 * @method mixed modifyconfig(array $args = []) Modify the configuration for the Uploads module
 * @method mixed newhook(array $args = [])
 * @method mixed overview(array $args = []) Overview displays standard Overview page
 * @method mixed privileges(array $args = []) Manage definition of instances for privileges (unfinished)
 * @method mixed purgeRejected(array $args = [])
 * @method mixed updateconfig(array $args = []) Update the configuration
 * @method mixed view(array $args) The view function for the site admin
 *  array{mimetype: int, subtype: int, status: int, inverse: bool, fileId: int, fileDo: string, action: int, startnum: int, numitems: int, sort: string, catid: string}
 * @method mixed waitingcontent(array $args = []) Waiting content hook - display waiting content as a hook
 * @extends AdminGuiClass<Module>
 */
class AdminGui extends AdminGuiClass
{
    // ...
}
