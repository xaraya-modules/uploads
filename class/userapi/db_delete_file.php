<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserApi;

use Xaraya\Modules\MethodClass;
use xarDB;
use xarModHooks;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi db_delete_file function
 */
class DbDeleteFileMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Remove a file entry from the database. This just removes any metadata about a file
     *  that we might have in store. The actual DATA (contents) of the file (ie., the file
     *  itself) are removed via either file_delete() or db_delete_fileData() depending on
     *  how the DATA is stored.
     *  @author  Carl P. Corliss
     * @access public
     * @param   integer file_id    The id of the file we are deleting
     *
     * @return integer The number of affected rows on success, or FALSE on error
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileId)) {
            $msg = xarML(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'file_id',
                'db_delete_file',
                'uploads'
            );
            throw new Exception($msg);
        }

        //add to uploads table
        // Get database setup
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();

        // table and column definitions
        $fileEntry_table   = $xartable['file_entry'];

        // insert value into table
        $sql = "DELETE FROM $fileEntry_table
                      WHERE xar_fileEntry_id = $fileId";


        $result = &$dbconn->Execute($sql);

        if (!$result) {
            return false;
        }

        // Pass the arguments to the hook modules too
        $args['module'] = 'uploads';
        $args['itemtype'] = 1; // Files
        xarModHooks::call('item', 'delete', $fileId, $args);

        return true;
    }
}
