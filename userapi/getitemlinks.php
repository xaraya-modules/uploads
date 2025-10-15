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
 * uploads userapi getitemlinks function
 * @extends MethodClass<UserApi>
 */
class GetitemlinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function to pass individual item links to whoever
     * @param array<mixed> $args
     * @var mixed $itemtype item type (optional)
     * @var mixed $itemids array of item ids to get
     * @return array array containing the itemlink(s) for the item(s).
     * @see UserApi::getitemlinks()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $itemlinks = [];

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        // get cids for security check in getall
        $fileList = $userapi->dbGetFile(['fileId' => $itemids]);

        if (!isset($fileList) || empty($fileList)) {
            return $itemlinks;
        }

        foreach ($itemids as $itemid) {
            if (!isset($fileList[$itemid])) {
                continue;
            }

            $file = $fileList[$itemid];

            $itemlinks[$itemid] = ['url'   => $this->mod()->getURL( 'user', 'download', ['fileId' => $file['fileId']]),
                'title' => $file['DownloadLabel'],
                'label' => $this->var()->prep($file['fileName']), ];
        }
        return $itemlinks;
    }
}
