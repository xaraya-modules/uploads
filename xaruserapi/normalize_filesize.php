<?php
/**
 * Uploads Module
 *
 * @package modules
 * @subpackage uploads module
 * @category Third Party Xaraya Module
 * @version 1.1.0
 * @copyright see the html/credits.html file in this Xaraya release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com/index.php/release/eid/666
 * @author Uploads Module Development Team
 */

/**
 * @param mixed $args with args['fileSize'], integer or null
 */
function uploads_userapi_normalize_filesize(mixed $args = [], $context = null)
{
    if (is_array($args)) {
        extract($args);
    } elseif (is_numeric($args)) {
        $fileSize = $args;
    } else {
        return ['long' => 0, 'short' => 0];
    }

    $size = $fileSize;

    $range = ['', 'KB', 'MB', 'GB', 'TB', 'PB'];

    for ($i = 0; $size >= 1024 && $i < count($range); $i++) {
        $size /= 1024;
    }

    $short = round($size, 2) . ' ' . $range[$i];

    return ['long' => number_format($fileSize), 'short' => $short];
}
