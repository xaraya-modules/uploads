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
use xarSecurity;
use xarVar;
use xarMod;
use xarController;
use xarSession;
use xarTpl;
use xarModVars;
use xarModHooks;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads user download function
 * @extends MethodClass<UserGui>
 */
class DownloadMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Summary of __invoke
     * @param array<mixed> $args
     * @return bool|string|void
     */
    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('ViewUploads')) {
            return;
        }

        if (!xarVar::fetch('file', 'str:1:', $fileName, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('fileId', 'int:1:', $fileId, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        $usergui = $this->getParent();

        /** @var UserApi $userapi */
        $userapi = $usergui->getAPI();

        $fileInfo = $userapi->dbGetFile(['fileId' => $fileId]);

        if (empty($fileName) && (empty($fileInfo) || !count($fileInfo))) {
            xarController::redirect(sys::code() . 'modules/uploads/xarimages/notapproved.gif', null, $this->getContext());
            return true;
        }

        if (!empty($fileName)) {
            $fileInfo = xarSession::getVar($fileName);

            try {
                $result = $userapi->filePush($fileInfo);
            } catch (Exception $e) {
                return xarTpl::module('uploads', 'user', 'errors', ['layout' => 'not_accessible']);
            }

            // Let any hooked modules know that we've just pushed a file
            // the hitcount module in particular needs to know to save the fact
            // that we just pushed a file and not display the count
            xarVar::setCached('Hooks.hitcount', 'save', 1);

            // File has been pushed to the client, now shut down.
            exit();
        } else {
            // the file should be the first indice in the array
            $fileInfo = end($fileInfo);

            // Check whether download is permitted
            switch (xarModVars::get('uploads', 'permit_download')) {
                // No download permitted
                case 0:
                    $permitted = false;
                    break;
                    // Personally files only
                case 1:
                    $permitted = $fileInfo['userId'] == xarSession::getVar('role_id') ? true : false;
                    break;
                    // Group files only
                case 2:
                    $rawfunction = xarModVars::get('uploads', 'permit_download_function');
                    if (empty($rawfunction)) {
                        $permitted = false;
                    }
                    $funcparts = explode(',', $rawfunction);
                    try {
                        $permitted = xarMod::apiFunc($funcparts[0], $funcparts[1], $funcparts[2], ['fileInfo' => $fileInfo]);
                    } catch (Exception $e) {
                        $permitted = false;
                    }
                    break;
                    // All files
                case 3:
                    $permitted = true;
                    break;
            }
            if (!$permitted) {
                return xarController::notFound(null, $this->getContext());
            }

            $instance[0] = $fileInfo['fileTypeInfo']['typeId'];
            $instance[1] = $fileInfo['fileTypeInfo']['subtypeId'];
            $instance[2] = xarSession::getVar('uid');
            $instance[3] = $fileId;

            $instance = implode(':', $instance);

            // If you are an administrator OR the file is approved, continue
            if ($fileInfo['fileStatus'] != Defines::STATUS_APPROVED && !xarSecurity::check('EditUploads', 0, 'File', $instance)) {
                return xarTpl::module('uploads', 'user', 'errors', ['layout' => 'no_permission']);
            }

            if (xarSecurity::check('ViewUploads', 1, 'File', $instance)) {
                if ($fileInfo['storeType'] & Defines::STORE_FILESYSTEM || ($fileInfo['storeType'] == Defines::STORE_DB_ENTRY)) {
                    if (!file_exists($fileInfo['fileLocation'])) {
                        return xarTpl::module('uploads', 'user', 'errors', ['layout' => 'not_accessible']);
                    }
                } elseif ($fileInfo['storeType'] & Defines::STORE_DB_FULL) {
                    if (!$userapi->dbCountData(['fileId' => $fileInfo['fileId']])) {
                        return xarTpl::module('uploads', 'user', 'errors', ['layout' => 'not_accessible']);
                    }
                }

                $result = $userapi->filePush($fileInfo);

                /*
                if (!$result) {
                    // now just return and let the error bubble up
                    return FALSE;
                }
                */

                // Let any hooked modules know that we've just pushed a file
                // the hitcount module in particular needs to know to save the fact
                // that we just pushed a file and not display the count
                xarVar::setCached('Hooks.hitcount', 'save', 1);

                // Note: we're ignoring the output from the display hooks here
                xarModHooks::call(
                    'item',
                    'display',
                    $fileId,
                    ['module'    => 'uploads',
                        'itemtype'  => 1, // Files
                        'returnurl' => xarController::URL('uploads', 'user', 'download', ['fileId' => $fileId]), ]
                );

                // File has been pushed to the client, now shut down.
                exit();
            } else {
                return false;
            }
        }
    }
}
