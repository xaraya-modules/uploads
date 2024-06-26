<?php
/**
 * Uploads Module
 *
 * @package modules
 * @subpackage uploads module
 * @category Third Party Xaraya Module
 * @version 1.1.0
 * @copyright see the html/credits.html file in this Xaraya release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com/index.php/release/eid/666
 * @author Uploads Module Development Team
 */

/**
 *  Get a list of files with metadata from some import directory or link
 *
 *  @author  Carl P. Corliss
 *  @access  public
 *  @param   string   fileLocation   The starting directory
 *  @param   boolean  descend        Go through all sub-directories too
 *  @param   boolean  onlyNew        Only return files that aren't imported yet
 *  @param   string   search         Search for a particular filename pattern
 *  @param   string   exclude        Exclude a particular filename pattern
 *  @param   integer  cacheExpire    Cache the result for a number of seconds (e.g. for DD Upload)
 *  @param   boolean  analyze        Analyze each file for mime type (default TRUE)
 *
 *  @return array
 *  @return array|string of file information
 */

function uploads_userapi_import_get_filelist(array $args = [], $context = null)
{
    extract($args);

    if (!empty($cacheExpire) && is_numeric($cacheExpire)) {
        $cachekey = md5(serialize($args));
        $cacheinfo = xarModVars::get('uploads', 'file.cachelist.' . $cachekey);
        if (!empty($cacheinfo)) {
            $cacheinfo = @unserialize($cacheinfo);
            if (!empty($cacheinfo['time']) && $cacheinfo['time'] > time() - $cacheExpire) {
                return $cacheinfo['list'];
            }
        }
    }

    // Whether or not to descend into any directory
    // that is found while creating the list of files
    if (!isset($descend)) {
        $descend = false;
    }

    // Whether or not to only add files that are -not- already
    // stored with entries in the database
    if (!isset($onlyNew)) {
        $onlyNew = false;
    }

    if ((isset($search) && isset($exclude)) && $search == $exclude) {
        return [];
    }

    if (!isset($search)) {
        $search = '.*';
    }

    if (!isset($exclude)) {
        $exclude = null;
    }

    // Whether or not to analyze each file for mime type
    if (!isset($analyze)) {
        $analyze = true;
    }

    // if search and exclude are the same, we would get no results
    // so return no results.
    $fileList = [];

    if (!isset($fileLocation)) {
        return xarController::redirect(xarController::URL(
            'uploads',
            'user',
            'errors',
            ['layout' => 'dir_not_set']
        ), null, $context);
    }

    if (!file_exists($fileLocation)) {
        return xarController::redirect(xarController::URL(
            'uploads',
            'user',
            'errors',
            ['layout' => 'dir_not_found','location' => $fileLocation]
        ), null, $context);
    }

    if (is_file($fileLocation)) {
        $type = _INODE_TYPE_FILE;
    } elseif (is_dir($fileLocation)) {
        $type = _INODE_TYPE_DIRECTORY;
    } elseif (is_link($fileLocation)) {
        $linkLocation = readlink($fileLocation);

        while (is_link($linkLocation)) {
            $linkLocation = readlink($linkLocation);
        }

        $fileLocation = $linkLocation;

        if (is_dir($linkLocation)) {
            $type = _INODE_TYPE_FILE;
        } elseif (is_file($linkLocation)) {
            $type = _INODE_TYPE_DIRECTORY;
        } else {
            $type = -1;
        }
    } else {
        $type = -1;
    }

    switch ($type) {
        case _INODE_TYPE_FILE:
            if ($onlyNew) {
                $file = xarMod::apiFunc(
                    'uploads',
                    'user',
                    'db_get_file',
                    ['fileLocation' => $fileLocation]
                );
                if (count($file)) {
                    break;
                }
            }
            $fileName = $fileLocation;
            // if we are searching for specific files, then check and break if search doesn't match
            if ((isset($search) && preg_match("/$search/", $fileName)) &&
                (!isset($exclude) || !preg_match("/$exclude/", $fileName))) {
                $fileList["$type:$fileName"] =
                        xarMod::apiFunc(
                            'uploads',
                            'user',
                            'file_get_metadata',
                            ['fileLocation' => $fileLocation,
                                             'analyze'      => $analyze, ]
                        );
            }
            break;
        case _INODE_TYPE_DIRECTORY:
            if ($fp = opendir($fileLocation)) {
                while (false !== ($inode = readdir($fp))) {
                    if (is_dir($fileLocation . '/' . $inode) && !preg_match('/^([.]{1,2})$/i', $inode)) {
                        $type = _INODE_TYPE_DIRECTORY;
                    } elseif (is_file($fileLocation . '/' . $inode)) {
                        $type = _INODE_TYPE_FILE;
                    } elseif (is_link($fileLocation . '/' . $inode)) {
                        $linkLocation = readlink($fileLocation . '/' . $inode);

                        while (is_link($linkLocation)) {
                            $linkLocation = readlink($linkLocation);
                        }

                        if (is_dir($linkLocation) && !preg_match('/([.]{1,2}$/i', $linkLocation)) {
                            $type = _INODE_TYPE_DIRECTORY;
                        } elseif (is_file($linkLocation)) {
                            $type = _INODE_TYPE_FILE;
                        } else {
                            $type = -1;
                        }
                    } else {
                        $type = -1;
                    }


                    switch ($type) {
                        case _INODE_TYPE_FILE:
                            $fileName = $fileLocation . '/' . $inode;

                            if ($onlyNew) {
                                $file = xarMod::apiFunc(
                                    'uploads',
                                    'user',
                                    'db_get_file',
                                    ['fileLocation' => $fileName]
                                );
                                if (count($file)) {
                                    continue;
                                }
                            }

                            if ((!isset($search) || preg_match("/$search/", $fileName)) &&
                                (!isset($exclude) || !preg_match("/$exclude/", $fileName))) {
                                $file = xarMod::apiFunc(
                                    'uploads',
                                    'user',
                                    'file_get_metadata',
                                    ['fileLocation' => $fileName,
                                    'analyze'      => $analyze, ]
                                );
                                $fileList["$file[inodeType]:$fileName"] = $file;
                            }
                            break;
                        case _INODE_TYPE_DIRECTORY:
                            $dirName = "$fileLocation/$inode";
                            if ($descend) {
                                $files = xarMod::apiFunc(
                                    'uploads',
                                    'user',
                                    'import_get_filelist',
                                    ['fileLocation' => $dirName,
                                    'descend' => true,
                                    'analyze' => $analyze,
                                    'exclude' => $exclude,
                                    'search' => $search, ]
                                );
                                $fileList += $files;
                            } else {
                                if ((!isset($search) || preg_match("/$search/", $dirName)) &&
                                    (!isset($exclude) || !preg_match("/$exclude/", $dirName))) {
                                    $files = xarMod::apiFunc(
                                        'uploads',
                                        'user',
                                        'file_get_metadata',
                                        ['fileLocation' => $dirName,
                                        'analyze'      => $analyze, ]
                                    );
                                    $fileList["$files[inodeType]:$inode"] = $files;
                                }
                            }
                            break;
                        default:
                            break;
                    }

                    if (is_dir($fileLocation . '/' . $inode) && !preg_match('/^([.]{1,2})$/i', $inode)) {
                    }
                    if (is_file($fileLocation . '/' . $inode)) {
                    }
                }
            }
            closedir($fp);
            break;
        default:
            break;
    }

    if (is_array($fileList)) {
        ksort($fileList);
    }

    if (!empty($cacheExpire) && is_numeric($cacheExpire)) {
        // get the cache list again, in case someone else filled it by now
        $cacheinfo = ['time' => time(),
                           'list' => $fileList, ];
        $cacheinfo = serialize($cacheinfo);
        xarModVars::set('uploads', 'file.cachelist.' . $cachekey, $cacheinfo);
    }

    return $fileList;
}
