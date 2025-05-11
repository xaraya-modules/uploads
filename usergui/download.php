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
     * @see UserGui::download()
     */
    public function __invoke(array $args = [])
    {
        if (!$this->sec()->checkAccess('ViewUploads')) {
            return;
        }
        extract($args);

        $this->var()->check('file', $fileName, 'str:1:', '');
        $this->var()->check('fileId', $fileId, 'int:1:', 0);

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        $fileInfo = $userapi->dbGetFile(['fileId' => $fileId]);

        if (empty($fileName) && (empty($fileInfo) || !count($fileInfo))) {
            $this->ctl()->redirect(sys::code() . 'modules/uploads/xarimages/notapproved.gif');
            return true;
        }

        if (!empty($fileName)) {
            $fileInfo = $this->session()->getVar($fileName);

            try {
                $result = $userapi->filePush($fileInfo);
            } catch (Exception $e) {
                return $this->mod()->template('errors', ['layout' => 'not_accessible']);
            }

            // Let any hooked modules know that we've just pushed a file
            // the hitcount module in particular needs to know to save the fact
            // that we just pushed a file and not display the count
            $this->var()->setCached('Hooks.hitcount', 'save', 1);

            // File has been pushed to the client, now shut down.
            $this->exit();
            return;
        } else {
            // the file should be the first indice in the array
            $fileInfo = end($fileInfo);

            // Check whether download is permitted
            switch ($this->mod()->getVar('permit_download')) {
                // No download permitted
                case 0:
                    $permitted = false;
                    break;
                    // Personally files only
                case 1:
                    $permitted = $fileInfo['userId'] == $this->user()->getId() ? true : false;
                    break;
                    // Group files only
                case 2:
                    $rawfunction = $this->mod()->getVar('permit_download_function');
                    if (empty($rawfunction)) {
                        $permitted = false;
                    }
                    $funcparts = explode(',', $rawfunction);
                    try {
                        $permitted = $this->mod()->apiFunc($funcparts[0], $funcparts[1], $funcparts[2], ['fileInfo' => $fileInfo]);
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
                return $this->ctl()->notFound();
            }

            $instance[0] = $fileInfo['fileTypeInfo']['typeId'];
            $instance[1] = $fileInfo['fileTypeInfo']['subtypeId'];
            $instance[2] = $this->user()->getId();
            $instance[3] = $fileId;

            $instance = implode(':', $instance);

            // If you are an administrator OR the file is approved, continue
            if ($fileInfo['fileStatus'] != Defines::STATUS_APPROVED && !$this->sec()->check('EditUploads', 0, 'File', $instance)) {
                return $this->mod()->template('errors', ['layout' => 'no_permission']);
            }

            if ($this->sec()->check('ViewUploads', 1, 'File', $instance)) {
                if ($fileInfo['storeType'] & Defines::STORE_FILESYSTEM || ($fileInfo['storeType'] == Defines::STORE_DB_ENTRY)) {
                    if (!file_exists($fileInfo['fileLocation'])) {
                        return $this->mod()->template('errors', ['layout' => 'not_accessible']);
                    }
                } elseif ($fileInfo['storeType'] & Defines::STORE_DB_FULL) {
                    if (!$userapi->dbCountData(['fileId' => $fileInfo['fileId']])) {
                        return $this->mod()->template('errors', ['layout' => 'not_accessible']);
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
                $this->var()->setCached('Hooks.hitcount', 'save', 1);

                // Note: we're ignoring the output from the display hooks here
                xarModHooks::call(
                    'item',
                    'display',
                    $fileId,
                    ['module'    => 'uploads',
                        'itemtype'  => 1, // Files
                        'returnurl' => $this->mod()->getURL( 'user', 'download', ['fileId' => $fileId]), ]
                );

                // File has been pushed to the client, now shut down.
                $this->exit();
                return;
            } else {
                return false;
            }
        }
    }
}
