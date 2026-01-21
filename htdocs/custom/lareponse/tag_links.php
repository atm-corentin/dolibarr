<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020      Arthur Croix         <arthur@code42.fr>
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
 *  \file       htdocs/custom/lareponse/tag_links.php
 *  \ingroup    lareponse
 *  \brief      Tab for articles linked to tag
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/lareponse/class/article.class.php');
dol_include_once('/lareponse/class/tag.class.php');
dol_include_once('/lareponse/lib/lareponse.lib.php');
dol_include_once('/lareponse/lib/lareponse_tag.lib.php');

// Load traductions files requiredby by page
$langs->loadLangs(array("lareponse@lareponse","companies","other"));


// Get parameters
$action=GETPOST('action', 'aZ09');
$confirm=GETPOST('confirm');
$id=(GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int'));
$ref = GETPOST('ref', 'alpha');


// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST("page", 'int');
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect))) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="a.title";

// Initialize technical objects
$article=new Article($db);
$object = new Tag($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction=$conf->mymodule->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('articlelist'));     // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('article');

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

//if ($id > 0 || ! empty($ref)) $upload_dir = $conf->sellyoursaas->multidir_output[$object->entity] . "/packages/" . dol_sanitizeFileName($object->id);
if ($id > 0 || ! empty($ref)) $upload_dir = $conf->sellyoursaas->multidir_output[$object->entity] . "/packages/" . dol_sanitizeFileName($object->ref);

if (!doTagAndCurrentEntityMatches($object)) accessforbidden();

/*
 * Actions
 */

include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$title=$langs->trans("LareponseTag").' - '.$langs->trans("LinkedArticles");
$help_url='';
//$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);


/*
 * Show tabs
 */
if (! empty($conf->notification->enabled)) $langs->load("mails");
$head = tagPrepareHead($object);

dol_fiche_head($head, 'linkedarticles', $langs->trans("LareponseTag"), -1, $object->picto);


// Object card
// ------------------------------------------------------------
$linkback = '<a href="' .dol_buildpath('/lareponse/tag_list.php', 1) . '?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

$morehtmlref = '<div class="refidno">';

$morehtmlref .= '</div>';

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'label', $morehtmlref);

/*
 * Article List Title
 */

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';

print '<div class="titre">'.$langs->trans('TagLinkedArticlesList').'</div>';

/*
 * Article Listing
 */

// Initialize array of search criterias
$search_all=trim(GETPOST("search_all", 'alpha'));
$search=array();
foreach ($article->fields as $key => $val) {
	if ($key == 'private' && $val == '-1') continue;
	if (GETPOST('search_'.$key, 'alpha') !== '') $search[$key]=GETPOST('search_'.$key, 'alpha');
}

// Prepare Form
print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
if ((float) DOL_VERSION >= 11) {
	print '<input type="hidden" name="token" value="' . newToken() . '">';
} else {
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
}
print '<input type="hidden" name="id" value="'.$id.'">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

// Definition of fields for list
$arrayfields=array();
foreach ($article->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['a.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	foreach ($article as $key => $val) {
		$search[$key] = '';
	}
	$toselect = '';
	$search_array_options = array();
}

// Prepare search criteria
$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
foreach ($search as $key => $val) {
	if (is_array($search[$key]) && count($search[$key])) foreach ($search[$key] as $skey) $param .= '&search_'.$key.'[]='.urlencode($skey);
	else $param .= '&search_'.$key.'='.urlencode($search[$key]);
}
if ($optioncss != '')     $param .= '&optioncss='.urlencode($optioncss);

if ($search_all) {
	foreach ($arrayfields as $key => $val) $arrayfields[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $arrayfields).'</div>';
}


// Modify sql query if searches are made
$sqlsearch = '';
foreach ($search as $key => $val) {
	if ($key == 'private' && $search[$key] == -1) continue;
	$mode_search = (($article->isInt($search[$key]) || $article->isFloat($search[$key])) ? 1 : 0);

	if (strpos($key, 'type') === 0) {
		if ($search[$key] == '-1') $search[$key] = '';
		$mode_search = 2;
	}
	if ($search[$key] != '') $sqlsearch .= natural_search($key, $search[$key], (($key == 'private') ? 2 : $mode_search));
}

// Recon all linked articles to the tag
$sql = 'SELECT ';
foreach ($article->fields as $key => $val) {
	$sql .= 'a.'.$key.', ';
}
$sql = preg_replace('/,\s*$/', '', $sql);
$sql.= " FROM ".MAIN_DB_PREFIX.$article->table_element." as a";
// Inner join to only get linked articles
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."lareponse_article_tag AS at";
$sql.= " ON at.fk_tag = ".GETPOST('id', 'integer')." AND at.fk_article = a.rowid";
// Multientity check
if ($object->ismultientitymanaged == 1) $sql .= " WHERE a.entity IN (".getEntity($object->element).")";
// Verify if the article is private, if so and its creater isn't the actual user, do not display it
$sql .= ' AND (a.private = 0 OR (a.private = 1 AND a.fk_user_creat = '.$user->id.'))';

$sql .= $sqlsearch;
if ($search_all) $sql .= natural_search(array_keys($arrayfields), $search_all);
if (!empty($sortfield) && !empty($sortorder)) {
	$sql .= ' ORDER BY '.$sortfield.' '.$sortorder;
}
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}
$num = $db->num_rows($resql);

$cssforfield = array(
	'title' => 'left',
	'content' => 'left',
	'private' => 'right'
);

print '<br><table class="liste">';
// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($article->fields as $key => $val) {
	$css = ($key == 'title' ? $cssforfield['title'] : '');
	$css = ($key == 'content' ? $cssforfield['content'] : '');
	$css = ($key == 'private' ? $cssforfield['private'] : '');
	if (!empty($arrayfields['a.'.$key]['checked'])) {
		print '<td class="liste_titre'.($css ? ' '.$css : '').'">';
		// Color field shouldn't be counted as a search field
		if ($key != 'color') {
			if (is_array($val['arrayofkeyval'])) {
				print $form->selectarray('search_'.$key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
			} elseif (strpos($val['type'], 'integer:') === 0) {
				print $article->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
			} elseif ($key == 'type') {
				print $form->selectarray('search_type', Tag::$MAP_TAG_TYPES, '', 1, 0, 0, '', 1);
			} elseif (! preg_match('/^(date|timestamp)/', $val['type'])) {
				print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
			}
		}
		print '</td>';
	}
}
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($article->fields as $key => $val) {
	$css = ($key == 'title' ? $cssforfield['title'] : '');
	$css = ($key == 'content' ? $cssforfield['content'] : '');
	$css = ($key == 'private' ? $cssforfield['private'] : '');
	if (!empty($arrayfields['a.'.$key]['checked'])) {
		print getTitleFieldOfList($arrayfields['a.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 'a.'.$key, '', $param.'&id='.$id, ($css ? 'class="'.$css.'"' : ''), $sortfield, $sortorder, ($css ? $css.' ' : ''))."\n";
	}
}

print '<th class="liste_titre"></th>';
// Loop on record
// --------------------------------------------------------------------
for ($i = 0; $i < $num; $i++) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) break; // Should not happen

	$article->fetch($obj->rowid);

	print '<tr class="oddeven">';
	foreach ($article->fields as $key => $val) {
		if (!empty($arrayfields['a.'.$key]['checked'])) {
			print '<td';
			print ($key == 'title' ? ' class="'.$cssforfield['title'].'"' : '');
			print ($key == 'content' ? ' class="'.$cssforfield['content'].'"' : '');
			print ($key == 'private' ? ' class="'.$cssforfield['private'].'"' : '');
			print '>';
			if ($key == 'title') print $article->getNomUrl(1);
			else if ($key == 'content') {
				if (strlen($article->$key) > 150) {
					print '<a href="'.dol_buildpath('/lareponse/article_card.php', 1).'?id='.$article->id.'&save_lastsearch_values=1"';
					print ' title="<u>'.$langs->trans('Article').'</u><br><b>'.$langs->trans('LinkToArticle').'</b>"';
					print 'class="classfortooltip">';
					print substr($article->$key, 0, 150).'...';
					print '</a>';
				} else print $article->showOutputField($val, $key, $article->$key, '');
			} else print $article->showOutputField($val, $key, $article->$key, '');
			print '</td>';
		}
	}
	print '<td></td>';
	print '</tr>';
}
print '</table>';
print '</form>';

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

print '</div>';

dol_fiche_end();


llxFooter();
$db->close();
