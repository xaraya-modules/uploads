<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserApi;

use Xaraya\Modules\MethodClass;
use xarMod;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi purge_files function
 */
class PurgeFilesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Takes a list of files and deletes them
     * @author Carl P. Corliss
     * @access public
     * @param array fileList    List of files to delete containing complete fileName => fileInfo arrays
     * @return bool true if successful, false otherwise
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileList)) {
            $msg = xarML(
                'Missing required parameter [#(1)] for API function [#(2)] in module [#(3)]',
                'fileList',
                'purge_files',
                'uploads'
            );
            throw new Exception($msg);
        }

        foreach ($fileList as $fileName => $fileInfo) {
            if ($fileInfo['storeType'] & _UPLOADS_STORE_FILESYSTEM) {
                xarMod::apiFunc('uploads', 'user', 'file_delete', ['fileName' => $fileInfo['fileLocation']]);
            }

            if ($fileInfo['storeType'] & _UPLOADS_STORE_DB_DATA) {
                xarMod::apiFunc('uploads', 'user', 'db_delete_file_data', ['fileId' => $fileInfo['fileId']]);
            }

            // go ahead and delete the file from the database.
            xarMod::apiFunc('uploads', 'user', 'db_delete_file', ['fileId' => $fileInfo['fileId']]);
        }

        return true;
    }
}
