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

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\Mime\UserApi as MimeApi;
use Xaraya\Modules\MethodClass;
use xarDB;
use xarMod;
use xarModHooks;
use xarServer;
use xarUser;
use xarController;
use xarSession;
use xarSecurity;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi db_get_file function
 * @extends MethodClass<UserApi>
 */
class DbGetFileMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieve the metadata stored for a particular file based on either
     *  the file id or the file name.
     * @author Carl P. Corliss
     * @author Micheal Cortez
     * @access public
     * @param array<mixed> $args
     * @var mixed $fileId       (Optional) grab file(s) with the specified file id(s)
     * @var string $fileName     (Optional) grab file(s) with the specified file name
     * @var string $fileType     (Optional) grab files with the specified mime type like 'image/%'
     * @var int $fileStatus   (Optional) grab files with a specified status  (SUBMITTED, APPROVED, REJECTED)
     * @var string $fileLocation (Optional) grab file(s) with the specified file location
     * @var string $fileHash     (Optional) grab file(s) with the specified file hash
     * @var int $userId       (Optional) grab files uploaded by a particular user
     * @var int $store_type   (Optional) grab files with the specified store type (FILESYSTEM, DATABASE)
     * @var bool $inverse      (Optional) inverse the selection
     * @var int $numitems     (Optional) number of files to get
     * @var int $startnum     (Optional) starting file number
     * @var string $sort         (Optional) sort order ('id','name','type','size','user','status','location',...)
     * @var string $catid        (Optional) grab file(s) in the specified categories
     * @var mixed $getnext      (Optional) grab the next file after this one (file id or file name)
     * @var mixed $getprev      (Optional) grab the previous file before this one (file id or file name)
     * @return array|void All of the metadata stored for the particular file(s)
     * @see UserApi::dbGetFile()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileId) && !isset($fileName) && !isset($fileStatus) && !isset($fileLocation) &&
            !isset($userId)  && !isset($fileType) && !isset($store_type) && !isset($fileHash) &&
            !isset($fileLocationMD5) && empty($getnext) && empty($getprev)) {
            $msg = $this->ml('Missing parameters for function [#(1)] in module [#(2)]', 'db_get_file', 'uploads');
            throw new Exception($msg);
        }

        $where = [];

        if (!isset($inverse)) {
            $inverse = false;
        }

        if (isset($fileId)) {
            if (is_array($fileId)) {
                // ignore IDs which are not numbers, like filenames
                $ids = [];
                foreach ($fileId as $id) {
                    if (is_numeric($id)) {
                        $ids[] = $id;
                    }
                }
                if (empty($ids)) {
                    return [];
                }
                $where[] = 'xar_fileEntry_id IN (' . implode(',', $ids) . ')';
            } elseif (!empty($fileId) && is_numeric($fileId)) {
                $where[] = "xar_fileEntry_id = $fileId";
            } else {
                // fileId == 0 so return an empty array.
                return [];
            }
        }

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        // table and column definitions
        $fileEntry_table = $xartable['file_entry'];

        if (isset($fileName) && !empty($fileName)) {
            $where[] = "(xar_filename LIKE '$fileName')";
        }

        if (isset($fileStatus) && !empty($fileStatus)) {
            $where[] = "(xar_status = $fileStatus)";
        }

        if (isset($fileSize) && !empty($fileSize)) {
            $where[] = "(xar_filesize = $fileSize)";
        }

        if (isset($userId) && !empty($userId)) {
            $where[] = "(xar_user_id = $userId)";
        }

        if (isset($store_type) && !empty($store_type)) {
            $where[] = "(xar_store_type = $store_type)";
        }

        if (isset($fileType) && !empty($fileType)) {
            $where[] = "(xar_mime_type LIKE '$fileType')";
        }

        if (isset($fileLocation) && !empty($fileLocation)) {
            if (strpos($fileLocation, '%') === false) {
                $where[] = '(xar_location = ' . $dbconn->qstr($fileLocation) . ')';
            } else {
                $where[] = '(xar_location LIKE ' . $dbconn->qstr($fileLocation) . ')';
            }
        }

        // Note: the fileHash is the last part of the location
        if (isset($fileHash) && !empty($fileHash)) {
            $where[] = '(xar_location LIKE ' . $dbconn->qstr("%/$fileHash") . ')';
        }

        // Note: the MD5 hash of the file location is used by derivatives in the images module
        if (isset($fileLocationMD5) && !empty($fileLocationMD5)) {
            if ($dbconn->databaseType == 'sqlite') {
                // CHECKME: verify this syntax for SQLite !
                $where[] = "(php('md5',xar_location) = " . $dbconn->qstr($fileLocationMD5) . ')';
            } else {
                $where[] = '(md5(xar_location) = ' . $dbconn->qstr($fileLocationMD5) . ')';
            }
        }

        if (!empty($getnext)) {
            $startnum = 1;
            $numitems = 1;
            if (is_numeric($getnext)) {
                // sort by file id
                $where[] = '(xar_fileEntry_id > ' . $dbconn->qstr($getnext) . ')';
                $sort = 'id_asc';
            } else {
                // sort by file name
                $where[] = '(xar_filename > ' . $dbconn->qstr($getnext) . ')';
                $sort = 'name_asc';
            }
        }

        if (!empty($getprev)) {
            $startnum = 1;
            $numitems = 1;
            if (is_numeric($getprev)) {
                // sort by file id
                $where[] = '(xar_fileEntry_id < ' . $dbconn->qstr($getprev) . ')';
                $sort = 'id_desc';
            } else {
                // sort by file name
                $where[] = '(xar_filename < ' . $dbconn->qstr($getprev) . ')';
                $sort = 'name_desc';
            }
        }

        if (count($where) > 1) {
            if ($inverse) {
                $where = implode(' OR ', $where);
            } else {
                $where = implode(' AND ', $where);
            }
        } else {
            $where = implode('', $where);
        }

        if ($inverse) {
            $where = "NOT ($where)";
        }

        $sql = "SELECT xar_fileEntry_id,
                       xar_user_id,
                       xar_filename,
                       xar_location,
                       xar_filesize,
                       xar_status,
                       xar_store_type,
                       xar_mime_type,
                       xar_extrainfo
                  FROM $fileEntry_table ";
        // Put the category id to work
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
                $where .= ' AND ' . $categoriesdef['where'];
            }
        }

        $sql .= " WHERE $where";

        // FIXME: we need some indexes on xar_file_entry to make this more efficient
        if (empty($sort)) {
            $sort = '';
        }
        switch ($sort) {
            case 'name':
            case 'name_asc':
                $sql .= ' ORDER BY xar_filename';
                break;

            case 'name_desc':
                $sql .= ' ORDER BY xar_filename DESC';
                break;

            case 'size':
                $sql .= ' ORDER BY xar_filesize DESC';
                break;

            case 'type':
                $sql .= ' ORDER BY xar_mime_type';
                break;

            case 'status':
                $sql .= ' ORDER BY xar_status';
                break;

            case 'location':
                $sql .= ' ORDER BY xar_location';
                break;

            case 'user':
                $sql .= ' ORDER BY xar_user_id';
                break;

            case 'store':
                $sql .= ' ORDER BY xar_store_type';
                break;

            case 'id':
            case 'id_desc':
                $sql .= ' ORDER BY xar_fileEntry_id DESC';
                break;

            case 'id_asc':
            default:
                $sql .= ' ORDER BY xar_fileEntry_id';
                break;
        }

        if (!empty($numitems) && is_numeric($numitems)) {
            if (empty($startnum) || !is_numeric($startnum)) {
                $startnum = 1;
            }
            $result = & $dbconn->SelectLimit($sql, $numitems, $startnum - 1);
        } else {
            $result = & $dbconn->Execute($sql);
        }

        if (!$result) {
            return;
        }

        // if no record found, return an empty array
        if ($result->EOF) {
            return [];
        }
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        $importDir = $userapi->dbGetDir(['directory' => 'imports_directory']);
        $uploadDir = $userapi->dbGetDir(['directory' => 'uploads_directory']);

        // remove the '/' from the path
        $importDir = str_replace('/$', '', $importDir);
        $uploadDir = str_replace('/$', '', $uploadDir);

        if ($this->ctl()->getServerVar('PATH_TRANSLATED')) {
            $base_directory = dirname(realpath($this->ctl()->getServerVar('PATH_TRANSLATED')));
        } elseif ($this->ctl()->getServerVar('SCRIPT_FILENAME')) {
            $base_directory = dirname(realpath($this->ctl()->getServerVar('SCRIPT_FILENAME')));
        } else {
            $base_directory = './';
        }

        /** @var MimeApi $mimeapi */
        $mimeapi = $userapi->getMimeAPI();

        $revcache = [];
        $imgcache = [];
        $usercache = [];

        $fileList = [];
        while ($result->next()) {
            $row = $result->GetRowAssoc(false);
            if (empty($row)) {
                break;
            }

            $fileInfo['fileId']        = $row['xar_fileentry_id'];
            $fileInfo['userId']        = $row['xar_user_id'];
            if (!isset($usercache[$fileInfo['userId']])) {
                $usercache[$fileInfo['userId']] = $this->user($fileInfo['userId'])->getName();
            }
            $fileInfo['userName']      = $usercache[$fileInfo['userId']];
            $fileInfo['fileName']      = $row['xar_filename'];
            $fileInfo['fileLocation']  = $row['xar_location'];
            $fileInfo['fileSize']      = $row['xar_filesize'];
            $fileInfo['fileStatus']    = $row['xar_status'];
            $fileInfo['fileType']      = $row['xar_mime_type'];
            if (!isset($revcache[$fileInfo['fileType']])) {
                $revcache[$fileInfo['fileType']] = $mimeapi->getRevMimetype(['mimeType' => $fileInfo['fileType']]);
            }
            $fileInfo['fileTypeInfo']  = $revcache[$fileInfo['fileType']];
            $fileInfo['storeType']     = $row['xar_store_type'];
            if (!isset($imgcache[$fileInfo['fileType']])) {
                $imgcache[$fileInfo['fileType']] = $mimeapi->getMimeImage(['mimeType' => $fileInfo['fileType']]);
            }
            $fileInfo['mimeImage']     = $imgcache[$fileInfo['fileType']];
            $fileInfo['fileDownload']  = $this->mod()->getURL( 'user', 'download', ['fileId' => $fileInfo['fileId']]);
            $fileInfo['fileURL']       = $fileInfo['fileDownload'];
            $fileInfo['DownloadLabel'] = $this->ml('Download file: #(1)', $fileInfo['fileName']);
            if (!empty($fileInfo['fileLocation']) && file_exists($fileInfo['fileLocation'])) {
                $fileInfo['fileModified'] = @filemtime($fileInfo['fileLocation']);
            }

            if (stristr($fileInfo['fileLocation'], $importDir)) {
                $fileInfo['fileDirectory'] = dirname(str_replace($importDir, 'imports', $fileInfo['fileLocation']));
                $fileInfo['fileHash']  = basename($fileInfo['fileLocation']);
            } elseif (stristr($fileInfo['fileLocation'], $uploadDir)) {
                $fileInfo['fileDirectory'] = dirname(str_replace($uploadDir, 'uploads', $fileInfo['fileLocation']));
                $fileInfo['fileHash']  = basename($fileInfo['fileLocation']);
            } else {
                $fileInfo['fileDirectory'] = dirname($fileInfo['fileLocation']);
                $fileInfo['fileHash']  = basename($fileInfo['fileLocation']);
            }

            $fileInfo['fileHashName']     = $fileInfo['fileDirectory'] . '/' . $fileInfo['fileHash'];
            $fileInfo['fileHashRealName'] = $fileInfo['fileDirectory'] . '/' . $fileInfo['fileName'];

            switch ($fileInfo['fileStatus']) {
                case Defines::STATUS_REJECTED:
                    $fileInfo['fileStatusName'] = $this->ml('Rejected');
                    break;
                case Defines::STATUS_APPROVED:
                    $fileInfo['fileStatusName'] = $this->ml('Approved');
                    break;
                case Defines::STATUS_SUBMITTED:
                    $fileInfo['fileStatusName'] = $this->ml('Submitted');
                    break;
                default:
                    $fileInfo['fileStatusName'] = $this->ml('Unknown!');
                    break;
            }

            if (!empty($row['xar_extrainfo'])) {
                $fileInfo['extrainfo'] = @unserialize($row['xar_extrainfo']);
            }

            $instance = [];
            $instance[0] = $fileInfo['fileTypeInfo']['typeId'];
            $instance[1] = $fileInfo['fileTypeInfo']['subtypeId'];
            $instance[2] = $this->user()->getId();
            $instance[3] = $fileInfo['fileId'];

            $instance = implode(':', $instance);

            if ($fileInfo['fileStatus'] == Defines::STATUS_APPROVED ||
                $this->sec()->check('EditUploads', 0, 'File', $instance)) {
                $fileList[$fileInfo['fileId']] = $fileInfo;
            }
        }

        return $fileList;
    }
}
