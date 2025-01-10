<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\AdminGui;

use Xaraya\Modules\MethodClass;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads admin waitingcontent function
 */
class WaitingcontentMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Waiting content hook
     * display waiting content as a hook
     * @since 19 Feb 2008
     * @return array count of the files in 'submitted' status
     */
    public function __invoke(array $args = [])
    {
        // Get count of files in submitted state
        unset($count_submitted);
        $count_submitted = xarMod::apiFunc(
            'uploads',
            'user',
            'db_count',
            ['fileStatus' => 1]
        );
        $data['count_submitted'] = $count_submitted;
        return $data;
    }
}
