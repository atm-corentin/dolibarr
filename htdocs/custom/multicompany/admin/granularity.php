<?php
/* Copyright (C) 2022-2024	Regis Houssin	<regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       /multicompany/admin/granularity.php
 *  \ingroup    multicompany
 *  \brief      Management of sharings by element
 */

$res=@include("../../main.inc.php");						// For root directory
if (empty($res) && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php")) {
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
}
if (empty($res)) {
	$res=@include("../../../main.inc.php");			// For "custom" directory
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/multicompany/class/actions_multicompany.class.php', 'ActionsMulticompany');

$langs->loadLangs(array('admin', 'multicompany@multicompany'));

// Security check
if (empty($conf->global->MULTICOMPANY_SHARING_BYELEMENT_ENABLED) || empty($user->admin) || !empty($user->entity)) {
	accessforbidden();
}

$action = GETPOST('action','alpha');

$object = new ActionsMulticompany($db);


/*
 * Action
 */


/*
 * View
 */

$extrajs = array(
	'/multicompany/core/js/lib_head.js'
);

$help_url='EN:Module_MultiCompany|FR:Module_MultiSoci&eacute;t&eacute;';
llxHeader('', $langs->trans("MultiCompanySharingsByElementSetup"), $help_url,'','','',$extrajs);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MultiCompanySetup"), $linkback, 'fa-globe', 0, 'multicompany_title');

$head = multicompany_prepare_head();
dol_fiche_head($head, 'granularity', $langs->trans("ModuleSetup"), -1);

$level = checkMultiCompanyVersion();
if ($level === -1) { // shouldn't happen
	print '<div class="multicompany_checker">';
	dol_htmloutput_mesg($langs->trans("DolibarrIsOlderThanMulticompany"), '', 'warning', 1);
	print '</div>';
}

$form = new Form($db);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';

$text = img_picto('', 'info','class="linkobject"');
$htmltext = $langs->trans("ShareAllByDefaultInfo");
print '<td align="center" width="200">'.$langs->trans("ShareAllByDefault").' '.$form->textwithtooltip('', $htmltext, 2, 1, $text).'</td>'."\n";

print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

/*
 * System parameters
 */

// Thirdparty granularity sharing
print '<tr id="thirdpartygranularity" class="oddeven">';
print '<td><span class="fa fa-building"></span><span class="multiselect-title">'.$langs->trans("EnableGranularityForThirdparties").'</span></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="200">';
print '<div id="thirdpartyshareallbydefault"'.(empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED) ? ' style="display:none;"' : '').'>';
$input = array(
	'hide' => array(
		'del_MULTICOMPANY_CONTACT_SHARE_ALL_BY_DEFAULT'
	),
	'hideshow' => array(
		'set_MULTICOMPANY_CONTACT_SHARE_ALL_BY_DEFAULT'
	),
	'del' => array(
		'MULTICOMPANY_CONTACT_SHARE_ALL_BY_DEFAULT'
	)
);
print ajax_mcconstantonoff('MULTICOMPANY_THIRDPARTY_SHARE_ALL_BY_DEFAULT', $input, 0);
print '</div';
print '</td>';
print '<td align="center" width="100">';
$input = array(
	'showhide' => array(
		'#thirdpartyshareallbydefault',
		'#contactgranularity'
	),
	'hide' => array(
		'#contactshareallbydefault'
	),
	'set' => array(
		'MULTICOMPANY_THIRDPARTY_SHARING_ENABLED'
	),
	'del' => array(
		'MULTICOMPANY_THIRDPARTY_SHARE_ALL_BY_DEFAULT',
		'MULTICOMPANY_CONTACT_SHARING_BYELEMENT_ENABLED',
		'MULTICOMPANY_CONTACT_SHARE_ALL_BY_DEFAULT'
	)
);
print ajax_mcconstantonoff('MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED', $input, 0);
print '</td></tr>';

// Contact granularity sharing
print '<tr id="contactgranularity" class="oddeven"'.(empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_BYELEMENT_ENABLED) ? ' style="display:none;"' : '').'>';
print '<td><span class="fa fa-users"></span><span class="multiselect-title">'.$langs->trans("EnableGranularityForContacts").'</span></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="200">';
print '<div id="contactshareallbydefault"'.(empty($conf->global->MULTICOMPANY_CONTACT_SHARING_BYELEMENT_ENABLED) ? ' style="display:none;"' : '').'>';
$input = array(
	'show' => array(
		'del_MULTICOMPANY_THIRDPARTY_SHARE_ALL_BY_DEFAULT'
	),
	'hideshow' => array(
		'set_MULTICOMPANY_THIRDPARTY_SHARE_ALL_BY_DEFAULT'
	),
	'set' => array(
		'MULTICOMPANY_THIRDPARTY_SHARE_ALL_BY_DEFAULT'
	)
);
print ajax_mcconstantonoff('MULTICOMPANY_CONTACT_SHARE_ALL_BY_DEFAULT', $input, 0);
print '</div';
print '</td>';
print '<td align="center" width="100">';
$input = array(
	'showhide' => array(
		'#contactshareallbydefault'
	),
	'del' => array(
		'MULTICOMPANY_CONTACT_SHARE_ALL_BY_DEFAULT'
	)
);
print ajax_mcconstantonoff('MULTICOMPANY_CONTACT_SHARING_BYELEMENT_ENABLED', $input, 0);
print '</td></tr>';

// Product/Service granularity sharing
print '<tr id="productgranularity" class="oddeven">';
print '<td><span class="fa fa-cube"></span><span class="multiselect-title">'.$langs->trans("EnableGranularityForProducts").'</span></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="200">';
print '<div id="productshareallbydefault"'.(empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_BYELEMENT_ENABLED) ? ' style="display:none;"' : '').'>';
print ajax_mcconstantonoff('MULTICOMPANY_PRODUCT_SHARE_ALL_BY_DEFAULT', '', 0);
print '</div';
print '</td>';
print '<td align="center" width="100">';
$input = array(
	'showhide' => array(
		'#productshareallbydefault'
	),
	'set' => array(
		'MULTICOMPANY_PRODUCT_SHARING_ENABLED'
	),
	'del' => array(
		'MULTICOMPANY_PRODUCT_SHARE_ALL_BY_DEFAULT'
	)
);
print ajax_mcconstantonoff('MULTICOMPANY_PRODUCT_SHARING_BYELEMENT_ENABLED', $input, 0);
print '</td></tr>';

// Category granularity sharing
print '<tr id="categorygranularity" class="oddeven">';
print '<td><span class="fa fa-paperclip"></span><span class="multiselect-title">'.$langs->trans("EnableGranularityForCategories").'</span></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="200">';
print '<div id="categoryshareallbydefault"'.(empty($conf->global->MULTICOMPANY_CATEGORY_SHARING_BYELEMENT_ENABLED) ? ' style="display:none;"' : '').'>';
print ajax_mcconstantonoff('MULTICOMPANY_CATEGORY_SHARE_ALL_BY_DEFAULT', '', 0);
print '</div';
print '</td>';
print '<td align="center" width="100">';
$input = array(
	'showhide' => array(
		'#categoryshareallbydefault'
	),
	'set' => array(
		'MULTICOMPANY_CATEGORY_SHARING_ENABLED'
	),
	'del' => array(
		'MULTICOMPANY_CATEGORY_SHARE_ALL_BY_DEFAULT'
	)
);
print ajax_mcconstantonoff('MULTICOMPANY_CATEGORY_SHARING_BYELEMENT_ENABLED', $input, 0);
print '</td></tr>';

print '</table>';

// Card end
dol_fiche_end();
// Footer
llxFooter();
// Close database handler
$db->close();
