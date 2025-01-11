<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
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
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi file_get_metadata function
 * @extends MethodClass<UserApi>
 */
class FileGetMetadataMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieves metadata on a file from the filesystem
     *  @author  Carl P. Corliss
     * @access public
     * @param   string   fileLocation  The location of the file on in the filesystem
     * @param   boolean  normalize     Whether or not to
     * @param   boolean  analyze       Whether or not to
     * @return array                  array containing the inodeType, fileSize, fileType, fileLocation, fileName
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($normalize)) {
            $normalize = false;
        }

        if (!isset($analyze)) {
            $analyze = true;
        }

        if (isset($fileLocation) && !empty($fileLocation) && file_exists($fileLocation)) {
            $file = & $fileLocation;
            if (is_dir($file)) {
                $type = Defines::TYPE_DIRECTORY;
                $size = 'N/A';
                $mime = 'filesystem/directory';
            } elseif (is_file($file)) {
                $type = Defines::TYPE_FILE;
                $size = filesize($file);
                if ($analyze) {
                    $mime = xarMod::apiFunc('mime', 'user', 'analyze_file', ['fileName' => $file]);
                } else {
                    $mime = 'application/octet';
                }
            } else {
                $type = Defines::TYPE_UNKNOWN;
                $size = 0;
                $mime = 'application/octet';
            }

            $name = basename($file);

            if ($normalize) {
                $size = xarMod::apiFunc('uploads', 'user', 'normalize_filesize', $size);
            }

            // CHECKME: use 'imports' name like in db_get_file() ?
            $relative_path = str_replace(xarModVars::get('uploads', 'imports_directory'), '/trusted', $file);

            $fileInfo = ['inodeType'    => $type,
                'fileName'     => $name,
                'fileLocation' => $file,
                'relativePath' => $relative_path,
                'fileType'     => $mime,
                'fileSize'     => $size, ];

            return $fileInfo;
        } else {
            // TODO: exception
            return false;
        }
    }
}
