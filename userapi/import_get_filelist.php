<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserApi;

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use xarModVars;
use xarController;
use xarMod;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads userapi import_get_filelist function
 * @extends MethodClass<UserApi>
 */
class ImportGetFilelistMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get a list of files with metadata from some import directory or link
     * @author  Carl P. Corliss
     * @access public
     * @param array<mixed> $args
     * @var string $fileLocation   The starting directory
     * @var boolean $descend        Go through all sub-directories too
     * @var boolean $onlyNew        Only return files that aren't imported yet
     * @var string $search         Search for a particular filename pattern
     * @var string $exclude        Exclude a particular filename pattern
     * @var integer $cacheExpire    Cache the result for a number of seconds (e.g. for DD Upload)
     * @var boolean $analyze        Analyze each file for mime type (default TRUE)
     *
     * @return array|string array|string of file information
     * @see UserApi::importGetFilelist()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!empty($cacheExpire) && is_numeric($cacheExpire)) {
            $cachekey = md5(serialize($args));
            $cacheinfo = $this->mod()->getVar('file.cachelist.' . $cachekey);
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
            return $this->ctl()->redirect($this->mod()->getURL(
                'user',
                'errors',
                ['layout' => 'dir_not_set']
            ));
        }

        if (!file_exists($fileLocation)) {
            return $this->ctl()->redirect($this->mod()->getURL(
                'user',
                'errors',
                ['layout' => 'dir_not_found','location' => $fileLocation]
            ));
        }

        if (is_file($fileLocation)) {
            $type = Defines::TYPE_FILE;
        } elseif (is_dir($fileLocation)) {
            $type = Defines::TYPE_DIRECTORY;
        } elseif (is_link($fileLocation)) {
            $linkLocation = readlink($fileLocation);

            while (is_link($linkLocation)) {
                $linkLocation = readlink($linkLocation);
            }

            $fileLocation = $linkLocation;

            if (is_dir($linkLocation)) {
                $type = Defines::TYPE_FILE;
            } elseif (is_file($linkLocation)) {
                $type = Defines::TYPE_DIRECTORY;
            } else {
                $type = -1;
            }
        } else {
            $type = -1;
        }
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        switch ($type) {
            case Defines::TYPE_FILE:
                if ($onlyNew) {
                    $file = $userapi->dbGetFile([
                        'fileLocation' => $fileLocation,
                    ]);
                    if (count($file)) {
                        break;
                    }
                }
                $fileName = $fileLocation;
                // if we are searching for specific files, then check and break if search doesn't match
                if ((isset($search) && preg_match("/$search/", $fileName)) &&
                    (!isset($exclude) || !preg_match("/$exclude/", $fileName))) {
                    $fileList["$type:$fileName"] =
                            $userapi->fileGetMetadata([
                                'fileLocation' => $fileLocation,
                                'analyze'      => $analyze,
                            ]);
                }
                break;
            case Defines::TYPE_DIRECTORY:
                if ($fp = opendir($fileLocation)) {
                    while (false !== ($inode = readdir($fp))) {
                        if (is_dir($fileLocation . '/' . $inode) && !preg_match('/^([.]{1,2})$/i', $inode)) {
                            $type = Defines::TYPE_DIRECTORY;
                        } elseif (is_file($fileLocation . '/' . $inode)) {
                            $type = Defines::TYPE_FILE;
                        } elseif (is_link($fileLocation . '/' . $inode)) {
                            $linkLocation = readlink($fileLocation . '/' . $inode);

                            while (is_link($linkLocation)) {
                                $linkLocation = readlink($linkLocation);
                            }

                            if (is_dir($linkLocation) && !preg_match('/([.]{1,2})$/i', $linkLocation)) {
                                $type = Defines::TYPE_DIRECTORY;
                            } elseif (is_file($linkLocation)) {
                                $type = Defines::TYPE_FILE;
                            } else {
                                $type = -1;
                            }
                        } else {
                            $type = -1;
                        }


                        switch ($type) {
                            case Defines::TYPE_FILE:
                                $fileName = $fileLocation . '/' . $inode;

                                if ($onlyNew) {
                                    $file = $userapi->dbGetFile([
                                        'fileLocation' => $fileName,
                                    ]);
                                    if (count($file)) {
                                        continue;
                                    }
                                }

                                if ((!isset($search) || preg_match("/$search/", $fileName)) &&
                                    (!isset($exclude) || !preg_match("/$exclude/", $fileName))) {
                                    $file = $userapi->fileGetMetadata([
                                        'fileLocation' => $fileName,
                                        'analyze'      => $analyze,
                                    ]);
                                    $fileList["$file[inodeType]:$fileName"] = $file;
                                }
                                break;
                            case Defines::TYPE_DIRECTORY:
                                $dirName = "$fileLocation/$inode";
                                if ($descend) {
                                    $files = $userapi->importGetFilelist([
                                        'fileLocation' => $dirName,
                                        'descend' => true,
                                        'analyze' => $analyze,
                                        'exclude' => $exclude,
                                        'search' => $search,
                                    ]);
                                    $fileList += $files;
                                } else {
                                    if ((!isset($search) || preg_match("/$search/", $dirName)) &&
                                        (!isset($exclude) || !preg_match("/$exclude/", $dirName))) {
                                        $files = $userapi->fileGetMetadata([
                                            'fileLocation' => $dirName,
                                            'analyze'      => $analyze,
                                        ]);
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
            $this->mod()->setVar('file.cachelist.' . $cachekey, $cacheinfo);
        }

        return $fileList;
    }
}
