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

use Xaraya\Modules\MethodClass;
use xarMod;
use xarTpl;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi transformhook function
 */
class TransformhookMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Primarily used by Articles as a transform hook to turn "upload tags" into various display formats
     * @param mixed $args ['extrainfo']
     * @return mixed
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (is_array($extrainfo)) {
            if (isset($extrainfo['transform']) && is_array($extrainfo['transform'])) {
                foreach ($extrainfo['transform'] as $key) {
                    if (isset($extrainfo[$key])) {
                        $extrainfo[$key] = & $this->transform($extrainfo[$key]);
                    }
                }
                return $extrainfo;
            }
            foreach ($extrainfo as $key => $text) {
                $result[] = & $this->transform($text);
            }
        } else {
            $result = & $this->transform($extrainfo);
        }
        return $result;
    }

    /**
     * Transform the $body parameter
     * @param mixed $body
     */
    public function transform($body)
    {
        while (preg_match('/#(ulid|file|ulidd|ulfn|fileURL|fileIcon|fileName|fileLinkedIcon):([^#]+)#/i', $body, $matches)) {
            $replacement = null;
            array_shift($matches);
            [$type, $id] = $matches;
            switch ($type) {
                case 'ulid':
                    // DEPRECATED
                case 'file':
                    //$replacement = "index.php?module=uploads&func=download&fileId=$id";
                    $list = xarMod::apiFunc('uploads', 'user', 'db_get_file', ['fileId' => $id]);
                    $replacement = xarTpl::module(
                        'uploads',
                        'user',
                        'attachment-list',
                        ['Attachments' => $list,
                            'style' => 'transform', ]
                    );
                    break;
                case 'ulidd':
                    // DEPRECATED
                    //$replacement = "index.php?module=uploads&func=download&fileId=$id";
                    $replacement = xarMod::apiFunc(
                        'uploads',
                        'user',
                        'showoutput',
                        ['value' => $id]
                    );
                    break;
                case 'ulfn': // ULFN is DEPRECATED
                case 'fileLinkedIcon':
                    $list = xarMod::apiFunc('uploads', 'user', 'db_get_file', ['fileId' => $id]);
                    $replacement = xarTpl::module(
                        'uploads',
                        'user',
                        'attachment-list',
                        ['Attachments' => $list]
                    );
                    break;
                case 'fileIcon':
                    $file = xarMod::apiFunc('uploads', 'user', 'db_get_file', ['fileId' => $id]);
                    $file = end($file);
                    $replacement = $file['mimeImage'];
                    break;
                case 'fileURL':
                    $file = xarMod::apiFunc('uploads', 'user', 'db_get_file', ['fileId' => $id]);
                    $file = end($file);
                    $replacement = $file['fileDownload'];
                    break;
                case 'fileName':
                    $file = xarMod::apiFunc('uploads', 'user', 'db_get_file', ['fileId' => $id]);
                    $file = end($file);
                    $replacement = $file['fileName'];
                    break;
                default:
                    $body = xarML("The text '#(1)' is not a valid replacement placeholder", "#$type:$id#");
                    return $body;
            }

            $body = preg_replace("/#$type:$id#/", $replacement, $body);
        }

        return $body;
    }
}
