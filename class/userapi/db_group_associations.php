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
use xarSecurity;
use xarDB;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi db_group_associations function
 * @extends MethodClass<UserApi>
 */
class DbGroupAssociationsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get the list of modules and itemtypes we're associating files with
     * @return array|bool|void $array[$modid][$itemtype] = array('items' => $numitems,'files' => $numfiles,'links' => $numlinks);
     * @see UserApi::dbGroupAssociations()
     */
    public function __invoke(array $args = [])
    {
        // Get arguments from argument array
        extract($args);

        // Security check
        if (!$this->sec()->checkAccess('ViewUploads')) {
            return;
        }

        if (empty($fileId) || !is_numeric($fileId)) {
            $fileId = 0;
        }

        // Database information
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();
        $fileassoctable = $xartable['file_associations'];

        if ($dbconn->databaseType == 'sqlite') {
            // TODO: see if we can't do this some other way in SQLite

            $bindvars = [];
            // Get links
            $sql = "SELECT xar_modid, xar_itemtype, COUNT(*)
                    FROM $fileassoctable";
            if (!empty($fileId)) {
                $sql .= " WHERE xar_fileEntry_id = ?";
                $bindvars[] = $fileId;
            }
            $sql .= " GROUP BY xar_modid, xar_itemtype";

            $result = $dbconn->Execute($sql, $bindvars);
            if (!$result) {
                return;
            }

            $modlist = [];
            while (!$result->EOF) {
                if (empty($result->fields)) {
                    break;
                }
                [$modid, $itemtype, $numlinks] = $result->fields;
                if (!isset($modlist[$modid])) {
                    $modlist[$modid] = [];
                }
                $modlist[$modid][$itemtype] = ['items' => 0, 'files' => 0, 'links' => $numlinks];
                $result->MoveNext();
            }
            $result->close();

            // Get items
            $sql = "SELECT xar_modid, xar_itemtype, COUNT(*)
                    FROM (SELECT DISTINCT xar_objectid, xar_modid, xar_itemtype
                          FROM $fileassoctable";
            if (!empty($fileId)) {
                $sql .= " WHERE xar_fileEntry_id = ?";
                $bindvars[] = $fileId;
            }
            $sql .= ") GROUP BY xar_modid, xar_itemtype";

            $result = $dbconn->Execute($sql, $bindvars);
            if (!$result) {
                return;
            }

            while (!$result->EOF) {
                if (empty($result->fields)) {
                    break;
                }
                [$modid, $itemtype, $numitems] = $result->fields;
                $modlist[$modid][$itemtype]['items'] = $numitems;
                $result->MoveNext();
            }
            $result->close();

            // Get files
            $sql = "SELECT xar_modid, xar_itemtype, COUNT(*)
                    FROM (SELECT DISTINCT xar_fileEntry_id, xar_modid, xar_itemtype
                          FROM $fileassoctable";
            if (!empty($fileId)) {
                $sql .= " WHERE xar_fileEntry_id = ?";
                $bindvars[] = $fileId;
            }
            $sql .= ") GROUP BY xar_modid, xar_itemtype";

            $result = $dbconn->Execute($sql, $bindvars);
            if (!$result) {
                return;
            }

            while (!$result->EOF) {
                if (empty($result->fields)) {
                    break;
                }
                [$modid, $itemtype, $numfiles] = $result->fields;
                $modlist[$modid][$itemtype]['files'] = $numfiles;
                $result->MoveNext();
            }
            $result->close();
        } else {
            $bindvars = [];
            // Get items
            $sql = "SELECT xar_modid, xar_itemtype, COUNT(*), COUNT(DISTINCT xar_objectid), COUNT(DISTINCT xar_fileEntry_id)
                    FROM $fileassoctable";
            if (!empty($fileId)) {
                $sql .= " WHERE xar_fileEntry_id = ?";
                $bindvars[] = $fileId;
            }
            $sql .= " GROUP BY xar_modid, xar_itemtype";

            $result = $dbconn->Execute($sql, $bindvars);
            if (!$result) {
                return;
            }

            $modlist = [];
            while (!$result->EOF) {
                if (empty($result->fields)) {
                    break;
                }
                [$modid, $itemtype, $numlinks, $numitems, $numfiles] = $result->fields;
                if (!isset($modlist[$modid])) {
                    $modlist[$modid] = [];
                }
                $modlist[$modid][$itemtype] = ['items' => $numitems, 'files' => $numfiles, 'links' => $numlinks];
                $result->MoveNext();
            }
            $result->close();
        }

        return $modlist;
    }
}
