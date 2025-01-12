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
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi file_create function
 * @extends MethodClass<UserApi>
 */
class FileCreateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Creates a file on the filesystem in the specified location with
     *  the specified contents.and adds an entry to the new file in the
     *  file_entry table after creations. Note: you must test specifically
     *  for false if you are creating a ZERO BYTE file, as this function
     *  will return zero for that file (ie: !== FALSE as opposed to != FALSE).
     * @author  Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     *     string  filename       The name of the file (minus any path information)
     *     string  fileLocation   The complete path to the file including the filename (obfuscated if so chosen)
     *     string  mime_type      The mime content-type of the file
     *     string  contents       The contents of the new file
     *
     * @return integer|void The fileId of the newly created file, or ZERO (FALSE) on error
     */
    public function __invoke(array $args = []) {}
}
