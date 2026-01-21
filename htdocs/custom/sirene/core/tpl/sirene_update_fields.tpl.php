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

if (!isset($form)) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
	$form = new Form($this->db);
}
$langs->loadLangs(array('companies', 'sirene@sirene'));

print "<!-- BEGIN PHP TEMPLATE sirene_update_fields.tpl.php -->\n";

print '<div id="sirene_update_field_content">' . "\n";

print <<<STYLE
	<style>
		.liste_titre.sirene_update_field {
			font-size: large;
		}
		.sirene_icon {
			font-size: 28px !important;
			margin-right: 4px !important;
		}
		#sirene_update_field_content td {
			vertical-align: middle !important;
		}
		.siren_green {
			color: #118822 !important;
		}
		.siren_red {
			color: #881111 !important;
		}
	</style>
STYLE;

$object_name = dol_escape_js($object->name, 1);
$object_name_alias = dol_escape_js($object->name_alias, 1);
$object_address = dol_escape_js($object->address, 1);
$object_zip = dol_escape_js($object->zip, 1);
$object_town = dol_escape_js($object->town, 1);
$object_state_code = dol_escape_js($object->state_code, 1);
$object_country = dol_escape_js($object->country, 1);
$object_siren = dol_escape_js($object->idprof1, 1);
$object_siret = dol_escape_js($object->idprof2, 1);
$object_naf = dol_escape_js($object->idprof3, 1);
$object_rna = dol_escape_js($object->idprof6, 1);
$object_vat_intra = dol_escape_js($object->tva_intra, 1);
$object_staff = dol_escape_js($object->effectif, 1);
$object_judicial_form = dol_escape_js($object->forme_juridique, 1);

// Manage checkbox button
print <<<SCRIPT
	<script type="text/javascript">
		$(document).ready(function() {
			let check_all_input = $("#sirene_check_all");
			check_all_input.on('click', function() {
				if (check_all_input.is(':checked')) {
					$(".sirene_check i.fa-times").hide();
					$(".sirene_check i.fa-caret-left").show();
					$(".sirene_check input").val(1);
				} else {
					$(".sirene_check i.fa-times").show();
					$(".sirene_check i.fa-caret-left").hide();
					$(".sirene_check input").val(0);
				}
			});

			$('#sirene_update_field_content tr').on('click', function() {
				let _this = $(this);
				let target_id = _this.attr('data-check-target');
				let target_span = $('#' + target_id);
				let target_icon_question = target_span.find('i.fa-times');
				let target_icon_update = target_span.find('i.fa-caret-left');
				let target_input = target_span.find('input');
				let status = target_input.val();

				if (status === '1') {
					target_icon_question.show();
					target_icon_update.hide();
					target_input.val(0);
				} else {
					target_icon_question.hide();
					target_icon_update.show();
					target_input.val(1);
				}

				// Check / uncheck the checkbox for all
				let checked = true;
				$.map($(".sirene_check:visible input"), function (item, idx) {
					checked &= $(item).val() === '1';
				});
				check_all_input.prop('checked', checked);
			});
		});

		function sireneUpdateValue(field_name_check, datas) {
			let match = true;
			$.map(datas, function(item, idx) {
				console.log('idx', idx, 'item', item, 'match', item.dval === item.sval);
				match &= item.dval.localeCompare(item.sval, "fr", { sensitivity: "accent" }) === 0;
				$('td#sirene_' + item.fname).text(item.sval);
			});

			if (match) {
				$('#sirene_match_' + field_name_check).show();
				$('#sirene_mismatch_' + field_name_check).hide();
			} else {
				$('#sirene_match_' + field_name_check).hide();
				$('#sirene_mismatch_' + field_name_check).show();
			}

			return match;
		}

		function sireneUpdateValues(data) {
			$('#sirene_company_infos').val(JSON.stringify(data));

			// Reset selected fields to be updated
			$(".sirene_check i.fa-times").show();
			$(".sirene_check i.fa-caret-left").hide();
			$(".sirene_check input").val(0);

			let check_all_input = $("#sirene_check_all");
			let match = true;
			match &= sireneUpdateValue('company_name', [
				{ fname: 'company_name', dval: '{$object_name}', sval: data.company_name }
			]);
			match &= sireneUpdateValue('company_name_alias', [
				{ fname: 'company_name_alias', dval: '{$object_name_alias}', sval: data.company_name_alias }
			]);
			match &= sireneUpdateValue('company_address', [
				{ fname: 'company_address', dval: '{$object_address}', sval: data.address },
				{ fname: 'company_zip', dval: '{$object_zip}', sval: data.zipcode },
				{ fname: 'company_town', dval: '{$object_town}', sval: data.town },
				{ fname: 'company_state_code', dval: '{$object_state_code}', sval: data.state_san },
				{ fname: 'company_country', dval: '{$object_country}', sval: data.country }
			]);
			match &= sireneUpdateValue('company_siren', [
				{ fname: 'company_siren', dval: '{$object_siren}', sval: data.siren }
			]);
			match &= sireneUpdateValue('company_siret', [
				{ fname: 'company_siret', dval: '{$object_siret}', sval: data.siret }
			]);
			match &= sireneUpdateValue('company_naf', [
				{ fname: 'company_naf', dval: '{$object_naf}', sval: data.codenaf_san }
			]);
			match &= sireneUpdateValue('company_rna', [
				{ fname: 'company_rna', dval: '{$object_rna}', sval: data.rna }
			]);
			match &= sireneUpdateValue('company_vat_intra', [
				{ fname: 'company_vat_intra', dval: '{$object_vat_intra}', sval: data.sirene_tva_intra }
			]);
			match &= sireneUpdateValue('company_staff', [
				{ fname: 'company_staff', dval: '{$object_staff}', sval: data.staff_label }
			]);
			match &= sireneUpdateValue('company_judicial_form', [
				{ fname: 'company_judicial_form', dval: '{$object_judicial_form}', sval: data.legalcategory_id }
			]);

			if (match) {
				check_all_input.hide();
			} else {
				check_all_input.show();
			}
		}
	</script>
SCRIPT;

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="sirene_confirm_check_company">';
print '<input type="hidden" id="sirene_company_infos" name="sirene_company_infos" value="">';

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">' . "\n";

// Headers
print '<tr class="liste_titre">' . "\n";
print '<td class="liste_titre sirene_update_field">' . $form->textwithpicto($langs->trans("SireneFieldName"), $langs->trans("SireneFieldNameHelp")) . '</td>' . "\n";
print '<td class="liste_titre sirene_update_field">' . $form->textwithpicto($langs->trans("SireneDataDolibarr"), $langs->trans("SireneDataDolibarrHelp")) . '</td>' . "\n";
print '<td class="width25 center"><input type="checkbox" id="sirene_check_all"></td>';
print '<td class="liste_titre sirene_update_field">' . $form->textwithpicto($langs->trans("SireneDataSirene"), $langs->trans("SireneDataSireneHelp")) . '</td>' . "\n";
print '</tr>' . "\n";

// Company name
print '<tr data-check-target="sirene_mismatch_company_name">' . "\n";
print '<td>' . $langs->trans('SireneCompanyName') . '</td>' . "\n";
print '<td>' . $object->name . '</td>' . "\n";
print '<td class="center">' . sirenePrintCheckbox('company_name') . '</td>' . "\n";
print '<td id="sirene_company_name"></td>' . "\n";
print '</tr>' . "\n";

// Company name alias
print '<tr data-check-target="sirene_mismatch_company_name_alias">' . "\n";
print '<td>' . $langs->trans('SireneCompanyNameAlias') . '</td>' . "\n";
print '<td>' . $object->name_alias . '</td>' . "\n";
print '<td class="center">' . sirenePrintCheckbox('company_name_alias') . '</td>' . "\n";
print '<td id="sirene_company_name_alias"></td>' . "\n";
print '</tr>' . "\n";

// Company address
print '<tr data-check-target="sirene_mismatch_company_address">' . "\n";
print '<td>' . $langs->trans('Address') . '</td>' . "\n";
print '<td>' . $object->address . '</td>' . "\n";
print '<td rowspan="5" class="center">' . sirenePrintCheckbox('company_address') . '</td>' . "\n";
print '<td id="sirene_company_address"></td>' . "\n";
print '</tr>' . "\n";

// Company zip
print '<tr data-check-target="sirene_mismatch_company_address">' . "\n";
print '<td>' . $langs->trans('Zip') . '</td>' . "\n";
print '<td>' . $object->zip . '</td>' . "\n";
print '<td id="sirene_company_zip"></td>' . "\n";
print '</tr>' . "\n";

// Company town
print '<tr data-check-target="sirene_mismatch_company_address">' . "\n";
print '<td>' . $langs->trans('Town') . '</td>' . "\n";
print '<td>' . $object->town . '</td>' . "\n";
print '<td id="sirene_company_town"></td>' . "\n";
print '</tr>' . "\n";

// Company state code
print '<tr data-check-target="sirene_mismatch_company_address">' . "\n";
print '<td>' . $langs->trans('StateShort') . '</td>' . "\n";
print '<td>' . $object->state_code . '</td>' . "\n";
print '<td id="sirene_company_state_code"></td>' . "\n";
print '</tr>' . "\n";

// Company country
print '<tr data-check-target="sirene_mismatch_company_address">' . "\n";
print '<td>' . $langs->trans('Country') . '</td>' . "\n";
print '<td>' . $object->country . '</td>' . "\n";
print '<td id="sirene_company_country"></td>' . "\n";
print '</tr>' . "\n";

// Company SIREN
print '<tr data-check-target="sirene_mismatch_company_siren">' . "\n";
print '<td>' . $langs->trans('SireneSiren') . '</td>' . "\n";
print '<td>' . $object->idprof1 . '</td>' . "\n";
print '<td class="center">' . sirenePrintCheckbox('company_siren') . '</td>' . "\n";
print '<td id="sirene_company_siren"></td>' . "\n";
print '</tr>' . "\n";

// Company SIRET
print '<tr data-check-target="sirene_mismatch_company_siret">' . "\n";
print '<td>' . $langs->trans('SireneSiret') . '</td>' . "\n";
print '<td>' . $object->idprof2 . '</td>' . "\n";
print '<td class="center">' . sirenePrintCheckbox('company_siret') . '</td>' . "\n";
print '<td id="sirene_company_siret"></td>' . "\n";
print '</tr>' . "\n";

// Company code NAF
print '<tr data-check-target="sirene_mismatch_company_naf">' . "\n";
print '<td>' . $langs->trans('SireneCodeNaf') . '</td>' . "\n";
print '<td>' . $object->idprof3 . '</td>' . "\n";
print '<td class="center">' . sirenePrintCheckbox('company_naf') . '</td>' . "\n";
print '<td id="sirene_company_naf"></td>' . "\n";
print '</tr>' . "\n";

// Company RNA
print '<tr data-check-target="sirene_mismatch_company_rna">' . "\n";
print '<td>' . $langs->trans('SireneRna') . '</td>' . "\n";
print '<td>' . $object->idprof6 . '</td>' . "\n";
print '<td class="center">' . sirenePrintCheckbox('company_rna') . '</td>' . "\n";
print '<td id="sirene_company_rna"></td>' . "\n";
print '</tr>' . "\n";

// Company VAT intra
print '<tr data-check-target="sirene_mismatch_company_vat_intra">' . "\n";
print '<td>' . $langs->trans('SireneTvaIntra') . '</td>' . "\n";
print '<td>' . $object->tva_intra . '</td>' . "\n";
print '<td class="center">' . sirenePrintCheckbox('company_vat_intra') . '</td>' . "\n";
print '<td id="sirene_company_vat_intra"></td>' . "\n";
print '</tr>' . "\n";

// Company staff
print '<tr data-check-target="sirene_mismatch_company_staff">' . "\n";
print '<td>' . $langs->trans('DictionaryStaff') . '</td>' . "\n";
print '<td>' . $object->effectif . '</td>' . "\n";
print '<td class="center">' . sirenePrintCheckbox('company_staff') . '</td>' . "\n";
print '<td id="sirene_company_staff"></td>' . "\n";
print '</tr>' . "\n";

// Company juridical form
print '<tr data-check-target="sirene_mismatch_company_judicial_form">' . "\n";
print '<td>' . $langs->trans('SireneJuridicalStatusLevel2') . '</td>' . "\n";
print '<td>' . $object->forme_juridique . '</td>' . "\n";
print '<td class="center">' . sirenePrintCheckbox('company_judicial_form') . '</td>' . "\n";
print '<td id="sirene_company_judicial_form"></td>' . "\n";
print '</tr>' . "\n";

print '</table>' . "\n";
print '</div>' . "\n";

print '</form>' . "\n";

print '</div>' . "\n";

print "<!-- END PHP TEMPLATE sirene_update_fields.tpl.php -->\n";

/**
 * Print html code for checkbox or check icon of update field
 * @param	string	$field_name		Field name to be updated
 * @return	string					HTML code
 */
function sirenePrintCheckbox($field_name)
{
	global $langs;

	$out = '<span id="sirene_match_' . $field_name . '" style="display: none;">' . "\n";
	$out .= '<i class="fas fa-check sirene_icon siren_green" title="' . $langs->trans("SireneIconCheckHelp") . '"></i>' . "\n";
	$out .= '</span>' . "\n";
	$out .= '<span id="sirene_mismatch_' . $field_name . '" class="nowrap sirene_check">' . "\n";
	$out .= '<i class="fas fa-times sirene_icon siren_red" title="' . $langs->trans("SireneIconNoUpdateHelp") . '"></i>' . "\n";
	$out .= '<i class="fas fa-caret-left sirene_icon" title="' . $langs->trans("SireneIconUpdateHelp") . '" style="display: none;"></i>' . "\n";
	$out .= '<input type="hidden" name="sirene_update_' . $field_name . '" value="0">' . "\n";
	$out .= '</span>' . "\n";

	return $out;
}
