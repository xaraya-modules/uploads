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

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use sys;
use BadParameterException;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads userapi file_push function
 * @extends MethodClass<UserApi>
 */
class FilePushMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Pushes a file to the client browser
     * Note: on success, the calling GUI function should exit()
     * @author Carl P. Corliss
     * @access   public
     * @param array<mixed> $args
     * @var string    $fileName        The name of the file
     * @var string    $fileLocation    The full path to the file
     * @var string    $fileType        The mimetype of the file
     * @var int       $fileSize        The size of the file (in bytes)
     * @var int       $storeType       The type of storage of the file
     * @return  boolean                    This function will return true upon succes and, returns False and throws an exception otherwise
     * @see UserApi::filePush()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($fileName)) {
            $msg = $this->translate(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'fileName',
                'file_push',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!isset($fileLocation)) {
            $msg = $this->translate(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'fileLocation',
                'file_push',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!isset($fileType)) {
            $msg = $this->translate(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'fileType',
                'file_push',
                'uploads'
            );
            throw new Exception($msg);
        }

        if (!isset($storeType)) {
            $msg = $this->translate(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'storeType',
                'file_push',
                'uploads'
            );
            throw new Exception($msg);
        } elseif ($storeType & Defines::STORE_DB_DATA) {
            if (!isset($fileId)) {
                $msg = $this->translate(
                    'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                    'fileId',
                    'file_push',
                    'uploads'
                );
                throw new Exception($msg);
            }
        }

        if (!isset($fileSize)) {
            $msg = $this->translate(
                'Missing parameter [#(1)] for function [#(2)] in module [#(3)]',
                'fileSize',
                'file_push',
                'uploads'
            );
            throw new Exception($msg);
        }
        // Close the buffer, saving it's current contents for possible future use
        // then restart the buffer to store the file
        $finished = false;
        $userapi = $this->getParent();

        $pageBuffer = $userapi->flushPageBuffer();


        if ($storeType & Defines::STORE_FILESYSTEM || ($storeType == Defines::STORE_DB_ENTRY)) {
            // Start buffering for the file
            ob_start();

            $fp = @fopen($fileLocation, 'rb');
            if (is_resource($fp)) {
                do {
                    $data = fread($fp, 65536);
                    if (strlen($data) == 0) {
                        break;
                    } else {
                        print("$data");
                    }
                } while (true);

                fclose($fp);
            }

            // Headers -can- be sent after the actual data
            // Why do it this way? So we can capture any errors and return if need be :)
            // not that we would have any errors to catch at this point but, mine as well
            // do it incase I think of some errors to catch
            header("Pragma: ");
            header("Cache-Control: ");
            header("Content-type: $fileType");
            header("Content-disposition: attachment; filename=\"$fileName\"");
            if ($fileSize) {
                header("Content-length: $fileSize");
            }

            // TODO: evaluate registering shutdown functions to take care of
            //       ending Xaraya in a safe manner
            $finished = true;
        } elseif ($storeType & Defines::STORE_DB_DATA) {
            // Start buffering for the file
            ob_start();

            // FIXME: <rabbitt> if we happen to be pushing a really big file, this
            //        method of grabbing it from the database and pushing will consume
            //        WAY too much memory. Think of an alternate method
            $data = $userapi->dbGetFileData(['fileId' => $fileId]);
            echo implode('', $data);

            // Headers -can- be sent after the actual data
            // Why do it this way? So we can capture any errors and return if need be :)
            // not that we would have any errors to catch at this point but, mine as well
            // do it incase I think of some errors to catch
            header("Pragma: ");
            header("Cache-Control: ");
            header("Content-type: $fileType");
            header("Content-disposition: attachment; filename=\"$fileName\"");
            if ($fileSize) {
                header("Content-length: $fileSize");
            }

            // TODO: evaluate registering shutdown functions to take care of
            //       ending Xaraya in a safe manner
            $finished = true;
        }

        if ($finished) {
            return true;
        }

        // rebuffer the old page data
        for ($i = 0, $total = count($pageBuffer); $i < $total; $i++) {
            ob_start();
            echo $pageBuffer[$i];
        }
        unset($pageBuffer);

        $msg = $this->translate('Could not open file [#(1)] for reading', $fileName);
        throw new BadParameterException(null, $msg);
    }
}
