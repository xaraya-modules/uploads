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
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi uploadmagic function
 * @extends MethodClass<UserApi>
 */
class UploadmagicMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Summary of __invoke
     * @param array<mixed> $args
     * @return mixed
     * @see UserApi::uploadmagic()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        // @todo it's still magic then :-)
        $fileUpload = $userapi->upload($args);

        if (is_array($fileUpload)) {
            return '#file:' . $fileUpload['ulid'] . '#';
        } else {
            return $fileUpload;
        }
    }
}
