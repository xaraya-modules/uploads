<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserGui;

use Xaraya\Modules\Uploads\UserGui;
use Xaraya\Modules\MethodClass;
use xarVar;
use xarSec;
use xarMod;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads user save_attachments function
 * @extends MethodClass<UserGui>
 */
class SaveAttachmentsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Save attachments
     * @return bool|void true
     * @see UserGui::saveAttachments()
     */
    public function __invoke(array $args = [])
    {
        // Get parameters
        if (!xarVar::fetch('modname', 'isset', $modname, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('itemtype', 'isset', $itemtype, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('objectid', 'isset', $objectid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('returnurl', 'isset', $returnurl, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('rating', 'isset', $rating, null, xarVar::DONT_SET)) {
            return;
        }

        // Confirm authorisation code
        if (!xarSec::confirmAuthKey()) {
            return;
        }

        // Pass to API
        $newrating = xarMod::apiFunc(
            'ratings',
            'user',
            'rate',
            ['modname'    => $modname,
                'itemtype'   => $itemtype,
                'objectid'   => $objectid,
                'rating'     => $rating, ]
        );

        xarController::redirect($returnurl, null, $this->getContext());

        return true;
    }
}
