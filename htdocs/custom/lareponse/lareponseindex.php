<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       lareponse/lareponseindex.php
 *	\ingroup    lareponse
 *	\brief      Home page of lareponse top menu
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('/lareponse/lib/lareponse_article.lib.php');
dol_include_once('/lareponse/class/article.class.php');
dol_include_once('/lareponse/lib/lareponse_favorites.lib.php');
dol_include_once('/lareponse/lib/lareponse.lib.php');
dol_include_once('/lareponse/class/tag.class.php');


global $db, $conf, $langs;
// Load translation files required by the page
$langs->loadLangs(array("lareponse@lareponse"));

$action = GETPOST('action', 'alpha');
$noSearch = GETPOST('nosearch', 'int');

// Security check
//if (! $user->rights->lareponse->myobject->read) accessforbidden();
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = $conf->global->LA_REPONSE_WIDGET_NUMBER ?? $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$now = dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$categstatic = new Tag($db);
$type = Tag::LAREPONSE_TAG_TYPE;
$moreparam = ($noSearch ? '&nosearch=1' : '');
$arrayofjs = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js', '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', '/lareponse/js/lareponse.js.php');
$arrayofcss = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');

llxHeader("", $langs->trans("LareponseArea"), '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre($langs->trans("LareponseArea"), '', 'object_lareponse62@lareponse');

/*
 * Demo text
 */
if ($conf->global->LAREPONSE_WIZARD_ACTIVE == 1 && !empty($conf->global->LAREPONSE_WIZARD_INDEX)) {
	print '<div class="gp-demo-div">';
	print $conf->global->LAREPONSE_WIZARD_INDEX;
	print '</div>';
	print '<div class="clearboth"></div>';
}

print '<div class="fichecenter"><div id="treetag" class="fichethirdleft">';

$cate_arbo = $categstatic->getFullArbo($type);

// Define fulltree array
$fulltree = $cate_arbo;


// Define data (format for treeview)
$data = array();
$data[] = array('rowid'=>0, 'fk_menu'=>-1, 'title'=>"racine", 'mainmenu'=>'', 'leftmenu'=>'', 'fk_mainmenu'=>'', 'fk_leftmenu'=>'');
foreach ($fulltree as $key => $val) {
	$categstatic->id = $val['id'];
	$categstatic->label = $val['label'];
	$categstatic->color = $val['color'];
	$categstatic->type = $type;

	$li = $categstatic->getNomUrl(1, '', 60, $moreparam.'&backtolist='.urlencode($_SERVER["PHP_SELF"].'?type='.$type.$moreparam));

	$desc = dol_htmlcleanlastbr($val['description']);

	$counter = '';

	if ($categstatic->color) {
		if ($categstatic->color[0] == "#") {
			$categstatic->color = substr($categstatic->color, 1);
		}
		$color = ' style="background: #'.sprintf("%06s", $categstatic->color).';"';
	} else {
		$color = ' style="background: #bbb"';
	}

	$data[] = array(
		'rowid'=>$val['rowid'],
		'fk_menu'=>$val['fk_parent'],
		'entry'=>'<table class="nobordernopadding centpercent"><tr><td><span class="noborderoncategories"'.$color.'>'.$li.'</span></td>
            <td class="right" width="20px;"><a href="'.dol_buildpath('/lareponse/card_tag.php?action=create&id='.$val['id'].'&type='.$type.$moreparam.'&backtolist='.urlencode($_SERVER["PHP_SELF"].'?type='.$type.$moreparam), 1).'";></a></td>
            </tr></table>'
	);
}

print '<table class="liste nohover" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Categories").'</td><td></td><td class="right">';
if (!empty($conf->use_javascript_ajax)) {
	print '<div id="iddivjstreecontrol"><a class="notasortlink" href="#">'.img_picto('', 'folder', 'class="paddingright"').$langs->trans("UndoExpandAll").'</a> | <a class="notasortlink" href="#">'.img_picto('', 'folder-open', 'class="paddingright"').$langs->trans("ExpandAll").'</a></div>';
}
print '</td></tr>';
$nbofentries = (count($data) - 1);

if ($nbofentries > 0) {
	print '<tr class="pair"><td colspan="3">';
	tree_recur($data, $data[0], 0, "iddivjstree");
	print '</td></tr>';
} else {
	print '<tr class="pair">';
	print '<td colspan="3"><table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('', 'treemenu/branchbottom.gif').'</td>';
	print '<td valign="middle">';
	print $langs->trans("NoCategoryYet");
	print '</td>';
	print '<td>&nbsp;</td>';
	print '</table></td>';
	print '</tr>';
}

print "</table>";

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

if (! empty($conf->lareponse->enabled) && $user->rights->lareponse->article->read) {
	$articles = getLastArticlesUpdated($max, $user);
	$num = count($articles);

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="4">'.$langs->trans("LastArticlesUpdated", $max).($num ? '&nbsp;<span class="badge marginleftonlyshort">'.$num.'</span>' : '').'</th></tr>';

	if (!empty($articles)) {
		foreach ($articles as $article) {
			$obj = new Article($db);
			$obj->id = $article->rowid;
			$obj->tms = $article->dateu;
			$obj->content = $article->content;
			$obj->title = $article->title;
			$obj->fk_user_creat = $article->author;
			$obj->fk_user_modif = $article->update_author;
			if ($obj->fk_user_modif > 0) $updatedContributorId = $obj->fk_user_modif;
			else $updatedContributorId = $obj->fk_user_creat;
			$obj->private = $article->private;
			// Article name
			print '<tr><td>';
			print $obj->getNomUrl(1);
			if (favoriteExists($user->id, $obj->id) == 1) print ' <i class="LaReponse fa fa-star"></i>';
			print '</td>';
			// User name
			print '<td class="nowrap">';
			$commentUser = new User($db);
			$commentUser->fetch($updatedContributorId);
			//print $commentUser->getNomUrl(-1);
			print activeContributorUrl($commentUser, -1);
			print '</td>';
			// Private label
			print '<td class="nowrap">';
			if ($obj->private == 0)
				print $langs->trans('Public');
			else print $langs->trans('Private');
			print '</td>';
			// Creation date
			print '<td class="right" class="nowrap">'.dol_print_date($db->jdate($obj->tms), 'dayhour', 'tzuserrel').'</td></tr>';
		}
	} else {
		// Print no data image
		print '<tr class="oddeven"><td colspan="3">';
		print '<div class="noDataContainer">';
		print '<div class="noDataIcon"><img src="'.dol_buildpath('/lareponse/img/no_data.svg', 1).'"></div>';
		print '<div class="noDataText">'.$langs->trans("NoArticle").'</div>';
		print '</div>';
		print '</td></tr>';
	}
	print "</table><br>";
}

print '</div></div></div>';

/* ----------- */
/* Second row  */
/* ----------- */
print '<div class="fichecenter"><div class="fichethirdleft">';

if (! empty($conf->lareponse->enabled) && $user->rights->lareponse->article->read) {
	$contributors = getMostActiveContributor($max);
	$num = count($contributors);

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="3">'.$langs->trans("MostActiveContributor", $max).($num ? '&nbsp;<span class="badge marginleftonlyshort">'.$num.'</span>' : '').'</th></tr>';

	if (!empty($contributors)) {
		foreach ($contributors as $contributor) {
			$userObj = new User($db);
			$userObj->fetch($contributor->author);
			// User name
			print '<td class="nowrap">';
			print activeContributorUrl($userObj, -1);
			print '</td>';
			// Nb articles published
			print '<td class="nowrap">';
			print $contributor->nb_article.' '.($contributor->nb_article > 1 ? $langs->trans('articlesPublished') : $langs->trans('articlePublished'));
			print '</td>';
			print '</tr>';
		}
	} else {
		// Print no data image
		print '<tr class="oddeven"><td colspan="3">';
		print '<div class="noDataContainer">';
		print '<div class="noDataIcon"><img src="'.dol_buildpath('/lareponse/img/no_contributor.svg', 1).'"></div>';
		print '<div class="noDataText">'.$langs->trans("NoContributor").'</div>';
		print '</div>';
		print '</td></tr>';
	}
	print "</table><br>";
}

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

/**
 * Favorites addon
 */
if (! empty($conf->lareponse->enabled) && $user->rights->lareponse->article->read) {
	$favorites = getUserFavoriteArticles($user->id);
	$num = count($favorites);

	// Get articles from favorites ids

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="4">'.$langs->trans("MyFavoritesArticles", $max).($num ? '&nbsp;<span class="badge marginleftonlyshort">'.$num.'</span>' : '').'</th></tr>';

	if (!empty($favorites)) {
		foreach ($favorites as $favorite) {
			$obj = new Article($db);
			$obj->id = $favorite->rowid;
			$obj->date_creation = $favorite->dateu;
			$obj->content = $favorite->content;
			$obj->title = $favorite->title;
			$obj->fk_user_creat = $favorite->author;
			$obj->private = $favorite->private;
			// Article name
			print '<tr><td>';
			print $obj->getNomUrl(1);
			print '</td>';
			// User name
			print '<td class="nowrap">';
			$commentUser = new User($db);
			$commentUser->fetch($obj->fk_user_creat);
			print activeContributorUrl($commentUser, -1);
			print '</td>';
			// Private label
			print '<td class="nowrap">';
			if ($obj->private == 0)
				print $langs->trans('Public');
			else print $langs->trans('Private');
			print '</td>';
			// Creation date
			print '<td class="right" class="nowrap">'.dol_print_date($db->jdate($obj->date_creation), '%d/%m/%y %H:%M').'</td></tr>';
		}
	} else {
		// Print no data image
		print '<tr class="oddeven"><td colspan="3">';
		print '<div class="noDataContainer">';
		print '<div class="noFavIcon"><img src="'.dol_buildpath('/lareponse/img/no_favorite.svg', 1).'"></div>';
		print '<div class="noDataText">'.$langs->trans("NoFavorite").'</div>';
		print '</div>';
		print '</td></tr>';
	}
	print "</table><br>";
}
print '</div></div></div>';

// End of page
llxFooter();
$db->close();
