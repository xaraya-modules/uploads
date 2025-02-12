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
use xarUser;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi db_get_users function
 * @extends MethodClass<UserApi>
 */
class DbGetUsersMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieve a list of users who have submitted files
     * @author Carl P. Corliss
     * @author Micheal Cortez
     * @access public
     * @param array<mixed> $args
     * @var string $mime_type   (Optional) grab files with the specified mime type
     * @return array|bool|void All of the metadata stored for the particular file
     * @see UserApi::dbGetUsers()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (isset($mimeType) && !empty($mimeType)) {
            $where = "WHERE (xar_mime_type LIKE '$mimeType')";
        } else {
            $where = '';
        }

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        // table and column definitions
        $fileEntry_table = $xartable['file_entry'];

        $sql = "SELECT DISTINCT xar_user_id
                  FROM $fileEntry_table
                $where";

        $result = $dbconn->Execute($sql);

        if (!$result) {
            return false;
        }


        // if no record found, return an empty array
        if ($result->EOF) {
            return [];
        }

        while (!$result->EOF) {
            $row = $result->GetRowAssoc(false);
            if (empty($row)) {
                break;
            }

            $userInfo['userId']   = $row['xar_user_id'];
            $userInfo['userName'] = xarUser::getVar('name', $row['xar_user_id']);

            $userList[$userInfo['userId']] = $userInfo;

            unset($userinfo);
            $result->MoveNext();
        }

        return $userList;
    }
}
