<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads;

use Xaraya\Modules\UserApiClass;
use Xaraya\Modules\Mime\UserApi as MimeApi;
use xarMod;
use xarVar;
use sys;

sys::import('xaraya.modules.userapi');

/**
 * Handle the uploads user API
 *
 * @method mixed dbAddAssociation(array $args)
 * @method mixed dbAddFile(array $args)
 * @method mixed dbAddFileData(array $args)
 * @method mixed dbChangeStatus(array $args)
 * @method mixed dbCount(array $args = [])
 * @method mixed dbCountAssociations(array $args)
 * @method mixed dbCountData(array $args)
 * @method mixed dbDeleteAssociation(array $args)
 * @method mixed dbDeleteFile(array $args)
 * @method mixed dbDeleteFileData(array $args)
 * @method mixed dbDiskusage(array $args = [])
 * @method mixed dbGetAssociations(array $args)
 * @method mixed dbGetDir(array $args)
 * @method mixed dbGetFile(array $args)
 * @method mixed dbGetFileData(array $args)
 * @method mixed dbGetFilename(array $args)
 * @method mixed dbGetUsers(array $args)
 * @method mixed dbGetallFiles(array $args = [])
 * @method mixed dbGroupAssociations(array $args)
 * @method mixed dbListAssociations(array $args)
 * @method mixed dbModifyFile(array $args)
 * @method mixed decodeShorturl(array $args)
 * @method mixed encodeShorturl(array $args)
 * @method mixed fileCreate(array $args)
 * @method mixed fileDelete(array $args)
 * @method mixed fileDump(array $args)
 * @method mixed fileGetMetadata(array $args)
 * @method mixed fileMove(array $args)
 * @method mixed fileObfuscateName(array $args)
 * @method mixed filePush(array $args)
 * @method mixed fileRename(array $args)
 * @method mixed fileStore(array $args)
 * @method mixed flushPageBuffer(array $args = [])
 * @method mixed getitemlinks(array $args)
 * @method mixed getitemtypes(array $args)
 * @method mixed importChdir(array $args)
 * @method mixed importExternalFile(array $args)
 * @method mixed importExternalFtp(array $args)
 * @method mixed importExternalHttp(array $args)
 * @method mixed importGetFilelist(array $args)
 * @method mixed normalizeFilesize(array $args)
 * @method mixed prepareImports(array $args)
 * @method mixed prepareUploads(array $args)
 * @method mixed processFiles(array $args)
 * @method mixed processFilters(array $args)
 * @method mixed purgeFiles(array $args)
 * @method mixed showoutput(array $args)
 * @method mixed transformhook(array $args)
 * @method mixed uploadmagic(array $args)
 * @method mixed validateFile(array $args)
 * @method mixed validateUpload(array $args)
 * @extends UserApiClass<Module>
 */
class UserApi extends UserApiClass
{
    /**
     * Get Mime UserApi class
     * @return MimeApi
     */
    public function getMimeAPI()
    {
        /** @var MimeApi $mimeapi */
        $mimeapi = xarMod::getAPI('mime');
        return $mimeapi;
    }

    /**
     * Utility function to synchronise file associations on validation
     * (for create/update of DD extra fields + update of DD objects and articles)
     */
    public function syncAssociations($moduleid = 0, $itemtype = 0, $itemid = 0, $filelist = [])
    {
        // see if we have anything to work with
        if (empty($moduleid) || empty($itemid)) {
            return;
        }

        // (try to) check if we're previewing or not
        xarVar::fetch('preview', 'isset', $preview, false, xarVar::NOT_REQUIRED);
        if (!empty($preview)) {
            return;
        }

        // get the current file associations for this module items
        $assoc = $this->dbGetAssociations([
            'modid'    => $moduleid,
            'itemtype' => $itemtype,
            'itemid'   => $itemid,
        ]);

        // see what we need to add or delete
        if (!empty($assoc) && count($assoc) > 0) {
            $add = array_diff($filelist, array_keys($assoc));
            $del = array_diff(array_keys($assoc), $filelist);
        } else {
            $add = $filelist;
            $del = [];
        }

        foreach ($add as $id) {
            if (empty($id)) {
                continue;
            }
            $this->dbAddAssociation([
                'fileId'   => $id,
                'modid'    => $moduleid,
                'itemtype' => $itemtype,
                'itemid'   => $itemid,
            ]);
        }
        foreach ($del as $id) {
            if (empty($id)) {
                continue;
            }
            $this->dbDeleteAssociation([
                'fileId'   => $id,
                'modid'    => $moduleid,
                'itemtype' => $itemtype,
                'itemid'   => $itemid,
            ]);
        }
    }
}
