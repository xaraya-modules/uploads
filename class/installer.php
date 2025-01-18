<?php

/**
 * Handle module installer functions
 *
 * @package modules\uploads
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Uploads;

use Xaraya\Modules\Uploads\Defines;
use Xaraya\Modules\Mime\UserApi as MimeApi;
use Xaraya\Modules\InstallerClass;
use xarMod;
use xarServer;
use xarModVars;
use xarDB;
use xarTableDDL;
use xarController;
use xarPrivileges;
use xarMasks;
use xarModHooks;
use sys;
use Exception;

sys::import('xaraya.modules.installer');

/**
 * Handle module installer functions
 *
 * @todo add extra use ...; statements above as needed
 * @todo replaced uploads_*() function calls with $this->*() calls
 * @extends InstallerClass<Module>
 */
class Installer extends InstallerClass
{
    /**
     * Configure this module - override this method
     *
     * @todo use this instead of init() etc. for standard installation
     * @return void
     */
    public function configure()
    {
        $this->objects = [
            // add your DD objects here
            //'uploads_object',
        ];
        $this->variables = [
            // add your module variables here
            'hello' => 'world',
        ];
        $this->oldversion = '2.4.1';
    }

    /** xarinit.php functions imported by bermuda_cleanup */

    /**
     * initialise the module
     */
    public function init()
    {
        //Not needed anymore with the dependency checks.
        if (!xarMod::isAvailable('mime')) {
            $msg = $this->ml('The mime module should be activated first');
            throw new Exception($msg);
        }

        // load the predefined constants
        xarMod::apiLoad('uploads', 'user');

        if (xarServer::getVar('SCRIPT_FILENAME')) {
            $base_directory = dirname(realpath(xarServer::getVar('SCRIPT_FILENAME')));
        } else {
            $base_directory = './';
        }
        $this->mod()->setVar('uploads_directory', 'Change me to something outside the webroot');
        $this->mod()->setVar('imports_directory', 'Change me to something outside the webroot');
        $this->mod()->setVar('file.maxsize', '10000000');
        $this->mod()->setVar('file.delete-confirmation', true);
        $this->mod()->setVar('file.auto-purge', false);
        $this->mod()->setVar('file.obfuscate-on-import', false);
        $this->mod()->setVar('file.obfuscate-on-upload', true);
        $this->mod()->setVar('path.imports-cwd', $this->mod()->getVar('imports_directory'));
        $this->mod()->setVar('dd.fileupload.stored', true);
        $this->mod()->setVar('dd.fileupload.external', true);
        $this->mod()->setVar('dd.fileupload.upload', true);
        $this->mod()->setVar('dd.fileupload.trusted', true);
        $this->mod()->setVar('file.auto-approve', Defines::APPROVE_ADMIN);

        $data['filters']['inverse']                     = false;
        $data['filters']['mimetypes'][0]['typeId']      = 0;
        $data['filters']['mimetypes'][0]['typeName']    = $this->ml('All');
        $data['filters']['subtypes'][0]['subtypeId']    = 0;
        $data['filters']['subtypes'][0]['subtypeName']  = $this->ml('All');
        $data['filters']['status'][0]['statusId']       = 0;
        $data['filters']['status'][0]['statusName']     = $this->ml('All');
        $data['filters']['status'][Defines::STATUS_SUBMITTED]['statusId']    = Defines::STATUS_SUBMITTED;
        $data['filters']['status'][Defines::STATUS_SUBMITTED]['statusName']  = 'Submitted';
        $data['filters']['status'][Defines::STATUS_APPROVED]['statusId']     = Defines::STATUS_APPROVED;
        $data['filters']['status'][Defines::STATUS_APPROVED]['statusName']   = 'Approved';
        $data['filters']['status'][Defines::STATUS_REJECTED]['statusId']     = Defines::STATUS_REJECTED;
        $data['filters']['status'][Defines::STATUS_REJECTED]['statusName']   = 'Rejected';
        $filter['fileType']     = '%';
        $filter['fileStatus']   = '';

        $mimetypes = & $data['filters']['mimetypes'];

        /** @var MimeApi $mimeapi */
        $mimeapi = xarMod::getAPI('mime');

        $mimetypes += $mimeapi->getallTypes();

        $this->mod()->setVar('view.filter', serialize(['data' => $data,'filter' => $filter]));
        unset($mimetypes);

        $this->mod()->setVar('items_per_page', 200);
        $this->mod()->setVar('file.cache-expire', 0);
        $this->mod()->setVar('file.allow-duplicate-upload', 0);

        // Get datbase setup
        $dbconn = xarDB::getConn();

        $xartable = xarDB::getTables();

        $file_entry_table = $xartable['file_entry'];
        $file_data_table  = $xartable['file_data'];
        $file_assoc_table = $xartable['file_associations'];

        sys::import('xaraya.tableddl');

        $file_entry_fields = [
            'xar_fileEntry_id' => ['type' => 'integer', 'size' => 'big', 'null' => false,  'increment' => true,'primary_key' => true],
            'xar_user_id'      => ['type' => 'integer', 'size' => 'big', 'null' => false],
            'xar_filename'     => ['type' => 'varchar', 'size' => 128,   'null' => false],
            'xar_location'     => ['type' => 'varchar', 'size' => 255,   'null' => false],
            'xar_status'       => ['type' => 'integer', 'size' => 'tiny','null' => false,  'default' => '0'],
            'xar_filesize'     => ['type' => 'integer', 'size' => 'big',    'null' => false],
            'xar_store_type'   => ['type' => 'integer', 'size' => 'tiny',     'null' => false],
            'xar_mime_type'    => ['type' => 'varchar', 'size' => 128,  'null' => false,  'default' => 'application/octet-stream'],
            'xar_extrainfo'    => ['type' => 'text'],
        ];


        // Create the Table - the function will return the SQL is successful or
        // raise an exception if it fails, in this case $sql is empty
        $query   =  xarTableDDL::createTable($file_entry_table, $file_entry_fields);
        $result  = & $dbconn->Execute($query);

        $file_data_fields = [
            'xar_fileData_id'  => ['type' => 'integer','size' => 'big','null' => false,'increment' => true, 'primary_key' => true],
            'xar_fileEntry_id' => ['type' => 'integer','size' => 'big','null' => false],
            'xar_fileData'     => ['type' => 'blob','size' => 'medium','null' => false],
        ];

        // Create the Table - the function will return the SQL is successful or
        // raise an exception if it fails, in this case $sql is empty
        $query  =  xarTableDDL::createTable($file_data_table, $file_data_fields);
        $result = & $dbconn->Execute($query);

        $file_assoc_fields = [
            'xar_fileEntry_id' => ['type' => 'integer', 'size' => 'big', 'null' => false],
            'xar_modid'        => ['type' => 'integer', 'size' => 'big', 'null' => false],
            'xar_itemtype'     => ['type' => 'integer', 'size' => 'big', 'null' => false, 'default' => '0'],
            'xar_objectid'       => ['type' => 'integer', 'size' => 'big', 'null' => false, 'default' => '0'],
        ];


        // Create the Table - the function will return the SQL is successful or
        // raise an exception if it fails, in this case $sql is empty
        $query   =  xarTableDDL::createTable($file_assoc_table, $file_assoc_fields);
        $result  = & $dbconn->Execute($query);

        $instances[0]['header'] = 'external';
        $instances[0]['query']  = $this->mod()->getURL('admin', 'privileges');
        $instances[0]['limit']  = 0;

        xarPrivileges::defineInstance('uploads', 'File', $instances);

        xarMasks::register('ViewUploads', 'All', 'uploads', 'File', 'All', 'ACCESS_OVERVIEW');
        xarMasks::register('ReadUploads', 'All', 'uploads', 'File', 'All', 'ACCESS_READ');
        xarMasks::register('EditUploads', 'All', 'uploads', 'File', 'All', 'ACCESS_EDIT');
        xarMasks::register('AddUploads', 'All', 'uploads', 'File', 'All', 'ACCESS_ADD');
        xarMasks::register('ManageUploads', 'All', 'uploads', 'File', 'All', 'ACCESS_DELETE');
        xarMasks::register('AdminUploads', 'All', 'uploads', 'File', 'All', 'ACCESS_ADMIN');

        xarPrivileges::register('ViewUploads', 'All', 'uploads', 'File', 'All', 'ACCESS_OVERVIEW');
        xarPrivileges::register('ReadUploads', 'All', 'uploads', 'File', 'All', 'ACCESS_READ');
        xarPrivileges::register('EditUploads', 'All', 'uploads', 'File', 'All', 'ACCESS_EDIT');
        xarPrivileges::register('AddUploads', 'All', 'uploads', 'File', 'All', 'ACCESS_ADD');
        xarPrivileges::register('ManageUploads', 'All', 'uploads', 'File', 'All', 'ACCESS_DELETE');
        xarPrivileges::register('AdminUploads', 'All', 'uploads', 'File', 'All', 'ACCESS_ADMIN');

        /**
         * Register hooks
         */
        if (!xarModHooks::register('item', 'transform', 'API', 'uploads', 'user', 'transformhook')) {
            $msg = $this->ml('Could not register hook');
            throw new Exception($msg);
        }
        /*
            if (!xarModHooks::register('item', 'create', 'API', 'uploads', 'admin', 'createhook')) {
                 $msg = $this->ml('Could not register hook');
                throw new Exception($msg);
            }
            if (!xarModHooks::register('item', 'update', 'API', 'uploads', 'admin', 'updatehook')) {
                 $msg = $this->ml('Could not register hook');
                throw new Exception($msg);
            }
            if (!xarModHooks::register('item', 'delete', 'API', 'uploads', 'admin', 'deletehook')) {
                 $msg = $this->ml('Could not register hook');
                throw new Exception($msg);
            }
            // when a whole module is removed, e.g. via the modules admin screen
            // (set object ID to the module name !)
            if (!xarModHooks::register('module', 'remove', 'API', 'uploads', 'admin', 'removehook')) {
                 $msg = $this->ml('Could not register hook');
                throw new Exception($msg);
            }
        */

        return true;
    }

    /**
     * upgrade the uploads module from an old version
     */
    public function upgrade($oldversion)
    {
        // Upgrade dependent on old version number
        switch ($oldversion) {
            case '1.1.0':
                // fall through
            case '2.6.0':
                // fall through
            default:
                break;
        }

        return true;
    }

    /**
     * delete the uploads module
     */
    public function delete()
    {
        xarModHooks::unregister('item', 'transform', 'API', 'uploads', 'user', 'transformhook');
        /*
            xarModHooks::unregister('item', 'create', 'API', 'uploads', 'admin', 'createhook');
            xarModHooks::unregister('item', 'update', 'API', 'uploads', 'admin', 'updatehook');
            xarModHooks::unregister('item', 'delete', 'API', 'uploads', 'admin', 'deletehook');
            xarModHooks::unregister('module', 'remove', 'API', 'uploads', 'admin', 'removehook');
        */

        $module = 'uploads';
        return xarMod::apiFunc('modules', 'admin', 'standarddeinstall', ['module' => $module]);
    }
}
