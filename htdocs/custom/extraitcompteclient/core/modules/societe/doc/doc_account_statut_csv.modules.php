<?php
/* Copyright (C) 2004-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      	Christophe Battarel <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2017-2019  Open-DSI            <support@open-dsi.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/societe/doc/doc_account_statut_csv.modules.php
 *	\ingroup    societe
 *	\brief      File of class to generate customers account statut from account_statut_csv model
 */
//Get Dolibarr version used
 $dolibarrversionprogram = preg_split('/[.-]/', DOL_VERSION);

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if ($dolibarrversionprogram[0] <= '17') {
	require_once DOL_DOCUMENT_ROOT.'/core/modules/export/export_csv.modules.php';
} else {
	require_once DOL_DOCUMENT_ROOT.'/core/modules/export/exportcsv.class.php';
};
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';


/**
 *	Class to manage CSV account statut template account_statut_csv
 */
class doc_account_statut_csv
{
    var $db;
    var $name;
    var $description;
    var $type;

    var $phpmin = array(4,3,0); // Minimum version of PHP required by module
    var $version = 'dolibarr';

	var $emetteur;	// Objet societe qui emet


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("extraitcompteclient@extraitcompteclient");

		$this->db = $db;
		$this->name = "account_statut_csv";
		$this->description = $langs->trans('ExtraitCompteClientCSVAccountStatutDescription');
		// societe table has no property last_main_doc before Dolibarr 17
		$this->update_main_doc_field = 0;
		if (version_compare(DOL_VERSION, '17.0') >= 0) {	
			$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template
		}

		$this->type = 'csv';

		// Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if was not defined
    }


	/**
     *  Function to build pdf onto disk
     *
     *  @param		Object		$object				Object to generate
     *  @param		Translate	$outputlangs		Lang output object
     *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int			$hidedetails		Do not show line details
     *  @param		int			$hidedesc			Do not show desc
     *  @param		int			$hideref			Do not show ref
     *  @return     int         	    			1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user, $langs, $conf, $mysoc, $db, $hookmanager,$mc;

		if (!is_object($outputlangs)) $outputlangs = $langs;

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("bills");
		$outputlangs->load("extraitcompteclient@extraitcompteclient");

		$date_start = $object->context['account_statut']['date_start'];
		$date_end = $object->context['account_statut']['date_end'];

		$show_payment_details = !empty($object->context['account_statut']['show_payment_details']);
		$show_invoice_payed = !empty($object->context['account_statut']['show_invoice_payed']) || $show_payment_details;
		$show_payment_deadline = !empty($object->context['account_statut']['show_payment_deadline']) || $show_payment_details;
		$show_invoice_abandoned = !empty($object->context['account_statut']['show_invoice_abandoned']);
		$add_product_tags = !empty($object->context['account_statut']['add_product_tags']);
		$product_tags_separator = !empty($conf->global->EXTRAITCOMPTECLIENT_PRODUCT_TAGS_SEPARATOR) ? $conf->global->EXTRAITCOMPTECLIENT_PRODUCT_TAGS_SEPARATOR : ';';

		$export_type = $object->context['account_statut']['export_type'];

		if ($conf->societe->multidir_output[$object->entity]) {
			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->societe->dir_output;
				$file = $dir . "/SPECIMEN.csv";
			} else {
				$objectid = dol_sanitizeFileName($object->id);
				$dir = $conf->societe->multidir_output[$object->entity] . "/" . $objectid;
				$datefile = dol_print_date(dol_now(), '%Y-%m-%d');
				if ($export_type == 'Customer') {
					$thirdparty_code = $object->code_client;
				} else if ($export_type == 'Supplier') {
					$thirdparty_code = $object->code_fournisseur;
				}
				$export_type_lang = $langs->transnoentitiesnoconv($export_type);
				$file_name = $langs->transnoentitiesnoconv('ExtraitCompteClientPDFAccountStatutFileName', $export_type_lang, $thirdparty_code, $datefile);
				$file_name = trim($file_name);
				if ($conf->multicompany->enabled && !empty($mc->sharings) && !empty($mc->sharings['thirdparty'])) {
					$ent = new DaoMulticompany($this->db);
					$ent->fetch($conf->entity);
					if (empty($conf->global->EXTRAITCOMPTECLIENT_ENTITY_NAME_BEGIN_LOCATION_IN_FILENAME)) {
						$file_name .= '_' . $ent->label;
					} else {
						$file_name = $ent->label . '_' . $file_name;
					}
				}
				$file = $dir . "/" . dol_sanitizeFileName($file_name) . ".csv";
			}
			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir)) {
				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('csvgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforeCSVCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks


				// Columns order && Columns labels
				$array_selected_sorted = array(
					'date' => 1,
					'label' => 1,
				);
				$array_export_fields_label = array(
					'date' => 'ExtraitCompteClientPDFAccountStatutDate',
					'label' => 'ExtraitCompteClientPDFAccountStatutLabel',
				);

				if (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_REF_SUPPLIER) & ($export_type == 'Supplier')) {
					$array_selected_sorted = array_merge($array_selected_sorted, array(
						'label_externe' => 1,
					));
					$array_export_fields_label = array_merge($array_export_fields_label, array(
						'label_externe' => 'ExtraitCompteClientCSVAccountStatutExternalLabel',
					));
				}

				if ($show_payment_deadline) {
					$array_selected_sorted = array_merge($array_selected_sorted, array(
						'date_limite' => 1,
					));
					$array_export_fields_label = array_merge($array_export_fields_label, array(
						'date_limite' => 'ExtraitCompteClientPDFAccountStatutLimitDate',
					));
				}

				$array_selected_sorted = array_merge($array_selected_sorted, array(
					'status' => 1,
					'total_amount' => 1,
				));
				$array_export_fields_label = array_merge($array_export_fields_label, array(
					'status' => 'Status',
					'total_amount' => 'ExtraitCompteClientPDFAccountStatutTotalAmountTTC',
				));

				if ($show_payment_details) {
					$array_selected_sorted = array_merge($array_selected_sorted, array(
						'payment_type' => 1,
						'payment_mode_label' => 1,
						'payment_date' => 1,
						'payment_number' => 1,
						'payment_sender' => 1,
						'payment_bank' => 1,
						'amount_rule' => 1,
						'balance' => 1,
					));
					$array_export_fields_label = array_merge($array_export_fields_label, array(
						'payment_type' => 'ExtraitCompteClientPDFAccountStatutPaymentType',
						'payment_mode_label' => 'ExtraitCompteClientPDFAccountStatutPaymentModeLabel',
						'payment_date' => 'ExtraitCompteClientPDFAccountStatutPaymentDate',
						'payment_number' => 'ExtraitCompteClientPDFAccountStatutPaymentNumber',
						'payment_sender' => 'ExtraitCompteClientPDFAccountStatutPaymentSender',
						'payment_bank' => 'ExtraitCompteClientPDFAccountStatutPaymentBank',
						'amount_rule' => 'ExtraitCompteClientPDFAccountStatutAmountRuleTTC',
						'balance' => 'ExtraitCompteClientPDFAccountStatutBalanceTTC',
					));
				} else {
					$array_selected_sorted += array(
						'amount_rule' => 1,
						'balance' => 1,
					);
					$array_export_fields_label += array(
						'amount_rule' => 'ExtraitCompteClientPDFAccountStatutAmountRuleTTC',
						'balance' => 'ExtraitCompteClientPDFAccountStatutBalanceTTC',
					);
				}

				if ($add_product_tags) {
					$array_selected_sorted = array_merge($array_selected_sorted, array(
						'product_tags' => 1,
					));
					$array_export_fields_label = array_merge($array_export_fields_label, array(
						'product_tags' => 'ExtraitCompteClientPDFAccountStatutProductTags',
					));
				}

				if (!empty($conf->global->EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD)) {
					$array_selected_sorted = array_merge($array_selected_sorted, array(
						'extrafield_invoice' => 1,
					));
					$array_export_fields_label = array_merge($array_export_fields_label, array(
						'extrafield_invoice' => $conf->global->EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD,
					));
				}

				$array_types = array();

				if ($export_type == 'Customer') {
					// Get lines of the customer account statut
					$sql = 'SELECT DISTINCT f.rowid';
					$sql .= ', f.datef AS date';
					$sql .= ', f.paye';
					if ((float)DOL_VERSION < 10) {
						$sql .= ', f.facnumber AS label';
					} else {
						$sql .= ', f.ref AS label';
					}

					if ($show_payment_deadline) {
						$sql .= ', f.date_lim_reglement AS date_limite';
					}
					$sql .= ', f.total_ttc AS total_amount';
					$sql .= ', f.type AS invoicetype';
					$sql .= ', pay.amount_payed';
					$sql .= ', pay.count_amount_payed';
					$sql .= ', pay.amount_creditnote';
					$sql .= ', pay.amount_creditused';
					if ($add_product_tags) {
						$sql .= ', pt.tags AS product_tags';
					}
					if (!empty($conf->global->EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD)) {
						$sql .= ', ef.' . $conf->global->EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD . ' AS extrafield_invoice';
					}
					if ($show_payment_details) {
						$sql .= ', cp.code  AS payment_mode_code';
						$sql .= ', cp.libelle  AS payment_mode_label';
						$sql .= ', p.datep AS payment_date';
						$sql .= ', p.num_paiement AS payment_number';
						$sql .= ', b.banque AS payment_bank';
						$sql .= ', b.emetteur AS payment_sender';
						$sql .= ', pf.amount AS payment_amount';
					}
					$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture AS f';
					if (!empty($conf->global->EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD)) {
						$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields AS ef';
						$sql .= ' ON (f.rowid = ef.fk_object)';
					}
					$sql .= ' LEFT JOIN (';
					$sql .= "   SELECT pf.fk_facture";
					$sql .= '   , sum(pf.amount) AS amount_payed';
					$sql .= '   , count(pf.amount) AS count_amount_payed';
					$sql .= '   , sum(rc.amount_ttc) AS amount_creditnote';
					$sql .= '   , sum(rc2.amount_ttc) AS amount_creditused';
					$sql .= '   FROM ' . MAIN_DB_PREFIX . 'paiement_facture AS pf';
					$sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'societe_remise_except AS rc ON rc.fk_facture_source = pf.fk_facture';
					$sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'societe_remise_except AS rc2 ON rc2.fk_facture = pf.fk_facture';
					$sql .= '   GROUP BY pf.fk_facture';
					$sql .= ' ) AS pay ON pay.fk_facture = f.rowid';
					if ($show_payment_details) {
						$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiement_facture AS pf ON pf.fk_facture = f.rowid';
						$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiement AS p ON p.rowid = pf.fk_paiement';
						$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_paiement AS cp ON cp.id = p.fk_paiement';
						$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank AS b ON b.rowid = p.fk_bank';
					}
					if ($add_product_tags) {
						$sql .= ' LEFT JOIN (';
						$sql .= "   SELECT fd.fk_facture, GROUP_CONCAT(DISTINCT c.label SEPARATOR '" . $this->db->escape($product_tags_separator) . "') AS tags";
						$sql .= '   FROM ' . MAIN_DB_PREFIX . 'facturedet AS fd';
						$sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie_product AS cp ON cp.fk_product = fd.fk_product';
						$sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie AS c ON c.rowid = cp.fk_categorie';
						$sql .= '   GROUP BY fd.fk_facture';
						$sql .= ' ) AS pt ON pt.fk_facture = f.rowid';
					}
					$sql .= ' WHERE f.entity IN (' . getEntity('facture') . ')';
					$sql .= ' AND f.datef >= \'' . dol_print_date($date_start, 'dayrfc') . '\'';
					$sql .= ' AND f.datef <= \'' . dol_print_date($date_end, 'dayrfc') . '\'';
					$sql .= ' AND f.fk_soc = ' . $object->id;
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
					if (!empty($conf->global->EXTRAITCOMPTECLIENT_ORDERBY)) {
						($conf->global->EXTRAITCOMPTECLIENT_ORDERBY = 0) ? $orderby = 'DESC' : $orderby = 'ASC';
					} else {
						$orderby = 'DESC';
					}
					$sql .= ' ORDER BY f.datef ' . $orderby;
					if ($show_payment_details) {
						$sql .= ' , p.datep ASC';
					}
				} else if ($export_type == 'Supplier') {
					// Get lines of the supplier account statut
					$sql = 'SELECT DISTINCT f.rowid';
					$sql .= ', f.datef AS date';
					$sql .= ', f.paye';
					$sql .= ', f.ref_supplier AS label';
					$sql .= ', f.ref AS label_externe';
					if ($show_payment_deadline) {
						$sql .= ', f.date_lim_reglement AS date_limite';
					}
					$sql .= ', f.total_ttc AS total_amount';
					$sql .= ', f.type AS invoicetype';
					$sql .= ', pay.amount_payed';
					$sql .= ', pay.count_amount_payed';
					$sql .= ', rc.amount_creditnote';
					$sql .= ', rc2.amount_creditused';
					if ($add_product_tags) {
						$sql .= ', pt.tags AS product_tags';
					}
					if ($show_payment_details) {
						$sql .= ', cp.code  AS payment_mode_code';
						$sql .= ', cp.libelle  AS payment_mode_label';
						$sql .= ', p.datep AS payment_date';
						$sql .= ', p.num_paiement AS payment_number';
						$sql .= ', b.banque AS payment_bank';
						$sql .= ', b.emetteur AS payment_sender';
						$sql .= ', pf.amount AS payment_amount';
					}
					$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture_fourn AS f';
					$sql .= ' LEFT JOIN (';
					$sql .= "   SELECT pf.fk_facturefourn";
					$sql .= '   , sum(pf.amount) AS amount_payed';
					$sql .= '   , count(pf.amount) AS count_amount_payed';
					$sql .= '   FROM ' . MAIN_DB_PREFIX . 'paiementfourn_facturefourn AS pf';
					$sql .= '   GROUP BY pf.fk_facturefourn';
					$sql .= ' ) AS pay ON pay.fk_facturefourn = f.rowid';

					$sql .= ' LEFT JOIN (';
					$sql .= '   SELECT sre.fk_invoice_supplier_source AS fk_facture_source, SUM(sre.amount_ttc) AS amount_creditnote';
					$sql .= '   FROM ' . MAIN_DB_PREFIX . 'societe_remise_except AS sre';
					$sql .= '   GROUP BY sre.fk_invoice_supplier_source';
					$sql .= ') AS rc ON rc.fk_facture_source = f.rowid';

					$sql .= ' LEFT JOIN (';
					$sql .= '   SELECT sre.fk_invoice_supplier AS fk_facture, SUM(sre.amount_ttc) AS amount_creditused';
					$sql .= '   FROM ' . MAIN_DB_PREFIX . 'societe_remise_except AS sre';
					$sql .= '   GROUP BY sre.fk_invoice_supplier';
					$sql .= ') AS rc2 ON rc2.fk_facture = f.rowid';

					if ($show_payment_details) {
						$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiementfourn_facturefourn AS pf ON pf.fk_facturefourn = f.rowid';
						$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'paiementfourn AS p ON p.rowid = pf.fk_paiementfourn';
						$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_paiement AS cp ON cp.id = p.fk_paiement';
						$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank AS b ON b.rowid = p.fk_bank';
					}
					if ($add_product_tags) {
						$sql .= ' LEFT JOIN (';
						$sql .= "   SELECT ffd.fk_facture_fourn, GROUP_CONCAT(DISTINCT c.label SEPARATOR '" . $this->db->escape($product_tags_separator) . "') AS tags";
						$sql .= '   FROM ' . MAIN_DB_PREFIX . 'facture_fourn_det AS ffd';
						$sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie_product AS cp ON cp.fk_product = ffd.fk_product';
						$sql .= '   LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie AS c ON c.rowid = cp.fk_categorie';
						$sql .= '   GROUP BY ffd.fk_facture_fourn';
						$sql .= ' ) AS pt ON pt.fk_facture_fourn = f.rowid';
					}
					$sql .= ' WHERE f.entity IN (' . getEntity('facture_fourn') . ')';
					$sql .= ' AND f.datef >= \'' . dol_print_date($date_start, 'dayrfc') . '\'';
					$sql .= ' AND f.datef <= \'' . dol_print_date($date_end, 'dayrfc') . '\'';
					$sql .= ' AND f.fk_soc = ' . $object->id;
					if (empty($show_invoice_payed)) {
						$sql .= " AND f.paye = 0";
					}
					if ($show_invoice_abandoned) {
						$sql .= " AND f.fk_statut > 0"; // No draft invoice
					} else {
						$sql .= " AND f.fk_statut IN (" . FactureFournisseur::STATUS_VALIDATED . "," . FactureFournisseur::STATUS_CLOSED . ")";
					}
					if (!empty($conf->global->EXTRAITCOMPTECLIENT_ORDERBY)) {
						($conf->global->EXTRAITCOMPTECLIENT_ORDERBY = 0) ? $orderby = 'DESC' : $orderby = 'ASC';
					} else {
						$orderby = 'DESC';
					}
					$sql .= ' ORDER BY f.datef ' . $orderby;
					if ($show_payment_details) {
						$sql .= ' , p.datep ASC';
					}
				}

				$resql = $db->query($sql);
				if (!$resql) {
					$this->error = $db->error();
					return 0;
				}

				// Set nblignes with the new facture lines content after hook
				$nblignes = $db->num_rows($resql);

				// Create csv instance
				$csv = new ExportCsv($this->db);
				$csv->separator = !empty($conf->global->EXPORT_CSV_SEPARATOR_TO_USE) ? $conf->global->EXPORT_CSV_SEPARATOR_TO_USE : ',';

				// Open file
				$csv->open_file($file, $outputlangs);

				// Write header
				$csv->write_header($outputlangs);

				$csv->write_title($array_export_fields_label, $array_selected_sorted, $outputlangs, null);

				$last_invoice_id = 0;
				for ($i = 0; $i < $nblignes; $i++) {
					$line = $this->db->fetch_object($resql);

					$line->status = $outputlangs->transnoentitiesnoconv($line->paye ? 'Paid' : 'Unpaid');

					if ($show_payment_details) {
						if ($last_invoice_id != $line->rowid) {
							$last_invoice_id = $line->rowid;
							$balance = $line->total_amount;

							// Add credit lines
							if ($line->amount_creditnote != 0 && $line->invoicetype <> 3) {
								$credit_line = clone $line;
								$credit_line->payment_type = $outputlangs->transnoentitiesnoconv('ExtraitCompteClientPDFAccountStatutCreditNote');
								$credit_line->payment_mode_code = null;
								$credit_line->payment_mode_label = null;
								$credit_line->payment_date = null;
								$credit_line->payment_number = null;
								$credit_line->payment_bank = null;
								$credit_line->payment_sender = null;
								$credit_line->amount_rule = $line->amount_creditnote;
								$balance += $line->amount_creditnote;
								$credit_line->balance = $balance;
								$this->addLine($csv, $array_selected_sorted, $credit_line, $outputlangs, $array_types);
							}

							if ($line->amount_creditused != 0) {
								$credit_line = clone $line;
								$credit_line->payment_type = $outputlangs->transnoentitiesnoconv('ExtraitCompteClientPDFAccountStatutCreditNoteUsed');
								$credit_line->payment_mode_code = null;
								$credit_line->payment_mode_label = null;
								$credit_line->payment_date = null;
								$credit_line->payment_number = null;
								$credit_line->payment_bank = null;
								$credit_line->payment_sender = null;
								$credit_line->amount_rule = $line->amount_creditused;
								$balance -= $line->amount_creditused;
								$credit_line->balance = $balance;
								$this->addLine($csv, $array_selected_sorted, $credit_line, $outputlangs, $array_types);
							}
						}

						$line->payment_type = $outputlangs->transnoentitiesnoconv('Payment');
						$line->amount_rule = $line->payment_amount;
						$balance -= $line->amount_rule;
					} else {
						// 2nd column - Total payed
						if ($line->invoicetype <> 3) {
							// if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
							//     $amount_payed = - $line->amount_creditnote;
							//     $amount_payed .= + $line->amount_creditused;
							//     if ($line->count_amount_payed > 0) $amount_payed .= ($line->amount_payed / $line->count_amount_payed);
							// } else {
							$line->amount_rule = $line->amount_payed - $line->amount_creditnote + $line->amount_creditused;
							// }
						} else {
							$line->amount_rule = $line->amount_payed + $line->amount_creditused;
						}

						// 3rd column - Total Remain to pay
						$balance = $line->total_amount - $line->amount_rule;
					}

					$line->balance = $balance;

					$this->addLine($csv, $array_selected_sorted, $line, $outputlangs, $array_types);
				}

				// Write footer
				$csv->write_footer($outputlangs);

				// Close file
				$csv->close_file();

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

				if (!empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				$this->result = array('fullpath'=>$file);

				return 1;   // No error
			} else {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->transnoentities("ErrorConstantNotDefined", "SOC_OUTPUTDIR");
			return 0;
		}
		$this->error = $langs->transnoentities("ErrorUnknown");
		return 0;   // Erreur par defaut
	}

	public function addLine(&$csv, &$array_selected_sorted, $line, &$outputlangs, &$array_types)
	{
		global $conf;

		$line->date = dol_print_date($this->db->jdate($line->date), 'day', false, $outputlangs);
		$line->date_limite = dol_print_date($this->db->jdate($line->date_limite), 'day', false, $outputlangs);
		$line->payment_date = dol_print_date($this->db->jdate($line->payment_date), 'day', false, $outputlangs);
		$line->payment_mode_label = ($outputlangs->transnoentitiesnoconv("PaymentTypeShort" . $line->payment_mode_code) != ("PaymentTypeShort" . $line->payment_mode_code) ? $outputlangs->transnoentitiesnoconv("PaymentTypeShort" . $line->payment_mode_code) : ($line->payment_mode_label != '-' ? $line->payment_mode_label : ''));

		$line->total_amount = price($line->total_amount, 0, $outputlangs);
		$line->amount_rule = price($line->amount_rule, 0, $outputlangs);
		$line->balance = price($line->balance, 0, $outputlangs);
		if (!empty($conf->global->EXTRAITCOMPTECLIENT_DELETESPACEFROMNUMBERONCSV)) { // To addapt for Excel, if the option is checked
			$line->amount_rule = str_replace(' ', '', $line->amount_rule);
			$line->total_amount = str_replace(' ', '', $line->total_amount);
			$line->balance = str_replace(' ', '', $line->balance);
		}

		$csv->write_record($array_selected_sorted, $line, $outputlangs, $array_types);
	}
}

