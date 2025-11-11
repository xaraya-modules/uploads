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

/**
 * uploads userapi db_diskusage function
 * @extends MethodClass<UserApi>
 */
class DbDiskusageMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieve the total size of disk usage for selected files based on the filters passed in
     * @author Carl P. Corliss
     * @author Micheal Cortez
     * @access public
     * @param array<mixed> $args
     * @var int $fileId      (Optional) grab file with the specified file id(s)
     * @var string $fileName    (Optional) grab file(s) with the specified file name
     * @var int $fileStatus  (Optional) grab files with a specified status  (SUBMITTED, APPROVED, REJECTED)
     * @var int $userId      (Optional) grab files uploaded by a particular user
     * @var int $store_type  (Optional) grab files with the specified store type (FILESYSTEM, DATABASE)
     * @var string $fileType    (Optional) grab files with the specified mime type like 'image/%'
     * @var string $catid       (Optional) grab file(s) in the specified categories
     * @return int|void The total amount of diskspace used by the current set of selected files
     * @see UserApi::dbDiskusage()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $where = [];

        if (!isset($inverse)) {
            $inverse = false;
        }

        if (isset($fileId)) {
            if (is_array($fileId)) {
                $where[] = 'xar_fileEntry_id IN (' . implode(',', $fileIds) . ')';
            } elseif (!empty($fileId)) {
                $where[] = "xar_fileEntry_id = $fileId";
            }
        }

        if (isset($fileName) && !empty($fileName)) {
            $where[] = "(xar_filename LIKE '$fileName')";
        }

        if (isset($fileStatus) && !empty($fileStatus) && is_numeric($fileStatus)) {
            $where[] = "(xar_status = $fileStatus)";
        }

        if (isset($userId) && !empty($userId) && is_numeric($userId)) {
            $where[] = "(xar_user_id = $userId)";
        }

        if (isset($store_type) && !empty($store_type) && is_numeric($store_type)) {
            $where[] = "(xar_store_type = $store_type)";
        }

        if (isset($fileType) && !empty($fileType)) {
            $where[] = "(xar_mime_type LIKE '$fileType')";
        }

        if (count($where) > 1) {
            if ($inverse) {
                $where = 'WHERE NOT (' . implode(' OR ', $where) . ')';
            } else {
                $where = 'WHERE ' . implode(' AND ', $where);
            }
        } elseif (count($where) == 1) {
            if ($inverse) {
                $where = 'WHERE NOT (' . implode('', $where) . ')';
            } else {
                $where = 'WHERE ' . implode('', $where);
            }
        } else {
            $where = '';
        }

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        // table and column definitions
        $fileEntry_table = $xartable['file_entry'];

        $sql = "SELECT SUM(xar_filesize) AS disk_usage
                  FROM $fileEntry_table ";

        if (!empty($catid) && $this->mod()->isAvailable('categories') && $this->mod()->isHooked('categories', 'uploads', 1)) {
            // Get the LEFT JOIN ... ON ...  and WHERE (!) parts from categories
            $categoriesdef = $this->mod()->apiFunc(
                'categories',
                'user',
                'leftjoin',
                ['modid' => $this->mod()->getRegID('uploads'),
                    'itemtype' => 1,
                    'catid' => $catid, ]
            );
            if (empty($categoriesdef)) {
                return;
            }

            // Add LEFT JOIN ... ON ... from categories_linkage
            $sql .= ' LEFT JOIN ' . $categoriesdef['table'];
            $sql .= ' ON ' . $categoriesdef['field'] . ' = ' . 'xar_fileEntry_id';
            if (!empty($categoriesdef['more'])) {
                // More LEFT JOIN ... ON ... from categories (when selecting by category)
                $sql .= $categoriesdef['more'];
            }
            if (!empty($categoriesdef['where'])) {
                if (!empty($where) && strpos($where, 'WHERE') !== false) {
                    $where .= ' AND ' . $categoriesdef['where'];
                } else {
                    $where .= ' WHERE ' . $categoriesdef['where'];
                }
            }
        }

        $sql .= " $where";

        $result = $dbconn->Execute($sql);

        if (!$result) {
            return false;
        }

        // if no record found, return an empty array
        if (!$result->first()) {
            return (int) 0;
        }

        $row = $result->GetRowAssoc(false);

        return $row['disk_usage'];
    }
}
