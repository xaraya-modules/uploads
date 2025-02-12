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
 * uploads userapi file_obfuscate_name function
 * @extends MethodClass<UserApi>
 */
class FileObfuscateNameMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Obscures the given filename for added security
     * @author  Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @return string
     * @see UserApi::fileObfuscateName()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileName) || empty($fileName)) {
            return false;
        }
        $hash = crypt($fileName, substr(md5(time() . $fileName . getmypid()), 0, 2));
        $hash = substr(md5($hash), 0, 8) . time() . getmypid();

        return $hash;
    }
}
