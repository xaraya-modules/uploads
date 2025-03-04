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

use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use xarDB;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi db_get_file_data function
 * @extends MethodClass<UserApi>
 */
class DbGetFileDataMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieve the DATA (contents) stored for a particular file based on
     *  the file id. This returns an array not unlike the php function
     *  'file()' whereby the contents of the file are in an ordered array.
     * The contents can be put back together by doing: implode('',
     * @author Carl P. Corliss
     * @author Micheal Cortez
     * @access public
     * @param array<mixed> $args
     * @var int $fileId     The ID of the file we are are retrieving
     * @return array|void All the (4K) blocks stored for this file
     * @see UserApi::dbGetFileData()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileId)) {
            $msg = $this->ml(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'fileId',
                'db_get_file_data',
                'uploads'
            );
            throw new Exception($msg);
        }

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        // table definition
        $fileData_table = $xartable['file_data'];

        $sql = "SELECT xar_fileEntry_id,
                       xar_fileData_id,
                       xar_fileData
                  FROM $fileData_table
                 WHERE xar_fileEntry_id = $fileId
              ORDER BY xar_fileData_id ASC";

        $result = $dbconn->Execute($sql);

        if (!$result) {
            return;
        }

        // if no record found, return an empty array
        if ($result->EOF) {
            return [];
        }

        while ($result->next()) {
            $row = $result->GetRowAssoc(false);
            if (empty($row)) {
                break;
            }
            $fileData[$row['xar_filedata_id']] = base64_decode($row['xar_filedata']);
        }
        $result->Close();

        return $fileData;
    }
}
