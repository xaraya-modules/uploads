<?php 
/**
 * Purpose of File
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Uploads Module
 * @link http://xaraya.com/index.php/release/666.html
 * @author Uploads Module Development Team
 */
function uploads_user_upload() 
{
    
    if (!xarSecurityCheck('AddUploads')) return;
    
    xarVarFetch('importFrom', 'str:1:', $importFrom, NULL, XARVAR_NOT_REQUIRED); 
    
    $list = xarModAPIFunc('uploads','user','process_files', 
                           array('importFrom' => $importFrom));
    
    if (is_array($list) && count($list)) {
        return array('fileList' => $list);
    } else {
        xarResponseRedirect(xarModURL('uploads', 'user', 'uploadform'));
    }
}

?>