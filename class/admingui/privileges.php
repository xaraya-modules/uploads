<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\AdminGui;

use Xaraya\Modules\Uploads\AdminGui;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\Mime\UserApi as MimeApi;
use Xaraya\Modules\MethodClass;
use xarVar;
use xarSecurity;
use xarMod;
use xarPrivileges;
use xarController;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads admin privileges function
 * @extends MethodClass<AdminGui>
 */
class PrivilegesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Manage definition of instances for privileges (unfinished)
     * @param array<mixed> $args
     * @return array|bool|void
     * @see AdminGui::privileges()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // fixed params
        if (!$this->var()->check('mimetype', $mimetype, 'int:0:')) {
            return;
        }
        if (!$this->var()->check('subtype', $subtype, 'int:0:')) {
            return;
        }
        if (!$this->var()->check('userId', $userId, 'int:0:')) {
            return;
        }
        if (!$this->var()->check('fileId', $fileId, 'int:0:')) {
            return;
        }
        if (!$this->var()->check('userName', $userName)) {
            return;
        }
        if (!$this->var()->check('apply', $apply)) {
            return;
        }
        if (!$this->var()->check('extpid', $extpid)) {
            return;
        }
        if (!$this->var()->check('extname', $extname)) {
            return;
        }
        if (!$this->var()->check('extrealm', $extrealm)) {
            return;
        }
        if (!$this->var()->check('extmodule', $extmodule)) {
            return;
        }
        if (!$this->var()->check('extcomponent', $extcomponent)) {
            return;
        }
        if (!$this->var()->check('extinstance', $extinstance)) {
            return;
        }
        if (!$this->var()->check('extlevel', $extlevel)) {
            return;
        }

        $userNameList = [];

        if (!empty($extinstance)) {
            $parts = explode(':', $extinstance);
            if (count($parts) > 0 && !empty($parts[0])) {
                $mimetype = $parts[0];
            }
            if (count($parts) > 1 && !empty($parts[1])) {
                $subtype = $parts[1];
            }
            if (count($parts) > 2 && !empty($parts[2])) {
                $userId = $parts[2];
            }
            if (count($parts) > 3 && !empty($parts[3])) {
                $fileId = $parts[3];
            }
        }

        // Check the mimetype to see if it's set and, if not assume 'All'
        // Otherwise do a quick check to make sure this user has access
        if (empty($mimetype) || !is_numeric($mimetype)) {
            $mimetype = 0;
            if (!$this->sec()->checkAccess('AdminUploads')) {
                return;
            }
        } else {
            if (!xarSecurity::check('AdminUploads', 1, 'Upload', "$mimetype:All:All:All")) {
                return;
            }
        }

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        // Check to see if subtype is set, if not assume 'All'
        if (empty($subtype) || $subtype == 'All' || !is_numeric($subtype)) {
            $subtype = 0;
        } else {
            /** @var MimeApi $mimeapi */
            $mimeapi = $userapi->getMimeAPI();

            $subtypeInfo = $mimeapi->getSubtype(['subtypeId' => $subtype]);

            if (empty($subtypeInfo) || $subtypeInfo['typeId'] != $mimetype) {
                $subtype = 0;
            }
            unset($subtypeInfo);
        }

        // Here we check for the userId (which is based on a list of users
        // that have submitted files - otherwise, a specific username is entered
        // if that no Id is selected but a username was entered, find the id for it
        // and go with that :)

        if (!empty($userName)) {
            if (!strcasecmp('myself', $userName)) {
                $userId = 'myself';
            } else {
                $user = xarMod::apiFunc(
                    'roles',
                    'user',
                    'get',
                    ['uname' => $userName]
                );
                if (!empty($user)) {
                    $userNameList[$user['uid']]['userId'] = $user['uid'];
                    $userNameList[$user['uid']]['userName'] = $user['uname'];
                    $userId = $user['uid'];
                    $userName = '';
                } else {
                    $userName = '';
                }
            }
        }

        if (empty($userId) || $userId == 'All' || !is_numeric($userId)) {
            if (!strcasecmp('myself', $userId ?? '')) {
                $userId = 'myself';
            } else {
                $userId = 0;
            }
        } else {
            $user = xarMod::apiFunc(
                'roles',
                'user',
                'get',
                ['uid' => $userId]
            );
            if (!empty($user)) {
                $userNameList[$user['uid']]['userId'] = $user['uid'];
                $userNameList[$user['uid']]['userName'] = $user['uname'];
                $userId = $user['uid'];
                $userName = '';
            } else {
                $userName = '';
            }
        }

        // Again, if the Id is not specified, assume 'All'
        // however, if it is set - make sure it's mime type matches up with the
        // currently selected mimetype / subtype - otherwise, switch to All files
        if (empty($fileId) || $fileId == 'All' || !is_numeric($fileId)) {
            $fileId = 0;
        } else {
            $fileInfo = $userapi->dbGetFile(['fileId' => $fileId]);

            if (isset($fileInfo[$fileId])) {
                $fileTypeInfo = & $fileInfo[$fileId]['fileTypeInfo'];

                // If the mimetype is the same and the subtype is either
                // the same or ALL (0) then add the file to the list
                // otherwise reset the fileId to ALL (0)
                if (($fileTypeInfo['typeId'] == $mimetype || $mimetype == 0) &&
                    ($fileTypeInfo['subtypeId'] == $subtype || $subtype == 0)) {
                    $fileList = $fileInfo;
                } else {
                    $fileId = 0;
                }
            } else {
                $fileId = 0;
            }
        }

        // define the filters for creating the select boxes
        // as well as for generating the filters used for 'count items'
        $filters['mimetype'] = $mimetype;
        $filters['subtype']  = $subtype;

        // define the new instance
        $newinstance    = [];
        $newinstance[]  = empty($mimetype) ? 'All' : $mimetype;
        $newinstance[]  = empty($subtype) ? 'All' : $subtype;
        $newinstance[]  = empty($userId) ? 'All' : $userId;
        $newinstance[]  = empty($fileId) ? 'All' : $fileId;

        if (!empty($apply)) {
            // create/update the privilege
            $pid = xarPrivileges::external($extpid, $extname, $extrealm, $extmodule, $extcomponent, $newinstance, $extlevel);
            if (empty($pid)) {
                return; // throw back
            }

            // redirect to the privilege
            $this->ctl()->redirect($this->ctl()->getModuleURL(
                'privileges',
                'admin',
                'modifyprivilege',
                ['pid' => $pid]
            ));
            return true;
        }

        $filters['storeOptions'] = false;
        $options            = $userapi->processFilters($filters);
        unset($filters);

        $filter             = $options['filter'];
        $filter['userId']   = $userId;
        $filter['fileId']   = $fileId;

        $instances = $options['data']['filters'];

        // Count how many items there are based on
        // the currently selected privilege settings
        $numitems = $userapi->dbCount($filter);

        $userNameList += $userapi->dbGetUsers([
            'mimeType' => $filter['fileType'],
        ]);

        // Set up default 'All' option for users
        $userNameList[0]['userId'] = 0;
        $userNameList[0]['userName'] = $this->ml('All');

        if (isset($userNameList[$userId])) {
            $userNameList[$userId]['selected'] = true;
        }

        // We don't need the userid nor the fileid for
        // retrieving a list of files - in this particular instance
        // we are only retrieving the list of files based on mimetype
        unset($filter['userId']);
        unset($filter['fileId']);

        $fileList = $userapi->dbGetFile($filter);
        $fileList[0]['fileId'] = 0;
        $fileList[0]['fileName'] = $this->ml('All');
        $fileList[0]['fileLocation'] = $fileList[0]['fileName'];

        if (isset($fileList[$fileId])) {
            $fileList[$fileId]['selected'] = true;
        }


        ksort($fileList);
        ksort($userNameList);

        if (!empty($userName) && isset($userNamelist[$userId])) {
            $userName = '';
        }

        $data['fileId']         = $fileId;
        $data['fileList']       = $fileList;
        $data['mimetype']       = $mimetype;
        $data['mimetypeList']   = $instances['mimetypes'];
        $data['subtype']        = $subtype;
        $data['subtypeList']    = $instances['subtypes'];
        $data['userId']         = $userId;
        $data['userName']       = $this->var()->prep($userName);
        $data['userNameList']   = $userNameList;
        $data['numitems']       = $numitems;
        $data['extpid']         = $extpid;
        $data['extname']        = $extname;
        $data['extrealm']       = $extrealm;
        $data['extmodule']      = $extmodule;
        $data['extcomponent']   = $extcomponent;
        $data['extlevel']       = $extlevel;
        $data['extinstance']    = $this->var()->prep(join(':', $newinstance));
        $data['applylabel']     = $this->ml('Finish and Apply to Privilege');

        return $data;
    }
}
