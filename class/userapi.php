<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads;

use Xaraya\Modules\UserApiClass;
use sys;

sys::import('xaraya.modules.userapi');

/**
 * Handle the uploads user API
 *
 * @method mixed dbAddAssociation(array $args)
 * @method mixed dbAddFile(array $args)
 * @method mixed dbAddFileData(array $args)
 * @method mixed dbChangeStatus(array $args)
 * @method mixed dbCount(array $args)
 * @method mixed dbCountAssociations(array $args)
 * @method mixed dbCountData(array $args)
 * @method mixed dbDeleteAssociation(array $args)
 * @method mixed dbDeleteFile(array $args)
 * @method mixed dbDeleteFileData(array $args)
 * @method mixed dbDiskusage(array $args)
 * @method mixed dbGetAssociations(array $args)
 * @method mixed dbGetDir(array $args)
 * @method mixed dbGetFile(array $args)
 * @method mixed dbGetFileData(array $args)
 * @method mixed dbGetFilename(array $args)
 * @method mixed dbGetUsers(array $args)
 * @method mixed dbGetallFiles(array $args)
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
 * @method mixed flushPageBuffer(array $args)
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
    // ...
}
