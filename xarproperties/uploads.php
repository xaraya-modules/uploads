<?php

/**
 * Uploads Module
 *
 * @package modules
 * @subpackage uploads module
 * @category Third Party Xaraya Module
 * @version 2.6.0
 * @copyright see the html/credits.html file in this Xaraya release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com/index.php/release/eid/666
 * @author Uploads Module Development Team
 */

/**
 * Dynamic Upload Property
 *
 * @package dynamicdata
 * @subpackage properties
 */
/* Include parent class */
sys::import('modules.base.xarproperties.fileupload');
sys::import('modules.uploads.class.defines');
use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\UserApi;

/**
 * Class to handle file upload properties
 *
 * @package dynamicdata
 */
class UploadProperty extends FileUploadProperty
{
    public $id         = 105;
    public $name       = 'uploads';
    public $desc       = 'Upload';
    public $reqmodules = ['uploads'];

    public $display_size                      = 40;
    public $validation_max_file_size          = 0;
    //    public $initialization_basepath         = null;
    public $initialization_basedirectory      = 'html/var/uploads';
    public $initialization_import_directory   = null;
    public $initialization_directory_name     = 'User_';
    public $initialization_file_input_methods = [5,2,1,7];
    public $initialization_initial_method;
    public $validation_max_length             = 10;  // The number of files this property can have
    public $validation_allow_duplicates       = 0;  // 0: no duplicates, 1: upload the dupliacte, 2: use the existing entry

    public $propertydata;                   // This is the data set assembled by the checkInput/validateValue method
    public $dbvalue;                        // Holds the last value saved in the db. Useful when using the file tag
    public $upload_clear = 0;               // Flag to clear the stored db value(s) of an file tag

    /*
    Trusted/local  --> 5  check xaruserapi.php for the list of all allowed constants.
    External       --> 2
    Uploads        --> 1
    Stored         --> 7
    */

    // the file data stored by this property
    public $filedata = [];

    // this is used by DataPropertyMaster::addProperty() to set the $object->upload flag
    public $upload = true;

    public function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule =  'uploads';
        $this->template =  'uploads';
        $this->filepath   = 'modules/uploads/xarproperties';

        // this is used by DD's importpropertytypes() function
        if (empty($args['skipInit'])) {                            // this parameter is not found in the core code
            // Note : {user} will be replaced by the current user uploading the file - e.g. var/uploads/{user} -> var/uploads/myusername_123
            $uid = xarSession::getVar('role_id');
            if (!empty($this->initialization_basedirectory) && preg_match('/\{user\}/', $this->initialization_basedirectory)) {
                // Note: we add the userid just to make sure it's unique e.g. when filtering
                // out unwanted characters through xarVar::prepForOS, or if the database makes
                // a difference between upper-case and lower-case and the OS doesn't...
                $udir = xarVar::prepForOS($this->initialization_directory_name) . $uid;
                $this->initialization_basedirectory = preg_replace('/\{user\}/', $udir, $this->initialization_basedirectory);
            }
            if (!empty($this->initialization_import_directory) && preg_match('/\{user\}/', $this->initialization_import_directory)) {
                // Note: we add the userid just to make sure it's unique e.g. when filtering
                // out unwanted characters through xarVar::prepForOS, or if the database makes
                // a difference between upper-case and lower-case and the OS doesn't...
                $udir = xarVar::prepForOS($this->initialization_directory_name) . $uid;
                $this->initialization_import_directory = preg_replace('/\{user\}/', $udir, $this->initialization_import_directory);
            }
        }
        $this->validation_max_file_size = xarModVars::get('uploads', 'file.maxsize');
        $this->initialization_import_directory = sys::root() . "/" . xarModVars::get('uploads', 'imports_directory');
        $this->initialization_basedirectory    = sys::root() . "/" . $this->initialization_basedirectory;

        // Save the value in a separate var that won't be changed with this->value
    }

    /**
     * Summary of userapi
     * @return UserApi
     */
    public function userapi()
    {
        /** @var UserApi $userapi */
        $userapi = xarMod::getAPI('uploads');
        return $userapi;
    }

    public function checkInput($name = '', $value = null)
    {
        //        if (isset($this->fieldname)) $name = $this->fieldname;
        //        else $name = 'dd_'.$this->id;
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }

        if (!xarVar::fetch($name . '_dbvalue', 'str', $dbvalue, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch($name . '_clear', 'checkbox', $clear, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        //        echo $name . '_dbvalue';
        $this->dbvalue = $dbvalue;
        $this->upload_clear = $clear;
        return parent::checkInput($name, $value);
    }

    /**
     * Validate the value entered
     */
    public function validateValue($value = null)
    {
        // TODO: move some of this to the parent
        // problematic, because the field names are different here and for the parent
        //        if (!parent::validateValue($value)) return false;

        if (isset($this->fieldname)) {
            $name = $this->fieldname;
        } else {
            $name = 'dd_' . $this->id;
        }

        // retrieve new value for preview + new/modify combinations
        if (xarVar::isCached('DynamicData.Upload', $name)) {
            $this->value = xarVar::getCached('DynamicData.Upload', $name);
            return true;
        }

        xarMod::apiLoad('uploads', 'user');

        $userapi = $this->userapi();

        $data['action'] = $this->getActiveInputMethod($name);

        switch ($data['action']) {
            case Defines::GET_UPLOAD:
                if (!xarVar::fetch($name . '_max_file_size', "int::$this->validation_max_file_size", $this->validation_max_file_size)) {
                    return;
                }
                if (!xarVar::validate('array', $_FILES[$name . '_attach_upload'])) {
                    return;
                }

                $data['upload'] = & $_FILES[$name . '_attach_upload'];
                //array_pop($data['upload']);
                if (empty($data['upload']['name'])) {
                    // No file name entered, ignore
                    $this->value = '';
                    return true;
                } elseif (!$this->validateExtension($data['upload']['name'])) {
                    $this->invalid = xarMLS::translate('The file type is not allowed');
                    $this->value = null;
                    return false;
                }
                break;
            case Defines::GET_EXTERNAL:
                // minimum external import link must be: ftp://a.ws  <-- 10 characters total

                if (!xarVar::fetch($name . '_attach_external', 'regexp:/^([a-z]*).\/\/(.{7,})/', $import, 0, xarVar::NOT_REQUIRED)) {
                    return;
                }

                if (empty($import)) {
                    // synchronize file associations with empty list
                    if (!empty($moduleid) && !empty($itemid)) {
                        $itemtype = $this->objectref?->itemtype ?? 0;
                        $this->sync_associations($moduleid, $itemtype, $itemid);
                    }
                    $this->value = null;
                    xarVar::setCached('DynamicData.Upload', $name, $this->value);
                    return true;
                }

                $data['import'] = $import;
                break;
            case Defines::GET_LOCAL:

                if (!xarVar::fetch($name . '_attach_trusted', 'list:regexp:/(?<!\.{2,2}\/)[\w\d]*/', $fileList, [], xarVar::NOT_REQUIRED)) {
                    return;
                }

                // CHECKME: use 'imports' name like in db_get_file() ?
                // replace /trusted coming from showinput() again
                $importDir = $this->initialization_import_directory;
                $data['fileList'] = [];
                foreach ($fileList as $file) {
                    $file = str_replace('/trusted', $importDir, $file);
                    $data['fileList']["$file"] = $userapi->fileGetMetadata([
                        'fileLocation' => "$file",
                    ]);
                    if (isset($data['fileList']["$file"]['fileSize']['long'])) {
                        $data['fileList']["$file"]['fileSize'] = $data['fileList']["$file"]['fileSize']['long'];
                    }
                }
                break;
            case Defines::GET_STORED:

                if (!xarVar::fetch($name . '_attach_stored', 'list:int:1:', $fileList, 0, xarVar::NOT_REQUIRED)) {
                    return;
                }

                // If we've made it this far, then fileList was empty to start,
                // so don't complain about it being empty now
                if (empty($fileList) || !is_array($fileList)) {
                    // synchronize file associations with empty list
                    if (!empty($moduleid) && !empty($itemid)) {
                        $itemtype = $this->objectref?->itemtype ?? 0;
                        $this->sync_associations($moduleid, $itemtype, $itemid);
                    }
                    $this->value = null;
                    xarVar::setCached('DynamicData.Upload', $name, $this->value);
                    return true;
                }

                // We prepend a semicolon onto the list of fileId's so that
                // we can tell, in the future, that this is a list of fileIds
                // and not just a filename
                $this->value = ';' . implode(';', $fileList);

                // synchronize file associations with file list
                if (!empty($moduleid) && !empty($itemid)) {
                    $itemtype = $this->objectref?->itemtype ?? 0;
                    $this->sync_associations($moduleid, $itemtype, $itemid, $fileList);
                }

                return true;
            case '-1':
                return true;
            case '-2':
                // clear stored value
                $this->value = '';
                xarVar::setCached('DynamicData.Upload', $name, $this->value);
                return true;
            default:
                if (isset($value)) {
                    if (strlen($value) && $value[0] == ';') {
                        return true;
                    } else {
                        $this->value = null;
                        return false;
                    }
                } else {
                    // If we have managed to get here then we have a NULL value
                    // and $action was most likely either null or something unexpected
                    // So let's keep things that way :-)
                    $this->value = null;
                    return true;
                }
        }

        //        if(!$this->createValue($data))return false;

        // Store the particulares so the createValue method can find them
        $this->propertydata = $data;
        xarVar::setCached('DynamicData.Upload', $name, $this->value);
        return true;
    }

    public function updateValue($itemid = 0)
    {
        return $this->createValue($itemid);
    }

    public function createValue($itemid = 0)
    {
        $data = $this->propertydata;
        if (!empty($data['action'])) {
            //            if (isset($storeType)) $data['storeType'] = $storeType;
            $userapi = $this->userapi();

            // This is where the actual saves happen
            $data['override']['upload']['path'] = $this->initialization_basedirectory;
            // Check for duplicates. This should actually happen in the validateValue method
            $data['allow_duplicate'] = $this->validation_allow_duplicates;
            $list = $userapi->processFiles($data);

            $storeList = [];
            $storeListData = [];
            foreach ($list as $file => $fileInfo) {
                if (!isset($fileInfo['errors'])) {
                    $storeList[] = $fileInfo['fileId'];
                    $storeListData[] = $fileInfo;
                } else {
                    $this->invalid .= xarMLS::translate('Invalid upload: #(1)', $fileInfo['fileName'] . " " . $fileInfo['errors'][0]['errorMesg']);
                }
            }
            if (!empty($this->invalid)) {
                $this->value = null;
                return false;
            }
            if (!empty($storeList)) {
                $this->filedata = $storeListData;
                // We prepend a semicolon onto the list of fileId's so that
                // we can tell, in the future, that this is a list of fileIds
                // and not just a filename
                $this->value = ';' . implode(';', $storeList);

                // synchronize file associations with store list
                if (!empty($moduleid) && !empty($itemid)) {
                    $itemtype = $this->objectref?->itemtype ?? 0;
                    $this->sync_associations($moduleid, $itemtype, $itemid, $storeList);
                }
            } else {
                // If the user wants, remove the current stored value(s). Otherwise do nothing
                if ($this->upload_clear) {
                    $this->value = '';
                    $this->dbvalue = '';
                } else {
                    $this->value = null;
                }
            }
        }
        return true;
    }

    /**
     * Utility function to synchronise uploads associations on validation
     * Given a list of resource IDs, this makes sure that there is an entry in the associations table
     * for each with the appropriate $itemid, $itemtype, $moduleid
     * @see Xaraya\Modules\Uploads\UserApi::syncAssociations()
     */
    public function sync_associations($moduleid = 0, $itemtype = 0, $itemid = 0, $filelist = [])
    {
        $userapi = $this->userapi();
        $userapi->syncAssociations($moduleid, $itemtype, $itemid, $filelist);
    }

    /**
     * Show the input form
     */
    public function showInput(array $data = [])
    {
        // inform anyone that we're showing a file upload field, and that they need to use
        // <form ... enctype="multipart/form-data" ... > in their input form
        xarVar::setCached('Hooks.dynamicdata', 'withupload', 1);

        if (!empty($data['name'])) {
            $this->name = $data['name'];
        }
        if (empty($data['name'])) {
            $data['name'] = 'dd_' . $this->id;
        }
        if (!empty($data['value'])) {
            $this->value = $data['value'];
        }
        if (!empty($data['basedir'])) {
            $this->initialization_basedirectory = $data['basedir'];
        }
        if (!empty($data['importdir'])) {
            $this->initialization_import_directory = $data['importdir'];
        }
        if (!empty($data['max_file_size'])) {
            $this->validation_max_file_size = $data['max_file_size'];
        }
        if (!empty($data['methods'])) {
            if (!is_array($data['methods'])) {
                $data['methods'] = explode(',', $data['methods']);
            }
            $this->initialization_file_input_methods = $data['methods'];
        }
        $descend = true;

        xarMod::apiLoad('uploads', 'user');

        $userapi = $this->userapi();

        $data['getAction']['LOCAL']       = Defines::GET_LOCAL;
        $data['getAction']['EXTERNAL']    = Defines::GET_EXTERNAL;
        $data['getAction']['UPLOAD']      = Defines::GET_UPLOAD;
        $data['getAction']['STORED']      = Defines::GET_STORED;
        $data['getAction']['REFRESH']     = Defines::GET_REFRESH_LOCAL;
        //    $data['id']                       = $id;

        // Set up for the trusted input method
        if (in_array(Defines::GET_LOCAL, $this->initialization_file_input_methods)) {
            if (!file_exists($this->initialization_import_directory)) {
                $msg = xarMLS::translate('Unable to find trusted directory #(1)', $this->initialization_import_directory);
                throw new Exception($msg);
            }
            $cacheExpire = xarModVars::get('uploads', 'file.cache-expire');

            // CHECKME: use 'imports' name like in db_get_file() ?
            // Note: for relativePath, the (main) import directory is replaced by /trusted in file_get_metadata()
            $data['fileList']     = $userapi->importGetFilelist([
                'fileLocation' => $this->initialization_import_directory,
                'descend'      => $descend,
                // no need to analyze the mime type here
                'analyze'      => false,
                // cache the results if configured
                'cacheExpire'  => $cacheExpire,
            ]);
        } else {
            $data['fileList']     = [];
        }

        // Set up for the stored input method
        if (in_array(Defines::GET_STORED, $this->initialization_file_input_methods)) {
            $userapi = $this->userapi();

            // if there is an override['upload']['path'], try to use that
            if (!empty($this->initialization_basedirectory)) {
                if (file_exists($this->initialization_basedirectory)) {
                    // find all files located under that upload directory
                    $data['storedList']   = $userapi->dbGetFile([
                        'fileLocation' => $this->initialization_basedirectory . '/%',
                    ]);
                } else {
                    // Note: the parent directory must already exist
                    $result = @mkdir($this->initialization_basedirectory);
                    if ($result) {
                        // create dummy index.html in case it's web-accessible
                        @touch($this->initialization_basedirectory . '/index.html');
                        // the upload directory is still empty for the moment
                        $data['storedList']   = [];
                    } else {
                        // CHECKME: fall back to common uploads directory, or fail here ?
                        //  $data['storedList']   = $userapi->dbGetallFiles();
                        $msg = xarMLS::translate('Unable to create an upload directory #(1)', $this->initialization_basedirectory);
                        throw new Exception($msg);
                    }
                }
            } else {
                $data['storedList']   = $userapi->dbGetallFiles();
            }
        } else {
            $data['storedList']   = [];
        }
        // This is the maximum number of files this property can upload
        if (!empty($data['multiple'])) {
            $data['multiple_' . $data['name']] = $data['multiple'];
        } else {
            $data['multiple_' . $data['name']] = $this->validation_max_length;
        }

        // Set up for the stored input method
        if (in_array(Defines::GET_UPLOAD, $this->initialization_file_input_methods)) {
            if (!empty($this->value)) {
                $this->dbvalue = $this->value;
            }
            if (!empty($data['value'])) {
                $this->value = $data['value'];
            }

            // If we have an empty value it might mean we submitted the form with nothing in the file tag
            // There might still be a value saved. Check it
            if (empty($this->value)) {
                $this->value = $this->dbvalue;
            }

            if (!empty($this->value)) {
                // We use array_filter to remove any values from
                // the array that are empty, null, or false
                $aList = array_filter(explode(';', $this->value));

                if (is_array($aList) && count($aList)) {
                    $data['inodeType']['DIRECTORY']   = Defines::TYPE_DIRECTORY;
                    $data['inodeType']['FILE']        = Defines::TYPE_FILE;
                    $data['attachments'] = $userapi->dbGetFile([
                        'fileId' => $aList,
                    ]);
                    // @todo what is happening with this?
                    $list = $userapi->showoutput([
                        'value' => $this->value,
                        'style' => 'icon',
                        'multiple' => $this->validation_max_length,
                    ]);

                    foreach ($aList as $fileId) {
                        if (!empty($data['storedList'][$fileId])) {
                            $data['storedList'][$fileId]['selected'] = true;
                        } elseif (!empty($data['attachments'][$fileId])) {
                            // add it to the list (e.g. from another user's upload directory - we need this when editing)
                            $data['storedList'][$fileId] = $data['attachments'][$fileId];
                            $data['storedList'][$fileId]['selected'] = true;
                        } else {
                            // missing data for $fileId
                        }
                    }
                }
            }
            $data['dbvalue'] = $this->dbvalue;
        }
        if (!isset($data['dbvalue'])) {
            $data['dbvalue'] = '';
        }
        $data['file_input_methods'] = $this->initialization_file_input_methods;
        $data['initial_method'] = !empty($this->initialization_initial_method) ? $this->initialization_initial_method : current($this->initialization_file_input_methods);
        $data['active_method'] = $this->getActiveInputMethod($data['name']);
        $data['max_file_size'] = $this->validation_max_file_size;

        // Jump over the direct parent for now
        return DataProperty::showInput($data);
    }

    /**
     * Show the output: a link to the file
     */
    public function showOutput(array $data = [])
    {
        if (empty($data['value'])) {
            $data['value'] = $this->value;
        }
        if (empty($data['multiple'])) {
            $data['multiple'] = $this->validation_max_length;
        }
        if (empty($data['format'])) {
            $data['format'] = 'fileupload';
        }

        // The explode will create an empty indice,
        // so we get rid of it with array_filter :-)
        $data['value'] = array_filter(explode(';', $data['value']));
        if (!$data['multiple']) {
            $data['value'] = [current($data['value'])];
        }

        // make sure to remove any indices which are empty
        $data['value'] = array_filter($data['value']);

        if (is_array($data['value']) && count($data['value'])) {
            $userapi = $this->userapi();

            $data['attachments'] = $userapi->dbGetFile(['fileId' => $data['value']]);
            if (empty($data['attachments'])) {
                // We probably have just a single file name
                $data['attachments'][] = ['fileDownload' => $this->initialization_basedirectory . "/" . $data['value'][0],
                    'fileName' => $data['value'][0],
                    'DownloadLabel' => $data['value'][0],
                ];
            }
        } else {
            if (empty($data['value'])) {
                $data['attachments'] = [];
            } else {
                // We probably have just a single file name
                $data['attachments'][] = ['fileDownload' => $this->initialization_basedirectory . "/" . $data['value'][0],
                    'fileName' => $data['value'][0],
                    'DownloadLabel' => $data['value'][0],
                ];
            }
        }

        // Jump over the direct parent because it uses text string field names as file entries
        return DataProperty::showOutput($data);
    }

    public function getActiveInputMethod($name = null)
    {
        if (!empty($this->initialization_initial_method)) {
            return $this->initialization_initial_method;
        }
        if (empty($name)) {
            $name = $this->name;
        }
        $typeCheck = 'enum:0';
        if (!empty($this->initialization_file_input_methods)) {
            $typeCheck .= (in_array(Defines::GET_LOCAL, $this->initialization_file_input_methods)) ? ':' . Defines::GET_LOCAL : '';
            $typeCheck .= (in_array(Defines::GET_EXTERNAL, $this->initialization_file_input_methods)) ? ':' . Defines::GET_EXTERNAL : '';
            $typeCheck .= (in_array(Defines::GET_UPLOAD, $this->initialization_file_input_methods)) ? ':' . Defines::GET_UPLOAD : '';
            $typeCheck .= (in_array(Defines::GET_STORED, $this->initialization_file_input_methods)) ? ':' . Defines::GET_STORED : '';
            $typeCheck .= ':-2'; // clear value
            xarVar::fetch($name . '_active_method', $typeCheck, $activemethod, current($this->initialization_file_input_methods), xarVar::NOT_REQUIRED);
        }
        return $activemethod;
    }

    /**
     * Get the value of this property (= for a particular object item)
     *
     * (keep this for compatibility with old Uploads values)
     *
     * @return mixed the value for the property
     */
    public function getValue()
    {
        $value = $this->value;

        if (empty($value)) {
            return $value;
            // For current values when DD stored the ULID
        } elseif (is_numeric($value)) {
            $ulid = ";$value";
            // For old values, pull the ULID from the URL that is stored
        } elseif (strstr($value, 'ulid=')) {
            mb_ereg('ulid=([0-9]+)', $value, $reg);
            $ulid = ";$reg[1]";
            // For new values when DD stores a ;-separated list
        } elseif (strstr($value, ';')) {
            $ulid = $value;
        }
        if (empty($ulid)) {
            $ulid = null;
        }
        return $ulid;
    }


    /*
        function parseValidation($validation = '')
        {
            list($multiple, $methods, $basedir, $importdir) = xarMod::apiFunc('uploads', 'admin', 'dd_configure', $validation);

            $this->initialization_multiple_files = $multiple;
            $this->initialization_methods = $methods;
            $this->initialization_basedirectory = $basedir;
            $this->initialization_import_directory = $importdir;
            $this->maxsize = xarModVars::get('uploads', 'file.maxsize');
        }

        function showValidation(Array $args = array())
        {
            extract($args);

            $data = array();
            $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
            $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
            $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
            $data['invalid']    = !empty($this->invalid) ? xarMLS::translate('Invalid #(1)', $this->invalid) :'';

            $data['size']       = !empty($size) ? $size : 50;
            $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;

            if (isset($validation)) {
                $this->validation = $validation;
                $this->parseValidation($validation);
            }

            $data['multiple'] = $this->initialization_multiple_files;
            $data['file_input_methods'] = $this->initialization_file_input_methods;
            $data['basedir'] = $this->initialization_basedirectory;
            $data['importdir'] = $this->initialization_import_directory;
            $data['other'] = '';

            // allow template override by child classes
            if (!isset($template)) {
                $template = '';
            }
            return xarTpl::property('uploads', 'upload', 'validation', $data);
        }

        function updateValidation(Array $args = array())
        {
            extract($args);

            // in case we need to process additional input fields based on the name
            if (empty($name)) {
                $name = 'dd_'.$this->id;
            }
            // do something with the validation and save it in $this->validation
            if (isset($validation)) {
                if (is_array($validation)) {
                    if (!empty($validation['other'])) {
                        $this->validation = $validation['other'];

                    } else {
                        $this->validation = '';
                        if (!empty($validation['multiple'])) {
                            $this->validation = 'multiple';
                        } else {
                            $this->validation = 'single';
                        }
    // CHECKME: verify format of methods(...) part
                        if (!empty($validation['methods'])) {
                            $todo = array();
                            foreach (array_keys($this->methods) as $method) {
                                if (!empty($validation['methods'][$method])) {
                                    $todo[] = '+' .$method;
                                } else {
                                    $todo[] = '-' .$method;
                                }
                            }
                            if (count($todo) > 0) {
                                $this->validation .= ';methods(';
                                $this->validation .= join(',',$todo);
                                $this->validation .= ')';
                            }
                        }
                        if (!empty($validation['basedir'])) {
                            $this->validation .= ';basedir(' . $validation['basedir'] . ')';
                        }
                        if (!empty($validation['importdir'])) {
                            $this->validation .= ';importdir(' . $validation['importdir'] . ')';
                        }
                    }
                } else {
                    $this->validation = $validation;
                }
            }

            // tell the calling function that everything is OK
            return true;
        }
        */
}
