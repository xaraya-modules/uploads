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

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\Mime\UserApi as MimeApi;
use Xaraya\Modules\MethodClass;
use xarModVars;
use xarMod;
use xarModUserVars;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi process_filters function
 * @extends MethodClass<UserApi>
 */
class ProcessFiltersMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Summary of __invoke
     * @param array<mixed> $args
     * @return array
     * @see UserApi::processFilters()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /**
         *  Set up the filter data for the template to use
         */

        if (!isset($storeOptions)) {
            $storeOptions = true;
        }
        $userapi = $this->getParent();

        /** @var MimeApi $mimeapi */
        $mimeapi = $userapi->getMimeAPI();

        $options   =  unserialize(xarModVars::get('uploads', 'view.filter'));

        $data      =  $options['data'];
        $filter    =  $options['filter'];
        $mimetypes = & $data['filters']['mimetypes'];
        $subtypes  = & $data['filters']['subtypes'];
        $statuses  = & $data['filters']['status'];

        $data['filters']['inverse'] = $inverse ?? false;
        $filter['inverse']  = $inverse ?? false;

        unset($options);
        /**
         *  Grab the mimetypes and setup the selected one
         */
        if (isset($mimetype) && $mimetype > 0) {
            $selected_mimetype = $mimeapi->getType(['typeId' => $mimetype]);
        }

        // if selected mimetype isn't set, empty or has an array count of
        // zero, then we set
        if (isset($selected_mimetype) && count($selected_mimetype)) {
            $mimetypes[$mimetype]['selected'] = true;
        } else {
            $mimetypes[0]['selected'] = true;
        }

        /**
         *  Grab the subtypes (if necessary) and setup the selected subtype
         */
        if (isset($selected_mimetype)) {
            if (isset($subtype) && $subtype > 0) {
                $selected_subtype = $mimeapi->getSubtype(['subtypeId' => $subtype]);
            }

            // add the rest of the types to the array
            // array returns is in form of: array[typeId]{[subtypeId], [subtypeName]}
            $subtypes = $subtypes + $mimeapi->getallSubtypes(['typeId' => $selected_mimetype['typeId']]);

            // if selected subtype isn't set, empty or has an array count of
            // zero, then we set
            if (isset($selected_subtype['typeId']) && $selected_subtype['typeId'] == $selected_mimetype['typeId']) {
                $subtypes[$subtype]['selected'] = true;
            } else {
                $subtypes[0]['selected'] = true;
            }
        } else {
            $subtypes[0]['selected'] = true;
        }
        unset($subtypes);
        unset($mimetypes);

        /**
         *  Set up the actual filter that will be passed to the api get function
         */

        if (isset($selected_mimetype)) {
            if (isset($selected_subtype)) {
                $filter['fileType'] = strtolower($selected_mimetype['typeName']) . '/' . strtolower($selected_subtype['subtypeName']);
            } else {
                $filter['fileType'] = strtolower($selected_mimetype['typeName']) . '/%';
            }
        } else {
            $filter['fileType'] = '%';
        }

        if (!isset($status)) {
            $status = '';
        }
        /**
         *  Set up the MIME subtype filter
         */

        switch ($status) {
            case Defines::STATUS_REJECTED:
                $filter['fileStatus'] = Defines::STATUS_REJECTED;
                $statuses[Defines::STATUS_REJECTED]['selected'] = true;
                break;
            case Defines::STATUS_SUBMITTED:
                $filter['fileStatus'] = Defines::STATUS_SUBMITTED;
                $statuses[Defines::STATUS_SUBMITTED]['selected'] = true;
                break;
            case Defines::STATUS_APPROVED:
                $filter['fileStatus'] = Defines::STATUS_APPROVED;
                $statuses[Defines::STATUS_APPROVED]['selected'] = true;
                break;
            case 0:
                $filter['fileStatus'] = '';
                $statuses[0]['selected'] = true;
                break;
            default:
                $filter['fileStatus'] = Defines::STATUS_SUBMITTED;
                $statuses[Defines::STATUS_SUBMITTED]['selected'] = true;
                break;
        }
        unset($statuses);
        $data['catid'] = $catid ?? null;
        $filter['catid'] = $catid ?? null;
        $filterInfo = ['data' => $data, 'filter' => $filter];


        if ($storeOptions) {
            // Save the filter settings for later use
            xarModUserVars::set('uploads', 'view.filter', serialize($filterInfo));
        }

        return $filterInfo;
    }
}
