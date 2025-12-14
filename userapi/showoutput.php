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
use Xaraya\Modules\Uploads\AdminApi;
use Xaraya\Modules\MethodClass;

/**
 * uploads userapi showoutput function
 * @extends MethodClass<UserApi>
 */
class ShowoutputMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * show output for uploads module (used in DD properties)
     * @param array<mixed> $args
     * @var string $value The current value(s)
     * @var string $format Format specifying 'fileupload', 'textupload' or 'upload'
     * @return string containing the uploads output
     * @see UserApi::showoutput()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (empty($value)) {
            $value = null;
        }
        if (empty($format)) {
            $format = 'fileupload';
        }
        if (empty($multiple)) {
            $multiple = false;
        }

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        $data = [];

        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        // Check to see if an old value is present. Old values just file names
        // and do not start with a semicolon (our delimiter)
        if ($adminapi->ddValueNeedsConversion(['value' => $value])) {
            $newValue = $adminapi->ddConvertValue(['value' => $value]);

            // if we were unable to convert the value, then go ahead and and return
            // an empty string instead of processing the value and bombing out
            if ($newValue == $value) {
                $value = null;
                unset($newValue);
            } else {
                $value = $newValue;
                unset($newValue);
            }
        }

        // The explode will create an empty indice,
        // so we get rid of it with array_filter :-)
        $value = array_filter(explode(';', $value));
        if (!$multiple) {
            $value = [current($value)];
        }

        // make sure to remove any indices which are empty
        $value = array_filter($value);

        if (empty($value)) {
            return '';
        }


        // FIXME: Quick Fix - Forcing return of raw array of fileId's with their metadata for now
        // Rabbitt :: March 29th, 2004

        if (isset($style) && $style = 'icon') {
            if (is_array($value) && count($value)) {
                $data['Attachments'] = $userapi->dbGetFile(['fileId' => $value]);
            } else {
                $data['Attachments'] = '';
            }

            $data['format'] = $format;
            return $this->render('attachment-list', $data, null);
        } else {
            // return a raw array for now
            return $userapi->dbGetFile(['fileId' => $value]);
        }
    }
}
