<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads\UserGui;

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Uploads\UserGui;
use Xaraya\Modules\Uploads\UserApi;
use Xaraya\Modules\MethodClass;
use xarMod;
use xarSecurity;
use xarVar;
use xarSession;
use xarModHooks;
use xarController;
use xarTpl;
use sys;
use Exception;

sys::import('xaraya.modules.method');

/**
 * uploads user file_properties function
 * @extends MethodClass<UserGui>
 */
class FilePropertiesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * @see UserGui::fileProperties()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!$this->sec()->checkAccess('ViewUploads')) {
            return;
        }
        $this->var()->get('fileId', $fileId, 'int:1');
        $this->var()->find('fileName', $fileName, 'str:1:64', '');

        if (!isset($fileId)) {
            $msg = $this->ml(
                'Missing paramater [#(1)] for GUI function [#(2)] in module [#(3)].',
                'fileId',
                'file_properties',
                'uploads'
            );
            throw new Exception($msg);
        }

        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        $fileInfo = $userapi->dbGetFile(['fileId' => $fileId]);
        if (empty($fileInfo) || !count($fileInfo)) {
            $data['fileInfo']   = [];
            $data['error']      = $this->ml('File not found!');
        } else {
            // the file should be the first indice in the array
            $fileInfo = end($fileInfo);

            $instance[0] = $fileInfo['fileTypeInfo']['typeId'];
            $instance[1] = $fileInfo['fileTypeInfo']['subtypeId'];
            $instance[2] = $this->user()->getId();
            $instance[3] = $fileId;

            $instance = implode(':', $instance);
            if (xarSecurity::check('EditUploads', 0, 'File', $instance)) {
                $data['allowedit'] = 1;
                $data['hooks'] = xarModHooks::call(
                    'item',
                    'modify',
                    $fileId,
                    ['module'    => 'uploads',
                        'itemtype'  => 1, ]
                );
            } else {
                $data['allowedit'] = 0;
            }

            if (isset($fileName) && !empty($fileName)) {
                if ($data['allowedit']) {
                    $args['fileId'] = $fileId;
                    $args['fileName'] = trim($fileName);

                    if (!$userapi->dbModifyFile($args)) {
                        $msg = $this->ml(
                            'Unable to change filename for file: #(1) with file Id #(2)',
                            $fileInfo['fileName'],
                            $fileInfo['fileId']
                        );
                        throw new Exception($msg);
                    }
                    $this->ctl()->redirect($this->mod()->getURL(
                        'user',
                        'file_properties',
                        ['fileId' => $fileId]
                    ));
                    return;
                } else {
                    $msg = $this->ml('You do not have the necessary permissions for this object.');
                    throw new Exception($msg);
                }
            }

            if ($fileInfo['fileStatus'] == Defines::STATUS_APPROVED || xarSecurity::check('ViewUploads', 1, 'File', $instance)) {
                // we don't want the theme to show up, so
                // get rid of everything in the buffer
                ob_end_clean();

                $storeType  = ['long' => '', 'short' => $fileInfo['storeType']];
                $storeType['long'] = 'Database File Entry';

                if (Defines::STORE_FILESYSTEM & $fileInfo['storeType']) {
                    if (!empty($storeType['long'])) {
                        $storeType['long'] .= ' / ';
                    }
                    $storeType['long'] .= 'File System Store';
                } elseif (Defines::STORE_DB_DATA & $fileInfo['storeType']) {
                    if (!empty($storeType['long'])) {
                        $storeType['long'] .= ' / ';
                    }
                    $storeType['long'] .= 'Database Store';
                }

                $fileInfo['storeType'] = $storeType;
                unset($storeType);

                $fileInfo['size'] = $userapi->normalizeFilesize(['fileSize' => $fileInfo['fileSize']]);

                if (mb_ereg('^image', $fileInfo['fileType'])) {
                    // let the images module handle it
                    if ($this->mod()->isAvailable('images')) {
                        $fileInfo['image'] = true;

                        // try to get the image size
                    } elseif (file_exists($fileInfo['fileLocation'])) {
                        $imageInfo = @getimagesize($fileInfo['fileLocation']);
                        if (is_array($imageInfo)) {
                            if ($imageInfo['0'] > 100 || $imageInfo[1] > 400) {
                                $oWidth  = $imageInfo[0];
                                $oHeight = $imageInfo[1];

                                $ratio = $oHeight / $oWidth;

                                // MAX WIDTH is 200 for this preview.
                                $newWidth  = 100;
                                $newHeight = round($newWidth * $ratio, 0);

                                $fileInfo['image']['height'] = $newHeight;
                                $fileInfo['image']['width']  = $newWidth;
                            } else {
                                $fileInfo['image']['height'] = $imageInfo[1];
                                $fileInfo['image']['width']  = $imageInfo[0];
                            }
                        }

                        // check if someone else already stored this information
                    } elseif (!empty($fileInfo['extrainfo']) && !empty($fileInfo['extrainfo']['width'])) {
                        $fileInfo['image']['height'] = $fileInfo['extrainfo']['height'];
                        $fileInfo['image']['width']  = $fileInfo['extrainfo']['width'];
                    }

                    if (empty($fileInfo['image'])) {
                        $fileInfo['image']['height'] = '';
                        $fileInfo['image']['width']  = '';
                    }
                }

                $fileInfo['numassoc'] = $userapi->dbCountAssociations([
                    'fileId' => $fileId,
                ]);

                $data['fileInfo'] = $fileInfo;

                $data['context'] ??= $this->getContext();
                echo $this->tpl()->module('uploads', 'user', 'file_properties', $data, null);
                $this->exit();
                return;
            } else {
                $msg = $this->ml('You do not have the necessary permissions for this object.');
                throw new Exception($msg);
            }
        }

        return $data;
    }
}
