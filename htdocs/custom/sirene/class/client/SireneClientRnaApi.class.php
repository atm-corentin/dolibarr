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
 *      \file       htdocs/sirene/class/client/SireneClientRnaApi.class.php
 *      \ingroup    sirene
 *      \brief      This file for managing Client RNA API
 */

dol_include_once('/sirene/class/client/SireneClientApi.class.php');
dol_include_once('/sirene/class/sirene.class.php');
dol_include_once('/sirene/lib/sirene.lib.php');

/**
 * 	Class to manage Client RNA API
 */
class SireneClientRnaApi extends SireneClientApi
{
	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		global $conf;
		parent::__construct($db);

		$this->api_url = getSireneDolGlobalString('SIRENE_API_RNA_URI', 'https://siva-integ1.cegedim.cloud/apim/api-asso/api/structure/');
		$this->api_no_verify_ssl = empty(getSireneDolGlobalInt('SIRENE_API_RNA_VERIFY_SSL'));
	}

	/**
	 * Get a company info from the RNA API
	 *
	 * @param	string		$rna		Rna
	 * @return	array					Result null if KO otherwise the company infos converted (empty if not found)
	 */
	public function searchCompanyByRna($rna)
	{
		global $langs;
		$langs->load('sirene@sirene');
		$this->errors = [];

		$result = $this->sendToApi(self::METHOD_GET, $rna);
		if (!isset($result)) {
			$this->errors[] = $langs->trans('SireneErrorWhenGetCompanyByRna', $rna);
			dol_syslog(__METHOD__ . ': Error:' . $this->errorsToString(), LOG_ERR);
			return null;
		}

		if (!empty($result)) {
			return $this->convertRnaCompanyInfo($result);
		}

		return [];
	}

	/**
	 * Convert RNA info of the company to dolibarr company infos
	 *
	 * @param	array	$company_infos	From the RNA API
	 * @return	array					Company infos converted
	 */
	private function convertRnaCompanyInfo($company_infos)
	{
		global $langs;

		// Error 404
		if (isset($company_infos['erreur'])) {
			return [];
		}

		if (isset($company_infos['id_rna'])) {
			$rna = $company_infos['id_rna'];
		}

		if (isset($company_infos['id_siren'])) {
			$siren = $company_infos['id_siren'];
		}

		if (isset($company_infos['identite'])) {
			$company_identity = $company_infos['identite'];

			if (isset($company_identity['nom'])) {
				$company_name_all = $company_identity['nom'];
				$company_name = $company_identity['nom'];
			}

			if (isset($company_identity['id_siret_siege'])) {
				$siret = $company_identity['id_siret_siege'];
			}

			if (isset($company_identity['date_creation'])) {
				$date_creation = $company_identity['date_creation'];
			} elseif (isset($company_identity['date_creat'])) {
				$date_creation = $company_identity['date_creat'];
			}

			if (isset($company_identity['id_forme_juridique'])) {
				$legalcategory = $company_identity['legalcategory'];

				$legalcategory = strval($legalcategory);
				$legalcategory = substr($legalcategory, 0, 2);
				$legalcategory = intval($legalcategory);

				$legalcategory_id = dol_getIdFromCode($this->db, $legalcategory, 'c_forme_juridique', 'code', 'libelle', 0);
			}

			if ($company_identity["active"] == true) {
				$status = Sirene::COMPANY_ADMIN_STATUS_OPENED;
			} else {
				$status = Sirene::COMPANY_ADMIN_STATUS_CLOSED;
			}
		}

		if (isset($company_infos['coordonnees'])) {
			if (isset($company_infos['coordonnees']['adresse_siege_sirene'])) {
				$company_address = $company_infos["coordonnees"]["adresse_siege_sirene"];
			} else {
				$company_address = $company_infos["coordonnees"]["adresse_siege"];
			}
		}

		if (isset($company_address)) {
			$address_all = ' ' . $company_address['num_voie'];
			$address_all .= ' ' . $company_address['type_voie'];
			$address_all .= ' ' . $company_address['voie'];

			$address = $address_all;

			$town = $company_address['commune'];

			$address_all .= ' ' . $company_address['commune'];
			$address_all .= ' ' . $company_address['cp'] . ' ';

			$zip_code = $company_address['cp'];

			$state_all = $company_address['cp'];

			$state_san = preg_replace('[\W]', '', $state_all);
			$state_san = substr(str_pad($state_san, 5, "0", STR_PAD_LEFT), 0, 3);

			if ($state_san < 970) $state_san = substr($state_san, 0, 2); // Compatibilité DOM-TOM

			$state_id = dol_getIdFromCode($this->db, $state_san, 'c_departements', 'code_departement', 'rowid', 0);
		}

		$status_all = $status == Sirene::COMPANY_ADMIN_STATUS_OPENED ? '<span style="color:green;">' . $langs->trans('SireneEstablishmentOpened') . '</span>' : '<span style="color:red;">' . $langs->trans('SireneEstablishmentClosed') . '</span>';

		return array(
			'company_name' => (isset($company_name) ? $company_name : ''),
			'firstname' => (isset($firstname) ? $firstname : ''),
			'civility' => (isset($civility) ? $civility : ''),
			'company_name_alias' => (isset($company_name_alias) ? $company_name_alias : ''),
			'private' => (isset($private) ? $private : ''),
			'company_name_all' => (isset($company_name_all) ? $company_name_all : ''),
			'date_creation' => (isset($date_creation) ? $date_creation : ''),
			'status' => (isset($status) ? $status : ''),
			'status_all' => (isset($status_all) ? $status_all : ''),
			'address' => (isset($address) ? $address : ''),
			'zipcode' => (isset($zip_code) ? $zip_code : ''),
			'town' => (isset($town) ? $town : ''),
			'state' => (isset($state) ? $state : ''),
			'state_id' => (isset($state_id) ? $state_id : ''),
			'state_san' => (isset($state_san) ? $state_san : ''),
			'state_all' => (isset($state_all) ? $state_all : ''),
			'address_all' => (isset($address_all) ? $address_all : ''),
			'siren' => (isset($siren) ? $siren : ''),
			'siret' => (isset($siret) ? $siret : ''),
			'rna' => (isset($rna) ? $rna : ''),
			'nic' => (isset($siret) ? substr($siret, -5) : ''),
			'codenaf' => (isset($codenaf) ? $codenaf : ''),
			'codenaf_san' => (isset($codenaf_san) ? $codenaf_san : ''),
			'codenaf_all' => (isset($codenaf_all) ? $codenaf_all : ''),
			'country' => (isset($country) ? $country : ''),
			'country_san' => (isset($country_san) ? $country_san : ''),
			'country_id' => (isset($country_id) ? $country_id : ''),
			'country_code' => (isset($country_code) ? $country_code : ''),
			'sirene_tva_intra' => (isset($tva_intra) ? $tva_intra : ''),
			'staff_code' => (isset($staff_code) ? $staff_code : ''),
			'staff' => (isset($staff) ? $staff : ''),
			'staff_label' => (isset($staff_label) ? $staff_label : ''),
			'legalcategory' => (isset($legalcategory) ? $legalcategory : ''),
			'legalcategory_id' => (isset($legalcategory_id) ? $legalcategory_id : ''),
			'latitude' => '',
			'longitude' => '',
			'siege' => (isset($siege) ? $siege : ''),
		);
	}
}
