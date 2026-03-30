<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once __DIR__ . '/CliChaumeilProductCost.class.php';

/**
 * Import-oriented orchestration service for CliChaumeil product cost updates.
 */
class CliChaumeilProductCostImportService
{
	/**
	 * Official import columns for the cost breakdown source fields.
	 *
	 * @var array<string,string>
	 */
	public const IMPORT_FIELD_MAP = array(
		'extra.clichaumeil_pa_support' => 'clichaumeil_pa_support',
		'extra.clichaumeil_pa_sav' => 'clichaumeil_pa_sav',
		'extra.clichaumeil_pa_machine' => 'clichaumeil_pa_machine',
		'extra.clichaumeil_pa_encre' => 'clichaumeil_pa_encre',
		'extra.clichaumeil_pa_mo' => 'clichaumeil_pa_mo',
		'extra.clichaumeil_conditionnement_percent' => 'clichaumeil_conditionnement_percent',
		'extra.clichaumeil_transport_percent' => 'clichaumeil_transport_percent',
		'extra.clichaumeil_fg_percent' => 'clichaumeil_fg_percent',
	);

	/**
	 * @var DoliDB
	 */
	private $db;

	/**
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Apply import payload on product extrafields and synchronize the final cost.
	 *
	 * @param array<string,mixed> $values Import values.
	 * @param User                $user   Acting user.
	 * @return int
	 */
	public function syncImportedProductCost(array $values, User $user): int
	{
		$product = $this->loadProductFromImportData($values);
		if (!$product || !CliChaumeilProductCostCalculator::isSupportedProduct($product)) {
			return 0;
		}

		$this->db->begin();

		$result = $this->applyImportedCostFields($product, $values, $user);
		if ($result < 0) {
			$this->db->rollback();
			return -1;
		}

		$result = CliChaumeilProductCostCalculator::calculateAndUpdateProductCostPriceFromExtrafields($product, $user);
		if ($result < 0) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();

		return 0;
	}

	/**
	 * Load a product from import identifiers and fetch its extrafields.
	 *
	 * @param array<string,mixed> $values Import values.
	 * @return Product|null
	 */
	private function loadProductFromImportData(array $values): ?Product
	{
		$id = !empty($values['p.rowid']) ? (int) $values['p.rowid'] : 0;
		$product = new Product($this->db);

		if ($id > 0) {
			$fetchResult = $product->fetch($id);
			if ($fetchResult <= 0) {
				dol_syslog(__METHOD__ . ' failed to fetch product #' . $id, LOG_ERR);
				return null;
			}
		} elseif (!empty($values['p.ref'])) {
			$fetchResult = $product->fetch(0, (string) $values['p.ref']);
			if ($fetchResult <= 0) {
				dol_syslog(__METHOD__ . ' failed to fetch product ref ' . $values['p.ref'], LOG_ERR);
				return null;
			}
		} else {
			dol_syslog(__METHOD__ . ' missing product identifier in import payload', LOG_ERR);
			return null;
		}

		$product->fetch_optionals($product->id);

		return $product;
	}

	/**
	 * Persist imported official columns on the product before recalculation.
	 *
	 * Absent optional columns are ignored. Present empty optional columns are
	 * explicitly persisted as empty values so the calculator can normalize them.
	 *
	 * @param Product             $product Product object.
	 * @param array<string,mixed> $values  Import values.
	 * @param User                $user    Current user.
	 * @return int
	 */
	private function applyImportedCostFields(Product $product, array $values, User $user): int
	{
		foreach (self::IMPORT_FIELD_MAP as $importKey => $fieldName) {
			if (!array_key_exists($importKey, $values)) {
				continue;
			}

			$product->array_options[CliChaumeilProductCostCalculator::EXTRA_PREFIX . $fieldName] = $values[$importKey];
			$result = $product->updateExtraField($fieldName, 'CLICHAUMEIL_PRODUCT_COST', $user);
			if ($result < 0) {
				dol_syslog(__METHOD__ . ' failed to update extrafield ' . $fieldName . ' for product #' . (int) $product->id, LOG_ERR);
				return -1;
			}
		}

		return 0;
	}
}
