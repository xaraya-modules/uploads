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
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi validate_file function
 * @extends MethodClass<UserApi>
 */
class ValidateFileMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Check an uploaded file for valid mime-type, and any errors that might
     *  have been encountered during the upload
     *  @author  Carl P. Corliss
     * @access private
     * @param   array   fileInfo               An array containing (fileName, fileType, fileSrc, fileSize, error):
     *                  fileInfo['fileName']   The (original) name of the file (minus any path information)
     *                  fileInfo['fileType']   The mime content-type of the file
     *                  fileInfo['fileSrc']    The temporary file name (complete path) of the file
     *                  fileInfo['error']      Number representing any errors that were encountered during the upload
     *                  fileInfo['fileSize']   The size of the file (in bytes)
     * @return boolean            TRUE if it passed the checks, FALSE otherwise
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileInfo)) {
            $msg = xarML(
                'Missing parameter [#(1)] for function [(#(2)] in module [#(3)]',
                'fileInfo',
                'validate_file',
                'uploads'
            );
            throw new Exception($msg);
        }

        // TODO: add functionality to validate properly formatted filename

        switch ($fileInfo['error']) {
            case 1: // The uploaded file exceeds the upload_max_filesize directive in php.ini
                $msg = xarML('File size exceeds the maximum allowable based on your system settings.');
                throw new Exception($msg);

            case 2: // The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form
                $msg = xarML('File size exceeds the maximum allowable.');
                throw new Exception($msg);

            case 3: // The uploaded file was only partially uploaded
                $msg = xarML('The file was only partially uploaded.');
                throw new Exception($msg);

            case 4: // No file was uploaded
                $msg = xarML('No file was uploaded..');
                throw new Exception($msg);
            default:
            case 0:  // no error
                break;
        }


        if (!is_uploaded_file($fileInfo['fileSrc'])) {
            $msg = xarML('Possible attempted malicious file upload.');
            throw new Exception($msg);
        }

        return true;
    }
}
