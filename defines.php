<?php

/**
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads;

/**
 * Defines constants for the uploads module (from xaruserapi.php)
 */
class Defines
{
    /*
    * Original Author of file: Marie Altobelli (Ladyofdragons)
    * Set various PARAM values
    */

    public const STORE_DB_ENTRY = 1 << 0;
    public const STORE_FILESYSTEM = 1 << 1;
    public const STORE_FSDB = self::STORE_FILESYSTEM | self::STORE_DB_ENTRY;
    public const STORE_DB_DATA = 1 << 2;
    public const STORE_DB_FULL = self::STORE_DB_ENTRY | self::STORE_DB_DATA;
    public const STORE_TEXT = 1 << 3;
    public const LOCATION_TRUSTED = 1 << 4;
    public const LOCATION_UNTRUSTED = 1 << 5;
    public const LOCATION_OTHER = 1 << 6;

    public const STATUS_SUBMITTED = 1;      // File has been recently submitted and needs approving
    public const STATUS_APPROVED = 2;      // File has been approved and is ready for system use
    public const STATUS_REJECTED = 3;      // File has been rejected and needs deleting

    public const TYPE_UNKNOWN = -1;      // Inode is of unknown type
    public const TYPE_P_DIRECTORY = 1;      // Inode is the previous directory
    public const TYPE_C_DIRECTORY = 2;      // Inode is the current directory
    public const TYPE_DIRECTORY = 3;      // Inode is a directory
    public const TYPE_FILE = 4;      // Inode is a file
    public const TYPE_LINK = 5;      // Inode is a link (symbolic or otherwise)

    public const ERROR_UNKNOWN = -1; // Unidentifiable error
    public const ERROR_NONE = 0; // No error
    public const ERROR_NO_OBFUSCATE = 1; // Unable to obfuscate the filename
    public const ERROR_BAD_FORMAT = 2; // Incorrect DATA structure
    public const ERROR_NOT_EXIST = 3; // non-existent file

    public const GET_UPLOAD = 1;
    public const GET_EXTERNAL = 2;
    public const GET_EXT_FTP = 3;
    public const GET_EXT_HTTP = 4;
    public const GET_LOCAL = 5;
    public const GET_REFRESH_LOCAL = 6;
    public const GET_STORED = 7;
    public const GET_NOTHING = 8;

    public const APPROVE_NOONE = 1;
    public const APPROVE_EVERYONE = 2;
    public const APPROVE_ADMIN = 3;
}
