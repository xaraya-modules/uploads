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
 * uploads userapi db_add_file_data function
 * @extends MethodClass<UserApi>
 */
class DbAddFileDataMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Adds a file's  contents to the database. This only takes 4K (4096 bytes) blocks.
     * So a file's data could potentially be contained amongst many records. This is done to
     * ensure that we are able to actually save the whole file in the db.
     * @author Carl P. Corliss
     * @access  public
     * @param array<mixed> $args
     * @var integer $fileId     The ID of the file this data belongs to
     * @var string  $fileData   A line of data from the file to be stored (no greater than 65535 bytes)
     *
     * @return integer The id of the fileData that was added, or FALSE on error
     * @see UserApi::dbAddFileData()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileId)) {
            $msg = $this->ml(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'fileId',
                'db_add_file_data',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!isset($fileData)) {
            $msg = $this->ml(
                'Missing parameter [#(1)] for function [#(2)] in module (#3)]',
                'location',
                'db_add_file_data',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (sizeof($fileData) >= (1024 * 64)) {
            $msg = $this->ml('#(1) exceeds maximum storage limit of 64KB per data chunk.', 'fileData');
            throw new Exception($msg);
        }

        $fileData = base64_encode($fileData);

        //add to uploads table
        // Get database setup
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();


        // table and column definitions
        $fileData_table = $xartable['file_data'];
        $fileDataID    = $dbconn->genID($fileData_table);

        // insert value into table
        $sql = "INSERT INTO $fileData_table
                          (
                            xar_fileEntry_id,
                            xar_fileData_id,
                            xar_fileData
                          )
                   VALUES
                          (
                            $fileId,
                            $fileDataID,
                            '$fileData'
                          )";
        $result = &$dbconn->Execute($sql);

        if (!$result) {
            return false;
        } else {
            $id = $dbconn->PO_Insert_ID($xartable['file_data'], 'id');
            return $id;
        }
    }
}
