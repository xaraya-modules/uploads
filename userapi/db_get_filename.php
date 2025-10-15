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
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi db_get_filename function
 * @extends MethodClass<UserApi>
 */
class DbGetFilenameMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieve the filename for a particular file based on the file id
     * @author Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var int $fileId     (Optional) grab file with the specified file id
     * @return string|bool|void The filename for the particular file
     * @see UserApi::dbFilename()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileId)) {
            $msg = $this->ml('Missing [#(1)] parameter for function [#(2)] in module [#(3)]', 'fileId', 'db_get_filename', 'uploads');
            throw new Exception($msg);
        }

        if (isset($fileId)) {
            if (is_array($fileId)) {
                $where = 'xar_fileEntry_id IN (' . implode(',', $fileId) . ')';
            } elseif (!empty($fileId)) {
                $where = "xar_fileEntry_id = $fileId";
            }
        }

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        // table and column definitions
        $fileEntry_table = $xartable['file_entry'];

        $sql = "SELECT xar_filename
                  FROM $fileEntry_table
                 WHERE $where";

        $result = $dbconn->Execute($sql);

        if (!$result) {
            return;
        }

        // if no record found, return false
        if ($result->EOF) {
            return false;
        }
        $fileName = false;

        while ($result->next()) {
            $row = $result->GetRowAssoc(false);
            if (empty($row)) {
                break;
            }
            $fileName = $row['xar_filename'];
        }
        return $fileName;
    }
}
