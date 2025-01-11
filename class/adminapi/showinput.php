<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\AdminApi;

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\AdminApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use xarModVars;
use xarTpl;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads adminapi showinput function
 * @extends MethodClass<AdminApi>
 */
class ShowinputMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * show input fields for uploads module (used in DD properties)
     * @param mixed $args ['id'] string id of the upload field(s)
     * @param mixed $args ['value'] string the current value(s)
     * @param mixed $args ['format'] string format specifying 'fileupload', 'textupload' or 'upload' (future ?)
     * @param mixed $args ['multiple'] boolean allow multiple uploads or not
     * @param mixed $args ['methods'] array of allowed methods 'trusted', 'external', 'stored' and/or 'upload'
     * @param mixed $args ['override'] array optional override values for import/upload path/obfuscate (cfr. process_files)
     * @param mixed $args ['invalid'] string invalid error message
     * @return string
     * @return string containing the input fields
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (empty($id)) {
            $id = null;
        }
        if (empty($value)) {
            $value = null;
        }
        if (empty($multiple)) {
            $multiple = false;
        } else {
            $multiple = true;
        }
        if (empty($format)) {
            $format = 'fileupload';
        }
        if (empty($methods)) {
            $methods = null;
        }

        // Check to see if an old value is present. Old values just file names
        // and do not start with a semicolon (our delimiter)
        if (xarMod::apiFunc('uploads', 'admin', 'dd_value_needs_conversion', ['value' => $value])) {
            $newValue = xarMod::apiFunc('uploads', 'admin', 'dd_convert_value', ['value' => $value]);

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

        $data = [];

        xarMod::apiLoad('uploads', 'user');

        if (isset($methods) && count($methods) == 4) {
            $data['methods'] = [
                'trusted'  => $methods['trusted'] ? true : false,
                'external' => $methods['external'] ? true : false,
                'upload'   => $methods['upload'] ? true : false,
                'stored'   => $methods['stored'] ? true : false,
            ];
        } else {
            $data['methods'] = [
                'trusted'  => xarModVars::get('uploads', 'dd.fileupload.trusted') ? true : false,
                'external' => xarModVars::get('uploads', 'dd.fileupload.external') ? true : false,
                'upload'   => xarModVars::get('uploads', 'dd.fileupload.upload') ? true : false,
                'stored'   => xarModVars::get('uploads', 'dd.fileupload.stored') ? true : false,
            ];
        }

        $descend = true;

        $data['getAction']['LOCAL']       = Defines::GET_LOCAL;
        $data['getAction']['EXTERNAL']    = Defines::GET_EXTERNAL;
        $data['getAction']['UPLOAD']      = Defines::GET_UPLOAD;
        $data['getAction']['STORED']      = Defines::GET_STORED;
        $data['getAction']['REFRESH']     = Defines::GET_REFRESH_LOCAL;
        $data['id']                       = $id;
        $data['file_maxsize'] = xarModVars::get('uploads', 'file.maxsize');
        if ($data['methods']['trusted']) {
            // if there is an override['import']['path'], try to use that
            if (!empty($override['import']['path'])) {
                $trusted_dir = $override['import']['path'];
                if (!file_exists($trusted_dir)) {
                    // CHECKME: fall back to common trusted directory, or fail here ?
                    $trusted_dir = sys::root() . "/" . xarModVars::get('uploads', 'imports_directory');
                    //  return xarML('Unable to find trusted directory #(1)', $trusted_dir);
                }
            } else {
                $trusted_dir = sys::root() . "/" . xarModVars::get('uploads', 'imports_directory');
            }
            $cacheExpire = xarModVars::get('uploads', 'file.cache-expire');

            // CHECKME: use 'imports' name like in db_get_file() ?
            // Note: for relativePath, the (main) import directory is replaced by /trusted in file_get_metadata()
            $data['fileList']     = xarMod::apiFunc(
                'uploads',
                'user',
                'import_get_filelist',
                ['fileLocation' => $trusted_dir,
                    'descend'      => $descend,
                    // no need to analyze the mime type here
                    'analyze'      => false,
                    // cache the results if configured
                    'cacheExpire'  => $cacheExpire, ]
            );
        } else {
            $data['fileList']     = [];
        }
        if ($data['methods']['stored']) {
            // if there is an override['upload']['path'], try to use that
            if (!empty($override['upload']['path'])) {
                $upload_directory = $override['upload']['path'];
                if (file_exists($upload_directory)) {
                    $data['storedList']   = xarMod::apiFunc(
                        'uploads',
                        'user',
                        'db_get_file',
                        // find all files located under that upload directory
                        ['fileLocation' => $upload_directory . '/%']
                    );
                } else {
                    // Note: the parent directory must already exist
                    $result = @mkdir($upload_directory);
                    if ($result) {
                        // create dummy index.html in case it's web-accessible
                        @touch($upload_directory . '/index.html');
                        // the upload directory is still empty for the moment
                        $data['storedList']   = [];
                    } else {
                        // CHECKME: fall back to common uploads directory, or fail here ?
                        //  $data['storedList']   = xarMod::apiFunc('uploads', 'user', 'db_getall_files');
                        return xarML('Unable to create upload directory #(1)', $upload_directory);
                    }
                }
            } else {
                $data['storedList']   = xarMod::apiFunc('uploads', 'user', 'db_getall_files');
            }
        } else {
            $data['storedList']   = [];
        }

        // used to allow selection of multiple files
        $data['multiple_' . $id] = $multiple;

        if (!empty($value)) {
            // We use array_filter to remove any values from
            // the array that are empty, null, or false
            $aList = array_filter(explode(';', $value));

            if (is_array($aList) && count($aList)) {
                $data['inodeType']['DIRECTORY']   = Defines::TYPE_DIRECTORY;
                $data['inodeType']['FILE']        = Defines::TYPE_FILE;
                $data['Attachments'] = xarMod::apiFunc(
                    'uploads',
                    'user',
                    'db_get_file',
                    ['fileId' => $aList]
                );
                $list = xarMod::apiFunc(
                    'uploads',
                    'user',
                    'showoutput',
                    ['value' => $value, 'style' => 'icon', 'multiple' => $multiple]
                );

                foreach ($aList as $fileId) {
                    if (!empty($data['storedList'][$fileId])) {
                        $data['storedList'][$fileId]['selected'] = true;
                    } elseif (!empty($data['Attachments'][$fileId])) {
                        // add it to the list (e.g. from another user's upload directory - we need this when editing)
                        $data['storedList'][$fileId] = $data['Attachments'][$fileId];
                        $data['storedList'][$fileId]['selected'] = true;
                    } else {
                        // missing data for $fileId
                    }
                }
            }
        }

        if (!empty($invalid)) {
            $data['invalid'] = $invalid;
        }
        $data['context'] ??= $this->getContext();
        // TODO: different formats ?
        return ($list ?? '') . xarTpl::module('uploads', 'user', 'attach_files', $data, null);
    }
}
