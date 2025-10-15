<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserApi;

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi purge_files function
 * @extends MethodClass<UserApi>
 */
class PurgeFilesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Takes a list of files and deletes them
     * @author Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var array $fileList    List of files to delete containing complete fileName => fileInfo arrays
     * @return bool true if successful, false otherwise
     * @see UserApi::purgeFiles()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileList)) {
            $msg = $this->ml(
                'Missing required parameter [#(1)] for API function [#(2)] in module [#(3)]',
                'fileList',
                'purge_files',
                'uploads'
            );
            throw new Exception($msg);
        }
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        foreach ($fileList as $fileName => $fileInfo) {
            if ($fileInfo['storeType'] & Defines::STORE_FILESYSTEM) {
                $userapi->fileDelete(['fileName' => $fileInfo['fileLocation']]);
            }

            if ($fileInfo['storeType'] & Defines::STORE_DB_DATA) {
                $userapi->dbDeleteFileData(['fileId' => $fileInfo['fileId']]);
            }

            // go ahead and delete the file from the database.
            $userapi->dbDeleteFile(['fileId' => $fileInfo['fileId']]);
        }

        return true;
    }
}
