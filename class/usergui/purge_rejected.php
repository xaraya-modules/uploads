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
use xarMod;
use xarSecurity;
use xarVar;
use xarSec;
use xarModVars;
use xarController;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads user purge_rejected function
 */
class PurgeRejectedMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    public function __invoke(array $args = [])
    {
        extract($args);

        if (!xarSecurity::check('ManageUploads')) {
            return;
        }

        if (isset($authid)) {
            $_GET['authid'] = $authid;
        }

        if (!isset($confirmation)) {
            xarVar::fetch('confirmation', 'int:1:', $confirmation, '', xarVar::NOT_REQUIRED);
        }
        // Confirm authorisation code.
        if (!xarSec::confirmAuthKey()) {
            return;
        }


        if ((isset($confirmation) && $confirmation) || !xarModVars::get('uploads', 'file.delete-confirmation')) {
            $fileList = xarMod::apiFunc(
                'uploads',
                'user',
                'db_get_file',
                ['fileStatus' => _UPLOADS_STATUS_REJECTED]
            );

            if (empty($fileList)) {
                xarController::redirect(xarController::URL('uploads', 'admin', 'view'), null, $this->getContext());
                return;
            } else {
                $result = xarMod::apiFunc(
                    'uploads',
                    'user',
                    'purge_files',
                    ['fileList'   => $fileList]
                );
                if (!$result) {
                    $msg = xarML('Unable to purge rejected files!');
                    throw new Exception($msg);
                }
            }
        } else {
            $fileList = xarMod::apiFunc(
                'uploads',
                'user',
                'db_get_file',
                ['fileStatus' => _UPLOADS_STATUS_REJECTED]
            );
            if (empty($fileList)) {
                $data['fileList']   = [];
            } else {
                $data['fileList']   = $fileList;
            }
            $data['authid']     = xarSec::genAuthKey();

            return $data;
        }

        xarController::redirect(xarController::URL('uploads', 'admin', 'view'), null, $this->getContext());
    }
}
