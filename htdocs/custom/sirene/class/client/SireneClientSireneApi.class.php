<?php
/* Copyright (C) 2025       Open-Dsi		<support@open-dsi.fr>
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
 *      \file       htdocs/sirene/class/client/SireneClientSireneApi.class.php
 *      \ingroup    sirene
 *      \brief      This file for managing Client Sirene API
 */

dol_include_once('/sirene/class/client/SireneClientApi.class.php');
dol_include_once('/sirene/class/sirene.class.php');
dol_include_once('/sirene/lib/sirene.lib.php');

/**
 * 	Class to manage Client Sirene API
 */
class SireneClientSireneApi extends SireneClientApi
{
	/**
	 * @var string  API brearer key
	 */
	public $api_bearer_key;


	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		global $conf;
		parent::__construct($db);

		$this->api_bearer_key = getSireneDolGlobalString('SIRENE_API_BEARER_KEY');
		$this->api_url = getSireneDolGlobalString('SIRENE_API_URI', 'https://api.insee.fr/api-sirene/3.11/');
		$this->api_no_verify_ssl = empty(getSireneDolGlobalInt('SIRENE_API_VERIFY_SSL'));
	}

	/**
	 *  Send to the Api
	 *
	 * @param string $method Method request
	 * @param string $url Url request
	 * @param array $options Options request
	 * @param bool $without_prefix Without api url prefix
	 * @param int $status_code Status code returned
	 * @param int $error_info Error info returned
	 * @return    array                                        null if KO otherwise result data
	 */
	public function sendToApi($method, $url, $options = [], $without_prefix = false, &$status_code = null, &$error_info = null)
	{
		// Authentication
		$options['headers']['X-INSEE-Api-Key-Integration'] = $this->api_bearer_key ?? '';

		return parent::sendToApi($method, $url, $options, $without_prefix, $status_code, $error_info);
	}

	/**
	 * Suggest a solution for the error.
	 * Mainly for codes 401 and 500.
	 *
	 * @param	int		$err_code	Error code
	 * @return	string				Translated string for a solution
	 */
	protected function suggestSolutionToHTTPError(int $err_code)
	{
		global $langs;
		$langs->load('sirene@sirene');

		switch ($err_code) {
			case 401:
				$message = 'SireneSolutionForError401';
				break;
			case 500:
				$message = 'SireneSolutionForError500';
				break;
			default:
				$message = '';
		}

		return $langs->transnoentities($message);
	}

	/**
	 * Get a single company by its siret with the sirene API
	 *
	 * @param	string		$siret		Siret
	 * @return	array					Result null if KO otherwise the company infos converted (empty if not found)
	 */
	public function getOneCompanyBySiret($siret)
	{
		global $langs;
		$langs->load('sirene@sirene');
		$this->errors = [];

		$result = $this->sendToApi(self::METHOD_GET, 'siret/' . $siret);
		if (!isset($result)) {
			$this->errors[] = $langs->trans('SireneErrorWhenGetOneCompanyBySiret', $siret);
			dol_syslog(__METHOD__ . ': Error:' . $this->errorsToString(), LOG_ERR);
			return null;
		}

		if (!empty($result['etablissement'])) {
			return $this->convertSireneCompanyInfo($result['etablissement']);
		}

		return [];
	}

	/**
	 *  Get all companies found when search with the sirene API
	 *
	 * @param	string			$company_name			Search by company name
	 * @param	string			$siren					Search by siren
	 * @param	string|array	$siret					Search by siret or a list of siret
	 * @param	string			$rna					Search by rna
	 * @param	string			$naf					Search by naf
	 * @param	string			$zipcode				Search by zip code
	 * @param	string			$nombre					Maximum number of results
	 * @param	string			$town					Search by town
	 * @param	int				$only_open				Search only open
	 * @param	int				$only_company_siege		Search by company siege only
	 * @return	array									Result null if KO otherwise the list of company infos converted (empty if not found)
	 */
	public function searchCompanies($company_name, $siren, $siret, $rna, $naf, $zipcode, $nombre, $town = '', $only_open = 0, $only_company_siege = 0)
	{
		global $langs;
		$langs->load('sirene@sirene');
		$this->errors = [];

		// Set filters
		$filter = array();
		if (!empty($company_name)) $filter[] = 'raisonSociale:"' . str_replace('"', '\\"', $company_name) . '"';
		if (!empty($siren)) $filter[] = 'siren:' . $siren;
		if (!empty($siret)) $filter[] = '(siret:' . implode(' OR siret:', is_array($siret) ? $siret : [ $siret ]) . ')';
		if (!empty($naf)) $filter[] = 'activitePrincipaleUniteLegale:' . $naf;
		if (!empty($zipcode)) $filter[] = 'codePostalEtablissement:' . $zipcode;
		if (!empty($town)) $filter[] = 'libelleCommuneEtablissement:"' . str_replace('"', '\\"', $town) . '"';
		if (!empty($only_open)) $filter[] = 'periode(etatAdministratifEtablissement:A)';
		if (!empty($only_company_siege)) $filter[] = 'etablissementSiege:T';
		if (!empty($rna)) $filter[] = 'identifiantAssociationUniteLegale:' . $rna;

		$result = $this->sendToApi(self::METHOD_POST, 'siret', [
			GuzzleHttp\RequestOptions::FORM_PARAMS => ['q' => implode(' AND ', $filter), 'nombre' => $nombre, 'date' => dol_print_date(dol_now(), "%Y-%m-%d")],
		]);
		if (!isset($result['etablissements'])) {
			$this->errors = array_merge([$langs->trans('SireneErrorWhenSearchCompanies')], $this->errors);
			dol_syslog(__METHOD__ . ': Error:' . $this->errorsToString(), LOG_ERR);
			return null;
		}

		$companies_infos = [];
		foreach ($result['etablissements'] as $info) {
			$companies_infos[] = $this->convertSireneCompanyInfo($info);
		}

		return $companies_infos;
	}

	/**
	 * Convert sirene info of the company to dolibarr company infos
	 *
	 * @param	array	$company_infos		From the Sirene API
	 * @return	array						Company infos converted
	 */
	private function convertSireneCompanyInfo(array $company_infos)
	{
		global $langs;

		$private = empty($company_infos['uniteLegale']['denominationUniteLegale']) && !empty($company_infos['uniteLegale']['nomUsageUniteLegale']) ? 1 : 0;

		if (!$private) {
			// Morale
			$company_name = $company_infos['uniteLegale']['nomUniteLegale'];
			if (empty($company_name)) $company_name = $company_infos['uniteLegale']['denominationUniteLegale'];

			$company_name_alias = '';
			if (empty($company_name_alias)) $company_name_alias = $company_infos['uniteLegale']['denominationUsuelle1UniteLegale'];
			if (empty($company_name_alias)) $company_name_alias = $company_infos['uniteLegale']['denominationUsuelle2UniteLegale'];
			if (empty($company_name_alias)) $company_name_alias = $company_infos['uniteLegale']['denominationUsuelle3UniteLegale'];
			if (empty($company_name_alias) && isset($company_infos['periodesEtablissement'][0])) {
				$etablissement_infos = $company_infos['periodesEtablissement'][0];
				$company_name_alias = $etablissement_infos['denominationUsuelleEtablissement'];
				if (empty($company_name_alias)) $company_name_alias = $etablissement_infos['enseigne1Etablissement'];
				if (empty($company_name_alias)) $company_name_alias = $etablissement_infos['enseigne2Etablissement'];
				if (empty($company_name_alias)) $company_name_alias = $etablissement_infos['enseigne3Etablissement'];
			}
			if (!empty($companies_name_alias)) {
				$company_name_alias = trim($company_name_alias);
			}
			if ($company_name == $company_name_alias) $company_name_alias = '';

			$company_name_all = $company_name;
		} else {
			// Physique
			$company_name = $company_infos['uniteLegale']['nomUsageUniteLegale'];

			$company_name_alias = '';
			$firstname = $company_infos['uniteLegale']['prenomUsuelUniteLegale'];
			if (empty($firstname)) $firstname = $company_infos['uniteLegale']['prenom1UniteLegale']; elseif ($firstname != $company_infos['uniteLegale']['prenom1UniteLegale']) $company_name_alias .= ' ' . $company_infos['uniteLegale']['prenom1UniteLegale'];
			if (empty($firstname)) $firstname = $company_infos['uniteLegale']['prenom2UniteLegale']; elseif ($firstname != $company_infos['uniteLegale']['prenom2UniteLegale']) $company_name_alias .= ' ' . $company_infos['uniteLegale']['prenom2UniteLegale'];
			if (empty($firstname)) $firstname = $company_infos['uniteLegale']['prenom3UniteLegale']; elseif ($firstname != $company_infos['uniteLegale']['prenom3UniteLegale']) $company_name_alias .= ' ' . $company_infos['uniteLegale']['prenom3UniteLegale'];
			if (empty($firstname)) $firstname = $company_infos['uniteLegale']['prenom4UniteLegale']; elseif ($firstname != $company_infos['uniteLegale']['prenom4UniteLegale']) $company_name_alias .= ' ' . $company_infos['uniteLegale']['prenom4UniteLegale'];
			if (!empty($companies_name_alias)) {
				$company_name_alias = trim($company_name_alias);
			}

			$civility = $company_infos['uniteLegale']['sexeUniteLegale'] == 'M' ? 'MR' : 'MME';
			$civility_all = $company_infos['uniteLegale']['sexeUniteLegale'] == 'M' ? 'M.' : 'Mme';

			$company_name_all = $civility_all . ' ' . dolGetFirstLastname($firstname, $company_name);
		}
		if ($company_infos['uniteLegale']['identifiantAssociationUniteLegale']) {
			$rna = $company_infos['uniteLegale']['identifiantAssociationUniteLegale'];
		}
		if (!empty($company_name_alias)) $company_name_all .= ' (' . $company_name_alias . ')';

		$date_creation = $company_infos['dateCreationEtablissement'];
		if (!empty($date_creation)) {
			$date_tmp = strtotime($date_creation);
			$date_creation = dol_print_date($date_tmp, 'day');
		} else {
			$date_creation = 'N/A';
		}
		$status = '';
		$status_all = '';
		$firstPeriodeEtablissementInfo = array_values($company_infos['periodesEtablissement']);
		if (!empty($firstPeriodeEtablissementInfo)) {
			$firstPeriodeEtablissementInfo = $firstPeriodeEtablissementInfo[0];
			$status = $firstPeriodeEtablissementInfo['etatAdministratifEtablissement'];

			if ($status != Sirene::COMPANY_ADMIN_STATUS_OPENED) {
				$status_all = '<span style="color:red;">' . $langs->trans('SireneEstablishmentClosed') . '</span>';
			} else {
				$status_all = '<span style="color:green;">' . $langs->trans('SireneEstablishmentOpened') . '</span>';
			}
		}

		$address_all = $this->isValueDefined($company_infos['adresseEtablissement']['numeroVoieEtablissement'] ?? '');
		$address_all .= $this->isValueDefined($company_infos['adresseEtablissement']['indiceRepetitionEtablissement'] ?? '');
		$address_all .= ' ' . $this->isValueDefined($company_infos['adresseEtablissement']['typeVoieEtablissement'] ?? '');
		$address_all .= ' ' . $this->isValueDefined($company_infos['adresseEtablissement']['libelleVoieEtablissement'] ?? '');
		$address = $address_all;
		$address_all .= ' ' . $this->isValueDefined($company_infos['adresseEtablissement']['codePostalEtablissement'] ?? '');
		$zip_code = $this->isValueDefined($company_infos['adresseEtablissement']['codePostalEtablissement'] ?? '');
		$address_all .= ' ' . $this->isValueDefined($company_infos['adresseEtablissement']['libelleCommuneEtablissement'] ?? '');
		$town = $this->isValueDefined($company_infos['adresseEtablissement']['libelleCommuneEtablissement'] ?? '');
		$address_all .= ' ' . $this->isValueDefined($company_infos['adresseEtablissement']['libelleCommuneEtrangerEtablissement'] ?? '');
		$address_all .= ' ' . $this->isValueDefined($company_infos['adresseEtablissement']['distributionSpecialeEtablissement'] ?? '');
		$address_all .= ' ' . $this->isValueDefined($company_infos['adresseEtablissement']['libelleCedexEtablissement'] ?? '');
		$address_all .= ' ' . $this->isValueDefined($company_infos['adresseEtablissement']['libellePaysEtrangerEtablissement'] ?? '');

		$siren = $company_infos['siren'];
		$siret = $company_infos['siret'];
		$nic = $company_infos['nic'];

		$latitude = $company_infos["adresseEtablissement"]["coordonneeLambertAbscisseEtablissement"];
		$longitude = $company_infos["adresseEtablissement"]["coordonneeLambertOrdonneeEtablissement"];

		$codenaf_all = $codenaf = $firstPeriodeEtablissementInfo['activitePrincipaleEtablissement'];
		$codenaf_san = preg_replace('[\W]', '', $codenaf);
		$codenaf_san = str_pad($codenaf_san, 5, "0", STR_PAD_LEFT);

		//effectif
		$staff_code = $company_infos['uniteLegale']['trancheEffectifsUniteLegale']; //recup code sirene pour effectif

		$staff_code = intval($staff_code);
		$staff_code = dol_getIdFromCode($this->db, $staff_code, 'c_sirene_staff', 'code_sirene_staff', 'code_dolibarr_staff', 0);
		$staff_label = dol_getIdFromCode($this->db, $staff_code, 'c_effectif', 'code', 'libelle', 0);
		$staff = dol_getIdFromCode($this->db, $staff_code, 'c_effectif', 'code', 'id', 0);

		// Forme juridique niveau 3 récupéré depuis l'API Sirene
		// Ne prendre que les 2 premiers chiffres pour s'aligner sur le niveau 2 qu'utilise Dolibarr dans la table llx_c_forme_juridique
		$legalcategory = $company_infos['uniteLegale']['categorieJuridiqueUniteLegale'];
		$legalcategory = strval($legalcategory);
		$legalcategory = substr($legalcategory, 0, 2);
		$legalcategory = intval($legalcategory);
		$legalcategory_id = dol_getIdFromCode($this->db, $legalcategory, 'c_forme_juridique', 'code', 'libelle', 0);

		// Siege
		$siege = 0;
		if (!empty($company_infos['etablissementSiege']) && $company_infos['etablissementSiege'] == 'true') {
			$siege = 1;
		}

		$sql = "SELECT label FROM " . MAIN_DB_PREFIX . "c_codenaf WHERE code = '" . $this->db->escape($codenaf_san) . "'";
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($obj = $this->db->fetch_object($resql)) {
				$codenaf_all .= ' - ' . $obj->label;
			} else {
				$codenaf_all .= ' - ' . $langs->trans('SireneErrorCodeNafNotFound', dol_buildpath('/sirene/admin/dictionaries.php', 1) . '?module=sirene&name=sirenecodenaf');
			}
		}

		$state_all = $state = $company_infos['adresseEtablissement']['codePostalEtablissement'];
		$state_san = preg_replace('[\W]', '', $company_infos['adresseEtablissement']['codePostalEtablissement']);
		$state_san = substr(str_pad($state_san, 5, "0", STR_PAD_LEFT), 0, 3);
		if ($state_san < 970) $state_san = substr($state_san, 0, 2); // Compatibilité DOM-TOM
		$state_id = dol_getIdFromCode($this->db, $state_san, 'c_departements', 'code_departement', 'rowid', 0);
		$country_san = $company_infos['adresseEtablissement']['codePaysEtrangerEtablissement'];

		if (empty($country_san)) {
			$country_san = -1;
		}
		$country_code = dol_getIdFromCode($this->db, $country_san, 'c_sirene_country', 'code_sirene', 'country_code', 0);
		if ($country_code !== -1) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
			$country = getCountry($country_code);
			$country_id = dol_getIdFromCode($this->db, $country_code, 'c_sirene_country', 'country_code', 'rowid', 0);
		} else {
			$country_code = '';
			$country = $langs->trans('Unknown') . ' - ID: ' . $country_san . " - Name: " . $company_infos['adresseEtablissement']['libellePaysEtrangerEtablissement'];
			$country_id = 0;
		}

		$tva_intra = '';

		if ($country_code == 'FR') {
			// intra-community vat number calculation
			$coef = 97;
			$vatintracalc = fmod($company_infos['siren'], $coef);
			$vatintracalc2 = fmod((12 + 3 * $vatintracalc), $coef);
			$tva_intra = 'FR' . str_pad($vatintracalc2, 2, 0, STR_PAD_LEFT) . $company_infos['siren'];
		}

		return array(
			'company_name' => $this->isValueDefined($company_name ?? ''),
			'firstname' => $this->isValueDefined($firstname ?? ''),
			'civility' => $this->isValueDefined($civility ?? ''),
			'company_name_alias' => $this->isValueDefined($company_name_alias ?? ''),
			'private' => $this->isValueDefined($private ?? ''),
			'company_name_all' => $this->isValueDefined($company_name_all ?? ''),
			'date_creation' => $this->isValueDefined($date_creation ?? ''),
			'status' => $this->isValueDefined($status ?? ''),
			'status_all' => $this->isValueDefined($status_all ?? ''),
			'address' => $this->isValueDefined($address ?? ''),
			'zipcode' => $this->isValueDefined($zip_code ?? ''),
			'town' => $this->isValueDefined($town ?? ''),
			'state' => $this->isValueDefined($state ?? ''),
			'state_id' => $this->isValueDefined($state_id ?? ''),
			'state_san' => $this->isValueDefined($state_san ?? ''),
			'state_all' => $this->isValueDefined($state_all ?? ''),
			'address_all' => $this->isValueDefined($address_all ?? ''),
			'siren' => $this->isValueDefined($siren ?? ''),
			'siret' => $this->isValueDefined($siret ?? ''),
			'nic' => $this->isValueDefined($nic ?? ''),
			'rna' => $this->isValueDefined($rna ?? ''),
			'codenaf' => $this->isValueDefined($codenaf ?? ''),
			'codenaf_san' => $this->isValueDefined($codenaf_san ?? ''),
			'codenaf_all' => $this->isValueDefined($codenaf_all ?? ''),
			'country' => $this->isValueDefined($country ?? ''),
			'country_san' => $this->isValueDefined($country_san ?? ''),
			'country_id' => $this->isValueDefined($country_id ?? ''),
			'country_code' => $this->isValueDefined($country_code ?? ''),
			'sirene_tva_intra' => $this->isValueDefined($tva_intra ?? ''),
			'staff_code' => $this->isValueDefined($staff_code ?? ''),
			'staff' => $this->isValueDefined($staff ?? ''),
			'staff_label' => $this->isValueDefined($staff_label ?? ''),
			'legalcategory' => $this->isValueDefined($legalcategory ?? ''),
			'legalcategory_id' => $this->isValueDefined($legalcategory_id ?? ''),
			'latitude' => $this->isValueDefined($latitude ?? ''),
			'longitude' => $this->isValueDefined($longitude ?? ''),
			'siege' => $siege,
		);
	}

	/**
	 * Return value if defined otherwise empty
	 *
	 * @param	mixed	$value	Value returned by sirene
	 * @return	mixed			Value if defined otherwise empty
	 */
	protected function isValueDefined($value)
	{
		return ((isset($value) && $value != '[ND]') ? $value : '');
	}
}
