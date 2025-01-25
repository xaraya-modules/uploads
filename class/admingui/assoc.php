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
use Xaraya\Modules\Uploads\AdminApi;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use xarSecurity;
use xarVar;
use xarMod;
use xarSec;
use xarController;
use xarModVars;
use xarTplPager;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads admin assoc function
 * @extends MethodClass<AdminGui>
 */
class AssocMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * View statistics about file associations (adapted from categories stats)
     * @see AdminGui::assoc()
     */
    public function __invoke(array $args = [])
    {
        // Security Check
        if (!$this->sec()->checkAccess('AdminUploads')) {
            return;
        }

        if (!$this->var()->check('modid', $modid)) {
            return;
        }
        if (!$this->var()->check('itemtype', $itemtype)) {
            return;
        }
        if (!$this->var()->check('itemid', $itemid)) {
            return;
        }
        if (!$this->var()->check('sort', $sort)) {
            return;
        }
        if (!$this->var()->find('startnum', $startnum, 'isset', 1)) {
            return;
        }
        if (!$this->var()->check('fileId', $fileId)) {
            return;
        }
        if (!$this->var()->check('action', $action)) {
            return;
        }

        if (empty($fileId) || !is_numeric($fileId)) {
            $fileId = null;
        }
        if (!empty($modid) && empty($itemtype)) {
            $itemtype = 0;
        }

        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        if (!empty($action)) {
            if ($action == 'rescan') {
                $result = $adminapi->rescanAssociations([
                    'modid' => $modid,
                    'itemtype' => $itemtype,
                    'itemid' => $itemid,
                    'fileId' => $fileId,
                ]);
                if (!$result) {
                    return;
                }
            } elseif ($action == 'missing') {
                $missing = $adminapi->checkAssociations();
                if (!isset($missing)) {
                    return;
                }
            } elseif ($action == 'delete' && !empty($modid)) {
                if (!$this->var()->check('confirm', $confirm)) {
                    return;
                }
                if (!empty($confirm)) {
                    // Confirm authorisation code.
                    if (!$this->sec()->confirmAuthKey()) {
                        return;
                    }
                    $result = $adminapi->deleteAssociations([
                        'modid' => $modid,
                        'itemtype' => $itemtype,
                        'itemid' => $itemid,
                        'fileId' => $fileId,
                    ]);
                    if (!$result) {
                        return;
                    }
                    $this->ctl()->redirect($this->mod()->getURL('admin', 'assoc'));
                    return true;
                }
            }
        }

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        $data = [];
        $data['modid'] = $modid;
        $data['itemtype'] = $itemtype;
        $data['itemid'] = $itemid;
        $data['fileId'] = $fileId;
        if (!empty($missing)) {
            $data['missing'] = $missing;
        }

        $modlist = $userapi->dbGroupAssociations([
            'fileId' => $fileId,
        ]);

        if (empty($modid)) {
            $data['moditems'] = [];
            $data['numitems'] = 0;
            $data['numlinks'] = 0;
            foreach ($modlist as $modid => $itemtypes) {
                $modinfo = xarMod::getInfo($modid);
                // Get the list of all item types for this module (if any)
                try {
                    $mytypes = xarMod::apiFunc($modinfo['name'], 'user', 'getitemtypes');
                } catch (Exception $e) {
                    $mytypes = [];
                }
                foreach ($itemtypes as $itemtype => $stats) {
                    $moditem = [];
                    $moditem['numitems'] = $stats['items'];
                    $moditem['numfiles'] = $stats['files'];
                    $moditem['numlinks'] = $stats['links'];
                    if ($itemtype == 0) {
                        $moditem['name'] = ucwords($modinfo['displayname']);
                        //    $moditem['link'] = $this->ctl()->getModuleURL($modinfo['name'],'user','main');
                    } else {
                        if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                            $moditem['name'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
                            //    $moditem['link'] = $mytypes[$itemtype]['url'];
                        } else {
                            $moditem['name'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
                            //    $moditem['link'] = $this->ctl()->getModuleURL($modinfo['name'],'user','view',array('itemtype' => $itemtype));
                        }
                    }
                    $moditem['link'] = $this->mod()->getURL(
                        'admin',
                        'assoc',
                        ['modid' => $modid,
                            'itemtype' => empty($itemtype) ? null : $itemtype,
                            'fileId' => $fileId, ]
                    );
                    $moditem['rescan'] = $this->mod()->getURL(
                        'admin',
                        'assoc',
                        ['action' => 'rescan',
                            'modid' => $modid,
                            'itemtype' => empty($itemtype) ? null : $itemtype,
                            'fileId' => $fileId, ]
                    );
                    $moditem['delete'] = $this->mod()->getURL(
                        'admin',
                        'assoc',
                        ['action' => 'delete',
                            'modid' => $modid,
                            'itemtype' => empty($itemtype) ? null : $itemtype,
                            'fileId' => $fileId, ]
                    );
                    $data['moditems'][] = $moditem;
                    $data['numitems'] += $moditem['numitems'];
                    $data['numlinks'] += $moditem['numlinks'];
                }
            }
            $data['rescan'] = $this->mod()->getURL(
                'admin',
                'assoc',
                ['action' => 'rescan',
                    'fileId' => $fileId, ]
            );
            $data['delete'] = $this->mod()->getURL(
                'admin',
                'assoc',
                ['action' => 'delete',
                    'fileId' => $fileId, ]
            );
            if (!empty($fileId)) {
                $data['fileinfo'] = $userapi->dbGetFile([
                    'fileId' => $fileId,
                ]);
            }
        } else {
            $modinfo = xarMod::getInfo($modid);
            $data['module'] = $modinfo['name'];
            if (empty($itemtype)) {
                $data['modname'] = ucwords($modinfo['displayname']);
                $itemtype = null;
                if (isset($modlist[$modid][0])) {
                    $stats = $modlist[$modid][0];
                }
            } else {
                // Get the list of all item types for this module (if any)
                try {
                    $mytypes = xarMod::apiFunc($modinfo['name'], 'user', 'getitemtypes');
                } catch (Exception $e) {
                    $mytypes = [];
                }
                if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                    $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
                    //    $data['modlink'] = $mytypes[$itemtype]['url'];
                } else {
                    $data['modname'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
                    //    $data['modlink'] = $this->ctl()->getModuleURL($modinfo['name'],'user','view',array('itemtype' => $itemtype));
                }
                if (isset($modlist[$modid][$itemtype])) {
                    $stats = $modlist[$modid][$itemtype];
                }
            }
            if (isset($stats)) {
                $data['numitems'] = $stats['items'];
                $data['numlinks'] = $stats['links'];
            } else {
                $data['numitems'] = 0;
                $data['numlinks'] = '';
            }
            $numstats = $this->mod()->getVar('numstats');
            if (empty($numstats)) {
                $numstats = 100;
            }
            /**
            if (!empty($fileId)) {
                $data['numlinks'] = $userapi->dbCountAssociations([
                    'modid' => $modid,
                    'itemtype' => $itemtype,
                    'fileId' => $fileId,
                ]);
            }
            */
            if ($numstats < $data['numlinks']) {
                sys::import('modules.base.class.pager');
                $data['pager'] = $this->tpl()->getPager(
                    $startnum,
                    $data['numlinks'],
                    $this->mod()->getURL(
                        'admin',
                        'assoc',
                        ['modid' => $modid,
                            'itemtype' => $itemtype,
                            'fileId' => $fileId,
                            'sort' => $sort,
                            'startnum' => '%%', ]
                    ),
                    $numstats
                );
            } else {
                $data['pager'] = '';
            }
            $getitems = $userapi->dbListAssociations([
                'modid' => $modid,
                'itemtype' => $itemtype,
                'itemid' => $itemid,
                'numitems' => $numstats,
                'startnum' => $startnum,
                'sort' => $sort,
                'fileId' => $fileId,
            ]);
            //$showtitle = $this->mod()->getVar('showtitle');
            $showtitle = true;
            if (!empty($getitems) && !empty($showtitle)) {
                $itemids = array_keys($getitems);
                try {
                    $itemlinks = xarMod::apiFunc(
                        $modinfo['name'],
                        'user',
                        'getitemlinks',
                        ['itemtype' => $itemtype,
                            'itemids' => $itemids]
                    );
                } catch (Exception $e) {
                    $itemlinks = [];
                }
            } else {
                $itemlinks = [];
            }
            $seenfileid = [];
            if (!empty($fileId)) {
                $seenfileid[$fileId] = 1;
            }
            $data['moditems'] = [];
            foreach ($getitems as $itemid => $filelist) {
                $data['moditems'][$itemid] = [];
                $data['moditems'][$itemid]['numlinks'] = count($filelist);
                $data['moditems'][$itemid]['filelist'] = $filelist;
                foreach ($filelist as $id) {
                    $seenfileid[$id] = 1;
                }
                $data['moditems'][$itemid]['rescan'] = $this->mod()->getURL(
                    'admin',
                    'assoc',
                    ['action' => 'rescan',
                        'modid' => $modid,
                        'itemtype' => $itemtype,
                        'itemid' => $itemid,
                        'fileId' => $fileId, ]
                );
                $data['moditems'][$itemid]['delete'] = $this->mod()->getURL(
                    'admin',
                    'assoc',
                    ['action' => 'delete',
                        'modid' => $modid,
                        'itemtype' => $itemtype,
                        'itemid' => $itemid,
                        'fileId' => $fileId, ]
                );
                if (isset($itemlinks[$itemid])) {
                    $data['moditems'][$itemid]['link'] = $itemlinks[$itemid]['url'];
                    $data['moditems'][$itemid]['title'] = $itemlinks[$itemid]['label'];
                }
            }
            unset($getitems);
            unset($itemlinks);
            if (!empty($seenfileid)) {
                $data['fileinfo'] = $userapi->dbGetFile([
                    'fileId' => array_keys($seenfileid),
                ]);
            } else {
                $data['fileinfo'] = [];
            }
            $data['rescan'] = $this->mod()->getURL(
                'admin',
                'assoc',
                ['action' => 'rescan',
                    'modid' => $modid,
                    'itemtype' => $itemtype,
                    'fileId' => $fileId, ]
            );
            $data['delete'] = $this->mod()->getURL(
                'admin',
                'assoc',
                ['action' => 'delete',
                    'modid' => $modid,
                    'itemtype' => $itemtype,
                    'fileId' => $fileId, ]
            );
            $data['sortlink'] = [];
            if (empty($sort) || $sort == 'itemid') {
                $data['sortlink']['itemid'] = '';
            } else {
                $data['sortlink']['itemid'] = $this->mod()->getURL(
                    'admin',
                    'assoc',
                    ['modid' => $modid,
                        'itemtype' => $itemtype,
                        'fileId' => $fileId, ]
                );
            }
            if (!empty($sort) && $sort == 'numlinks') {
                $data['sortlink']['numlinks'] = '';
            } else {
                $data['sortlink']['numlinks'] = $this->mod()->getURL(
                    'admin',
                    'assoc',
                    ['modid' => $modid,
                        'itemtype' => $itemtype,
                        'fileId' => $fileId,
                        'sort' => 'numlinks', ]
                );
            }

            if (!empty($action) && $action == 'delete') {
                $data['action'] = 'delete';
                $data['authid'] = $this->sec()->genAuthKey();
            }
        }

        return $data;
    }
}
