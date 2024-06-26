 <?php
/**
 * Uploads Module
 *
 * @package modules
 * @subpackage uploads module
 * @category Third Party Xaraya Module
 * @version 1.1.0
 * @copyright see the html/credits.html file in this Xaraya release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com/index.php/release/eid/666
 * @author Uploads Module Development Team
 */

/**
 *  Change the status on a file, or group of files based on the file id(s) or filetype
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *
 *  @return integer The number of affected rows on success, or FALSE on error
 */

function uploads_userapi_db_change_status(array $args = [], $context = null)
{
    extract($args);

    if (!isset($inverse)) {
        $inverse = false;
    }

    if (!isset($fileId) && !isset($fileType)) {
        $msg = xarML(
            'Missing identifying parameter function [#(1)] in module [#(2)]',
            'db_change_status',
            'uploads'
        );
        throw new Exception($msg);
    }

    if (!isset($newStatus)) {
        $msg = xarML(
            'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
            'newStatus',
            'db_change_status',
            'uploads'
        );
        throw new Exception($msg);
    }

    if (isset($fileId)) {
        // Looks like we have an array of file ids, so change them all
        if (is_array($fileId)) {
            $where = " WHERE xar_fileEntry_id IN (" . implode(',', $fileId) . ")";
            // Guess we're only changing one file id ...
        } else {
            $where = " WHERE xar_fileEntry_id = $fileId";
        }
        // Otherwise, we're changing based on MIME type
    } else {
        if (!$inverse) {
            $where = " WHERE xar_mime_type LIKE '$fileType'";
        } else {
            $where = " WHERE xar_mime_type NOT LIKE '$fileType'";
        }
    }

    if (isset($curStatus) && is_numeric($curStatus)) {
        $where .= " AND xar_status = $curStatus";
    }

    //add to uploads table
    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $fileEntry_table = $xartable['file_entry'];

    $sql             = "UPDATE $fileEntry_table
                           SET xar_status = $newStatus
                        $where";

    $result          = &$dbconn->Execute($sql);

    if (!$result) {
        return false;
    } else {
        return $dbconn->Affected_Rows();
    }
}

?>