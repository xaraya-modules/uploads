<?php

/**
 *  Retrieve the total count of data blocks stored for a particular file
 * 
 * @author  Carl P. Corliss
 * @author  Micheal Cortez
 * @access  public
 * @param   integer  fileId     (Optional) grab file with the specified file id
 * @returns integer             The total number of DATA Blocks stored for a particular file
 */
 
function uploads_userapi_db_count_data( $args )  {
    
    extract($args);
    
    $where = array();
    
    if (!isset($fileId)) {
        $msg = xarML('Missing parameter [#(1)] for API function [#(2)] in module [#(3)]', 
                     'fileId','db_count_data','uploads');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return FALSE;
    }
    
    // Get database setup
    list($dbconn) = xarDBGetConn();
    $xartable     = xarDBGetTables();
        
        // table and column definitions
    $fileEntry_table = $xartable['file_data'];
    
    $sql = "SELECT COUNT(xar_fileData_id) AS total
              FROM $fileEntry_table
             WHERE xar_fileEntry_id = $fileId";
    
    $result = $dbconn->Execute($sql);

    if (!$result)  {
        return FALSE;
    }

    // if no record found, return an empty array        
    if ($result->EOF) {
        return (integer) 0;
    }
    
    $row = $result->GetRowAssoc(false);
    
    return $row['total'];
}

?>