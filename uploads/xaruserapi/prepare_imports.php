<?php

function uploads_userapi_prepare_imports( $args ) {
    
    extract ($args);
    
    if (!isset($importFrom)) {
        $msg = xarML('Missing parameter [#(1)] for function [#(2)] in module [#(3)]', 
                     'importFrom','prepare_imports','uploads');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return FALSE;
    }

    if (!isset($import_directory)) {
        $import_directory = xarModGetVar('uploads', 'path.imports-directory');
    }
    
    if (!isset($import_obfuscate)) {
        $import_obfuscate = xarModGetVar('uploads', 'file.obfuscate-on-import');
    }
        
    /**
    * if the importFrom is an url, then
    * we can't descend (obviously) so set it to FALSE
    */
    if (!isset($descend)) {
        if (eregi('^(http[s]?|ftp)?\:\/\/', $importFrom)) {
            $descend = FALSE;
        } else {
            $descend = TRUE;
        }
    }
    
    $imports = xarModAPIFunc('uploads','user','import_get_filelist',
                              array('fileLocation'  => $importFrom,
                                    'descend'       => $descend));
    if ($imports) { 
        $imports = xarModAPIFunc('uploads','user','import_prepare_files',
                                array('fileList'  => $imports,
                                        'savePath'  => $import_directory,
                                        'obfuscate' => $import_obfuscate));
    }
    
    if (!$imports || xarCurrentError() !== XAR_NO_EXCEPTION) {

        $fileInfo['errors'][] = $fileError;
        $fileInfo['fileName'] = basename($importFrom);
        $fileInfo['fileSrc']  = $importFrom;
        $fileInfo['fileDest'] = $import_directory;
        $fileInfo['fileSize'] = 0;

        while (xarCurrentErrorType() !== XAR_NO_EXCEPTION) {

            $errorObj = xarExceptionValue();

            if (is_object($errorObj)) {
                $fileError = array('errorMsg'   => $errorObj->getShort(),
                                'errorID'    => $errorObj->getID());
            } else {
                $fileError = array('errorMsg'   => 'Unknown Error!',
                                'errorID'    => _UPLOADS_ERROR_UNKNOWN);
            }

            if (!isset($fileInfo['errors'])) {
                $fileInfo['errors'] = array();
            }

            // Clear the exception because we've handled it already
            xarExceptionHandled();
        }    
        return array($fileInfo);
    } else {
        return $imports;
    }

}

?>