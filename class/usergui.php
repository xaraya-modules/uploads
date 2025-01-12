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
sys::import('modules.uploads.class.userapi');

/**
 * Handle the uploads user GUI
 *
 * @method mixed displayAttachments(array $args)
 * @method mixed download(array $args)
 * @method mixed errors(array $args)
 * @method mixed fileProperties(array $args)
 * @method mixed main(array $args)
 * @method mixed purgeRejected(array $args)
 * @method mixed saveAttachments(array $args)
 * @method mixed upload(array $args)
 * @method mixed uploadform(array $args)
 * @extends UserGuiClass<Module>
 */
class UserGui extends UserGuiClass
{
    /**
     * User main GUI function
     * @param array<string, mixed> $args
     * @return array<mixed>
     */
    public function main(array $args = [])
    {
        $args['description'] ??= 'Description of uploads';

        // Pass along the context for xarTpl::module() if needed
        $args['context'] ??= $this->getContext();
        return $args;
    }
}
