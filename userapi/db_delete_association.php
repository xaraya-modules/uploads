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
 * uploads userapi db_delete_association function
 * @extends MethodClass<UserApi>
 */
class DbDeleteAssociationMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Remove an assocation between a particular file and module/itemtype/item.
     * <br />
     * If just the fileId is passed in, all assocations for that file will be deleted.
     * If the fileId and modid are supplied, any assocations for the given file and modid
     * will be removed. The same holds true for itemtype and itemid.
     * @author Carl P. Corliss
     * @access  public
     * @param array<mixed> $args
     * @var integer $fileId    The id of the file we are going to remove association with
     * @var integer $modid     The id of module this file is associated with
     * @var integer $itemtype  The item type within the defined module
     * @var integer $itemid    The id of the item types item
     *
     * @return bool TRUE on success, FALSE with exception on error
     * @see UserApi::dbDeleteAssociation()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $whereList = [];
        $bindvars = [];

        if (!isset($fileId)) {
        } elseif (is_array($fileId)) {
            $whereList[] = ' (xar_fileEntry_id IN (' . implode(',', $fileId) . ') ) ';
        } else {
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

        //add to uploads table
        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        // table and column definitions
        $file_assoc_table   = $xartable['file_associations'];

        // insert value into table
        $sql = "DELETE
                  FROM $file_assoc_table
                $where";

        $result = &$dbconn->Execute($sql, $bindvars);

        if (!$result) {
            return false;
        } else {
            return true;
        }
    }
}
