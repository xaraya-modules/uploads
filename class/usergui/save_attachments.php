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
        if (!$this->var()->check('modname', $modname)) {
            return;
        }
        if (!$this->var()->check('itemtype', $itemtype)) {
            return;
        }
        if (!$this->var()->check('objectid', $objectid)) {
            return;
        }
        if (!$this->var()->check('returnurl', $returnurl)) {
            return;
        }
        if (!$this->var()->check('rating', $rating)) {
            return;
        }

        // Confirm authorisation code
        if (!$this->sec()->confirmAuthKey()) {
            return;
        }

        // Pass to API
        $newrating = $this->mod()->apiFunc(
            'ratings',
            'user',
            'rate',
            ['modname'    => $modname,
                'itemtype'   => $itemtype,
                'objectid'   => $objectid,
                'rating'     => $rating, ]
        );

        $this->ctl()->redirect($returnurl);

        return true;
    }
}
