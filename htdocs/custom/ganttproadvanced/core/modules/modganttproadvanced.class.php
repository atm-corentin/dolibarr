<?php
/**
 * 	\defgroup   ganttproadvanced     Module ganttproadvanced
 *  \brief      Example of a module descriptor.
 *				Such a file must be copied into htdocs/ganttproadvanced/core/modules directory.
 *  \file       htdocs/ganttproadvanced/core/modules/modganttproadvanced.class.php
 *  \ingroup    ganttproadvanced
 *  \brief      Description and activation file for module ganttproadvanced
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module ganttproadvanced
 */
class modganttproadvanced extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
        global $langs,$conf;

        $this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 19051700;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'ganttproadvanced';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "NextGestion";
		$this->editor_name = 'NextGestion';
		$this->editor_url = 'https://www.nextgestion.com';
		
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Module933330070Desc";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '4.0';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='ganttproadvanced@ganttproadvanced';
		
		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /ganttproadvanced/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /ganttproadvanced/core/modules/barcode)
		// for specific css file (eg: /ganttproadvanced/css/ganttproadvanced.css.php)
		//$this->module_parts = array(
		//                        	'triggers' => 0,                                 	// Set this to 1 if module has its own trigger directory (core/triggers)
		//							'login' => 0,                                    	// Set this to 1 if module has its own login method directory (core/login)
		//							'substitutions' => 0,                            	// Set this to 1 if module has its own substitution function file (core/substitutions)
		//							'menus' => 0,                                    	// Set this to 1 if module has its own menus handler directory (core/menus)
		//							'theme' => 0,                                    	// Set this to 1 if module has its own theme directory (theme)
		//                        	'tpl' => 0,                                      	// Set this to 1 if module overwrite template dir (core/tpl)
		//							'barcode' => 0,                                  	// Set this to 1 if module has its own barcode directory (core/modules/barcode)
		//							'models' => 0,                                   	// Set this to 1 if module has its own models directory (core/modules/xxx)
		//							'css' => array('/ganttproadvanced/css/ganttproadvanced.css.php'),	// Set this to relative path of css file if module has its own css file
	 	//							'js' => array('/ganttproadvanced/js/ganttproadvanced.js'),          // Set this to relative path of js file if module must load a js on all pages
		//							'hooks' => array('hookcontext1','hookcontext2')  	// Set here all hooks context managed by module
		//							'dir' => array('output' => 'othermodulename'),      // To force the default directories names
		//							'workflow' => array('WORKFLOW_MODULE1_YOURACTIONTYPE_MODULE2'=>array('enabled'=>'! empty($conf->module1->enabled) && ! empty($conf->module2->enabled)', 'picto'=>'yourpicto@ganttproadvanced')) // Set here all workflow context managed by module
		//                        );
		$this->module_parts = array(
		    // 'hooks' => array('ganttproadvancedpage','ganttproadvanced'),
			'triggers' 	=> 1,
			'hooks' => array('projectcard','projecttaskcard','projecttaskscard', 'timesheetperdaycard', 'timesheetpermonthcard', 'timesheetperweekcard', 'contacttpl'), 
			// 'css' 	=> array('/ganttproadvanced/css/ganttproadvanced.css.php'),
			'css' 	=> array('/ganttproadvanced/css/ganttproadvanced.css'),
			'js' 	=> array(),
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/ganttproadvanced/temp");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into ganttproadvanced/admin directory, to use to setup module.
		$this->config_page_url = array('configuration.php@ganttproadvanced');

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array('modProjet', 'modMailing');		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->conflictwith = array();	// List of modules id this module is in conflict with
		$this->phpmin = array(5,0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("ganttproadvanced@ganttproadvanced");

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array();

		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:ganttproadvanced@ganttproadvanced:$user->rights->ganttproadvanced->read:/ganttproadvanced/mynewtab1.php?id=__ID__',  	// To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:Title2:ganttproadvanced@ganttproadvanced:$user->rights->othermodule->read:/ganttproadvanced/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2
        //                              'objecttype:-tabname:NU:conditiontoremove');                                                     						// To remove an existing tab identified by code tabname
		// where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view
        $this->tabs = array();
        // $namtab = 'ganttproadvanced2';
        // $this->tabs = array(
        // 	'user:+tab_ganttproadvanced:'.$namtab.':ganttproadvanced@ganttproadvanced:(!empty($user->admin) || $user->rights->user->user->lire):/ganttproadvanced/employee/card.php?id=__ID__',
        // );

        // Dictionaries
	    if (! isset($conf->ganttproadvanced->enabled))
        {
        	$conf->ganttproadvanced=new stdClass();
        	$conf->ganttproadvanced->enabled=0;
        }
		$this->dictionaries=array();
        /* Example:
        if (! isset($conf->ganttproadvanced->enabled)) $conf->ganttproadvanced->enabled=0;	// This is to avoid warnings
        $this->dictionaries=array(
            'langs'=>'ganttproadvanced@ganttproadvanced',
            'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
            'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
            'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionary)
            'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
            'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array($conf->ganttproadvanced->enabled,$conf->ganttproadvanced->enabled,$conf->ganttproadvanced->enabled)												// Condition to show each dictionary
        );
        */

        // Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array();			// List of boxes
		// Example:
		//$this->boxes=array(array(0=>array('file'=>'myboxa.php','note'=>'','enabledbydefaulton'=>'Home'),1=>array('file'=>'myboxb.php','note'=>''),2=>array('file'=>'myboxc.php','note'=>'')););

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		// Add here list of permission defined by an id, a label, a boolean and two constant strings.


		$this->rights[$r][0] = $this->numero+$r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Show';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'lire';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;


		$this->rights[$r][0] = $this->numero+$r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'CloseProject';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'project';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'close';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		// $this->rights[$r][0] = $this->numero+$r;	// Permission id (must not be already used)
		// // $this->rights[$r][1] = 'Delete';	// Permission label
		// // $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		// // $this->rights[$r][4] = 'supprimer';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// // $r++;

		// $this->rights[$r][0] = $this->numero+$r;	// Permission id (must not be already used)
		// $this->rights[$r][1] = 'Closing';	// Permission label
		// $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		// $this->rights[$r][4] = 'ferme';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $r++;
		

		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;
		// Add here entries to declare new menus
		//
		// Example to declare a new Top Menu entry and its Left menu entry:



		// $this->menu[$r]=array(	'fk_menu'=>0,		// Put 0 if this is a single top menu or keep fk_mainmenu to give an entry on left
		// 	'type'=>'top',			                // This is a Top menu entry
		// 	'titre'=>'ganttproadvanced',
		// 	'mainmenu'=>'ganttproadvanced',
		// 	'leftmenu'=>'ganttproadvanced_left',			// This is the name of left menu for the next entries
		// 	'url'=>'ganttproadvanced/index.php',
		// 	'langs'=>'ganttproadvanced@ganttproadvanced',	       
		// 	'position'=>410,
		// 	'enabled'=>'$conf->ganttproadvanced->enabled',
		// 	'perms'=>'($user->rights->ganttproadvanced->lire || $user->admin)',			                
		// 	'target'=>'',
		// 	'user'=>2);				               
		// $r++;

		$link = "ganttproadvanced/index.php";

		dol_include_once('/ganttproadvanced/class/ganttproadvanced.class.php');
		$ganttproadvanced = new ganttproadvanced($this->db);

		if($ganttproadvanced->default_onglet == 'viewgantt' || $ganttproadvanced->default_onglet == 'byresource'){
			$link = 'ganttproadvanced/index.php';
			if($ganttproadvanced->default_onglet == 'byresource') $link .= '?viewmode=byresource';
		}else{
			$link = 'ganttproadvanced/'.$ganttproadvanced->default_onglet.'.php';
		}

		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=project',
			'type'=>'left',
			'titre'=>'ganttproadvanced',
			'leftmenu'=>'ganttproadvanced',
			'url' => $link,
			'langs'=>'ganttproadvanced@ganttproadvanced',
			'position'=>100,
			'enabled'=>'1',
			'perms'=>'$user->rights->ganttproadvanced->lire',
			'target'=>'',
			'prefix'=> '<span class="paddingrightonly fa fa-stream"></span>',
			'user'=>2);
		$r++;

		if(isset($conf->global->DOLIBARR_PLATEFORME_DEMO_MODULES)) {
			$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=project,fk_leftmenu=ganttproadvanced',
				'type'=>'left',
				'titre'=>'Configuration',
				'leftmenu'=>'configtrello',
				'url'=>'/ganttproadvanced/admin/configuration.php',
				'langs'=>'ganttproadvanced@ganttproadvanced',
				'position'=>203,
				'enabled'=>'1',
				'perms'=>'($conf->global->DOLIBARR_PLATEFORME_DEMO_MODULES)',
				'target'=>'',
				'user'=>2);
			$r++;
		}

		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=project',
			'type'=>'left',
			'titre'=>'CloseProject',
			'url'=>'/ganttproadvanced/closeproject.php',
			'langs'=>'ganttproadvanced@ganttproadvanced',
			'position'=>101,
			'enabled'=>'1',
			'perms'=>'$user->rights->ganttproadvanced->project->close',
			'prefix'=> '<span class="paddingrightonly fas fa-project-diagram"></span>',
			'target'=>'',
			'user'=>2);
		$r++;
		
		// $this->menu[$r]=array('fk_menu'=>'fk_mainmenu=ganttproadvanced',
		// 	'type'=>'left',
		// 	'titre'=>'payrolllist',
		// 	'leftmenu'=>'payrolllist',
		// 	'url'=>'/ganttproadvanced/index.php',
		// 	'langs'=>'ganttproadvanced@ganttproadvanced',
		// 	'position'=>201,
		// 	'enabled'=>'1',
		// 	'perms'=>'($user->rights->ganttproadvanced->lire || $user->admin)',
		// 	'target'=>'',
		// 	'user'=>2);
		// $r++;

		// 	$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=ganttproadvanced,fk_leftmenu=payrolllist',
		// 		'type'=>'left',
		// 		'titre'=>'listofpayroll',
		// 		'leftmenu'=>'payrolllist2',
		// 		'url'=>'/ganttproadvanced/index.php',
		// 		'langs'=>'ganttproadvanced@ganttproadvanced',
		// 		'position'=>202,
		// 		'enabled'=>'1',
		// 		'perms'=>'($user->rights->ganttproadvanced->lire || $user->admin)',
		// 		'target'=>'',
		// 		'user'=>2);
		// 	$r++;

		// 	$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=ganttproadvanced,fk_leftmenu=payrolllist2',
		// 		'type'=>'left',
		// 		'titre'=>'NewPayroll',
		// 		'url'=>'/ganttproadvanced/card.php?action=add',
		// 		'langs'=>'ganttproadvanced@ganttproadvanced',
		// 		'position'=>203,
		// 		'enabled'=>'1',
		// 		'perms'=>'($user->rights->ganttproadvanced->creer || $user->admin)',
		// 		'target'=>'',
		// 		'user'=>2);
		// 	$r++;
		
		// 	$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=ganttproadvanced,fk_leftmenu=payrolllist',
		// 		'type'=>'left',
		// 		'titre'=>'payrollrules',
		// 		'leftmenu'=>'payrolllist3',
		// 		'url'=>'/ganttproadvanced/rules/index.php',
		// 		'langs'=>'ganttproadvanced@ganttproadvanced',
		// 		'position'=>207,
		// 		'enabled'=>'1',
		// 		'perms'=>'($user->rights->ganttproadvanced->lire || $user->admin)',
		// 		'target'=>'',
		// 		'user'=>2);
		// 	$r++;
		
		// 	$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=ganttproadvanced,fk_leftmenu=payrolllist3',
		// 		'type'=>'left',
		// 		'titre'=>'NewPayrollRule2',
		// 		'url'=>'/ganttproadvanced/rules/card.php?action=add',
		// 		'langs'=>'ganttproadvanced@ganttproadvanced',
		// 		'position'=>208,
		// 		'enabled'=>'1',
		// 		'perms'=>'($user->rights->ganttproadvanced->creer || $user->admin)',
		// 		'target'=>'',
		// 		'user'=>2);
		// 	$r++;
		
		// 	// $this->menu[$r]=array('fk_menu'=>'fk_mainmenu=ganttproadvanced,fk_leftmenu=payrolllist3',
		// 	// 	'type'=>'left',
		// 	// 	'titre'=>'PayrollRuleParentElem',
		// 	// 	'url'=>'/ganttproadvanced/rules/title/index.php',
		// 	// 	'langs'=>'ganttproadvanced@ganttproadvanced',
		// 	// 	'position'=>209,
		// 	// 	'enabled'=>'1',
		// 	// 	'perms'=>'($user->rights->ganttproadvanced->creer || $user->admin)',
		// 	// 	'target'=>'',
		// 	// 	'user'=>2);
		// 	// $r++;
		
		// 	$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=ganttproadvanced,fk_leftmenu=payrolllist',
		// 		'type'=>'left',
		// 		'titre'=>'Configuration',
		// 		'leftmenu'=>'Configuration',
		// 		'url'=>'ganttproadvanced/admin/ganttproadvanced_setup.php',
		// 		'langs'=>'ganttproadvanced@ganttproadvanced',
		// 		'position'=>211,
		// 		'enabled'=>'1',
		// 		'perms'=>'($user->rights->ganttproadvanced->lire || $user->admin)',
		// 		'target'=>'',
		// 		'user'=>2);
		// 	$r++;

		// Exports
		$r=1;
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf, $langs;
		$langs->load('ganttproadvanced@ganttproadvanced');
		$sqlm = array();

		dol_include_once('/ganttproadvanced/class/ganttproadvanced.class.php');
		$archdol = new ganttproadvanced($this->db);

		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);

		$archdol->initTheModuleganttproadvanced($this->version);

		return $this->_init($sqlm, $options);
	}

	/**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function remove($options='')
	{
		$sql = array();
		$sql = array(
			'DELETE FROM `'.MAIN_DB_PREFIX.'extrafields` WHERE `name` like "%ganttproadvanced%"',
		);
		return $this->_remove($sql, $options);
	}

}
