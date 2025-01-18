<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\AdminGui;

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\AdminGui;
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
 * uploads admin purge_rejected function
 * @extends MethodClass<AdminGui>
 */
class PurgeRejectedMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * @see AdminGui::purgeRejected()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!$this->sec()->checkAccess('ManageUploads')) {
            return;
        }

        if (isset($authid)) {
            $_GET['authid'] = $authid;
        }

        if (!isset($confirmation)) {
            $this->var()->find('confirmation', $confirmation, 'int:1:', '');
        }
        // Confirm authorisation code.
        if (!$this->sec()->confirmAuthKey()) {
            return;
        }
        $admingui = $this->getParent();

        /** @var UserApi $userapi */
        $userapi = $admingui->getAPI();

        if ((isset($confirmation) && $confirmation) || !$this->mod()->getVar('file.delete-confirmation')) {
            $fileList = $userapi->dbGetFile([
                'fileStatus' => Defines::STATUS_REJECTED,
            ]);

            if (empty($fileList)) {
                $this->ctl()->redirect($this->mod()->getURL('admin', 'view'));
                return;
            } else {
                $result = $userapi->purgeFiles([
                    'fileList' => $fileList,
                ]);
                if (!$result) {
                    $msg = $this->ml('Unable to purge rejected files!');
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
            $data['authid']     = $this->sec()->genAuthKey();

            return $data;
        }

        $this->ctl()->redirect($this->mod()->getURL('admin', 'view'));
    }
}
