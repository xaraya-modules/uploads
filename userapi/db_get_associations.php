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

sys::import('xaraya.modules.method');

/**
 * uploads userapi db_get_associations function
 * @extends MethodClass<UserApi>
 */
class DbGetAssociationsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieve a list of file assocations for a particular file/module/itemtype/item combination
     * @author Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var int $modid     The id of module this file is associated with
     * @var int $itemtype  The item type within the defined module
     * @var int $itemid    The id of the item types item
     * @var int $fileId    The id of the file we are going to associate with an item
     * @return array A list of associations, including the fileId -> (fileId + modid + itemtype + itemid)
     * @see UserApi::dbGetAssociations()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

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
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        // table and column definitions
        $file_assoc_table = $xartable['file_associations'];

        $sql = "SELECT
                        xar_fileEntry_id,
                        xar_modid,
                        xar_itemtype,
                        xar_objectid
                  FROM $file_assoc_table
                $where";

        $result = $dbconn->Execute($sql, $bindvars);

        if (!$result) {
            return [];
        }

        // if no record found, return an empty array
        if ($result->EOF) {
            return [];
        }

        $fileList = [];
        while ($result->next()) {
            $row = $result->GetRowAssoc(false);
            if (empty($row)) {
                break;
            }

            $fileAssoc['fileId']   = $row['xar_fileEntry_id'];
            $fileAssoc['modid']    = $row['xar_modid'];
            $fileAssoc['itemtype'] = $row['xar_itemtype'];
            $fileAssoc['itemid']   = $row['xar_objectid'];

            // Note: only one association is returned per file here !
            $fileList[$fileAssoc['fileId']] = $fileAssoc;
        }
        return $fileList;
    }
}
