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
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi db_count_associations function
 * @extends MethodClass<UserApi>
 */
class DbCountAssociationsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieve the total count associations for a particular file/module/itemtype/item combination
     * @author Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var mixed $fileId    The id of the file, or an array of fileId's
     * @var int $modid     The id of module this file is associated with
     * @var int $itemtype  The item type within the defined module
     * @var int $itemid    The id of the item types item
     * @return mixed The total number of associations for particular file/module/itemtype/item combination
     * or an array of fileId's and their number of associations
     * @see UserApi::dbCountAssociations()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $whereList = [];
        $bindvars = [];

        if (isset($fileId)) {
            if (is_array($fileId)) {
                $whereList[] = ' (xar_fileEntry_id IN (' . implode(',', $fileId) . ') ) ';
                $isgrouped = 1;
            } else {
                $whereList[] = ' (xar_fileEntry_id = ?) ';
                $bindvars[] = (int) $fileId;
            }
        }

        if (isset($modid)) {
            $whereList[] = ' (xar_modid = ?) ';
            $bindvars[] = (int) $modid;

            if (isset($itemtype)) {
                $whereList[] = ' (xar_itemtype = ?) ';
                $bindvars[] = (int) $itemtype;

                if (isset($itemid)) {
                    $whereList[] = ' (xar_objectid = ?) ';
                    $bindvars[] = (int) $itemid;
                }
            }
        }

        if (count($whereList)) {
            $where = 'WHERE ' . implode(' AND ', $whereList);
        } else {
            $where = '';
        }

        // Get database setup
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();

        // table and column definitions
        $file_assoc_table = $xartable['file_associations'];

        if (empty($isgrouped)) {
            $sql = "SELECT COUNT(xar_fileEntry_id) AS total
                    FROM $file_assoc_table
                    $where";

            $result = $dbconn->Execute($sql, $bindvars);

            if (!$result) {
                return false;
            }

            // if no record found, return zero
            if ($result->EOF) {
                return 0;
            }

            $row = $result->GetRowAssoc(false);

            return $row['total'];
        } else {
            $sql = "SELECT xar_fileEntry_id, COUNT(*) AS total
                    FROM $file_assoc_table
                    $where
                    GROUP BY xar_fileEntry_id";

            $result = $dbconn->Execute($sql, $bindvars);

            if (!$result) {
                return false;
            }

            $count = [];
            while (!$result->EOF) {
                [$file, $total] = $result->fields;
                $count[$file] = $total;

                $result->MoveNext();
            }

            return $count;
        }
    }
}
