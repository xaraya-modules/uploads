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
 * uploads userapi db_add_association function
 * @extends MethodClass<UserApi>
 */
class DbAddAssociationMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Create an assocation between a (stored) file and a module/itemtype/item
     * @author  Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var integer $fileId    The id of the file we are going to associate with an item
     * @var integer $modid     The id of module this file is associated with
     * @var integer $itemtype  The item type within the defined module
     * @var integer $itemid    The id of the item types item
     *
     * @return integer The id of the file that was associated, FALSE with exception on error
     * @see UserApi::dbAddAssociation()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileId)) {
            $msg = $this->ml(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'fileId',
                'db_add_assocation',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!isset($modid)) {
            $msg = $this->ml(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'modid',
                'db_add_assocation',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!isset($itemtype)) {
            $itemtype = 0;
        }

        if (!isset($itemid)) {
            $itemid = 0;
        }

        //add to uploads table
        // Get database setup
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();

        // table and column definitions
        $file_assoc_table = $xartable['file_associations'];

        // insert value into table
        $sql = "INSERT INTO $file_assoc_table
                          (
                            xar_fileEntry_id,
                            xar_modid,
                            xar_itemtype,
                            xar_objectid
                          )
                   VALUES
                          ( ?, ?, ?, ? )";

        $bindvars = [(int) $fileId,(int) $modid,(int) $itemtype,(int) $itemid];
        $result = &$dbconn->Execute($sql, $bindvars);

        if (!$result) {
            return false;
        } else {
            return $fileId  ;
        }
    }
}
