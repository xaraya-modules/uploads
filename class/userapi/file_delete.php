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
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi file_delete function
 * @extends MethodClass<UserApi>
 */
class FileDeleteMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Delete a file from the filesystem
     * @author  Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var string $fileName    The complete path to the file being deleted
     *
     * @return bool TRUE on success, FALSE on error
     * @see UserApi::fileDelete()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileName)) {
            $msg = xarML(
                'Missing parameter [#(1)] for function [(#(2)] in module [#(3)]',
                'fileName',
                'file_move',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!file_exists($fileName)) {
            // if the file doesn't exist, then we don't need
            // to worry about deleting it - so return true :)
            return true;
        }

        if (!unlink($fileName)) {
            $msg = xarML('Unable to remove file: [#(1)].', $fileName);
            throw new Exception($msg);
        }

        return true;
    }
}
