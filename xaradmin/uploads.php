<?php

/**
 * View a list of uploaded images (managed by the uploads module)
 *
 * @todo add startnum and numitems support
 */
function images_admin_uploads($args)
{
    extract($args);

    // Security check for images
    if (!xarSecurityCheck('AdminImages')) return;

    // Security check for uploads
    if (!xarModIsAvailable('uploads') || !xarSecurityCheck('AdminUploads')) return;

    $data = array();

    $data['images'] = xarModAPIFunc('images','admin','getuploads');

    // Check if we need to do anything special here
    if (!xarVarFetch('action','str:1:',$action,'',XARVAR_NOT_REQUIRED)) return;

    // Note: fileId is the uploads fileId here, or an array of uploads fileId's
    if (!xarVarFetch('fileId','isset',$fileId,'',XARVAR_NOT_REQUIRED)) return;

    // Find the right uploaded image
    if (!empty($action) && !empty($fileId)) {
        $found = '';

        // if we're dealing with a list of fileId's, make sure they exist
        if (is_array($fileId) && $action == 'resize') {
            $found = array();
            foreach ($fileId as $id => $val) {
                if (!empty($data['images'][$id])) {
                    $found[] = $id;
                }
            }
            if (count($found) > 0) {
                $action = 'resizelist';
            } else {
                $action = '';
            }

        // if we're dealing with an individual fileId, get some additional information
        } elseif (is_numeric($fileId) && !empty($data['images'][$fileId])) {
            $found = $data['images'][$fileId];
            // Get derivative images for this image
            if (!empty($found['fileHash'])) {
                $found['derivatives'] = xarModAPIFunc('images','admin','getderivatives',
                                                      array('fileName' => $found['fileHash']));
            }
            // Get known associations for this image (currently unused)
            $found['associations'] = xarModAPIFunc('uploads','user','db_get_associations',
                                                   array('fileId' => $found['fileId']));
            $found['moditems'] = array();
            if (!empty($found['associations'])) {
                $modlist = array();
                foreach ($found['associations'] as $assoc) {
                    // uploads 0.9.8 format
                    if (isset($assoc['objectId'])) {
                        if (!isset($modlist[$assoc['modId']])) {
                            $modlist[$assoc['modId']] = array();
                        }
                        if (!isset($modlist[$assoc['modId']][$assoc['itemType']])) {
                            $modlist[$assoc['modId']][$assoc['itemType']] = array();
                        }
                        $modlist[$assoc['modId']][$assoc['itemType']][$assoc['objectId']] = 1;

                    // uploads_guimods 0.9.9+ format
                    } elseif (isset($assoc['itemid'])) {
                        if (!isset($modlist[$assoc['modid']])) {
                            $modlist[$assoc['modid']] = array();
                        }
                        if (!isset($modlist[$assoc['modid']][$assoc['itemtype']])) {
                            $modlist[$assoc['modid']][$assoc['itemtype']] = array();
                        }
                        $modlist[$assoc['modid']][$assoc['itemtype']][$assoc['itemid']] = 1;
                    }
                }
                foreach ($modlist as $modid => $itemtypes) {
                    $modinfo = xarModGetInfo($modid);
                    // Get the list of all item types for this module (if any)
                    $mytypes = xarModAPIFunc($modinfo['name'],'user','getitemtypes',
                                             // don't throw an exception if this function doesn't exist
                                             array(), 0);
                    foreach ($itemtypes as $itemtype => $items) {
                        $moditem = array();
                        $moditem['module'] = $modinfo['name'];
                        $moditem['modid'] = $modid;
                        $moditem['itemtype'] = $itemtype;
                        if ($itemtype == 0) {
                            $moditem['modname'] = ucwords($modinfo['displayname']);
                        //    $moditem['modlink'] = xarModURL($modinfo['name'],'user','main');
                        } else {
                            if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                                $moditem['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
                            //    $moditem['modlink'] = $mytypes[$itemtype]['url'];
                            } else {
                                $moditem['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
                            //    $moditem['modlink'] = xarModURL($modinfo['name'],'user','view',array('itemtype' => $itemtype));
                            }
                        }
                        $itemids = array_keys($items);
                        $itemlinks = xarModAPIFunc($modinfo['name'],'user','getitemlinks',
                                                   array('itemtype' => $itemtype,
                                                         'itemids' => $itemids),
                                                   0); // don't throw an exception here
                        $moditem['items'] = array();
                        foreach ($itemids as $itemid) {
                            if (isset($itemlinks[$itemid])) {
                                $moditem['items'][$itemid]['link'] = $itemlinks[$itemid]['url'];
                                $moditem['items'][$itemid]['title'] = $itemlinks[$itemid]['label'];
                            } else {
                                $moditem['items'][$itemid]['link'] = '';
                                $moditem['items'][$itemid]['title'] = $itemid;
                            }
                        }
                        $found['moditems'][] = $moditem;
                    }
                }
            }
        }
    }

    if (!empty($action) && !empty($found)) {
        switch ($action) {
            case 'view':
                $data['selimage'] = $found;
                $data['action'] = 'view';
                return $data;

            case 'resize':
                if (!xarVarFetch('width', 'int:1:',$width, NULL, XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('height','int:1:',$height,NULL,XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('replace','checkbox',$replace,'',XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('confirm','str:1:',$confirm,'',XARVAR_NOT_REQUIRED)) return;
                if (!empty($confirm) && (!empty($width) || !empty($height))) {
                    if (!xarSecConfirmAuthKey()) return;
                    if (!empty($replace) && !empty($found['fileLocation'])) {
                        $location = xarModAPIFunc('images','admin','replace_image',
                                                  array('fileId' => $found['fileId'],
                                                        'width'  => (!empty($width) ? $width . 'px' : NULL),
                                                        'height' => (!empty($height) ? $height . 'px' : NULL)));
                        if (!$location) return;
                        // Redirect to viewing the original image here (for now)
                        xarResponseRedirect(xarModURL('images', 'admin', 'uploads',
                                                      array('action' => 'view',
                                                            'fileId' => $found['fileId'])));
                    } else {
                        $location = xarModAPIFunc('images','admin','resize_image',
                                                  array('fileId' => $found['fileId'],
                                                        'width'  => (!empty($width) ? $width . 'px' : NULL),
                                                        'height' => (!empty($height) ? $height . 'px' : NULL)));
                        if (!$location) return;
                        // Redirect to viewing the derivative image here (for now)
                        xarResponseRedirect(xarModURL('images', 'admin', 'derivatives',
                                                      array('action' => 'view',
                                                            'fileId' => md5($location))));
                    }
                    return true;
                }
                $data['selimage'] = $found;
                if (empty($width) && empty($height)) {
                    $data['width'] = $found['width'];
                    $data['height'] = $found['height'];
                } else {
                    $data['width'] = $width;
                    $data['height'] = $height;
                }
                if (empty($replace)) {
                    $data['replace'] = '';
                } else {
                    $data['replace'] = 'checked="checked"';
                }
                $data['action'] = 'resize';
                $data['authid'] = xarSecGenAuthKey();
                return $data;

            case 'delete':
                if (!xarVarFetch('confirm','str:1:',$confirm,'',XARVAR_NOT_REQUIRED)) return;
                if (!empty($confirm)) {
                    if (!xarSecConfirmAuthKey()) return;
                    // delete the uploaded image now
                    $fileList = array($fileId => $found);
                    $result = xarModAPIFunc('uploads', 'user', 'purge_files', array('fileList' => $fileList));
                    if (!$result) return;
                    xarResponseRedirect(xarModURL('images', 'admin', 'uploads'));
                    return true;
                }
                $data['selimage'] = $found;
                $data['action'] = 'delete';
                $data['authid'] = xarSecGenAuthKey();
                return $data;

            case 'resizelist':
                if (!xarVarFetch('width', 'int:1:',$width, NULL, XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('height','int:1:',$height,NULL,XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('replace','checkbox',$replace,'',XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('confirm','str:1:',$confirm,'',XARVAR_NOT_REQUIRED)) return;
                if (!empty($confirm) && (!empty($width) || !empty($height))) {
                    if (!xarSecConfirmAuthKey()) return;
                    if (!empty($replace)) {
                        foreach ($found as $id) {
                            $location = xarModAPIFunc('images','admin','replace_image',
                                                      array('fileId' => $id,
                                                            'width'  => (!empty($width) ? $width . 'px' : NULL),
                                                            'height' => (!empty($height) ? $height . 'px' : NULL)));
                            if (!$location) return;
                        }
                        // Redirect to viewing the uploaded images here (for now)
                        xarResponseRedirect(xarModURL('images', 'admin', 'uploads',
                                                      array('sort' => 'time')));
                    } else {
                        foreach ($found as $id) {
                            $location = xarModAPIFunc('images','admin','resize_image',
                                                      array('fileId' => $id,
                                                            'width'  => (!empty($width) ? $width . 'px' : NULL),
                                                            'height' => (!empty($height) ? $height . 'px' : NULL)));
                            if (!$location) return;
                        }
                        // Redirect to viewing the derivative images here (for now)
                        xarResponseRedirect(xarModURL('images', 'admin', 'derivatives',
                                                      array('sort' => 'time')));
                    }
                    return true;
                }
                $data['selected'] = $found;
                if (empty($width) && empty($height)) {
                    $data['width'] = '';
                    $data['height'] = '';
                } else {
                    $data['width'] = $width;
                    $data['height'] = $height;
                }
                if (empty($replace)) {
                    $data['replace'] = '';
                } else {
                    $data['replace'] = '1';
                }
                $data['action'] = 'resizelist';
                $data['authid'] = xarSecGenAuthKey();
                return $data;

            default:
                break;
        }
    }

    if (!xarVarFetch('sort','enum:name:type:width:height:size:time',$sort,'name',XARVAR_NOT_REQUIRED)) return;
    switch ($sort) {
        case 'name':
            $strsort = 'fileName';
            break;
        case 'type':
            $strsort = 'fileType';
            break;
        case 'width':
        case 'height':
            $numsort = $sort;
            break;
        case 'size':
            $numsort = 'fileSize';
            break;
        case 'time':
            $numsort = 'fileModified';
            break;
        default:
            break;
    }
    if (!empty($numsort)) {
        $sortfunc = create_function('$a,$b','if ($a["'.$numsort.'"] == $b["'.$numsort.'"]) return 0; return ($a["'.$numsort.'"] > $b["'.$numsort.'"]) ? -1 : 1;');
        usort($data['images'], $sortfunc);
    } elseif (!empty($strsort)) {
        $sortfunc = create_function('$a,$b','return strcmp($a["'.$strsort.'"], $b["'.$strsort.'"]);');
        usort($data['images'], $sortfunc);
    }

    // Return the template variables defined in this function
    return $data;
}
?>
