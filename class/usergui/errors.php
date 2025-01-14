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
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads user errors function
 * @extends MethodClass<UserGui>
 */
class ErrorsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Summary of __invoke
     * @param array<mixed> $args
     * @return array|void
     * @see UserGui::errors()
     */
    public function __invoke(array $args = [])
    {
        if (!$this->checkAccess('ViewUploads')) {
            return;
        }

        if (!$this->fetch('layout', 'str:1:100', $data['layout'], '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('maxallowed', 'str:1:100', $data['maxallowed'], '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!$this->fetch('location', 'str:1:100', $data['location'], '', xarVar::NOT_REQUIRED)) {
            return;
        }

        return $data;
    }
}
