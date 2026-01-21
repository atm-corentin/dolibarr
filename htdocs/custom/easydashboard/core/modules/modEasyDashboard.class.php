<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019      florian Dufourg	<florian.dufourg@outlook.fr>
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
 * 	\defgroup   easydashboard     Module EasyDashboard
 *  \brief      EasyDashboard module descriptor.
 *
 *  \file       htdocs/easydashboard/core/modules/modEasyDashboard.class.php
 *  \ingroup    easydashboard
 *  \brief      Description and activation file for module EasyDashboard
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module EasyDashboard
 */
class modEasyDashboard extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs,$conf;
        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 141151; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'easydashboard';
        // Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
        // It is used to group modules by family in module setup page
        $this->family = "other";
        // Module position in the family on 2 digits ('01', '10', '20', ...)
        $this->module_position = '90';
        // Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        //$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
        // Module label (no space allowed), used if translation string 'ModuleEasyDashboardName' not found (EasyDashboard is name of module).
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description, used if translation string 'ModuleEasyDashboardDesc' not found (EasyDashboard is name of module).
        $this->description = "EasyDashboardDescription";
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = "EasyDashboard description (Long)";
        $this->editor_name = 'Florian Dufourg';
        $this->editor_url = '';
        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '4.17';
        // Url to the file with your last numberversion of this module
        //$this->url_last_version = 'http://www.example.com/versionmodule.txt';

        // Key used in llx_const table to save module status enabled/disabled (where EASYDASHBOARD is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto='generic';
        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
        $this->module_parts = array(
            // Set this to 1 if module has its own trigger directory (core/triggers)
            'triggers' => 0,
            // Set this to 1 if module has its own login method file (core/login)
            'login' => 0,
            // Set this to 1 if module has its own substitution function file (core/substitutions)
            'substitutions' => 0,
            // Set this to 1 if module has its own menus handler directory (core/menus)
            'menus' => 0,
            // Set this to 1 if module overwrite template dir (core/tpl)
            'tpl' => 0,
            // Set this to 1 if module has its own barcode directory (core/modules/barcode)
            'barcode' => 0,
            // Set this to 1 if module has its own models directory (core/modules/xxx)
            'models' => 0,
            // Set this to 1 if module has its own theme directory (theme)
            'theme' => 0,
            // Set this to relative path of css file if module has its own css file
            'css' => array(
                //    '/easydashboard/css/easydashboard.css.php',
            ),
            // Set this to relative path of js file if module must load a js on all pages
            'js' => array(
                //   '/easydashboard/js/easydashboard.js.php',
            ),
            // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
            'hooks' => array(
                //   'data' => array(
                //       'hookcontext1',
                //       'hookcontext2',
                //   ),
                //   'entity' => '0',
            ),
            // Set this to 1 if features of module are opened to external users
            'moduleforexternal' => 0,
        );
        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/easydashboard/temp","/easydashboard/subdir");
        $this->dirs = array("/easydashboard/temp");
        // Config pages. Put here list of php page, stored into easydashboard/admin directory, to use to setup module.
        $this->config_page_url = array("setup.php@easydashboard");
        // Dependencies
        // A condition to hide module
        $this->hidden = false;
        // List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
        $this->depends = array();
        $this->requiredby = array();	// List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = array();	// List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
        $this->langfiles = array("easydashboard@easydashboard");
        $this->phpmin = array(5,5);					    // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(8,0);		// Minimum version of Dolibarr required by module
        $this->warnings_activation = array();			// Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        $this->warnings_activation_ext = array();		// Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)


        if (! isset($conf->easydashboard) || ! isset($conf->easydashboard->enabled)) {
            $conf->easydashboard=new stdClass();
            $conf->easydashboard->enabled=0;
        }

        // Array to add new pages in new tabs
        $this->tabs = array();

        // Dictionaries
        $this->dictionaries=array();

        // Permissions provided by this module
        $this->rights = array();
        $r=0;
        // Add here entries to declare new permissions
        /* BEGIN MODULEBUILDER PERMISSIONS */
        $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'Read the dashboard';	// Permission label
        $this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->easydashboard->level1->level2)
        $this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->easydashboard->level1->level2)
        $r++;
        /* END MODULEBUILDER PERMISSIONS */

        // Main menu entries to add
        $this->menu = array();
        $r=0;
        // Add here entries to declare new menus
        /* BEGIN MODULEBUILDER LEFTMENU MYOBJECT*/
        $this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=home',                          // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',                          // This is a Top menu entry
            'titre'=>'<i class="fa fa-line-chart fa-fw paddingright"></i>Easy Dashboard',
            'mainmenu'=>'home',
            'leftmenu'=>'home',
            'url'=>'/easydashboard/easydashboardindex.php',
            'langs'=>'easydashboard@easydashboard',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1+$r,
            'enabled'=>'$user->rights->easydashboard->read',  // Define condition to show or hide menu entry. Use '$conf->dashboard->enabled' if entry must be visible if module is enabled.
            'perms'=>'1',			                // Use 'perms'=>'$user->rights->dashboard->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
        );
        /* END MODULEBUILDER LEFTMENU MYOBJECT */


        // Exports profiles provided by this module
        $r=1;
        /* BEGIN MODULEBUILDER EXPORT MYOBJECT */
        /*
        $langs->load("easydashboard@easydashboard");
        $this->export_code[$r]=$this->rights_class.'_'.$r;
        $this->export_label[$r]='MyObjectLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_icon[$r]='myobject@easydashboard';
        $keyforclass = 'MyObject'; $keyforclassfile='/mymobule/class/myobject.class.php'; $keyforelement='myobject';
        include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
        $keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject';
        include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
        //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'myobject as t';
        $this->export_sql_end[$r] .=' WHERE 1 = 1';
        $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('myobject').')';
        $r++; */
        /* END MODULEBUILDER EXPORT MYOBJECT */

        // Imports profiles provided by this module
        $r=1;
        /* BEGIN MODULEBUILDER IMPORT MYOBJECT */
        /*
         $langs->load("easydashboard@easydashboard");
         $this->export_code[$r]=$this->rights_class.'_'.$r;
         $this->export_label[$r]='MyObjectLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
         $this->export_icon[$r]='myobject@easydashboard';
         $keyforclass = 'MyObject'; $keyforclassfile='/mymobule/class/myobject.class.php'; $keyforelement='myobject';
         include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
         $keyforselect='myobject'; $keyforaliasextra='extra'; $keyforelement='myobject';
         include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
         //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
         $this->export_sql_start[$r]='SELECT DISTINCT ';
         $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'myobject as t';
         $this->export_sql_end[$r] .=' WHERE 1 = 1';
         $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('myobject').')';
         $r++; */
        /* END MODULEBUILDER IMPORT MYOBJECT */
    }

    /**
     *  Function called when module is enabled.
     *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *  It also creates data directories
     *
     *  @param      string  $options    Options when enabling module ('', 'noboxes')
     *  @return     int             	1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $result=$this->_load_tables('/easydashboard/sql/');
        if ($result < 0) return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')

		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);

		$result1=$extrafields->addExtraField(
			'percent_expense',	//$attrname
			"percentExpense",	//$label
			'select',	//$type (text,varchar,html,int,double,date,price,select,sellist,radio...)
			10,	//$pos
			10,	//$size
			'commande',	//$elementtype
			0,	//$unique = 0
			0,	//$required = 0
			'',	//$default_value = ''
			array('options'=>array('0'=>'0%','10'=>'10%','20'=>'20%','30'=>'30%','40'=>'40%','50'=>'50%','60'=>'60%','70'=>'70%','80'=>'80%','90'=>'90%','100'=>'100%')),	//$param / list,
			1,	//$alwayseditable = 0
			'',	//$perms
			1,	//visibility
			'',	//$help = ''
			'',	//$computed = ''
			0,	//$entity = ''
			'easydashboard@easydashboard',		//$langfile = ''
			'$conf->easydashboard->enabled',		//$enabled = '1'
			0		//totalizable
		);
		
		$result2=$extrafields->addExtraField(
			'revient',	//$attrname
			"revient",	//$label
			'price',	//$type (text,varchar,html,int,double,date,price,select,sellist,radio...)
			10,	//$pos
			10,	//$size
			'contratdet',	//$elementtype
			0,	//$unique = 0
			0,	//$required = 0
			'',	//$default_value = ''
			'',	//$param / list,
			1,	//$alwayseditable = 0
			'',	//$perms
			1,	//visibility
			'PricePerUnity',	//$help = ''
			'',	//$computed = ''
			0,	//$entity = ''
			'easydashboard@easydashboard',		//$langfile = ''
			'$conf->easydashboard->enabled',		//$enabled = '1'
			0		//totalizable
		);
		
		$result3=$extrafields->addExtraField(
			'period_contract',	//$attrname
			"period_contract",	//$label
			'select',	//$type (text,varchar,html,int,double,date,price,select,sellist,radio...)
			10,	//$pos
			10,	//$size
			'contrat',	//$elementtype
			0,	//$unique = 0
			0,	//$required = 0
			'',	//$default_value = ''
			array('options'=>array('1'=>'Monthly','3'=>'Quarterly','6'=>'Biannual','12'=>'Annual')),	//$param / list,
			1,	//$alwayseditable = 0
			'',	//$perms
			1,	//visibility
			'contractValuePeriod',	//$help = ''
			'',	//$computed = ''
			0,	//$entity = ''
			'easydashboard@easydashboard',		//$langfile = ''
			'$conf->easydashboard->enabled',		//$enabled = '1'
			0		//totalizable
		);


        // Create extrafields during init
        //include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        //$extrafields = new ExtraFields($this->db);
        //$result1=$extrafields->addExtraField('myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'easydashboard@easydashboard', '$conf->easydashboard->enabled');
        //$result2=$extrafields->addExtraField('myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'easydashboard@easydashboard', '$conf->easydashboard->enabled');
        //$result3=$extrafields->addExtraField('myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'easydashboard@easydashboard', '$conf->easydashboard->enabled');
        //$result4=$extrafields->addExtraField('myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'easydashboard@easydashboard', '$conf->easydashboard->enabled');
        //$result5=$extrafields->addExtraField('myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'easydashboard@easydashboard', '$conf->easydashboard->enabled');

        $sql = array();
        return $this->_init($sql, $options);
    }

    /**
     *  Function called when module is disabled.
     *  Remove from database constants, boxes and permissions from Dolibarr database.
     *  Data directories are not deleted
     *
     *  @param      string	$options    Options when enabling module ('', 'noboxes')
     *  @return     int                 1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}