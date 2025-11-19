<?php
/* Copyright (C) 2004-2018	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019	Nicolas ZABOURI				<info@inovea-conseil.com>
 * Copyright (C) 2019-2024	Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2025		Grégory Maza             <gregory.maza@atm-consulting.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   clichaumeil     Module Clichaumeil
 *  \brief      Clichaumeil module descriptor.
 *
 *  \file       htdocs/clichaumeil/core/modules/modClichaumeil.class.php
 *  \ingroup    clichaumeil
 *  \brief      Description and activation file for module Clichaumeil
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module Clichaumeil
 */
class modClichaumeil extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 104321; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'clichaumeil';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "other";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleClichaumeilName' not found (Clichaumeil is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// DESCRIPTION_FLAG
		// Module description, used if translation string 'ModuleClichaumeilDesc' not found (Clichaumeil is name of module).
		$this->description = "ClichaumeilDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "ClichaumeilDescription";

		// Author
		$this->editor_name = 'ATM Consulting';
		$this->editor_url = '';		// Must be an external online web site
		$this->editor_squarred_logo = '';					// Must be image filename into the module/img directory followed with @modulename. Example: 'myimage.png@clichaumeil'

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated', 'experimental_deprecated' or a version string like 'x.y.z'
		$this->version = '1.3.0';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where CLICHAUMEIL is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'fa-money-bill';

		$this->const = array();

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 1,
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
			'models' => 1,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
				//    '/clichaumeil/css/clichaumeil.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/clichaumeil/js/clichaumeil.js.php',
			),
		// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
		/* BEGIN MODULEBUILDER HOOKSCONTEXTS */
		'hooks' => array(
			'thirdpartycard',
			'globalcard',
			'projectthirdparty',
			'bomcard',
			'productcard',
			'imports'
		),
		/* END MODULEBUILDER HOOKSCONTEXTS */
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
			// Set this to 1 if the module provides a website template into doctemplates/websites/website_template-mytemplate
			'websitetemplates' => 0,
			// Set this to 1 if the module provides a captcha driver
			'captcha' => 0
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/clichaumeil/temp","/clichaumeil/subdir");
		$this->dirs = array("/clichaumeil/temp");

		// Config pages. Put here list of php page, stored into clichaumeil/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@clichaumeil");

		// Dependencies
		// A condition to hide module
		$this->hidden = getDolGlobalInt('MODULE_CLICHAUMEIL_DISABLED'); // A condition to disable module;
		// List of module class names that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR')...)
		$this->depends = array();
		// List of module class names to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->requiredby = array();
		// List of module class names this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array();

		// The language file dedicated to your module
		$this->langfiles = array("clichaumeil@clichaumeil");

		// Prerequisites
		$this->phpmin = array(7, 1); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(19, -3); // Minimum version of Dolibarr required by module
		$this->need_javascript_ajax = 0;

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)

		$this->const = array();
		$this->rfa_tab_added = false;


		if (!isModEnabled("clichaumeil")) {
			$conf->clichaumeil = new stdClass();
			$conf->clichaumeil->enabled = 0;
		}

		// Array to add new pages in new tabs
		/* BEGIN MODULEBUILDER TABS */
		$this->tabs = array();

		/* BEGIN MODULEBUILDER DICTIONARIES */
		$this->dictionaries = array();
		/* END MODULEBUILDER DICTIONARIES */

		// Boxes/Widgets
		// Add here list of php file(s) stored in clichaumeil/core/boxes that contains a class to show a widget.
		/* BEGIN MODULEBUILDER WIDGETS */
		$this->boxes = array();
		/* END MODULEBUILDER WIDGETS */

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		/* BEGIN MODULEBUILDER CRON */
		$this->cronjobs = array(
			0 => array(
				'label' => $langs->trans('CliChaumeilAutomaticRenewalContract'),
				'jobtype' => 'method',
				'class' => '/clichaumeil/class/cronupdatecontractrevision.class.php',
				'objectname' => 'CronJobUpdateContractRevision',
				'method' => 'run',
				'parameters' => '',
				'comment' => $langs->trans('CliChaumeilApplyRenewalRate'),
				'frequency' => 24,
				'unitfrequency' => 3600,
				'status' => 0, // 0 for disabled by default, 1 for enabled
				'priority' => 50,
			)
		);

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 0 + 1);
		$this->rights[$r][1] = 'ReadRightsChaumeilRfa';
		$this->rights[$r][4] = 'chaumeilrfa';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 1 + 1);
		$this->rights[$r][1] = 'CreateUpadteRightsChaumeilRfa';
		$this->rights[$r][4] = 'chaumeilrfa';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 2 + 1);
		$this->rights[$r][1] = 'DeleteRightsChaumeilRfa';
		$this->rights[$r][4] = 'chaumeilrfa';
		$this->rights[$r][5] = 'delete';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 3 + 1);
		$this->rights[$r][1] = 'ReadProductCostComposition';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'product';
		$this->rights[$r][5] = 'read_cost_composition';
		$r++;

		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER LEFTMENU CHAUMEILRFA */
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=companies',
			'type' => 'left',
			'titre' => 'ChaumeilRfa',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu' => 'companies',
			'leftmenu' => 'chaumeilrfa',
			'url' => '/clichaumeil/chaumeilrfa_list_fourn.php',
			'langs' => 'clichaumeil@clichaumeil',
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("clichaumeil")',
			'perms' => '$user->hasRight("clichaumeil", "chaumeilrfa", "read") && $user->hasRight("fournisseur", "facture", "lire")',
			'target' => '',
			'user' => 2,
			'object' => 'ChaumeilRfa'
		);
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=clichaumeil,fk_leftmenu=chaumeilrfa',
			'type' => 'left',
			'titre' => 'List ChaumeilRfa',
			'mainmenu' => 'clichaumeil',
			'leftmenu' => 'clichaumeil_chaumeilrfa_list',
			'url' => '/clichaumeil/chaumeilrfa_list.php',
			'langs' => 'clichaumeil@clichaumeil',
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("clichaumeil")',
			'perms' => '$user->hasRight("clichaumeil", "chaumeilrfa", "read")',
			'target' => '',
			'user' => 2,
			'object' => 'ChaumeilRfa'
		);
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=clichaumeil,fk_leftmenu=chaumeilrfa',
			'type' => 'left',
			'titre' => 'New ChaumeilRfa',
			'mainmenu' => 'clichaumeil',
			'leftmenu' => 'clichaumeil_chaumeilrfa_new',
			'url' => '/clichaumeil/chaumeilrfa_card.php?action=create',
			'langs' => 'clichaumeil@clichaumeil',
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("clichaumeil")',
			'perms' => '$user->hasRight("clichaumeil", "chaumeilrfa", "write")',
			'target' => '',
			'user' => 2,
			'object' => 'ChaumeilRfa'
		);

		// Exports profiles provided by this module
		$r = 0;
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT */

		$langs->load("clichaumeil@clichaumeil");
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'ChaumeilRfaLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r] = $this->picto;
		$keyforclass = 'ChaumeilRfa'; $keyforclassfile='/clichaumeil/class/chaumeilrfa.class.php'; $keyforelement='chaumeilrfa@clichaumeil';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='chaumeilrfa'; $keyforaliasextra='extra'; $keyforelement='chaumeilrfa@clichaumeil';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.$db->prefix().'clichaumeil_chaumeilrfa as t';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$r = 0;

	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int<-1,1>          	1 if OK, <=0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		// Create tables of module at module activation
		//$result = $this->_load_tables('/install/mysql/', 'clichaumeil');
		$result = $this->_load_tables('/clichaumeil/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
		$extrafields->addExtraField('clichaumeilreviewrate', 'CliChaumeilReviewRate', 'double', 100, '24,2', 'contrat', 0, 0, '', array ( 'options' => array ( '' => NULL, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array ( 'css' => '', 'cssview' => '', 'csslist' => ''));
		$extrafields->addExtraField('clichaumeil_reviewdate', 'CliChaumeilReviewDate', 'date', 100, '', 'contratdet', 0, 0, '', array ( 'options' => array ( '' => NULL, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array ( 'css' => '', 'cssview' => '', 'csslist' => '', ));
		$extrafields->addExtraField('clichaumeil_height', 'CliChaumeilHeight', 'double', 100, '24,2', 'propaldet', 0, 0, '', array ( 'options' => array ( '' => NULL, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array ( 'css' => '', 'cssview' => '', 'csslist' => ''));
		$extrafields->addExtraField('clichaumeil_length', 'CliChaumeilLength', 'double', 100, '24,2', 'propaldet', 0, 0, '', array ( 'options' => array ( '' => NULL, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array ( 'css' => '', 'cssview' => '', 'csslist' => ''));
		$extrafields->addExtraField('clichaumeil_height', 'CliChaumeilHeight', 'double', 100, '24,2', 'commandedet', 0, 0, '', array ( 'options' => array ( '' => NULL, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array ( 'css' => '', 'cssview' => '', 'csslist' => ''));
		$extrafields->addExtraField('clichaumeil_length', 'CliChaumeilLength', 'double', 100, '24,2', 'commandedet', 0, 0, '', array ( 'options' => array ( '' => NULL, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array ( 'css' => '', 'cssview' => '', 'csslist' => ''));
		$extrafields->addExtraField('clichaumeil_ref_required', 'CliChaumeilRefRequired', 'boolean', 100, '', 'thirdparty', 0, 0, '', array ( 'options' => array ( '' => NULL, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array ( 'css' => '', 'cssview' => '', 'csslist' => ''));
		$extrafields->addExtraField('clichaumeil_generalexpenses', 'CliChaumeilGeneralExpenses', 'double', 100, '24,2', 'bom_bom', 0, 0, '', array ( 'options' => array ( '' => NULL, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array ( 'css' => '', 'cssview' => '', 'csslist' => '', ));

		$permsCostComposition = '$user->rights->clichaumeil->product->read_cost_composition';
		$enabledSimpleProduct = '(!isset($object) || !property_exists($object, "type") || (int) $object->type === 0)';
		$extrafields->addExtraField('prc_separator', 'CliChaumeilCostBreakdown', 'separate', 100, '', 'product', 0, 0, '', '', 1, $permsCostComposition, '1', '', '', 0, 'clichaumeil@clichaumeil', $enabledSimpleProduct, 0, '0', array());
		$extrafields->addExtraField('pa_support', 'CliChaumeilPaSupport', 'double', 101, '24,4', 'product', 0, 0, '', '', 1, $permsCostComposition, '1', '', '', 0, 'clichaumeil@clichaumeil', $enabledSimpleProduct, 0, '0', array());
		$extrafields->addExtraField('pa_sav', 'CliChaumeilPaSav', 'double', 102, '24,4', 'product', 0, 0, '', '', 1, $permsCostComposition, '1', '', '', 0, 'clichaumeil@clichaumeil', $enabledSimpleProduct, 0, '0', array());
		$extrafields->addExtraField('pa_machine', 'CliChaumeilPaMachine', 'double', 103, '24,4', 'product', 0, 0, '', '', 1, $permsCostComposition, '1', '', '', 0, 'clichaumeil@clichaumeil', $enabledSimpleProduct, 0, '0', array());
		$extrafields->addExtraField('pa_encre', 'CliChaumeilPaInk', 'double', 104, '24,4', 'product', 0, 0, '', '', 1, $permsCostComposition, '1', '', '', 0, 'clichaumeil@clichaumeil', $enabledSimpleProduct, 0, '0', array());
		$extrafields->addExtraField('pa_mo', 'CliChaumeilPaLabor', 'double', 105, '24,4', 'product', 0, 0, '', '', 1, $permsCostComposition, '1', '', '', 0, 'clichaumeil@clichaumeil', $enabledSimpleProduct, 0, '0', array());
		$extrafields->addExtraField('fg_percent', 'CliChaumeilFgPercent', 'double', 106, '24,4', 'product', 0, 1, '', '', 1, $permsCostComposition, '1', '', '', 0, 'clichaumeil@clichaumeil', $enabledSimpleProduct, 0, '0', array());
		$extrafields->addExtraField('pa_fg', 'CliChaumeilPaFg', 'double', 107, '24,4', 'product', 0, 0, '', '', 0, $permsCostComposition, '1', '', '', 0, 'clichaumeil@clichaumeil', $enabledSimpleProduct, 0, '0', array());

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('clichaumeil');
		$myTmpObjects = array();
		$myTmpObjects['ChaumeilRfa'] = array('includerefgeneration' => 0, 'includedocgeneration' => 0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_chaumeilrfas.odt';
				$dirodt = DOL_DATA_ROOT.($conf->entity > 1 ? '/'.$conf->entity : '').'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_chaumeilrfas.odt';

				if (file_exists($src) && !file_exists($dest)) {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					dol_mkdir($dirodt);
					$result = dol_copy($src, $dest, '0', 0);
					if ($result < 0) {
						$langs->load("errors");
						$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
						return 0;
					}
				}

				$sql = array_merge($sql, array(
					"DELETE FROM ".$this->db->prefix()."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".$this->db->prefix()."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")",
					"DELETE FROM ".$this->db->prefix()."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".$this->db->prefix()."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")"
				));
			}
		}

		return $this->_init($sql, $options);
	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted
	 *
	 *	@param	string		$options	Options when enabling module ('', 'noboxes')
	 *	@return	int<-1,1>				1 if OK, <=0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
