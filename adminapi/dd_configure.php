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
use Xaraya\Modules\MethodClass;
use xarModVars;
use sys;
use BadParameterException;

sys::import('xaraya.modules.method');

/**
 * uploads adminapi dd_configure function
 * @extends MethodClass<AdminApi>
 */
class DdConfigureMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Summary of __invoke
     * @todo fix signature
     * @param mixed $confString
     * @return array
     * @see AdminApi::ddConfigure()
     */
    public function __invoke($confString = null)
    {
        // Default to multiple selection
        $multiple = true;
        // Grab the sitewide defaults for the methods
        $methods = [
            'trusted'  => $this->mod()->getVar('dd.fileupload.trusted') ? true : false,
            'external' => $this->mod()->getVar('dd.fileupload.external') ? true : false,
            'upload'   => $this->mod()->getVar('dd.fileupload.upload') ? true : false,
            'stored'   => $this->mod()->getVar('dd.fileupload.stored') ? true : false,
        ];
        $basedir = null;
        $importdir = null;

        if (!isset($confString) || empty($confString)) {
            $conf = [];
        } elseif (stristr($confString, ';')) {
            $conf = explode(';', $confString);
        } else {
            $conf = [$confString];
        }
        foreach ($conf as $item) {
            $item = trim($item);
            $check = strtolower(substr($item, 0, 6));

            if ('single' == $check) {
                $multiple = 0;
            } elseif ('basedi' == $check) {
                if (preg_match('/^basedir\((.+)\)$/i', $item, $matches)) {
                    $basedir = $matches[1];
                }
            } elseif ('import' == $check) {
                if (preg_match('/^importdir\((.+)\)$/i', $item, $matches)) {
                    $importdir = $matches[1];
                }
            } elseif ('method' == $check) {
                $item = strtolower($item);
                if (stristr($item, 'methods')) {
                    // if it's the methods, then let's set them up
                    preg_match('/^methods\(([^)]*)\)$/i', $item, $parts);

                    // if any methods were specified, then we should have at -least-
                    // two parts here - otherwise, there will be just the whole item
                    // if no methods were specified, use the defaults.
                    if (count($parts) <= 1) {
                        continue;
                    } elseif (count($parts) == 2) {
                        // reset the methods to nothing
                        // and add only the ones specified
                        $list = explode(',', $parts[1]);
                        foreach ($list as $method) {
                            $method = trim(strtolower($method));

                            // grab the modifier if there was one
                            preg_match('/^(\-|\+)?([a-z0-9_-]*)/i', $method, $matches);
                            [$full, $modifier, $method] = $matches;
                            // If modifier == '-' then we are specifically
                            // turning off this file import method,
                            // otherwise, leave it as on
                            if (!empty($modifier) && $modifier == '-') {
                                $modifier = (int) false;
                            } else {
                                $modifier = (int) true;
                            }

                            switch ($method) {
                                case 'upload':
                                case 'uploads':
                                    $methods['upload'] = $modifier;
                                    break;
                                case 'external':
                                case 'extern':
                                    $methods['external'] = $modifier;
                                    break;
                                case 'trusted':
                                case 'trust':
                                    $methods['trusted'] = $modifier;
                                    break;
                                case 'stored':
                                case 'store':
                                    $methods['stored'] = $modifier;
                                    break;
                                default:
                            }
                        }
                    }
                }
            }
        }

        // FIXME: clean up weird return format
        // return the settings
        $options[0] = $multiple;
        $options[1] = $methods;
        $options[2] = $basedir;
        $options[3] = $importdir;
        $options['multiple']  = $multiple;
        $options['methods']   = $methods;
        $options['basedir']   = $basedir;
        $options['importdir'] = $importdir;

        return $options;
    }
}
