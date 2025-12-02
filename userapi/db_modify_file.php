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
use Exception;

/**
 * uploads userapi db_modify_file function
 * @extends MethodClass<UserApi>
 */
class DbModifyFileMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modifies a file's metadata stored in the database
     * @author  Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var integer $fileId    The id of the file we are modifying
     * @var integer $userId    (optional) The id of the user whom submitted the file
     * @var string $filename   (optional) The name of the file (minus any path information)
     * @var string $fileLocation   (optional) The complete path to the file including the filename (obfuscated if so chosen)
     * @var integer $status     (optional) The status of the file (APPROVED, SUBMITTED, READABLE, REJECTED)
     * @var string $fileType  (optional) The mime content-type of the file
     * @var string $fileSize  (optional) The size of the file
     * @var integer $store_type (optional) The manner in which the file is to be stored (filesystem, database)
     * @var array $extrainfo  (optional) Extra information to be stored for this file (e.g. modified, width, height, ...)
     *
     * @return integer The number of affected rows on success, or FALSE on error
     * @see UserApi::dbModifyFile()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $update_fields = [];

        if (!isset($fileId)) {
            $msg = $this->ml(
                'Missing parameter [#(1)] for API function [#(2)] in module (#3)]',
                'fileId',
                'db_modify_file',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (isset($fileName)) {
            $update_fields[] = "xar_filename=?";
            $update_args[] = $fileName;
        }


        if (isset($fileLocation)) {
            $update_fields[] = "xar_location=?";
            $update_args[] = $fileLocation;
        }

        if (isset($userId)) {
            $update_fields[] = "xar_user_id = ?";
            $update_args[] = $userId;
        }

        if (isset($fileStatus)) {
            $update_fields[] = "xar_status = ?";
            $update_args[] = $fileStatus;
        }

        if (isset($store_type)) {
            $update_fields[] = "xar_store_type = ?";
            $update_args[] = $store_type;
        }

        if (isset($fileType)) {
            $update_fields[] = "xar_mime_type = ?";
            $update_args[] = $fileType;
        }

        if (isset($fileSize)) {
            $update_fields[] = "xar_filesize = ?";
            $update_args[] = $fileSize;
        }

        if (isset($extrainfo)) {
            $update_fields[] = "xar_extrainfo = ?";
            if (empty($extrainfo)) {
                $update_args[] = '';
            } elseif (is_array($extrainfo)) {
                $update_args[] = serialize($extrainfo);
            } else {
                $update_args[] = $extrainfo;
            }
        }

        if (!count($update_fields)) {
            return true;
        } else {
            $update_args[] = $fileId;
        }

        //add to uploads table
        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        $fileEntry_table = $xartable['file_entry'];

        $update_string   = implode(', ', $update_fields);

        $sql             = "UPDATE $fileEntry_table
                               SET $update_string
                             WHERE xar_fileEntry_id = ?";

        $result          = &$dbconn->Execute($sql, $update_args);

        if (!$result) {
            return false;
        }

        // Pass the arguments to the hook modules too
        $args['module'] = 'uploads';
        $args['itemtype'] = 1; // Files
        $this->mod()->callHooks('item', 'update', $fileId, $args);

        return true;
    }
}
