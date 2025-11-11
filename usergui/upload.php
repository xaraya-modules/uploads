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
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;

/**
 * uploads user upload function
 * @extends MethodClass<UserGui>
 */
class UploadMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Import a file
     * @param array<mixed> $args
     * @var string $importFrom
     * @return mixed
     * @see UserGui::upload()
     */
    public function __invoke(array $args = [])
    {
        if (!$this->sec()->checkAccess('AddUploads')) {
            return;
        }

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        $this->var()->find('importFrom', $importFrom, 'str:1:');

        $list = $userapi->processFiles([
            'importFrom' => $importFrom,
        ]);

        if (is_array($list) && count($list)) {
            return ['fileList' => $list];
        } else {
            $this->ctl()->redirect($this->mod()->getURL('user', 'uploadform'));
        }
    }
}
