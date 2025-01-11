<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserApi;


use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi file_rename function
 * @extends MethodClass<UserApi>
 */
class FileRenameMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Rename a file. (alias for file_move)
     *  @author  Carl P. Corliss
     * @access public
     * @param   <type>
     * @return <type>
     */
    public function __invoke(array $args = [])
    {
        return xarMod::apiFunc('uploads', 'user', 'file_move', $args);
    }
}
