<?php

/** 
 *  Remove a file entry from the database. This just removes any metadata about a file 
 *  that we might have in store. The actual DATA (contents) of the file (ie., the file 
 *  itself) are removed via either file_delete() or db_delete_fileData() depending on 
 *  how the DATA is stored.
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   integer file_id    The id of the file we are deleting
 *
 *  @returns integer The number of affected rows on success, or FALSE on error
 */

function uploads_userapi_db_delete_file( $args ) 
{
    extract($args);
    
    if (!isset($fileId)) {
        $msg = xarML('Missing parameter [#(1)] for function [#(2)] in module [#(3)]', 
                     'file_id','db_delete_file','uploads');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return FALSE;
    }
    
    //add to uploads table
    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // table and column definitions
    $fileEntry_table   = $xartable['file_entry'];
    
    // insert value into table
    $sql = "DELETE FROM $fileEntry_table
                  WHERE xar_fileEntry_id = $fileId";
                  
                      
    $result = &$dbconn->Execute($sql);
    
    if (!$result) {
        return FALSE;
    } else {
        return $dbconn->Affected_Rows();
    }

}

?>