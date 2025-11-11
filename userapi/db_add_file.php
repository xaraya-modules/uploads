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
use xarModHooks;
use Exception;

/**
 * uploads userapi db_add_file function
 * @extends MethodClass<UserApi>
 */
class DbAddFileMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Adds a file (fileEntry) entry to the database. This entry just contains metadata
     *  about the file and not the actual DATA (contents) of the file.
     * @author  Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var integer $userId         The id of the user whom submitted the file
     * @var string  $fileName       The name of the file (minus any path information)
     * @var string  $fileLocation   The complete path to the file including the filename (obfuscated if so chosen)
     * @var string  $fileType       The mime content-type of the file
     * @var integer $fileStatus     The status of the file (APPROVED, SUBMITTED, READABLE, REJECTED)
     * @var integer $store_type     The manner in which the file is to be stored (filesystem, database)
     * @var array   $extrainfo      Extra information to be stored for this file (e.g. modified, width, height, ...)
     *
     * @return integer The id of the fileEntry that was added, or FALSE on error
     * @see UserApi::dbAddFile()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileName)) {
            $msg = $this->ml(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'filename',
                'db_add_file',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!isset($fileLocation)) {
            $msg = $this->ml(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'fileLocation',
                'db_add_file',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!isset($userId)) {
            $userId = $this->user()->getId();
        }

        if (!isset($fileStatus)) {
            $autoApprove = $this->mod()->getVar('file.auto-approve');

            if ($autoApprove == Defines::APPROVE_EVERYONE
               || ($autoApprove == Defines::APPROVE_ADMIN && $this->sec()->checkAccess('AdminUploads', 0))) {
                $fileStatus = Defines::STATUS_APPROVED;
            } else {
                $fileStatus = Defines::STATUS_SUBMITTED;
            }
        }

        if (!isset($fileSize)) {
            $fileSize = 0;
        } else {
            // FIXME: only normalize the filesize before it's passed to a template
            //        otherwise, keep it as an integer <rabbitt>
            if (is_array($fileSize)) {
                if (stristr($fileSize['long'], ',')) {
                    $fileSize = str_replace(',', '', $fileSize['long']);
                } else {
                    $fileSize = $fileSize['long'];
                }
            }
        }

        if (!isset($store_type)) {
            $store_type = Defines::STORE_FILESYSTEM;
        }
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        if (!isset($fileType)) {
            /** @var MimeApi $mimeapi */
            $mimeapi = $userapi->getMimeAPI();

            $fileType = $mimeapi->analyzeFile(['fileName' => $fileLocation, 'altFileName' => $fileName]);
            if (empty($fileType)) {
                $fileType = 'application/octet-stream';
            }
        }

        if (empty($extrainfo)) {
            $extrainfo = '';
        } elseif (is_array($extrainfo)) {
            $extrainfo = serialize($extrainfo);
        }

        //add to uploads table
        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();


        // table and column definitions
        $fileEntry_table = $xartable['file_entry'];
        $file_id    = $dbconn->genID($fileEntry_table);

        // insert value into table
        $sql = "INSERT INTO $fileEntry_table
                          (
                            xar_fileEntry_id,
                            xar_user_id,
                            xar_filename,
                            xar_location,
                            xar_status,
                            xar_filesize,
                            xar_store_type,
                            xar_mime_type,
                            xar_extrainfo
                          )
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $bindvars = [ $file_id,
            (int) $userId,
            (string) $fileName,
            (string) $fileLocation,
            (int) $fileStatus,
            (int) $fileSize,
            (int) $store_type,
            (string) $fileType,
            (string) $extrainfo, ];

        $result = &$dbconn->Execute($sql, $bindvars);

        if (!$result) {
            return false;
        }

        $fileId = $dbconn->PO_Insert_ID($xartable['file_entry'], 'xar_fileEntry_id');

        // Pass the arguments to the hook modules too
        $args['module'] = 'uploads';
        $args['itemtype'] = 1; // Files
        xarModHooks::call('item', 'create', $fileId, $args);

        return $fileId;
    }
}
