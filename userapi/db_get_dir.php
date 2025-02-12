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
use xarModVars;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi db_get_dir function
 * @extends MethodClass<UserApi>
 */
class DbGetDirMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieve a directory path
     * @param array<mixed> $args
     * @var string $directory designation
     * @return string relative path of the directory
     * @see UserApi::dbGetDir()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($directory)) {
            $msg = $this->ml('Missing [#(1)] parameter for function [#(2)] in module [#(3)]', 'directory', 'db_get_dir', 'uploads');
            throw new Exception($msg);
        }

        $root = sys::root();
        if (empty($root)) {
            $directory = $this->mod()->getVar($directory);
        } else {
            $directory = sys::root() . "/" . $this->mod()->getVar($directory);
        }
        return $directory;
    }
}
