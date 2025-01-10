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
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi import_external_file function
 */
class ImportExternalFileMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Retrieves an external file using the File scheme
     *  @author  Carl P. Corliss
     * @access public
     * @param   array  uri     the array containing the broken down url information
     * @return array          FALSE on error, otherwise an array containing the fileInformation
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

        $fileList = xarMod::apiFunc(
            'uploads',
            'user',
            'import_get_filelist',
            ['fileLocation' => $uri['path'],
                'descend' => $descend, ]
        );

        if (empty($fileList) || (is_array($fileList) && !count($fileList))) {
            return [];
        }

        $list = [];
        foreach ($fileList as $location => $fileInfo) {
            if ($fileInfo['inodeType'] == _INODE_TYPE_DIRECTORY) {
                $list += xarMod::apiFunc(
                    'uploads',
                    'user',
                    'import_get_filelist',
                    ['fileLocation' => $location, 'descend' => true]
                );
                unset($fileList[$location]);
            }
        }

        $fileList += $list;
        unset($list);


        return $fileList;
    }
}
