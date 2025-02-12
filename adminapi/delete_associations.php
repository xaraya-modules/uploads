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
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads adminapi delete_associations function
 * @extends MethodClass<AdminApi>
 */
class DeleteAssociationsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Delete all file associations for a specific module, itemtype [and itemid] [and fileId]
     * Caution : this tries to remove the file references in the module items too
     * @author mikespub
     * @access  public
     * @param array<mixed> $args
     * @var integer $modid     The id of module we are going to rescan
     * @var integer $itemtype  The item type within the defined module
     * @var integer $itemid    The id of the item types item
     * @var integer $fileId    The id of the file we are going to rescan
     * @return  mixed TRUE on success, void with exception on error
     * @see AdminApi::deleteAssociations()
     */
    public function __invoke(array $args = [])
    {
        // FIXME: don't use this as such in the uploads_guimods version, because you'd
        //        loose information about the categories and direct file associations

        extract($args);

        // we only accept deleting file associations for a particular module + itemtype
        if (empty($modid)) {
            $msg = $this->ml('Invalid #(1) for #(2) function #(3)() in module #(4)', 'modid', 'admin', 'delete_associations', 'uploads');
            throw new Exception($msg);
        }
        if (empty($itemtype)) {
            $itemtype = 0;
        }

        // 2. get the upload-related property types
        $proptypes = $this->mod()->apiFunc('dynamicdata', 'user', 'getproptypes');
        $proptypelist = [];
        foreach ($proptypes as $typeid => $proptype) {
            if ($proptype['name'] == 'uploads' || $proptype['name'] == 'fileupload' || $proptype['name'] == 'textupload') {
                $proptypelist[$typeid] = $proptype['name'];
            }
        }

        // 3. get the list of dynamic objects we're interesting in
        $objectinfolist = [];
        $objectinfolist[] = $this->data()->getObjectInfo(
            ['modid' => $modid,
                'itemtype' => $itemtype ?? null, ]
        );

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
                $modinfo = $this->mod()->getInfo($modid);
                if (empty($modinfo)) {
                    return;
                }
                $modnames[$modid] = $modinfo['name'];
            }

            // 6. get a dynamic object list
            $object = $this->mod()->apiFunc('dynamicdata', 'user', 'getobjectlist', $objectinfo);

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
                    !$this->mod()->isHooked('uploads', $modnames[$modid], $itemtype)) {
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
                $values = [];
                foreach ($fields as $name => $value) {
                    if ($proplist[$name] == 'textupload') {
                        // scan for #ulid:NN# and #file*:NN# in the text - cfr. uploads transformhook
                        if (!empty($args['fileId'])) {
                            if (!preg_match("/#(ul|file)\w*:$args[fileId]#/", $value)) {
                                continue;
                            }
                            $values[$name] = preg_replace("/#(ul|file)\w*:$args[fileId]#/", '', $value);
                        } else {
                            if (!preg_match('/#(ul|file)\w*:(\d+)#/', $value)) {
                                continue;
                            }
                            $values[$name] = preg_replace('/#(ul|file)\w*:(\d+)#/', '', $value);
                        }
                    } else {
                        // get the file id's directly from the value
                        if (!empty($args['fileId'])) {
                            // ;NN followed by another ;MM or the end
                            if (!preg_match("/;$args[fileId](;.*|)$/", $value)) {
                                continue;
                            }
                            $values[$name] = preg_replace("/;$args[fileId](;.*|)$/", '$1', $value);
                        } else {
                            $values[$name] = '';
                        }
                    }
                }
                if (empty($values)) {
                    continue;
                }

                // 10. update the item values if necessary
                if (!$this->mod()->apiFunc(
                    'dynamicdata',
                    'admin',
                    'update',
                    ['modid' => $modid,
                        'itemtype' => $itemtype,
                        'itemid' => $itemid,
                        'values' => $values, ]
                )) {
                    return;
                }
            }
        }

        // let's try some articles fields too
        if (!$this->mod()->isAvailable('articles')) {
            return true;
        }
        $artmodid = $this->mod()->getRegID('articles');
        if (!empty($args['modid']) && $args['modid'] != $artmodid) {
            return true;
        }

        $pubtypes = $this->mod()->apiFunc('articles', 'user', 'getpubtypes');
        foreach ($pubtypes as $pubtypeid => $pubtypeinfo) {
            if (!empty($args['itemtype']) && $args['itemtype'] != $pubtypeid) {
                continue;
            }
            if (!$this->mod()->isHooked('uploads', 'articles', $pubtypeid)) {
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
            $articles = $this->mod()->apiFunc(
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
                $values = [];
                foreach ($fieldlist as $field) {
                    if (empty($article[$field])) {
                        continue;
                    }
                    if ($pubtypeinfo['config'][$field]['format'] == 'textupload') {
                        // scan for #ulid:NN# and #file*:NN# in the text - cfr. uploads transformhook
                        if (!empty($args['fileId'])) {
                            if (!preg_match("/#(ul|file)\w*:$args[fileId]#/", $article[$field])) {
                                continue;
                            }
                            $values[$field] = preg_replace("/#(ul|file)\w*:$args[fileId]#/", '', $article[$field]);
                        } else {
                            if (!preg_match('/#(ul|file)\w*:(\d+)#/', $article[$field])) {
                                continue;
                            }
                            $values[$field] = preg_replace('/#(ul|file)\w*:(\d+)#/', '', $article[$field]);
                        }
                    } else {
                        // get the file id's directly from the value
                        if (!empty($args['fileId'])) {
                            // ;NN followed by another ;MM or the end
                            if (!preg_match("/;$args[fileId](;.*|)$/", $article[$field])) {
                                continue;
                            }
                            $values[$field] = preg_replace("/;$args[fileId](;.*|)$/", '$1', $article[$field]);
                        } else {
                            $values[$field] = '';
                        }
                    }
                }
                if (empty($values)) {
                    continue;
                }
                // mandatory arguments for articles update
                $values['aid'] = $article['aid'];
                $values['ptid'] = $article['pubtypeid'];
                if (!isset($values['title'])) {
                    $values['title'] = $article['title'];
                }
                // update the article fields
                if (!$this->mod()->apiFunc(
                    'articles',
                    'admin',
                    'update',
                    $values
                )) {
                    return;
                }
            }
        }

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        // 1. delete any existing associations for these arguments
        if (!$userapi->dbDeleteAssociation($args)) {
            return;
        }

        return true;
    }
}
