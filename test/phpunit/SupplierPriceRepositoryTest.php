<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * @file    test/phpunit/SupplierPriceRepositoryTest.php
 * @brief   Integration tests for SupplierPriceRepository (ACHT-2-ANTALIS).
 *
 * Real database, wrapped in the CommonClassTest begin()/rollback() transaction.
 *
 * Run: phpunit htdocs/custom/clichaumeil/test/phpunit/SupplierPriceRepositoryTest.php
 *
 * @backupGlobals          disabled
 * @backupStaticAttributes enabled
 */

declare(strict_types=1);

global $conf, $user, $langs, $db;

require_once dirname(__FILE__) . '/../../../../master.inc.php';
require_once dirname(__FILE__) . '/../../../../../test/phpunit/CommonClassTest.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Repository/SupplierPriceRepository.php';

/**
 * Tests for SupplierPriceRepository.
 */
class SupplierPriceRepositoryTest extends CommonClassTest
{
	/**
	 * Create the full fetch/activate/deactivate/ensure flow on a real DB.
	 *
	 * @return void
	 */
	public function testFetchActivateDeactivateEnsure(): void
	{
		global $db, $user;

		$supplier = new Societe($db);
		$supplier->name = 'TEST ANTALIS SUPPLIER';
		$supplier->fournisseur = 1;
		$supplier->code_fournisseur = 'TESTSUP' . uniqid();
		$supplierId = $supplier->create($user);
		$this->assertGreaterThan(0, $supplierId);

		$product = new Product($db);
		$product->ref = 'TEST_ACHT2_' . $supplierId;
		$product->label = 'Test product ACHT2';
		$product->type = Product::TYPE_PRODUCT;
		$product->status_buy = 1;
		$productId = $product->create($user);
		$this->assertGreaterThan(0, $productId);

		$supplierRef = 'TESTREF_' . $productId;
		$addResult = $product->add_fournisseur($user, $supplierId, $supplierRef, 100);
		$this->assertGreaterThan(0, $addResult);

		$repository = new SupplierPriceRepository($db);
		$candidates = $repository->fetchCandidatesForSupplier((int) $supplierId);
		$this->assertNotEmpty($candidates);

		$target = null;
		foreach ($candidates as $candidate) {
			if ($candidate->supplierRef === $supplierRef) {
				$target = $candidate;
				break;
			}
		}
		$this->assertNotNull($target);
		$this->assertSame($productId, $target->productId);
		$this->assertSame(100.0, $target->quantity);

		$this->assertTrue($repository->deactivate($target->supplierPriceId));
		$this->assertSame(0, $this->readStatus($target->supplierPriceId));

		$this->assertTrue($repository->activate($target->supplierPriceId));
		$this->assertSame(1, $this->readStatus($target->supplierPriceId));

		$repository->ensureExtrafieldsRow($target->supplierPriceId);
		$repository->ensureExtrafieldsRow($target->supplierPriceId);
		$this->assertSame(1, $this->countExtrafieldsRows($target->supplierPriceId));
	}

	/**
	 * Read the raw status of a supplier price line.
	 *
	 * @param int $supplierPriceId product_fournisseur_price.rowid.
	 * @return int
	 */
	private function readStatus(int $supplierPriceId): int
	{
		global $db;
		$sql = "SELECT status FROM " . $db->prefix() . "product_fournisseur_price WHERE rowid = " . ((int) $supplierPriceId);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);

		return (int) $obj->status;
	}

	/**
	 * Count extrafields rows for a supplier price line.
	 *
	 * @param int $supplierPriceId product_fournisseur_price.rowid.
	 * @return int
	 */
	private function countExtrafieldsRows(int $supplierPriceId): int
	{
		global $db;
		$sql = "SELECT COUNT(*) as nb FROM " . $db->prefix() . "product_fournisseur_price_extrafields WHERE fk_object = " . ((int) $supplierPriceId);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);

		return (int) $obj->nb;
	}
}
