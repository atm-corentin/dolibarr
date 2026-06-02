<?php
/* Copyright (C) 2026 ATM Consulting
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    class/SupplierPriceSync/Repository/SupplierPriceRepository.php
 * \ingroup clichaumeil
 * \brief   Data access for supplier price candidates and status toggling.
 */

declare(strict_types=1);

require_once __DIR__ . '/../SupplierPriceSyncConstants.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceCandidate.php';

/**
 * Reads supplier price candidates and toggles their activation status.
 *
 * Status has no core setter, so it is updated with a direct SQL statement.
 * Candidates are loaded in a single JOIN query (the core per-product method
 * would cause an N+1 on the whole supplier catalogue).
 */
final class SupplierPriceRepository
{
	/** @var DoliDB Database handler. */
	private DoliDB $db;

	/**
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Load every buyable supplier price line for the given supplier.
	 *
	 * @param int $thirdpartyId Supplier third party id (fk_soc).
	 * @return SupplierPriceCandidate[]
	 * @throws Exception When the SQL query fails.
	 */
	public function fetchCandidatesForSupplier(int $thirdpartyId): array
	{
		$sql = "SELECT pfp.rowid, pfp.fk_product, p.ref as product_ref, pfp.fk_soc,";
		$sql .= " pfp.ref_fourn, pfp.quantity, pfp.unitprice, pfp.status,";
		$sql .= " ef.conditionnement_unite_de_prix as order_unit_source";
		$sql .= " FROM " . $this->db->prefix() . "product_fournisseur_price as pfp";
		$sql .= " INNER JOIN " . $this->db->prefix() . "product as p ON p.rowid = pfp.fk_product";
		$sql .= " LEFT JOIN " . $this->db->prefix() . "product_fournisseur_price_extrafields as ef ON ef.fk_object = pfp.rowid";
		$sql .= " WHERE pfp.fk_soc = " . ((int) $thirdpartyId);
		$sql .= " AND p.tobuy = 1";
		$sql .= " AND pfp.entity IN (" . getEntity('productsupplierprice') . ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog('SupplierPriceRepository::fetchCandidatesForSupplier ' . $this->db->lasterror(), LOG_ERR);
			throw new Exception('Unable to load supplier price candidates');
		}

		$candidates = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$candidates[] = new SupplierPriceCandidate(
				(int) $obj->rowid,
				(int) $obj->fk_product,
				(string) $obj->product_ref,
				(int) $obj->fk_soc,
				(string) $obj->ref_fourn,
				(float) $obj->quantity,
				(float) $obj->unitprice,
				(int) $obj->status,
				(string) ($obj->order_unit_source ?? '')
			);
		}
		$this->db->free($resql);

		return $candidates;
	}

	/**
	 * Activate a supplier price line (status = 1).
	 *
	 * @param int $supplierPriceId product_fournisseur_price.rowid.
	 * @return bool True on success.
	 */
	public function activate(int $supplierPriceId): bool
	{
		return $this->setStatus($supplierPriceId, SupplierPriceSyncConstants::STATUS_ACTIVE);
	}

	/**
	 * Deactivate (close) a supplier price line (status = 0).
	 *
	 * @param int $supplierPriceId product_fournisseur_price.rowid.
	 * @return bool True on success.
	 */
	public function deactivate(int $supplierPriceId): bool
	{
		return $this->setStatus($supplierPriceId, SupplierPriceSyncConstants::STATUS_INACTIVE);
	}

	/**
	 * Update the status of a supplier price line.
	 *
	 * @param int $supplierPriceId product_fournisseur_price.rowid.
	 * @param int $status          Target status.
	 * @return bool True on success.
	 */
	private function setStatus(int $supplierPriceId, int $status): bool
	{
		$sql = "UPDATE " . $this->db->prefix() . "product_fournisseur_price";
		$sql .= " SET status = " . ((int) $status);
		$sql .= " WHERE rowid = " . ((int) $supplierPriceId);
		$sql .= " AND entity IN (" . getEntity('productsupplierprice') . ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog('SupplierPriceRepository::setStatus ' . $this->db->lasterror(), LOG_ERR);

			return false;
		}

		return true;
	}

	/**
	 * Guarantee that the extrafields row exists for a supplier price line.
	 *
	 * Explicit SELECT then INSERT (no INSERT IGNORE).
	 *
	 * @param int $supplierPriceId product_fournisseur_price.rowid.
	 * @return void
	 * @throws Exception When a SQL query fails.
	 */
	public function ensureExtrafieldsRow(int $supplierPriceId): void
	{
		$sql = "SELECT rowid FROM " . $this->db->prefix() . "product_fournisseur_price_extrafields";
		$sql .= " WHERE fk_object = " . ((int) $supplierPriceId);

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog('SupplierPriceRepository::ensureExtrafieldsRow select ' . $this->db->lasterror(), LOG_ERR);
			throw new Exception('Unable to check supplier price extrafields row');
		}
		$exists = ($this->db->num_rows($resql) > 0);
		$this->db->free($resql);

		if ($exists) {
			return;
		}

		$sqlInsert = "INSERT INTO " . $this->db->prefix() . "product_fournisseur_price_extrafields (fk_object)";
		$sqlInsert .= " VALUES (" . ((int) $supplierPriceId) . ")";

		$resqlInsert = $this->db->query($sqlInsert);
		if (!$resqlInsert) {
			dol_syslog('SupplierPriceRepository::ensureExtrafieldsRow insert ' . $this->db->lasterror(), LOG_ERR);
			throw new Exception('Unable to create supplier price extrafields row');
		}
	}
}
