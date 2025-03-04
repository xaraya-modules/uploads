<?php

/**
 * Uploads Module
 *
 * @package modules
 * @subpackage uploads module
 * @category Third Party Xaraya Module
 * @version 2.6.0
 * @copyright see the html/credits.html file in this Xaraya release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com/index.php/release/eid/666
 * @author Uploads Module Development Team
 */

/**
 *
 * The "media-types" directory contains a subdirectory for each content
 * type and each of those directories contains a file for each content
 * subtype.
 *
 *                                |-application-
 *                                |-audio-------
 *                                |-image-------
 *                  |-media-types-|-message-----
 *                                |-model-------
 *                                |-multipart---
 *                                |-text--------
 *                                |-video-------
 *
 *    URL = ftp://ftp.isi.edu/in-notes/iana/assignments/media-types
 */

$modversion['name']         = 'uploads';
$modversion['id']           = '666';
$modversion['version']      = '2.6.0'; // long jump to current mime version
$modversion['displayname']  = xarMLS::translate('Uploads');
$modversion['description']  = 'Upload/Download File Handler';
$modversion['credits']      = 'docs/credits.txt';
$modversion['help']         = 'docs/help.txt';
$modversion['changelog']    = 'docs/changelog.txt';
$modversion['license']      = 'docs/license.txt';
$modversion['official']     = 1;
$modversion['author']       = 'Marie Altobelli (Ladyofdragons); Michael Cortez (mcortez); Carl P. Corliss (rabbitt)';
$modversion['contact']      = 'ladyofdragons@xaraya.com; mcortez@xaraya.com; rabbitt@xaraya.com';
$modversion['admin']        = 1;
$modversion['user']         = 0;
$modversion['class']        = 'Utility';
$modversion['category']     = 'Global';
$modversion['dependency']   = [ 999 ];
$modversion['namespace']    = 'Xaraya\Modules\Uploads';
$modversion['twigtemplates']  = true;
$modversion['dependencyinfo'] = [
    0 => [
        'name' => 'Xaraya Core',
        'version_ge' => '2.4.1',
    ],
    999 => [
        'name' => 'mime',
        'minversion' => '2.6.0',
    ],
];
