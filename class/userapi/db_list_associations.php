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
 * uploads userapi db_list_associations function
 * @extends MethodClass<UserApi>
 */
class DbListAssociationsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieve a list of (item - file) associations for a particular module/itemtype combination
     * @author Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var int $modid     The id of module this file is associated with
     * @var int $itemtype  The item type within the defined module
     * @var int $itemid    The id of the item types item
     * @var int $fileId    The id of the file we are going to associate with an item
     * @return array A list of associations, including the itemid -> fileId
     * @see UserApi::dbListAssociations()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($modid)) {
            return [];
        }

        $whereList = [];
        $bindvars = [];

        if (isset($fileId)) {
            $whereList[] = ' (xar_fileEntry_id = ?) ';
            $bindvars[] = (int) $fileId;
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

        $sql = "SELECT
                        xar_modid,
                        xar_itemtype,
                        xar_objectid,
                        xar_fileEntry_id
                FROM $file_assoc_table
                $where
                ORDER BY xar_objectid ASC";

        if (!empty($numitems)) {
            if (empty($startnum)) {
                $startnum = 1;
            }
            $result = $dbconn->SelectLimit($sql, $numitems, $startnum - 1, $bindvars);
        } else {
            $result = $dbconn->Execute($sql, $bindvars);
        }

        if (!$result) {
            return [];
        }

        // if no record found, return an empty array
        if ($result->EOF) {
            return [];
        }

        $list = [];
        while (!$result->EOF) {
            [$modid, $itemtype, $itemid, $fileId] = $result->fields;
            // simple item - file array
            if (!isset($list[$itemid])) {
                $list[$itemid] = [];
            }
            $list[$itemid][] = (int) $fileId;
            $result->MoveNext();
        }
        return $list;
    }
}
