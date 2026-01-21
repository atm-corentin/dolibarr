<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2017      AXeL                 <contact.axel.dev@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \defgroup   doliexportlist     Module DoliExportList
 *  \brief      Example of a module descriptor.
 *              Such a file must be copied into htdocs/doliexportlist/core/modules directory.
 *  \file       htdocs/doliexportlist/core/modules/modDoliExportList.class.php
 *  \ingroup    doliexportlist
 *  \brief      Description and activation file for module DoliExportList
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module DoliExportList
 */
class modDoliExportList extends DolibarrModules
{
    /**
     *   Constructor. Define names, constants, directories, boxes, permissions
     *
     *   @param      DoliDB     $db      Database handler
     */
    function __construct($db)
    {
        global $langs,$conf;

        $this->db = $db;

        $this->editor_name = 'CodLines Developer';
        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 483154;
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'doliexportlist';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        // It is used to group modules in module setup page
        $this->family = "CodLines Developer";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Exportar listas Dolibarr";
        // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = '2.1.0';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
        $this->special = 0;
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $picto = function_exists('version_compare') && version_compare(DOL_VERSION, '12.0.0') >= 0 ? "doliexportlist_128" : "doliexportlist";
        $this->picto = $picto."@doliexportlist";

        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
        // for default path (eg: /doliexportlist/core/xxxxx) (0=disable, 1=enable)
        // for specific path of parts (eg: /doliexportlist/core/modules/barcode)
        // for specific css file (eg: /doliexportlist/css/doliexportlist.css.php)
        $this->module_parts = array('hooks'=>array('main'));

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/doliexportlist/temp");
        $this->dirs = array();

        // Config pages. Put here list of php page, stored into doliexportlist/admin directory, to use to setup module.
        $this->config_page_url = array("setup.php@doliexportlist");

        // Dependencies
        $this->hidden = false;          // A condition to hide module
        $this->depends = array();       // List of modules id that must be enabled if this module is enabled
        $this->requiredby = array();    // List of modules id to disable if this one is disabled
        $this->conflictwith = array();  // List of modules id this module is in conflict with
        $this->phpmin = array(5,3);                 // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(3,9);  // Minimum version of Dolibarr required by module
        $this->langfiles = array("doliexportlist@doliexportlist");

        // Constants
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        $this->const = array();
        $r=0;

        $this->const[$r][0] = "LIST_EXPORT_IMPORT_USE_COMPACT_MODE";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "0";
        $this->const[$r][3] = 'Use compact mode of list export/import module';
        $this->const[$r][4] = 0;
        $r++;


        $this->tabs = array();

        // Dictionaries
        if (! isset($conf->doliexportlist->enabled))
        {
            $conf->doliexportlist=new stdClass();
            $conf->doliexportlist->enabled=0;
        }
        $this->dictionaries=array();

        // Boxes
        // Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array();         // List of boxes

        // Permissions
        $this->rights = array();        // Permission array used by this module
        $r=0;

        $this->rights[$r][0] = 413451;
        $this->rights[$r][1] = 'Export list';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'export';
        $r++;

        /*$this->rights[$r][0] = 413452;
        $this->rights[$r][1] = 'Import list';
        $this->rights[$r][2] = 'm';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'import';
        $r++;*/

        // Main menu entries
        $this->menu = array();          // List of menus to add
        //$r=0;

        // Exports
        //$r=1;
    }

    /**
     *      Function called when module is enabled.
     *      The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *      It also creates data directories
     *
     *      @param      string  $options    Options when enabling module ('', 'noboxes')
     *      @return     int                 1 if OK, 0 if KO
     */
    function init($options='')
    {
        $sql = array();

        // Fix customer invoice export issue: empty ref column
        // $file = DOL_DOCUMENT_ROOT . "/compta/facture/list.php";
        // $content = file_get_contents($file);
        // $new_content = str_replace(
        //     'print empty($obj->increment) ? \'\' : \' (\'.$obj->increment.\')\';'."\n\n\t\t\t\t".'$filename = dol_sanitizeFileName($obj->ref);',
        //     'print empty($obj->increment) ? \'\' : \' (\'.$obj->increment.\')\';'."\n\t\t\t\t".'print \'</td>\';'."\n\n\t\t\t\t".'print \'<td style="min-width: 20px" class="nobordernopadding nowrap">\';'."\n\t\t\t\t".'$filename = dol_sanitizeFileName($obj->ref);',
        //     $content
        // );
        // if ($new_content != $content) {
        //     file_put_contents($file, $new_content);
        // }

        // Load tables
        $result=$this->_load_tables('/doliexportlist/sql/');

        return $this->_init($sql, $options);
    }

    /**
     *      Function called when module is disabled.
     *      Remove from database constants, boxes and permissions from Dolibarr database.
     *      Data directories are not deleted
     *
     *      @param      string  $options    Options when enabling module ('', 'noboxes')
     *      @return     int                 1 if OK, 0 if KO
     */
    function remove($options='')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }

}
