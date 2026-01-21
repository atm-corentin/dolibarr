<?php
/* Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 *      \file       htdocs/sirene/class/actions_sirene.class.php
 *      \ingroup    sirene
 *      \brief
 */

dol_include_once('/sirene/class/sirene.class.php');
dol_include_once('/sirene/lib/sirene.lib.php');


/**
 * Action Class for Dolibarr Hook
 */
class ActionsSirene
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;
	/**
	 * @var string Error
	 */
	public $error = '';
	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param       array			$parameters     Hook metadatas (context, etc...)
	 * @param       CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param       string          $action         Current action (if set). Generally create or edit or null
	 * @param       HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return      int                             Result < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $user;

		$context = explode(':', $parameters['context']);
		$confirm = GETPOST('confirm', 'alpha');
		if (in_array('thirdpartycard', $context)) {
			$langs->load('sirene@sirene');
			dol_include_once('/sirene/class/sirene.class.php');
			$sirene = new Sirene($this->db);

			// Set GET/POST infos of the selected company to create dolibarr form
			if ($action == 'sirene_set_company_infos') {
				/**
				 * @var	Societe	$object
				 */
				$company_infos = json_decode(GETPOST('sirene_selected_company', 'none'), true);

				if (isset($company_infos)) {
					$this->setGetPost('private', $company_infos['private']);
					$this->setGetPost('civility_id', $company_infos['civility']);
					$this->setGetPost('name', $sirene->getCompanyFinalName($object, $company_infos));
					$this->setGetPost('name_alias', $company_infos['company_name_alias']);
					$this->setGetPost('firstname', $company_infos['firstname']);
					$this->setGetPost('address', $company_infos['address']);
					$this->setGetPost('zipcode', $company_infos['zipcode']);
					$this->setGetPost('town', $company_infos['town']);
					$this->setGetPost('state_id', $company_infos['state_id']);
					$this->setGetPost('country_id', $company_infos['country_id']);
					$this->setGetPost('idprof1', $company_infos['siren']);
					$this->setGetPost('idprof2', $company_infos['siret']);
					$this->setGetPost('idprof3', $company_infos['codenaf_san']);
					$this->setGetPost('idprof6', $company_infos['rna']);
					$this->setGetPost('tva_intra', $company_infos['sirene_tva_intra']);
					$this->setGetPost('effectif_id', $company_infos['staff']);					// effectif
					$this->setGetPost('forme_juridique_code', $company_infos['legalcategory']);	// type d'entité légale niveau 2
					$this->setGetPost('options_sirene_company_admin_status', $company_infos['status']);

					// Prospecting map support
					if (!empty($conf->prospectingmap->enabled) && (!empty($company_infos['latitude']) || !empty($company_infos['longitude']))) {
						/*
							Les identifiants des carreaux sont établis à partir des normes fixées par la Ouvrir dans un nouvel ongletdirective Inspire qui se décompose de la manière suivante : « CRS » pour « coordinate reference system » + code_crs (code projection EPSG) + « RES » pour « résolution » + « 200m / 1000m » + « N » pour Nord + coordonnée_y_coin_inférieur_gauche + « E » pour Est + coordonnée_x_coin_inférieur_gauche.
							Les projections cartographiques utilisées sont les suivantes :
							Métropole : la projection utilisée est la projection Lambert 93 (EPSG 2154). Toutefois, la grille de carreaux a été produite à partir des données projetées en LAEA (EPSG 3035) qui est la projection utilisée au niveau européen. Les contours des carreaux ainsi obtenus ont ensuite été reprojetés en Lambert 93. L'identifiant Inspire du carreau décrit les coordonnées du coin en bas à gauche du carreau selon la projection LAEA.
							Martinique : la projection utilisée est la projection UTM 20N (EPSG 5490).
							La Réunion : la projection utilisée est la projection UTM 40S (EPSG 2975).
						 */
						dol_include_once('/prospectingmap/class/prospectingmap.class.php');
						$prospectingmap = new ProspectingMap($this->db);

						$converted_coordinate = $prospectingmap->convertCoordinates('EPSG:2154', getSireneDolGlobalString('PROSPECTINGMAP_COORDINATES_METRICS'), $company_infos['latitude'], $company_infos['longitude']);
						if (!isset($converted_coordinate)) {
							setEventMessage($prospectingmap->errorsToString(), 'errors');
						} else {
							// lat	45.7588809
							// long	4.9256323
							$this->setGetPost('map_latitude', $converted_coordinate[1]);
							$this->setGetPost('map_longitude', $converted_coordinate[0]);
						}
					}
				}

				$action = 'create';
			}

			// Update third party with sirene infos
			if ($action == 'sirene_confirm_check_company') {
				/**
				 * @var	Societe	$object
				 */
				$company_infos = json_decode(GETPOST('sirene_company_infos', 'none'), true);

				if (isset($company_infos)) {
					$updated = false;
					$object->oldcopy = clone $object;

					$updated |= $this->setObjectValue($object, 'company_name', 'name', $sirene->getCompanyFinalName($object, $company_infos));
					$updated |= $this->setObjectValue($object, 'company_name_alias', 'name_alias', $company_infos['company_name_alias']);
					$updated |= $this->setObjectValue($object, 'company_address', 'address', $company_infos['address']);
					$updated |= $this->setObjectValue($object, 'company_address', 'zip', $company_infos['zipcode']);
					$updated |= $this->setObjectValue($object, 'company_address', 'town', $company_infos['town']);
					$updated |= $this->setObjectValue($object, 'company_address', 'state_id', $company_infos['state_id']);
					$updated |= $this->setObjectValue($object, 'company_address', 'country_id', $company_infos['country_id']);
					$updated |= $this->setObjectValue($object, 'company_siren', 'idprof1', $company_infos['siren']);
					$updated |= $this->setObjectValue($object, 'company_siret', 'idprof2', $company_infos['siret']);
					$updated |= $this->setObjectValue($object, 'company_naf', 'idprof3', $company_infos['codenaf_san']);
					$updated |= $this->setObjectValue($object, 'company_rna', 'idprof6', $company_infos['rna']);
					$updated |= $this->setObjectValue($object, 'company_vat_intra', 'tva_intra', $company_infos['sirene_tva_intra']);
					$updated |= $this->setObjectValue($object, 'company_staff', 'effectif_id', $company_infos['staff']);
					$updated |= $this->setObjectValue($object, 'company_judicial_form', 'forme_juridique_code', $company_infos['legalcategory']);

					// Prospecting map support
					if (!empty($conf->prospectingmap->enabled) && GETPOST("sirene_update_company_address", 'int') && (!empty($company_infos['latitude']) || !empty($company_infos['longitude']))) {
						/*
							Les identifiants des carreaux sont établis à partir des normes fixées par la Ouvrir dans un nouvel ongletdirective Inspire qui se décompose de la manière suivante : « CRS » pour « coordinate reference system » + code_crs (code projection EPSG) + « RES » pour « résolution » + « 200m / 1000m » + « N » pour Nord + coordonnée_y_coin_inférieur_gauche + « E » pour Est + coordonnée_x_coin_inférieur_gauche.
							Les projections cartographiques utilisées sont les suivantes :
							Métropole : la projection utilisée est la projection Lambert 93 (EPSG 2154). Toutefois, la grille de carreaux a été produite à partir des données projetées en LAEA (EPSG 3035) qui est la projection utilisée au niveau européen. Les contours des carreaux ainsi obtenus ont ensuite été reprojetés en Lambert 93. L'identifiant Inspire du carreau décrit les coordonnées du coin en bas à gauche du carreau selon la projection LAEA.
							Martinique : la projection utilisée est la projection UTM 20N (EPSG 5490).
							La Réunion : la projection utilisée est la projection UTM 40S (EPSG 2975).
						 */
						$object->coordinate_longitude = $company_infos['latitude'];
						$object->coordinate_latitude = $company_infos['longitude'];
						$object->coordinate_format = 'EPSG:2154';
						$updated |= true;
					}

					// Set sirene fields
					$object->array_options['options_sirene_company_admin_status'] = $company_infos['status'];
					$object->array_options['options_sirene_update_date'] = dol_now();

					if ($updated) {
						$result = $object->update($object->id, $user);
					} else {
						$result = $object->insertExtraFields('COMPANY_MODIFY', $user);
					}
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						return -1;
					}
				}

				$action = '';
			}
		}

		return 0;
	}

	/**
	 * Overloading the formObjectOptions function : replacing the parent's function with the one below
	 *
	 * @param       array			$parameters     Hook metadatas (context, etc...)
	 * @param       CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param       string          $action         Current action (if set). Generally create or edit or null
	 * @param       HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return      int                             Result < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $form, $user;

		$context = explode(':', $parameters['context']);

		if (in_array('thirdpartycard', $context)) {
			// Management of the Code NAF
			if ($action != 'create' && $action != 'edit' && strtoupper((string) $object->country_code) == 'FR') {
				$codenaf = trim(strtoupper((string) $object->idprof3));
				if (!empty($codenaf)) {
					$langs->load('sirene@sirene');

					$sql = "SELECT label FROM " . MAIN_DB_PREFIX . "c_codenaf WHERE code = '" . $this->db->escape($codenaf) . "'";
					$resql = $this->db->query($sql);
					if ($resql) {
						if ($this->db->num_rows($resql) == 1) {
							$obj = $this->db->fetch_object($resql);
							$codenaf = $codenaf . ' - ' . $langs->trans((string) $obj->label);
						} else {
							$codenaf = '<span style="color:red">' . $codenaf . ' - ' . $langs->trans('SireneErrorCodeNafNotFound',
									dol_buildpath('/sirene/admin/dictionaries.php', 1) . '?module=sirene&name=sirenecodenaf') . '</span>';
						}
						$this->db->free($resql);

						$idprof3 = dol_escape_js($langs->transcountry('ProfId3', $object->country_code), 3);
						$codenaf = dol_escape_js($codenaf, 1);
						print <<<SCRIPT
	<script type="text/javascript" language="javascript">
		$(document).ready(function () {
			$("div.fichehalfleft td:contains('{$idprof3}')").next().html('{$codenaf}');
		});
	</script>
SCRIPT;
					} else {
						setEventMessage($this->db->lasterror(), "errors");
					}
				}
			}

			// Management of Sirene (Create card)
			if ($action == 'create' || $action == '' && empty($object->id)) {
				print '<tr id="sirene_infos"><td colspan="4">' . "\n";
				print '<div id="sirene_form_bloc">' . "\n";

				print '<input type="hidden" id="sirene_selected_company" name="sirene_selected_company">' . "\n";

				// Insert Sirene form
				include dol_buildpath('/sirene/core/tpl/sirene_search.tpl.php', 0);

				// Dialog box for result
				$formquestion = array(
					array(
						'name' => 'sirene_result',
						'type' => 'onecolumn',
						'value' => '<div id="sirene_result"></div>'
					)
				);
				print $form->formconfirm('#', $langs->trans("SireneSelectCompany"), $langs->trans("SireneConfirmSelectCompany"), "", $formquestion, 'no', 'sirene-search', 400, '75%');
				$yes_label = dol_escape_js($langs->transnoentitiesnoconv("Yes"), 3);
				$no_label = dol_escape_js($langs->transnoentitiesnoconv("No"), 3);
				$select_label = dol_escape_js($langs->transnoentitiesnoconv("Select"), 1);
				$cancel_label = dol_escape_js($langs->transnoentitiesnoconv("Cancel"), 1);
				$warning_select_a_company = dol_escape_js($langs->transnoentitiesnoconv("SireneWarningSelectACompany"), 1);

				// Search AJAX
				$ajax_url = dol_buildpath('/sirene/ajax/getSearchChoiceResults.php', 1);
				$token = newToken();

				print <<<SCRIPT
    <script type="text/javascript">
        $(document).ready(function () {
            let sirene_tr = $("tr#sirene_infos");
            let sirene_block = sirene_tr.find("div#sirene_form_bloc");
            let sirene_anchor = sirene_tr.closest('table');
			let sirene_search_button = sirene_block.find('button#sirene_search_btn');
			let sirene_search_button_waiting = sirene_block.find('button#sirene_search_waiting');

	    	// Move form to correct place
            sirene_block.detach().insertBefore(sirene_anchor);
            sirene_tr.remove();

			// Send search
			sirene_search_button.on('click', function (event) {
				// Disabled search button
				sirene_search_button_waiting.show();
				sirene_search_button.hide();

				// Get params to send
				let data_send = {};
				data_send.token = '{$token}';
				data_send.sirene_only_open = $('#sirene_only_open').is(':checked') ? 1 : 0;
				data_send.sirene_only_open_siege = $('#sirene_only_open_siege').is(':checked') ? 1 : 0;
				data_send.sirene_number = $('#sirene_number').val();
				data_send.sirene_company_name = $('#sirene_company_name').val();
				data_send.sirene_siren_siret = $('#sirene_siren_siret').val();
				data_send.sirene_naf = $('#sirene_naf').val();
				data_send.sirene_rna = $('#sirene_rna').val();
				data_send.sirene_town = $('#sirene_town').val();
				data_send.sirene_zipcode = $('#sirene_zipcode').val();

				$.ajax('{$ajax_url}', {
					method: "POST",
					data: data_send,
					dataType: "json"
				}).done(function (response) {
					if (typeof response.error === 'string') {
						/* jnotify(message, preset of message type, keepmessage) */
						$.jnotify(response.error, 'error', true, { remove: function() {} });
					} else if (typeof response.warning === 'string') {
						/* jnotify(message, preset of message type, keepmessage) */
						$.jnotify(response.warning, 'warning', false);
					} else if (typeof response.content === 'string') {
						// Insert result
						$('#sirene_result').empty().html(response.content);

						// Set company infos with the selected result
						let confirm_box = $('#dialog-confirm-sirene-search');
						let confirm_button_yes = confirm_box.closest('.ui-dialog').find('.ui-dialog-buttonset button:contains("{$yes_label}")');
						let confirm_button_no = confirm_box.closest('.ui-dialog').find('.ui-dialog-buttonset button:contains("{$no_label}")');
						confirm_button_yes.text('{$select_label}');
						confirm_button_no.text('{$cancel_label}');
						confirm_button_yes.unbind('click');
						confirm_button_yes.click(function () {
							let companies_infos = JSON.parse(response.companies_results);
							let selected_value = $('table#sirene_table tr td input.sirene_choice:checked').val();

							if (companies_infos.hasOwnProperty(selected_value)) {
								let company_infos = companies_infos[selected_value];
								$('input[name="action"]').val('sirene_set_company_infos');
								$('#sirene_selected_company').val(JSON.stringify(company_infos));
								$('form').submit();
							} else {
								/* jnotify(message, preset of message type, keepmessage) */
								$.jnotify('{$warning_select_a_company}', 'warning', false);
							}
						});

						// Show result
						confirm_box.dialog("open");
					}
				}).fail(function (jqxhr, textStatus, error) {
					/* jnotify(message, preset of message type, keepmessage) */
					$.jnotify(textStatus + ' - ' + error, 'error', true, { remove: function() {} });
				}).always(function () {
					// Enabled search button
					sirene_search_button_waiting.hide();
					sirene_search_button.show();
				});

				event.stopPropagation();
				return false;
			});
        });
    </script>
SCRIPT;
				print '</div>' . "\n";
				print '</td></tr>' . "\n";
			}

			// Check existing name
			print '<tr id="sirene_infos" style="display: none;"><td colspan="4">' . "\n";

			// Search AJAX
			$ajax_url = dol_buildpath('/sirene/ajax/searchCompanyName.php', 1);
			$token = newToken();

			$company_int = (int) $object->id;
			$company_name = dol_escape_js($object->name, 2);
			$waiting_message = dol_escape_js($langs->trans("SireneWaitingMessage"), 2);
			print <<<SCRIPT
    <script type="text/javascript">
        $(document).ready(function () {
			// Search where to put the check infos near the company name
			let sirene_insert_before = false;
            let sirene_name_td = $("input#name").closest('td');
			if (sirene_name_td.length === 0) {
				sirene_name_td = $("div.arearef").find('div.refid');
				let tmp = sirene_name_td.find('.refidno:first');
				if (tmp.length === 1) {
					sirene_name_td = tmp;
					sirene_insert_before = true;
				}
			}
			if (sirene_insert_before) {
				sirene_name_td.before('<span id="sirene_check_name" style="margin-left: 10px; position: rigth;"></span>');
			} else {
				sirene_name_td.append('<span id="sirene_check_name" style="margin-left: 10px; position: rigth;"></span>');
			}
            let sirene_check_name_content = $("span#sirene_check_name");

			sirene_search_comapny_name();
			// Check after providing a name
			let sirene_timer_check_company_name = null;
			$('input#name').on('keydown, change, input', function () {
				if (sirene_timer_check_company_name) clearTimeout(sirene_timer_check_company_name);
				sirene_timer_check_company_name = setTimeout(sirene_search_comapny_name, 500);
			});

			// Send search
			function sirene_search_comapny_name() {
				// Wainting text
				sirene_check_name_content.html('<i class="fa fa-spin fa-spinner" title="{$waiting_message}"></i>');

				// Get company name
				let name_value = $('input#name').val();
				if (typeof name_value == "undefined") name_value = "{$company_name}";

				$.ajax('{$ajax_url}', {
					method: "POST",
					data: {
						token: "{$token}",
						name: name_value,
						id: {$company_int}
					},
					dataType: "json"
				}).done(function (response) {
					if (response) {
						sirene_check_name_content.empty();

						if (typeof response.error === 'string') {
							/* jnotify(message, preset of message type, keepmessage) */
							$.jnotify(response.error, 'error', true, { remove: function() {} });
						} else if (typeof response.warning === 'string') {
							/* jnotify(message, preset of message type, keepmessage) */
							$.jnotify(response.warning, 'warning', false);
						} else if (typeof response.content === 'string') {
							$('#idfortooltiponclick_sirene_check_name_infos').closest('.ui-dialog').remove();
							sirene_check_name_content.html(response.content);
							sirene_check_name_content.find(".classfortooltiponclick").click(function () {
								console.log("We click on tooltip for element with dolid=" + $(this).attr('dolid'));
								if ($(this).attr('dolid')) {
									sirene_check_name_content.find(".classfortooltiponclicktext").dialog({ width: 'auto', autoOpen: false, open: function() { $(this).find('a:focus').blur(); } });
									obj = $("#idfortooltiponclick_" + $(this).attr('dolid'));
									obj.dialog("open");
								}
							});
							sirene_check_name_content.find(".classfortooltip").tooltip({
								show: { collision: "flipfit", effect:'toggle', delay:50 },
								hide: { delay: 50 }, 	/* If I enable effect:\'toggle\' here, a bug appears: the tooltip is shown when collpasing a new dir if it was shown before */
								tooltipClass: "mytooltip",
								content: function() {
									return $(this).prop('title');		/* To force to get title as is */
								},
							});
						}
					}
				}).fail(function (jqxhr, textStatus, error) {
					sirene_check_name_content.empty();

					/* jnotify(message, preset of message type, keepmessage) */
					$.jnotify(textStatus + ' - ' + error, 'error', true, { remove: function() {} });
				});
			}
	    });
    </script>
SCRIPT;
			print <<<STYLE
    <style>
		ul#sirene_check_name_company_list {
			max-height: 1024px;
			margin-top: 5px;
		}
		ul#sirene_check_name_company_list li {
			margin-top: 5px;
		}
    </style>
STYLE;
			print '</div>' . "\n";
			print '</td></tr>' . "\n";

			// if module addressefrance is not enabled and the address option of sirene is enabled
			if (empty($conf->adressefrance->enabled) && getSireneDolGlobalString('SIRENE_ADDRESSES') &&
				($action == 'create' || $action == 'edit')
			) {
				// search addresses frances api
				print '<script src="' . dol_buildpath('/sirene/js/search_addresses.js', 1) . '"></script>';
			}
		}

		return 0;
	}

	/**
	 * Overloading the getIdProfUrl function : replacing the parent's function with the one below
	 *
	 * @param       array			$parameters     Hook metadatas (context, etc...)
	 * @param       CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param       string          $action         Current action (if set). Generally create or edit or null
	 * @param       HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return      int                             Result < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function getIdProfUrl($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs;

		$url = '';
		if (!getSireneDolGlobalInt('MAIN_DISABLEPROFIDRULES')) {
			$idprof = $parameters['idprof'];
			$thirdparty = $parameters['company'];

			// TODO Move links to validate professional ID into a dictionary table "country" + "link"
			$strippedIdProf1 = str_replace(' ', '', $thirdparty->idprof1);
			if ($idprof == 1 && $thirdparty->country_code == 'FR') {
				$url = getSireneDolGlobalString('SIRENE_VERIFICATION_SIRET_URL', 'https://annuaire-entreprises.data.gouv.fr/entreprise/') . $strippedIdProf1;
			}
			if ($idprof == 1 && ($thirdparty->country_code == 'GB' || $thirdparty->country_code == 'UK')) {
				$url = 'https://beta.companieshouse.gov.uk/company/' . $strippedIdProf1;
			}
			if ($idprof == 1 && $thirdparty->country_code == 'ES') {
				$url = 'https://www.e-informa.es/servlet/app/portal/ENTP/screen/SProducto/prod/ETIQUETA_EMPRESA/nif/' . $strippedIdProf1;
			}
			if ($idprof == 1 && $thirdparty->country_code == 'IN') {
				$url = 'http://www.tinxsys.com/TinxsysInternetWeb/dealerControllerServlet?tinNumber=' . $strippedIdProf1 . ';&searchBy=TIN&backPage=searchByTin_Inter.jsp';
			}
			if ($idprof == 1 && $thirdparty->country_code == 'PT') {
				$url = 'https://www.nif.pt/' . $strippedIdProf1;
			}

			if (!empty($url)) {
				$this->resprints = '<a target="_blank" href="' . $url . '">' . $langs->trans('Check') . ' ' . img_picto($langs->trans("SireneIntraCheckDesc"), 'info') . '</a>';
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Overloading the replaceThirdparty function : replacing the parent's function with the one below
	 *
	 * @param       array			$parameters     Hook metadatas (context, etc...)
	 * @param       CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param       string          $action         Current action (if set). Generally create or edit or null
	 * @param       HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return      int                             Result < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function replaceThirdparty($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		$soc_origin = $parameters['soc_origin'];
		$soc_dest = $parameters['soc_dest'];

		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
		$company_src = new Societe($this->db);
		$result = $company_src->fetch($soc_origin);
		if ($result <= 0) {
			$langs->load('errors');
			$this->errors[] = $langs->trans("SireneErrorWhenFetchCompanySource", $soc_origin) . ' : ' . ($result == 0 ? $langs->trans('ErrorRecordNotFound') : $company_src->errorsToString());
			return -1;
		}

		$company_dst = new Societe($this->db);
		$result = $company_dst->fetch($soc_dest);
		if ($result <= 0) {
			$langs->load('errors');
			$this->errors[] = $langs->trans("SireneErrorWhenFetchCompanyTarget", $soc_dest) . ' : ' . ($result == 0 ? $langs->trans('ErrorRecordNotFound') : $company_dst->errorsToString());
			return -1;
		}

		if (!empty($company_src->idprof1) && !empty($company_dst->idprof1) && $company_src->idprof1 != $company_dst->idprof1) {
			$langs->load('sirene@sirene');
			$this->errors[] = $langs->trans("SireneErrorMismatchSiren");
			return -1;
		}
		if (!empty($company_src->idprof2) && !empty($company_dst->idprof2) && $company_src->idprof2 != $company_dst->idprof2) {
			$langs->load('sirene@sirene');
			$this->errors[] = $langs->trans("SireneErrorMismatchSiret");
			return -1;
		}

		return 0;
	}

	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param       array			$parameters     Hook metadatas (context, etc...)
	 * @param       CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param       string          $action         Current action (if set). Generally create or edit or null
	 * @param       HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return      int                             Result < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $langs, $form;
		$context = explode(':', $parameters['context']);

		if (in_array('thirdpartycard', $context)) {
			if ($user->rights->societe->creer) {
				$langs->loadLangs(array('sirene@sirene', 'companies'));

				// Add sirene button
				print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?socid=' . $object->id . '&action=sirene_check_company">' . $langs->trans(!empty($object->idprof2) ? 'SireneCheckSirene' : 'SireneSearchThirdparty') . '</a></div>';

				// Get action
				$act = GETPOST('action', 'aZ09');

				// Check company with sirene
				if ($act == 'sirene_check_company') {
					// Waiting
					$content = '<div id="sirene_waiting"><i class="fa fa-spin fa-spinner"></i> ' . $langs->trans("SireneWaitingMessage") . '</div>' . "\n";

					// Insert Sirene search form
					$content .= '<div id="sirene_search_form" style="display: none;">' . "\n";
					ob_start();
					include dol_buildpath('/sirene/core/tpl/sirene_search.tpl.php', 0);
					$content .= ob_get_clean();
					$content .= '</div>' . "\n";

					// Choice list result
					$content .= '<div id="sirene_choice_list_result" style="display: none;"></div>';

					// Insert Sirene update fields form
					$content .= '<div id="sirene_update_fields_choice_form" style="display: none;">' . "\n";
					ob_start();
					include dol_buildpath('/sirene/core/tpl/sirene_update_fields.tpl.php', 0);
					$content .= ob_get_clean();
					$content .= '</div>' . "\n";

					// Confirm messages
					$content .= '<div id="sirene_choice_list_result_confirm_message" class="confirmmessage" style="display: none;">' . img_help('', '') . ' ' . $langs->trans("SireneConfirmCheckCompanySearch") . '</div>';
					$content .= '<div id="sirene_update_fields_choice_form_confirm_message" class="confirmmessage centpercent" style="display: none;">' . img_help('', '') . ' ' . $langs->trans("SireneConfirmCheckCompanyUpdate") . '<button id="sirene_return_to_search" class="button" href="#" style="float: right;">' . $langs->trans("GoBack") . '</button></div>';

					// Dialog box for result
					$formquestion = array(
						array(
							'name' => 'sirene_form',
							'type' => 'onecolumn',
							'value' => $content
						)
					);
					print $form->formconfirm('#', $langs->trans("SireneCheckCompany"), '', "", $formquestion, 'no', 1, 750, '75%');
					$yes_label = dol_escape_js($langs->transnoentitiesnoconv("Yes"), 3);
					$no_label = dol_escape_js($langs->transnoentitiesnoconv("No"), 3);
					$select_label = dol_escape_js($langs->transnoentitiesnoconv("Select"), 1);
					$update_label = dol_escape_js($langs->transnoentitiesnoconv("SireneButtonUpdate"), 1);
					$cancel_label = dol_escape_js($langs->transnoentitiesnoconv("Cancel"), 1);
					$warning_select_a_company = dol_escape_js($langs->transnoentitiesnoconv("SireneWarningSelectACompany"), 1);
					$warning_launch_search_before = dol_escape_js($langs->transnoentitiesnoconv("SireneWarningLaunchSearchBefore"), 1);
					$status_closed = Sirene::COMPANY_ADMIN_STATUS_CLOSED;
					$status_opened = Sirene::COMPANY_ADMIN_STATUS_OPENED;

					// Search AJAX
					$ajax_search_url = dol_buildpath('/sirene/ajax/getSearchChoiceResults.php', 1);
					$ajax_search_siret_url = dol_buildpath('/sirene/ajax/getSearchBySiret.php', 1);
					$token = newToken();
					$siret = trim($object->idprof2);

					print <<<SCRIPT
    <script type="text/javascript">
        $(document).ready(function () {
			let sirene_waiting = $("div#sirene_waiting");
			let sirene_search_div = $("div#sirene_search_form");
			let sirene_search_button = sirene_search_div.find('button#sirene_search_btn');
			let sirene_search_button_waiting = sirene_search_div.find('button#sirene_search_waiting');
			let sirene_choice_list_result = $('div#sirene_choice_list_result');
			let sirene_update_fields_choice_form = $('div#sirene_update_fields_choice_form');
			let sirene_choice_list_result_confirm_message = $('div#sirene_choice_list_result_confirm_message');
			let sirene_update_fields_choice_form_confirm_message = $('div#sirene_update_fields_choice_form_confirm_message');
			let sirene_return_to_search = $('button#sirene_return_to_search');
			let sirene_warning_company_close = $('#sirene_warning_company_close');

			// Rewrite the yes button of the dialog box
			let confirm_box = $('div#sirene_search_form').closest('#dialog-confirm');
			confirm_box.on("dialogopen", function(event, ui) {
				let confirm_button_yes = confirm_box.closest('.ui-dialog').find('.ui-dialog-buttonset button:contains("{$yes_label}")');
				let confirm_button_no = confirm_box.closest('.ui-dialog').find('.ui-dialog-buttonset button:contains("{$no_label}")');
				confirm_button_yes.addClass('sirene_button_yes');
				confirm_button_no.addClass('sirene_button_no');
				confirm_button_yes.hide();
				confirm_button_no.text('{$cancel_label}');
				confirm_button_yes.unbind('click');
				confirm_button_yes.click(function () {
					if (sirene_update_fields_choice_form.is(':visible')) {
						sirene_update_fields_choice_form.find('form').submit();
					} else if (sirene_choice_list_result.is(':visible')) {
						let companies_infos = typeof window.sirene_companies_results != 'undefined' ? window.sirene_companies_results : null;
						let selected_value = $('table#sirene_table tr td input.sirene_choice:checked').val();

						if (companies_infos) {
							if (companies_infos.hasOwnProperty(selected_value)) {
								let company_infos = companies_infos[selected_value];
								sireneUpdateValues(company_infos);
								sireneSetMatchFieldsScreen();
							} else {
								// jnotify(message, preset of message type, keepmessage)
								$.jnotify('{$warning_select_a_company}', 'warning', false);
							}
						} else {
							// jnotify(message, preset of message type, keepmessage)
							$.jnotify('{$warning_launch_search_before}', 'warning', false);
						}
					}
				});
			});

			// Search by SIRET if provided
			if ('{$siret}'.length > 0) {
				let data_send = {};
				data_send.token = '{$token}';
				data_send.sirene_siret = '{$siret}';

				$.ajax('{$ajax_search_siret_url}', {
					method: "POST",
					data: data_send,
					dataType: "json"
				}).done(function (response) {
					if (typeof response.error === 'string') {
						// jnotify(message, preset of message type, keepmessage)
						$.jnotify(response.error, 'error', true, { remove: function() {} });
					} else if (typeof response.warning === 'string') {
						// jnotify(message, preset of message type, keepmessage)
						$.jnotify(response.warning, 'warning', false);
					} else if (typeof response.company_infos === 'string') {
						let data = JSON.parse(response.company_infos);

						if (data.status === '{$status_closed}') {
							sirene_warning_company_close.show();
							$('#sirene_company_name').val('');
							$('#sirene_siren_siret').val('{$siret}'.substring(0, 9));
							$('#sirene_naf').val('');
							$('#sirene_rna').val('');
							$('#sirene_town').val('');
							$('#sirene_zipcode').val('');
							sireneSetSearchCompaniesScreen();
						} else {
							// Set company infos found and show match values screen
							sireneUpdateValues(data);
							sireneSetMatchFieldsScreen();
						}
					} else {
						// Not found so launch the search with company infos
						sireneSetSearchCompaniesScreen();
					}
				}).fail(function (jqxhr, textStatus, error) {
					// jnotify(message, preset of message type, keepmessage)
					$.jnotify(textStatus + ' - ' + error, 'error', true, { remove: function() {} });

					// Show screen to search with company infos
					sireneSetSearchCompaniesScreen();
				}).always(function () {
					sirene_waiting.hide();
				});
			} else {
				sirene_waiting.hide();

				// No SIRET so launch the search with company infos
				sireneSetSearchCompaniesScreen();

				// Auto start search companies
				sireneSearchCompanies();
			}

			// Return to search
			sirene_return_to_search.click(function(event) {
				sireneSetSearchCompaniesScreen();

				event.stopPropagation();
				return false;
			});

			// Send search
			sirene_search_button.click(function (event) {
				sireneSearchCompanies();

				event.stopPropagation();
				return false;
			});

			// Show search companies screen
			function sireneSetSearchCompaniesScreen() {
				let confirm_button_yes = $('.sirene_button_yes');
				confirm_button_yes.text('{$select_label}');
				sirene_search_div.show();
				sirene_choice_list_result.show();
				if (!!window.sirene_companies_results) {
					confirm_button_yes.show();
					sirene_choice_list_result_confirm_message.show();
				} else {
					confirm_button_yes.hide();
					sirene_choice_list_result_confirm_message.hide();
				}
				sirene_update_fields_choice_form.hide();
				sirene_update_fields_choice_form_confirm_message.hide();
			}

			// Show match fields screen
			function sireneSetMatchFieldsScreen() {
				let confirm_button_yes = $('.sirene_button_yes');
				confirm_button_yes.text('{$update_label}');
				confirm_button_yes.show();
				sirene_warning_company_close.hide();
				sirene_search_div.hide();
				sirene_choice_list_result.hide();
				sirene_choice_list_result_confirm_message.hide();
				sirene_update_fields_choice_form.show();
				sirene_update_fields_choice_form_confirm_message.show();
			}

			// Search companies
			function sireneSearchCompanies() {
				// Disabled search button
				sirene_search_button_waiting.show();
				sirene_search_button.hide();

				window.sirene_companies_results = null;
				sirene_choice_list_result.empty();
				sireneSetSearchCompaniesScreen();

				// Get params to send
				let data_send = {};
				data_send.token = '{$token}';
				data_send.sirene_only_open = $('#sirene_only_open').is(':checked') ? 1 : 0;
				data_send.sirene_only_open_siege = $('#sirene_only_open_siege').is(':checked') ? 1 : 0;
				data_send.sirene_number = $('#sirene_number').val();
				data_send.sirene_company_name = $('#sirene_company_name').val();
				data_send.sirene_siren_siret = $('#sirene_siren_siret').val();
				data_send.sirene_naf = $('#sirene_naf').val();
				data_send.sirene_rna = $('#sirene_rna').val();
				data_send.sirene_town = $('#sirene_town').val();
				data_send.sirene_zipcode = $('#sirene_zipcode').val();

				$.ajax('{$ajax_search_url}', {
					method: "POST",
					data: data_send,
					dataType: "json"
				}).done(function (response) {
					if (typeof response.error === 'string') {
						// jnotify(message, preset of message type, keepmessage)
						$.jnotify(response.error, 'error', true, { remove: function() {} });
					} else if (typeof response.warning === 'string') {
						// jnotify(message, preset of message type, keepmessage)
						$.jnotify(response.warning, 'warning', false);
					} else if (typeof response.content === 'string') {
						// Insert result
						sirene_choice_list_result.html(response.content);
						window.sirene_companies_results = JSON.parse(response.companies_results);
						sireneSetSearchCompaniesScreen();

						// Auto select if only one found
						if (window.sirene_companies_results.length === 1 && window.sirene_companies_results[0].status === '{$status_opened}') {
							$('.sirene_button_yes').click();
						}
					}
				}).fail(function (jqxhr, textStatus, error) {
					// jnotify(message, preset of message type, keepmessage)
					$.jnotify(textStatus + ' - ' + error, 'error', true, { remove: function() {} });
				}).always(function () {
					// Enabled search button
					sirene_search_button_waiting.hide();
					sirene_search_button.show();
				});
			}
        });
    </script>
SCRIPT;
				}
			}
		}

		return 0;
	}

	/**
	 * Set GET/POST value of a property name
	 * @param	string	$name	Property name
	 * @param	mixed	$value	Value
	 * @return	void
	 */
	private function setGetPost($name, $value)
	{
		$_GET[$name] = $value;
		$_POST[$name] = $value;
	}

	/**
	 * Set object value if updated checked
	 * @param	Societe		$object				Company handler
	 * @param	string		$check_name			Name of the checkbox input
	 * @param	string		$property_name		Property name
	 * @param	mixed		$value				Value
	 * @return	bool							If property updated ?
	 */
	private function setObjectValue(&$object, $check_name, $property_name, $value)
	{
		if (GETPOST("sirene_update_" . $check_name, 'int')) {
			$object->{$property_name} = $value;
			return true;
		}

		return false;
	}
}
