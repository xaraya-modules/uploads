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
use Xaraya\Modules\MethodClass;
use xarMod;
use xarModVars;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi prepare_imports function
 * @extends MethodClass<UserApi>
 */
class PrepareImportsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Summary of __invoke
     * @param array<mixed> $args
     * @throws \Exception
     * @return mixed
     * @see UserApi::prepareImports()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($importFrom)) {
            $msg = $this->translate(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'importFrom',
                'prepare_imports',
                'uploads'
            );
            throw new Exception($msg);
        }
        $userapi = $this->getParent();

        if (!isset($import_directory)) {
            $import = $userapi->dbGetDir(['directory' => 'imports_directory']);
        }

        if (!isset($import_obfuscate)) {
            $import_obfuscate = $this->getModVar('file.obfuscate-on-import');
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

        $imports = $userapi->importGetFilelist([
            'fileLocation'  => $importFrom,
            'descend'       => $descend,
        ]);
        if ($imports) {
            // @todo what's this about then, and where can we find it?
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

            $fileInfo['errors'][]['errorMsg'] = $this->translate('Unknown');
            $fileInfo['errors'][]['errorId']  = Defines::ERROR_UNKNOWN;
            return [$fileInfo];
        } else {
            return $imports;
        }
    }
}
