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

function uploads_userapi_prepare_imports(array $args = [], $context = null)
{
    extract($args);

    if (!isset($importFrom)) {
        $msg = xarML(
            'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
            'importFrom',
            'prepare_imports',
            'uploads'
        );
        throw new Exception($msg);
    }

    if (!isset($import_directory)) {
        $import = xarMod::apiFunc('uploads', 'user', 'db_get_dir', ['directory' => 'imports_directory']);
    }

    if (!isset($import_obfuscate)) {
        $import_obfuscate = xarModVars::get('uploads', 'file.obfuscate-on-import');
    }

    /**
    * if the importFrom is an url, then
    * we can't descend (obviously) so set it to FALSE
    */
    if (!isset($descend)) {
        if (preg_match('%^(http[s]?|ftp)?\:\/\/%i', $importFrom)) {
            $descend = false;
        } else {
            $descend = true;
        }
    }

    $imports = xarMod::apiFunc(
        'uploads',
        'user',
        'import_get_filelist',
        ['fileLocation'  => $importFrom,
                                    'descend'       => $descend, ]
    );
    if ($imports) {
        $imports = xarMod::apiFunc(
            'uploads',
            'user',
            'import_prepare_files',
            ['fileList'  => $imports,
                                        'savePath'  => $import_directory,
                                        'obfuscate' => $import_obfuscate, ]
        );
    }

    if (!$imports) {
        $fileInfo['errors']   = [];
        $fileInfo['fileName'] = $importFrom;
        $fileInfo['fileSrc']  = $importFrom;
        $fileInfo['fileDest'] = $import_directory;
        $fileInfo['fileSize'] = 0;

        $fileInfo['errors'][]['errorMsg'] = xarML('Unknown');
        $fileInfo['errors'][]['errorId']  = _UPLOADS_ERROR_UNKNOWN;
        return [$fileInfo];
    } else {
        return $imports;
    }
}
