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
 * uploads userapi normalize_filesize function
 * @extends MethodClass<UserApi>
 */
class NormalizeFilesizeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * @todo fix signature
     * @param mixed $args
     * @var ?int $fileSize integer or null
     * @see UserApi::normalizeFilesize()
     */
    public function __invoke(mixed $args = [])
    {
        if (is_array($args)) {
            extract($args);
        } elseif (is_numeric($args)) {
            $fileSize = $args;
        } else {
            return ['long' => 0, 'short' => 0];
        }
        $fileSize ??= 0;

        $size = $fileSize;

        $range = ['', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $size >= 1024 && $i < count($range); $i++) {
            $size /= 1024;
        }

        $short = round($size, 2) . ' ' . $range[$i];

        return ['long' => number_format($fileSize), 'short' => $short];
    }
}
