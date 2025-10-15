<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\AdminApi;

use Xaraya\Modules\Uploads\AdminApi;
use Xaraya\Modules\MethodClass;
use sys;

sys::import('xaraya.modules.method');

/**
 * uploads adminapi dd_value_needs_conversion function
 * @extends MethodClass<AdminApi>
 */
class DdValueNeedsConversionMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Summary of __invoke
     * @param array<mixed> $args
     * @return bool
     * @see AdminApi::ddValueNeedsConversion()
     */
    public function __invoke(array $args = [])
    {
        // replaced where this was called - needs to be an array too
        extract($args);

        // if the value is empty or it has a value starting with ';'
        // Then it doesn't need to be converted - so return false.
        if (empty($value) || (strlen($value) && ';' == $value[0])) {
            // conversion not needed
            return false;
        } else {
            // conversion needed
            return true;
        }
    }
}
