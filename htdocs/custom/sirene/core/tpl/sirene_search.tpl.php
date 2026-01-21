<?php
/* Copyright (C) 2025      	Open-Dsi     <support@open-dsi.fr>
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
 *
 * Need to have following variables defined:
 * $this (class)
 */

// Protection to avoid direct call of template
if (empty($this) || !is_object($this)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}

/**
 * @var Societe $object
 */

global $conf, $langs, $form, $mysoc;
dol_include_once('/sirene/lib/sirene.lib.php');

if (!isset($form)) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
	$form = new Form($this->db);
}
$langs->load('sirene@sirene');

print "<!-- BEGIN PHP TEMPLATE sirene_search.tpl.php -->\n";

print '<div id="sirene_search_content">' . "\n";

print <<<STYLE
	<style>
		@media only screen and (max-width: 1200px) {
			.displaygridonsmartphone {
				display: grid;
				grid-template-columns: 1fr 1fr;
			}
			.tablehead{
				grid-template-columns: 2fr 1fr;
			}
		
			table.liste td, table.noborder td, div.noborder form div, table.tableforservicepart1 td, table.tableforservicepart2 td {
				height: unset !important;
				border-bottom: unset !important;
			}
		}
		
		@media only screen and (max-width: 570px) {
			.displaygridonsmartphone {
				grid-template-columns: 1fr;
			}
		}

		.warning_text {
			font-size:16px;
			font-weight:bold
		}
	</style>
STYLE;

// Manage more/less criteria button
$more_criteria_label = dol_escape_js($langs->transnoentitiesnoconv("SireneSearchMoreCriteria"), 1);
$less_criteria_label = dol_escape_js($langs->transnoentitiesnoconv("SireneSearchLessCriteria"), 1);
print <<<SCRIPT
	<script type="text/javascript">
		$(document).ready(function () {
			let sirene_more_less_criteria_button = $("#btn_hidden_inputs");
			let sirene_second_criteria = $("#second_line_inputs_siren");

			// Manage more/less criteria button
			sirene_more_less_criteria_button.on('click', function () {
				if (sirene_second_criteria.is(':visible')) {
					sirene_more_less_criteria_button.text('{$more_criteria_label}');
					sirene_second_criteria.hide();
				} else {
					sirene_more_less_criteria_button.text('{$less_criteria_label}');
					sirene_second_criteria.show();
				}
			});
		});
	</script>
SCRIPT;

print '<table class="noborder centpercent tableforfieldcreate table">' . "\n";

print '<tr id="sirene_warning_company_close" style="display:none; margin:1em 1em;"><td colspan=4 class="warning_text">' . $langs->trans('SireneWarningClosedThirdParty') . '</td></tr>';

//-------------------------
// Header
//-------------------------
print '<tr class="displaygridonsmartphone tablehead">' . "\n";

print '<td colspan="3" class="maxwidth400onsmartphone">';
// Title and help
print '<u>' . $langs->trans("SireneSearchTitle") . '</u> ' . $form->textwithpicto('', $langs->trans("SireneSearchHelp"), 2) . "\n";

// Option : Open company only
$only_open = GETPOSTISSET('sirene_number') ? (GETPOST('sirene_only_open', 'int') ? 1 : 0) : 1;
print '<br><label for="sirene_only_open">' . $langs->trans("SireneSearchOnlyOpen") . '</label> : <input type="checkbox" id="sirene_only_open" name="sirene_only_open" value="1"' . ($only_open ? ('checked="checked"') : '') . '>' . "\n";

// Option : Siege company only
$only_open_siege = getSireneDolGlobalInt('SIRENE_SIEGE_BY_DEFAULT') ? 1 : 0;
if (GETPOSTISSET('sirene_number')) {
	$only_open_siege = GETPOST('sirene_only_open_siege', 'int') ? 1 : 0;
}
print '&nbsp;&nbsp;&nbsp;<label for="sirene_only_open_siege">' . $langs->trans("SireneSearchOnlyOpenSiege") . '</label> : <input type="checkbox" id="sirene_only_open_siege" name="sirene_only_open_siege" value="1"' . ($only_open_siege ? ('checked="checked"') : '') . '>' . "\n";
print '</td>' . "\n";

// More criteria button
print '<td class="center" style="vertical-align: middle;"><button class="button" type="button" id="btn_hidden_inputs" >' . $langs->trans('SireneSearchMoreCriteria') . '</button></td>';
print '</tr>' . "\n";

//-------------------------
// First criteria line
//-------------------------
print '<tr class="displaygridonsmartphone">' . "\n";

// Search : Company name
$sirene_company_name = GETPOST('sirene_company_name', 'alpha');
if (empty($sirene_company_name) && !empty($object->name)) $sirene_company_name = $object->name;
$placeholder = dol_escape_js(empty($mysoc->name) ? "ex. Association dolibarr" : "ex. " . $mysoc->name, 2);
print '<td class="maxwidth400onsmartphone">';
print '<label for="sirene_company_name">' . $langs->trans("SireneCompanyName") . '</label>';
print '<br><input type="text" class="flat" id="sirene_company_name" name="sirene_company_name" placeholder="' . $placeholder . '" value="' . dol_escape_js($sirene_company_name, 2) . '">';
print '</td>' . "\n";

// Search : Company SIREN / SIRET
$siren_siret = GETPOST('sirene_siren_siret', 'alpha');
if (empty($siren_siret) && !empty($object->idprof2)) $siren_siret = $object->idprof2;
if (empty($siren_siret) && !empty($object->idprof1)) $siren_siret = $object->idprof1;
$siren_siret = str_replace(' ', '', $siren_siret);
$idprof1 = trim(str_replace(' ', '', (string) $mysoc->idprof1));
$placeholder = dol_escape_js(empty($idprof1) ? "ex. 520339938" : "ex. " . $idprof1, 2);
print '<td class="maxwidth400onsmartphone">';
print '<label for="sirene_siren_siret">' . $langs->trans("SireneSiren") . ' / ' . $langs->trans("SireneSiret") . '</label>';
print '<br><input type="text" class="flat" id="sirene_siren_siret" name="sirene_siren_siret" placeholder="' . $placeholder . '" value="' . dol_escape_js($siren_siret, 2) . '">';
print '</td>' . "\n";

// Search : Company code NAF
$sirene_naf = GETPOST('sirene_naf', 'alpha');
if (empty($sirene_naf) && !empty($object->idprof3)) $sirene_naf = $object->idprof3;
$sirene_naf = str_replace(' ', '', $sirene_naf);
if (!empty($sirene_naf) && stristr($sirene_naf, '.') === false) {
	$sirene_naf = substr($sirene_naf, 0, 2) . '.' . substr($sirene_naf, 2, 3);
}
$idprof3 = trim(str_replace(' ', '', (string) $mysoc->idprof3));
$placeholder = dol_escape_js(empty($idprof3) ? "ex. 94.99Z" : "ex. " . substr($idprof3, 0, 2) . '.' . substr($idprof3, 2, 3), 2);
print '<td class="maxwidth400onsmartphone">';
print '<label for="sirene_naf">' . $langs->trans("SireneCodeNaf") . ' ' . $form->textwithpicto('', $langs->trans("SireneSearchCodeNafHelp"), 2) . '</label>';
print '<br><input type="text" class="flat" id="sirene_naf" name="sirene_naf" placeholder="' . $placeholder . '" value="' . dol_escape_js($sirene_naf, 2) . '">';
print '</td>' . "\n";

// Search : Company RNA
$sirene_rna = GETPOST('sirene_rna', 'alpha');
if (empty($sirene_rna) && !empty($object->idprof6)) $sirene_rna = $object->idprof6;
$sirene_rna = str_replace(' ', '', $sirene_rna);
$idprof6 = trim(str_replace(' ', '', (string) $mysoc->idprof6));
$placeholder = dol_escape_js(empty($idprof6) ? "ex. rna" : "ex. " . $idprof6, 2);
print '<td class="maxwidth400onsmartphone">';
print '<label for="sirene_rna">' . $langs->trans("SireneRna") . '</label>';
print '<br><input type="text" class="flat" id="sirene_rna" name="sirene_rna" placeholder="' . $placeholder . '" value="' . dol_escape_js($sirene_rna, 2) . '">';
print '</td>' . "\n";

print '</tr>' . "\n";

//--------------------------------
// Second criteria line (hidden)
//--------------------------------
print '<tr class="displaygridonsmartphone" id="second_line_inputs_siren" style="display:none">' . "\n";

// Search : Company town
$sirene_town = GETPOST('sirene_town', 'alpha');
if (empty($sirene_town) && !empty($object->town)) $sirene_town = $object->town;
$placeholder = dol_escape_js(empty($mysoc->town) ? "ex. Lyon" : "ex. " . $mysoc->town, 2);
print '<td class="maxwidth400onsmartphone">';
print '<label for="sirene_town">' . $langs->trans("Town") . '</label>';
print '<br><input type="text" class="flat" id="sirene_town" name="sirene_town" placeholder="' . $placeholder . '" value="' . dol_escape_js($sirene_town, 2) . '">';
print '</td>' . "\n";

// Search : Company zip
$sirene_zipcode = GETPOST('sirene_zipcode', 'alpha');
if (empty($sirene_zipcode) && !empty($object->zip)) $sirene_zipcode = $object->zip;
$placeholder = dol_escape_js(empty($mysoc->zip) ? "ex. 45160" : "ex. " . $mysoc->zip, 2);
print '<td class="maxwidth400onsmartphone">';
print '<label for="sirene_zipcode">' . $langs->trans("Zip") . '</label>';
print '<br><input type="text" class="flat" id="sirene_zipcode" name="sirene_zipcode" placeholder="' . $placeholder . '" value="' . dol_escape_js($sirene_zipcode, 2) . '">';
print '</td>' . "\n";

// Search : Number of result maximum
print '<td class="maxwidth400onsmartphone" colspan="2">';
print '<label for="sirene_number">' . $langs->trans("SireneSearchNumber") . '</label>';
print '<br><input type="number" class="flat" id="sirene_number" name="sirene_number" placeholder="20" min="20" max="100" size="3" value="' . dol_escape_js(GETPOST('sirene_number', 'int'), 2) . '">';
print '</td>' . "\n";

print '</tr>' . "\n";

print '</table>' . "\n";

// Auto display more criteria if zip or town provided
$auto_show_more_criteria = !empty($sirene_town) || !empty($sirene_zipcode) ? 'true' : 'false';
print <<<SCRIPT
	<script type="text/javascript">
		$(document).ready(function () {
			// Auto display more criteria if zip or town provided
			if ({$auto_show_more_criteria}) {
				$("#btn_hidden_inputs").text('{$less_criteria_label}');
				$("#second_line_inputs_siren").show();
			}
		});
	</script>
SCRIPT;

//--------------------------------
// Search Button
//--------------------------------
print '<div class="center">';
print '<button id="sirene_search_btn" class="button">' . $langs->trans("Search") . '</button>';
print '<button id="sirene_search_waiting" class="button butActionRefused" style="display: none;"><i class="fa fa-spin fa-spinner"></i> ' . $langs->trans("SireneWaitingMessage") . '</button>';
print '</div>' . "\n";

print '</div>' . "\n";

print "<!-- END PHP TEMPLATE sirene_search.tpl.php -->\n";
