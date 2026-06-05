<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    clichaumeil/admin/api_connections.php
 * \ingroup clichaumeil
 * \brief   Clichaumeil API connections settings page (ANTALIS).
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/security.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formsetup.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php";
require_once '../lib/clichaumeil.lib.php';
require_once __DIR__ . '/../class/SupplierPriceSync/SupplierPriceSyncConstants.php';
require_once __DIR__ . '/../class/SupplierPriceSync/ValueObject/SupplierProductRequest.php';
require_once __DIR__ . '/../class/SupplierPriceSync/Antalis/AntalisConnectorConfig.php';
require_once __DIR__ . '/../class/SupplierPriceSync/Antalis/AntalisCustomerPricesConnector.php';
require_once __DIR__ . '/../class/SupplierPriceSync/Antalis/AntalisOrderUnitMapper.php';
require_once __DIR__ . '/../class/SupplierPriceSync/ValueObject/CronRecipients.php';
require_once __DIR__ . '/../class/SupplierPriceSync/ValueObject/SupplierPriceSyncReport.php';
require_once __DIR__ . '/../class/SupplierPriceSync/Service/SupplierPriceSyncMailer.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "clichaumeil@clichaumeil"));

$hookmanager->initHooks(array('clichaumeilapiconnections', 'globalsetup'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Access control
if (!isModEnabled('clichaumeil') || !$user->admin) {
	accessforbidden();
}

// Build the supplier list (active suppliers only).
$supplierOptions = array();
$sql = "SELECT rowid, nom FROM " . $db->prefix() . "societe";
$sql .= " WHERE fournisseur = 1 AND entity IN (" . getEntity('societe') . ")";
$sql .= " ORDER BY nom";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$supplierOptions[$obj->rowid] = $obj->nom;
	}
	$db->free($resql);
}

$form = new Form($db);

// Global block: settings shared by every API connector (notification policy + recipients).
// It uses its own form action so saving it never blanks the connector fields.
$globalFormSetup = new FormSetup($db);
$globalFormSetup->formHiddenInputs['action'] = 'updateglobalsettings';
// Scope caption next to the save button: the native FormSetup button label is a plain
// "Save", so this disambiguates which block each of the several save buttons persists.
$globalFormSetup->htmlOutputMoreButton = '<span class="opacitymedium paddingright">' . dol_escape_htmltag($langs->trans('CliChaumeil_ScopeCommon')) . '</span>';
$mailPolicyOptions = array(
	SupplierPriceSyncConstants::MAIL_POLICY_NEVER => $langs->trans('CliChaumeil_AntalisMailPolicyNever'),
	SupplierPriceSyncConstants::MAIL_POLICY_ERRORS => $langs->trans('CliChaumeil_AntalisMailPolicyErrors'),
	SupplierPriceSyncConstants::MAIL_POLICY_ERRORS_WARNINGS => $langs->trans('CliChaumeil_AntalisMailPolicyErrorsWarnings'),
	SupplierPriceSyncConstants::MAIL_POLICY_ALWAYS => $langs->trans('CliChaumeil_AntalisMailPolicyAlways'),
);
// Default the select to the effective default policy when unset, so the displayed value
// matches what the cron actually does (getDolGlobalString(..., DEFAULT_MAIL_POLICY)).
$mailPolicyItem = $globalFormSetup->newItem(SupplierPriceSyncConstants::CONST_MAIL_POLICY);
$mailPolicyItem->defaultFieldValue = SupplierPriceSyncConstants::DEFAULT_MAIL_POLICY;
$mailPolicyItem->setAsSelect($mailPolicyOptions);
$globalFormSetup->newItem(SupplierPriceSyncConstants::CONST_MAIL_RECIPIENTS)->setAsString();

// ANTALIS connector — "Connection" sub-block (identity: who/where we connect).
// The HTTP password is deliberately NOT managed by FormSetup: FormSetup renders the
// stored value in the HTML "value" attribute (clear text in the page source); it is
// handled by a dedicated, never-prefilled form rendered right after this block so all
// connection credentials stay grouped together.
$formSetup = new FormSetup($db);
$formSetup->htmlOutputMoreButton = '<span class="opacitymedium paddingright">' . dol_escape_htmltag($langs->trans('CliChaumeil_ScopeConnection')) . '</span>';
$formSetup->newItem('CliChaumeil_AntalisSectionConnection')->setAsTitle();
$formSetup->newItem(SupplierPriceSyncConstants::CONST_BASE_URL)->setAsString();
$formSetup->newItem(SupplierPriceSyncConstants::CONST_HTTP_LOGIN)->setAsString();
$formSetup->newItem(SupplierPriceSyncConstants::CONST_THIRDPARTY_ID)->setAsSelect($supplierOptions);
$formSetup->newItem(SupplierPriceSyncConstants::CONST_CUSTOMER_ID)->setAsString();
$formSetup->newItem(SupplierPriceSyncConstants::CONST_USER_CODE)->setAsString();
$formSetup->newItem(SupplierPriceSyncConstants::CONST_DELIVERY_ADDRESS_ID)->setAsString();

// ANTALIS connector — "Behaviour" sub-block (how this connector's sync runs). Its own
// form action so saving behaviour never blanks the connection fields, and vice versa.
$behaviourFormSetup = new FormSetup($db);
$behaviourFormSetup->formHiddenInputs['action'] = 'updatebehaviour';
$behaviourFormSetup->htmlOutputMoreButton = '<span class="opacitymedium paddingright">' . dol_escape_htmltag($langs->trans('CliChaumeil_ScopeBehaviour')) . '</span>';
$behaviourFormSetup->newItem('CliChaumeil_AntalisSectionBehaviour')->setAsTitle();
$behaviourFormSetup->newItem(SupplierPriceSyncConstants::CONST_DRY_RUN)->setAsYesNo();
$behaviourFormSetup->newItem(SupplierPriceSyncConstants::CONST_MAX_CLOSURE_RATIO)->setAsString();
$behaviourFormSetup->newItem(SupplierPriceSyncConstants::CONST_PRODUCT_LIMIT)->setAsString();

/*
 * Actions
 */

if ($action == 'updateglobalsettings' && !empty($user->admin)) {
	$globalFormSetup->saveConfFromPost();

	header('Location: ' . $_SERVER["PHP_SELF"]);
	exit;
}

if ($action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();

	header('Location: ' . $_SERVER["PHP_SELF"]);
	exit;
}

if ($action == 'updatebehaviour' && !empty($user->admin)) {
	$behaviourFormSetup->saveConfFromPost();

	header('Location: ' . $_SERVER["PHP_SELF"]);
	exit;
}

if ($action == 'setantalispassword' && !empty($user->admin)) {
	// 'password' type: no sanitisation (case 'password' just breaks in GETPOST) so a
	// secret containing <, > or & is stored verbatim — 'alphanohtml' would strip them.
	$newPassword = GETPOST(SupplierPriceSyncConstants::CONST_HTTP_PASSWORD, 'password');
	if ($newPassword !== '') {
		// Store the secret reversibly encrypted (dolEncrypt); conf auto-decrypts it
		// on load (conf.class.php), so getDolGlobalString() still returns it in clear.
		dolibarr_set_const($db, SupplierPriceSyncConstants::CONST_HTTP_PASSWORD, dolEncrypt($newPassword), 'chaine', 0, '', $conf->entity);
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
	}

	header('Location: ' . $_SERVER["PHP_SELF"]);
	exit;
}

if ($action == 'testantalisconnection' && !empty($user->admin)) {
	// Read-only connectivity probe: build the connector and send a single dummy
	// product. Any structured SOAP answer (even "unknown product") proves the URL,
	// credentials and service are reachable; only a transport/auth failure is fatal.
	try {
		$probeConfig = AntalisConnectorConfig::fromGlobals();
		$probeConnector = new AntalisCustomerPricesConnector($probeConfig, new AntalisOrderUnitMapper());
		$probeRequest = new SupplierProductRequest(0, $probeConfig->getSupplierThirdpartyId(), 'TESTCONNECTION', 'TESTCONNECTION');
		$probeResult = $probeConnector->fetchPriceGrids(array($probeRequest));

		if ($probeResult->fatalError) {
			$probeDetail = (!empty($probeResult->issues)) ? $probeResult->issues[0]->message : '';
			setEventMessages($langs->trans('CliChaumeil_AntalisTestConnectionKo') . ($probeDetail !== '' ? ' (' . dol_trunc($probeDetail, 200) . ')' : ''), null, 'errors');
		} else {
			setEventMessages($langs->trans('CliChaumeil_AntalisTestConnectionOk'), null, 'mesgs');
		}
	} catch (RuntimeException $exception) {
		setEventMessages($langs->trans('CliChaumeil_AntalisTestConnectionMissingConfig'), null, 'warnings');
	} catch (Throwable $exception) {
		setEventMessages($langs->trans('CliChaumeil_AntalisTestConnectionKo') . ' (' . dol_trunc($exception->getMessage(), 200) . ')', null, 'errors');
	}

	header('Location: ' . $_SERVER["PHP_SELF"]);
	exit;
}

if ($action == 'sendtestantalismail' && !empty($user->admin)) {
	// Send a sample report to the configured recipients to validate delivery
	// (sender, SMTP, addresses) without having to wait for a failing nightly run.
	$rawRecipients = getDolGlobalString(SupplierPriceSyncConstants::CONST_MAIL_RECIPIENTS);
	try {
		$testRecipients = CronRecipients::fromRaw($rawRecipients);
		if ($testRecipients->isEmpty()) {
			setEventMessages($langs->trans('CliChaumeil_AntalisTestMailNoRecipient'), null, 'warnings');
		} else {
			$testReport = new SupplierPriceSyncReport(SupplierPriceSyncConstants::SUPPLIER_ANTALIS);
			$testReport->dryRun = true;
			$testReport->recordChange(
				SupplierPriceSyncConstants::CHANGE_UPDATE,
				$langs->trans('CliChaumeil_AntalisTestMailSampleProduct'),
				'',
				1.0,
				0.95,
				'Feuille'
			);
			if ((new SupplierPriceSyncMailer())->send($testReport, $testRecipients, $langs)) {
				setEventMessages($langs->trans('CliChaumeil_AntalisTestMailOk', count($testRecipients->all())), null, 'mesgs');
			} else {
				setEventMessages($langs->trans('CliChaumeil_AntalisTestMailKo'), null, 'errors');
			}
		}
	} catch (InvalidArgumentException $exception) {
		setEventMessages($langs->trans('CliChaumeil_AntalisTestMailInvalidRecipient') . ' (' . dol_trunc($exception->getMessage(), 120) . ')', null, 'errors');
	}

	header('Location: ' . $_SERVER["PHP_SELF"]);
	exit;
}

/*
 * View
 */

$help_url = '';
$title = "CliChaumeil_ApiConnectionsTab";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-clichaumeil page-admin');

$linkback = '<a href="' . ($backtopage ? dol_escape_htmltag($backtopage) : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

$head = clichaumeilAdminPrepareHead();
print dol_get_fiche_head($head, 'api_connections', $langs->trans("CliChaumeil_AntalisApiTitle"), -1, "clichaumeil@clichaumeil");

echo '<span class="opacitymedium">' . $langs->trans("CliChaumeil_AntalisApiIntro") . '</span><br><br>';

// Global block: settings shared by every API connector (notification policy + recipients).
// Same card shell as the connectors, without a status badge.
print clichaumeilConnectorCardStart($langs->trans('CliChaumeil_ApiGlobalSectionTitle'), 'email');
print $globalFormSetup->generateOutput(true);
print clichaumeilConnectorCardEnd();
print '<br>';

// Connector section as a reusable card (future GEODIS/OVOL connectors call the same
// clichaumeilConnectorCardStart()/End() helpers to get the identical look).
// Status badge: a connector is "configured" once its endpoint and secret are set.
$antalisConfigured = getDolGlobalString(SupplierPriceSyncConstants::CONST_BASE_URL) !== ''
	&& getDolGlobalString(SupplierPriceSyncConstants::CONST_HTTP_PASSWORD) !== '';
$antalisBadge = $antalisConfigured
	? '<span class="badge badge-success">' . dol_escape_htmltag($langs->trans('CliChaumeil_ConnectorConfigured')) . '</span>'
	: '<span class="badge badge-warning">' . dol_escape_htmltag($langs->trans('CliChaumeil_ConnectorNotConfigured')) . '</span>';
print clichaumeilConnectorCardStart($langs->trans('CliChaumeil_AntalisConnectorSectionTitle'), 'bill', $antalisBadge);

// Single "test mode active" banner gathering every test-only setting currently on
// (dry-run, product limit): they alter how THIS connector runs and must never be left
// on in production. One box keeps the hierarchy clear instead of stacked warnings.
$testModeNotes = array();
if (getDolGlobalInt(SupplierPriceSyncConstants::CONST_DRY_RUN) === 1) {
	$testModeNotes[] = $langs->trans('CliChaumeil_AntalisDryRunBanner');
}
$productLimitActive = getDolGlobalInt(SupplierPriceSyncConstants::CONST_PRODUCT_LIMIT);
if ($productLimitActive > 0) {
	$testModeNotes[] = $langs->trans('CliChaumeil_AntalisProductLimitBanner', $productLimitActive);
}
if (!empty($testModeNotes)) {
	print '<div class="warning">' . img_warning() . ' <strong>' . dol_escape_htmltag($langs->trans('CliChaumeil_AntalisTestModeActive')) . '</strong>';
	print '<ul style="margin:4px 0 0">';
	foreach ($testModeNotes as $testModeNote) {
		print '<li>' . $testModeNote . '</li>';
	}
	print '</ul></div><br>';
}

print $formSetup->generateOutput(true);
print '<br>';

// Dedicated HTTP password form: never pre-filled, only updated when a value is submitted.
$hasPassword = (getDolGlobalString(SupplierPriceSyncConstants::CONST_HTTP_PASSWORD) !== '');
print '<form method="POST" action="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '" autocomplete="off">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setantalispassword">';
print '<table class="noborder centpercent"><tr class="liste_titre"><td>' . $form->textwithpicto($langs->trans('CLICHAUMEIL_SUPPLIER_ANTALIS_HTTP_PASSWORD'), $langs->trans('CliChaumeil_AntalisPasswordTooltip')) . '</td><td></td></tr>';
print '<tr class="oddeven"><td>';
print '<input type="password" name="' . SupplierPriceSyncConstants::CONST_HTTP_PASSWORD . '" value="" autocomplete="new-password" class="flat">';
print ' <span class="opacitymedium">' . $langs->trans('CliChaumeil_AntalisPasswordHint') . '</span>';
if ($hasPassword) {
	print ' ' . img_picto('', 'tick', 'class="paddingleft"') . ' ' . $langs->trans('CliChaumeil_AntalisPasswordConfigured');
}
print '</td><td class="right"><input type="submit" class="button button-save" value="' . dol_escape_htmltag($langs->trans('Save')) . '"></td></tr>';
print '</table></form>';
print '<br>';

// "Behaviour" sub-block, rendered after the grouped connection credentials.
print $behaviourFormSetup->generateOutput(true);
print '<br>';

// "Test connection" button: a read-only probe so config errors surface here and
// now, instead of being discovered at the next nightly cron run. It is a diagnostic
// action (not a save), hence a neutral button rather than the green save button.
print '<form method="POST" action="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="testantalisconnection">';
print '<input type="submit" class="button" value="' . dol_escape_htmltag($langs->trans('CliChaumeil_AntalisTestConnectionButton')) . '">';
print ' <span class="opacitymedium">' . $langs->trans('CliChaumeil_AntalisTestConnectionHint') . '</span>';
print '</form>';
print '<br>';

// "Send test email" button: deliver a sample report to the configured recipients
// to validate sender/SMTP/addresses without waiting for a failing nightly run.
// Diagnostic action, neutral button.
print '<form method="POST" action="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="sendtestantalismail">';
print '<input type="submit" class="button" value="' . dol_escape_htmltag($langs->trans('CliChaumeil_AntalisTestMailButton')) . '">';
print ' <span class="opacitymedium">' . $langs->trans('CliChaumeil_AntalisTestMailHint') . '</span>';
print '</form>';
print '<br>';

// Link to the scheduled job (where the last report and the recipients live).
$cronJobId = 0;
$sqlCron = "SELECT rowid FROM " . $db->prefix() . "cronjob";
$sqlCron .= " WHERE objectname = 'AntalisSupplierPriceSyncCronJob'";
$sqlCron .= " AND entity IN (0, " . ((int) $conf->entity) . ")";
$resqlCron = $db->query($sqlCron);
if ($resqlCron) {
	if ($objCron = $db->fetch_object($resqlCron)) {
		$cronJobId = (int) $objCron->rowid;
	}
	$db->free($resqlCron);
}
if ($cronJobId > 0) {
	print '<div class="info">' . $langs->trans("CliChaumeil_AntalisPriceSyncCronComment");
	print ' <a href="' . DOL_URL_ROOT . '/cron/card.php?id=' . $cronJobId . '">' . $langs->trans('CliChaumeil_AntalisPriceSyncCronLink') . '</a>';
	print '</div>';
} else {
	echo '<div class="info">' . $langs->trans("CliChaumeil_AntalisPriceSyncCronComment") . '</div>';
}

// Last recorded run (persisted by the cron after each execution).
$lastRunRaw = getDolGlobalString(SupplierPriceSyncConstants::CONST_LASTRUN_PREFIX . SupplierPriceSyncConstants::SUPPLIER_ANTALIS);
$lastRun = $lastRunRaw !== '' ? json_decode($lastRunRaw, true) : null;
if (is_array($lastRun) && isset($lastRun['date'])) {
	print '<div class="info">';
	print '<strong>' . dol_escape_htmltag($langs->trans('CliChaumeil_AntalisLastRunLabel')) . '</strong> : '
		. dol_escape_htmltag(dol_print_date((int) $lastRun['date'], 'dayhour'));
	if (!empty($lastRun['dryRun'])) {
		print ' <span class="badge badge-warning">' . dol_escape_htmltag($langs->trans('CliChaumeil_AntalisLastRunDryRun')) . '</span>';
	}

	if (isset($lastRun['counts']) && is_array($lastRun['counts'])) {
		$counts = $lastRun['counts'];
		// Action counters as coloured badges; errors/warnings stand out.
		$badges = array(
			array((int) ($counts['updated'] ?? 0), 'badge-info', 'CliChaumeil_SupplierPriceSyncMetricUpdated'),
			array((int) ($counts['created'] ?? 0), 'badge-success', 'CliChaumeil_SupplierPriceSyncMetricCreated'),
			array((int) ($counts['closed'] ?? 0), 'badge-secondary', 'CliChaumeil_SupplierPriceSyncMetricClosed'),
			array((int) ($counts['reactivated'] ?? 0), 'badge-success', 'CliChaumeil_SupplierPriceSyncMetricReactivated'),
			array((int) ($counts['errors'] ?? 0), 'badge-danger', 'CliChaumeil_SupplierPriceSyncMetricErrors'),
			array((int) ($counts['warnings'] ?? 0), 'badge-warning', 'CliChaumeil_SupplierPriceSyncMetricWarnings'),
		);
		print '<div style="margin-top:6px">';
		foreach ($badges as $badge) {
			print '<span class="badge ' . $badge[1] . ' marginrightonlyshort">' . $badge[0] . ' ' . dol_escape_htmltag($langs->trans($badge[2])) . '</span> ';
		}
		print '</div>';
		print '<div class="opacitymedium" style="margin-top:4px">'
			. ((int) ($counts['scanned'] ?? 0)) . ' ' . dol_escape_htmltag($langs->trans('CliChaumeil_SupplierPriceSyncMetricScanned'))
			. ' · ' . ((int) ($counts['unchanged'] ?? 0)) . ' ' . dol_escape_htmltag($langs->trans('CliChaumeil_SupplierPriceSyncMetricUnchanged'))
			. ' · ' . ((int) ($counts['skipped'] ?? 0)) . ' ' . dol_escape_htmltag($langs->trans('CliChaumeil_SupplierPriceSyncMetricSkipped'))
			. '</div>';
	} elseif (isset($lastRun['summary'])) {
		// Back-compat: a run persisted before the structured counts existed.
		print ' — ' . dol_escape_htmltag((string) $lastRun['summary']);
	}
	print '</div>';
} else {
	print '<div class="opacitymedium">' . dol_escape_htmltag($langs->trans('CliChaumeil_AntalisLastRunNone')) . '</div>';
}

// Operating guide, collapsed by default (native <details>, no JS).
print '<br><details><summary class="cursorpointer">' . dol_escape_htmltag($langs->trans('CliChaumeil_AntalisPriceSyncHelpTitle')) . '</summary>';
print '<div class="opacitymedium" style="margin-top:8px"><ul>';
for ($helpLine = 1; $helpLine <= 7; $helpLine++) {
	print '<li>' . $langs->trans('CliChaumeil_AntalisPriceSyncHelp' . $helpLine) . '</li>';
}
print '</ul></div></details>';

// Close the ANTALIS connector card.
print clichaumeilConnectorCardEnd();

print dol_get_fiche_end();

llxFooter();
$db->close();
