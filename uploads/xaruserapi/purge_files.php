<?php

/**
 * Takes a list of files and deletes them

 *
 * @author  Carl P. Corliss
 * @access  public
 * @param   array   fileList    List of files to delete
 * @returns boolean             true if successful, false otherwise
 */
 
function uploads_userapi_purge_files( $args ) {

    extract ( $args );
    
    if (!isset($fileList)) {
        $msg = xarML('Missing required parameter [#(1)] for API function [#(2)] in module [#(3)]',
                     'fileList', 'purge_files', 'uploads');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }
    
    foreach ($fileList as $fileName => $fileInfo) {
        
        if ($fileInfo['storeType'] & _UPLOADS_STORE_FILESYSTEM) {
            echo "<br />Unlinking file: [<strong>$fileInfo[fileName]</strong>]";
            xarModAPIFunc('uploads', 'user', 'file_delete', array('fileName' => $fileInfo['fileLocation']));
        }
        
        if ($fileInfo['storeType'] & _UPLOADS_STORE_DB_DATA) {
            // TODO: add the file's contents to the database
            continue;
        }
        // If the store db_entry bit is set, then go ahead 
        // and set up the database meta information for the file
        xarModAPIFunc('uploads', 'user', 'db_delete_file', array('fileId' => $fileInfo['fileId']));
        
    }
 
    return TRUE;
}

?>