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

sys::import('xaraya.modules.method');

/**
 * uploads userapi import_external_file function
 * @extends MethodClass<UserApi>
 */
class ImportExternalFileMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieves an external file using the File scheme
     * @author  Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var array $uri     the array containing the broken down url information
     * @return array|bool|void          FALSE on error, otherwise an array containing the fileInformation
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!isset($uri) || !isset($uri['path'])) {
            return; // error
        }

        // create the URI
        $fileURI = "$uri[scheme]://$uri[path]";

        if (is_dir($uri['path']) || @is_dir(readlink($uri['path']))) {
            $descend = true;
        } else {
            $descend = false;
        }
        $userapi = $this->getParent();

        $fileList = $userapi->importGetFilelist([
            'fileLocation' => $uri['path'],
            'descend' => $descend,
        ]);

        if (empty($fileList) || (is_array($fileList) && !count($fileList))) {
            return [];
        }

        $list = [];
        foreach ($fileList as $location => $fileInfo) {
            if ($fileInfo['inodeType'] == Defines::TYPE_DIRECTORY) {
                $list += $userapi->importGetFilelist([
                    'fileLocation' => $location,
                    'descend' => true,
                ]);
                unset($fileList[$location]);
            }
        }

        $fileList += $list;
        unset($list);


        return $fileList;
    }
}
