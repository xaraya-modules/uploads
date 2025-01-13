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

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\AdminGui;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use xarSecurity;
use xarVar;
use xarModUserVars;
use xarModVars;
use xarSec;
use xarSession;
use xarTplPager;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads admin view function
 * @extends MethodClass<AdminGui>
 */
class ViewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * The view function for the site admin
     * @param array<mixed> $args
     * @var int $mimetype
     * @var int $subtype
     * @var int $status
     * @var bool $inverse
     * @var int $fileId
     * @var string $fileDo
     * @var int $action
     * @var int $startnum
     * @var int $numitems
     * @var string $sort
     * @var string $catid
     * @return array|void
     * @see AdminGui::view()
     */
    public function __invoke(array $args = [])
    {
        //security check
        if (!xarSecurity::check('AdminUploads')) {
            return;
        }

        /**
         *  Validate variables passed back
         */

        if (!xarVar::fetch('mimetype', 'int:0:', $mimetype, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('subtype', 'int:0:', $subtype, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('status', 'int:0:', $status, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('inverse', 'checkbox', $inverse, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('fileId', 'list:int:1', $fileId, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('fileDo', 'str:5:', $fileDo, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('action', 'int:0:', $action, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('startnum', 'int:0:', $startnum, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('numitems', 'int:0:', $numitems, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('sort', 'enum:id:name:size:user:status', $sort, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('catid', 'str:1:', $catid, null, xarVar::DONT_SET)) {
            return;
        }
        $admingui = $this->getParent();

        /** @var UserApi $userapi */
        $userapi = $admingui->getAPI();

        /**
         *  Determine the filter settings to use for this view
         */
        if (!isset($mimetype) || !isset($subtype) || !isset($status) || !isset($inverse)) {
            // if the filter settings are empty, then
            // grab the users last view filter
            $options  = unserialize(xarModUserVars::get('uploads', 'view.filter'));
            $data     = $options['data'];
            $filter   = $options['filter'];
            unset($options);
        } else {
            // otherwise, grab whatever filter options where passed in
            // and process them to create a filter
            $filters['mimetype'] = $mimetype;
            $filters['subtype']  = $subtype;
            $filters['status']   = $status;
            $filters['inverse']  = $inverse;
            $filters['catid']    = $catid;

            $options  =  $userapi->processFilters($filters);
            $data     = $options['data'];
            $filter   = $options['filter'];
            unset($options);
        }
        // Override catid when we're not simply changing the sort order
        if (!isset($sort)) {
            $filter['catid'] = $catid;
            $data['catid']   = $catid;
        }

        /**
         * Perform all actions
         */

        if (isset($action)) {
            if ($action > 0) {
                if (isset($fileDo)) {
                    // If we got a signal to change status but no list of files to change,
                    // then do nothing
                    if (isset($fileId) && !empty($fileId)) {
                        $args['fileId']     = $fileId;
                    } else {
                        $action = 0;
                    }
                } else {
                    $args['fileType']   = $filter['fileType'];
                    $args['inverse']    = ($inverse ?? false);
                    $args['curStatus']  = $filter['fileStatus'];
                }
            }

            switch ($action) {
                case Defines::STATUS_APPROVED:
                    $userapi->dbChangeStatus($args + ['newStatus'    => Defines::STATUS_APPROVED]);
                    break;
                case Defines::STATUS_SUBMITTED:
                    $userapi->dbChangeStatus($args + ['newStatus'    => Defines::STATUS_SUBMITTED]);
                    break;
                case Defines::STATUS_REJECTED:
                    $userapi->dbChangeStatus($args + ['newStatus'   => Defines::STATUS_REJECTED]);
                    if (xarModVars::get('uploads', 'file.auto-purge')) {
                        if (xarModVars::get('uploads', 'file.delete-confirmation')) {
                            return xarMod::guiFunc('uploads', 'admin', 'purge_rejected', ['confirmation' => false, 'authid' => xarSec::genAuthKey('uploads')]);
                        } else {
                            return xarMod::guiFunc('uploads', 'admin', 'purge_rejected', ['confirmation' => true, 'authid' => xarSec::genAuthKey('uploads')]);
                        }
                    }
                    break;
                case 0: /* Change View or anything not defined */
                default:
                    break;
            }
        }

        /**
         * Grab a list of files based on the defined filter
         */

        if (!isset($numitems)) {
            $numitems = xarModVars::get('uploads', 'view.itemsperpage');
            $skipnum = 1;
        }

        if (!isset($filter) || count($filter) <= 0) {
            $filter = [];
            $filter['startnum'] = $startnum;
            $filter['numitems'] = $numitems;
            $filter['sort']     = $sort;
            $items = $userapi->dbGetallFiles($filter);
        } else {
            $filter['startnum'] = $startnum;
            $filter['numitems'] = $numitems;
            $filter['sort']     = $sort;
            $items = $userapi->dbGetFile($filter);
        }
        $countitems = $userapi->dbCount($filter);

        if (!empty($items)) {
            $data['numassoc'] = $userapi->dbCountAssociations([
                'fileId' => array_keys($items),
            ]);
        }

        if (xarSecurity::check('EditUploads', 0)) {
            $data['diskUsage']['stored_size_filtered'] = $userapi->dbDiskusage($filter);
            $data['diskUsage']['stored_size_total']    = $userapi->dbDiskusage();

            $data['uploadsdir'] = $userapi->dbGetDir(['directory' => 'uploads_directory']);
            if (is_dir($data['uploadsdir'])) {
                $data['diskUsage']['device_free']  = disk_free_space($data['uploadsdir']);
                $data['diskUsage']['device_total'] = disk_total_space($data['uploadsdir']);
            } else {
                $data['diskUsage']['device_free']  = -1;
                $data['diskUsage']['device_total'] = -1;
            }
            $data['diskUsage']['device_used']  = $data['diskUsage']['device_total'] - $data['diskUsage']['device_free'];

            foreach ($data['diskUsage'] as $key => $value) {
                $data['diskUsage'][$key] = $userapi->normalizeFilesize(['fileSize' => $value]);
            }

            $data['diskUsage']['numfiles_filtered']   = $countitems;
            $data['diskUsage']['numfiles_total']      = $userapi->dbCount();
        }
        // now we check to see if the user has enough access to view
        // each particular file - if not, we just silently remove it
        // from the view
        foreach ($items as $key => $fileInfo) {
            unset($instance);
            $instance[0] = $fileInfo['fileTypeInfo']['typeId'];
            $instance[1] = $fileInfo['fileTypeInfo']['subtypeId'];
            $instance[2] = xarSession::getVar('uid');
            $instance[3] = $fileInfo['fileId'];

            if (is_array($instance)) {
                $instance = implode(':', $instance);
            }
            if (!xarSecurity::check('EditUploads', 0, 'File', $instance)) {
                unset($items[$key]);
            }
        }


        /**
         *  Check for exceptions
         */
        if (!isset($items)) {
            return;
        } // throw back

        $data['items'] = $items;
        $data['authid'] = xarSec::genAuthKey();

        // Add pager
        if (!empty($numitems) && $countitems > $numitems) {
            sys::import('modules.base.class.pager');
            $data['pager'] = xarTplPager::getPager(
                $startnum,
                $countitems,
                xarController::URL(
                    'uploads',
                    'admin',
                    'view',
                    ['startnum' => '%%',
                        'numitems' => (empty($skipnum) ? $numitems : null),
                        'sort'     => $sort, ]
                ),
                $numitems
            );
        } else {
            $data['pager'] = '';
        }

        return $data;
    }
}
