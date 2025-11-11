<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\AdminGui;

use Xaraya\Modules\Uploads\AdminGui;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;

/**
 * uploads admin waitingcontent function
 * @extends MethodClass<AdminGui>
 */
class WaitingcontentMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Waiting content hook
     * display waiting content as a hook
     * @since 19 Feb 2008
     * @return array count of the files in 'submitted' status
     * @see AdminGui::waitingcontent()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        // Get count of files in submitted state
        $count_submitted = $userapi->dbCount([
            'fileStatus' => 1,
        ]);
        $data['count_submitted'] = $count_submitted;
        return $data;
    }
}
