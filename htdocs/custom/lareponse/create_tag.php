<?php
/* Copyright (C) 2005		Ayoub Bayed	<ayoub@code42.fr>
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
 *		\file      lareponse/create_tag.php
 *		\ingroup    tag
 *		\brief      Page to create a new tag
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
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

global $langs, $conf, $db, $user;
// Load translation files required by the page
$langs->load("categories");

// Security check
$socid = (int) GETPOST('socid', 'int');
if (!$user->rights->categorie->lire) accessforbidden();

$action		= GETPOST('action', 'alpha');
$cancel		= GETPOST('cancel', 'alpha');
$origin		= GETPOST('origin', 'alpha');
$catorigin  = (int) GETPOST('catorigin', 'int');
$type       = GETPOST('type', 'aZ09');
$urlfrom	= GETPOST('urlfrom', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$label = (string) GETPOST('label', 'alphanohtml');
$description = (string) GETPOST('description', 'restricthtml');
$color = preg_replace('/[^0-9a-f#]/i', '', (string) GETPOST('color', 'alphanohtml'));
$visible = (int) GETPOST('visible', 'int');
$parent = (int) GETPOST('parent', 'int');

if ($origin) {
	if ($type == Categorie::TYPE_PRODUCT)     $idProdOrigin     = $origin;
	if ($type == Categorie::TYPE_SUPPLIER)    $idSupplierOrigin = $origin;
	if ($type == Categorie::TYPE_CUSTOMER)    $idCompanyOrigin  = $origin;
	if ($type == Categorie::TYPE_MEMBER)      $idMemberOrigin   = $origin;
	if ($type == Categorie::TYPE_CONTACT)     $idContactOrigin  = $origin;
	if ($type == Categorie::TYPE_PROJECT)     $idProjectOrigin  = $origin;
}

if ($catorigin && $type == Categorie::TYPE_PRODUCT) $idCatOrigin = $catorigin;

$object = new Categorie($db);

$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('categorycard'));

$error = 0;


/*
 *	Actions
 */

// Add action
if ($action == 'add' && $user->rights->categorie->creer) {
	// Action ajout d'une categorie
	if ($cancel) {
		if ($urlfrom) {
			header("Location: ".$urlfrom);
			exit;
		} elseif ($backtopage) {
			header("Location: ".$backtopage);
			exit;
		} elseif ($idProdOrigin) {
			header("Location: ".dol_buildpath('/lareponse/card_tag.php?id='.$idProdOrigin.'&type='.$type, 1));
			exit;
		} elseif ($idCompanyOrigin) {
			header("Location: ".dol_buildpath('/lareponse/card_tag.php?id='.$idCompanyOrigin.'&type='.$type, 1));
			exit;
		} elseif ($idSupplierOrigin) {
			header("Location: ".dol_buildpath('/lareponse/card_tag.php?id='.$idSupplierOrigin.'&type='.$type, 1));
			exit;
		} elseif ($idMemberOrigin) {
			header("Location: ".dol_buildpath('/lareponse/card_tag.php?id='.$idMemberOrigin.'&type='.$type, 1));
			exit;
		} elseif ($idContactOrigin) {
			header("Location: ".dol_buildpath('/lareponse/card_tag.php?id='.$idContactOrigin.'&type='.$type, 1));
			exit;
		} elseif ($idProjectOrigin) {
			header("Location: ".dol_buildpath('/lareponse/card_tag.php?id='.$idProjectOrigin.'&type='.$type, 1));
			exit;
		} else {
			header("Location: ".DOL_URL_ROOT.'/lareponse/tagstree.php?leftmenu=cat&type='.$type);
			exit;
		}
	}



	$object->label			= $label;
	$object->color			= $color;
	$object->description = dol_htmlcleanlastbr($description);
	$object->socid			= ($socid > 0 ? $socid : 0);
	$object->visible = $visible;
	$object->type = $type;

	if ($parent != "-1") $object->fk_parent = $parent;

	$ret = $extrafields->setOptionalsFromPost(null, $object);
	if ($ret < 0) $error++;

	if (!$object->label) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
		$action = 'create';
	}

	// Create category in database
	if (!$error) {
		$result = $object->create($user);
		if ($result > 0) {
			$action = 'confirmed';
			$_POST["addcat"] = '';
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

// Confirm action
if (($action == 'add' || $action == 'confirmed') && $user->rights->categorie->creer) {
	// Action confirmation de creation categorie
	if ($action == 'confirmed') {
		if ($urlfrom) {
			header("Location: ".$urlfrom);
			exit;
		} elseif ($backtopage) {
			header("Location: ".$backtopage);
			exit;
		} elseif ($idProdOrigin) {
			header("Location: ".dol_buildpath('/lareponse/card_tag.php?id='.$idProdOrigin.'&type='.$type.'&mesg='.urlencode($langs->trans("CatCreated")), 1));
			exit;
		} elseif ($idCompanyOrigin) {
			header("Location: ".dol_buildpath('/lareponse/card_tag.php?id='.$idCompanyOrigin.'&type='.$type.'&mesg='.urlencode($langs->trans("CatCreated")), 1));
			exit;
		} elseif ($idSupplierOrigin) {
			header("Location: ".dol_buildpath('/lareponse/card_tag.php?id='.$idSupplierOrigin.'&type='.$type.'&mesg='.urlencode($langs->trans("CatCreated")), 1));
			exit;
		} elseif ($idMemberOrigin) {
			header("Location: ".dol_buildpath('/lareponse/card_tag.php?id='.$idMemberOrigin.'&type='.$type.'&mesg='.urlencode($langs->trans("CatCreated")), 1));
			exit;
		} elseif ($idContactOrigin) {
			header("Location: ".dol_buildpath('/lareponse/card_tag.php?id='.$idContactOrigin.'&type='.$type.'&mesg='.urlencode($langs->trans("CatCreated")), 1));
			exit;
		} elseif ($idProjectOrigin) {
			header("Location: ".dol_buildpath('/lareponse/card_tag.php?id='.$idProjectOrigin.'&type='.$type.'&mesg='.urlencode($langs->trans("CatCreated")), 1));
			exit;
		}

		header("Location: ".dol_buildpath('/lareponse/card_tag.php?id='.$result.'&type='.$type, 1));
		exit;
	}
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

$helpurl = '';
llxHeader("", $langs->trans("Categories"), $helpurl);

if ($user->rights->categorie->creer) {
	// Create or add
	if ($action == 'create' || GETPOST("addcat") == 'addcat') {
		dol_set_focus('#label');

		print '<form action="'.$_SERVER['PHP_SELF'].'?type='.$type.'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="urlfrom" value="'.$urlfrom.'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="addcat" value="addcat">';
		print '<input type="hidden" name="id" value="'.GETPOST('origin', 'alpha').'">';
		print '<input type="hidden" name="type" value="'.$type.'">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		if ($origin) print '<input type="hidden" name="origin" value="'.$origin.'">';
		if ($catorigin)	print '<input type="hidden" name="catorigin" value="'.$catorigin.'">';

		print load_fiche_titre($langs->trans("CreateCat"));

		print dol_get_fiche_head('');

		print '<table width="100%" class="border">';

		// Ref
		print '<tr>';
		print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td><input id="label" class="minwidth100" name="label" value="'.dol_escape_htmltag($label).'">';
		print'</td></tr>';

		// Description
		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$fckEditorEnabled = isset($conf->fckeditor) ? $conf->fckeditor->enabled : false;
		$doleditor = new DolEditor('description', $description, '', 160, 'dolibarr_notes', '', false, true, $fckEditorEnabled, ROWS_5, '90%');
		$doleditor->Create();
		print '</td></tr>';

		// Color
		print '<tr><td>'.$langs->trans("Color").'</td><td>';
		print $formother->selectColor($color, 'color');
		print '</td></tr>';

		// Parent category
		print '<tr><td>'.$langs->trans("AddIn").'</td><td>';
		print $form->select_all_categories($type, $catorigin, 'parent');
		print ajax_combobox('parent');
		print '</td></tr>';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if (empty($reshook)) {
			print $object->showOptionals($extrafields, 'edit', $parameters);
		}

		print '</table>';

		print dol_get_fiche_end('');

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("CreateThisCat").'" name="creation" />';
		print '&nbsp; &nbsp; &nbsp;';
		print '<input type="submit" class="button button-cancel" value="'.$langs->trans("Cancel").'" name="cancel" />';
		print '</div>';

		print '</form>';
	}
}

// End of page
llxFooter();
$db->close();
