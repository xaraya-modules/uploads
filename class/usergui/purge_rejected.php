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

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\UserGui;
use Xaraya\Modules\Uploads\UserApi;
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
 * @extends MethodClass<UserGui>
 */
class PurgeRejectedMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * @see UserGui::purgeRejected()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!$this->checkAccess('ManageUploads')) {
            return;
        }

        if (isset($authid)) {
            $_GET['authid'] = $authid;
        }

        if (!isset($confirmation)) {
            $this->fetch('confirmation', 'int:1:', $confirmation, '', xarVar::NOT_REQUIRED);
        }
        // Confirm authorisation code.
        if (!$this->confirmAuthKey()) {
            return;
        }
        $usergui = $this->getParent();

        /** @var UserApi $userapi */
        $userapi = $usergui->getAPI();

        if ((isset($confirmation) && $confirmation) || !$this->getModVar('file.delete-confirmation')) {
            $fileList = $userapi->dbGetFile([
                'fileStatus' => Defines::STATUS_REJECTED,
            ]);

            if (empty($fileList)) {
                $this->redirect($this->getUrl('admin', 'view'));
                return;
            } else {
                $result = $userapi->purgeFiles([
                    'fileList'   => $fileList,
                ]);
                if (!$result) {
                    $msg = $this->translate('Unable to purge rejected files!');
                    throw new Exception($msg);
                }
            }
        } else {
            $fileList = $userapi->dbGetFile([
                'fileStatus' => Defines::STATUS_REJECTED,
            ]);
            if (empty($fileList)) {
                $data['fileList']   = [];
            } else {
                $data['fileList']   = $fileList;
            }
            $data['authid']     = $this->genAuthKey();

            return $data;
        }

        $this->redirect($this->getUrl('admin', 'view'));
    }
}
