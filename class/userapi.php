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
 * @method mixed dbAddAssociation(array $args) Create an assocation between a (stored) file and a module/itemtype/item
 *  array{fileId: int, modid: int, itemtype: int, itemid: int}
 * @method mixed dbAddFile(array $args) Adds a file (fileEntry) entry to the database. This entry just contains metadata -  about the file and not the actual DATA (contents) of the file.
 *  array{userId: int, fileName: string, fileLocation: string, fileType: string, fileStatus: int, store_type: int, extrainfo: array}
 * @method mixed dbAddFileData(array $args) Adds a file's  contents to the database. This only takes 4K (4096 bytes) blocks.
 *  array{fileId: int, fileData: string}
 * @method mixed dbChangeStatus(array $args = []) Change the status on a file, or group of files based on the file id(s) or filetype
 * @method mixed dbCount(array $args = []) Retrieve the total count of files in the database based on the filters passed in
 *  array{fileId?: mixed, fileName?: string, fileType?: string, fileStatus?: int, fileLocation?: string, fileHash?: string, userId?: int, store_type?: int, inverse?: bool, catid?: string}
 * @method mixed dbCountAssociations(array $args) Retrieve the total count associations for a particular file/module/itemtype/item combination
 *  array{fileId: mixed, modid: int, itemtype: int, itemid: int}
 * @method mixed dbCountData(array $args = []) Retrieve the total count of data blocks stored for a particular file
 *  array{fileId?: int}
 * @method mixed dbDeleteAssociation(array $args) Remove an assocation between a particular file and module/itemtype/item.
 *  array{fileId: int, modid: int, itemtype: int, itemid: int}
 * @method mixed dbDeleteFile(array $args) Remove a file entry from the database. This just removes any metadata about a file -  that we might have in store. The actual DATA (contents) of the file (ie., the file -  itself) are removed via either file_delete() or db_delete_fileData() depending on -  how the DATA is stored.
 *  array{file_id: int}
 * @method mixed dbDeleteFileData(array $args) Remove a file's data contents from the database. This just removes any data (contents) -  that we might have in store for this file. The actual metadata (FILE ENTRY) for the file -  itself is removed via db_delete_file() .
 *  array{fileId: int}
 * @method mixed dbDiskusage(array $args = []) Retrieve the total size of disk usage for selected files based on the filters passed in
 *  array{fileId?: int, fileName?: string, fileStatus?: int, userId?: int, store_type?: int, fileType?: string, catid?: string}
 * @method mixed dbGetAssociations(array $args) Retrieve a list of file assocations for a particular file/module/itemtype/item combination
 *  array{modid: int, itemtype: int, itemid: int, fileId: int}
 * @method mixed dbGetDir(array $args) Retrieve a directory path
 *  array{directory: string}
 * @method mixed dbGetFile(array $args = []) Retrieve the metadata stored for a particular file based on either -  the file id or the file name.
 *  array{fileId?: mixed, fileName?: string, fileType?: string, fileStatus?: int, fileLocation?: string, fileHash?: string, userId?: int, store_type?: int, inverse?: bool, numitems?: int, startnum?: int, sort?: string, catid?: string, getnext?: mixed, getprev?: mixed}
 * @method mixed dbGetFileData(array $args) Retrieve the DATA (contents) stored for a particular file based on -  the file id. This returns an array not unlike the php function -  'file()' whereby the contents of the file are in an ordered array.
 *  array{fileId: int}
 * @method mixed dbGetFilename(array $args = []) Retrieve the filename for a particular file based on the file id
 *  array{fileId?: int}
 * @method mixed dbGetUsers(array $args = []) Retrieve a list of users who have submitted files
 *  array{mime_type?: string}
 * @method mixed dbGetallFiles(array $args = []) Retrieve the metadata stored for all files in the database
 *  array{numitems?: int, startnum?: int, sort?: string}
 * @method mixed dbGroupAssociations(array $args = []) get the list of modules and itemtypes we're associating files with
 * @method mixed dbListAssociations(array $args) Retrieve a list of (item - file) associations for a particular module/itemtype combination
 *  array{modid: int, itemtype: int, itemid: int, fileId: int}
 * @method mixed dbModifyFile(array $args) Modifies a file's metadata stored in the database
 *  array{fileId: int, userId?: int, filename?: string, fileLocation?: string, status?: int, fileType?: string, fileSize?: string, store_type?: int, extrainfo?: array}
 * @method mixed decodeShorturl(array $args = []) extract function and arguments from short URLs for this module, and pass - them back to xarGetRequestInfo()
 * @method mixed encodeShorturl(array $args = []) return the path for a short URL to xarController::URL for this module
 * @method mixed fileCreate(array $args) Creates a file on the filesystem in the specified location with -  the specified contents.and adds an entry to the new file in the -  file_entry table after creations. Note: you must test specifically -  for false if you are creating a ZERO BYTE file, as this function -  will return zero for that file (ie: !== FALSE as opposed to != FALSE).
 *  array{filename: string, fileLocation: string, mime_type: string, contents: string}
 * @method mixed fileDelete(array $args) Delete a file from the filesystem
 *  array{fileName: string}
 * @method mixed fileDump(array $args) Dump a files contents into the database.
 *  array{fileSrc: string, fileId: int}
 * @method mixed fileGetMetadata(array $args) Retrieves metadata on a file from the filesystem
 *  array{fileLocation: string, normalize: bool, analyze: bool}
 * @method mixed fileMove(array $args) Move a file from one location to another. Can (or will eventually be able to) grab a file from -  a remote site via ftp/http/etc and save it locally as well. Note: isUpload=TRUE implies isLocal=True
 *  array{fileSrc: string, fileDest: string, isUpload: bool, isLocal: bool}
 * @method mixed fileObfuscateName(array $args = []) Obscures the given filename for added security
 * @method mixed filePush(array $args) Pushes a file to the client browser - Note: on success, the calling GUI function should exit()
 *  array{fileName: string, fileLocation: string, fileType: string, fileSize: int, storeType: int}
 * @method mixed fileRename(array $args = []) Rename a file. (alias for file_move)
 * @method mixed fileStore(array $args = [])
 * @method mixed flushPageBuffer(array $args = [])
 * @method mixed getitemlinks(array $args) utility function to pass individual item links to whoever
 *  array{itemtype?: mixed, itemids: mixed}
 * @method mixed getitemtypes(array $args = []) utility function to retrieve the list of item types of this module (if any)
 * @method mixed importChdir(array $args) Change to the specified directory within the local imports sandbox directory
 *  array{dirName: string}
 * @method mixed importExternalFile(array $args) Retrieves an external file using the File scheme
 *  array{uri: array}
 * @method mixed importExternalFtp(array $args) Retrieves an external file using the FTP scheme
 *  array{uri: array, obfuscate: bool, savePath: string}
 * @method mixed importExternalHttp(array $args) Retrieves an external file using the http scheme
 *  array{uri: array, obfuscate: bool, savePath: string}
 * @method mixed importGetFilelist(array $args) Get a list of files with metadata from some import directory or link
 *  array{fileLocation: string, descend: bool, onlyNew: bool, search: string, exclude: string, cacheExpire: int, analyze: bool}
 * @method mixed normalizeFilesize(array $args)
 *  array{fileSize: ?int}
 * @method mixed prepareImports(array $args = [])
 * @method mixed prepareUploads(array $args) Prepares a list of files that have been uploaded, creating a structure for -  each file with the following parts: -      * fileType  - mimetype -      * fileSrc   - the source location of the file -      * fileSize  - the filesize of the file -      * fileName  - the file's basename -      * fileDest  - the (potential) destination for the file (filled in even if stored in the db and not filesystem) -  Any file that has errors will have it noted in the same structure with error number and message in: -      * errors[]['errorMesg'] -      * errors[]['errorId']
 *  array{obfuscate: bool, savePath: string}
 * @method mixed processFiles(array $args = [])
 * @method mixed processFilters(array $args = [])
 * @method mixed purgeFiles(array $args) Takes a list of files and deletes them
 *  array{fileList: array}
 * @method mixed showoutput(array $args) show output for uploads module (used in DD properties)
 *  array{value: string, format: string}
 * @method mixed transformhook(array $args) Primarily used by Articles as a transform hook to turn "upload tags" into various display formats
 *  array{extrainfo: array|string}
 * @method mixed uploadmagic(array $args = [])
 * @method mixed validateFile(array $args) Check an uploaded file for valid mime-type, and any errors that might -  have been encountered during the upload
 *  array{fileInfo: array}
 * @method mixed validateUpload(array $args) Validates file based on criteria specified by hooked modules (well, that's the intended future -  functionality anyhow - which won't be available until the hooks system has been revamped.
 *  array{fileInfo: array}
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
        $this->var()->find('preview', $preview, 'isset', false);
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
