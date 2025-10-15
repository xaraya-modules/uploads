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

use Xaraya\Modules\Uploads\AdminApi;
use Xaraya\Modules\MethodClass;
use sys;

sys::import('xaraya.modules.method');

/**
 * uploads adminapi check_associations function
 * @extends MethodClass<AdminApi>
 */
class CheckAssociationsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Check if files defined in associations still exist
     * @author  mikespub
     * @access public
     *
     * @return mixed list of associations with missing files on success, void with exception on error
     * @see AdminApi::checkAssociations()
     */
    public function __invoke(array $args = [])
    {
        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        // table definitions
        $file_assoc_table = $xartable['file_associations'];
        $file_entry_table = $xartable['file_entry'];

        // CHECKME: verify this for different databases
        // find file associations without corresponding file entry
        $sql = "SELECT
                       $file_assoc_table.xar_fileEntry_id,
                       $file_assoc_table.xar_modid,
                       $file_assoc_table.xar_itemtype,
                       $file_assoc_table.xar_objectid
                  FROM $file_assoc_table
             LEFT JOIN $file_entry_table
                    ON $file_assoc_table.xar_fileEntry_id = $file_entry_table.xar_fileEntry_id
                 WHERE $file_entry_table.xar_filename IS NULL";

        $result = $dbconn->Execute($sql);

        if (!$result) {
            return [];
        }

        $list = [];
        while ($result->next()) {
            if (empty($result->fields)) {
                break;
            }
            [$fileId, $modid, $itemtype, $itemid] = $result->fields;
            // simple item - file array
            if (!isset($list[$fileId])) {
                $list[$fileId] = 0;
            }
            $list[$fileId]++;
        }
        return $list;
    }
}
