<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\AdminApi;

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\AdminApi;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use xarServer;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads adminapi dd_convert_value function
 * @extends MethodClass<AdminApi>
 */
class DdConvertValueMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Summary of __invoke
     * @param array<mixed> $args
     * @throws \Exception
     * @return mixed
     * @see AdminApi::ddConvertValue()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($value)) {
            return null;
        }
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        if (!isset($basedir)) {
            // try something here in hopes that it works.
            $basedir = 'var/uploads/';
        }

        // if conversion isn't needed, then don't do it
        if (!$adminapi->ddValueNeedsConversion(['value' => $value])) {
            return $value;
        }

        if (!isset($basePath)) {
            if (xarServer::getVar('SCRIPT_FILENAME')) {
                $base_directory = dirname(realpath(xarServer::getVar('SCRIPT_FILENAME')));
            } else {
                $base_directory = './';
            }

            $basePath = $base_directory;
        }

        if (file_exists($basedir . $value) && !is_file($basedir . $value)) {
            $this->mod()->apiLoad('uploads', 'user');

            /** @var UserApi $userapi */
            $userapi = $this->userapi();

            $args['import'] = 'file://' . $basePath . '/' . $basedir . $value;
            $args['action'] = Defines::GET_EXTERNAL;
            $list = $userapi->processFiles($args);
            $storeList = [];
            foreach ($list as $file => $fileInfo) {
                if (!isset($fileInfo['errors'])) {
                    $storeList[] = $fileInfo['fileId'];
                } else {
                    $msg = $this->ml('Error Found: #(1)', $fileInfo['errors'][0]['errorMesg']);
                    throw new Exception($msg);
                }
            }

            if (is_array($storeList) && count($storeList)) {
                // We prepend a semicolon onto the list of fileId's so that
                // we can tell, in the future, that this is a list of fileIds
                // and not just a filename

                return $value = ';' . implode(';', $storeList);
            } else {
                // if we've managed to get here, then just return the original value
                return $value;
            }
        } else {
            // do nothing for now - until i find a way to ensure that
            // all files can be migrated...
            // if we've managed to get here, then just return the original value
            return $value;
        }
    }
}
