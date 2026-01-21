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
 *   	\file       tag_list.php
 *		\ingroup    lareponse
 *		\brief      List page for tag
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
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
dol_include_once('/lareponse/lib/lareponse_tag.lib.php');
// load lareponse libraries
require_once __DIR__.'/class/tag.class.php';

// for other modules
//dol_include_once('/othermodule/class/otherobject.class.php');

// Load translation files required by the page
$langs->loadLangs(array("lareponse@lareponse", "other"));

$action     = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$show_files = GETPOST('show_files', 'int'); // Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'taglist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$id = GETPOST('id', 'int');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new Tag($db);
$diroutputmassaction = $conf->lareponse->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('taglist')); // Note that conf->hooks_modules contains array

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) $sortfield = 't.rowid'; // Set here default search field. By default 1st field in definition.
if (!$sortorder) $sortorder = "ASC";

// Security check
if (empty($conf->lareponse->enabled)) accessforbidden('Module not enabled');
$socid = 0;
if ($user->socid > 0) {	// Protection if external user
	//$socid = $user->socid;
	accessforbidden();
}
//$result = restrictedArea($user, 'lareponse', $id, '');

// Initialize array of search criterias
$search_all=trim(GETPOST("search_all", 'alpha'));
$search=array();
foreach ($object as $key => $val) {
	if (!in_array($key, array('id', 'label', 'description', 'color', 'type'))) continue;
	if (GETPOST('search_'.$key, 'alpha') !== '') $search[$key]=GETPOST('search_'.$key, 'alpha');
}

// Definition of fields for list
$arrayfields=array();
foreach ($object as $key => $val) {
	if (!in_array($key, array('id', 'label', 'description', 'color', 'type'))) continue;
	$currentpos = 0;
	switch ($key) {
		case 'label':
			$currentpos = 0;
			break;
		case 'color':
			$currentpos = 30;
			break;
		case 'description':
			$currentpos = 50;
			break;
		case 'type':
			$currentpos = 100;
			break;
	}
	if ($key != 'id') $arrayfields['t.'.$key] = array('label'=>$key, 'checked'=>1, 'enabled'=>1, 'position'=>$currentpos);
	else $arrayfields['t.rowid'] = array('label'=>$key, 'checked'=>1, 'enabled'=>1, 'position'=>$currentpos);
}

$arrayfields = dol_sort_array($arrayfields, 'position');

$fieldstosearchall = $arrayfields;

$permissiontoread = $user->rights->lareponse->tag->read;
$permissiontoadd = $user->rights->lareponse->tag->write;
$permissiontodelete = $user->rights->lareponse->tag->delete;

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		foreach ($object as $key => $val) {
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
	$objectclass = 'tag';
	$objectlabel = 'tag';
	$uploaddir = $conf->lareponse->dir_output;

	if ((float) DOL_VERSION < 11) {
		// [#104] Add linked object check if the LA_REPONSE_PROTECT_TAG_DELETION param is set
		if ($action == 'delete') {  // TODO delete all article tag linked (table: lareponse_article_tag)
			deleteTag();
		} else {
			include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
		}
	} else {
		// [#104] Add linked object check if the LA_REPONSE_PROTECT_TAG_DELETION param is set
		if ($action == 'delete') {
			if ($conf->global->LA_REPONSE_PROTECT_TAG_DELETION == 1) {
				$id = GETPOST('toselect', 'array');
				$linkedObjects = getAllArticleLinked($id);
				foreach ($linkedObjects as $linkedObject) {
					$linkedObjectsInt = intval($linkedObject->count);
					if ($linkedObjectsInt > 0) {
						setEventMessage($langs->trans('DeleteErrorTagLinkedToObject'), 'errors');
						header('Location: ' . $_SERVER['PHP_SELF']);
						exit;
					}
					// if no tag is linked to an article so delete the tag
					deleteTag();
				}
			} else {
				// if $conf->global->LA_REPONSE_PROTECT_TAG_DELETION == 0 so delete the tag
				deleteTag();
			}
		} else {
			include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
		}
	}
}



/*
 * View
 */

$form = new Form($db);

$now = dol_now();

//$help_url="EN:Module_tag|FR:Module_tag_FR|ES:Módulo_tag";
$help_url = '';
$title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("tags"));


// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT ';
foreach ($object as $key => $val) {
	if (in_array($key, array('id', 'label', 'description', 'color', 'visible', 'type', 'entity'))) {
		if ($key == 'id') $sql .= 't.rowid, ';
		else $sql .= 't.'.$key.', ';
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
$sql = preg_replace('/,\s*$/', '', $sql);
$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";

if ($object->ismultientitymanaged == 1) $sql .= " WHERE t.entity IN (".getEntityLareponse($object->element).")";
else $sql .= " WHERE 1 = 1";

// Modify sql query if searches are made
$sqlsearch = '';
foreach ($search as $key => $val) {
	if ($key == 'status' && $search[$key] == -1) continue;
	$mode_search = (($object->isInt($search[$key]) || $object->isFloat($search[$key])) ? 1 : 0);

	if (strpos($key, 'type') === 0) {
		if ($search[$key] == '-1') $search[$key] = '';
		$mode_search = 2;
	}
	if ($search[$key] != '') $sqlsearch .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
}

// Get labels from Dolibarr and GestionParc
$categoriemaybedisp = Tag::mayTagBeDisplayed('categorie');
$gestionparcmaybedisp = Tag::mayTagBeDisplayed('gestionparc');
if ($categoriemaybedisp || $gestionparcmaybedisp) {
	if (!$categoriemaybedisp && $gestionparcmaybedisp) $sql .= ' AND (type = '.Tag::GESTIONPARC_TAG_TYPE.' OR type = '.Tag::LAREPONSE_TAG_TYPE.')'.$sqlsearch;
	elseif (!$gestionparcmaybedisp && $categoriemaybedisp) $sql .= ' AND type != '.Tag::GESTIONPARC_TAG_TYPE.$sqlsearch;
} else $sql .= ' AND type = '.Tag::LAREPONSE_TAG_TYPE.$sqlsearch;
$sql .= $sqlsearch;
if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= $db->order($sortfield, $sortorder);
// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
		$page = 0;
		$offset = 0;
	}
}
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit))) {
	$num = $nbtotalofrecords;
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
	header("Location: ".dol_buildpath('/lareponse/tag_card.php', 1).'?id='.$id);
	exit;
}



// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);


$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
foreach ($search as $key => $val) {
	if (is_array($search[$key]) && count($search[$key])) foreach ($search[$key] as $skey) $param .= '&search_'.$key.'[]='.urlencode($skey);
	else $param .= '&search_'.$key.'='.urlencode($search[$key]);
}
if ($optioncss != '')     $param .= '&optioncss='.urlencode($optioncss);

// List of mass actions available
$arrayofmassactions = array(
	//'validate'=>$langs->trans("Validate"),
	//'generate_doc'=>$langs->trans("ReGeneratePDF"),
	//'builddoc'=>$langs->trans("PDFMerge"),
	//'presend'=>$langs->trans("SendByMail"),
);
if ($permissiontodelete) $arrayofmassactions['predelete'] = '<span class="fas fa-trash-alt paddingrightonly"></span>'.$langs->trans("Delete");
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
if ((float) DOL_VERSION >= 11) {
	print '<input type="hidden" name="token" value="' . newToken() . '">';
} else {
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
}
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

//$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/lareponse/tag_card.php', 1).'?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '');
if ((float) DOL_VERSION >= '10.0')
	$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/lareponse/tag_card.php', 1).'?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);
else {
	$newcardbutton = '<a class="btnTitle" href="' . DOL_URL_ROOT . '/custom/lareponse/tag_card.php?action=create&amp;backtopage=%2Fcustom%2Flareponse%2Farticle_list.php" id="" >';
	$newcardbutton .= '<span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span><span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">' . $langs->trans('New') . '</span></a>';
}


print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'lareponse_black_50@lareponse', 0, $newcardbutton, '', $limit);

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "SendtagRef";
$modelmail = "tag";
$trackid = 'xxxx'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";


// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($object->fields as $key => $val) {
	if (!in_array($key, array('label', 'description', 'color', 'type'))) continue;
	switch ($key) {
		case 'label':
			$cssforfield = 'left';
			break;
		case 'color':
		case 'description':
			$cssforfield = 'center';
			break;
		case 'type':
			$cssforfield = 'right';
			break;
	}
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	if (!empty($arrayfields['t.'.$key]['checked'])) {
		print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').'">';
		// Color field shouldn't be counted as a search field
		if ($key != 'color') {
			if (is_array($val['arrayofkeyval'])) print $form->selectarray('search_'.$key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
			elseif (strpos($val['type'], 'integer:') === 0) {
				print $object->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
			} elseif ($key == 'type') print $form->selectarray('search_type', Tag::$MAP_TAG_TYPES, '', 1, 0, 0, '', 1);
			elseif (! preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
		}
		print '</td>';
	}
}

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>'."\n";


// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($object->fields as $key => $val) {
	if (!in_array($key, array('label', 'description', 'color', 'type'))) continue;
	switch ($key) {
		case 'label':
			$cssforfield = 'left';
			break;
		case 'color':
		case 'description':
			$cssforfield = 'center';
			break;
		case 'type':
			$cssforfield = 'right';
			break;
	}
	if (!empty($arrayfields['t.'.$key]['checked'])) {
		print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
	}
}
// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
print '</tr>'."\n";


// Loop on record
// --------------------------------------------------------------------
$i = 0;
$totalarray = array();
while ($i < ($limit ? min($num, $limit) : $num)) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) break; // Should not happen

	$currcolor = $obj->color;
	// In case the module Categorie is enabled, check if the current tag is a Lareponse one
	$islareponsetag = false;
	$iscategoriestag = false;
	$isgestionparctag = false;
	switch ($obj->type) {
		case Tag::GESTIONPARC_TAG_TYPE:
			$isgestionparctag = true;
			break;
		case Tag::LAREPONSE_TAG_TYPE:
			$islareponsetag = true;
			break;
		default:
			$iscategoriestag = true;
			break;
	}
	// Verify when the current tag type is not Lareponse if it can be displayed
	if (($isgestionparctag && !$gestionparcmaybedisp) || ($iscategoriestag && !$categoriemaybedisp)) continue;
	// Some colors added by categories module may be empty or missing a '#'. We're here changing the string so that it matches a #XXXXXX pattern or becomes black if empty
	mb_substr($obj->color, 0, 1) == '#' ? $currcolor = $obj->color : $currcolor = '#'.$obj->color;
	if (strlen($currcolor) == 1) {
		$currcolor .= '000000';
	}
	$object->fetch($obj->rowid);
	// Prepare background colors for the tags. Those who don't have colors will be black
	if ($islareponsetag) $tagtype = ' Lareponse';
	else if ($isgestionparctag) $tagtype = ' GestionParc';
	else $tagtype = ' '.$langs->trans(Categorie::$MAP_ID_TO_CODE[$obj->type]);
	// Show here line of result
	print '<tr class="oddeven">';
	foreach ($object->fields as $key => $val) {
		if (!in_array($key, array('id', 'label', 'description', 'color', 'type'))) continue;

		if (!empty($arrayfields['t.'.$key]['checked'])) {
			switch ($key) {
				case 'label':
					$cssforfield = 'left';
					break;
				case 'color':
				case 'description':
					$cssforfield = 'center';
					break;
				case 'type':
					$cssforfield = 'right';
					break;
			}
			print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';
			if ($key == 'status') print $object->getLibStatut(5);
			else if ($key == 'label') {
				print '<span class="badge in-list-badge" style="background-color: '.$currcolor.';">';
				print $object->getNomUrl(0);
				print '</span>';
			} else if ($key == 'color') {
				print '<span class="badge in-list-badge classfortooltip ';
				// We format the color from hex format (#FFFFFF) to hex format without the '#' char (FFFFFF)
				$formatedColor = substr($currcolor, 1 - strlen($currcolor));
				// We want the text to be visible. In accordance to the tag's background color, the text will be black or white
				print (colorIsLight($formatedColor) ? 'categtextblack' : 'categtextwhite');
				print '" style="background-color:'.$currcolor.';">';
				print $currcolor;
				print '</span>';
			} else if ($key == 'type') print $tagtype;
			else if ($key == 'description') print $object->description;
			else print $object->showOutputField($val, $key, $object->$key, '');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
			if (!empty($val['isameasure'])) {
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.'.$key;
				$totalarray['val']['t.'.$key] += $object->$key;
			}
		}
	}
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields, 'object'=>$object, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print '<td class="nowrap center">';
	if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		$selected = 0;
		if (in_array($object->id, $arrayofselected)) $selected = 1;
		print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked' : '').'>';
	}
	print '</td>';
	if (!$i) $totalarray['nbfield']++;

	print '</tr>'."\n";

	$i++;
}

// Show total line
//include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}


$db->free($resql);

$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

if (in_array('builddoc', $arrayofmassactions) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty = 0;

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $permissiontoread;
	$delallowed = $permissiontoadd;

	print $formfile->showdocuments('massfilesarea_lareponse', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();
