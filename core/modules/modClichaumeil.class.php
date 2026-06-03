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
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';
include_once __DIR__ . '/../../class/CliChaumeilProductCost.class.php';
include_once __DIR__ . '/../../class/CliChaumeilCommissionConfig.class.php';
include_once __DIR__ . '/../../class/Service/CliChaumeilCommissionDictionarySeeder.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';


/**
 *  Description and activation class for module Clichaumeil
 */
class modClichaumeil extends DolibarrModules
{
	/**
	 * @var string
	 */
	private const DEFAULT_RFA_TEMPLATE_CODE = 'CLICHAUMEIL_RFA_NEGOTIATION';

	/**
	 * @var string
	 */
	private const DEFAULT_RFA_TEMPLATE_TYPE = 'thirdparty';

	/**
	 * @var string
	 */
	private const DEFAULT_RFA_LIST_URL = '/clichaumeil/chaumeilrfa_list_fourn.php?yearid=__YEAR__';

	/**
	 * @var string
	 */
	private const DEFAULT_RFA_CRON_PARAMETERS = ',CLICHAUMEIL_RFA_NEGOTIATION';

	/**
	 * @var string
	 */
	private const DEFAULT_RFA_SUMMARY_CRON_PARAMETERS = '';

	/**
	 * Default parameters for the ANTALIS price sync cron (error-report recipients).
	 */
	private const DEFAULT_ANTALIS_PRICE_CRON_PARAMETERS = '';

	/**
	 * Default proposal line extrafield key.
	 *
	 * @var string
	 */
	private const DEFAULT_PROPAL_LINE_EXTRAFIELD = 'clichaumeil_default_inserted';

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
		$this->version = '1.19.0';

		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where CLICHAUMEIL is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

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
			'js' => array(),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			/* BEGIN MODULEBUILDER HOOKSCONTEXTS */
			'hooks' => array(
				'main',
				'thirdpartycard',
				'globalcard',
				'projectthirdparty',
				'bomcard',
				'externalaccesssetup',
				'externalaccess',
				'propalcard',
				'propallist',
				'massactionshowlines',
				'massactionsplitlines',
				'productcard',
				'pricesuppliercard',
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
		$this->phpmin = array(7, 4); // Minimum version of PHP required by module
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
		$this->dictionaries = array(
			'langs' => 'clichaumeil@clichaumeil',
			'tabname' => array(CliChaumeilCommissionConfig::DICTIONARY_TABLE),
			'tablib' => array('CliChaumeilCommissionDictionary'),
			'tabsql' => array('SELECT f.rowid as rowid, f.code, f.role_code, f.customer_tag, f.label, f.coefficient, f.active, f.entity FROM '.$this->db->prefix().'c_clichaumeil_commission_coeff as f WHERE f.entity = '.((int) $conf->entity)),
			'tabsqlsort' => array('role_code ASC, customer_tag ASC'),
			'tabfield' => array('code,role_code,customer_tag,label,coefficient'),
			'tabfieldvalue' => array('code,role_code,customer_tag,label,coefficient'),
			'tabfieldinsert' => array('code,role_code,customer_tag,label,coefficient,entity'),
			'tabrowid' => array('rowid'),
			'tabcond' => array($conf->clichaumeil->enabled),
			'tabhelp' => array(array(
				'role_code' => $langs->trans('CliChaumeilCommissionDictionaryRoleCodeHelp'),
				'customer_tag' => $langs->trans('CliChaumeilCommissionDictionaryCustomerTagHelp'),
			)),
		);
		/* END MODULEBUILDER DICTIONARIES */

		// Boxes/Widgets
		// Add here list of php file(s) stored in clichaumeil/core/boxes that contains a class to show a widget.
		/* BEGIN MODULEBUILDER WIDGETS */
		$this->boxes = array();
		/* END MODULEBUILDER WIDGETS */

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		/* BEGIN MODULEBUILDER CRON */
		$now = dol_now();
		$cronStart = dol_mktime(1, 0, 0, (int) dol_print_date($now, '%m'), (int) dol_print_date($now, '%d'), (int) dol_print_date($now, '%Y'));
		if ($cronStart <= $now) {
			$cronStart = dol_time_plus_duree($cronStart, 1, 'd');
		}
		$rfaCronStart = $this->getNextRfaNegotiationStartTimestamp($now);

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
			,
			1 => array(
				'label' => $langs->trans('CliChaumeilCronCustomerSegmentation'),
				'jobtype' => 'method',
				'class' => '/clichaumeil/class/cronupdatecustomercategories.class.php',
				'objectname' => 'CronJobUpdateCustomerCategories',
				'method' => 'run',
				'parameters' => '',
				'comment' => $langs->trans('CliChaumeilCronCustomerSegmentationDesc'),
				'frequency' => 1,
				'unitfrequency' => 86400,
				'datestart' => $cronStart,
				'datenextrun' => $cronStart,
				'status' => 0, // 0 for disabled by default, 1 for enabled
				'priority' => 50,
			),
			2 => array(
				'label' => $langs->trans('CliChaumeil_RfaReminderCronLabel'),
				'jobtype' => 'method',
				'class' => '/clichaumeil/class/Cron/RfaNegotiationReminderCronJob.php',
				'objectname' => 'RfaNegotiationReminderCronJob',
				'method' => 'run',
				'parameters' => self::DEFAULT_RFA_CRON_PARAMETERS,
				'comment' => $langs->trans('CliChaumeil_RfaReminderCronDescription'),
				'frequency' => 12,
				'unitfrequency' => 2678400,
				'datestart' => $rfaCronStart,
				'datenextrun' => $rfaCronStart,
				'status' => 0,
				'priority' => 50,
			),
			3 => array(
				'label' => $langs->trans('CliChaumeil_RfaSummaryCronLabel'),
				'jobtype' => 'method',
				'class' => '/clichaumeil/class/Cron/RfaSummaryRebuildCronJob.php',
				'objectname' => 'RfaSummaryRebuildCronJob',
				'method' => 'run',
				'parameters' => self::DEFAULT_RFA_SUMMARY_CRON_PARAMETERS,
				'comment' => $langs->trans('CliChaumeil_RfaSummaryCronDescription'),
				'frequency' => 1,
				'unitfrequency' => 86400,
				'datestart' => $cronStart,
				'datenextrun' => $cronStart,
				'status' => 0,
				'priority' => 50,
			),
			4 => array(
				'label' => $langs->trans('CliChaumeil_AntalisPriceSyncCronLabel'),
				'jobtype' => 'method',
				'class' => '/clichaumeil/class/SupplierPriceSync/Cron/AntalisSupplierPriceSyncCronJob.php',
				'objectname' => 'AntalisSupplierPriceSyncCronJob',
				'method' => 'run',
				'parameters' => self::DEFAULT_ANTALIS_PRICE_CRON_PARAMETERS,
				'comment' => $langs->trans('CliChaumeil_AntalisPriceSyncCronComment'),
				'frequency' => 1,
				'unitfrequency' => 86400,
				'datestart' => $cronStart,
				'datenextrun' => $cronStart,
				'status' => 0,
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
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'ReadSupplierProposal';
		$this->rights[$r][4] = 'SupplierProposal';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 3 + 1);
		$this->rights[$r][1] = 'ReadProductCostComposition';
		$this->rights[$r][4] = 'product';
		$this->rights[$r][5] = 'read_cost_composition';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 4 + 1);
		$this->rights[$r][1] = 'CLICHAUMEIL_DEFAULT_PROPAL_LINE_MANAGE_RIGHT';
		$this->rights[$r][4] = 'propal_default_line';
		$this->rights[$r][5] = 'manage';
		$r++;
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;

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
		$this->export_code[$r] = $this->rights_class . '_' . $r;
		$this->export_label[$r] = 'ChaumeilRfaLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r] = $this->picto;
		$keyforclass = 'ChaumeilRfa';
		$keyforclassfile = '/clichaumeil/class/chaumeilrfa.class.php';
		$keyforelement = 'chaumeilrfa@clichaumeil';
		include DOL_DOCUMENT_ROOT . '/core/commonfieldsinexport.inc.php';
		$keyforselect = 'chaumeilrfa';
		$keyforaliasextra = 'extra';
		$keyforelement = 'chaumeilrfa@clichaumeil';
		include DOL_DOCUMENT_ROOT . '/core/extrafieldsinexport.inc.php';
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r] = ' FROM ' . $db->prefix() . 'clichaumeil_chaumeilrfa as t';
		$this->export_sql_end[$r] .= ' WHERE 1 = 1';
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

		$result = $this->ensureRfaRootAggregationIndex();
		if ($result < 0) {
			return -1;
		}

		// Create extrafields during init
		include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
		$extrafields->addExtraField('clichaumeilreviewrate', 'CliChaumeilReviewRate', 'double', 100, '24,2', 'contrat', 0, 0, '', array('options' => array('' => null, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array('css' => '', 'cssview' => '', 'csslist' => ''));
		$extrafields->addExtraField('clichaumeil_reviewdate', 'CliChaumeilReviewDate', 'date', 100, '', 'contratdet', 0, 0, '', array('options' => array('' => null, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array('css' => '', 'cssview' => '', 'csslist' => '', ));
		$extrafields->addExtraField('clichaumeil_height', 'CliChaumeilHeight', 'double', 100, '24,2', 'propaldet', 0, 0, '', array('options' => array('' => null, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array('css' => '', 'cssview' => '', 'csslist' => ''));
		$extrafields->addExtraField('clichaumeil_length', 'CliChaumeilLength', 'double', 100, '24,2', 'propaldet', 0, 0, '', array('options' => array('' => null, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array('css' => '', 'cssview' => '', 'csslist' => ''));
		$extrafields->addExtraField('clichaumeil_height', 'CliChaumeilHeight', 'double', 100, '24,2', 'commandedet', 0, 0, '', array('options' => array('' => null, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array('css' => '', 'cssview' => '', 'csslist' => ''));
		$extrafields->addExtraField('clichaumeil_length', 'CliChaumeilLength', 'double', 100, '24,2', 'commandedet', 0, 0, '', array('options' => array('' => null, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array('css' => '', 'cssview' => '', 'csslist' => ''));
		$extrafields->addExtraField('clichaumeil_ref_required', 'CliChaumeilRefRequired', 'boolean', 100, '', 'thirdparty', 0, 0, '', array('options' => array('' => null, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array('css' => '', 'cssview' => '', 'csslist' => ''));
		$extrafields->addExtraField('clichaumeil_generalexpenses', 'CliChaumeilGeneralExpenses', 'double', 100, '24,2', 'bom_bom', 0, 0, '', array('options' => array('' => null, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array('css' => '', 'cssview' => '', 'csslist' => '', ));
		$extrafields->addExtraField('clichaumeil_units', 'CliChaumeilUnits', 'sellist', 210, '', 'propaldet', 0, 0, '', ['options' => ["c_units:short_label:rowid::((unit_type:=:'surface') AND (active:=:1))" => null]], 1, '', 1, '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")');
		$extrafields->addExtraField('clichaumeil_units', 'CliChaumeilUnits', 'sellist', 210, '', 'commandedet', 0, 0, '', ['options' => ["c_units:short_label:rowid::((unit_type:=:'surface') AND (active:=:1))" => null]], 1, '', 1, '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")');

		$param = array(
			'options' => array(
				'CLICHAUMEIL_PENDING_FILE' => $langs->trans("CLICHAUMEIL_PENDING_FILE"),
				'CLICHAUMEIL_FILE_RECEIVED' => $langs->trans("CLICHAUMEIL_FILE_RECEIVED"),
			),
		);

		$extrafields->addExtraField('clichaumeil_supplierstatut', 'CliChaumeilSupplierStatut', 'select', 100, '24', 'supplier_proposal', 0, 0, 'CLICHAUMEIL_PENDING_FILE', $param, 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array('css' => '', 'cssview' => '', 'csslist' => '', ));
		$extrafields->addExtraField('clichaumeil_supplierresponsedate', 'CliChaumeilSupplierResponseDate', 'datetime', 101, '', 'supplier_proposal', 0, 0, '', array('options' => array('' => null, ), ), 1, '', '1', '', '', 0, 'clichaumeil@clichaumeil', 'isModEnabled("clichaumeil")', 0, '0', array('css' => '', 'cssview' => '', 'csslist' => '', ));

		$permsCostComposition = '$user->hasRight(\'clichaumeil\',\'product\',\'read_cost_composition\') ? 1:0';
		$permsPaFg = '$user->hasRight(\'clichaumeil\',\'product\',\'read_cost_composition\') ? 5:0';

		$extrafields->fetch_name_optionals_label('product', true);
		$this->ensureProductExtrafield($extrafields, 'clichaumeil_prc_separator', 'CliChaumeilCostBreakdown', 'separate', 100, '', 0, 0, '', array('options' => array('1' => null)), 1, $permsCostComposition, $permsCostComposition, '', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array());
		$this->ensureProductExtrafield($extrafields, 'clichaumeil_pa_support', 'CliChaumeilPaSupport', 'double', 101, '24,4', 0, 0, '', '', 1, $permsCostComposition, $permsCostComposition, 'CLICHAUMEIL_HELP_PA_SUPPORT', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array());
		$this->ensureProductExtrafield($extrafields, 'clichaumeil_pa_sav', 'CliChaumeilPaSav', 'double', 102, '24,4', 0, 0, '', '', 1, $permsCostComposition, $permsCostComposition, 'CLICHAUMEIL_HELP_PA_SAV', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array());
		$this->ensureProductExtrafield($extrafields, 'clichaumeil_pa_machine', 'CliChaumeilPaMachine', 'double', 103, '24,4', 0, 0, '', '', 1, $permsCostComposition, $permsCostComposition, 'CLICHAUMEIL_HELP_PA_MACHINE', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array());
		$this->ensureProductExtrafield($extrafields, 'clichaumeil_pa_encre', 'CliChaumeilPaInk', 'double', 104, '24,4', 0, 0, '', '', 1, $permsCostComposition, $permsCostComposition, 'CLICHAUMEIL_HELP_PA_INK', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array());
		$this->ensureProductExtrafield($extrafields, 'clichaumeil_pa_mo', 'CliChaumeilPaLabor', 'double', 105, '24,4', 0, 0, '', '', 1, $permsCostComposition, $permsCostComposition, 'CLICHAUMEIL_HELP_PA_LABOR', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array());
		$this->ensureProductExtrafield($extrafields, CliChaumeilProductCostCalculator::PACKAGING_PERCENT_FIELD, 'CLICHAUMEIL_CONDITIONNEMENT_PERCENT', 'double', 106, '24,4', 0, 0, '', '', 1, $permsCostComposition, $permsCostComposition, 'CLICHAUMEIL_HELP_PACKAGING_PERCENT', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array());
		$this->ensureProductExtrafield($extrafields, CliChaumeilProductCostCalculator::TRANSPORT_PERCENT_FIELD, 'CLICHAUMEIL_TRANSPORT_PERCENT', 'double', 107, '24,4', 0, 0, '', '', 1, $permsCostComposition, $permsCostComposition, 'CLICHAUMEIL_HELP_TRANSPORT_PERCENT', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array());
		$this->ensureProductExtrafield($extrafields, CliChaumeilProductCostCalculator::FILE_FEE_PERCENT_FIELD, 'CLICHAUMEIL_TAUX_FRAIS_DOSSIER', 'double', 108, '24,4', 0, 0, '', '', 1, $permsCostComposition, $permsCostComposition, 'CLICHAUMEIL_HELP_TAUX_FRAIS_DOSSIER', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array());
		$this->ensureProductExtrafield($extrafields, CliChaumeilProductCostCalculator::FILE_FEE_AMOUNT_FIELD, 'CLICHAUMEIL_MT_FRAIS_DOSSIER', 'double', 109, '24,4', 0, 0, '', '', 0, $permsCostComposition, $permsCostComposition, 'CLICHAUMEIL_HELP_MT_FRAIS_DOSSIER', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array());
		$this->ensureProductExtrafield($extrafields, 'clichaumeil_fg_percent', 'CliChaumeilFgPercent', 'double', 110, '24,4', 0, 0, '', '', 1, $permsCostComposition, $permsCostComposition, 'CLICHAUMEIL_HELP_FG_PERCENT', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array());
		$this->ensureProductExtrafield($extrafields, 'clichaumeil_pa_fg', 'CliChaumeilPaFg', 'double', 111, '24,4', 0, 0, '', '', 0, $permsPaFg, $permsPaFg, 'CLICHAUMEIL_HELP_PA_FG', '', 0, 'clichaumeil@clichaumeil', 1, 0, '0', array());
		$this->ensurePropalDefaultLineExtrafield($extrafields);

		if (!getDolGlobalInt('CLICHAUMEIL_DEFAULT_OVERHEAD_RATE')) {
			dolibarr_set_const($this->db, 'CLICHAUMEIL_DEFAULT_OVERHEAD_RATE', CliChaumeilProductCostCalculator::DEFAULT_RATE_VALUE, 'chaine', 0, '', $conf->entity);
		}

		if ($this->initCommissionConfiguration() < 0) {
			return -1;
		}
		try {
			$this->ensureDefaultRfaEmailTemplate();
		} catch (Throwable $exception) {
			$this->error = $exception->getMessage();
			dol_syslog(__METHOD__ . ' failed to initialize default RFA email template: ' . $this->error, LOG_ERR);
			return -1;
		}

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('clichaumeil');
		$myTmpObjects = array();
		$myTmpObjects['ChaumeilRfa'] = array('includerefgeneration' => 0, 'includedocgeneration' => 0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT . '/install/doctemplates/' . $moduledir . '/template_chaumeilrfas.odt';
				$dirodt = DOL_DATA_ROOT . ($conf->entity > 1 ? '/' . $conf->entity : '') . '/doctemplates/' . $moduledir;
				$dest = $dirodt . '/template_chaumeilrfas.odt';

				if (file_exists($src) && !file_exists($dest)) {
					require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
					dol_mkdir($dirodt);
					$result = dol_copy($src, $dest, '0', 0);
					if ($result < 0) {
						$langs->load("errors");
						$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
						return 0;
					}
				}

				$sql = array_merge($sql, array(
					"DELETE FROM " . $this->db->prefix() . "document_model WHERE nom = 'standard_" . strtolower($myTmpObjectKey) . "' AND type = '" . $this->db->escape(strtolower($myTmpObjectKey)) . "' AND entity = " . ((int) $conf->entity),
					"INSERT INTO " . $this->db->prefix() . "document_model (nom, type, entity) VALUES('standard_" . strtolower($myTmpObjectKey) . "', '" . $this->db->escape(strtolower($myTmpObjectKey)) . "', " . ((int) $conf->entity) . ")",
					"DELETE FROM " . $this->db->prefix() . "document_model WHERE nom = 'generic_" . strtolower($myTmpObjectKey) . "_odt' AND type = '" . $this->db->escape(strtolower($myTmpObjectKey)) . "' AND entity = " . ((int) $conf->entity),
					"INSERT INTO " . $this->db->prefix() . "document_model (nom, type, entity) VALUES('generic_" . strtolower($myTmpObjectKey) . "_odt', '" . $this->db->escape(strtolower($myTmpObjectKey)) . "', " . ((int) $conf->entity) . ")"
				));
			}
		}

		$result = $this->_init($sql, $options);

		$this->deduplicateAntalisPriceCron();

		return $result;
	}

	/**
	 * Remove duplicate ANTALIS price sync cron jobs for the current entity.
	 *
	 * Only the ANTALIS cron is de-duplicated (the oldest row is kept); other
	 * historical cron jobs of the module are left untouched.
	 *
	 * @return void
	 */
	private function deduplicateAntalisPriceCron(): void
	{
		global $conf;

		$sql = "SELECT MIN(rowid) as keptid FROM " . $this->db->prefix() . "cronjob";
		$sql .= " WHERE objectname = 'AntalisSupplierPriceSyncCronJob'";
		$sql .= " AND entity = " . ((int) $conf->entity);

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog('modClichaumeil::deduplicateAntalisPriceCron ' . $this->db->lasterror(), LOG_ERR);

			return;
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);

		if (!$obj || empty($obj->keptid)) {
			return;
		}

		$sqlDelete = "DELETE FROM " . $this->db->prefix() . "cronjob";
		$sqlDelete .= " WHERE objectname = 'AntalisSupplierPriceSyncCronJob'";
		$sqlDelete .= " AND entity = " . ((int) $conf->entity);
		$sqlDelete .= " AND rowid <> " . ((int) $obj->keptid);

		if (!$this->db->query($sqlDelete)) {
			dol_syslog('modClichaumeil::deduplicateAntalisPriceCron delete ' . $this->db->lasterror(), LOG_ERR);
		}
	}

	/**
	 * Ensure the index used by the aggregated RFA list exists.
	 *
	 * @return int<-1,1> 1 on success, -1 on failure.
	 */
	private function ensureRfaRootAggregationIndex(): int
	{
		$indexName = 'idx_clichaumeil_chaumeilrfa_soc_year_palier';
		$tableName = $this->db->prefix().'clichaumeil_chaumeilrfa';
		$sql = $this->getIndexExistenceSql($tableName, $indexName);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' unable to inspect RFA index: '.$this->db->lasterror(), LOG_ERR);
			$this->error = $this->db->lasterror();
			return -1;
		}

		$indexExists = ($this->db->num_rows($resql) > 0);
		$this->db->free($resql);

		if ($indexExists) {
			return 1;
		}

		$sql = 'CREATE INDEX '.$indexName.' ON '.$tableName.' (fk_soc, datestart, dateend, palier)';
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' unable to create RFA index: '.$this->db->lasterror(), LOG_ERR);
			$this->error = $this->db->lasterror();
			return -1;
		}

		return 1;
	}

	/**
	 * Build a DB-specific SQL query to inspect index existence.
	 *
	 * @param string $tableName Full SQL table name with prefix.
	 * @param string $indexName Index name.
	 * @return string
	 */
	private function getIndexExistenceSql(string $tableName, string $indexName): string
	{
		if ($this->db->type === 'pgsql') {
			$sql = "SELECT indexname";
			$sql .= " FROM pg_indexes";
			$sql .= " WHERE schemaname = 'public'";
			$sql .= " AND tablename = '".$this->db->escape($tableName)."'";
			$sql .= " AND indexname = '".$this->db->escape($indexName)."'";

			return $sql;
		}

		$sql = 'SELECT index_name';
		$sql .= ' FROM information_schema.statistics';
		$sql .= " WHERE table_schema = DATABASE()";
		$sql .= " AND table_name = '".$this->db->escape($tableName)."'";
		$sql .= " AND index_name = '".$this->db->escape($indexName)."'";

		return $sql;
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

	/**
	 * Initialize commission configuration (constants and categories) during module activation.
	 *
	 * @return int<-1,1> 1 on success, -1 on failure.
	 */
	private function initCommissionConfiguration(): int
	{
		global $conf, $langs, $user;

		$langs->loadLangs(array('clichaumeil@clichaumeil'));
		$commissionDictionaryMigration = new CliChaumeilCommissionDictionarySeeder($this->db, (int) $conf->entity);
		if ($commissionDictionaryMigration->migrate() < 0) {
			$this->error = $commissionDictionaryMigration->getError();
			return -1;
		}

		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

		if (empty($user) || empty($user->id)) {
			$user = new User($this->db);
			$user->fetch(1);
		}

		/** @var array<string,string> $labels */
		$labels = CliChaumeilCommissionConfig::getDefaultCategoryLabels($langs);
		/** @var array<string,string> $refExts */
		$refExts = CliChaumeilCommissionConfig::getDefaultCategoryRefExt();
		foreach ($labels as $constKey => $label) {
			if (getDolGlobalInt($constKey)) {
				continue;
			}

			$refExt = $refExts[$constKey] ?? '';
			$categoryId = $this->findOrCreateCustomerCategory($label, $user, $refExt);
			if ($categoryId > 0) {
				dolibarr_set_const($this->db, $constKey, $categoryId, 'integer', 0, '', $conf->entity);
			}
		}

		return 1;
	}

	/**
	 * Ensure the default RFA email template exists.
	 *
	 * @return void
	 * @throws RuntimeException When the template cannot be created.
	 */
	private function ensureDefaultRfaEmailTemplate(): void
	{
		global $conf, $langs, $user;

		$langs->loadLangs(array('clichaumeil@clichaumeil'));
		$existingTemplateId = $this->findEmailTemplateIdByCode(self::DEFAULT_RFA_TEMPLATE_CODE);
		if ($existingTemplateId > 0) {
			return;
		}

		if (empty($user) || empty($user->id)) {
			$user = new User($this->db);
			$user->fetch(1);
		}

		$now = dol_now();
		$sql = "INSERT INTO " . $this->db->prefix() . "c_email_templates (";
		$sql .= "entity, module, type_template, lang, private, fk_user, datec, label, position, active, topic, content, enabled, joinfiles, email_from, email_to, email_tocc, email_tobcc, defaultfortype";
		$sql .= ") VALUES (";
		$sql .= (int) $conf->entity . ", ";
		$sql .= "'', ";
		$sql .= "'" . $this->db->escape(self::DEFAULT_RFA_TEMPLATE_TYPE) . "', ";
		$sql .= "'', ";
		$sql .= "0, ";
		$sql .= "null, ";
		$sql .= "'" . $this->db->idate($now) . "', ";
		$sql .= "'" . $this->db->escape(self::DEFAULT_RFA_TEMPLATE_CODE) . "', ";
		$sql .= "0, ";
		$sql .= "1, ";
		$sql .= "'" . $this->db->escape($langs->transnoentitiesnoconv('CliChaumeil_RfaReminderDefaultTemplateSubject')) . "', ";
		$sql .= "'" . $this->db->escape($langs->transnoentitiesnoconv('CliChaumeil_RfaReminderDefaultTemplateBody')) . "', ";
		$sql .= "'1', ";
		$sql .= "'', ";
		$sql .= "null, ";
		$sql .= "null, ";
		$sql .= "null, ";
		$sql .= "null, ";
		$sql .= "0";
		$sql .= ")";

		$result = $this->db->query($sql);
		if (!$result) {
			$errorMessage = $this->db->lasterror();
			dol_syslog(__METHOD__ . ' failed to create default RFA email template: ' . $errorMessage, LOG_ERR);
			throw new RuntimeException($errorMessage);
		}
	}

	/**
	 * Find an email template id by its code stored in c_email_templates.label.
	 *
	 * @param string $templateCode Template code.
	 * @return int
	 */
	private function findEmailTemplateIdByCode(string $templateCode): int
	{
		global $conf;

		$sql = "SELECT rowid";
		$sql .= " FROM " . $this->db->prefix() . "c_email_templates";
		$sql .= " WHERE entity = " . ((int) $conf->entity);
		$sql .= " AND type_template = '" . $this->db->escape(self::DEFAULT_RFA_TEMPLATE_TYPE) . "'";
		$sql .= " AND label = '" . $this->db->escape($templateCode) . "'";
		$sql .= " ORDER BY rowid ASC";

		$resql = $this->db->query($sql);
		if (!$resql) {
			throw new RuntimeException($this->db->lasterror());
		}

		$rowid = 0;
		$obj = $this->db->fetch_object($resql);
		if (!empty($obj->rowid)) {
			$rowid = (int) $obj->rowid;
		}
		$this->db->free($resql);

		return $rowid;
	}

	/**
	 * Get the next default execution timestamp for the yearly RFA reminder.
	 *
	 * @param int $now Current timestamp.
	 * @return int
	 */
	private function getNextRfaNegotiationStartTimestamp(int $now): int
	{
		$currentYear = (int) dol_print_date($now, '%Y');
		$targetTimestamp = dol_mktime(1, 0, 0, 12, 15, $currentYear);
		if ($targetTimestamp <= $now) {
			$targetTimestamp = dol_mktime(1, 0, 0, 12, 15, $currentYear + 1);
		}

		return $targetTimestamp;
	}

	/**
	 * Find a customer category by label, or create it if missing.
	 *
	 * @param string $label  Category label.
	 * @param User   $user   User used for create/update operations.
	 * @param string $refExt External reference used to match or update the category.
	 * @return int Category id, or 0 on failure
	 */
	private function findOrCreateCustomerCategory(string $label, User $user, string $refExt = ''): int
	{
		global $conf;

		$sql = "SELECT rowid, ref_ext FROM " . $this->db->prefix() . "categorie";
		$sql .= " WHERE entity = " . ((int) $conf->entity);
		$sql .= " AND type = " . ((int) Categorie::TYPE_CUSTOMER);
		if (!empty($refExt)) {
			$sql .= " AND ref_ext = '" . $this->db->escape($refExt) . "'";
		} else {
			$sql .= " AND label = '" . $this->db->escape($label) . "'";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if (!empty($obj->rowid)) {
				$category = new Categorie($this->db);
				if ($category->fetch((int) $obj->rowid) > 0) {
					if (!empty($refExt) && $category->ref_ext !== $refExt) {
						$category->ref_ext = $refExt;
						$updateResult = $category->update($user, 1);
						if ($updateResult < 0) {
							dol_syslog(__METHOD__ . ' category ref_ext update failed: ' . $category->error, LOG_ERR);
						}
					}
				}

				$this->db->free($resql);
				return (int) $obj->rowid;
			}

			$this->db->free($resql);
		}

		if (!empty($refExt)) {
			$sql = "SELECT rowid FROM " . $this->db->prefix() . "categorie";
			$sql .= " WHERE label = '" . $this->db->escape($label) . "'";
			$sql .= " AND entity = " . ((int) $conf->entity);
			$sql .= " AND type = " . ((int) Categorie::TYPE_CUSTOMER);

			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if (!empty($obj->rowid)) {
					$category = new Categorie($this->db);
					if ($category->fetch((int) $obj->rowid) > 0) {
						$category->ref_ext = $refExt;
						$updateResult = $category->update($user, 1);
						if ($updateResult < 0) {
							dol_syslog(__METHOD__ . ' category ref_ext update failed: ' . $category->error, LOG_ERR);
						}
					}
					$this->db->free($resql);
					return (int) $obj->rowid;
				}

				$this->db->free($resql);
			}
		}

		$category = new Categorie($this->db);
		$category->label = $label;
		$category->ref_ext = $refExt;
		$category->visible = 1;
		$category->type = Categorie::TYPE_CUSTOMER;
		$category->entity = (int) $conf->entity;

		$result = $category->create($user, 1);
		if ($result > 0) {
			return $category->id;
		}

		if (!empty($category->error)) {
			dol_syslog(__METHOD__ . ' category create failed: ' . $category->error, LOG_ERR);
		}

		return 0;
	}

	/**
	 * Create or update a product extrafield definition without destructive recreation.
	 *
	 * @param ExtraFields        $extrafields     Extrafields manager.
	 * @param string             $name            Extrafield name.
	 * @param string             $label           Translation key.
	 * @param string             $type            Field type.
	 * @param int                $position        Sort position.
	 * @param string             $size            Field size.
	 * @param int                $unique          Unique flag.
	 * @param int                $required        Required flag.
	 * @param string             $defaultValue    Default value.
	 * @param string|array       $params          Extra params.
	 * @param int                $alwaysEditable  Always editable flag.
	 * @param string             $perms           Permission expression.
	 * @param string|int         $list            Visibility expression.
	 * @param string             $help            Tooltip key.
	 * @param string             $computed        Computed expression.
	 * @param string|int         $entity          Entity.
	 * @param string             $langfile        Lang file.
	 * @param string|int         $enabled         Enabled expression.
	 * @param int                $totalizable     Totalizable flag.
	 * @param string|int         $printable       Printable flag.
	 * @param array<string,mixed> $moreParams     Additional parameters.
	 * @return void
	 */
	private function ensureProductExtrafield(
		ExtraFields $extrafields,
		string $name,
		string $label,
		string $type,
		int $position,
		string $size,
		int $unique,
		int $required,
		string $defaultValue,
		$params,
		int $alwaysEditable,
		string $perms,
		$list,
		string $help,
		string $computed,
		$entity,
		string $langfile,
		$enabled,
		int $totalizable,
		$printable,
		array $moreParams
	): void {
		$exists = isset($extrafields->attributes['product']['label'][$name]);

		if (!$exists) {
			$result = $extrafields->addExtraField(
				$name,
				$label,
				$type,
				$position,
				$size,
				'product',
				$unique,
				$required,
				$defaultValue,
				$params,
				$alwaysEditable,
				$perms,
				$list,
				$help,
				$computed,
				$entity,
				$langfile,
				$enabled,
				$totalizable,
				$printable,
				$moreParams
			);

			if ($result <= 0) {
				dol_syslog(__METHOD__ . ' failed to create extrafield ' . $name, LOG_ERR);
			}

			return;
		}

		$result = $extrafields->updateExtraField(
			$name,
			$label,
			$type,
			$position,
			$size,
			'product',
			$unique,
			$required,
			$defaultValue,
			$params,
			$alwaysEditable,
			$perms,
			$list,
			$help,
			$computed,
			$entity,
			$langfile,
			$enabled,
			$totalizable,
			$printable,
			$moreParams
		);

		if ($result <= 0) {
			dol_syslog(__METHOD__ . ' failed to update extrafield ' . $name, LOG_ERR);
		}
	}

	/**
	 * Ensure the protected default proposal line extrafield exists on proposal lines.
	 *
	 * @param ExtraFields $extrafields Extrafields manager.
	 * @return void
	 */
	private function ensurePropalDefaultLineExtrafield(ExtraFields $extrafields): void
	{
		$extrafields->fetch_name_optionals_label('propaldet', true);
		if (isset($extrafields->attributes['propaldet']['label'][self::DEFAULT_PROPAL_LINE_EXTRAFIELD])) {
			return;
		}

		$result = $extrafields->addExtraField(
			self::DEFAULT_PROPAL_LINE_EXTRAFIELD,
			'CLICHAUMEIL_DEFAULT_PROPAL_LINE_MARKER',
			'boolean',
			220,
			'',
			'propaldet',
			0,
			0,
			'',
			array('options' => array('' => null)),
			1,
			'',
			'0',
			'',
			'',
			0,
			'clichaumeil@clichaumeil',
			'isModEnabled("clichaumeil")',
			0,
			'0',
			array('css' => '', 'cssview' => '', 'csslist' => '')
		);

		if ($result <= 0) {
			dol_syslog(__METHOD__ . ' failed to create propal default line extrafield ' . self::DEFAULT_PROPAL_LINE_EXTRAFIELD, LOG_ERR);
		}
	}
}
