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
/** 
 *  Rename a file. (alias for file_move)
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   <type>
 *  @returns <type> 
 */

function uploads_userapi_file_rename( $args ) 
{ 
    return xarModAPIFunc('uploads','user','file_move', $args);
}

?>