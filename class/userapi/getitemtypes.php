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
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi getitemtypes function
 * @extends MethodClass<UserApi>
 */
class GetitemtypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function to retrieve the list of item types of this module (if any)
     * @return array array containing the item types and their description
     * @see UserApi::getitemtypes()
     */
    public function __invoke(array $args = [])
    {
        $itemtypes = [];

        // Files
        $id = 1;
        $itemtypes[$id] = ['label' => xarML('Files'),
            'title' => xarML('View All Files'),
            'url'   => xarController::URL('uploads', 'admin', 'view'),
        ];

        // TODO: Assoc, VDir and other future tables ?

        return $itemtypes;
    }
}
