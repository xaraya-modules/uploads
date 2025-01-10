<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserGui;

use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarMod;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads user upload function
 */
class UploadMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Import a file
     * @param string importFrom
     * @return mixed
     */
    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('AddUploads')) {
            return;
        }

        xarVar::fetch('importFrom', 'str:1:', $importFrom, null, xarVar::NOT_REQUIRED);

        $list = xarMod::apiFunc(
            'uploads',
            'user',
            'process_files',
            ['importFrom' => $importFrom]
        );

        if (is_array($list) && count($list)) {
            return ['fileList' => $list];
        } else {
            xarController::redirect(xarController::URL('uploads', 'user', 'uploadform'), null, $this->getContext());
        }
    }
}
