<?php
declare(strict_types=1);
/* Copyright (C) 2007-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025-2026  Grégory Maza            <gregory.maza@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       chaumeilrfa_list_fourn.php
 * \ingroup    clichaumeil
 * \brief      Global supplier RFA list backed by the yearly summary table.
 */

$res = 0;
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
	$res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php';
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen((string) $tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] === $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)).'/main.inc.php')) {
	$res = @include substr($tmp, 0, ($i + 1)).'/main.inc.php';
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php')) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php';
}
if (!$res && file_exists('../main.inc.php')) {
	$res = @include '../main.inc.php';
}
if (!$res && file_exists('../../main.inc.php')) {
	$res = @include '../../main.inc.php';
}
if (!$res && file_exists('../../../main.inc.php')) {
	$res = @include '../../../main.inc.php';
}
if (!$res) {
	die('Include of main fails');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once __DIR__.'/class/chaumeilrfa.class.php';
require_once __DIR__.'/class/Rfa/RfaSummarySourceRepository.php';
require_once __DIR__.'/class/Rfa/RfaSummaryStorageManager.php';

$langs->loadLangs(array('clichaumeil@clichaumeil', 'other'));

$action = GETPOST('action', 'aZ09') ?: 'view';
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ?: str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php'));
$optioncss = GETPOST('optioncss', 'aZ');
$mode = GETPOST('mode', 'aZ');
$groupby = GETPOST('groupby', 'aZ09');
$searchYear = GETPOSTINT('yearid');
if (empty($searchYear)) {
	$searchYear = (int) date('Y');
}

$limit = GETPOSTINT('limit') ?: (int) $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}
$offset = $limit * $page;

$object = new ChaumeilRfa($db);
$hookmanager->initHooks(array($contextpage, 'globalrfalist'));

$arrayfields = array(
	'fk_soc' => array(
		'label' => $langs->trans('Suppliers'),
		'checked' => 1,
		'type' => 'integer',
		'enabled' => 1,
		'visible' => 1,
		'position' => 10,
	),
	'ca_achats' => array(
		'label' => $langs->trans('ClichaumeilCaYearHT'),
		'checked' => 1,
		'type' => 'price',
		'enabled' => 1,
		'visible' => 1,
		'position' => 20,
		'help' => $langs->trans('CliChaumeil_RfaListAggregatedAmountHelp'),
	),
	'taux_rfa' => array(
		'label' => $langs->trans('ClichaumeilRate').' %',
		'checked' => 1,
		'type' => 'float',
		'enabled' => 1,
		'visible' => 1,
		'position' => 30,
		'help' => $langs->trans('ClichaumeilLevelReachedRate'),
	),
	'discount_amount_rfa' => array(
		'label' => $langs->trans('ClichaumeilDiscount'),
		'checked' => 1,
		'type' => 'price',
		'enabled' => 1,
		'visible' => 1,
		'position' => 40,
		'help' => $langs->trans('ClichaumeilEstimatedAmountDiscount'),
	),
	'status' => array(
		'label' => $langs->trans('Status'),
		'checked' => 1,
		'type' => 'status',
		'enabled' => 1,
		'visible' => 1,
		'position' => 50,
		'arrayofkeyval' => array(
			$object::STATUS_DRAFT => $langs->trans('RfaStatusDraft'),
			$object::STATUS_WON => $langs->trans('RfaStatusWon'),
			$object::STATUS_LOST => $langs->trans('RfaStatusLost'),
		),
		'help' => $langs->trans('ClichaumeilRfaStatus'),
	),
);

if (!$sortfield) {
	$sortfield = 'fk_soc';
}
if (!$sortorder) {
	$sortorder = 'ASC';
}

$search = array(
	'fk_soc' => GETPOST('search_fk_soc', 'alphanohtml'),
	'ca_achats' => GETPOST('search_ca_achats', 'alphanohtml'),
	'taux_rfa' => GETPOST('search_taux_rfa', 'alphanohtml'),
	'discount_amount_rfa' => GETPOST('search_discount_amount_rfa', 'alphanohtml'),
	'status' => GETPOST('search_status', 'alphanohtml'),
);

if ($search['status'] === '-1') {
	$search['status'] = '';
}
if ($search['fk_soc'] === '-1' || $search['fk_soc'] === '0') {
	$search['fk_soc'] = '';
}

$permissiontoread = $user->hasRight('clichaumeil', 'chaumeilrfa', 'read');
$permissiontowrite = $user->hasRight('clichaumeil', 'chaumeilrfa', 'write');
$canRebuildSummary = $permissiontowrite;

if ($user->socid > 0) {
	accessforbidden();
}
if (!isModEnabled('clichaumeil') || !$permissiontoread) {
	accessforbidden();
}

if ($cancel) {
	$action = 'list';
}

$parameters = array('arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
}

if (empty($reshook) && (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha'))) {
	foreach (array_keys($search) as $searchKey) {
		$search[$searchKey] = '';
	}
}

$repository = new RfaSummarySourceRepository($db);
$listRows = array();
$num = 0;
$hasSummaryForYear = false;
$isSummaryStorageReady = true;

if (empty($reshook)) {
	try {
		$isSummaryStorageReady = $repository->isSummaryStorageReady();
		$num = $repository->countSummaryRowsForYear($searchYear, $search);
		$listRows = $repository->fetchSummaryRowsForYear($searchYear, $search, $sortfield, $sortorder, $offset, $limit);
		$hasSummaryForYear = $repository->hasSummaryForYear($searchYear);
	} catch (Throwable $exception) {
		dol_syslog(__FILE__.' '.$exception->getMessage(), LOG_ERR);
		setEventMessages($langs->trans('CliChaumeil_RfaListLoadError'), null, 'errors');
		$listRows = array();
		$num = 0;
		$hasSummaryForYear = false;
		$isSummaryStorageReady = false;
	}
}

$form = new Form($db);
$formother = new FormOther($db);
$title = $langs->trans('ChaumeilRfas');

llxHeader('', $title, '', '', 0, 0, array(), array(), '', 'mod-clichaumeil page-list bodyforlist');

$param = '';
if ($mode !== '') {
	$param .= '&mode='.urlencode($mode);
}
if ($contextpage !== '' && $contextpage !== $_SERVER['PHP_SELF']) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit !== (int) $conf->liste_limit) {
	$param .= '&limit='.(int) $limit;
}
if ($optioncss !== '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($groupby !== '') {
	$param .= '&groupby='.urlencode($groupby);
}
foreach ($search as $searchKey => $searchValue) {
	if ($searchValue !== '') {
		$param .= '&search_'.$searchKey.'='.urlencode($searchValue);
	}
}
$param .= '&yearid='.(int) $searchYear;

print '<form method="POST" id="searchFormList" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">'."\n";
if ($optioncss !== '') {
	print '<input type="hidden" name="optioncss" value="'.dol_escape_htmltag($optioncss).'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
print '<input type="hidden" name="page" value="'.((int) $page).'">';
print '<input type="hidden" name="contextpage" value="'.dol_escape_htmltag($contextpage).'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.dol_escape_htmltag($mode).'">';

$newcardbutton = dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER['PHP_SELF'].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', (empty($mode) || $mode === 'common') ? 2 : 1, array('morecss' => 'reposition'));
$rebuildUrl = '';
if ($canRebuildSummary) {
	$rebuildUrl = dol_buildpath('/clichaumeil/scripts/rebuild_rfa_summary.php', 1);
	$rebuildButtonLabel = $langs->transnoentitiesnoconv('CliChaumeil_RfaSummaryRebuildActionForYear', $searchYear);
	$rebuildLoadingLabel = $langs->transnoentitiesnoconv('CliChaumeil_RfaSummaryRebuildLoadingForYear', $searchYear);
	$newcardbutton .= '<a id="clichaumeil-rfa-summary-rebuild-button" class="butAction reposition" href="#"';
	$newcardbutton .= ' data-url="'.dol_escape_htmltag($rebuildUrl).'"';
	$newcardbutton .= ' data-year="'.((int) $searchYear).'"';
	$newcardbutton .= ' data-loading-label="'.dol_escape_htmltag($rebuildLoadingLabel).'"';
	$newcardbutton .= ' data-error-label="'.dol_escape_htmltag($langs->transnoentitiesnoconv('Error')).'"';
	$newcardbutton .= ' data-reload-delay="700"';
	$newcardbutton .= '><span class="fas fa-sync"></span> '.dol_escape_htmltag($rebuildButtonLabel).'</a>';
}

print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, 0, $object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

if ($canRebuildSummary) {
	print '<div id="clichaumeil-rfa-summary-rebuild-feedback" class="marginbottomonly" style="display:none;"></div>';
	print '<script src="'.dol_buildpath('/clichaumeil/js/rfa_summary_list.js', 1).'"></script>';
}

$moreforfilter = '<div class="divsearchfield">'.$langs->trans('ByYear').' : '.$formother->selectyear($searchYear, 'yearid').'</div>';
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action);
if (empty($reshook)) {
	$moreforfilter .= $hookmanager->resPrint;
} else {
	$moreforfilter = $hookmanager->resPrint;
}

if ($moreforfilter !== '') {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

if (!$isSummaryStorageReady) {
	print info_admin($langs->trans('CliChaumeil_RfaSummaryStorageMissing'), 0, 0, 'warning');
} elseif (!$hasSummaryForYear) {
	print info_admin($langs->trans('CliChaumeil_RfaSummaryMissingForYear', $searchYear), 0, 0, 'warning');
}

$varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, $conf->main_checkbox_left_column);
$selectedfields = ($mode !== 'kanban' ? $htmlofselectarray : '');

print '<div class="div-table-responsive">';
print '<table class="tagtable noborder liste'.($moreforfilter ? ' listwithfilterbefore' : '').'">'."\n";

print '<tr class="liste_titre_filter">';
if ($conf->main_checkbox_left_column) {
	print '<td class="liste_titre center maxwidthsearch">';
	print $form->showFilterButtons('left');
	print '</td>';
}

foreach ($arrayfields as $key => $val) {
	if (empty($arrayfields[$key]['checked'])) {
		continue;
	}

	$cssforfield = '';
	if ($key === 'status') {
		$cssforfield = 'center';
	} elseif (in_array($val['type'], array('price', 'float'), true)) {
		$cssforfield = 'right';
	}

	print '<td class="liste_titre'.($cssforfield !== '' ? ' '.$cssforfield : '').'">';
	if ($key === 'fk_soc') {
		print $form->select_company($search['fk_soc'], 'search_fk_soc', '(s.fournisseur:=:1)', 'SelectThirdParty', 0, 0, array(), 0, 'maxwidth250');
	} elseif ($key === 'status') {
		print $form->selectarray('search_status', $val['arrayofkeyval'], $search['status'], 1, 0, 0, '', 1, 0, 0, '', 'maxwidth100 search_status width100', 1);
	} else {
		print '<input type="text" class="flat maxwidth75'.($cssforfield !== '' ? ' right' : '').'" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
	}
	print '</td>';
}

$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action);
print $hookmanager->resPrint;

if (!$conf->main_checkbox_left_column) {
	print '<td class="liste_titre center maxwidthsearch">';
	print $form->showFilterButtons();
	print '</td>';
}
print '</tr>'."\n";

print '<tr class="liste_titre">';
if ($conf->main_checkbox_left_column) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
}
foreach ($arrayfields as $key => $val) {
	if (empty($arrayfields[$key]['checked'])) {
		continue;
	}

	$cssforfield = '';
	if ($key === 'status') {
		$cssforfield = 'center';
	} elseif (in_array($val['type'], array('price', 'float'), true)) {
		$cssforfield = 'right';
	}

	print getTitleFieldOfList($val['label'], 0, $_SERVER['PHP_SELF'], $key, '', $param, ($cssforfield !== '' ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield !== '' ? $cssforfield.' ' : ''), 0, (isset($val['help']) ? $val['help'] : ''))."\n";
}

$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action);
print $hookmanager->resPrint;

if (!$conf->main_checkbox_left_column) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
}
print '</tr>'."\n";

$totalCaAchats = 0.0;
$totalDiscountAmount = 0.0;

foreach ($listRows as $listRow) {
	$rowObject = (object) $listRow;
	$object->status = (int) $rowObject->status;
	print '<tr class="oddeven">';

	if ($conf->main_checkbox_left_column) {
		print '<td class="nowrap center"></td>';
	}

	foreach ($arrayfields as $key => $val) {
		if (empty($arrayfields[$key]['checked'])) {
			continue;
		}

		$cssforfield = '';
		if ($key === 'status') {
			$cssforfield = 'center';
		} elseif (in_array($val['type'], array('price', 'float'), true)) {
			$cssforfield = 'right';
		}

		print '<td'.($cssforfield !== '' ? ' class="'.$cssforfield.'"' : '').'>';

		if ($key === 'fk_soc') {
			$thirdpartyUrl = DOL_URL_ROOT.'/societe/card.php?socid='.(int) $rowObject->fk_soc;
			print '<a href="'.dol_escape_htmltag($thirdpartyUrl).'">'.dol_escape_htmltag((string) $rowObject->soc_name).'</a>';
		} elseif ($key === 'ca_achats') {
			$totalCaAchats += (float) $rowObject->ca_achats;
			if (!empty($rowObject->is_aggregated)) {
				print '<span title="'.dol_escape_htmltag($langs->trans('CliChaumeil_RfaListAggregatedAmountNoLinkHelp')).'">'.price((float) $rowObject->ca_achats).'</span>';
			} else {
				$url = sprintf(
					'%s/fourn/facture/list.php?socid=%d&search_date_startday=1&search_date_startmonth=1&search_date_startyear=%d&search_date_endday=31&search_date_endmonth=12&search_date_endyear=%d&search_status=%d',
					DOL_URL_ROOT,
					(int) $rowObject->fk_soc,
					(int) $searchYear,
					(int) $searchYear,
					(int) FactureFournisseur::STATUS_CLOSED
				);
				print '<a href="'.dol_escape_htmltag($url).'">'.price((float) $rowObject->ca_achats).'</a>';
			}
		} elseif ($key === 'taux_rfa') {
			print price((float) $rowObject->taux_rfa, 0, $langs, 0, 2, 2);
		} elseif ($key === 'discount_amount_rfa') {
			$totalDiscountAmount += (float) $rowObject->discount_amount_rfa;
			print price((float) $rowObject->discount_amount_rfa);
		} elseif ($key === 'status') {
			print $object->getLibStatut(5);
		} else {
			print dol_escape_htmltag((string) $rowObject->$key);
		}

		print '</td>';
	}

	$parameters = array('arrayfields' => $arrayfields, 'object' => $object, 'obj' => $rowObject);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action);
	print $hookmanager->resPrint;

	if (!$conf->main_checkbox_left_column) {
		print '<td class="nowrap center"></td>';
	}

	print '</tr>'."\n";
}

if (!empty($listRows)) {
	print '<tr class="liste_total">';
	if ($conf->main_checkbox_left_column) {
		print '<td></td>';
	}
	foreach ($arrayfields as $key => $val) {
		if (empty($arrayfields[$key]['checked'])) {
			continue;
		}

		$cssforfield = '';
		if ($key === 'status') {
			$cssforfield = 'center';
		} elseif (in_array($val['type'], array('price', 'float'), true)) {
			$cssforfield = 'right';
		}

		print '<td'.($cssforfield !== '' ? ' class="'.$cssforfield.'"' : '').'>';
		if ($key === 'fk_soc') {
			print $langs->trans('Total');
		} elseif ($key === 'ca_achats') {
			print price($totalCaAchats);
		} elseif ($key === 'discount_amount_rfa') {
			print price($totalDiscountAmount);
		} else {
			print '&nbsp;';
		}
		print '</td>';
	}
	if (!$conf->main_checkbox_left_column) {
		print '<td></td>';
	}
	print '</tr>'."\n";
}

if ($num === 0) {
	$colspan = count(array_filter($arrayfields, static function (array $fieldDefinition): bool {
		return !empty($fieldDefinition['checked']);
	})) + 1;
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans('NoRecordFound').'</span></td></tr>';
}

$parameters = array('arrayfields' => $arrayfields, 'total_count' => $num);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action);
print $hookmanager->resPrint;

print '</table>';
print '</div>';
print '</form>';

llxFooter();
$db->close();
