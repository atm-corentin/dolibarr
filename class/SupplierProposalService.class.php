<?php
/* Copyright (C) 2024 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

/**
 * Service class for Supplier Proposal operations
 * Handles data access and business logic
 */
class SupplierProposalService
{
	/** @var DoliDB */
	private $db;

	/** @var Conf */
	private $conf;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param Conf $conf Configuration object
	 */
	public function __construct(DoliDB $db, Conf $conf)
	{
		$this->db = $db;
		$this->conf = $conf;
	}

	/**
	 * Fetch supplier proposal with lines (avoiding getEntity() issue)
	 *
	 * @param int $id Supplier proposal ID
	 * @param int $socId Third-party ID (for security check)
	 * @return SupplierProposal|false Object if found, false otherwise
	 */
	public function fetchProposalWithLines(int $id, int $socId = 0)
	{
		if (empty($id)) {
			return false;
		}

		// Security check
		$sql = 'SELECT sp.rowid FROM ' . $this->db->prefix() . 'supplier_proposal sp';
		$sql .= ' WHERE sp.rowid = ' . intval($id);
		if (!empty($socId)) {
			$sql .= ' AND sp.fk_soc = ' . intval($socId);
		}

		$resql = $this->db->query($sql);
		if (!$resql || $this->db->num_rows($resql) == 0) {
			return false;
		}

		$object = $this->fetchMainProposalData($id);
		if (!$object) {
			return false;
		}

		$this->fetchProposalLines($object);
		$object->fetch_optionals();

		return $object;
	}

	/**
	 * Fetch main proposal data
	 *
	 * @param int $id Supplier proposal ID
	 * @return SupplierProposal|false
	 */
	private function fetchMainProposalData(int $id)
	{
		$sql = 'SELECT sp.rowid, sp.ref, sp.ref_ext, sp.ref_fourn, sp.fk_soc, sp.fk_projet, sp.datec, sp.date_valid,';
		$sql .= ' sp.date_livraison, sp.total_ht, sp.total_tva, sp.total_ttc, sp.fk_statut,';
		$sql .= ' sp.note_private, sp.note_public, sp.entity,';
		$sql .= ' sp.multicurrency_code, sp.multicurrency_tx, sp.multicurrency_total_ht,';
		$sql .= ' sp.multicurrency_total_tva, sp.multicurrency_total_ttc,';
		$sql .= ' p.ref as project_ref, p.title as project_title';
		$sql .= ' FROM ' . $this->db->prefix() . 'supplier_proposal sp';
		$sql .= ' LEFT JOIN ' . $this->db->prefix() . 'projet p ON sp.fk_projet = p.rowid';
		$sql .= ' WHERE sp.rowid = ' . intval($id);

		$resql = $this->db->query($sql);
		if (!$resql) {
			return false;
		}

		$obj = $this->db->fetch_object($resql);
		if (!$obj) {
			return false;
		}

		$object = new SupplierProposal($this->db);
		$this->populateProposalFromDbResult($object, $obj);

		return $object;
	}

	/**
	 * Populate proposal object from database result
	 *
	 * @param SupplierProposal $object
	 * @param object $obj Database row
	 * @return void
	 */
	private function populateProposalFromDbResult(SupplierProposal $object, object $obj) : void
	{
		$object->id = $obj->rowid;
		$object->ref = $obj->ref;
		$object->ref_ext = $obj->ref_ext;
		$object->ref_fourn = $obj->ref_fourn;
		$object->socid = $obj->fk_soc;
		$object->fk_project = $obj->fk_projet;
		$object->date_creation = $this->db->jdate($obj->datec);
		$object->date_validation = $this->db->jdate($obj->date_valid);
		$object->delivery_date = $this->db->jdate($obj->date_livraison);
		$object->total_ht = $obj->total_ht;
		$object->total_tva = $obj->total_tva;
		$object->total_ttc = $obj->total_ttc;
		$object->status = $obj->fk_statut;
		$object->note_private = $obj->note_private;
		$object->note_public = $obj->note_public;
		$object->entity = $obj->entity;
		$object->multicurrency_code = $obj->multicurrency_code;
		$object->multicurrency_tx = $obj->multicurrency_tx;
		$object->multicurrency_total_ht = $obj->multicurrency_total_ht;
		$object->multicurrency_total_tva = $obj->multicurrency_total_tva;
		$object->multicurrency_total_ttc = $obj->multicurrency_total_ttc;
		$object->project_ref = $obj->project_ref;
		$object->project_title = $obj->project_title;
		$object->project = !empty($obj->project_ref) ? $obj->project_ref . (!empty($obj->project_title) ? ' - ' . $obj->project_title : '') : '';
	}

	/**
	 * Fetch proposal lines
	 *
	 * @param SupplierProposal $object
	 * @return void
	 */
	private function fetchProposalLines(SupplierProposal $object) : void
	{
		$sqlLines = 'SELECT spd.rowid, spd.fk_supplier_proposal, spd.fk_parent_line, spd.description, spd.qty,';
		$sqlLines .= ' spd.subprice, spd.tva_tx, spd.localtax1_tx, spd.localtax2_tx,';
		$sqlLines .= ' spd.total_ht, spd.total_tva, spd.total_localtax1, spd.total_localtax2, spd.total_ttc,';
		$sqlLines .= ' spd.fk_product, spd.product_type, spd.label, spd.fk_unit, spd.rang, spd.special_code,';
		$sqlLines .= ' spd.multicurrency_subprice, spd.multicurrency_total_ht, spd.multicurrency_total_tva, spd.multicurrency_total_ttc,';
		$sqlLines .= ' p.ref as product_ref, p.label as product_label, pfp.ref_fourn as ref_supplier';
		$sqlLines .= ' FROM ' . $this->db->prefix() . 'supplier_proposaldet spd';
		$sqlLines .= ' LEFT JOIN ' . $this->db->prefix() . 'product p ON spd.fk_product = p.rowid';
		$sqlLines .= ' LEFT JOIN ' . $this->db->prefix() . 'product_fournisseur_price pfp ON pfp.fk_product = spd.fk_product';
		$sqlLines .= ' AND pfp.fk_soc = ' . intval($object->socid);
		$sqlLines .= ' WHERE spd.fk_supplier_proposal = ' . intval($object->id);
		$sqlLines .= ' ORDER BY spd.rang ASC';

		$resqlLines = $this->db->query($sqlLines);
		if (!$resqlLines) {
			$object->lines = array();
			return;
		}

		$object->lines = array();
		$numLines = $this->db->num_rows($resqlLines);
		$i = 0;
		while ($i < $numLines) {
			$objLine = $this->db->fetch_object($resqlLines);
			$line = $this->createLineFromDbResult($objLine);
			$object->lines[] = $line;
			$i++;
		}
		$this->db->free($resqlLines);
	}

	/**
	 * Create line object from database result
	 *
	 * @param object $objLine Database row
	 * @return SupplierProposalLine
	 */
	private function createLineFromDbResult(object $objLine) : SupplierProposalLine
	{
		$line = new SupplierProposalLine($this->db);
		$line->id = $objLine->rowid;
		$line->fk_supplier_proposal = $objLine->fk_supplier_proposal;
		$line->fk_parent_line = $objLine->fk_parent_line;
		$line->desc = $objLine->description;
		$line->qty = $objLine->qty;
		$line->subprice = $objLine->subprice;
		$line->tva_tx = $objLine->tva_tx;
		$line->localtax1_tx = $objLine->localtax1_tx;
		$line->localtax2_tx = $objLine->localtax2_tx;
		$line->total_ht = $objLine->total_ht;
		$line->total_tva = $objLine->total_tva;
		$line->total_localtax1 = $objLine->total_localtax1;
		$line->total_localtax2 = $objLine->total_localtax2;
		$line->total_ttc = $objLine->total_ttc;
		$line->fk_product = $objLine->fk_product;
		$line->product_type = $objLine->product_type;
		$line->ref_supplier = $objLine->ref_supplier;
		$line->product_ref = $objLine->product_ref;
		// Use line label if exists, otherwise use product label
		$line->label = !empty($objLine->label) ? $objLine->label : (!empty($objLine->product_label) ? $objLine->product_label : '');
		$line->fk_unit = $objLine->fk_unit;
		$line->rang = $objLine->rang;
		$line->special_code = $objLine->special_code;
		$line->multicurrency_subprice = $objLine->multicurrency_subprice;
		$line->multicurrency_total_ht = $objLine->multicurrency_total_ht;
		$line->multicurrency_total_tva = $objLine->multicurrency_total_tva;
		$line->multicurrency_total_ttc = $objLine->multicurrency_total_ttc;

		return $line;
	}

	/**
	 * Fetch actions/comments for a proposal
	 *
	 * @param SupplierProposal $object
	 * @return array Array of ActionComm objects
	 */
	public function fetchProposalActions(SupplierProposal $object) : array
	{
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

		$TAction = array();

		$sql = "SELECT id, fk_user_author, fk_user_action, datec, datep, label, note, code, percent, fk_element, entity";
		$sql .= ' FROM ' . $this->db->prefix() . 'actioncomm';
		$sql .= ' WHERE fk_element = ' . intval($object->id);
		$sql .= ' AND elementtype = "' . $this->db->escape($object->element) . '"';
		// Only keep discussion-style actions to avoid showing automatic system events
		$sql .= ' AND code = "' . $this->db->escape('AC_OTH') . '"';
		$sql .= ' ORDER BY datep ASC';

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$action = new ActionComm($this->db);
				// Populate object properties directly from SQL result instead of doing another fetch
				$action->id = $obj->id;
				$action->authorid = $obj->fk_user_author;
				$action->userownerid = $obj->fk_user_action;
				$action->usermodid = $obj->fk_user_action;
				$action->datec = $this->db->jdate($obj->datec);
				$action->datep = $this->db->jdate($obj->datep);
				$action->label = $obj->label;
				$action->note_private = $obj->note;
				$action->code = $obj->code;
				$action->percentage = $obj->percent;
				$action->elementid = $obj->fk_element;
				$action->entity = $obj->entity;

				$TAction[] = $action;
			}
			$this->db->free($resql);
		}

		return $TAction;
	}

	/**
	 * Get ECM file list for proposal
	 *
	 * @param SupplierProposal $object
	 * @param bool $publicOnly Only public files
	 * @return array
	 */
	public function getProposalDocuments(SupplierProposal $object, bool $publicOnly = true) : array
	{
		$documents = array();
		$elementType = 'supplier_proposal';
		$refDir = dol_sanitizeFileName($object->ref);

		$sql = 'SELECT ecm.rowid as id, ecm.src_object_type, ecm.src_object_id, ecm.filepath, ecm.filename, ecm.share, ecm.gen_or_uploaded';
		$sql .= ' FROM ' . $this->db->prefix() . 'ecm_files ecm';
		$sql .= ' WHERE ((ecm.src_object_type = \'' . $this->db->escape($elementType) . '\' ';
		$sql .= ' AND  ecm.src_object_id = ' . intval($object->id) . ') ';
		$sql .= ' OR  ecm.filepath = \'' . $this->db->escape($elementType . '/' . $refDir) . '\' )';

		if ($publicOnly) {
			$sql .= ' AND ecm.share IS NOT NULL ';
		}

		$sql .= ' AND ecm.entity = ' . intval($this->conf->entity) . ' ';
		$sql .= ' ORDER BY ecm.position ASC';

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ($obj = $this->db->fetch_object($resql)) {
					$documents[$obj->id] = $obj;
				}
			}
		}

		return $documents;
	}

	/**
	 * Get SQL query to fetch supplier proposals for external access
	 *
	 * @param int $socId Third-party ID
	 * @return string SQL query string
	 */
	public function getSqlForExternalList(int $socId) : string
	{
		$sql = 'SELECT sp.rowid, sp.ref, sp.ref_ext, sp.ref_fourn, sp.datec, sp.total_ht, sp.total_tva, sp.fk_statut, sp.entity, sp.date_livraison ';
		$sql .= ' FROM `'.$this->db->prefix().'supplier_proposal` sp';
		$sql .= ' WHERE sp.fk_soc = '.intval($socId);
		$sql .= ' AND sp.fk_statut IN ('.SupplierProposal::STATUS_VALIDATED.', '.SupplierProposal::STATUS_SIGNED.', '.SupplierProposal::STATUS_CLOSE.', '.SupplierProposal::STATUS_NOTSIGNED.')';
		$sql .= ' ORDER BY sp.datec DESC';

		return $sql;
	}

	/**
	 * Fetch extrafields for a supplier proposal
	 *
	 * @param int $proposalId Supplier proposal ID
	 * @param array $extraFieldNames Array of extra field names to fetch (e.g., ['EXTRAFIELD_fieldname'])
	 * @return array Array of extrafields in array_options format ['options_fieldname' => value]
	 */
	public function fetchExtrafields(int $proposalId, array $extraFieldNames = array()) : array
	{
		$extrafieldsData = array();

		// Determine which columns to select
		$extrafieldColumns = array();
		foreach ($extraFieldNames as $field) {
			if (strpos($field, 'EXTRAFIELD_') !== false) {
				$extrafieldName = strtr($field, array('EXTRAFIELD_' => ''));
				$extrafieldColumns[] = $this->db->escape($extrafieldName);
			}
		}

		// If no extrafields to fetch, return empty array
		if (empty($extrafieldColumns)) {
			return $extrafieldsData;
		}

		// Build SQL with only necessary columns
		$sql = 'SELECT rowid, tms, fk_object';
		$sql .= ', '.implode(', ', $extrafieldColumns);
		$sql .= ' FROM '.$this->db->prefix().'supplier_proposal_extrafields';
		$sql .= ' WHERE fk_object = '.intval($proposalId);

		$resql = $this->db->query($sql);

		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				// Map extrafield columns to array_options format
				foreach ($obj as $key => $value) {
					if ($key !== 'rowid' && $key !== 'tms' && $key !== 'fk_object' && $key !== 'import_key') {
						$extrafieldsData['options_'.$key] = $value;
					}
				}
			}
			$this->db->free($resql);
		}

		return $extrafieldsData;
	}

	/**
	 * Load supplier proposals linked to a business object, including reverse links and commande->propal fallback.
	 *
	 * @param CommonObject $object Business object (propal or commande)
	 * @param DoliDB       $db     Database handler
	 * @return SupplierProposal[]
	 */
	public static function loadLinkedSupplierProposals(CommonObject $object, DoliDB $db)
	{
		$linkedSupplierProposals = array();

		if (!method_exists($object, 'fetchObjectLinked')) {
			return $linkedSupplierProposals;
		}

		// Collect supplier proposals linked in both directions on current object
		$object->fetchObjectLinked($object->id, $object->element, '', 'supplier_proposal');
		if (!empty($object->linkedObjects['supplier_proposal'])) {
			$linkedSupplierProposals = $object->linkedObjects['supplier_proposal'];
		}

		if (method_exists($object, 'clearObjectLinkedCache')) {
			$object->clearObjectLinkedCache();
		}
		$object->fetchObjectLinked('', '', $object->id, $object->element, 'OR', 1, 'sourcetype', 1);
		if (!empty($object->linkedObjects['supplier_proposal'])) {
			$linkedSupplierProposals = $linkedSupplierProposals + $object->linkedObjects['supplier_proposal'];
		}

		$object->linkedObjects['supplier_proposal'] = $linkedSupplierProposals;

		// Fallback: if a commande has no direct links, try its origin propal (both directions)
		if ($object->element === 'commande' && empty($object->linkedObjects['supplier_proposal'])) {
			self::loadSupplierProposalsFromOriginPropal($object, $db);
		}

		return $object->linkedObjects['supplier_proposal'] ?? array();
	}

	/**
	 * Load supplier proposals from a commande origin propal (both directions).
	 *
	 * @param CommonObject $object
	 * @param DoliDB       $db
	 * @return void
	 */
	private static function loadSupplierProposalsFromOriginPropal(CommonObject $object, DoliDB $db): void
	{
		if (empty($object->origin_id) || $object->origin !== 'propal') {
			return;
		}

		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		$origin = new Propal($db);
		if ($origin->fetch($object->origin_id) <= 0) {
			return;
		}

		$origin->fetchObjectLinked($origin->id, $origin->element, '', 'supplier_proposal');
		$originLinked = $origin->linkedObjects['supplier_proposal'] ?? array();

		if (method_exists($origin, 'clearObjectLinkedCache')) {
			$origin->clearObjectLinkedCache();
		}
		$origin->fetchObjectLinked('', '', $origin->id, $origin->element, 'OR', 1, 'sourcetype', 1);
		if (!empty($origin->linkedObjects['supplier_proposal'])) {
			$originLinked = $originLinked + $origin->linkedObjects['supplier_proposal'];
		}

		if (!empty($originLinked)) {
			$object->linkedObjects['supplier_proposal'] = $originLinked;
		}
	}

	/**
	 * Preload thirdparties for a list of supplier proposals to avoid N+1 queries in views.
	 *
	 * @param SupplierProposal[] $supplierProposals
	 * @param DoliDB             $db
	 * @return SupplierProposal[]
	 */
	public static function preloadThirdparties(array $supplierProposals, DoliDB $db): array
	{
		$proposalById = array();
		$proposalIds = array();
		foreach ($supplierProposals as $proposal) {
			if (empty($proposal->id)) {
				continue;
			}
			$proposalById[(int) $proposal->id] = $proposal;
			$proposalIds[] = (int) $proposal->id;
		}

		if (empty($proposalIds)) {
			return $supplierProposals;
		}

		$sql = 'SELECT sp.rowid as spid, sp.fk_soc, s.rowid as socid, s.nom as socname';
		$sql .= ' FROM '.$db->prefix().'supplier_proposal sp';
		$sql .= ' LEFT JOIN '.$db->prefix().'societe s ON s.rowid = sp.fk_soc';
		$sql .= ' WHERE sp.rowid IN ('.implode(',', array_map('intval', $proposalIds)).')';

		$resql = $db->query($sql);
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$propId = (int) $obj->spid;
				if (!isset($proposalById[$propId])) {
					continue;
				}

				$proposal = $proposalById[$propId];
				$socId = (int) $obj->fk_soc;
				$proposal->socid = $socId;
				$proposal->fk_soc = $socId;

				if ($socId > 0 && !empty($obj->socid)) {
					$thirdparty = new Societe($db);
					$thirdparty->id = (int) $obj->socid;
					$thirdparty->name = (string) $obj->socname;
					$thirdparty->nom = $thirdparty->name;
					$proposal->thirdparty = $thirdparty;
				} elseif ($socId > 0 && method_exists($proposal, 'fetch_thirdparty')) {
					// Fallback if LEFT JOIN does not return societe (edge cases)
					$proposal->fetch_thirdparty();
				}
			}
		}

		return $supplierProposals;
	}
}
