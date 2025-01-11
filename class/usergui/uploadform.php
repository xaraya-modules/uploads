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


use Xaraya\Modules\Uploads\UserGui;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarSec;
use xarModVars;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads user uploadform function
 * @extends MethodClass<UserGui>
 */
class UploadformMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Show the uploads form
     * @return array
     */
    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('AddUploads')) {
            return;
        }
        // Generate a one-time authorisation code for this operation
        $data['authid'] = xarSec::genAuthKey();
        $data['file_maxsize'] = xarModVars::get('uploads', 'file.maxsize');

        return $data;
    }
}
