<?php
/* Copyright (C) 2024      Easya Solutions             <support@easya.solutions>
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
 * \file    htdocs/extraitcompteclient/class/extraitcompteclient.class.php
 * \ingroup extraitcompteclient
 * \brief
 */


/**
 * Class ExtraitCompteClient
 *
 * Put here description of your class
 */
class ExtraitCompteClient
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
	 * Constructor
	 *
	 * @param        DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	public function getData($export_type, $date_start, $date_end, $show_payment_deadline, $show_invoice_abandoned, $add_product_tags, $show_subsidiaries)
	{
		global $conf;

		if ($export_type == 'Customer') {
			// Get lines of the customer account statut
			$sql = 'SELECT f.datef AS date';
			if ((float) DOL_VERSION < 10) {
				$sql .= ', f.facnumber AS label';
			} else {
				$sql .= ', f.ref AS label';
			}
			if ($show_payment_deadline) {
				$sql .= ', f.date_lim_reglement AS date_limite';
			}
			$sql .= ', f.ref_client AS label_externe';
			$sql .= ', f.total_ttc AS total_amount';
			$sql .= ', COALESCE(f.multicurrency_code, "' . $conf->currency . '") AS facture_multicurrency_code, f.multicurrency_tx AS facture_multicurrency_tx, f.multicurrency_total_ttc AS multicurrency_total_amount';
			$sql .= ', f.type AS invoicetype';
			$sql .= ', pf.amount_payed';
			$sql .= ', COALESCE(pf.multicurrency_code, "' . $conf->currency . '") AS paiement_multicurrency_code';
			$sql .= ', pf.paiement_multicurrency_tx, pf.multicurrency_amount_payed';
			$sql .= ', pf.count_amount_payed';
			$sql .= ', rc.amount_creditnote';
			$sql .= ', rc.multicurrency_amount_creditnote';
			$sql .= ', rc2.amount_creditused';
			$sql .= ', rc2.multicurrency_amount_creditused';

			if ($add_product_tags) {
				$sql .= ', pt.tags AS product_tags';
			}
			if (!empty($conf->global->EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD)) {
				$sql .= ', ef.' . $conf->global->EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD . ' AS extrafield_invoice';
			}
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture AS f';
			if (!empty($conf->global->EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD)) {
				$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields AS ef';
				$sql .= ' ON (f.rowid = ef.fk_object)';
			}
			$sql .= ' LEFT JOIN (';
			$sql .= '   SELECT sre.fk_facture AS fk_facture_source, SUM(sre.amount) as amount_payed, sre.multicurrency_code,';
			$sql .= '   sre.multicurrency_tx AS paiement_multicurrency_tx, count(sre.amount) AS count_amount_payed,';
			$sql .= '   SUM(sre.multicurrency_amount) AS multicurrency_amount_payed';
			$sql .= '   FROM ' . MAIN_DB_PREFIX . 'paiement_facture AS sre';
			$sql .= '   GROUP BY sre.fk_facture';
			$sql .= ') AS pf ON pf.fk_facture_source = f.rowid';

			$sql .= ' LEFT JOIN (';
			$sql .= '   SELECT sre.fk_facture_source AS fk_facture_source, SUM(sre.amount_ttc) AS amount_creditnote, SUM(sre.multicurrency_amount_ttc) AS multicurrency_amount_creditnote';
			$sql .= '   FROM ' . MAIN_DB_PREFIX . 'societe_remise_except AS sre';
			$sql .= '   GROUP BY sre.fk_facture_source';
			$sql .= ') AS rc ON rc.fk_facture_source = f.rowid';

			$sql .= ' LEFT JOIN (';
			$sql .= '   SELECT sre.fk_facture AS fk_facture, SUM(sre.amount_ttc) AS amount_creditused, SUM(sre.multicurrency_amount_ttc) AS multicurrency_amount_creditused';
			$sql .= '   FROM ' . MAIN_DB_PREFIX . 'societe_remise_except AS sre';
			$sql .= '   GROUP BY sre.fk_facture';
			$sql .= ') AS rc2 ON rc2.fk_facture = f.rowid';

			if ($add_product_tags) {
				$sql .= ' LEFT JOIN (';
				$sql .= "   SELECT fd.fk_facture, GROUP_CONCAT(DISTINCT c.label SEPARATOR '] [') AS tags";
				$sql .= '   FROM ' . MAIN_DB_PREFIX . 'facturedet AS fd';
				$sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie_product AS cp ON cp.fk_product = fd.fk_product';
				$sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie AS c ON c.rowid = cp.fk_categorie';
				$sql .= '   GROUP BY fd.fk_facture';
				$sql .= ' ) AS pt ON pt.fk_facture = f.rowid';
			}
			$sql .= ' WHERE f.entity IN (' . getEntity('facture') . ')';
			$sql .= ' AND f.datef >= \'' . dol_print_date($date_start, 'dayrfc') . '\'';
			$sql .= ' AND f.datef <= \'' . dol_print_date($date_end, 'dayrfc') . '\'';
			if ($show_subsidiaries) {
				$sql .= " AND ((f.fk_soc = " . ((int) $object->id);
				$sql .= ")";
				foreach ($idssubsidiaries as $id) {
					$sql .= " OR (f.fk_soc = " . ((int) $id);
					$sql .= ")";
				}
				$sql .= ")";
			} else {
				$sql .= ' AND f.fk_soc = ' . $object->id;
			}
			if (empty($show_invoice_payed)) {
				$sql .= " AND f.paye = 0";
			}
			if ($show_invoice_abandoned) {
				$sql .= " AND f.fk_statut > 0"; // No draft invoice
			} else {
				$sql .= " AND f.fk_statut IN (" . Facture::STATUS_VALIDATED . "," . Facture::STATUS_CLOSED . ")";
			}
			if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
				$sql .= " AND f.type IN (" . Facture::TYPE_STANDARD . "," . Facture::TYPE_REPLACEMENT . "," . Facture::TYPE_CREDIT_NOTE . "," . Facture::TYPE_SITUATION . ")";
			} else {
				$sql .= " AND f.type IN (" . Facture::TYPE_STANDARD . "," . Facture::TYPE_REPLACEMENT . "," . Facture::TYPE_CREDIT_NOTE . "," . Facture::TYPE_DEPOSIT . "," . Facture::TYPE_SITUATION . ")";
			}
			$sql .= ' GROUP BY f.rowid';
			$sql .= ', f.datef';
			if ((float) DOL_VERSION < 10) {
				$sql .= ', f.facnumber';
			} else {
				$sql .= ', f.ref';
			}
			if ($show_payment_deadline) {
				$sql .= ', f.date_lim_reglement';
			}
			$sql .= ', f.ref_client';
			$sql .= ', f.total_ttc';
			$sql .= ', f.multicurrency_code';
			$sql .= ', f.multicurrency_tx';
			$sql .= ', f.multicurrency_total_ttc';
			$sql .= ', f.type';
			$sql .= ', pf.multicurrency_code';
			$sql .= ', pf.paiement_multicurrency_tx';
			$sql .= ', rc.amount_creditnote';
			$sql .= ', rc.multicurrency_amount_creditnote';
			$sql .= ', rc2.amount_creditused';
			$sql .= ', rc2.multicurrency_amount_creditused';

			if ($add_product_tags) {
				$sql .= ', pt.tags';
			}
			if (!empty($conf->global->EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD)) {
				$sql .= ', ef.' . $conf->global->EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD . '';
			}
			if (!empty($conf->global->EXTRAITCOMPTECLIENT_ORDERBY)) {
				($conf->global->EXTRAITCOMPTECLIENT_ORDERBY = 0) ? $orderby = 'DESC' : $orderby = 'ASC';
			} else {
				$orderby = 'DESC';
			}
			$sql .= ' ORDER BY f.datef ' . $orderby;
			$sql .= ' , f.ref ' . $orderby;
		} elseif ($export_type == 'Supplier') {
			// Get lines of the supplier account statut
			$sql = 'SELECT f.datef AS date,';
			$sql .= ' f.ref AS label,';
			$sql .= ' f.ref_supplier AS label_externe,';
			if ($show_payment_deadline) {
				$sql .= ' f.date_lim_reglement AS date_limite,';
			}
			$sql .= ' f.total_ttc AS total_amount,';
			$sql .= ' COALESCE(f.multicurrency_code, "' . $conf->currency . '") AS facture_multicurrency_code, f.multicurrency_tx AS facture_multicurrency_tx, f.multicurrency_total_ttc AS multicurrency_total_amount,';
			$sql .= ' f.type AS invoicetype,';
			$sql .= ' pf.amount_payed,';
			$sql .= ' COALESCE(pf.multicurrency_code, "' . $conf->currency . '") AS paiement_multicurrency_code,';
			$sql .= ' pf.paiement_multicurrency_tx, pf.multicurrency_amount_payed,';
			$sql .= ' pf.count_amount_payed,';
			$sql .= ' rc.amount_creditnote,';
			$sql .= ' rc.multicurrency_amount_creditnote,';
			$sql .= ' rc2.amount_creditused,';
			$sql .= ' rc2.multicurrency_amount_creditused';
			if ($add_product_tags) {
				$sql .= ', pt.tags AS product_tags';
			}
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture_fourn AS f';

			$sql .= ' LEFT JOIN (';
			$sql .= '   SELECT sre.fk_facturefourn AS fk_facture_source, SUM(sre.amount) as amount_payed, sre.multicurrency_code,';
			$sql .= '   sre.multicurrency_tx AS paiement_multicurrency_tx, count(sre.amount) AS count_amount_payed,';
			$sql .= '   SUM(sre.multicurrency_amount) AS multicurrency_amount_payed';
			$sql .= '   FROM ' . MAIN_DB_PREFIX . 'paiementfourn_facturefourn AS sre';
			$sql .= '   GROUP BY sre.fk_facturefourn';
			$sql .= ') AS pf ON pf.fk_facture_source = f.rowid';

			$sql .= ' LEFT JOIN (';
			$sql .= '   SELECT sre.fk_invoice_supplier_source AS fk_facture_source, SUM(sre.amount_ttc) AS amount_creditnote, SUM(sre.multicurrency_amount_ttc) AS multicurrency_amount_creditnote';
			$sql .= '   FROM ' . MAIN_DB_PREFIX . 'societe_remise_except AS sre';
			$sql .= '   GROUP BY sre.fk_invoice_supplier_source';
			$sql .= ') AS rc ON rc.fk_facture_source = f.rowid';

			$sql .= ' LEFT JOIN (';
			$sql .= '   SELECT sre.fk_invoice_supplier AS fk_facture, SUM(sre.amount_ttc) AS amount_creditused, SUM(sre.multicurrency_amount_ttc) AS multicurrency_amount_creditused';
			$sql .= '   FROM ' . MAIN_DB_PREFIX . 'societe_remise_except AS sre';
			$sql .= '   GROUP BY sre.fk_invoice_supplier';
			$sql .= ') AS rc2 ON rc2.fk_facture = f.rowid';

			if ($add_product_tags) {
				$sql .= ' LEFT JOIN (';
				$sql .= "   SELECT ffd.fk_facture_fourn, GROUP_CONCAT(DISTINCT c.label SEPARATOR '] [') AS tags";
				$sql .= '   FROM ' . MAIN_DB_PREFIX . 'facture_fourn_det AS ffd';
				$sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie_product AS cp ON cp.fk_product = ffd.fk_product';
				$sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie AS c ON c.rowid = cp.fk_categorie';
				$sql .= '   GROUP BY ffd.fk_facture_fourn';
				$sql .= ' ) AS pt ON pt.fk_facture_fourn = f.rowid';
			}
			$sql .= ' WHERE f.entity IN (' . getEntity('facture_fourn') . ')';
			$sql .= ' AND f.datef >= \'' . dol_print_date($date_start, 'dayrfc') . '\'';
			$sql .= ' AND f.datef <= \'' . dol_print_date($date_end, 'dayrfc') . '\'';
			if ($show_subsidiaries) {
				$sql .= " AND ((f.fk_soc = " . ((int) $object->id);
				$sql .= ")";
				foreach ($idssubsidiaries as $id) {
					$sql .= " OR (f.fk_soc = " . ((int) $id);
					$sql .= ")";
				}
				$sql .= ")";
			} else {
				$sql .= ' AND f.fk_soc = ' . $object->id;
			}
			if (empty($show_invoice_payed)) {
				$sql .= " AND f.paye = 0";
			}
			if ($show_invoice_abandoned) {
				$sql .= " AND f.fk_statut > 0"; // No draft invoice
			} else {
				$sql .= " AND f.fk_statut IN (" . FactureFournisseur::STATUS_VALIDATED . "," . FactureFournisseur::STATUS_CLOSED . ")";
			}
			$sql .= ' GROUP BY f.rowid';
			$sql .= ', f.datef';
			$sql .= ', f.ref';
			$sql .= ', f.ref_supplier';
			if ($show_payment_deadline) {
				$sql .= ', f.date_lim_reglement';
			}
			$sql .= ', f.total_ttc';
			$sql .= ', f.multicurrency_code';
			$sql .= ', f.multicurrency_tx';
			$sql .= ', f.multicurrency_total_ttc';
			$sql .= ', f.type';
			$sql .= ', pf.multicurrency_code';
			$sql .= ', pf.paiement_multicurrency_tx';
			if ($add_product_tags) {
				$sql .= ', pt.tags';
			}
			if (!empty($conf->global->EXTRAITCOMPTECLIENT_ORDERBY)) {
				($conf->global->EXTRAITCOMPTECLIENT_ORDERBY = 0) ? $orderby = 'DESC' : $orderby = 'ASC';
			} else {
				$orderby = 'DESC';
			}
			$sql .= ' ORDER BY f.datef ' . $orderby;
			$sql .= ' , f.ref ' . $orderby;
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->error();
			return 0;
		}

	}


	/**
	 * Method to output saved errors
	 *
	 * @return	string		String with errors
	 */
	public function errorsToString()
	{
		return $this->error . (is_array($this->errors) ? (($this->error != '' ? ', ' : '') . join(', ', $this->errors)) : '');
	}
}
