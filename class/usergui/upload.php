<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserGui;

use Xaraya\Modules\Uploads\UserGui;
use Xaraya\Modules\Uploads\UserApi;
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
 * @extends MethodClass<UserGui>
 */
class UploadMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Import a file
     * @param array<mixed> $args
     * @var string $importFrom
     * @return mixed
     */
    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('AddUploads')) {
            return;
        }
        $usergui = $this->getParent();

        /** @var UserApi $userapi */
        $userapi = $usergui->getAPI();

        xarVar::fetch('importFrom', 'str:1:', $importFrom, null, xarVar::NOT_REQUIRED);

        $list = $userapi->processFiles([
            'importFrom' => $importFrom,
        ]);

        if (is_array($list) && count($list)) {
            return ['fileList' => $list];
        } else {
            xarController::redirect(xarController::URL('uploads', 'user', 'uploadform'), null, $this->getContext());
        }
    }
}
