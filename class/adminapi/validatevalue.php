<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\AdminApi;

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\AdminApi;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use xarModVars;
use xarMod;
use xarVar;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads adminapi validatevalue function
 * @extends MethodClass<AdminApi>
 */
class ValidatevalueMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * validate input values for uploads module (used in DD properties)
     * @param array<mixed> $args
     * @var string $id string id of the upload field(s)
     * @var string $value string the current value(s)
     * @var string $format string format specifying 'fileupload', 'textupload' or 'upload' (future ?)
     * @var boolean $multiple boolean allow multiple uploads or not
     * @var integer $maxsize integer maximum size for upload files
     * @var array $methods array allowed methods 'trusted', 'external', 'stored' and/or 'upload'
     * @var array $override array optional override values for import/upload path/obfuscate (cfr. process_files)
     * @var integer $moduleid integer optional module id for keeping file associations
     * @var integer $itemtype integer optional item type for keeping file associations
     * @var integer $itemid integer optional item id for keeping file associations
     * @return array|bool|void array of (result, value) with result true, false or NULL (= error)
     * @see AdminApi::validatevalue()
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
        if (empty($format)) {
            $format = 'fileupload';
        }
        if (empty($multiple)) {
            $multiple = false;
        } else {
            $multiple = true;
        }
        if (empty($maxsize)) {
            $maxsize = $this->mod()->getVar('file.maxsize');
        }
        if (empty($methods)) {
            $methods = null;
        }
        if (empty($itemtype)) {
            $itemtype = 0;
        }
        $adminapi = $this->getParent();

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

        xarMod::apiLoad('uploads', 'user');

        /** @var UserApi $userapi */
        $userapi = $adminapi->getAPI();

        if (isset($methods) && count($methods) > 0) {
            $typeCheck = 'enum:0:' . Defines::GET_STORED;
            $typeCheck .= (isset($methods['external']) && $methods['external']) ? ':' . Defines::GET_EXTERNAL : '';
            $typeCheck .= (isset($methods['trusted']) && $methods['trusted']) ? ':' . Defines::GET_LOCAL : '';
            $typeCheck .= (isset($methods['upload']) && $methods['upload']) ? ':' . Defines::GET_UPLOAD : '';
            $typeCheck .= ':-2'; // clear value
        } else {
            $typeCheck = 'enum:0:' . Defines::GET_STORED;
            $typeCheck .= ($this->mod()->getVar('dd.fileupload.external') == true) ? ':' . Defines::GET_EXTERNAL : '';
            $typeCheck .= ($this->mod()->getVar('dd.fileupload.trusted') == true) ? ':' . Defines::GET_LOCAL : '';
            $typeCheck .= ($this->mod()->getVar('dd.fileupload.upload') == true) ? ':' . Defines::GET_UPLOAD : '';
            $typeCheck .= ':-2'; // clear value
        }

        $this->var()->find($id . '_attach_type', $action, $typeCheck, -3);

        if (!isset($action)) {
            $action = -3;
        }

        $args['action']    = $action;
        switch ($action) {
            case Defines::GET_UPLOAD:

                $file_maxsize = $this->mod()->getVar('file.maxsize');
                $file_maxsize = $file_maxsize > 0 ? $file_maxsize : $maxsize;

                if (!$this->var()->get('MAX_FILE_SIZE', $maxsize), "int::$file_maxsize") {
                    return;
                }

                if (!$this->var()->validate('array:1:', $_FILES[$id . '_attach_upload'])) {
                    return;
                }

                $upload         = & $_FILES[$id . '_attach_upload'];
                $args['upload'] = & $_FILES[$id . '_attach_upload'];
                break;
            case Defines::GET_EXTERNAL:
                // minimum external import link must be: ftp://a.ws  <-- 10 characters total

                if (!$this->var()->get($id . '_attach_external', }, 'regexp:/^([a-z]*).\/\/(.{7)/', $import, 0, xarVar::NOT_REQUIRED)) {
                    return;
                }

                if (empty($import)) {
                    // synchronize file associations with empty list
                    if (!empty($moduleid) && !empty($itemid)) {
                        $userapi->syncAssociations($moduleid, $itemtype, $itemid);
                    }
                    return [true,null];
                }

                $args['import'] = $import;
                break;
            case Defines::GET_LOCAL:

                if (!$this->var()->get($id . '_attach_trusted', 2}\/, 'list:regexp:/(?<!\.{2)[\w\d]*/', $fileList)) {
                    return;
                }

                // CHECKME: use 'imports' name like in db_get_file() ?
                // replace /trusted coming from showinput() again
                $importDir = sys::root() . "/" . $this->mod()->getVar('imports_directory');
                foreach ($fileList as $file) {
                    $file = str_replace('/trusted', $importDir, $file);
                    $args['fileList']["$file"] = $userapi->fileGetMetadata(['fileLocation' => "$file"]);
                    if (isset($args['fileList']["$file"]['fileSize']['long'])) {
                        $args['fileList']["$file"]['fileSize'] = $args['fileList']["$file"]['fileSize']['long'];
                    }
                }
                break;
            case Defines::GET_STORED:

                if (!$this->var()->find($id . '_attach_stored', $fileList, 'list:int:1:', 0)) {
                    return;
                }


                // If we've made it this far, then fileList was empty to start,
                // so don't complain about it being empty now
                if (empty($fileList) || !is_array($fileList)) {
                    // synchronize file associations with empty list
                    if (!empty($moduleid) && !empty($itemid)) {
                        $userapi->syncAssociations($moduleid, $itemtype, $itemid);
                    }
                    return [true,null];
                }

                // We prepend a semicolon onto the list of fileId's so that
                // we can tell, in the future, that this is a list of fileIds
                // and not just a filename
                $value = ';' . implode(';', $fileList);

                // synchronize file associations with file list
                if (!empty($moduleid) && !empty($itemid)) {
                    $userapi->syncAssociations($moduleid, $itemtype, $itemid, $fileList);
                }

                return [true,$value];
            case '-1':
                return [true,$value];
            case '-2':
                // clear stored value
                return [true, null];
            default:
                if (isset($value)) {
                    if (strlen($value) && $value[0] == ';') {
                        return [true,$value];
                    } else {
                        return [false,null];
                    }
                } else {
                    // If we have managed to get here then we have a NULL value
                    // and $action was most likely either null or something unexpected
                    // So let's keep things that way :-)
                    return [true,null];
                }
        }

        if (!empty($action)) {
            if (isset($storeType)) {
                $args['storeType'] = $storeType;
            }

            $list = $userapi->processFiles($args);
            $storeList = [];
            foreach ($list as $file => $fileInfo) {
                if (!isset($fileInfo['errors'])) {
                    $storeList[] = $fileInfo['fileId'];
                } else {
                    $msg = $this->ml('Error Found: #(1)', $fileInfo['errors'][0]['errorMesg']);
                    throw new Exception($msg);
                }
            }
            if (is_array($storeList) && count($storeList)) {
                // We prepend a semicolon onto the list of fileId's so that
                // we can tell, in the future, that this is a list of fileIds
                // and not just a filename
                $value = ';' . implode(';', $storeList);

                // synchronize file associations with store list
                if (!empty($moduleid) && !empty($itemid)) {
                    $userapi->syncAssociations($moduleid, $itemtype, $itemid, $storeList);
                }
            } else {
                return [false,null];
            }
        } else {
            return [false,null];
        }

        return [true,$value];
    }
}
