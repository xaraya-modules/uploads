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
 * This function is called internally by the core whenever the module is
 * loaded.  It adds in the information
 * Original Author of file: Carl P. corliss
 */
function uploads_xartables(?string $prefix = null)
{
    // Initialise table array
    $xartable = [];

    $prefix ??= xarDB::getPrefix();

    // Get the name for the uploads item table.  This is not necessary
    // but helps in the following statements and keeps them readable
    $fileEntry_table = $prefix . '_file_entry';
    $fileData_table  = $prefix . '_file_data';
    $fileAssoc_table = $prefix . '_file_assoc';

    // Set the table name
    $xartable['file_entry']         = $fileEntry_table;
    $xartable['file_data']          = $fileData_table;
    $xartable['file_associations']  = $fileAssoc_table;

    // Return the table information
    return $xartable;
}
