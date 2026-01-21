<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *    \file       article_list.php
 *        \ingroup    lareponse
 *        \ingroup    lareponse
 *        \brief      List page for article
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');					// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');					// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');					// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');					// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');			// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');			// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');					// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');					// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');					// Do not check style html tag into posted data
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');						// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');					// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');					// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       		  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');						// If this page is public (can be called outside logged session)
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');			// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', '1');		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("XFRAMEOPTIONS_ALLOWALL"))   define('XFRAMEOPTIONS_ALLOWALL', '1');			// Do not add the HTTP header 'X-Frame-Options: SAMEORIGIN' but 'X-Frame-Options: ALLOWALL'

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';
// load lareponse libraries
require_once __DIR__ . '/class/article.class.php';
require_once __DIR__ . '/lib/lareponse_article.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('/lareponse/class/export.class.php');
dol_include_once('/lareponse/class/tag.class.php');
dol_include_once('/lareponse/lib/lareponse_favorites.lib.php');

global $conf, $user, $db, $langs;

if (isset($conf->gestionparc->enabled) && $conf->gestionparc->enabled) {
	dol_include_once('/gestionparc/lib/device.lib.php');
	dol_include_once('/gestionparc/class/device.class.php');
	dol_include_once('/gestionparc/lib/application.lib.php');
	dol_include_once('/gestionparc/class/application.class.php');
	dol_include_once('/gestionparc/lib/address.lib.php');
	dol_include_once('/gestionparc/lib/output.lib.php');
	dol_include_once('/gestionparc/class/categorie.class.php');
	dol_include_once('/gestionparc/class/role.class.php');
	dol_include_once('/gestionparc/class/gestionparccommonobject.class.php');
	dol_include_once('/gestionparc/lib/contact.lib.php');
	dol_include_once('/gestionparc/class/contact.class.php');
	dol_include_once('/gestionparc/class/address.class.php');
}

// Load translation files required by the page
$langs->loadLangs(array("lareponse@lareponse", "other"));

$action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$show_files = GETPOST('show_files', 'int'); // Show files area generated by bulk actions ?
$confirm = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'articlelist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$type = GETPOST('type', 'alpha');
$catid = GETPOST('catid', 'int');
$id = GETPOST('id', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
// Setup
$searchCategoryTagOperator = (GETPOST('search_category_tag_operator', 'int') ? GETPOST('search_category_tag_operator', 'int') : 0);
$searchCategoryTagList = GETPOST('search_category_tag_list', 'array');
$searchStatusList = GETPOST('search_private_status', 'array');
$searchFavorite = GETPOST('search_favorite', 'alpha');

if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$type_array = array();

// Initialize technical objects
$object = new Article($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->lareponse->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array('articlelist')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
//$extrafields->fetch_name_optionals_label($object->table_element_line);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) $sortfield = "t.tms"; // Set here default search field. By default 1st field in definition.
if (!$sortorder) $sortorder = "DESC";

// Security check
if (empty($conf->lareponse->enabled)) accessforbidden('Module not enabled');
$socid = 0;

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha') ? GETPOST("search_all", 'alpha') : GETPOST("sall", 'alpha');
$search_all = trim($search_all);
$search = array();
$object->fields['favorite'] = array('type' => 'integer', 'label' => 'Favorite', 'enabled' => 1, 'position' => 150, 'visible' => 1, 'arrayofkeyval' => array(-1 => '', 0 => 'No', 1 => 'Yes'), 'notnull' => 0);
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_' . $key, 'alpha') !== '') $search[$key] = GETPOST('search_' . $key, 'alpha');
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($object->fields as $key => $val) {
	if (isset($val['searchall']) && $val['searchall']) $fieldstosearchall['t.' . $key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['t.' . $key] = array('label' => $val['label'], 'checked' => (($val['visible'] < 0) ? 0 : 1), 'enabled' => ($val['enabled'] && ($val['visible'] != 3)), 'position' => $val['position']);
}
// Extra fields
if (isset($extrafields->attributes[$object->table_element]['label'])) {
	if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
			if (!empty($extrafields->attributes[$object->table_element]['list'][$key])) {
				$arrayfields["ef." . $key] = array(
					'label' => $extrafields->attributes[$object->table_element]['label'][$key],
					'checked' => (($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1),
					'position' => $extrafields->attributes[$object->table_element]['pos'][$key],
					'enabled' => (abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key])
				);
			}
		}
	}
}
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

$permissiontoread = $user->rights->lareponse->article->read ?? false;
$permissiontoadd = $user->rights->lareponse->article->write ?? false;
$permissiontodelete = $user->rights->lareponse->article->delete ?? false;
$permissiontoexport = $user->rights->lareponse->article->export ?? false;
$permissiontoeditother = $user->rights->lareponse->article->update_other ?? false;

if ($permissiontoeditother) $permissiontoadd = 1;

$permtoread = $permissiontoread;
$permtodelete = $permissiontodelete;


/*
 * Actions
 */

//remove tag_list because url length can exceeds the capacity limit for the server
if (!empty($searchCategoryTagList) && $id > 0 && $type != '') {
	$parsedUrl = parse_url($_SERVER["REQUEST_URI"]);
	parse_str($parsedUrl['query'] ?? '', $queryParams);
	unset($queryParams['search_category_tag_list']);
	header("Location: " . $_SERVER["PHP_SELF"] . '?' . http_build_query($queryParams));
	exit;
}

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

if (($massaction == 'export' || ($action == 'export' && $confirm == 'yes')) && $permissiontoexport) {
	$export = new LareponseExport();
	if ($export->generateJsonFile($toselect)) {
		$export->downloadExportFile($toselect);
	} else {
		setEventMessages($langs->trans('ArticleFailureExport'), '', 'errors');
	}
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		// Remove filter
		$searchCategoryTagOperator = 0;
		$searchCategoryTagList = array();
		$searchStatusList = array();

		foreach ($object->fields as $key => $val) {
			$search[$key] = '';
		}
		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'article';
	$objectlabel = 'article';
	$uploaddir = $conf->lareponse->dir_output;
	include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
}


/*
 * View
 */

$form = new Form($db);

$now = dol_now();
$cat = new Categorie($db);

$type = ucfirst($type);

if ($id > 0) {
	$moduleClassName = new $type($db);
	if ($type == 'Societe') {
		if (isset($conf->gestionparc->enabled) && $conf->gestionparc->enabled) {
			if (dolibarr_get_const($db, "LAREPONSE_TAG_GESTIONPARC_ACTIVE", $conf->entity) == '1') {
				$arrayTagsGestionParc = getAllTag($id);
				foreach ($arrayTagsGestionParc as $tagGestionParc) {
					$searchCategoryTagList[] = $tagGestionParc;
				}
			}
		}
		if (dolibarr_get_const($db, "LAREPONSE_TAG_CATEGORIES_ACTIVE", $conf->entity) == '1') {
			$societeCategories = $cat->containing($id, Categorie::TYPE_CUSTOMER);
			$supplierCategories = $cat->containing($id, Categorie::TYPE_SUPPLIER);
			$moduleClassName->fetch($id);

			foreach ($societeCategories as $cat) {
				$searchCategoryTagList[] = $cat->id;
			}

			if ($moduleClassName->fournisseur == 1) {
				foreach ($supplierCategories as $cat) {
					$searchCategoryTagList[] = $cat->id;
				}
			}

			if (count($moduleClassName->contact_array_objects()) > 0) {
				$contacts = $moduleClassName->contact_array_objects();
				foreach ($contacts as $contact) {
					$contactTags = $cat->containing($contact->id, Categorie::TYPE_CONTACT);
					foreach ($contactTags as $contactTag) {
						$searchCategoryTagList[] = $contactTag->id;
					}
				}
			}
		}
	} elseif ($type == 'Article') {
		$tag = new Tag($db);
		$object->fetch($id);
		$listTagsArticle = $tag->getArticleTag($id);
		foreach ($listTagsArticle as $tagValue) {
			$tagId = intval($tagValue['id']);
			$searchCategoryTagList[] = $tagId;
		}
	} elseif ($type == 'Device' || $type == 'Application' || $type == 'Address' ) {
		if (isset($conf->gestionparc->enabled) && $conf->gestionparc->enabled) {
			if (dolibarr_get_const($db, "LAREPONSE_TAG_GESTIONPARC_ACTIVE", $conf->entity) == '1') {
				if ($type == 'Application') {
					$newtype = substr($type, 0, 3);
					$getRolesOf = 'getRolesOf' . $newtype;
				} else {
					$getRolesOf = 'getRolesOf' . $type;
				}
				if (function_exists($getRolesOf)) {
					$listOfRolesId = call_user_func($getRolesOf, $id);
				}
				foreach ($listOfRolesId as $roleId) {
					$searchCategoryTagList[] = $roleId;
				}
			}
		}
	} else {
		if (dolibarr_get_const($db, "LAREPONSE_TAG_CATEGORIES_ACTIVE", $conf->entity) == '1') {
			$const = constant("Categorie::TYPE_" . strtoupper($type));
			$categories = $cat->containing($id, $const);
			foreach ($categories as $cat) {
				$searchCategoryTagList[] = $cat->id;
			}
		}
		if (isset($conf->gestionparc->enabled) && $conf->gestionparc->enabled) {
			if (dolibarr_get_const($db, "LAREPONSE_TAG_GESTIONPARC_ACTIVE", $conf->entity) == '1') {
				if ($type == 'Contact') {
					$rolesContact = getRolesOfContact($id);
					foreach ($rolesContact as $roles) {
						$searchCategoryTagList[] = $roles;
					}
				}

				if ($type == 'Ticket') {
					if (isset($moduleClassName->array_options['options_c42ticket_device_linked'])) {
						$moduleClassName->fetch($id);
						$device = new Device($db);
						$idDevice = $device->fetch($moduleClassName->array_options['options_c42ticket_device_linked']);
						$listOfDeviceId = getRolesOfDevice($idDevice);
						foreach ($listOfDeviceId as $deviceId) {
							$searchCategoryTagList[] = $deviceId;
						}
					}
				}
			}
		}
	}
}
if ($id > 0 && $type != '') {
	if (count($searchCategoryTagList) >= 2) {
		$searchCategoryTagOperator = 1;
	} else {
		$searchCategoryTagOperator = 0;
	}
}

$help_url = '';
$title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("articles"));

// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT DISTINCT ';
foreach ($object->fields as $key => $val) {
	if ($key == 'favorite') continue;
	else $sql .= 't.' . $key . ', ';
}

// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef." . $key . ' as options_' . $key . ', ' : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
$sql = preg_replace('/,\s*$/', '', $sql);
$sql .= " FROM " . MAIN_DB_PREFIX . $object->table_element . " as t";

if ($searchCategoryTagOperator == 1) {
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'lareponse_article_tag cp ON t.rowid = cp.fk_article';
}


if ($searchFavorite == "0" || $searchFavorite == "1") {
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'lareponse_favorites f ON t.rowid = f.fk_article' . ($searchFavorite == "1" ? '' : ' AND f.fk_user = ' . $user->id);
}

if (isset($extrafields->attributes[$object->table_element]['label'])) {
	if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $object->table_element . "_extrafields as ef on (t.rowid = ef.fk_object)";
}
if ($object->ismultientitymanaged == 1) $sql .= " WHERE t.entity IN (" . getEntityLareponse($object->element) . ")";
else $sql .= " WHERE 1 = 1";
if (!$search && empty($searchStatusList)) $sql .= " AND (t.private = 0 OR (t.private = 1 AND t.fk_user_creat = " . $user->id . "))";
if ($id && $type == 'Article') $sql .= " AND t.rowid <> " . $id;
if ($catid > 0) $sql .= " AND cp.fk_categorie = " . $catid;
if ($catid == -2) $sql .= " AND cp.fk_categorie IS NULL";

if ($searchFavorite == "0" || $searchFavorite == "1") {
	$sql .= ($searchFavorite == "1" ? " AND f.fk_user = " . $user->id : " AND f.fk_article IS NULL");
}

foreach ($search as $key => $val) {
	// original search
	if (($db->type == 'pgsql') and ($key == 'title') and ($val != '')) {
		$sql .= " AND (title LIKE '%" . $search[$key] . "%')";
	}
	if (($db->type == 'mysqli') and ($key == 'title') and ($val != '')) {
		$sql .= " AND ((t.title LIKE '%" . $db->escape($search[$key]) . "%') OR (t.content LIKE '%" . $db->escape($search[$key]) . "%'))"; // #236
	}


	if ($key == 'status' && $search[$key] == -1) continue;
	if ($key == 'favorite') continue;
	if ($key == 'type' && $search[$key] == -1) continue;
	$mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
	if (strpos($object->fields[$key]['type'], 'integer:') === 0) {
		if ($search[$key] == '-1') $search[$key] = '';
		$mode_search = 2;
	}

	if ($key == 'publish_token') {
		if ($val == '1') $sql .= " AND t.publish_token IS NOT NULL";
		elseif ($val == '0') $sql .= " AND t.publish_token IS NULL";
	} else if (($search[$key] != '') and (($key != 'title')) and ($key != 'publish_token') and ($key != 'private')) $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
}

if (!empty($searchStatusList)) {
	$sql .= " AND ";
	foreach ($searchStatusList as $status) {
		switch ($status) {
			case '0':
			case '2':
				$statusList[] = "t.private = " . $status;
				break;
			case '1':
				$statusList[] = "(t.private = " . $status . " AND fk_user_creat = " . $user->id . ")";
				break;
		}
	}
	$sql .= "(" . implode(' OR ',  $statusList) . ")";
} else {
	$searchStatusList = array('0', '1');
}

// Creation Date
$dateCreationArticleStart = dol_stringtotime(GETPOST('search_date_creation_start'));
$dateCreationArticleEnd = dol_stringtotime(GETPOST('search_date_creation_end'));
if (!empty($dateCreationArticleStart) && !empty($dateCreationArticleEnd)) $sql .= " AND t.date_creation >= '" . dol_print_date($dateCreationArticleStart, '%Y-%m-%d') . " 0:00:00' AND t.date_creation <= '" . dol_print_date($dateCreationArticleEnd, '%Y-%m-%d') . " 23:59:59'";
elseif (!empty($dateCreationArticleStart)) $sql .= " AND t.date_creation >= '" . dol_print_date($dateCreationArticleStart, '%Y-%m-%d') . " 0:00:00'";
elseif (!empty($dateCreationArticleEnd)) $sql .= " AND t.date_creation <= '" . dol_print_date($dateCreationArticleEnd, '%Y-%m-%d') . " 23:59:59'";
// Modification Date
$dateModificationArticleStart = dol_stringtotime(GETPOST('search_tms_start'));
$dateModificationArticleEnd = dol_stringtotime(GETPOST('search_tms_end'));
if (!empty($dateModificationArticleStart) && !empty($dateModificationArticleEnd)) $sql .= " AND t.tms >= '" . dol_print_date($dateModificationArticleStart, '%Y-%m-%d') . " 0:00:00' AND t.date_creation <= '" . dol_print_date($dateModificationArticleEnd, '%Y-%m-%d') . " 23:59:59'";
elseif (!empty($dateModificationArticleStart)) $sql .= " AND t.tms >= '" . dol_print_date($dateModificationArticleStart, '%Y-%m-%d') . " 0:00:00'";
elseif (!empty($dateModificationArticleEnd)) $sql .= " AND t.tms <= '" . dol_print_date($dateModificationArticleEnd, '%Y-%m-%d') . " 23:59:59'";

if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);

// Action
$searchCategoryTagSqlList = array();
if ($searchCategoryTagOperator == 1) {
	foreach ($searchCategoryTagList as $searchCategoryTag) {
		if (intval($searchCategoryTag) == -2) {
			$searchCategoryTagSqlList[] = "cp.fk_tag IS NULL";
		} elseif (intval($searchCategoryTag) > 0) {
			$searchCategoryTagSqlList[] = "cp.fk_tag = " . $db->escape($searchCategoryTag);
		}
	}
	if (!empty($searchCategoryTagSqlList)) {
		$sql .= " AND (" . implode(' OR ', $searchCategoryTagSqlList) . ")";
	}
} else {
	foreach ($searchCategoryTagList as $searchCategoryTag) {
		if (intval($searchCategoryTag) == -2) {
			$searchCategoryTagSqlList[] = "cp.fk_tag IS NULL";
		} elseif (intval($searchCategoryTag) > 0) {
			$searchCategoryTagSqlList[] = "t.rowid IN (SELECT fk_article FROM " . MAIN_DB_PREFIX . "lareponse_article_tag as cp WHERE cp.fk_tag = " . $searchCategoryTag . ")";
		}
	}
	if (!empty($searchCategoryTagSqlList)) {
		$sql .= " AND (" . implode(' AND ', $searchCategoryTagSqlList) . ")";
	}
}

//$sql.= dolSqlDateFilter("t.field", $search_xxxday, $search_xxxmonth, $search_xxxyear);
// Add where from extra fields
if ($search_array_options)
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters = array();

$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);
// Count total nb of records
if ($id > 0 && $type != '') {
	if (empty($searchCategoryTagList)) $sql = '';
}

$nbTotalOfRecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	if ($id > 0 && $type != '' && empty($searchCategoryTagList)) {
		$nbTotalOfRecords = 0;
	} else {
		$nbTotalOfRecords = $db->num_rows($resql);
	}
	if (($page * $limit) > $nbTotalOfRecords) {    // if total of record found is smaller than page * limit, goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbTotalOfRecords) && ($limit > $nbTotalOfRecords || empty($limit))) {
	$num = $nbTotalOfRecords;
} else {
	if ($limit) $sql .= $db->plimit($limit + 1, $offset);
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}

// Direct jump if only one record found
if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && !$page) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: " . dol_buildpath('/lareponse/article_card.php', 1) . '?id=' . $id);
	exit;
}

// Output page
// --------------------------------------------------------------------

$arrayofjs = array(
	'/lareponse/js/lareponse_article_list.js.php',
	'/lareponse/js/chosen.jquery.min.js'
);

$arrayofcss = array(
	'/lareponse/css/chosen.min.css'
);

llxHeader('', $title, $help_url, '', 0, 0, $arrayofjs, $arrayofcss);
if ($id > 0) {
	if ($moduleClassName->fetch($id) > 0) {
		$linkback = '<a href="' . DOL_URL_ROOT . '/'.strtolower($type).'/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
		if ($type == 'Project') {
			print prepareHead($type, $moduleClassName);
			$morehtmlref = '<div class="refidno">';

			// Title
			$morehtmlref .= $moduleClassName->title;

			// Thirdparty
			if (!empty($moduleClassName->thirdparty->id) && $moduleClassName->thirdparty->id > 0) {
				$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$moduleClassName->thirdparty->getNomUrl(1, 'project');
			}

			$morehtmlref .= '</div>';
			$linkback = '<a href="' . DOL_URL_ROOT . '/'.strtolower($langs->trans($type)).'/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
			dol_banner_tab($moduleClassName, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '&type='.$type.'');
		} elseif ($type == 'Product') {
			print prepareHead($type, $moduleClassName);
			dol_banner_tab($moduleClassName, 'id', $linkback, 1, 'rowid', 'ref', '', '&type='.$type.'');
		} elseif ($type == 'Contact') {
			print prepareHead($type, $moduleClassName);
			// Store current page url
			$morehtmlref = '<a href="'.DOL_URL_ROOT.'/contact/vcard.php?id='.$moduleClassName->id.'" class="refid">';
			$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
			$morehtmlref .= '</a>';

			$morehtmlref .= '<div class="refidno">';
			if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
				$objsoc = new Societe($db);
				$objsoc->fetch($moduleClassName->socid);
				// Thirdparty
				$morehtmlref .= $langs->trans('LaReponseThirdParty').' ';
				if ($objsoc->id > 0) {
					$morehtmlref .= $objsoc->getNomUrl(1);
				} else {
					$morehtmlref .= $langs->trans("ContactNotLinkedToCompany");
				}
			}
			$morehtmlref .= '</div>';
			dol_banner_tab($moduleClassName, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '&type='.$type.'');
		} elseif ($type == 'Societe') {
			print prepareHead($type, $moduleClassName);
			dol_banner_tab($moduleClassName, 'id', $linkback, 1, 'rowid', 'ref', '', '&type='.$type.'');
		} elseif ($type == 'Ticket') {
			print prepareHead($type, $moduleClassName);
			$urlPageCurrent = DOL_URL_ROOT.'/ticket/contact.php';
			$morehtmlref = '<div class="refidno">';
			$morehtmlref .= $moduleClassName->subject;
			// Author
			if ($moduleClassName->fk_user_create > 0) {
				$morehtmlref .= '<br>'.$langs->trans("CreatedBy").' ';
				$morehtmlref .= $user->getNomUrl(-1);
			}

			// Thirdparty
			if (isModEnabled('societe')) {
				$morehtmlref .= '<br>'.$langs->trans('LaReponseThirdParty'). ' ';
				$morehtmlref .= $form->form_thirdparty($urlPageCurrent.'?track_id='.$moduleClassName->track_id, $moduleClassName->socid, 'none', '', 1, 0, 0, array(), 1);
			}

			// Project
			if (isModEnabled('project')) {
				$morehtmlref .= '<br>'.$langs->trans('LaReponseProject').' ';
				if (!empty($moduleClassName->fk_project)) {
					$project = new Project($db);
					$project->fetch($moduleClassName->fk_project);
					$morehtmlref .= $project->getNomUrl(1);
				}
			}

			$morehtmlref .= '</div>';
			dol_banner_tab($moduleClassName, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '&type='.$type.'');
		} elseif ($type == 'Article') {
			$head = articlePrepareHead($moduleClassName);
			$moduleClassName->picto = 'article_50@lareponse';
			print dol_get_fiche_head($head, 'article', $langs->trans("Article"), 0, $moduleClassName->picto);
			$linkback = '<a href="'.dol_buildpath('/lareponse/article_list.php', 1). '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid='.$socid : '') . '">' . $langs->trans("BackToList") .'</a>';
			// Tag for banner tab
			$tagArticleList = $tag->getArticleTag($id);
			$tagList = $tag->getAllTag();
			$collapseTagButton = '<span id="lareponse-tags-icon" class="fas fa-chevron-up"></span>';
			$tagDiv = '';
			if ($object->private == 2) $tagDiv .= '<span style="background: grey; color: var(--colortextbackhmenu);" class="badge marginleftonlyshort"> '.$langs->trans('ArticleClosed').'</span>';

			if (count($tagArticleList) > 6) {
				$tagDiv = $collapseTagButton;
			}

			$tagDiv .= '<br><br><div id="tags-section">';

			foreach ($tagArticleList as $tagVal) {
				$tagId = intval($tagVal['id']);
				$tag->fetch($tagId);
				$typeObject = getObjectNameByType($tagVal);
				$ways = $tag->print_all_ways(' &gt;&gt; <span class="fa fa-tag"></span> ', dol_buildpath('/lareponse/article_list.php', 1));
				$tagData = $ways[0];
				// Some colors added by categories module may be empty or missing a '#'. We're here changing the string so that it matches a #XXXXXX pattern or becomes black if empty
				mb_substr($tagVal['color'], 0, 1) == '#' ? $currcolor = $tagVal['color'] : $currcolor = '#'.$tagVal['color'];
				if (strlen($currcolor) == 1) {
					$currcolor .= '000000';
				}
				$tagDiv .= '<div><a style="background-color: '.$currcolor.'; color:'.(colorIsLight($currcolor) ? 'black' : 'white').' !important;" class="lareponse_tag" href="'.dol_buildpath('/lareponse/card_tag.php', 1).'?id='.$tagVal['id'].'"><span class="fa fa-tag"></span> '.$tagData . ' ('. $typeObject.')</a></div>';
			}
			$tagDiv .= '</div>';
			dol_banner_tab($moduleClassName, 'id', $linkback, 1, 'rowid', 'title', $tagDiv, '&type=article');
		} elseif ($type == 'Device') {
			$head = devicePrepareHead($moduleClassName);
			print dol_get_fiche_head($head, 'lareponse_article', $langs->trans("Article"), 0, 'device_18@gestionparc');
			$morehtmlref = gestionParcHeader($moduleClassName, $type);

			$linkback = '<a href="' . dol_buildpath('/gestionparc/device_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
			gestionparc_dol_banner_tab($moduleClassName, 'id', $linkback, 1, 'rowid', 'name', $morehtmlref, '&type='.$type.'', '', '', '', '1', '');
		} elseif ($type == 'Application') {
			$head = applicationPrepareHead($moduleClassName);
			print dol_get_fiche_head($head, 'lareponse_article', $langs->trans("Article"), 0, 'application_18@gestionparc');
			$morehtmlref = gestionParcHeader($moduleClassName, $type);

			$linkback = '<a href="' . dol_buildpath('/gestionparc/application_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
			gestionparc_dol_banner_tab($moduleClassName, 'id', $linkback, 1, 'rowid', 'name', $morehtmlref, '&type='.$type.'', '', '', '', '1', '');
		} elseif ($type == 'Address') {
			$head = addressPrepareHead($moduleClassName);
			print dol_get_fiche_head($head, 'lareponse_article', $langs->trans("Article"), 0, 'address_18@gestionparc');
			$morehtmlref = gestionParcHeader($moduleClassName, $type);

			$linkback = '<a href="' . dol_buildpath('/gestionparc/address_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
			gestionparc_dol_banner_tab($moduleClassName, 'id', $linkback, 1, 'rowid', 'name', $morehtmlref, '&type='.$type.'', '', '', '', '1', '');
		}
		print dol_get_fiche_end();
	} else {
		print "ErrorRecordNotFound";
	}
}

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);
foreach ($search as $key => $val) {
	if (is_array($search[$key]) && count($search[$key])) foreach ($search[$key] as $skey) $param .= '&search_' . $key . '[]=' . urlencode($skey);
	else $param .= '&search_' . $key . '=' . urlencode($search[$key]);
}
if ($optioncss != '') $param .= '&optioncss=' . urlencode($optioncss);
// Add $param from extra fields
if ($search_array_options)
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';

// List of mass actions available
$arrayofmassactions = array();
if ($id > 0) $param .= "&id=" . $id;

if ($type != '') $param .= "&type=" . $type;


if ($permissiontodelete) $arrayofmassactions['predelete'] = '<span class="fas fa-trash-alt paddingrightonly"></span>' . $langs->trans("Delete");
if ($permissiontoexport) $arrayofmassactions['preexport'] = $langs->trans("Export");
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete', 'preexport'))) $arrayofmassactions = array();
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

/*
 * Demo text
 */

if ($conf->global->LAREPONSE_WIZARD_ACTIVE == 1 && !empty($conf->global->LAREPONSE_WIZARD_ARTICLE_LIST)) {
	print '<br /><div class="gp-demo-div">';
	print $conf->global->LAREPONSE_WIZARD_ARTICLE_LIST;
	print '</div>';
}

print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">' . "\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
if ((float) DOL_VERSION >= 11) {
	print '<input type="hidden" name="token" value="' . newToken() . '">';
} else {
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
}
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="page" value="' . $page . '">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
print '<input type="hidden" name="id" value="' . $id . '">';
print '<input type="hidden" name="type" value="' . $type . '">';
// Confirmation menu for mass export on list
if ($massaction == 'preexport') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassExport"), $langs->trans("ConfirmMassExportQuestion", count($toselect)), "export", null, '', 0, 200, 500, 1);
}


if ((float) DOL_VERSION >= 10) {
	if (empty($searchCategoryTagList)) {
		$createUrl = dol_buildpath('/lareponse/article_card.php', 1) . '?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF']);
	} else {
		$createUrl = dol_buildpath('/lareponse/article_card.php', 1) . '?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '&tag_ids=' . urlencode(implode(',', $searchCategoryTagList));
	}
	$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', $createUrl, '', $permissiontoadd);
} else {
	$newcardbutton = '<a class="btnTitle" href="' . DOL_URL_ROOT . '/custom/lareponse/article_card.php?action=create&amp;backtopage=%2Fcustom%2Flareponse%2Farticle_list.php" id="" >';
	$newcardbutton .= '<span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span><span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">' . $langs->trans('New') . '</span></a>';
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbTotalOfRecords, 'object_article_50@lareponse', 0, $newcardbutton, '', $limit);

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "SendarticleRef";
$modelmail = "article";
$objecttmp = new Article($db);
$trackid = 'xxxx' . $object->id;
include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';


if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">' . $langs->trans("FilterOnInto", $search_all) . join(', ', $fieldstosearchall) . '</div>';
}

$moreforfilter = '';

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

$tag = new Tag($db);
$tagList = $tag->getAllTag();
$tagIdList = $tag->getAllIdTag($tagList);
$tagIds = array();

foreach ($tagIdList as $key => $value) {
	$ids = intval($value['id']);
	$tag->fetch($ids);
	$ways = $tag->print_all_ways(' &gt;&gt; ', dol_buildpath('/lareponse/article_list.php', 1));
	$typeObject = getObjectNameByType($value);
	$tagIds[$ids] = $ways[0] . ' ('. $typeObject . ')';
}

//#224
if (empty($id) && empty($type)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print '<div class="divsearchfield">';
	print '<span class="fa fa-tag" style="padding: 0 5px"></span>';
	$placeholder = $langs->trans('PlaceholderTags');
	$tag->printSelectTag('search_category_tag_list', $searchCategoryTagList, $placeholder);
	print ' <input type="checkbox" class="valignmiddle" name="search_category_tag_operator" value="1"' . ($searchCategoryTagOperator == 1 ? ' checked="checked"' : '') . '/> <span class="none">' . $langs->trans('LaReponseCategoriesUseOrOperatorForCategories') . '</span>';
	print '</div></div>';
}

// Url param
if ($searchCategoryTagOperator == 1) $param .= "&search_category_tag_operator=" . urlencode($searchCategoryTagOperator);
foreach ($searchCategoryTagList as $searchCategoryTag) { $param .= "&search_category_tag_list[]=" . urlencode($searchCategoryTag);}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable liste">' . "\n";


if (empty($id) && empty($type)) {
	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre_filter">';
	foreach ($object->fields as $key => $val) {
		if (!isset($search[$key])) {
			$search[$key] = "";
		}

		if (in_array($key, array('publish_token'))) {
			print '<td class="liste_titre">';
			print $form->selectarray('search_' . $key, array(0 => $langs->trans('No'), 1 => $langs->trans('Yes')), $search[$key], $langs->trans('LaReponseAll'), 0, 0, '', 1, 0, 0, '', 'maxwidth75');
			print '</td>';
			continue;
		}
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
		elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '') . 'right';
		if (!empty($arrayfields['t.' . $key]['checked'])) {
			print '<td class="liste_titre' . ($cssforfield ? ' ' . $cssforfield : '') . '">';
			if ($key == 'private') {
				$privateMultiSelect = '<div class="arrayofkeyval">';
				if ($id > 0 && $type != '') $privateMultiSelect .= Form::multiselectarray('search_private_status', $val["arrayofkeyval"], $searchStatusList, 0, 0, 'minwidth200', '', '', 'disabled');
				else $privateMultiSelect .= Form::multiselectarray('search_private_status', $val["arrayofkeyval"], $searchStatusList, 0, 0, 'minwidth200');
				if (!empty($privateMultiSelect)) print $privateMultiSelect;
				print "</div>";
			} elseif (isset($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) { print $form->selectarray('search_' . $key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
			} elseif (strpos($val['type'], 'integer:User:') === 0) {
				print $form->select_dolusers($search[$key], 'search_' . $key, 1, null, 0, '', '', '0', 0, 0, 'AND u.fk_soc IS NULL');
			} elseif (preg_match('/^(date|timestamp)/', $val['type'])) {
				print $form->selectDateToDate(-1, -1, 'search_' . $key);
			} elseif (strpos($val['type'], 'integer:') === 0) {
				// Resolve #12 ticket
				if ((float) DOL_VERSION >= 10) {
					print $object->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth200', 1);
				}
			} elseif ($key == 'title') {
				print '<input type="text" placeholder="'.$langs->trans('LaReponseInputTitlePlaceHolder').'" class="flat maxwidth600" size="80" name="search_' . $key . '" value="' . dol_escape_htmltag($search[$key]) . '">'; //#145
			} elseif (isset($val['type']) && !preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth200" name="search_' . $key . '" value="' . dol_escape_htmltag($search[$key]) . '">';
			print '</td>';
		}
	}

	// Extra fields
	if ($search_array_options)
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print '</tr>' . "\n";
}


// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($object->fields as $key => $val) {
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '') . 'right';
	if (!empty($arrayfields['t.' . $key]['checked'])) {
		print getTitleFieldOfList($arrayfields['t.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, ($cssforfield ? 'class="' . $cssforfield . '"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield . ' ' : '')) . "\n";
	}
}
// Extra fields
if ($search_array_options)
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';

// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ') . "\n";
print '</tr>' . "\n";


// Detect if we need a fetch on each output line
$needToFetchEachLine = 0;
if (isset($extrafields->attributes[$object->table_element]['computed'])) {
	if (is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0) {
		foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val) {
			if (preg_match('/\$object/', $val)) $needToFetchEachLine++; // There is at least one compute field that use $object
		}
	}
}

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$totalarray = array();
$cloneObjectFields = $object->fields;
unset($object->fields['favorite']);
while ($i < ($limit ? min($num, $limit) : $num)) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) break; // Should not happen

	// Store properties in $object
	//$object->setVarsFromFetchObj($obj);
	$object->fetch($obj->rowid);

	// Show here line of result
	foreach ($cloneObjectFields as $key => $val) {
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		elseif ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		if (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
		elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
		elseif ($key == 'fk_user_creat' || $key == 'fk_user_modif') $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';

		if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $key != 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'right';

		if (!empty($arrayfields['t.' . $key]['checked'])) {
			print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . '>';
			if ($key == 'title') {
				print $object->getNomUrl(1);
			} else if ($key == 'favorite') {
				if (favoriteExists($user->id, $object->id)) print '<i class="LaReponse fa fa-star">';
			} else if ($key == 'content') {
				$currentcontent = contentWithoutTable($object->$key);
				if (strlen($currentcontent) > 150 && !strlen($currentcontent) == 0) {
					print '<a href="' . dol_buildpath('/lareponse/article_card.php', 1) . '?id=' . $object->id . '&save_lastsearch_values=1"';
					print ' title="<u>' . $langs->trans('Article') . '</u><br><b>' . $langs->trans('LinkToArticle') . '</b>"';
					print 'class="classfortooltip">';
					print substr($currentcontent, 0, 150) . '...';
					print '</a>';
				} else print $object->showOutputField($val, $key, $currentcontent, '');
			} else if ($key == 'publish_token') {
				print ($object->$key ? $langs->trans('Yes') : $langs->trans('No'));
			} else if ($key == 'fk_user_creat') {
				$userCreate = new User($db);
				$userCreate->fetch($object->fk_user_creat);
				print activeContributorUrl($userCreate, -1);
			} else if ($key == 'fk_user_modif') {
				$userModif = new User($db);
				$userModif->fetch($object->fk_user_modif);
				print activeContributorUrl($userModif, -1);
			} else {
				print $object->showOutputField($val, $key, $object->$key, '');
			}
			print '</td>';
			if (isset($totalarray['nbfield'])) {
				if (!$i) $totalarray['nbfield']++;
			}
			if (!empty($val['isameasure'])) {
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
				$totalarray['val']['t.' . $key] += $object->$key;
			}
		}
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields, 'object' => $object, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print '<td class="nowrap center">';
	if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		$selected = 0;
		if (in_array($object->id, $arrayofselected)) $selected = 1;
		print '<input id="cb' . $object->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $object->id . '"' . ($selected ? ' checked' : '') . '>';
	}
	print '</td>';
	if (isset($totalarray['nbfield'])) {
		if (!$i) $totalarray['nbfield']++;
	}
	print '</tr>' . "\n";

	$i++;
}

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) $colspan++;
	}
	print '<tr><td colspan="' . $colspan . '" class="opacitymedium">' . $langs->trans("NoArticledFound") . '</td></tr>';
}

$db->free($resql);
$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>' . "\n";
print '</div>' . "\n";

print '</form>' . "\n";

if (in_array('builddoc', $arrayofmassactions) && ($nbTotalOfRecords === '' || $nbTotalOfRecords)) {
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty = 0;

	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'] . '?sortfield=' . $sortfield . '&sortorder=' . $sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $permissiontoread;
	$delallowed = $permissiontoadd;

	print $formfile->showdocuments('massfilesarea_lareponse', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}// End of page
llxFooter();
$db->close();
