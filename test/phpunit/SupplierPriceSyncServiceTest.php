<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * @file    test/phpunit/SupplierPriceSyncServiceTest.php
 * @brief   Integration tests for SupplierPriceSyncService (ACHT-2-ANTALIS).
 *
 * Real database (begin/rollback), with a FakeConnector returning canned results.
 *
 * Run: phpunit htdocs/custom/clichaumeil/test/phpunit/SupplierPriceSyncServiceTest.php
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
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Contract/SupplierConfigInterface.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Contract/SupplierPriceConnectorInterface.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierPriceFetchResult.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierPriceLineResult.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Repository/SupplierPriceRepository.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Service/SupplierPriceSyncService.php';

/**
 * Connector test double returning canned results.
 */
class FakeSupplierPriceConnector implements SupplierPriceConnectorInterface
{
	/** @var SupplierPriceFetchResult Canned result. */
	private SupplierPriceFetchResult $canned;

	/** @var int Number of candidates received on the last call. */
	public int $receivedCount = 0;

	/**
	 * @param SupplierPriceFetchResult $canned Canned fetch result.
	 */
	public function __construct(SupplierPriceFetchResult $canned)
	{
		$this->canned = $canned;
	}

	/**
	 * Return the supplier code.
	 *
	 * @return string
	 */
	public function getCode(): string
	{
		return 'ANTALIS';
	}

	/**
	 * Return the batch size.
	 *
	 * @return int
	 */
	public function getRecommendedBatchSize(): int
	{
		return 50;
	}

	/**
	 * No tier discovery.
	 *
	 * @return bool
	 */
	public function supportsTierDiscovery(): bool
	{
		return false;
	}

	/**
	 * Return the canned result.
	 *
	 * @param SupplierPriceCandidate[] $candidates Candidates received from the service.
	 * @return SupplierPriceFetchResult
	 */
	public function fetchPrices(array $candidates): SupplierPriceFetchResult
	{
		$this->receivedCount = count($candidates);

		return $this->canned;
	}
}

/**
 * Minimal supplier configuration for tests.
 */
class FakeSupplierConfig implements SupplierConfigInterface
{
	/** @var int Supplier third party id. */
	private int $thirdpartyId;

	/**
	 * @param int $thirdpartyId Supplier third party id.
	 */
	public function __construct(int $thirdpartyId)
	{
		$this->thirdpartyId = $thirdpartyId;
	}

	/**
	 * Return the supplier code.
	 *
	 * @return string
	 */
	public function getCode(): string
	{
		return 'ANTALIS';
	}

	/**
	 * Return the supplier label.
	 *
	 * @return string
	 */
	public function getLabel(): string
	{
		return 'ANTALIS';
	}

	/**
	 * Return the supplier third party id.
	 *
	 * @return int
	 */
	public function getSupplierThirdpartyId(): int
	{
		return $this->thirdpartyId;
	}
}

/**
 * Tests for SupplierPriceSyncService.
 */
class SupplierPriceSyncServiceTest extends CommonClassTest
{
	/** @var int Supplier id created for the test. */
	private int $supplierId = 0;

	/** @var int Product id created for the test. */
	private int $productId = 0;

	/** @var int Supplier price line id created for the test. */
	private int $supplierPriceId = 0;

	/** @var string Supplier reference used. */
	private string $supplierRef = '';

	/** @var float Quantity used. */
	private float $quantity = 100.0;

	/**
	 * Create a supplier price line with a known initial unit price.
	 *
	 * @param float $initialUnitPrice Initial unit price to store.
	 * @return void
	 */
	private function createLine(float $initialUnitPrice): void
	{
		global $db, $user;

		$supplier = new Societe($db);
		$supplier->name = 'TEST SVC SUPPLIER';
		$supplier->fournisseur = 1;
		$supplier->code_fournisseur = 'TESTSVC' . uniqid();
		$this->supplierId = (int) $supplier->create($user);

		$product = new Product($db);
		$product->ref = 'TEST_SVC_' . $this->supplierId;
		$product->label = 'Test product SVC';
		$product->type = Product::TYPE_PRODUCT;
		$product->status_buy = 1;
		$this->productId = (int) $product->create($user);

		$this->supplierRef = 'SVCREF_' . $this->productId;
		$product->add_fournisseur($user, $this->supplierId, $this->supplierRef, $this->quantity);

		$repository = new SupplierPriceRepository($db);
		$candidates = $repository->fetchCandidatesForSupplier($this->supplierId);
		$this->supplierPriceId = (int) $candidates[0]->supplierPriceId;

		$productFournisseur = new ProductFournisseur($db);
		$productFournisseur->fetch_product_fournisseur_price($this->supplierPriceId);
		$productFournisseur->id = $this->productId;
		$productFournisseur->update_buyprice(
			$this->quantity,
			$initialUnitPrice * $this->quantity,
			$user,
			'HT',
			$this->supplierId,
			0,
			$this->supplierRef,
			20.0
		);
	}

	/**
	 * Reload the candidate after creation.
	 *
	 * @return SupplierPriceCandidate
	 */
	private function reloadCandidate(): SupplierPriceCandidate
	{
		global $db;
		$repository = new SupplierPriceRepository($db);
		$candidates = $repository->fetchCandidatesForSupplier($this->supplierId);

		return $candidates[0];
	}

	/**
	 * Read the raw status/unitprice of the line.
	 *
	 * @return object
	 */
	private function readLine(): object
	{
		global $db;
		$sql = "SELECT status, unitprice FROM " . $db->prefix() . "product_fournisseur_price WHERE rowid = " . ((int) $this->supplierPriceId);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);

		return $obj;
	}

	/**
	 * A different price triggers an update and rewrites the stored unit price.
	 *
	 * @return void
	 */
	public function testDifferentPriceUpdates(): void
	{
		global $db, $user;
		$this->createLine(0.048);
		$candidate = $this->reloadCandidate();

		$fetch = new SupplierPriceFetchResult(
			array(SupplierPriceLineResult::success($candidate->supplierRef, $candidate->quantity, 0.0321)),
			array(),
			false
		);
		$service = new SupplierPriceSyncService($db);
		$report = $service->run(new FakeSupplierConfig($this->supplierId), new FakeSupplierPriceConnector($fetch), $user);

		$this->assertSame(1, $report->updated);
		$this->assertSame(0, $report->unchanged);
		$this->assertEqualsWithDelta(0.0321, (float) $this->readLine()->unitprice, 0.0001);
	}

	/**
	 * An identical price leaves the line unchanged.
	 *
	 * @return void
	 */
	public function testIdenticalPriceUnchanged(): void
	{
		global $db, $user;
		$this->createLine(0.0321);
		$candidate = $this->reloadCandidate();

		$fetch = new SupplierPriceFetchResult(
			array(SupplierPriceLineResult::success($candidate->supplierRef, $candidate->quantity, 0.0321)),
			array(),
			false
		);
		$service = new SupplierPriceSyncService($db);
		$report = $service->run(new FakeSupplierConfig($this->supplierId), new FakeSupplierPriceConnector($fetch), $user);

		$this->assertSame(0, $report->updated);
		$this->assertSame(1, $report->unchanged);
	}

	/**
	 * A close result deactivates an active line.
	 *
	 * @return void
	 */
	public function testCloseDeactivatesActiveLine(): void
	{
		global $db, $user;
		$this->createLine(0.048);
		$candidate = $this->reloadCandidate();

		$fetch = new SupplierPriceFetchResult(
			array(SupplierPriceLineResult::close($candidate->supplierRef, $candidate->quantity)),
			array(),
			false
		);
		$service = new SupplierPriceSyncService($db);
		$report = $service->run(new FakeSupplierConfig($this->supplierId), new FakeSupplierPriceConnector($fetch), $user);

		$this->assertSame(1, $report->closed);
		$this->assertSame(0, (int) $this->readLine()->status);
	}

	/**
	 * A success result reactivates a previously closed line.
	 *
	 * @return void
	 */
	public function testSuccessReactivatesInactiveLine(): void
	{
		global $db, $user;
		$this->createLine(0.0321);
		$repository = new SupplierPriceRepository($db);
		$repository->deactivate($this->supplierPriceId);
		$candidate = $this->reloadCandidate();
		$this->assertSame(0, $candidate->currentStatus);

		$fetch = new SupplierPriceFetchResult(
			array(SupplierPriceLineResult::success($candidate->supplierRef, $candidate->quantity, 0.0321)),
			array(),
			false
		);
		$service = new SupplierPriceSyncService($db);
		$report = $service->run(new FakeSupplierConfig($this->supplierId), new FakeSupplierPriceConnector($fetch), $user);

		$this->assertSame(1, $report->reactivated);
		$this->assertSame(1, (int) $this->readLine()->status);
	}

	/**
	 * A fatal error stops the run and leaves the line untouched.
	 *
	 * @return void
	 */
	public function testFatalErrorStopsRun(): void
	{
		global $db, $user;
		$this->createLine(0.048);

		$issue = new SupplierPriceSyncIssue(
			SupplierPriceSyncIssue::SEVERITY_ERROR,
			SupplierPriceSyncConstants::ISSUE_API_UNAVAILABLE,
			'down'
		);
		$fetch = new SupplierPriceFetchResult(array(), array($issue), true);
		$service = new SupplierPriceSyncService($db);
		$report = $service->run(new FakeSupplierConfig($this->supplierId), new FakeSupplierPriceConnector($fetch), $user);

		$this->assertTrue($report->hasFailures());
		$this->assertSame(0, $report->updated);
		$this->assertEqualsWithDelta(0.048, (float) $this->readLine()->unitprice, 0.0001);
	}
}
