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


use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use xarDB;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi db_count_data function
 * @extends MethodClass<UserApi>
 */
class DbCountDataMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieve the total count of data blocks stored for a particular file
     * @author Carl P. Corliss
     * @author Micheal Cortez
     * @access public
     * @param int fileId     (Optional) grab file with the specified file id
     * @return int The total number of DATA Blocks stored for a particular file
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $where = [];

        if (!isset($fileId)) {
            $msg = xarML(
                'Missing parameter [#(1)] for API function [#(2)] in module [#(3)]',
                'fileId',
                'db_count_data',
                'uploads'
            );
            throw new Exception($msg);
        }

        // Get database setup
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();

        // table and column definitions
        $fileEntry_table = $xartable['file_data'];

        $sql = "SELECT COUNT(xar_fileData_id) AS total
                  FROM $fileEntry_table
                 WHERE xar_fileEntry_id = $fileId";

        $result = $dbconn->Execute($sql);

        if (!$result) {
            return false;
        }

        // if no record found, return an empty array
        if ($result->EOF) {
            return (int) 0;
        }

        $row = $result->GetRowAssoc(false);

        return $row['total'];
    }
}
