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
        if (!$this->sec()->checkAccess('ViewUploads')) {
            return;
        }

        $this->var()->find('layout', $data['layout'], 'str:1:100', '');
        $this->var()->find('maxallowed', $data['maxallowed'], 'str:1:100', '');
        $this->var()->find('location', $data['location'], 'str:1:100', '');

        return $data;
    }
}
