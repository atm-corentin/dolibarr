<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019-2022 Open-DSI             <support@open-dsi.fr>
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
 *	    \file       htdocs/extraitcompteclient/admin/setup.php
 *		\ingroup    extraitcompteclient
 *		\brief      Page to setup extraitcompteclient module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/extraitcompteclient/lib/extraitcompteclient.lib.php');

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$langs->load("admin");
$langs->load("extraitcompteclient@extraitcompteclient");
$langs->load("opendsi@extraitcompteclient");

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');

// Security check
$id = GETPOST('id', 'int');
$object = new User($db);
$object->fetch($id, '', '', 1);
$object->getrights();

/*
 *	Actions
 */

$errors = [];
$error = 0;

if ($action == 'setdefaultaddsubsidiaries') {
    $setdefaultaddsubsidiaries= GETPOST('value', 'int');
    $res = dolibarr_set_const($db, "EXTRAITCOMPTECLIENT_DEFAULT_ADD_SUBSIDIARIES", $setdefaultaddsubsidiaries, 'yesno', 0, '', $conf->entity);
    if (!$res > 0)
        $error++;
}

if ($action == 'setdefaultinvoicepayed') {
    $setdefaultinvoicepayed= GETPOST('value', 'int');
    $res = dolibarr_set_const($db, "EXTRAITCOMPTECLIENT_DEFAULT_INVOICE_PAYED", $setdefaultinvoicepayed, 'yesno', 0, '', $conf->entity);
    if (!$res > 0)
        $error++;
}

if ($action == 'setdefaultinvoiceabandoned') {
    $setdefaultinvoiceabandoned= GETPOST('value', 'int');
    $res = dolibarr_set_const($db, "EXTRAITCOMPTECLIENT_DEFAULT_INVOICE_ABANDONED", $setdefaultinvoiceabandoned, 'yesno', 0, '', $conf->entity);
    if (!$res > 0)
        $error++;
}

if ($action == 'setdefaultpaymentdeadline') {
    $setdefaultpaymentdeadline= GETPOST('value', 'int');
    $res = dolibarr_set_const($db, "EXTRAITCOMPTECLIENT_DEFAULT_PAYMENT_DEADLINE", $setdefaultpaymentdeadline, 'yesno', 0, '', $conf->entity);
    if (!$res > 0)
        $error++;
}

if ($action == 'setdefaultspacedeleted') {
    $setdefaultspacedeleted= GETPOST('value', 'int');
    $res = dolibarr_set_const($db, "EXTRAITCOMPTECLIENT_DELETESPACEFROMNUMBERONCSV", $setdefaultspacedeleted, 'yesno', 0, '', $conf->entity);
    if (!$res > 0)
        $error++;
}

if ($action == 'setdefaultrefsupplier') {
    $setdefaultrefsupplier= GETPOST('value', 'int');
    $res = dolibarr_set_const($db, "EXTRAITCOMPTECLIENT_DEFAULT_REF_SUPPLIER", $setdefaultrefsupplier, 'yesno', 0, '', $conf->entity);
    if (!$res > 0)
        $error++;
}

if ($action == 'setdefaultaddproducttags') {
    $setdefaultaddproducttags= GETPOST('value', 'int');
    $res = dolibarr_set_const($db, "EXTRAITCOMPTECLIENT_DEFAULT_ADD_PRODUCT_TAGS", $setdefaultaddproducttags, 'yesno', 0, '', $conf->entity);
    if (!$res > 0)
        $error++;
}

if ($action == 'setthirdpartyref') {
	$setthirdpartyref = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "EXTRAITCOMPTECLIENT_THIRDPARTY_REF", $setthirdpartyref, 'yesno', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;
}

if ($action == 'setdefaultaddpaymentdetails') {
	$setdefaultaddpaymentdetails = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "EXTRAITCOMPTECLIENT_DEFAULT_PAYMENT_DETAILS", $setdefaultaddpaymentdetails, 'yesno', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;
}

if ($action == 'setdefaultaddmulticurrency') {
	$setdefaultaddmulticurrency = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "EXTRAITCOMPTECLIENT_DEFAULT_ADD_MULTICURRENCY", $setdefaultaddmulticurrency, 'yesno', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;
}

if ($action == 'set_extraitcompteclient_options') {
    $value = GETPOST('EXTRAITCOMPTECLIENT_PRODUCT_TAGS_SEPARATOR', "alpha");
    $res = dolibarr_set_const($db, 'EXTRAITCOMPTECLIENT_PRODUCT_TAGS_SEPARATOR', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }

    $value = GETPOST('EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD', "alpha");
    if ($value == -1) $value = '';
    $res = dolibarr_set_const($db, 'EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }

    $setorderby= GETPOST('EXTRAITCOMPTECLIENT_ORDERBY', 'int');
    $res = dolibarr_set_const($db, "EXTRAITCOMPTECLIENT_ORDERBY", $setorderby, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }

    $value = GETPOST('EXTRAITCOMPTECLIENT_COLOR_LINE_PDF', "aZ09");
    $res = dolibarr_set_const($db, 'EXTRAITCOMPTECLIENT_COLOR_LINE_PDF', $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }


} elseif (preg_match('/set_(.*)/',$action,$reg)) {
    $code = $reg[1];
    $value = (GETPOST($code) ? GETPOST($code) : 1);
    $res = dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }
} elseif (preg_match('/del_(.*)/',$action,$reg)) {
    $code = $reg[1];
    $res = dolibarr_del_const($db, $code, $conf->entity);
    if (!($res > 0)) {
        $errors[] = $db->lasterror();
        $error++;
    }
}

if ($action != '') {
    if (!$error) {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("Error"), null, 'mesgs');
    }
}

/*
 *	View
 */

$form = new Form($db);
$formadmin=new FormAdmin($db);
$formother=new FormOther($db);

$arrayofjs=array();
$arrayofcss=array();

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ExtraitCompteClientSetup"),$linkback,'title_setup');
print "<br>\n";

$head=extraitcompteclient_admin_prepare_head();

$isV14p = version_compare(DOL_VERSION, "14.0.0") >= 0;

if ($isV14p) {
    print dol_get_fiche_head($head, 'settings', $langs->trans("Module163030Name"), -1, 'opendsi@extraitcompteclient');
} else {
    dol_fiche_head($head, 'settings', $langs->trans("Module163030Name"), -1, 'opendsi@extraitcompteclient');
}

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_extraitcompteclient_options">';

// Managing module options
print load_fiche_titre($langs->trans("ExtraitCompteClientSetupOptions"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tbody>';
print '<tr class="liste_titre">';
print '<td width="20%">'.$langs->trans("Parameters").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td width="30%">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

// EXTRAITCOMPTECLIENT_DEFAULT_REF_SUPPLIER
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultRefSupplierName").'</td>';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultRefSupplierDesc").'</td>';
if (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_REF_SUPPLIER)) {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultrefsupplier&token='.newToken().'&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultrefsupplier&token='.newToken().'&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>' . "\n";

// EXTRAITCOMPTECLIENT_ORDERBY
print '<tr class="oddeven">'."\n";
//print '<td>'.$langs->trans("ExtraitCompteClientSortByName").'</td>';
print '<td>'.$form->textwithtooltip($langs->trans("ExtraitCompteClientSortByName"), $langs->trans("ExtraitCompteClientSortByDescTooltip"), 2, 1, img_help(1, '')).'</td>';
print '<td>'.$langs->trans("ExtraitCompteClientSortByDesc").'</td>';
print '<td class="right">'."\n";
$array = array(0=>$langs->trans("ExtraitCompteClientDesc"), 1=>$langs->trans("ExtraitCompteClientAsc"));
print Form::selectarray('EXTRAITCOMPTECLIENT_ORDERBY', $array, !empty($conf->global->EXTRAITCOMPTECLIENT_ORDERBY));
print '</td></tr>'."\n";

// EXTRAITCOMPTECLIENT_DELETESPACEFROMNUMBERONCSV
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultSpaceDeletedName").'</td>';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultSpaceDeletedDesc").'</td>';
if (!empty($conf->global->EXTRAITCOMPTECLIENT_DELETESPACEFROMNUMBERONCSV)) {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultspacedeleted&token='.newToken().'&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultspacedeleted&token='.newToken().'&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>' . "\n";

// EXTRAITCOMPTECLIENT_PRODUCT_TAGS_SEPARATOR
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ExtraitCompteClientProductTagsSeparatorName").'</td>';
print '<td>'.$langs->trans("ExtraitCompteClientProductTagsSeparatorDesc").'</td>';
print '<td class="right nowrap">';
$constantExtraitCompteClientProductTagsSeparator = !empty($conf->global->EXTRAITCOMPTECLIENT_PRODUCT_TAGS_SEPARATOR) ? $conf->global->EXTRAITCOMPTECLIENT_PRODUCT_TAGS_SEPARATOR : '';
print '<input type="text" name="EXTRAITCOMPTECLIENT_PRODUCT_TAGS_SEPARATOR" value="'.htmlspecialchars($constantExtraitCompteClientProductTagsSeparator).'">';
print '</td></tr>';

// EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("ExtraitCompteClientFactureExtrafieldName").'</td>'."\n";
print '<td>'.$langs->trans("ExtraitCompteClientFactureExtrafieldDesc").'</td>'."\n";
print '<td class="right nowrap">'."\n";
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label('facture');
$constantExtraitCompteClientFactureCodeExtrafield = !empty($conf->global->EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD) ? $conf->global->EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD : '';
print Form::selectarray("EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD", $extralabels, $constantExtraitCompteClientFactureCodeExtrafield, 1);
print '</td></tr>';

// EXTRAITCOMPTECLIENT_COLOR_LINE_PDF
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("ExtraitCompteClientFactureLineColorName").'</td>'."\n";
print '<td>'.$langs->trans("ExtraitCompteClientFactureLineColorDesc").'</td>'."\n";
print '<td class="right nowrap">'."\n";
if (empty($conf->global->EXTRAITCOMPTECLIENT_COLOR_LINE_PDF)) $conf->global->EXTRAITCOMPTECLIENT_COLOR_LINE_PDF = '';
print $formother->selectColor(GETPOSTISSET('EXTRAITCOMPTECLIENT_COLOR_LINE_PDF')?GETPOST('EXTRAITCOMPTECLIENT_COLOR_LINE_PDF', 'alphanohtml'):$conf->global->EXTRAITCOMPTECLIENT_COLOR_LINE_PDF, 'EXTRAITCOMPTECLIENT_COLOR_LINE_PDF', null, 1, '', 'hideifnotset');
print '</td></tr>';

// EXTRAITCOMPTECLIENT_ENTITY_NAME_BEGIN_LOCATION_IN_FILENAME
print '<tr class="oddeven">' . "\n";
print '<td>'.$langs->trans("ExtraitCompteClientEntityNameBeginLocationInFilenameName").'</td>';
print '<td>'.$langs->trans("ExtraitCompteClientEntityNameBeginLocationInFilenameDesc").'</td>';
print '<td class="right">' . "\n";
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('EXTRAITCOMPTECLIENT_ENTITY_NAME_BEGIN_LOCATION_IN_FILENAME');
} else {
	if (empty($conf->global->EXTRAITCOMPTECLIENT_ENTITY_NAME_BEGIN_LOCATION_IN_FILENAME)) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_EXTRAITCOMPTECLIENT_ENTITY_NAME_BEGIN_LOCATION_IN_FILENAME">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_EXTRAITCOMPTECLIENT_ENTITY_NAME_BEGIN_LOCATION_IN_FILENAME">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
	}
}
print '</td></tr>' . "\n";

print '</tbody>';
print '</table>';
print '</div>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
print '</div>';

// Management of default extract generator options
print load_fiche_titre($langs->trans("ExtraitCompteClientSetupDefaultOptionsExtractGenerator"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tbody>';
print '<tr class="liste_titre">';
print '<td width="20%">'.$langs->trans("Parameters").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td width="30%">'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";

// EXTRAITCOMPTECLIENT_DEFAULT_ADD_SUBSIDIARIES
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultAddSubsidiariesName").'</td>';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultAddSubsidiariesDesc").'</td>';
if (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_ADD_SUBSIDIARIES)) {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultaddsubsidiaries&token='.newToken().'&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultaddsubsidiaries&token='.newToken().'&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>' . "\n";

// EXTRAITCOMPTECLIENT_DEFAULT_INVOICE_PAYED
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultInvoicePayedName").'</td>';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultInvoicePayedDesc").'</td>';
if (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_INVOICE_PAYED)) {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultinvoicepayed&token='.newToken().'&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultinvoicepayed&token='.newToken().'&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>' . "\n";

// EXTRAITCOMPTECLIENT_DEFAULT_PAYMENT_DEADLINE
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultDeadlineName").'</td>';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultDeadlineDesc").'</td>';
if (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_PAYMENT_DEADLINE)) {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultpaymentdeadline&token='.newToken().'&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultpaymentdeadline&token='.newToken().'&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>' . "\n";

// EXTRAITCOMPTECLIENT_DEFAULT_INVOICE_ABANDONED
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultInvoiceAbandonedName").'</td>';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultInvoiceAbandonedDesc").'</td>';
if (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_INVOICE_ABANDONED)) {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultinvoiceabandoned&token='.newToken().'&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultinvoiceabandoned&token='.newToken().'&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>' . "\n";

// EXTRAITCOMPTECLIENT_DEFAULT_PAYMENT_DETAILS
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultAddPaymentDetailsName").'</td>';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultAddPaymentDetailsDesc").'</td>';
if (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_PAYMENT_DETAILS)) {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultaddpaymentdetails&token='.newToken().'&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultaddpaymentdetails&token='.newToken().'&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>' . "\n";

// EXTRAITCOMPTECLIENT_DEFAULT_ADD_PRODUCT_TAGS
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultAddProductTagsName").'</td>';
print '<td>'.$langs->trans("ExtraitCompteClientDefaultAddProductTagsDesc").'</td>';
if (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_ADD_PRODUCT_TAGS)) {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultaddproducttags&token='.newToken().'&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultaddproducttags&token='.newToken().'&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>' . "\n";

// EXTRAITCOMPTECLIENT_THIRDPARTY_REF
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ExtraitCompteClientThirdpertyRefName").'</td>';
print '<td>'.$langs->trans("ExtraitCompteClientThirdpertyRefDesc").'</td>';
if (!empty($conf->global->EXTRAITCOMPTECLIENT_THIRDPARTY_REF)) {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setthirdpartyref&token='.newToken().'&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setthirdpartyref&token='.newToken().'&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>' . "\n";

// EXTRAITCOMPTECLIENT_DEFAULT_ADD_MULTICURRENCY
if (!empty($conf->multicurrency->enabled)) {
    print '<tr class="oddeven">';
    print '<td>'.$langs->trans("ExtraitCompteClientDefaultAddMulticurrencyName").'</td>';
    print '<td>'.$langs->trans("ExtraitCompteClientDefaultAddMulticurrencyDesc").'</td>';
    if (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_ADD_MULTICURRENCY)) {
        print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultaddmulticurrency&token='.newToken().'&value=0">';
        print img_picto($langs->trans("Activated"), 'switch_on');
        print '</a></td>';
    } else {
        print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setdefaultaddmulticurrency&token='.newToken().'&value=1">';
        print img_picto($langs->trans("Disabled"), 'switch_off');
        print '</a></td>';
    }
    print '</tr>' . "\n";
}

print '</tbody>';
print '</table>';
print '</div>';

print '</form>';

print dol_get_fiche_end();

llxFooter();

$db->close();
