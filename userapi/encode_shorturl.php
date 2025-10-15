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

sys::import('xaraya.modules.method');

/**
 * uploads userapi encode_shorturl function
 * @extends MethodClass<UserApi>
 */
class EncodeShorturlMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * return the path for a short URL to xarController::URL for this module
     * @author the Example module development team
     * @param array<mixed> $args the function and arguments passed to xarController::URL
     * @return string|void path to be added to index.php for a short URL, or empty if failed
     * @see UserApi::encodeShorturl()
     */
    public function __invoke(array $args = [])
    {
        // Get arguments from argument array
        extract($args);

        // Check if we have something to work with
        if (!isset($func)) {
            return;
        }

        // if we don't have a fileId, can't do too much
        if (!isset($fileId) || empty($fileId)) {
            return;
        } else {
            /** @var UserApi $userapi */
            $userapi = $this->userapi();

            $fileName = $userapi->dbGetFilename(['fileId' => $fileId]);

            if (!isset($fileName) || empty($fileName)) {
                // fileId is nonexistant...
                return;
            }

            $parts = explode('.', $fileName);
            $ext = end($parts);
            $fileName = "$fileId.$ext";
        }

        // default path is empty -> no short URL
        $path = '';
        // if we want to add some common arguments as URL parameters below
        $join = '?';
        // we can't rely on $this->mod()->getName() here -> you must specify the modname !
        $module = 'uploads';

        // specify some short URLs relevant to your module
        if ($func == 'download') {
            // check for required parameters
            if (!empty($fileId) && is_numeric($fileId)) {
                $path = '/' . $module . '/' . $fileName;
            }
        } else {
            // anything else that you haven't defined a short URL equivalent for
            // -> don't create a path here
        }

        return $path;
    }
}
