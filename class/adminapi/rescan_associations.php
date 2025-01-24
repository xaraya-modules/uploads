<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\AdminApi;

use Xaraya\Modules\Uploads\AdminApi;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use xarModHooks;
use DataObjectFactory;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads adminapi rescan_associations function
 * @extends MethodClass<AdminApi>
 */
class RescanAssociationsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Re-scan all file associations (possibly for a specific module, itemtype and itemid)
     * @author  mikespub
     * @access public
     * @param array<mixed> $args
     * @var integer $modid     The id of module we are going to rescan
     * @var integer $itemtype  The item type within the defined module
     * @var integer $itemid    The id of the item types item
     * @var integer $fileId    The id of the file we are going to rescan
     * @return mixed TRUE on success, void with exception on error
     * @see AdminApi::rescanAssociations()
     */
    public function __invoke(array $args = [])
    {
        // FIXME: don't use this as such in the uploads_guimods version, because you'd
        //        loose information about the categories and direct file associations

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        // 1. delete any existing associations for these arguments
        if (!$userapi->dbDeleteAssociation($args)) {
            return;
        }

        extract($args);

        // 2. get the upload-related property types
        $proptypes = xarMod::apiFunc('dynamicdata', 'user', 'getproptypes');
        $proptypelist = [];
        foreach ($proptypes as $typeid => $proptype) {
            if ($proptype['name'] == 'uploads' || $proptype['name'] == 'fileupload' || $proptype['name'] == 'textupload') {
                $proptypelist[$typeid] = $proptype['name'];
            }
        }

        // 3. get the list of dynamic objects we're interesting in
        if (!empty($modid)) {
            $objectinfolist = [];
            $objectinfolist[] = $this->data()->getObjectInfo(
                ['modid' => $modid,
                    'itemtype' => $itemtype ?? null, ]
            );
        } else {
            $objectinfolist = xarMod::apiFunc('dynamicdata', 'user', 'getobjects');
        }

        // 4. for each dynamic object
        $modnames = [];
        foreach ($objectinfolist as $objectinfo) {
            if (empty($objectinfo['objectid'])) {
                continue;
            }

            // 5. get the module name for later
            $modid = $objectinfo['moduleid'];
            $itemtype = $objectinfo['itemtype'];
            if (!isset($modnames[$modid])) {
                $modinfo = xarMod::getInfo($modid);
                if (empty($modinfo)) {
                    return;
                }
                $modnames[$modid] = $modinfo['name'];
            }

            // 6. get a dynamic object list
            $object = xarMod::apiFunc('dynamicdata', 'user', 'getobjectlist', $objectinfo);

            // 7. build the list of properties we're interested in
            $proplist = [];
            $wherelist = [];
            foreach (array_keys($object->properties) as $propname) {
                $proptype = $object->properties[$propname]->type;
                if (!isset($proptypelist[$proptype])) {
                    continue;
                }
                // see if uploads is hooked where necessary
                if (($proptypelist[$proptype] == 'fileupload' || $proptypelist[$proptype] == 'textupload') &&
                    !xarModHooks::isHooked('uploads', $modnames[$modid], $itemtype)) {
                    // skip this property
                    continue;
                }
                // add this property to the list
                $proplist[$propname] = $proptypelist[$proptype];
                // we're only interested in items with non-empty values
                $wherelist[] = "$propname ne ''";
            }
            if (empty($proplist)) {
                continue;
            }

            // 8. get the items and properties we're interested in
            $object->getItems(['itemids'   => !empty($args['itemid']) ? [$args['itemid']] : null,
                'fieldlist' => array_keys($proplist),
                'where'     => join(' and ', $wherelist), ]);
            if (empty($object->items)) {
                continue;
            }

            // 9. analyze the values for file associations
            foreach ($object->items as $itemid => $fields) {
                foreach ($fields as $name => $value) {
                    if ($proplist[$name] == 'textupload') {
                        // scan for #ulid:NN# and #file*:NN# in the text - cfr. uploads transformhook
                        if (!preg_match_all('/#(ul|file)\w*:(\d+)#/', $value, $matches)) {
                            continue;
                        }
                        foreach ($matches[2] as $file) {
                            // Note: we may have more than one association between item and file here
                            $userapi->dbAddAssociation([
                                'modid'    => $modid,
                                'itemtype' => $itemtype,
                                'itemid'   => $itemid,
                                'fileId'   => $file,
                            ]);
                        }
                    } else {
                        // get the file id's directly from the value
                        $files = explode(';', $value);
                        foreach ($files as $file) {
                            if (empty($file) || !is_numeric($file)) {
                                continue;
                            }
                            // Note: we may have more than one association between item and file here
                            $userapi->dbAddAssociation([
                                'modid'    => $modid,
                                'itemtype' => $itemtype,
                                'itemid'   => $itemid,
                                'fileId'   => $file,
                            ]);
                        }
                    }
                }
            }
        }

        // let's try some articles fields too
        if (!$this->mod()->isAvailable('articles')) {
            return true;
        }
        $artmodid = xarMod::getRegID('articles');
        if (!empty($args['modid']) && $args['modid'] != $artmodid) {
            return true;
        }

        $pubtypes = xarMod::apiFunc('articles', 'user', 'getpubtypes');
        foreach ($pubtypes as $pubtypeid => $pubtypeinfo) {
            if (!empty($args['itemtype']) && $args['itemtype'] != $pubtypeid) {
                continue;
            }
            if (!xarModHooks::isHooked('uploads', 'articles', $pubtypeid)) {
                continue;
            }
            $fieldlist = [];
            foreach ($pubtypeinfo['config'] as $fieldname => $fieldinfo) {
                if ($fieldinfo['format'] == 'fileupload' || $fieldinfo['format'] == 'textupload') {
                    $fieldlist[] = $fieldname;
                }
            }
            if (empty($fieldlist)) {
                continue;
            }
            $articles = xarMod::apiFunc(
                'articles',
                'user',
                'getall',
                ['aids'   => !empty($args['itemid']) ? [$args['itemid']] : null,
                    'ptid'   => $pubtypeid,
                    'fields' => $fieldlist, ]
            );
            if (empty($articles)) {
                continue;
            }
            foreach ($articles as $article) {
                foreach ($fieldlist as $field) {
                    if (empty($article[$field])) {
                        continue;
                    }
                    if ($pubtypeinfo['config'][$field]['format'] == 'textupload') {
                        // scan for #ulid:NN# and #file*:NN# in the text - cfr. uploads transformhook
                        if (!preg_match_all('/#(ul|file)\w*:(\d+)#/', $article[$field], $matches)) {
                            continue;
                        }
                        foreach ($matches[2] as $file) {
                            // Note: we may have more than one association between item and file here
                            $userapi->dbAddAssociation([
                                'modid'    => $artmodid,
                                'itemtype' => $pubtypeid,
                                'itemid'   => $article['aid'],
                                'fileId'   => $file,
                            ]);
                        }
                    } else {
                        // get the file id's directly from the value
                        $files = explode(';', $article[$field]);
                        foreach ($files as $file) {
                            if (empty($file) || !is_numeric($file)) {
                                continue;
                            }
                            // Note: we may have more than one association between item and file here
                            $userapi->dbAddAssociation([
                                'modid'    => $artmodid,
                                'itemtype' => $pubtypeid,
                                'itemid'   => $article['aid'],
                                'fileId'   => $file,
                            ]);
                        }
                    }
                }
            }
        }

        return true;
    }
}
