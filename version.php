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

namespace Xaraya\Modules\Uploads;

class Version
{
    /**
     * Get module version information
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'name' => 'uploads',
            'id' => '666',
            'version' => '2.6.0',
            'displayname' => 'Uploads',
            'description' => 'Upload/Download File Handler',
            'credits' => 'docs/credits.txt',
            'help' => 'docs/help.txt',
            'changelog' => 'docs/changelog.txt',
            'license' => 'docs/license.txt',
            'official' => 1,
            'author' => 'Marie Altobelli (Ladyofdragons); Michael Cortez (mcortez); Carl P. Corliss (rabbitt)',
            'contact' => 'ladyofdragons@xaraya.com; mcortez@xaraya.com; rabbitt@xaraya.com',
            'admin' => 1,
            'user' => 0,
            'class' => 'Utility',
            'category' => 'Global',
            'dependency'
             => [
                 0 => 999,
             ],
            'namespace' => 'Xaraya\\Modules\\Uploads',
            'twigtemplates' => true,
            'dependencyinfo'
             => [
                 0
                  => [
                      'name' => 'Xaraya Core',
                      'version_ge' => '2.4.1',
                  ],
                 999
                  => [
                      'name' => 'mime',
                      'minversion' => '2.6.0',
                  ],
             ],
        ];
    }
}
