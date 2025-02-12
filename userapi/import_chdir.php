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
use xarModUserVars;
use xarMod;
use xarModVars;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi import_chdir function
 * @extends MethodClass<UserApi>
 */
class ImportChdirMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Change to the specified directory within the local imports sandbox directory
     * @author  Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var string $dirName  The name of the directory (within the import sandbox) to change to
     * @return string           The complete path to the new Current Working Directory within the sandbox
     * @see UserApi::importChdir()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($dirName) || empty($dirName)) {
            $dirName = null;
        }

        $root = sys::root();
        if (empty($root)) {
            $cwd = xarModUserVars::get('uploads', 'path.imports-cwd');
        } else {
            $cwd = sys::root() . "/" . xarModUserVars::get('uploads', 'path.imports-cwd');
        }
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        $importDir = $userapi->dbGetDir(['directory' => 'imports_directory']);

        if (!empty($dirName)) {
            if ($dirName == '...') {
                if (stristr($cwd, $importDir) && strlen($cwd) > strlen($importDir)) {
                    $cwd = dirname($cwd);
                    xarModUserVars::set('uploads', 'path.imports-cwd', $cwd);
                }
            } else {
                if (file_exists("$cwd/$dirName") && is_dir("$cwd/$dirName")) {
                    $cwd = "$cwd/$dirName";
                    xarModUserVars::set('uploads', 'path.imports-cwd', $cwd);
                }
            }
        } else {
            // if dirName is empty, then reset the cwd to the top level directory
            $cwd = $this->mod()->getVar('imports_directory');
            xarModUserVars::set('uploads', 'path.imports-cwd', $cwd);
        }

        if (!stristr($cwd, $importDir)) {
            $cwd = $importDir;
            xarModUserVars::set('uploads', 'path.imports-cwd', $importDir);
        }

        return $cwd;
    }
}
