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
use Exception;

/**
 * uploads userapi validate_upload function
 * @extends MethodClass<UserApi>
 */
class ValidateUploadMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Validates file based on criteria specified by hooked modules (well, that's the intended future
     *  functionality anyhow - which won't be available until the hooks system has been revamped.
     * .....
     * @author Carl P. Corliss
     * @access  public
     * @param array<mixed> $args
     * @var array $fileInfo               An array containing (fileName, fileType, fileSrc, fileSize, error):
     *                   fileInfo['fileName']   The (original) name of the file (minus any path information)
     *                   fileInfo['fileType']   The mime content-type of the file
     *                   fileInfo['fileSrc']    The temporary file name (complete path) of the file
     *                   fileInfo['error']      Number representing any errors that were encountered during the upload (>= PHP 4.2.0)
     *                   fileInfo['fileSize']   The size of the file (in bytes)
     * @return boolean                      TRUE if checks pass, FALSE otherwise
     * @see UserApi::validateUpload()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileInfo)) {
            $msg = $this->ml(
                'Missing parameter [#(1)] for function [(#(2)] in module [#(3)]',
                'fileInfo',
                'validate_upload',
                'uploads'
            );
            throw new Exception($msg);
        }

        switch ($fileInfo['error']) {
            case 1: // The uploaded file exceeds the upload_max_filesize directive in php.ini
                $msg = $this->ml('File size exceeds the maximum allowable based on the server\'s settings.');
                return $this->ctl()->redirect($this->mod()->getURL(
                    'user',
                    'errors',
                    ['layout' => 'maxfilesize','maxallowed' => ini_get('upload_max_filesize')]
                ));
                //throw new Exception($msg);

            case 2: // The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form
                $msg = $this->ml('File size exceeds the maximum allowable defined by the website administrator.');
                throw new Exception($msg);

            case 3: // The uploaded file was only partially uploaded
                $msg = $this->ml('The file was only partially uploaded.');
                throw new Exception($msg);

            case 4: // No file was uploaded
                $msg = $this->ml('No file was uploaded..');
                throw new Exception($msg);
            default:
            case 0:  // no error
                break;
        }

        $maxsize = $this->mod()->getVar('file.maxsize');
        $maxsize = $maxsize > 0 ? $maxsize : 0;

        if ($fileInfo['size'] > $maxsize) {
            $msg = $this->ml('File size exceeds the maximum allowable defined by the website administrator.');
            throw new Exception($msg);
        }

        if (!is_uploaded_file($fileInfo['fileSrc'])) {
            $msg = $this->ml('Possible attempted malicious file upload.');
            throw new Exception($msg);
        }

        // future functionality - ...
        // if (!$this->mod()->callHooks('item', 'validation', array('type' => 'file', 'fileInfo' => $fileInfo))) {
        //     return FALSE;
        // }
        return true;
    }
}
