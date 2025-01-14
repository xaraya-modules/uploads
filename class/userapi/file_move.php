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
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi file_move function
 * @extends MethodClass<UserApi>
 */
class FileMoveMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Move a file from one location to another. Can (or will eventually be able to) grab a file from
     *  a remote site via ftp/http/etc and save it locally as well. Note: isUpload=TRUE implies isLocal=True
     * @author  Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var string $fileSrc    Complete path to source file
     * @var string $fileDest   Complete path to destination
     * @var boolean $isUpload   Whether or not this file was uploaded (uses special checks on uploaded files)
     * @var boolean $isLocal    Whether or not the file is a Local file or not (think: grabbing a web page)
     *
     * @return boolean TRUE on success, FALSE otherwise
     * @see UserApi::fileMove()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($force)) {
            $force = true;
        }

        // if it wasn't specified, assume TRUE
        if (!isset($isUpload)) {
            $isUpload = false;
        }

        if (!isset($fileSrc)) {
            $msg = $this->translate(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'fileSrc',
                'file_move',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!isset($fileDest)) {
            $msg = $this->translate(
                'Missing parameter [#(1)] for function [(#(2)] in module [#(3)]',
                'fileDest',
                'file_move',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!is_readable($fileSrc)) {
            $msg = $this->translate('Unable to move file - Source file [#(1)]is unreadable!', $fileSrc);
            throw new Exception($msg);
        }

        if (!file_exists($fileSrc)) {
            $msg = $this->translate('Unable to move file - Source file [#(1)]does not exist!', $fileSrc);
            throw new Exception($msg);
        }

        $dirDest = realpath(dirname($fileDest));

        if (!file_exists($dirDest)) {
            $msg = $this->translate('Unable to move file - Destination directory does not exist!');
            throw new Exception($msg);
        }

        if (!is_writable($dirDest)) {
            $msg = $this->translate('Unable to move file - Destination directory is not writable!');
            throw new Exception($msg);
        }

        $freespace = @disk_free_space($dirDest);
        if (!empty($freespace) && $freespace <= filesize($fileSrc)) {
            $msg = $this->translate('Unable to move file - Destination drive does not have enough free space!');
            throw new Exception($msg);
        }

        if (file_exists($fileDest) && $force != true) {
            $msg = $this->translate('Unable to move file - Destination file already exists!');
            throw new Exception($msg);
        }

        if ($isUpload) {
            if (!move_uploaded_file($fileSrc, $fileDest)) {
                $msg = $this->translate('Unable to move file [#(1)] to destination [#(2)].', $fileSrc, $fileDest);
                throw new Exception($msg);
            }
        } else {
            if (!copy($fileSrc, $fileDest)) {
                $msg = $this->translate('Unable to move file [#(1)] to destination [#(2)].', $fileSrc, $fileDest);
                throw new Exception($msg);
            }
            // Now remove the file :-)
            @unlink($fileSrc);
        }

        return true;
    }
}
