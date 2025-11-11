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
use Xaraya\Modules\MethodClass;

/**
 * uploads admin newhook function
 * @extends MethodClass<AdminGui>
 */
class NewhookMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Summary of __invoke
     * @param array<mixed> $args
     * @return string
     * @see AdminGui::newhook()
     */
    public function __invoke(array $args = [])
    {
        // TODO: do you really want to generate some input field here or not ?

        // TODO: update the upload's module-ID to correspond to the article's ID
        return '';
    }
}
