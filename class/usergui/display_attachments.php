<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserGui;

use Xaraya\Modules\MethodClass;
use xarVar;
use xarMod;
use xarModUserVars;
use xarController;
use xarSec;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads user display_attachments function
 */
class DisplayAttachmentsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * display rating for a specific item, and request rating
     * @param mixed $args ['objectid'] ID of the item this rating is for
     * @param mixed $args ['extrainfo'] URL to return to if user chooses to rate
     * @param mixed $args ['style'] style to display this rating in (optional)
     * @param mixed $args ['itemtype'] item type
     * @return \output
     * @return \output with rating information
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!xarVar::fetch('inode', 'regexp:/(?<!\.{2,2}\/)[\w\d]*/', $inode, '', xarVar::NOT_REQUIRED)) {
            return;
        }

        $data = [];

        $objectid = (isset($objectid)) ? $objectid : 0;
        ;
        $itemtype = 0;

        if (isset($extrainfo)) {
            if (is_array($extrainfo)) {
                if (isset($extrainfo['module']) && is_string($extrainfo['module'])) {
                    $modname = $extrainfo['module'];
                }
                if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
                    $itemtype = $extrainfo['itemtype'];
                }
                if (isset($extrainfo['returnurl']) && is_string($extrainfo['returnurl'])) {
                    $data['returnurl'] = $extrainfo['returnurl'];
                }
            } else {
                $data['returnurl'] = $extrainfo;
            }
        }

        if (empty($modname)) {
            $modname = xarMod::getName();
        }

        $args['modName']  = $modname;
        $args['modid']    = xarMod::getRegId($modname);
        $args['itemtype'] = $itemtype ?? 0;
        $args['itemid']   = $objectid;

        // save the current attachment info for use later on if the
        // user decides to add / remove attachments for this item
        xarModUserVars::set('uploads', 'save.attachment-info', serialize($args));

        // Run API function
        $associations = xarMod::apiFunc('uploads', 'user', 'db_get_associations', $args);

        if (!empty($associations)) {
            $fileIds = [];
            foreach ($associations as $assoc) {
                $fileIds[] = $assoc['fileId'];
            }

            $Attachments = xarMod::apiFunc('uploads', 'user', 'db_get_file', ['fileId' => $fileIds]);
        } else {
            $Attachments = [];
        }

        $data = $args;
        $data['Attachments']              = $Attachments;
        $data['local_import_post_url']    = xarController::URL('uploads', 'user', 'display_attachments');
        // module name is mandatory here, because this is displayed via hooks (= from within another module)
        $data['authid'] = xarSec::genAuthKey('uploads');
        return $data;
    }
}
