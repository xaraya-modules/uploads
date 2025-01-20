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
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi db_getall_files function
 * @extends MethodClass<UserApi>
 */
class DbGetallFilesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieve the metadata stored for all files in the database
     * @author Carl P. Corliss
     * @author Micheal Cortez
     * @access public
     * @param array<mixed> $args
     * @var int $numitems     (Optional) number of files to get
     * @var int $startnum     (Optional) starting file number
     * @var string $sort         (Optional) sort order ('id','name','type','size','user','status','location',...)
     * @return array|bool|void All of the metadata stored for all files
     * @see UserApi::dbGetallFiles()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();

        // table and column definitions
        $fileEntry_table = $xartable['file_entry'];

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

        if (!empty($catid) && $this->mod()->isAvailable('categories') && xarModHooks::isHooked('categories', 'uploads', 1)) {
            // Get the LEFT JOIN ... ON ...  and WHERE (!) parts from categories
            $categoriesdef = xarMod::apiFunc(
                'categories',
                'user',
                'leftjoin',
                ['modid' => xarMod::getRegID('uploads'),
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
                $sql .= ' WHERE ' . $categoriesdef['where'];
            }
        }

        // FIXME: we need some indexes on xar_file_entry to make this more efficient
        if (empty($sort)) {
            $sort = '';
        }
        switch ($sort) {
            case 'name':
                $sql .= ' ORDER BY xar_filename';
                break;

            case 'size':
                $sql .= ' ORDER BY xar_filesize';
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

        // remove the '/' at the end of the path
        $importDir = str_replace('/$', '', $importDir);
        $uploadDir = str_replace('/$', '', $uploadDir);

        if (xarServer::getVar('PATH_TRANSLATED')) {
            $base_directory = dirname(realpath(xarServer::getVar('PATH_TRANSLATED')));
        } elseif (xarServer::getVar('SCRIPT_FILENAME')) {
            $base_directory = dirname(realpath(xarServer::getVar('SCRIPT_FILENAME')));
        } else {
            $base_directory = './';
        }

        /** @var MimeApi $mimeapi */
        $mimeapi = $userapi->getMimeAPI();

        $revcache = [];
        $imgcache = [];
        $usercache = [];

        while (!$result->EOF) {
            $row = $result->GetRowAssoc(false);
            if (empty($row)) {
                break;
            }

            $fileInfo['fileId']        = $row['xar_fileEntry_id'];
            $fileInfo['userId']        = $row['xar_user_id'];
            if (!isset($usercache[$fileInfo['userId']])) {
                $usercache[$fileInfo['userId']] = xarUser::getVar('name', $fileInfo['userId']);
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
            $instance[2] = xarSession::getVar('uid');
            $instance[3] = $fileInfo['fileId'];

            $instance = implode(':', $instance);

            if ($fileInfo['fileStatus'] == Defines::STATUS_APPROVED ||
                xarSecurity::check('EditUploads', 0, 'File', $instance)) {
                $fileList[$fileInfo['fileId']] = $fileInfo;
            }
            $result->MoveNext();
        }

        $result->Close();

        return $fileList;
    }
}
