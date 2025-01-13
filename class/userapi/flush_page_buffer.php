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
 * uploads userapi flush_page_buffer function
 * @extends MethodClass<UserApi>
 */
class FlushPageBufferMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Summary of __invoke
     * @param array<mixed> $args
     * @return array
     * @see UserApi::flushPageBuffer()
     */
    public function __invoke(array $args = [])
    {
        if (ini_get('output_handler') == 'ob_gzhandler' || ini_get('zlib.output_compression') == true) {
            do {
                $contents = ob_get_contents();
                if (!strlen($contents)) {
                    // Assume we have nothing to store
                    $pageBuffer[] = '';
                    break;
                } else {
                    $pageBuffer[] = $contents;
                }
            } while (@ob_end_clean());
        } else {
            do {
                $pageBuffer[] = ob_get_contents();
            } while (@ob_end_clean());
        }

        $buffer = array_reverse($pageBuffer);

        return $buffer;
    }
}
