<?php
/* Copyright (C) 2004-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012	  	Christophe Battarel <christophe.battarel@altairis.fr>
 * Copyright (C) 2012	   Cédric Salvador	 <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015	   Marcos García	   <marcosgdf@gmail.com>
 * Copyright (C) 2017-2019  Open-DSI			<support@open-dsi.fr>
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
 *	\file	   htdocs/core/modules/societe/doc/pdf_account_statut.modules.php
 *	\ingroup	societe
 *	\brief	  File of class to generate customers account statut from account_statut model
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';


/**
 *	Class to manage PDF account statut template account_statut
 */
class pdf_account_statut extends CommonDocGenerator
{
	public $db;
	public $name;
	public $description;
	public $type;

	public $phpmin = array(4,3,0); // Minimum version of PHP required by module
	public $version = 'dolibarr';

	public $page_largeur;
	public $page_hauteur;
	public $format;
	public $marge_gauche;
	public $marge_droite;
	public $marge_haute;
	public $marge_basse;

	public $emetteur;	// Objet societe qui emet


	/**
	 * @var float X position for the columns
	 */
	public $posxdate;
	public $posxlabel;
	public $posxdeadline;
	public $posxtotalamount;
	public $posxamountrule;
	public $posxbalance;

	public $posxpaiementlabel;
	public $posxdatepaiement;
	public $posxmontantreglement;

	/**
	 * @var bool display amount in invoice currencies
	 */
	private $add_multicurrency;

	/**
	 * @var array list of currencies for multicurrency option
	 */
	private $currencies = array();

	/**
	 * @var array Export parameters
	 */
	public $exportparameters = array();


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db	  Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		$langs->load("main");
		$langs->load("extraitcompteclient@extraitcompteclient");

		$this->db = $db;
		$this->name = "account_statut";
		$this->description = $langs->trans('ExtraitCompteClientPDFAccountStatutDescription');
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template
		// societe table has no property last_main_doc before Dolibarr 17
		$this->update_main_doc_field = 0;
		if (version_compare(DOL_VERSION, '17.0') >= 0) {
			$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template
		}

		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Affiche mode reglement
		$this->option_condreg = 1;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 1;                // Affiche si il y a eu escompte
		$this->option_credit_note = 1;             // Support credit notes
		$this->option_freetext = 1;                   // Support add of a personalised text
		$this->option_draft_watermark = 1;           // Support add of a watermark on drafts

		$this->franchise = !$mysoc->tva_assuj;

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code = substr($langs->defaultlang, -2);    // By default, if was not defined

		// Define position of columns

		$this->posxdate = $this->marge_gauche;
		//$this->posxlabel=40;
		$this->posxlabel = 35;
		$this->posxdeadline = 0;
		$this->posxtotalamount = 80;
		$this->posxamountrule = 110;
		$this->posxbalance = 150;

		$this->posxpaiementlabel = $this->marge_gauche;
		$this->posxdatepaiement = 80;
		$this->posxmontantreglement = 150;

		if ($this->page_largeur < 210) { // To work with US executive format
			$this->posxlabel -= 20;
			$this->posxtotalamount -= 20;
			$this->posxamountrule -= 20;
			$this->posxbalance -= 20;
		}
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
	 *  @return	 int		 					1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $mc;

		if (!is_object($outputlangs)) $outputlangs = $langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output = 'ISO-8859-1';

		// surcharge les traductions
		$outputlangsold = $outputlangs;
		$outputlangs = new Translate("", $conf);
		$newlang = '';
		if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang)) {
			$newlang = $object->thirdparty->default_lang;
		}
		if (!empty($newlang)) {
			$outputlangs->setDefaultLang($newlang);
		} else {
			$outputlangs->setDefaultLang($outputlangsold->defaultlang);
		}
		$outputlangs->load("extraitcompteclient@extraitcompteclient");
		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("bills");
		$outputlangs->load("companies");

		$this->exportparameters = $object->context['account_statut'];
		$date_start = $this->exportparameters['date_start'];
		$date_end = $this->exportparameters['date_end'];

		$show_subsidiaries = !empty($this->exportparameters['show_subsidiaries']);
		if ($show_subsidiaries) {
			$sql = "SELECT s.rowid, s.client, s.fournisseur, s.nom as name, s.name_alias, s.email, s.address, s.zip, s.town, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur, s.canvas";
			$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
			$sql .= " WHERE s.parent = " . ((int) $object->id);
			$sql .= " AND s.entity IN (" . getEntity('societe') . ")";
			$sql .= " ORDER BY s.nom";

			$result = $db->query($sql);
			$num = $db->num_rows($result);

			if ($num) {
				$i = 0;
				$idssubsidiaries = array();
				while ($i < $num) {
					$obj = $db->fetch_object($result);
					array_push($idssubsidiaries, $obj->rowid);
					$i++;
				}
			}
		}
		$show_invoice_payed = !empty($this->exportparameters['show_invoice_payed']);
		$show_invoice_abandoned = !empty($this->exportparameters['show_invoice_abandoned']);
		$show_payment_deadline = !empty($this->exportparameters['show_payment_deadline']);
		$show_payment_details = !empty($this->exportparameters['show_payment_details']);
		$add_product_tags = !empty($this->exportparameters['add_product_tags']);
		$show_thirdparty_ref = !empty($this->exportparameters['show_thirdparty_ref']);
		$this->add_multicurrency = !empty($conf->multicurrency->enabled) && !empty($this->exportparameters['add_multicurrency']);

		$export_type = $this->exportparameters['export_type'];

		if ($show_payment_deadline) { // to adjust column position when payment deadlines are displayed
			$this->posxdeadline = 80;
			$this->posxtotalamount = 105;
			$this->posxamountrule = 137;
			$this->posxbalance = 170;
			if ($this->page_largeur < 210) { // To work with US executive format
				$this->posxlabel -= 20;
				$this->posxdeadline -= 20;
				$this->posxtotalamount -= 20;
				$this->posxamountrule -= 20;
				$this->posxbalance -= 20;
			}
		}

		if ($conf->societe->multidir_output[$object->entity]) {
			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->societe->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			} else {
				$objectid = dol_sanitizeFileName($object->id);
				$dir = $conf->societe->multidir_output[$object->entity] . "/" . $objectid;
				$datefile = dol_print_date(dol_now(), '%Y-%m-%d');
				if ($export_type == 'Customer') {
					$thirdparty_code = $object->code_client;
				} elseif ($export_type == 'Supplier') {
					$thirdparty_code = $object->code_fournisseur;
					$this->posxlabel = 35; // To adjust column position when it's a supplier, also, usefull out of the fonction because $export_type is not available
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
				$file = $dir . "/" . dol_sanitizeFileName($file_name) . ".pdf";
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
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

				if (empty($idssubsidiaries)) $idssubsidiaries = array(); // fix PHP8.2 warnings
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
					$sql .= ', f.fk_statut AS invoicestatus';
					$sql .= ', f.close_code AS invoiceclosecode';
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

				$resql = $db->query($sql);
				if (!$resql) {
					$this->error = $db->error();
					return 0;
				}

				// Set nblignes with the new facture lines content after hook
				$nblignes = $db->num_rows($resql);

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);    // Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);

				$heightforinfotot = 5;    // Height reserved to output the info and total part and payment part
				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5);    // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 14;    // Height reserved to output the footer (value include bottom margin)

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));

				// Set path to the background PDF File
				if (empty($conf->global->MAIN_DISABLE_FPDI) && !empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output . '/' . $conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);
				$pdf->SetFillColor(224, 224, 224);
				$pdf->SetTitle($outputlangs->convToOutputCharset($object->name));
				$pdf->SetSubject($outputlangs->transnoentities("ExtraitCompteClientPDFAccountStatut'.$export_type.'"));
				$pdf->SetCreator("Dolibarr " . DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->name) . " " . $outputlangs->transnoentities("ExtraitCompteClientPDFAccountStatut'.$export_type.'") . " " . $outputlangs->transnoentities("ExtraitCompteClientPDFAccountStatutPeriod", dol_print_date($date_start, "daytext", false, $outputlangs), dol_print_date($date_end, "daytext", false, $outputlangs)));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;

				$this->_pagehead($pdf, $object, 1, $outputlangs, $export_type);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');        // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$currency = $conf->currency;
				$tab_top = 90;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 42 : 10);
				$tab_height = 130;
				$tab_height_newpage = 150;

				$pdf->startTransaction();
				$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $currency, false);
				$y_after_top = $pdf->getY();
				$pdf->rollbackTransaction(true);

				$iniY = $y_after_top + 2;
				$curY = $y_after_top + 2;
				$nexY = $y_after_top + 2;

				$total_totalamount = 0;
				$total_multicurrencytotalamount = 0;
				$total_amountrule = 0;
				$total_multicurrencyamountrule = 0;
				$total_balance = 0;

				// pour mettre ou non une couleur en arriere plan sur les lignes de facture
				$color_back = false;

				if ($conf->global->EXTRAITCOMPTECLIENT_COLOR_LINE_PDF != '') {
					$rgbcolorarray = colorHexToRgb($conf->global->EXTRAITCOMPTECLIENT_COLOR_LINE_PDF, false, true);
					$pdf->SetFillColorArray($rgbcolorarray);
					$color_back = true;
				}
				$total_multicurrencybalance = 0;
				$consistency_multicurrency = 1;

				// Loop on each lines
				for ($i = 0; $i < $nblignes; $i++) {
					$line = $this->db->fetch_object($resql);
					$invoice_ref = $line->label;

					if ($add_product_tags && !empty($line->product_tags)) {
						$line->label .= " - [" . $line->product_tags . "]";
					}

					if (!empty($line->extrafield_invoice)) {
						$line->label .= " - (" . $line->extrafield_invoice . ")";
					}

					if (!empty($this->add_multicurrency)) {
						$currency = $line->facture_multicurrency_code;
						$trans_currency = $outputlangs->transnoentitiesnoconv('Currency' . $currency) . ' (' . $outputlangs->getCurrencySymbol($currency) . ')';
						if (!in_array($trans_currency, $this->currencies)) {
							$this->currencies[] = $trans_currency;
						}
					}

					// 1st column - Total Amount
					$totalamount = $line->total_amount;
					$multicurrencytotalamount = $line->multicurrency_total_amount;

					// 2nd column - Total payed
					if ($line->invoicetype <> 3) {    // Deposit
						// if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
						//    $amount_payed = - $line->amount_creditnote;
						//    $amount_payed .= + $line->amount_creditused;
						//    if ($line->count_amount_payed > 0) $amount_payed .= ($line->amount_payed / $line->count_amount_payed);
						// } else {
						$amount_payed = $line->amount_payed - $line->amount_creditnote + $line->amount_creditused;
						$multicurrency_amount_payed = $line->multicurrency_amount_payed - $line->multicurrency_amount_creditnote + $line->multicurrency_amount_creditused;
						// }
					} else {
						$amount_payed = $line->amount_payed + $line->amount_creditused;
						$multicurrency_amount_payed = $line->multicurrency_amount_payed + $line->multicurrency_amount_creditused;
					}

					// 3rd column - Total Remain to pay
					$balance = $line->total_amount - $amount_payed;
					$multicurrency_balance = $line->multicurrency_total_amount - $multicurrency_amount_payed;

					// Classified paid case
					$amount_remaining = 0;
					$multicurrency_amount_remaining = 0;
					if ($export_type == 'Customer' &&
						($line->invoicestatus == Facture::STATUS_CLOSED || $line->invoicestatus == Facture::STATUS_ABANDONED) &&
						in_array($line->invoiceclosecode, [ 'discount_vat' , 'badcustomer', 'product_returned', 'abandon' ])
					) {
						$amount_payed = $totalamount;
						$multicurrency_amount_payed = $multicurrencytotalamount;
						$amount_remaining = $balance;
						$multicurrency_amount_remaining = $multicurrency_balance;
						$balance = 0;
						$multicurrency_balance = 0;
					}

					// Total columns
					$total_totalamount += $totalamount;
					$total_multicurrencytotalamount += $multicurrencytotalamount;
					$total_amountrule += $amount_payed;
					$total_multicurrencyamountrule += $multicurrency_amount_payed;
					$total_balance += $balance;
					$total_multicurrencybalance += $multicurrency_balance;

					// Payment details
					$nblignes_d = 0;
					// paiement detail list
					$paiement_detail_list = array();
					if ($show_payment_details) {
						// Customer
						if ($export_type == 'Customer') {
							$sql_d = 'SELECT p.datep as datepayment, p.ref as label_rglt, c.code as payment_code, c.libelle as payment_label, pf.amount as montant_rglt, p.num_paiement';
							$sql_d .= ', p.multicurrency_amount AS multicurrency_montant_rglt';
							$sql_d .= ', f.ref AS num_fac';
							$sql_d .= ' FROM ' . MAIN_DB_PREFIX . 'paiement AS p ';
							$sql_d .= 'JOIN ' . MAIN_DB_PREFIX . 'paiement_facture AS pf ON p.rowid = pf.fk_paiement ';
							$sql_d .= 'JOIN ' . MAIN_DB_PREFIX . 'facture AS f ON f.rowid = pf.fk_facture ';
							$sql_d .= 'JOIN ' . MAIN_DB_PREFIX . 'c_paiement AS c ON p.fk_paiement = c.id ';
							$sql_d .= ' WHERE f.entity IN (' . getEntity('facture') . ')';
							$sql_d .= ' AND f.ref = \'' . $invoice_ref . '\' AND pf.fk_paiement = p.rowid ';
							$sql_d .= 'ORDER BY f.ref, p.datep ASC;';
						}

						// Supplier
						if ($export_type == 'Supplier') {
							$sql_d = 'SELECT p.datep as datepayment, p.ref as label_rglt, c.code as payment_code, c.libelle as payment_label, pf.amount as montant_rglt, p.num_paiement';
							$sql_d .= ', p.multicurrency_amount AS multicurrency_montant_rglt';
							$sql_d .= ', f.ref AS num_fac';
							$sql_d .= ' FROM ' . MAIN_DB_PREFIX . 'paiementfourn AS p ';
							$sql_d .= 'JOIN ' . MAIN_DB_PREFIX . 'paiementfourn_facturefourn AS pf ON p.rowid = pf.fk_paiementfourn ';
							$sql_d .= 'JOIN ' . MAIN_DB_PREFIX . 'facture_fourn AS f ON f.rowid = pf.fk_facturefourn ';
							$sql_d .= 'JOIN ' . MAIN_DB_PREFIX . 'c_paiement AS c ON p.fk_paiement = c.id ';
							$sql_d .= ' WHERE f.entity IN (' . getEntity('facture_fourn') . ')';
							$sql_d .= ' AND f.ref = \'' . $invoice_ref . '\' AND pf.fk_paiementfourn = p.rowid ';
							$sql_d .= 'ORDER BY f.ref, p.datep ASC;';
						}

						$resql_d = $db->query($sql_d);
						if (!$resql_d) {
							$this->error = $db->error();
							return 0;
						}

						$nblignes_d = $db->num_rows($resql_d);

						$paiement_detail_list[$invoice_ref] = array();
						for ($j = 0; $j < $nblignes_d; $j++) {
							$line_d = $this->db->fetch_object($resql_d);
							if ($show_payment_details && !empty($line_d->payment_label)) {
								$paiement_detail_list[$invoice_ref][] = array(
									'label' => $line_d->payment_label,
									'num' => $line_d->num_paiement,
									'date' => $line_d->datepayment,
									'montant' => $line_d->montant_rglt,
									'montant_multicurrency' => $line_d->multicurrency_montant_rglt,
									'code' => $line_d->payment_code,
									'custom_code_label' => '',
								);
							}
						}

						if ($export_type == 'Customer' && $line->invoicetype != Facture::TYPE_CREDIT_NOTE) {
							// Loop on each credit note or deposit amount applied
							$sql_cn = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
							$sql_cn .= " re.description, re.fk_facture_source, fs.type AS invoicetype, fs.ref AS invoiceref";
							$sql_cn .= " FROM " . MAIN_DB_PREFIX . "societe_remise_except as re";
							$sql_cn .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture AS f ON f.rowid = re.fk_facture ';
							$sql_cn .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture AS fs ON fs.rowid = re.fk_facture_source ';
							$sql_cn .= ' WHERE f.entity IN (' . getEntity('facture') . ')';
							$sql_cn .= ' AND f.ref = \'' . $invoice_ref . '\'';
							$resql_cn = $db->query($sql_cn);
							if (!$resql_cn) {
								$this->error = $db->error();
								return 0;
							}
							while ($obj_cn = $db->fetch_object($resql_cn)) {
								$tmp_label = '';
								if ($obj_cn->invoicetype == Facture::TYPE_CREDIT_NOTE) {
									$tmp_label = $langs->trans("CreditNote");
								} elseif ($obj_cn->invoicetype == Facture::TYPE_DEPOSIT) {
									$tmp_label = $langs->trans("Deposit");
								}
								$paiement_detail_list[$invoice_ref][] = array(
									'label' => '',
									'num' => $obj_cn->invoiceref,
									'date' => '',
									'montant' => $obj_cn->amount_ttc,
									'montant_multicurrency' => '',
									'code' => '',
									'custom_code_label' => $tmp_label,
								);
							}
							$db->free($resql_cn);
						}

						if ($export_type == 'Customer' &&
							($line->invoicestatus == Facture::STATUS_CLOSED || $line->invoicestatus == Facture::STATUS_ABANDONED) &&
							in_array($line->invoiceclosecode, [ 'discount_vat' , 'badcustomer', 'product_returned', 'abandon' ])
						) {
							$discount_label = '';
							if ($line->invoiceclosecode == 'discount_vat') {
								// Paye partiellement 'escompte'
								$discount_label = $langs->trans("Escompte");
							} elseif ($line->invoiceclosecode == 'badcustomer') {
								// Paye partiellement ou Abandon 'badcustomer'
								$discount_label = $langs->trans("Abandoned");
							} elseif ($line->invoiceclosecode == 'product_returned') {
								// Paye partiellement ou Abandon 'product_returned'
								$discount_label = $langs->trans("ProductReturned");
							} elseif ($line->invoiceclosecode == 'abandon') {
								// Paye partiellement ou Abandon 'abandon'
								$discount_label = $langs->trans("Abandoned");
							}
							if (!empty($discount_label)) {
								$paiement_detail_list[$invoice_ref][] = array(
									'label' => '',
									'num' => '',
									'date' => '',
									'montant' => $amount_remaining,
									'montant_multicurrency' => $multicurrency_amount_remaining,
									'code' => '',
									'custom_code_label' => $discount_label,
								);
							}
						}
					}

					$curY = $nexY;
					$pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
					$pdf->SetTextColor(0, 0, 0);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforinfotot);    // The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					$showpricebeforepagebreak = 1;

					$pdf->startTransaction();
					if ($color_back === true) {
						$pdf->SetFillColorArray($rgbcolorarray);
					}

					// Label
					$pdf->SetXY($this->posxlabel, $curY);
					if ($export_type == 'Supplier') {
						$label = !empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_REF_SUPPLIER) ? $line->label_externe . ' (' . $line->label . ')' : $line->label;
					} else {
						$label = !empty($show_thirdparty_ref) ? $line->label . ' (' . $line->label_externe . ')' : $line->label;
					}
					$pdf->MultiCell($this->posxdeadline - $this->posxlabel, 3, $label, 0, 'L', $color_back);

					$pageposafter = $pdf->getPage();
					if ($pageposafter > $pageposbefore) {   // There is a pagebreak
						$pdf->rollbackTransaction(true);
						$pageposafter = $pageposbefore;

						$pdf->setPageOrientation('', 1, $heightforfooter);    // The only function to edit the bottom margin of current page to set it.

						// Date
						//$pdf->SetXY($this->posxdate, $curY);
						//$pdf->MultiCell($this->posxlabel-$this->posxdate, 3, dol_print_date(dol_stringtotime($line->date), 'day', false, $outputlangs), 0, 'C', 0);
						// Label
						$pdf->SetXY($this->posxlabel, $curY);
						if ($export_type == 'Supplier') {
							$label = !empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_REF_SUPPLIER) ? $line->label_externe . ' (' . $line->label . ')' : $line->label;
						} else {
							$label = !empty($show_thirdparty_ref) ? $line->label . ' (' . $line->label_externe . ')' : $line->label;
						}
						$pdf->MultiCell($this->posxdeadline - $this->posxlabel, 3, $label, 0, 'L', $color_back);

						$pageposafter = $pdf->getPage();
						$posyafter = $pdf->GetY();

						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {   // There is no space left for total+free text
							if ($i == ($nblignes - 1)) {   // No more lines, and no space left to show total, so we create a new page
								$pdf->AddPage('', '', true);
								if (!empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs, $export_type);
								$pdf->setPage($pageposafter + 1);
							}
						} else {
							// We found a page break
							$showpricebeforepagebreak = 0;
						}
					} else {   // No pagebreak
						$pdf->commitTransaction();
					}

					$nexY = $pdf->GetY();
					$pageposafter = $pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);    // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 1);   // On repositionne la police par defaut
					if ($color_back === true) {
						$pdf->SetFillColorArray($rgbcolorarray);
					}

					// Date
					$pdf->SetXY($this->posxdate, $curY);
					$pdf->MultiCell($this->posxlabel - $this->posxdate, 3, dol_print_date(dol_stringtotime($line->date), 'day', false, $outputlangs), 0, 'C', $color_back);


					// Date limite
					if ($this->posxdeadline > 0) {
						$pdf->SetXY($this->posxdeadline, $curY);
						$pdf->MultiCell($this->posxtotalamount - $this->posxdeadline, 3, dol_print_date(dol_stringtotime($line->date_limite), 'day', false, $outputlangs), 0, 'C', $color_back);
					}

					// Total amount
					$pdf->SetXY($this->posxtotalamount, $curY);
					$amount = $this->add_multicurrency ? $line->multicurrency_total_amount : $line->total_amount;
					$pdf->MultiCell($this->posxamountrule - $this->posxtotalamount - 2, 3, price($amount, 0, $outputlangs, 1, -1, -1, $currency), 0, 'R', $color_back);
					// Amount rule
					$pdf->SetXY($this->posxamountrule, $curY);
					$amount_payed = $this->add_multicurrency ? $multicurrency_amount_payed : $amount_payed;
					$pdf->MultiCell($this->posxbalance - $this->posxamountrule - 2, 3, price($amount_payed, 0, $outputlangs, 1, -1, -1, $currency), 0, 'R', $color_back);

					// Balance
					$pdf->SetXY($this->posxbalance, $curY);
					$balance = $this->add_multicurrency ? $multicurrency_balance : $balance;
					$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxbalance - 1, 3, price($balance, 0, $outputlangs, 1, -1, -1, $currency), 0, 'R', $color_back);

					// debut des lignes de reglement
					if (isset($paiement_detail_list[$invoice_ref])) {
						$nblignespayment = count($paiement_detail_list[$invoice_ref]);
						foreach ($paiement_detail_list[$invoice_ref] as $j => $paiement_detail) {
							// Add line
							if (!empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES)) {
								$pdf->setPage($pageposafter);
								$pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(188, 186, 186)));
								//$pdf->SetDrawColor(190,190,200);
								$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
								$pdf->SetLineStyle(array('dash' => 0, 'color' => array(47, 47, 47)));
							}

							$nexY += 2;    // Passe espace entre les lignes

							$payment_label = !empty($paiement_detail['custom_code_label']) ? $paiement_detail['custom_code_label'] : (($outputlangs->transnoentities("PaymentTypeShort" . $paiement_detail['code']) != ("PaymentTypeShort" . $paiement_detail['code'])) ? $outputlangs->transnoentities("PaymentTypeShort" . $paiement_detail['code']) : $paiement_detail['code']);
							$montant_rglt = $paiement_detail['montant'];
							$multicurrency_montant_rglt = $paiement_detail['montant_multicurrency'];
							$datepayment = $paiement_detail['date'];
							$num_paiement = $paiement_detail['num'];

							$curY = $nexY;
							$pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
							$pdf->SetTextColor(0, 0, 0);
							$pdf->setTopMargin($tab_top_newpage);
							$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforinfotot);    // The only function to edit the bottom margin of current page to set it.
							$pageposbefore = $pdf->getPage();

							$showpricebeforepagebreak = 1;

							$pdf->startTransaction();
							// Label
							$pdf->SetXY($this->posxlabel, $curY);
							$pdf->MultiCell($this->posxdeadline - $this->posxlabel, 3, $num_paiement, 0, 'L', 0);

							$pageposafter = $pdf->getPage();
							if ($pageposafter > $pageposbefore) {   // There is a pagebreak
								$pdf->rollbackTransaction(true);
								$pageposafter = $pageposbefore;

								$pdf->setPageOrientation('', 1, $heightforfooter);    // The only function to edit the bottom margin of current page to set it.


								$pdf->SetXY($this->posxlabel, $curY);
								$pdf->MultiCell($this->posxdeadline - $this->posxlabel, 3, $num_paiement, 0, 'L', 0);

								$pageposafter = $pdf->getPage();
								$posyafter = $pdf->GetY();

								if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {   // There is no space left for total+free text
									// if ($i == ($nblignes - 1)) // No more lines, and no space left to show total, so we create a new page
									if ($i == ($nblignes - 1) && $j == ($nblignespayment - 1)) {
										$pdf->AddPage('', '', true);
										if (!empty($tplidx)) $pdf->useTemplate($tplidx);
										if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs, $export_type);
										$pdf->setPage($pageposafter + 1);
									}
								} else {
									// We found a page break
									$showpricebeforepagebreak = 0;
								}
							} else {   // No pagebreak
								$pdf->commitTransaction();
							}

							$nexY = $pdf->GetY();
							$pageposafter = $pdf->getPage();

							$pdf->setPage($pageposbefore);
							$pdf->setTopMargin($this->marge_haute);
							$pdf->setPageOrientation('', 1, 0);    // The only function to edit the bottom margin of current page to set it.

							// We suppose that a too long description or photo were moved completely on next page
							if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
								$pdf->setPage($pageposafter);
								$curY = $tab_top_newpage;
							}

							// Date
							$showpricebeforepagebreak = 1;
							$pageposbefore = $pdf->getPage();
							$pdf->startTransaction();

							$pdf->SetFont('', '', $default_font_size - 1);   // On repositionne la police par defaut
							$pdf->SetXY($this->posxdate, $curY);
							$pdf->MultiCell($this->posxlabel - $this->posxdate, 3, $payment_label, 0, 'C', 0);

							$pageposafter = $pdf->getPage();
							if ($pageposafter > $pageposbefore) {   // There is a pagebreak
								$pdf->rollbackTransaction(true);
								$pageposafter = $pageposbefore;

								$pdf->setPageOrientation('', 1, $heightforfooter);    // The only function to edit the bottom margin of current page to set it.

								$pdf->SetFont('', '', $default_font_size - 1);   // On repositionne la police par defaut
								$pdf->SetXY($this->posxdate, $curY);
								$pdf->MultiCell($this->posxlabel - $this->posxdate, 3, $payment_label, 0, 'C', 0);

								$pageposafter = $pdf->getPage();
								$posyafter = $pdf->GetY();

								if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {   // There is no space left for total+free text
									// if ($i == ($nblignes - 1)) // No more lines, and no space left to show total, so we create a new page
									if ($i == ($nblignes - 1) && $j == ($nblignespayment - 1)) {
										$pdf->AddPage('', '', true);
										if (!empty($tplidx)) $pdf->useTemplate($tplidx);
										if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs, $export_type);
										$pdf->setPage($pageposafter + 1);
									}
								} else {
									// We found a page break
									$showpricebeforepagebreak = 0;
								}
							} else {   // No pagebreak
								$pdf->commitTransaction();
							}

							$nexY = $pdf->GetY();
							$pageposafter = $pdf->getPage();

							$pdf->setPage($pageposbefore);
							$pdf->setTopMargin($this->marge_haute);
							$pdf->setPageOrientation('', 1, 0);    // The only function to edit the bottom margin of current page to set it.

							// We suppose that a too long description or photo were moved completely on next page
							if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
								$pdf->setPage($pageposafter);
								$curY = $tab_top_newpage;
							}

							// Date
							// Reference = label
							// deja ecrite au dessus

							$pdf->SetFont('', '', $default_font_size - 1);   // On repositionne la police par defaut

							// Date limite -> Date de paiement
							if ($this->posxdeadline > 0) {
								$pdf->SetXY($this->posxdeadline, $curY);
								$pdf->MultiCell($this->posxtotalamount - $this->posxdeadline, 3, dol_print_date(dol_stringtotime($datepayment), 'day', false, $outputlangs), 0, 'C', 0);
							}

							// Total TTC -> ''
							$pdf->SetXY($this->posxtotalamount, $curY);
							$pdf->MultiCell($this->posxamountrule - $this->posxtotalamount - 2, 3, '', 0, 'R', 0);


							// Regle TTC -> Montant reglement
							$pdf->SetXY($this->posxamountrule, $curY);
							$montant_rglt = $this->add_multicurrency ? $multicurrency_montant_rglt : $montant_rglt;
							$pdf->MultiCell($this->posxbalance - $this->posxamountrule - 2, 3, price($montant_rglt, 0, $outputlangs, 1, -1, -1, $currency), 0, 'R', 0);


							// Reste TTC -> ''
							$pdf->SetXY($this->posxbalance, $curY);
							$pdf->MultiCell($this->posxamountrule - $this->posxtotalamount - 2, 3, '', 0, 'R', 0);
						}
					}
					// fin des lignes de reglement

					// Add line
					if (!empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblignes - 1)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash' => 0, 'color' => array(88, 86, 86)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
						$pdf->SetLineStyle(array('dash' => 0));
					}

					$nexY += 2;    // Passe espace entre les lignes

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $currency);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $currency);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);    // The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs, $export_type);
						$montant_rglt = "";
						$datepayment = "";
						$payment_label = "";
					}
					if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $currency);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $currency);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						// New page
						$pdf->AddPage();
						if (!empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs, $export_type);
					}
				}

				// Show square
				if ($pagenb == 1) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $currency);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $currency);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}

				if (!$this->add_multicurrency || count($this->currencies) < 2) {
					// Affiche zone totaux
					$posy = $this->_tableau_tot($pdf, $total_totalamount, $total_multicurrencytotalamount, $total_amountrule, $total_multicurrencyamountrule, $total_balance, $total_multicurrencybalance, $bottomlasttab, $outputlangs);
				}

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

				if (!empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				$this->result = array('fullpath' => $file);

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


	/**
	 *	Show total to pay
	 *
	 *	@param	PDF			$pdf								Object PDF
	 *	@param  double		$total_totalamount					Total amount HT
	 *	@param  double		$total_multicurrencytotalamount		Total amount HT in currency
	 *	@param  double		$total_amountrule					Amount rule HT
	 *	@param  double		$total_multicurrencyamountrule		Amount rule HT in currency
	 *	@param  double		$total_balance						Balance HT
	 *	@param  double		$total_multicurrencybalance			Balance HT in currency
	 *	@param	int			$posy								Position depart
	 *	@param	Translate	$outputlangs						Objet langs
	 *	@return int												Position pour suite
	 */
	public function _tableau_tot(&$pdf, $total_totalamount, $total_multicurrencytotalamount, $total_amountrule, $total_multicurrencyamountrule, $total_balance, $total_multicurrencybalance, $posy, $outputlangs)
	{
		global $conf, $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$tab2_top = $posy;
		$tab2_hl = 4;
		$pdf->SetFont('', 'B', $default_font_size - 1);

		// Background
		//$pdf->SetFillColor(248,248,248);
		$pdf->SetFillColor(224, 224, 224);
		$pdf->SetXY($this->marge_gauche, $posy);
		$pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab2_hl, '', 0, 'L', 1);

		// Date
		$pdf->SetXY($this->posxdate, $posy);
		$pdf->MultiCell($this->posxlabel - $this->posxdate, 3, '', 0, 'C', 0);

		// Label
		$pdf->SetXY($this->posxlabel, $posy);
		$pdf->MultiCell($this->posxdeadline - $this->posxlabel, 3, '', 0, 'L', 0);

		// DAte limite
		if ($this->posxdeadline > 0) {
			$pdf->SetXY($this->posxdeadline, $posy);
			$pdf->MultiCell($this->posxtotalamount - $this->posxdeadline, 3, '', 0, 'C', 0);
		}

		// Total amount HT
		$pdf->SetXY($this->posxtotalamount, $posy);
		$total_totalamount = $this->add_multicurrency ? $total_multicurrencytotalamount : $total_totalamount;
		$pdf->MultiCell($this->posxamountrule - $this->posxtotalamount - 2, 3, price($total_totalamount, 0, $outputlangs), 0, 'R', 0);

		// Amount rule HT
		$pdf->SetXY($this->posxamountrule, $posy);
		$total_amountrule = $this->add_multicurrency ? $total_multicurrencyamountrule : $total_amountrule;
		$pdf->MultiCell($this->posxbalance - $this->posxamountrule - 2, 3, price($total_amountrule, 0, $outputlangs), 0, 'R', 0);

		// Balance HT
		$pdf->SetXY($this->posxbalance, $posy);
		$total_balance = $this->add_multicurrency ? $total_multicurrencybalance : $total_balance;
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxbalance - 1, 3, price(price2num($total_balance), 0, $outputlangs), 0, 'R', 0);

		return ($tab2_top + $tab2_hl);
	}

	/**
	 * Compute max height of the table header
	 */
	private function _maxHeaderHeight($pdf, $tab_top, $prev_max_height)
	{
		$newY = $pdf->getY();
		$cur_height = $newY - $tab_top;
		return ($cur_height > $prev_max_height) ? $cur_height : $prev_max_height;
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			$pdf	 		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	public function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetDrawColor(11, 11, 11);
		$pdf->SetFont('', 'B', $default_font_size - 1);

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height);    // Rect prend une longueur en 3eme param et 4eme param

		// Header
		$max_header_height = 6;
		if (empty($hidetop)) {
			if ($this->add_multicurrency && !empty($this->currencies)) {
				$infocurrency = $outputlangs->transnoentities('ExtraitCompteClientAmountInMultiCurrency', implode(', ', $this->currencies));
			} else {
				$infocurrency = $outputlangs->transnoentities('AmountInCurrency', $outputlangs->transnoentitiesnoconv('Currency' . $currency));
			}

			$pdf->MultiCell($pdf->GetStringWidth($infocurrency) + 3, 2, $infocurrency, '', 'R', 0, 1, $this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($infocurrency) + 3), $tab_top - 6, true, 0, 0, false, 0, 'M', false);

			// Date
			$pdf->SetXY($this->posxdate, $tab_top + 1);
			$pdf->SetFillColor(214, 214, 214);
			$pdf->MultiCell($this->posxlabel - $this->posxdate - 1, 3, $outputlangs->transnoentities("ExtraitCompteClientPDFAccountStatutDate"), '', 'C', $fill = true);
			$max_header_height = $this->_maxHeaderHeight($pdf, $tab_top, $max_header_height);

			// Label
			if (!empty($conf->global->EXTRAITCOMPTECLIENT_DEFAULT_REF_SUPPLIER) && ($this->posxlabel == 35)) {
				$pdf->SetXY($this->posxlabel, $tab_top + 1);
				$pdf->SetFillColor(214, 214, 214);
				$pdf->MultiCell($this->posxdeadline - $this->posxlabel, 3, $outputlangs->transnoentities("ExtraitCompteClientPDFAccountStatutDeuxLabel"), '', 'L', $fill = true);
			} else {
				$pdf->SetXY($this->posxlabel, $tab_top + 1);
				$pdf->SetFillColor(214, 214, 214);
				$pdf->MultiCell($this->posxdeadline - $this->posxlabel, 3, $outputlangs->transnoentities("ExtraitCompteClientPDFAccountStatutLabel"), '', 'L', $fill = true);
			}
			$max_header_height = $this->_maxHeaderHeight($pdf, $tab_top, $max_header_height);

			// Payment Deadline
			if ($this->posxdeadline > 0) {
				$pdf->SetXY($this->posxdeadline, $tab_top + 1);
				$pdf->SetFillColor(214, 214, 214);
				$pdf->MultiCell($this->posxtotalamount - $this->posxdeadline, 3, $outputlangs->transnoentities("ExtraitCompteClientPDFAccountStatutLimitDate"), '', 'C', $fill = true);
			}
			$max_header_height = $this->_maxHeaderHeight($pdf, $tab_top, $max_header_height);

			// Total amount HT
			$pdf->SetXY($this->posxtotalamount, $tab_top + 1);
			$pdf->SetFillColor(214, 214, 214);
			$pdf->MultiCell($this->posxamountrule - $this->posxtotalamount - 2, 3, $outputlangs->transnoentities("ExtraitCompteClientPDFAccountStatutTotalAmountTTC"), '', 'R', $fill = true);
			$max_header_height = $this->_maxHeaderHeight($pdf, $tab_top, $max_header_height);

			// Amount rule HT
			$pdf->SetXY($this->posxamountrule, $tab_top + 1);
			$pdf->SetFillColor(214, 214, 214);
			$pdf->MultiCell($this->posxbalance - $this->posxamountrule - 2, 3, $outputlangs->transnoentities("ExtraitCompteClientPDFAccountStatutAmountRuleTTC"), '', 'R', $fill = true);
			$max_header_height = $this->_maxHeaderHeight($pdf, $tab_top, $max_header_height);

			// Balance HT
			$pdf->SetXY($this->posxbalance, $tab_top + 1);
			$pdf->SetFillColor(214, 214, 214);
			$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxbalance - 1, 3, $outputlangs->transnoentities("ExtraitCompteClientPDFAccountStatutBalanceTTC"), '', 'R', $fill = true);
			$max_header_height = $this->_maxHeaderHeight($pdf, $tab_top, $max_header_height);

			// Header line
			$pdf->line($this->marge_gauche, $tab_top + $max_header_height + 1, $this->page_largeur - $this->marge_droite, $tab_top + $max_header_height + 1);

			// Filler
			$pdf->SetXY($this->posxdate, $tab_top + 1);
			$pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, $max_header_height - 1, '', '', 'C', $fill = true);

		}

		// Vertical line
		$pdf->line($this->posxlabel - 1, $tab_top, $this->posxlabel - 1, $tab_top + $tab_height);
		if ($this->posxdeadline > 0) {
			$pdf->line($this->posxdeadline - 1, $tab_top, $this->posxdeadline - 1, $tab_top + $tab_height);
		}
		$pdf->line($this->posxtotalamount - 1, $tab_top, $this->posxtotalamount - 1, $tab_top + $tab_height);
		$pdf->line($this->posxamountrule - 1, $tab_top, $this->posxamountrule - 1, $tab_top + $tab_height);
		$pdf->line($this->posxbalance - 1, $tab_top, $this->posxbalance - 1, $tab_top + $tab_height);
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf	 		Object PDF
	 *  @param  Object		$object	 	Object to show
	 *  @param  int			$showaddress	0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  String		$export_type	Customer or Supplier
	 *  @return	void
	 */
	public function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $export_type)
	{
		global $conf, $langs;

		$outputlangs->load("main");
		$outputlangs->load("extraitcompteclient@extraitcompteclient");

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 110;

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - $w;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
		if ($this->emetteur->logo) {
			if (is_readable($logo)) {
				$height = pdf_getHeightForLogo($logo);
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);    // width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text = $this->emetteur->name;
			$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title = $outputlangs->transnoentities("ExtraitCompteClientPDFAccountStatut" . $export_type);
		$pdf->MultiCell($w, 3, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size);

		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell($w, 4, $outputlangs->transnoentities($export_type) . " : " . $outputlangs->convToOutputCharset($object->name), '', 'R');

		$posy += 1;
		$pdf->SetFont('', '', $default_font_size - 2);

		$posy += 4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell($w, 3, $outputlangs->transnoentities("Date") . " : " . dol_print_date(dol_now(), "day", false, $outputlangs), '', 'R');

		$posy += 4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$date_start = $this->exportparameters['date_start'];
		$date_end = $this->exportparameters['date_end'];
		$pdf->MultiCell($w, 3, $outputlangs->transnoentities("Period") . " : " . dol_print_date($date_start, "day", false, $outputlangs) . " - " . dol_print_date($date_end, "day", false, $outputlangs), '', 'R');

		if ($export_type == 'Customer') {
			$thirdparty_language_key = 'CustomerCode';
			$thirdparty_code = $object->code_client;
		} elseif ($export_type == 'Supplier') {
			$thirdparty_language_key = 'SupplierCode';
			$thirdparty_code = $object->code_fournisseur;
		}
		$posy += 3;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell($w, 3, $outputlangs->transnoentities($thirdparty_language_key) . " : " . $outputlangs->transnoentities($thirdparty_code), '', 'R');

		$posy += 1;

		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, 3, 'R', $default_font_size);

		if ($showaddress) {
			// Sender properties
			$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object);

			// Show sender
			$posy = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
			$posx = $this->marge_gauche;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->page_largeur - $this->marge_droite - 80;

			$hautcadre = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 38 : 40;
			$widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 82;


			// Show sender frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy - 5);
			$pdf->MultiCell(66, 5, $outputlangs->transnoentities("BillFrom") . ":", 0, 'L');
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(230, 230, 230);
			$pdf->MultiCell($widthrecbox, $hautcadre, "", 0, 'R', 1);
			$pdf->SetTextColor(0, 0, 60);

			// Show sender name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy = $pdf->getY();

			// Show sender information
			$pdf->SetXY($posx + 2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, 'L');


			//Recipient name
			$thirdparty = $object;

			$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

			$carac_client = pdf_build_address($outputlangs, $this->emetteur, $object, '', false, 'target', $object);

			// Show recipient
			$widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 100;
			if ($this->page_largeur < 210) $widthrecbox = 84;    // To work with US executive format
			$posy = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx + 2, $posy - 5);
			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo") . ":", 0, 'L');
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

			// Show recipient name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 2, $carac_client_name, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}

		$pdf->SetTextColor(0, 0, 0);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	PDF			$pdf	 			PDF
	 * 		@param	Object		$object				Object to show
	 *	  @param	Translate	$outputlangs		Object lang for output
	 *	  @param	int			$hidefreetext		1=Hide free text
	 *	  @return	int								Return height of bottom margin including footer text
	 */
	public function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 0 : $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf, $outputlangs, 'SOCIETE_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}
}

