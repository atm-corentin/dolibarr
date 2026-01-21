<?php
/* Copyright (C) 2022       Ayoub Bayed   <ayoub@code42.fr>

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
 *      \file       htdocs/categories/index.php
 *      \ingroup    category
 *      \brief      Home page of category area
 */


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

require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('/lareponse/class/tag.class.php');

// Load translation files required by the page
$langs->load("categories");

if (!$user->rights->lareponse->article->read) accessforbidden();

$id = GETPOST('id', 'int');
$catname = GETPOST('catname', 'alpha');
$nosearch = GETPOST('nosearch', 'int');

$categstatic = new Tag($db);

//if (is_numeric($type)) $type = Categorie::$MAP_ID_TO_CODE[$type]; // For backward compatibility

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('categoryindex'));

/*
 * View
 */

$form = new Form($db);

$moreparam = ($nosearch ? '&nosearch=1' : '');

// Specified to lareponse tag type
$type = Tag::LAREPONSE_TAG_TYPE;

$arrayofjs = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js', '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
$arrayofcss = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');

$title = $langs->trans('LareponseTagArea');
llxHeader('', $title, '', '', 0, 0, $arrayofjs, $arrayofcss);

$newcardbutton = '';
if (!empty($user->rights->categorie->creer)) {
	$url = dol_buildpath('lareponse/create_tag.php', 1);
	$url .= '?action=create';
	$url .= '&type='.$type;
	$url .= '&backtopage='.urlencode($_SERVER["PHP_SELF"].'?type='.$type.'&id='.$id);
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewCategory'), '', 'fa fa-plus-circle', $url);
}

print load_fiche_titre($title, $newcardbutton, 'object_category');

// Search categories
if (empty($nosearch)) {
	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?type='.$type.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="type" value="'.$type.'">';
	print '<input type="hidden" name="nosearch" value="'.$nosearch.'">';

	print '<table class="noborder nohover centpercent">';
	print '<tr class="liste_titre">';
	print '<td colspan="3">'.$langs->trans("Search").'</td>';
	print '</tr>';
	print '<tr class="oddeven nohover"><td>';
	print $langs->trans("Name").':</td><td><input class="flat inputsearch" type="text" name="catname" value="'.$catname.'"/></td><td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
	print '</table></form>';

	print '</div><div class="fichehalfright">';


	/*
	 * Categories found
	 */
	if ($catname || $id > 0) {
		$cats = $categstatic->rechercher($id, $catname, $type);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("FoundCats").'</td></tr>';

		foreach ($cats as $cat) {
			if ($categstatic->color) {
				if ($categstatic->color[0] == "#") {
					$categstatic->color = substr($categstatic->color, 1);
				}
				$color = ' style="background: #'.$categstatic->color.';"';
			} else {
				$color = ' style="background: #bbb"';
			}
			print "\t".'<tr class="oddeven">'."\n";
			print "\t\t<td>";
			$categstatic->id = $cat->id;
			$categstatic->ref = $cat->label;
			$categstatic->label = $cat->label;
			$categstatic->type = $cat->type;
			$categstatic->color = $cat->color;
			print '<span class="noborderoncategories"'.$color.'>';
			print $categstatic->getNomUrl(1, '');
			print '</span>';
			print "</td>\n";
			print "\t\t<td>";
			print dolGetFirstLineOfText($cat->description);
			print "</td>\n";
			print "\t</tr>\n";
		}
		print "</table>";
	} else print '&nbsp;';

	print '</div></div>';
}

print '<div class="fichecenter"><br>';

// Charge tableau des categories
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
            <td class="right" width="20px;"><a href="'.dol_buildpath('/lareponse/card_tag.php?action=create&id='.$val['id'].'&type='.$type.$moreparam.'&backtolist='.urlencode($_SERVER["PHP_SELF"].'?type='.$type.$moreparam), 1).'";>'.img_view().'</a></td>
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
	tree_recur($data, $data[0], 0);
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

print '</div>';

// End of page
llxFooter();
$db->close();
