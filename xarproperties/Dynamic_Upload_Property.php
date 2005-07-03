<?php
/**
 * Dynamic Upload Property
 *
 * @package dynamicdata
 * @subpackage properties
 */
/* Include parent class */
include_once "modules/dynamicdata/class/properties.php";
/**
 * Class to handle file upload properties
 *
 * @package dynamicdata
 */
class Dynamic_Upload_Property extends Dynamic_Property
{
    var $size = 40;
    var $maxsize = 1000000;
    var $multiple = TRUE;
    var $methods = array('trusted'  => false,
                         'external' => false,
                         'upload'   => false,
                         'stored'   => false);
    var $basedir = null;
    var $importdir = null;

    // this is used by Dynamic_Property_Master::addProperty() to set the $object->upload flag
    var $upload = true;

    function Dynamic_Upload_Property($args)
    {
        $this->Dynamic_Property($args);

        if (!isset($this->validation)) {
            $this->validation = '';
        }
        // this is used by DD's importpropertytypes() function
        if (empty($args['skipInit'])) {
            // always parse validation to preset methods here
            $this->parseValidation($this->validation);

            // Note : {user} will be replaced by the current user uploading the file - e.g. var/uploads/{user} -&gt; var/uploads/myusername_123
            if (!empty($this->basedir) && preg_match('/\{user\}/',$this->basedir)) {
                $uname = xarUserGetVar('uname');
                $uname = xarVarPrepForOS($uname);
                $uid = xarUserGetVar('uid');
                // Note: we add the userid just to make sure it's unique e.g. when filtering
                // out unwanted characters through xarVarPrepForOS, or if the database makes
                // a difference between upper-case and lower-case and the OS doesn't...
                $udir = $uname . '_' . $uid;
                $this->basedir = preg_replace('/\{user\}/',$udir,$this->basedir);
            }
            if (!empty($this->importdir) && preg_match('/\{user\}/',$this->importdir)) {
                $uname = xarUserGetVar('uname');
                $uname = xarVarPrepForOS($uname);
                $uid = xarUserGetVar('uid');
                // Note: we add the userid just to make sure it's unique e.g. when filtering
                // out unwanted characters through xarVarPrepForOS, or if the database makes
                // a difference between upper-case and lower-case and the OS doesn't...
                $udir = $uname . '_' . $uid;
                $this->importdir = preg_replace('/\{user\}/',$udir,$this->importdir);
            }
        }
    }

    function validateValue($value = null)
    {
        // convert old Upload values if necessary
        if (!isset($value)) {
            $value = $this->getValue();
        }

        if (isset($this->fieldname)) {
            $name = $this->fieldname;
        } else {
            $name = 'dd_'.$this->id;
        }

        // retrieve new value for preview + new/modify combinations
        if (xarVarIsCached('DynamicData.Upload',$name)) {
            $this->value = xarVarGetCached('DynamicData.Upload',$name);
            return true;
        }

        // set override for the upload/import paths if necessary
        if (!empty($this->basedir) || !empty($this->importdir)) {
            $override = array();
            if (!empty($this->basedir)) {
                $override['upload'] = array('path' => $this->basedir);
            }
            if (!empty($this->importdir)) {
                $override['import'] = array('path' => $this->importdir);
            }
        } else {
            $override = null;
        }

        $return = xarModAPIFunc('uploads','admin','validatevalue',
                                array('id' => $name, // not $this->id
                                      'value' => $value,
                                      // pass the module id, item type and item id (if available) for associations
                                      'moduleid' => $this->_moduleid,
                                      'itemtype' => $this->_itemtype,
                                      'itemid'   => !empty($this->_itemid) ? $this->_itemid : null,
                                      'multiple' => $this->multiple,
                                      'format' => 'upload',
                                      'methods' => $this->methods,
                                      'override' => $override,
                                      'maxsize' => $this->maxsize));
        if (!isset($return) || !is_array($return) || count($return) < 2) {
            $this->value = null;
        // CHECKME: copied from autolinks :)
            // 'text' rendering will return an array
            $errorstack = xarErrorGet();
            $errorstack = array_shift($errorstack);
            $this->invalid = $errorstack['short'];
            xarErrorHandled();
            return false;
        }
        if (empty($return[0])) {
            $this->value = null;
            $this->invalid = xarML('value');
            return false;
        } else {
            if (empty($return[1])) {
                $this->value = '';
            } else {
                $this->value = $return[1];
            }
            // save new value for preview + new/modify combinations
            xarVarSetCached('DynamicData.Upload',$name,$this->value);
            return true;
        }
    }

//    function showInput($name = '', $value = null, $size = 0, $maxsize = 0, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);

        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }

        // convert old Upload values if necessary
        if (!isset($value)) {
            $value = $this->getValue();
        }

        // inform anyone that we're showing a file upload field, and that they need to use
        // <form ... enctype="multipart/form-data" ... > in their input form
        xarVarSetCached('Hooks.dynamicdata','withupload',1);

        // set override for the upload/import paths if necessary
        if (!empty($this->basedir) || !empty($this->importdir)) {
            $override = array();
            if (!empty($this->basedir)) {
                $override['upload'] = array('path' => $this->basedir);
            }
            if (!empty($this->importdir)) {
                $override['import'] = array('path' => $this->importdir);
            }
        } else {
            $override = null;
        }

        return xarModAPIFunc('uploads','admin','showinput',
                             array('id' => $name, // not $this->id
                                   'value' => $value,
                                   'multiple' => $this->multiple,
                                   'methods' => $this->methods,
                                   'override' => $override,
                                   'format' => 'upload',
                                   'invalid' => $this->invalid));
    }

    function showOutput($args = array())
    {
        extract($args);

        // convert old Upload values if necessary
        if (!isset($value)) {
            $value = $this->getValue();
        }

        return xarModAPIFunc('uploads','user','showoutput',
                             array('value' => $value,
                                   'format' => 'upload',
                                   'multiple' => $this->multiple));
    }

    /**
     * Get the value of this property (= for a particular object item)
     *
     * (keep this for compatibility with old Uploads values)
     *
     * @returns mixed
     * @return the value for the property
     */
    function getValue()
    {
        $value = $this->value;

        if (empty($value)) {
            return NULL;
        // For current values when DD stored the ULID
        } elseif ( is_numeric($value) ) {
            $ulid = ";$value";
        // For old values, pull the ULID from the URL that is stored
        } elseif (strstr($value, 'ulid=')) {
            ereg('ulid=([0-9]+)',$value,$reg);
            $ulid = ";$reg[1]";
        // For new values when DD stores a ;-separated list
        } elseif (strstr($value, ';')) {
            $ulid = $value;
        }
        if (empty($ulid)) {
            $ulid = NULL;
        }
        return $ulid;
    }


    function parseValidation($validation = '')
    {
        list($multiple, $methods, $basedir, $importdir) = xarModAPIFunc('uploads', 'admin', 'dd_configure', $validation);

        $this->multiple = $multiple;
        $this->methods = $methods;
        $this->basedir = $basedir;
        $this->importdir = $importdir;
    }

    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $baseInfo = array(
                            'id'         => 105,
                            'name'       => 'uploads',
                            'label'      => 'Upload',
                            'format'     => '105',
                            'validation' => '',
                            'source'     => 'hook module',
                            'dependancies' => '',
                            'requiresmodule' => 'uploads',
                            'aliases' => '',
                            'args'         => '',
                            // ...
                           );
        return $baseInfo;
     }

    function showValidation($args = array())
    {
        extract($args);

        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        $data['size']       = !empty($size) ? $size : 50;
        $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;

        if (isset($validation)) {
            $this->validation = $validation;
            $this->parseValidation($validation);
        }

        $data['multiple'] = $this->multiple;
        $data['methods'] = $this->methods;
        $data['basedir'] = $this->basedir;
        $data['importdir'] = $this->importdir;
        $data['other'] = '';

        // allow template override by child classes
        if (!isset($template)) {
            $template = '';
        }
        return xarTplProperty('uploads', 'upload', 'validation', $data);
    }

    function updateValidation($args = array())
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
}
?>
