<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 *        \file       htdocs/sirene/admin/setup.php
 *        \ingroup    sirene
 *        \brief      Page to setup sirene module
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/sirene/lib/sirene.lib.php');
dol_include_once('/custom/sirene/class/sirene.class.php');

$langs->load("admin");
$langs->load("sirene@sirene");
$langs->load("opendsi@sirene");

if (!$user->admin) accessforbidden();
$isV14p = version_compare(DOL_VERSION, "14.0.0") >= 0;

$action = GETPOST('action', 'aZ09');


/*
 *	Actions
 */

$errors = [];
$error = 0;

if ($action == 'set_sirene_api_options') {
	$value = GETPOST('SIRENE_API_URI', "alpha");
	if (empty($value)) $value = 'https://api.insee.fr/api-sirene/3.11/';
	$res = dolibarr_set_const($db, 'SIRENE_API_URI', $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}
	$value = GETPOST('SIRENE_API_BEARER_KEY', "alpha");
	$res = dolibarr_set_const($db, 'SIRENE_API_BEARER_KEY', $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}
	$value = GETPOST('SIRENE_API_TIMEOUT', "int");
	$res = dolibarr_set_const($db, 'SIRENE_API_TIMEOUT', $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}
} elseif ($action == 'set_sirene_options') {
	$value = GETPOST('SIRENE_VERIFICATION_SIRET_URL', "alpha");
	$res = dolibarr_set_const($db, 'SIRENE_VERIFICATION_SIRET_URL', $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}
} elseif ($action == 'set_sirene_cron_options') {
	$value = GETPOST('SIRENE_MAIL_TO_SEND', "alpha");
	$res = dolibarr_set_const($db, 'SIRENE_MAIL_TO_SEND', $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}
} elseif (preg_match('/set_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$value = (GETPOST($code) ? GETPOST($code) : 1);
	$res = dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}
} elseif (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$res = dolibarr_del_const($db, $code, $conf->entity);
	if (!($res > 0)) {
		$errors[] = $db->lasterror();
		$error++;
	}
}

if ($action != '') {
	if (!$error) {
		setEventMessage($langs->trans("SetupSaved"));
		//        Header("Location: " . $_SERVER["PHP_SELF"]);
		//        exit;
	} else {
		setEventMessages('', $errors, 'errors');
	}
}

/*
 *  View
 */

$form = new Form($db);

llxHeader();

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("SireneSetup"), $linkback, 'title_setup');

$head = sirene_admin_prepare_head();
if ($isV14p) {
	print dol_get_fiche_head($head, 'settings', $langs->trans("Module163027Name"), 0, 'opendsi@sirene');
} else {
	dol_fiche_head($head, 'settings', $langs->trans("Module163027Name"), 0, 'opendsi@sirene');
}

print '<br>';

/********************************************************
 *  Sirene API options
 ********************************************************/
print '<div id="sirene_api_options"></div>';
print load_fiche_titre($langs->trans("SireneApiOptions"), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '#sirene_api_options">';
print '<input type="hidden" name="token" value="' . newToken() . '" />';
print '<input type="hidden" name="action" value="set_sirene_api_options">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="width20p">' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td>' . $langs->trans("Description") . '</td>' . "\n";
print '<td class="width40p">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

// SIRENE_API_URI
$sireneAPIUrl = getSireneDolGlobalString('SIRENE_API_URI', 'https://api.insee.fr/api-sirene/3.11/');
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("SireneApiUrlName") . '</td>' . "\n";
print '<td>' . $langs->trans("SireneApiUrlDesc") . '</td>' . "\n";
print '<td class="nowrap">' . "\n";
print '<input type="text" name="SIRENE_API_URI" class="centpercent" value="' . dol_escape_htmltag($sireneAPIUrl) . '" />' . "\n";
print '</td></tr>' . "\n";

// SIRENE_API_BEARER_KEY
$sireneAPIBearerKey = getSireneDolGlobalString('SIRENE_API_BEARER_KEY');
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("SireneApiBearerKeyName") . '</td>' . "\n";
print '<td>' . $langs->trans("SireneApiBearerKeyDesc") . '</td>' . "\n";
print '<td class="nowrap">' . "\n";
print '<input type="text" name="SIRENE_API_BEARER_KEY" class="centpercent" value="' . dol_escape_htmltag($sireneAPIBearerKey) . '" />' . "\n";
print '</td></tr>' . "\n";

// SIRENE_API_VERIFY_SSL
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("SireneApiVerifySslName") . '</td>';
print '<td>' . $langs->trans("SireneApiVerifySslDesc") . '</td>';
print '<td>' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('SIRENE_API_VERIFY_SSL', '', $conf->entity);
} else {
	if (!getSireneDolGlobalInt('SIRENE_API_VERIFY_SSL')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_API_VERIFY_SSL">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_API_VERIFY_SSL">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

print '<tr class="liste_titre">';
print '<td class="width20p">' . $langs->trans("SireneGlobalParameters") . '</td>' . "\n";
print '<td>' . $langs->trans("Description") . '</td>' . "\n";
print '<td class="width40p">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

// SIRENE_API_TIMEOUT
$sireneAPITimeout = getSireneDolGlobalInt('SIRENE_API_TIMEOUT', 10);
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("SireneApiTimeOutName") . '</td>' . "\n";
print '<td>' . $langs->trans("SireneApiTimeOutDesc") . '</td>' . "\n";
print '<td class="nowrap">' . "\n";
print '<input type="text" name="SIRENE_API_TIMEOUT" size="30" value="' . dol_escape_htmltag($sireneAPITimeout) . '" />' . "\n";
print '</td></tr>' . "\n";

// SIRENE_API_DEBUG
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("SireneApiDebugName") . '</td>';
print '<td>' . $langs->trans("SireneApiDebugDesc") . '</td>';
print '<td>' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('SIRENE_API_DEBUG', '', $conf->entity);
} else {
	if (!getSireneDolGlobalInt('SIRENE_API_DEBUG')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_API_DEBUG">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_API_DEBUG">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

print '</table>';
print '</div>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
print '</div>';

print '</form>';

/********************************************************
 *  Sirene options
 ********************************************************/
print '<div id="sirene_options"></div>';
print load_fiche_titre($langs->trans("SireneOptions"), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '#sirene_options">';
print '<input type="hidden" name="token" value="' . newToken() . '" />';
print '<input type="hidden" name="action" value="set_sirene_options">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="width20p">' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td>' . $langs->trans("Description") . '</td>' . "\n";
print '<td class="width40p">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

// SIRENE ADDRESSES
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("SireneAddressesOption") . '</td>';
if (isset($conf->adressefrance)) {
	if (getSireneDolGlobalInt('SIRENE_ADDRESSES')) {
		$res = dolibarr_set_const($db, 'SIRENE_ADDRESSES', '0', 'chaine', 0, '', $conf->entity);
	}
	print '<td>' . $langs->trans("SireneAddressesOptionDesc") .  '<br> '. $langs->trans("SireneAddressesOptionAdresseModuleActivated")  . '</td>';
	print '<td>' . "\n";
	print '<a style="cursor: not-allowed;" href="#">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
} else {
	print '<td>' . $langs->trans("SireneAddressesOptionDesc") .  '</td>';
	print '<td>' . "\n";

	if (!empty($conf->use_javascript_ajax)) {
		print ajax_constantonoff('SIRENE_ADDRESSES', '', $conf->entity);
	} else {
		if (!getSireneDolGlobalInt('SIRENE_ADDRESSES')) {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_ADDRESSES">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
		} else {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_ADDRESSES">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
		}
	}
}
print '</td></tr>' . "\n";

// SIRENE_VERIFICATION_SIRET_URL
$sireneVerificationSiretUrl = getSireneDolGlobalString('SIRENE_VERIFICATION_SIRET_URL');
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("SireneVerificationSiretUrlName") . '</td>' . "\n";
print '<td>' . $langs->trans("SireneVerificationSiretUrlDesc") . '</td>' . "\n";
print '<td class="nowrap">' . "\n";
print '<input type="text" name="SIRENE_VERIFICATION_SIRET_URL" class="centpercent" value="' . dol_escape_htmltag($sireneVerificationSiretUrl) . '" />' . "\n";
print '</td></tr>' . "\n";

// SIRENE_SIEGE_BY_DEFAULT
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("SireneSiegeByDefaultName") . '</td>';
print '<td>' . $langs->trans("SireneSiegeByDefaultDesc") . '</td>';
print '<td>' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('SIRENE_SIEGE_BY_DEFAULT', '', $conf->entity);
} else {
	if (!getSireneDolGlobalInt('SIRENE_SIEGE_BY_DEFAULT')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_SIEGE_BY_DEFAULT">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_SIEGE_BY_DEFAULT">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

// SIRENE_ADD_NIC_TOWN_IN_NAME_IF_DUPLICATE
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("SireneAddNicTownInNameIfDuplicateName") . '</td>' . "\n";
print '<td>' . $langs->trans("SireneAddNicTownInNameIfDuplicateDesc") . '</td>' . "\n";
print '<td class="nowrap">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('SIRENE_ADD_NIC_TOWN_IN_NAME_IF_DUPLICATE', '', $conf->entity);
} else {
	if (!getSireneDolGlobalInt('SIRENE_ADD_NIC_TOWN_IN_NAME_IF_DUPLICATE')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_ADD_NIC_TOWN_IN_NAME_IF_DUPLICATE">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_ADD_NIC_TOWN_IN_NAME_IF_DUPLICATE">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

print '</table>';
print '</div>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
print '</div>';

print '</form>';


/********************************************************
 *  Sirene Cron options
 ********************************************************/
print '<div id="sirene_cron_options">';
print load_fiche_titre($langs->trans("SireneCronOptions"), '', '');
print info_admin($langs->trans("SireneCronOptionsDesc"));

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '#sirene_cron_options">';
print '<input type="hidden" name="token" value="' . newToken() . '" />';
print '<input type="hidden" name="action" value="set_sirene_cron_options">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="width20p">' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td>' . $langs->trans("Description") . '</td>' . "\n";
print '<td class="width40p">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

// SIRENE_MAIL_TO_SEND
$sireneMailToSend = getSireneDolGlobalString('SIRENE_MAIL_TO_SEND');
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("SireneMailReceiverToSendName") . '</td>' . "\n";
print '<td>' . $langs->trans("SireneMailReceiverToSendDesc") . '</td>' . "\n";
print '<td class="nowrap">' . "\n";
print '<input type="text" name="SIRENE_MAIL_TO_SEND" class="centpercent" value="' . dol_escape_htmltag($sireneMailToSend) . '" />' . "\n";
print '</td></tr>' . "\n";

// SIRENE_CRON_CHECK_FREQUENCY
$sireneCheckFrequency = getSireneDolGlobalInt('SIRENE_CRON_CHECK_FREQUENCY', 30);
print '<tr class="oddeven">' . "\n";
print '<td>' . $langs->trans("SireneCronCheckFrequencyName") . '</td>' . "\n";
print '<td>' . $langs->trans("SireneCronCheckFrequencyDesc") . '</td>' . "\n";
print '<td class="nowrap">' . "\n";
print '<input type="text" name="SIRENE_CRON_CHECK_FREQUENCY" class="centpercent" value="' . dol_escape_htmltag($sireneCheckFrequency) . '" />' . "\n";
print '</td></tr>' . "\n";

print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans("SireneMailFieldUpdated") . '</td>' . "\n";
print '<td class="width40p">' . $langs->trans("Value") . '</td>' . "\n";
print "</tr>\n";

// SIRENE_CRON_COMPANY_NAME
print '<tr class="oddeven">' . "\n";
print '<td colspan="2">' . $langs->trans("SireneCompanyName") . '</td>';
print '<td>' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('SIRENE_CRON_COMPANY_NAME', '', $conf->entity);
} else {
	if (!getSireneDolGlobalInt('SIRENE_CRON_COMPANY_NAME')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_CRON_COMPANY_NAME">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_CRON_COMPANY_NAME">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

// SIRENE_CRON_COMPANY_NAME_ALIAS
print '<tr class="oddeven">' . "\n";
print '<td colspan="2">' . $langs->trans("SireneCompanyNameAlias") . '</td>';
print '<td>' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('SIRENE_CRON_COMPANY_NAME_ALIAS', '', $conf->entity);
} else {
	if (!getSireneDolGlobalInt('SIRENE_CRON_COMPANY_NAME_ALIAS')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_CRON_COMPANY_NAME_ALIAS">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_CRON_COMPANY_NAME_ALIAS">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

// SIRENE_CRON_ADRESS
print '<tr class="oddeven">' . "\n";
print '<td colspan="2">' . $langs->trans("SireneAddress") . '</td>';
print '<td>' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('SIRENE_CRON_ADRESS', '', $conf->entity);
} else {
	if (!getSireneDolGlobalInt('SIRENE_CRON_ADRESS')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_CRON_ADRESS">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_CRON_ADRESS">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

// SIRENE_CRON_RNA
print '<tr class="oddeven">' . "\n";
print '<td colspan="2">' . $langs->trans("SireneRna") . '</td>';
print '<td>' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('SIRENE_CRON_RNA', '', $conf->entity);
} else {
	if (!getSireneDolGlobalInt('SIRENE_CRON_RNA')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_CRON_RNA">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_CRON_RNA">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

// SIRENE_CRON_NAF
print '<tr class="oddeven">' . "\n";
print '<td colspan="2">' . $langs->trans("SireneCodeNaf") . '</td>';
print '<td>' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('SIRENE_CRON_NAF', '', $conf->entity);
} else {
	if (!getSireneDolGlobalInt('SIRENE_CRON_NAF')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_CRON_NAF">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_CRON_NAF">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

// SIRENE_CRON_TVA
print '<tr class="oddeven">' . "\n";
print '<td colspan="2">' . $langs->trans("SireneTvaIntra") . '</td>';
print '<td>' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('SIRENE_CRON_TVA', '', $conf->entity);
} else {
	if (!getSireneDolGlobalInt('SIRENE_CRON_TVA')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_CRON_TVA">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_CRON_TVA">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

// SIRENE_CRON_STAFF
print '<tr class="oddeven">' . "\n";
print '<td colspan="2">' . $langs->trans("DictionaryStaff") . '</td>';
print '<td>' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('SIRENE_CRON_STAFF', '', $conf->entity);
} else {
	if (!getSireneDolGlobalInt('global->SIRENE_CRON_STAFF')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_CRON_STAFF">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_CRON_STAFF">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

// SIRENE_CRON_JURI_STATUS
print '<tr class="oddeven">' . "\n";
print '<td colspan="2">' . $langs->trans("SireneJuridicalStatusLevel2") . '</td>';
print '<td>' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('SIRENE_CRON_JURI_STATUS', '', $conf->entity);
} else {
	if (!getSireneDolGlobalInt('SIRENE_CRON_JURI_STATUS')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_SIRENE_CRON_JURI_STATUS">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_SIRENE_CRON_JURI_STATUS">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

print '</table>';
print '</div>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
print '</div>';

print '</form>';

if ($isV14p) {
	print dol_get_fiche_end();
} else {
	dol_fiche_end();
}

llxFooter();
$db->close();
