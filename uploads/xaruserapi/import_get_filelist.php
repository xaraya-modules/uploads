<?php

/** 
 *  Rename a file. (alias for file_move)
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   <type>
 *  @returns <type> 
 */

function uploads_userapi_import_get_filelist( $args ) {
    
    
    extract($args);
    
    $fileList    = array();
    
    if (!isset($descend)) {
        $descend = FALSE;
    }
    
    if (!isset($fileLocation)) {
        $msg = xarML('Missing parameter [#(1)] for function [#(2)] in module [#(3)].',
                     'fileLocation', 'import_get_filelist', 'uploads');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }
    
    if (!file_exists($fileLocation)) {
        
        // check to see if it's actually an external location that was passed in
        if (eregi('^(http|ftp)\:\/\/([a-zA-Z.-]*/){1}([^.?&#]*/)?([^?&#/]*)?', $fileLocatoin, $matches)) {
            
            // The last indice in the matches array should 
            // be the filename, or empty if we don't find one
            $fileName = end($matches);
            
            // reset the internal array pointer to it's first element
            reset($matches);
            
            if (empty($fileName)) {
                // if we didn't get a filename, then this is an html file most likely
                // so, we set the fileName to domain.com-index.html
                $fileName = str_replace(array('.','/'), array('-',''), $matches[2]) . '-index.html';
            }
                
            $fileList[$fileName]['fileSize'] = xarModAPIFunc('uploads','user','file_remote_filesize', 
                                                              array('remoteLocation' => $fileLocation));
            $fileList[$fileName]['fileName'] = $fileName;
            // here we have to rely on the file extension to figure out the mime type
            $fileList[$fileName]['fileType'] = xarModAPIFunc('mime', 'user', 'get_extension', 
                                                              array('extensionName' => end(explode('.', $fileName))));
			$fileList[$fileName]['fileSrc']  = $fileLocation;
			$fileList[$fileName]['isExternal'] = TRUE;
			$fileList[$fileName]['isUpload']   = FALSE;
			$fileList[$fileName]['isLocal']    = FALSE;
			return $fileList;
        } else {
            $msg = xarML('Unable to acquire list of files to import - Location does not exist!');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'FILE_NO_EXIST', new SystemException($msg));
            return;
        }
    }
    
    if (is_file($fileLocation)) {
        $type = _INODE_TYPE_FILE;
    } elseif(is_dir($fileLocation)) {
        $type = _INODE_TYPE_DIRECTORY;
    } else {
        $type = -1;
    }
    switch ($type) {
        case _INODE_TYPE_FILE:
            $fileName = basename($fileLocation);
            $fileList[$fileName] = array( 0          => _INODE_TYPE_FILE,
                                         'fileSize'  => filesize($fileName),
                                         'fileName'  => $fileName,
                                         'fileType' => xarModAPIFunc('mime', 'user', 'analyze_file', 
                                                                      array('fileName' => $fileName)),
                                         'fileSrc'   => "$fileLocation"
                                        );
            break;
        case _INODE_TYPE_DIRECTORY:
            if ($fp = opendir($fileLocation)) {

                while (false !== ($inode = readdir($fp))) { 
                    if (!eregi('^(\.|\.\.)$', $inode)) {
                        if (is_link($fileLocation. '/' . $inode)) {
                            continue;
                        }

                        if (is_dir($fileLocation. '/' . $inode) && $descend == TRUE) {
                            $files = xarModAPIFunc('uploads', 'user', 'import_get_filelist', 
                                                    array('fileLocation' => $fileLocation . '/' . $inode));
                            // No we add the fileList from the directory
                            // that we just descended to the directories inode in the direoctory list
                            if (count($files) >= 1) {
                                $files = array_merge(_INODE_TYPE_DIRECTORY, $files);
                                $fileList[$inode] = $files;
                            }
                        } 

                        if (is_file($fileLocation. '/' . $inode)) {

                            $fileName = $fileLocation . '/' . $inode;
                            $fileList[$inode] = array( 0 => _INODE_TYPE_FILE,
                                                    'fileSize' => filesize($fileName),
                                                    'fileName' => basename($fileName), 
                                                    'fileType' => xarModAPIFunc('mime', 'user', 'analyze_file', 
                                                                                 array('fileName' => $fileName)),
                                                    'fileSrc'  => "$fileLocation/$inode"
                                                    );
                        }
                    }

                }

                closedir($fp);
            } 
            break;
    }
    
    return $fileList;
}

?>

