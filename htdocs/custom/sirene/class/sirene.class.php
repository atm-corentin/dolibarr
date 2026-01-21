<?php
/* Copyright (C) 2025      Open-DSI             <support@open-dsi.fr>
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
 * \file    htdocs/sirene/class/sirene.class.php
 * \ingroup sirene
 * \brief
 */

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
dol_include_once('/sirene/class/client/SireneClientSireneApi.class.php');
dol_include_once('/sirene/lib/sirene.lib.php');


/**
 * Class Sirene
 *
 * Put here description of your class
 */
class Sirene
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

	const COMPANY_ADMIN_STATUS_CLOSED = 'F';
	const COMPANY_ADMIN_STATUS_OPENED = 'A';


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
	 *  Check all open companies who have SIRET with sirene (cron)
	 *
	 *  @return	int				0 if OK, < 0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function cronSirene()
	{
		global $user, $langs, $conf;
		$langs->load('sirene@sirene');
		$error = 0;
		$output = '';

		$errors_msg = '';
		$warnings_msg = '';
		$closed_companies_msg = '';
		$companies_not_found_msg = '';

		// Get all companies to check with sirene
		$sql = "SELECT s.rowid, s.nom as name, s.name_alias, s.siret as idprof2";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe AS s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_extrafields AS sef ON sef.fk_object = s.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country AS cc ON cc.rowid = s.fk_pays";
		$sql .= " WHERE s.status = 1";
		$sql .= " AND s.siret != ''";
		$sql .= " AND (cc.code IS NULL OR cc.code = 'FR')";
		$sql .= " AND (";
		$sql .= "   sef.sirene_update_date IS NULL";
		$sql .= "   OR sef.sirene_update_date <= '" . $this->db->idate(dol_time_plus_duree(dol_now(), -1 * max(1, getSireneDolGlobalInt('SIRENE_CRON_CHECK_FREQUENCY', 30)), 'd')) . "'";
		$sql .= " )";
		$sql .= " ORDER BY s.rowid ASC";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$msg = $langs->transnoentitiesnoconv('SireneErrorWhenGetAllCompaniesToCheck') . ' : ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . " " . $msg, LOG_ERR);
			$errors_msg .= '<li>' . $msg . '</li>';
			$error++;
		}

		$companies_to_check = array();
		if (!$error) {
			while ($obj = $this->db->fetch_object($resql)) {
				$siret = str_replace(' ', '', trim($obj->idprof2));
				if (empty($siret)) continue;
				$obj->idprof2 = $siret;

				if (strlen($siret) != 14) {
					$msg = $langs->transnoentitiesnoconv('SireneWarningCompanyHaveWrongSiret', $siret,
						'<a href="' . dol_buildpath('/societe/card.php', 2) . '?socid=' . $obj->rowid . '">' .
						$obj->name . (!empty($obj->name_alias) ? ' (' . $obj->name_alias . ')' : '') . '</a>');
					dol_syslog(__METHOD__ . " " . $msg, LOG_WARNING);
					$warnings_msg .= '<li>' .$msg . '</li>';
				} elseif (isset($companies_to_check[$siret])) {
					$msg = $langs->transnoentitiesnoconv('SireneWarningCompanyHaveSameSiret', $siret,
						'<a href="' . dol_buildpath('/societe/card.php', 2) . '?socid=' . $companies_to_check[$siret]->rowid . '">' .
						$companies_to_check[$siret]->name . (!empty($companies_to_check[$siret]->name_alias) ? ' (' . $companies_to_check[$siret]->name_alias . ')' : '') . '</a>',
						'<a href="' . dol_buildpath('/societe/card.php', 2) . '?socid=' . $obj->rowid . '">' .
						$obj->name . (!empty($obj->name_alias) ? ' (' . $obj->name_alias . ')' : '') . '</a>');
					dol_syslog(__METHOD__ . " " . $msg, LOG_WARNING);
					$warnings_msg .= '<li>' .$msg . '</li>';
				} else {
					$companies_to_check[$siret] = $obj;
				}
			}
			$this->db->free($resql);
		}

		if (!empty($companies_to_check)) {
			$nb_request_by_group = min(getSireneDolGlobalInt('SIRENE_NB_REQUEST_BY_GROUP', 100), 100);
			$nb_request_by_time_limit = max(getSireneDolGlobalInt('SIRENE_NB_REQUEST_BY_TIME_LIMIT', 20), 20);
			$time_limit_all_request = min(getSireneDolGlobalInt('SIRENE_TIME_LIMIT_ALL_REQUEST', 60), 60);

			// Regroup request block
			$grouped_companies_to_check = array();
			$current_group = array();
			$count = 0;
			foreach ($companies_to_check as $k => $v) {
				$current_group[$k] = $v;

				$count++;
				if ($count >= $nb_request_by_group) {
					$count = 0;
					$grouped_companies_to_check[] = $current_group;
					$current_group = array();
				}
			}
			if (!empty($current_group)) {
				$grouped_companies_to_check[] = $current_group;
			}

			$client_sirene = new SireneClientSireneApi($this->db);

			// Connection to sirene API
			$result = $client_sirene->connection();
			if ($result < 0) {
				$msg = $langs->transnoentitiesnoconv('SireneErrorWhenConnectToSirene') . ' : ' . $client_sirene->errorsToString();
				dol_syslog(__METHOD__ . " " . $msg, LOG_ERR);
				$errors_msg .= '<li>' . $msg . '</li>';
				$error++;
			} else {
				$idx = 0;
				$timer = time(); // seconds
				foreach ($grouped_companies_to_check as $companies_to_check) {
					// Get companies infos from sirene by siret
					$siret_to_check = array_keys($companies_to_check);
					$companies_results = $client_sirene->searchCompanies('', '', $siret_to_check, '', '', '', $nb_request_by_group);
					if (!isset($companies_results)) {
						$msg = $langs->transnoentitiesnoconv('SireneErrorWhileGetCompaniesBySiret', implode(', ', $siret_to_check)) . ': ' . $client_sirene->errorsToString();
						dol_syslog(__METHOD__ . " " . $msg, LOG_ERR);
						$errors_msg .= '<li>' . $msg . '</li>';
						$error++;
					} elseif (!empty($companies_results)) {
						$now = dol_now();

						// Process companies found
						$companies_found = [];
						foreach ($companies_results as $company_infos) {
							$company_sql_infos = $companies_to_check[$company_infos['siret']];
							$companies_found[] = $company_infos['siret'];

							$company = new Societe($this->db);
							$company->fetch($company_sql_infos->rowid);
							$company->oldcopy = clone $company;

							$updated = false;
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_COMPANY_NAME'), 'name', $this->getCompanyFinalName($company_infos['company_name'], $company_infos['town'], $company_infos['nic']));
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_COMPANY_NAME_ALIAS'), 'name_alias', $company_infos['company_name_alias']);
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_ADRESS'), 'address', $company_infos['address']);
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_ADRESS'), 'zip', $company_infos['zipcode']);
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_ADRESS'), 'town', $company_infos['town']);
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_ADRESS'), 'state_id', $company_infos['state_id']);
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_ADRESS'), 'country_id', $company_infos['country_id']);
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_SIREN'), 'idprof1', $company_infos['siren']);
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_SIRET'), 'idprof2', $company_infos['siret']);
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_NAF'), 'idprof3', $company_infos['codenaf_san']);
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_RNA'), 'idprof6', $company_infos['rna']);
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_TVA'), 'tva_intra', $company_infos['sirene_tva_intra']);
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_STAFF'), 'effectif_id', $company_infos['staff']);
							$updated |= $this->setObjectValue($company, getSireneDolGlobalInt('SIRENE_CRON_JURI_STATUS'), 'forme_juridique_code', $company_infos['legalcategory']);

							// Prospecting map support
							if (!empty($conf->prospectingmap->enabled) && getSireneDolGlobalInt('SIRENE_CRON_ADRESS') && (!empty($company_infos['latitude']) || !empty($company_infos['longitude']))) {
								/*
									Les identifiants des carreaux sont établis à partir des normes fixées par la Ouvrir dans un nouvel ongletdirective Inspire qui se décompose de la manière suivante : « CRS » pour « coordinate reference system » + code_crs (code projection EPSG) + « RES » pour « résolution » + « 200m / 1000m » + « N » pour Nord + coordonnée_y_coin_inférieur_gauche + « E » pour Est + coordonnée_x_coin_inférieur_gauche.
									Les projections cartographiques utilisées sont les suivantes :
									Métropole : la projection utilisée est la projection Lambert 93 (EPSG 2154). Toutefois, la grille de carreaux a été produite à partir des données projetées en LAEA (EPSG 3035) qui est la projection utilisée au niveau européen. Les contours des carreaux ainsi obtenus ont ensuite été reprojetés en Lambert 93. L'identifiant Inspire du carreau décrit les coordonnées du coin en bas à gauche du carreau selon la projection LAEA.
									Martinique : la projection utilisée est la projection UTM 20N (EPSG 5490).
									La Réunion : la projection utilisée est la projection UTM 40S (EPSG 2975).
								 */
								$company->coordinate_longitude = $company_infos['latitude'];
								$company->coordinate_latitude = $company_infos['longitude'];
								$company->coordinate_format = 'EPSG:2154';
								$updated |= true;
							}

							// Set sirene fields
							$company->array_options['options_sirene_company_admin_status'] = $company_infos['status'];
							$company->array_options['options_sirene_update_date'] = $now;

							if ($updated) {
								$result = $company->update($company->id, $user);
							} else {
								$result = $company->insertExtraFields('COMPANY_MODIFY', $user);
							}
							if ($result < 0) {
								$msg = $langs->transnoentitiesnoconv('SireneErrorWhileUpdateCompany',
									'<a href="' . dol_buildpath('/societe/card.php', 2) . '?socid=' . $company_sql_infos->rowid . '">' .
									$company_sql_infos->name . (!empty($company_sql_infos->name_alias) ? ' (' . $company_sql_infos->name_alias . ')' : '') . '</a>') .
									': ' . $company->errorsToString();
								dol_syslog(__METHOD__ . " " . $msg, LOG_ERR);
								$errors_msg .= '<li>' . $msg . '</li>';
								$error++;
							}

							if ($company_infos['status'] == Sirene::COMPANY_ADMIN_STATUS_CLOSED) {
								// Company closed
								$closed_companies_msg .= '<li><a href="' . dol_buildpath('/societe/card.php', 2) . '?socid=' . $company_sql_infos->rowid . '">' . $company_infos['company_name_all'] . '</a><br>';
								$closed_companies_msg .= $langs->transnoentitiesnoconv('SireneSiren') . ' : ' . $company_infos['siren'] . '<br>';
								$closed_companies_msg .= $langs->transnoentitiesnoconv('SireneSiret') . ' : ' . $company_infos['siret'] . '<br>';
								$closed_companies_msg .= $langs->transnoentitiesnoconv('SireneCodeNaf') . ' : ' . $company_infos['codenaf_all'] . '<br>';
								$closed_companies_msg .= $langs->transnoentitiesnoconv('Address') . ' : ' . $company_infos['address_all'] . '<br>';
								$closed_companies_msg .= $langs->transnoentitiesnoconv('StatusSireneMail') . ' : ' . $company_infos['status_all'] . '<br>';
								$closed_companies_msg .= $langs->transnoentitiesnoconv('SireneCreateDate') . ' : ' . $company_infos['date_creation'] . '</li>';
							}
						}

						// Process companies not found
						$companies_not_found = array_diff($siret_to_check, $companies_found);
						if (!empty($companies_not_found)) {
							foreach ($companies_not_found as $siret) {
								$company_sql_infos = $companies_to_check[$siret];
								// Company not found
								$companies_not_found_msg .= '<li><a href="' . dol_buildpath('/societe/card.php', 2) . '?socid=' . $company_sql_infos->rowid . '">' .
									$company_sql_infos->name . (!empty($company_sql_infos->name_alias) ? ' (' . $company_sql_infos->name_alias . ')' : '') . '</a></li>';
							}
						}
					}

					// Sleep when more request than x by y second
					$idx++;
					$elapsed_time = time() - $timer;
					if ($elapsed_time > $time_limit_all_request) {
						// Time limit exceeded so we reset the timer
						$idx = 0;
						$timer = time(); // seconds
					} elseif ($idx >= $nb_request_by_time_limit) {
						// Nb request authorized for the limit time exceeded so we sleep the remaining timer time and reset the timer
						sleep($time_limit_all_request - $elapsed_time);
						$idx = 0;
						$timer = time(); // seconds
					}
				}
			}
		}

		// Send mail if we have warnings, errors, companies closed or companies not found
		if (getSireneDolGlobalString('SIRENE_MAIL_TO_SEND') && (!empty($warnings_msg) || !empty($errors_msg) || !empty($closed_companies_msg) || !empty($companies_not_found_msg))) {
			$sendto = getSireneDolGlobalString('SIRENE_MAIL_TO_SEND');
			$from = $this->formatEmail(dol_string_nospecial(getSireneDolGlobalString('MAIN_MAIL_EMAIL_FROM'), ' ', array(",")), getSireneDolGlobalString('MAIN_MAIL_EMAIL_FROM'));

			$subject = '[' . getSireneDolGlobalString('MAIN_INFO_SOCIETE_NOM') . '] ' . $langs->transnoentities('SireneMailCheckCompaniesSubject');
			// Format result message (duplicated for escape out of memory)
			$message = '';
			if (!empty($closed_companies_msg)) {
				$message .= $langs->transnoentitiesnoconv('SireneMailListCompaniesClosed') . ' :<ul>' . $closed_companies_msg . '</ul><br>';
			}
			if (!empty($companies_not_found_msg)) {
				$message .= $langs->transnoentitiesnoconv('SireneMailListCompaniesNotFound') . ' :<ul>' . $companies_not_found_msg . '</ul><br>';
			}
			if (!empty($warnings_msg)) {
				$message .= $langs->transnoentitiesnoconv('SireneMailListWarnings') . ' :<span style="color: darkorange;"><ul>' . $warnings_msg . '</ul></span><br>';
			}
			if (!empty($errors_msg)) {
				$message .= $langs->transnoentitiesnoconv('SireneMailListErrors') . ' :<span style="color: red;"><ul>' . $errors_msg . '</ul></span><br>';
			}
			$message = dol_nl2br($langs->trans('SireneMailCompaniesClosedBody') . '<br><br>' . $message);

			$result = $this->sendEmail($subject, $sendto, $from, $message);
			if (is_numeric($result) && $result < 0) {
				$output .= '<span style="color: red;">' . $langs->trans('Error') . ': ' . $this->errorsToString('<br>') . '</span><br>';
			} else {
				$output .= '<pre>' . $result . '</pre>';
			}
		}

		// Format result message (duplicated for escape out of memory)
		if (!empty($closed_companies_msg)) {
			$output .= $langs->transnoentitiesnoconv('SireneMailListCompaniesClosed') . ' :<ul>' . $closed_companies_msg . '</ul><br>';
		}
		if (!empty($companies_not_found_msg)) {
			$output .= $langs->transnoentitiesnoconv('SireneMailListCompaniesNotFound') . ' :<ul>' . $companies_not_found_msg . '</ul><br>';
		}
		if (!empty($warnings_msg)) {
			$output .= $langs->transnoentitiesnoconv('SireneMailListWarnings') . ' :<span style="color: darkorange;"><ul>' . $warnings_msg . '</ul></span><br>';
		}
		if (!empty($errors_msg)) {
			$output .= $langs->transnoentitiesnoconv('SireneMailListErrors') . ' :<span style="color: red;"><ul>' . $errors_msg . '</ul></span><br>';
		}

		if ($error) {
			$this->error = $langs->trans('SireneCheckInfosFail') . '<br>' . $output;
			$this->errors = array();
			return -1;
		}

		$this->error = "";
		$this->errors = array();
		$this->output = $langs->trans('SireneCheckInfosSuccess') . '<br>' . $output;
		$this->result = array("commandbackuplastdone" => "", "commandbackuptorun" => "");

		return 0;
	}

	/**
	 *  Get company final name
	 *
	 * @param   Societe     $object       	Object handler
	 * @param   array      	$company_infos	Company infos from API
	 * @return  string                  	Final name
	 */
	public function getCompanyFinalName($object, $company_infos)
	{
		global $conf;

		$final_name = $company_infos['company_name'] ?? null;
		if (getSireneDolGlobalInt('SIRENE_ADD_NIC_TOWN_IN_NAME_IF_DUPLICATE') && !empty($final_name)) {
			$sql = "SELECT COUNT(*) AS nb";
			$sql .= " FROM " . MAIN_DB_PREFIX . "societe";
			$sql .= " WHERE entity IN (" . getEntity('societe') . ")";
			$sql .= " AND rowid != " . ((int)$object->id);
			$sql .= " AND nom = '" . $this->db->escape($final_name) . "'";
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($obj = $this->db->fetch_object($resql)) {
					if ($obj->nb > 0) {
						if (!empty($company_infos['town'])) $final_name .= ' - ' . $company_infos['town'];
						if (!empty($company_infos['nic'])) $final_name .= ' - ' . $company_infos['nic'];
					}
				}
			} else {
				dol_syslog(__METHOD__ . " getCompanyFinalName error " . $this->db->lasterror(), LOG_ERR);
			}
		}

		return $final_name;
	}

	/**
	 *  Get emails list of all the assigned to the request
	 *
	 * @param   string      $name       Name of the user
	 * @param   string      $email      Address email
	 * @return  string                  Formatted email (RFC 2822: "Name firstname <email>" or "email" or "<email>")
	 */
	public function formatEmail($name, $email)
	{
		if (!preg_match('/<|>/i', $email) && !empty($name)) {
			$email = str_replace(array('<', '>'), '', $name) . ' <' . $email . '>';
		}

		return $email;
	}
	/**
	 *  Send notification to the assigned, requesters, watchers for a type of notification
	 *
	 * @param   string	        $subject             Topic/Subject of mail
	 * @param   array|string	$sendto              List of recipients emails  (RFC 2822: "Name firstname <email>" or "email" or "<email>")
	 * @param   string	        $from                Sender email               (RFC 2822: "Name firstname <email>" or "email" or "<email>")
	 * @param   string	        $body                Body message
	 * @param   array	        $filename_list       List of files to attach (full path of filename on file system)
	 * @param   array	        $mimetype_list       List of MIME type of attached files
	 * @param   array	        $mimefilename_list   List of attached file name in message
	 * @param   array|string	$sendtocc            Email cc
	 * @param   array|string	$sendtobcc           Email bcc (Note: This is autocompleted with MAIN_MAIL_AUTOCOPY_TO if defined)
	 * @param   int		        $deliveryreceipt     Ask a delivery receipt
	 * @param   int		        $msgishtml           1=String IS already html, 0=String IS NOT html, -1=Unknown make autodetection (with fast mode, not reliable)
	 * @param   string	        $errors_to      	 Email for errors-to
	 * @param   string	        $css                 Css option
	 * @param   string          $moreinheader        More in header. $moreinheader must contains the "\r\n" (TODO not supported for other MAIL_SEND_MODE different than 'phpmail' and 'smtps' for the moment)
	 * @param   string          $sendcontext      	 'standard', 'emailing', ...
	 * @return  int|string                           <0 if KO, result message if OK
	 */
	public function sendEmail($subject, $sendto, $from, $body, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array(), $sendtocc = "", $sendtobcc = "", $deliveryreceipt = 0, $msgishtml = 1, $errors_to = '', $css = '', $moreinheader = '', $sendcontext = 'standard')
	{
		global $langs, $dolibarr_main_url_root;
		dol_syslog(__METHOD__ . " subject=$subject, sendto=$sendto, from=$from, body=$body, filename_list=".json_encode($filename_list).", mimetype_list=".json_encode($mimetype_list).", mimefilename_list=".json_encode($mimefilename_list).", sendtocc=$sendtocc, sendtobcc=$sendtobcc, deliveryreceipt=$deliveryreceipt, msgishtml=$msgishtml, errors_to=$errors_to, css=$css, moreinheader=$moreinheader, sendcontext=$sendcontext", LOG_DEBUG);
		$this->errors = array();

		$langs->load('mails');

		// Check parameters
		$sendto = is_array($sendto) ? implode(',', $sendto) : $sendto;
		$sendtocc = is_array($sendtocc) ? implode(',', $sendtocc) : $sendtocc;
		$sendtobcc = is_array($sendtobcc) ? implode(',', $sendtobcc) : $sendtobcc;

		if (!empty($sendto)) {
			// Define $urlwithroot
			$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
			$urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;   // This is to use external domain name found into config file
			//$urlwithroot=DOL_MAIN_URL_ROOT;                     // This is to use same domain name than current

			// Make a change into HTML code to allow to include images from medias directory with an external reabable URL.
			// <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
			// become
			// <img alt="" src="'.$urlwithroot.'viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
			$body = preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1' . $urlwithroot . '/viewimage.php\2modulepart=medias\3file=\4\5', $body);

			// Send mail
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$mailfile = new CMailFile($subject, $sendto, $from, $body, $filename_list, $mimetype_list, $mimefilename_list, $sendtocc, $sendtobcc, $deliveryreceipt, $msgishtml, $errors_to, $css, '', $moreinheader, $sendcontext);
			if (!empty($mailfile->error)) {
				$this->errors[] = $mailfile->error;
			} else {
				$result = $mailfile->sendfile();
				if ($result) {
					return $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($sendto, 2));
				} else {
					$langs->load("other");
					$mesg = '<div class="error">';
					if ($mailfile->error) {
						$mesg .= $langs->trans('ErrorFailedToSendMail', $from, $sendto);
						$mesg .= '<br>' . $mailfile->error;
					} else {
						$mesg .= 'No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
					}
					$mesg .= '</div>';
					$this->errors[] = $mesg;
				}
			}
		} else {
			$langs->load("errors");
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailTo"));
		}

		dol_syslog(__METHOD__ . " Error: {$this->errorsToString()}", LOG_ERR);
		return -1;
	}

	/**
	 * Set object value if updated checked
	 * @param	Societe		$object				Company handler
	 * @param	bool		$can_update			Can update the property
	 * @param	string		$property_name		Property name
	 * @param	mixed		$value				Value
	 * @return	bool							If property updated ?
	 */
	private function setObjectValue(&$object, $can_update, $property_name, $value)
	{
		if (!empty($can_update)) {
			$object->{$property_name} = $value;
			return true;
		}

		return false;
	}

	/**
	 * Method to output saved errors
	 *
	 * @param	string      $separator      Separator between each error
	 * @return	string		                String with errors
	 */
	public function errorsToString($separator = ', ')
	{
		return $this->error . (is_array($this->errors) ? (!empty($this->error) ? $separator : '') . join($separator, $this->errors) : '');
	}
}
