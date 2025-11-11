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
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;

/**
 * uploads user display_attachments function
 * @extends MethodClass<UserGui>
 */
class DisplayAttachmentsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * display rating for a specific item, and request rating
     * @param array<mixed> $args
     * @var mixed $objectid ID of the item this rating is for
     * @var mixed $extrainfo URL to return to if user chooses to rate
     * @var mixed $style style to display this rating in (optional)
     * @var mixed $itemtype item type
     * @return string|void output with rating information
     * @see UserGui::displayAttachments()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        $this->var()->get('inode', $inode, 'regexp:/(?<!\.{2,2}\/)[\w\d]*/');

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
            $modname = $this->mod()->getName();
        }

        $args['modName']  = $modname;
        $args['modid']    = $this->mod()->getRegID($modname);
        $args['itemtype'] = $itemtype ?? 0;
        $args['itemid']   = $objectid;

        // save the current attachment info for use later on if the
        // user decides to add / remove attachments for this item
        $this->mod()->setUserVar('save.attachment-info', serialize($args));

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        // Run API function
        $associations = $userapi->dbGetAssociations($args);

        if (!empty($associations)) {
            $fileIds = [];
            foreach ($associations as $assoc) {
                $fileIds[] = $assoc['fileId'];
            }

            $Attachments = $userapi->dbGetFile(['fileId' => $fileIds]);
        } else {
            $Attachments = [];
        }

        $data = $args;
        $data['Attachments']              = $Attachments;
        $data['local_import_post_url']    = $this->mod()->getURL('user', 'display_attachments');
        // module name is mandatory here, because this is displayed via hooks (= from within another module)
        $data['authid'] = $this->sec()->genAuthKey();
        return $data;
    }
}
