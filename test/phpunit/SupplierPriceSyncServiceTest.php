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
 * @brief   Integration tests for SupplierPriceSyncService (ACHT-2-ANTALIS, grid pivot).
 *
 * Real database (begin/rollback), with a FakeConnector returning canned grids.
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
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierProductRequest.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierPriceTier.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierProductPriceGrid.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierPriceGridFetchResult.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Repository/SupplierPriceRepository.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Service/SupplierPriceSyncService.php';

/**
 * Connector test double returning a canned grid fetch result.
 */
class FakeGridConnector implements SupplierPriceConnectorInterface
{
	/** @var SupplierPriceGridFetchResult Canned result. */
	private SupplierPriceGridFetchResult $canned;

	/** @var bool Tier discovery capability. */
	private bool $discovery;

	/** @var int Number of products received on the last call. */
	public int $receivedCount = 0;

	/**
	 * @param SupplierPriceGridFetchResult $canned    Canned fetch result.
	 * @param bool                         $discovery Tier discovery capability.
	 */
	public function __construct(SupplierPriceGridFetchResult $canned, bool $discovery = true)
	{
		$this->canned = $canned;
		$this->discovery = $discovery;
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
	 * Return the tier discovery capability.
	 *
	 * @return bool
	 */
	public function supportsTierDiscovery(): bool
	{
		return $this->discovery;
	}

	/**
	 * Return the canned grid result.
	 *
	 * @param SupplierProductRequest[] $products Products received from the service.
	 * @return SupplierPriceGridFetchResult
	 */
	public function fetchPriceGrids(array $products): SupplierPriceGridFetchResult
	{
		$this->receivedCount = count($products);

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
	 * @param float  $initialUnitPrice Initial unit price to store.
	 * @param string $packagingUnit    Optional packaging unit label (extrafield).
	 * @return void
	 */
	private function createLine(float $initialUnitPrice, string $packagingUnit = ''): void
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

		if ($packagingUnit !== '') {
			$repository->setPackagingUnit($this->supplierPriceId, $packagingUnit);
		}
	}

	/**
	 * Build a found grid for the created supplier reference.
	 *
	 * @param SupplierPriceTier[] $tiers Tiers.
	 * @return SupplierPriceGridFetchResult
	 */
	private function foundGrid(array $tiers): SupplierPriceGridFetchResult
	{
		return new SupplierPriceGridFetchResult(
			array(SupplierProductPriceGrid::found($this->supplierRef, $tiers)),
			array(),
			false
		);
	}

	/**
	 * Read the raw status/unitprice of the original line.
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
	 * Count the supplier price lines of the test supplier.
	 *
	 * @return int
	 */
	private function countLines(): int
	{
		global $db;
		$sql = "SELECT COUNT(*) as nb FROM " . $db->prefix() . "product_fournisseur_price WHERE fk_soc = " . ((int) $this->supplierId);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);

		return (int) $obj->nb;
	}

	/**
	 * Run the service against a canned fetch result.
	 *
	 * @param SupplierPriceGridFetchResult $fetch     Canned result.
	 * @param bool                         $discovery Discovery capability.
	 * @param bool                         $dryRun    Dry-run flag.
	 * @return SupplierPriceSyncReport
	 */
	private function runService(SupplierPriceGridFetchResult $fetch, bool $discovery = true, bool $dryRun = false): SupplierPriceSyncReport
	{
		global $db, $user;
		$service = new SupplierPriceSyncService($db);

		return $service->run(new FakeSupplierConfig($this->supplierId), new FakeGridConnector($fetch, $discovery), $user, $dryRun);
	}

	/**
	 * Add a second supplier price line (same product/ref, different quantity).
	 *
	 * @param float $quantity Quantity of the extra line.
	 * @return void
	 */
	private function addLine(float $quantity): void
	{
		global $db, $user;
		$productFournisseur = new ProductFournisseur($db);
		$productFournisseur->id = $this->productId;
		$productFournisseur->add_fournisseur($user, $this->supplierId, $this->supplierRef, $quantity);
		$newId = (int) $productFournisseur->product_fourn_price_id;
		$productFournisseur->fetch_product_fournisseur_price($newId);
		$productFournisseur->id = $this->productId;
		$productFournisseur->update_buyprice($quantity, 0.05 * $quantity, $user, 'HT', $this->supplierId, 0, $this->supplierRef, 20.0);
	}

	/**
	 * A different price triggers an update and rewrites the stored unit price.
	 *
	 * @return void
	 */
	public function testDifferentPriceUpdates(): void
	{
		$this->createLine(0.048);

		$report = $this->runService($this->foundGrid(array(
			new SupplierPriceTier($this->quantity, '', 0.0321),
		)));

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
		$this->createLine(0.0321);

		$report = $this->runService($this->foundGrid(array(
			new SupplierPriceTier($this->quantity, '', 0.0321),
		)));

		$this->assertSame(0, $report->updated);
		$this->assertSame(1, $report->unchanged);
	}

	/**
	 * An absent grid deactivates the active lines.
	 *
	 * @return void
	 */
	public function testAbsentGridClosesLines(): void
	{
		$this->createLine(0.048);

		$fetch = new SupplierPriceGridFetchResult(
			array(SupplierProductPriceGrid::absent($this->supplierRef)),
			array(),
			false
		);
		$report = $this->runService($fetch);

		$this->assertSame(1, $report->closed);
		$this->assertSame(0, (int) $this->readLine()->status);
	}

	/**
	 * A found grid reactivates a previously closed matching line.
	 *
	 * @return void
	 */
	public function testFoundGridReactivatesInactiveLine(): void
	{
		global $db;
		$this->createLine(0.0321);
		(new SupplierPriceRepository($db))->deactivate($this->supplierPriceId);

		$report = $this->runService($this->foundGrid(array(
			new SupplierPriceTier($this->quantity, '', 0.0321),
		)));

		$this->assertSame(1, $report->reactivated);
		$this->assertSame(1, (int) $this->readLine()->status);
	}

	/**
	 * A new tier is created when discovery is enabled; the existing line is kept.
	 *
	 * @return void
	 */
	public function testNewTierCreated(): void
	{
		$this->createLine(0.0321);
		$before = $this->countLines();

		$report = $this->runService($this->foundGrid(array(
			new SupplierPriceTier($this->quantity, '', 0.0321),
			new SupplierPriceTier(500.0, '', 0.028),
		)));

		$this->assertSame(1, $report->created);
		$this->assertSame(1, $report->unchanged);
		$this->assertSame($before + 1, $this->countLines());
	}

	/**
	 * A tier absent from an authoritative grid closes the existing line.
	 *
	 * @return void
	 */
	public function testTierAbsentFromGridClosesLine(): void
	{
		$this->createLine(0.0321);

		// Grid returns only a different quantity: the qty=100 line vanished.
		$report = $this->runService($this->foundGrid(array(
			new SupplierPriceTier(500.0, '', 0.028),
		)));

		$this->assertSame(1, $report->closed);
		$this->assertSame(1, $report->created);
		$this->assertSame(0, (int) $this->readLine()->status);
	}

	/**
	 * With a non-authoritative connector, missing tiers are neither created nor closed.
	 *
	 * @return void
	 */
	public function testNoCreationNorClosureWhenNotDiscovery(): void
	{
		$this->createLine(0.0321);
		$before = $this->countLines();

		$report = $this->runService($this->foundGrid(array(
			new SupplierPriceTier(500.0, '', 0.028),
		)), false);

		$this->assertSame(0, $report->created);
		$this->assertSame(0, $report->closed);
		$this->assertSame($before, $this->countLines());
		$this->assertSame(1, (int) $this->readLine()->status);
	}

	/**
	 * A fatal error stops the run and leaves the line untouched.
	 *
	 * @return void
	 */
	public function testFatalErrorStopsRun(): void
	{
		$this->createLine(0.048);

		$issue = new SupplierPriceSyncIssue(
			SupplierPriceSyncIssue::SEVERITY_ERROR,
			SupplierPriceSyncConstants::ISSUE_API_UNAVAILABLE,
			'down'
		);
		$fetch = new SupplierPriceGridFetchResult(array(), array($issue), true);
		$report = $this->runService($fetch);

		$this->assertTrue($report->hasFailures());
		$this->assertSame(0, $report->updated);
		$this->assertEqualsWithDelta(0.048, (float) $this->readLine()->unitprice, 0.0001);
	}

	/**
	 * Dry-run reports the intended changes but writes nothing to the database.
	 *
	 * @return void
	 */
	public function testDryRunWritesNothing(): void
	{
		$this->createLine(0.048);

		$report = $this->runService($this->foundGrid(array(
			new SupplierPriceTier($this->quantity, '', 0.0321),
		)), true, true);

		$this->assertSame(1, $report->updated);
		// The stored price is unchanged: nothing was written.
		$this->assertEqualsWithDelta(0.048, (float) $this->readLine()->unitprice, 0.0001);
	}

	/**
	 * The closure guard caps the number of lines closed in a single run (default 50%).
	 *
	 * @return void
	 */
	public function testClosureGuardCapsClosures(): void
	{
		$this->createLine(0.0321);
		$this->addLine(200.0);

		// Grid returns only a quantity absent from Dolibarr: both existing lines (100, 200)
		// are closure candidates. scanned=2, ratio 50% => maxClosures=1.
		$report = $this->runService($this->foundGrid(array(
			new SupplierPriceTier(500.0, '', 0.028),
		)));

		$this->assertSame(1, $report->closed);
		$this->assertSame(1, $report->countErrors());
		$this->assertTrue($report->hasFailures());
	}

	/**
	 * With several price-unit tiers, the line is updated from the tier matching its
	 * unit, NOT from the base-unit tier (regression for the ANTALIS multi-unit grid).
	 *
	 * @return void
	 */
	public function testUnitMatchPicksTierOfLineUnit(): void
	{
		$this->createLine(0.01, 'Ramette');

		// ANTALIS-style grid: same quantity, one base-unit tier + one commercial tier.
		$report = $this->runService($this->foundGrid(array(
			new SupplierPriceTier($this->quantity, 'Feuille', 0.02),
			new SupplierPriceTier($this->quantity, 'Ramette', 9.76),
		)), false);

		$this->assertSame(1, $report->updated);
		$this->assertSame(0, $report->created);
		$this->assertEqualsWithDelta(9.76, (float) $this->readLine()->unitprice, 0.0001);
	}

	/**
	 * When no returned tier matches the line unit, the line is left untouched and a
	 * warning is raised (fail-safe: never write a price in the wrong unit).
	 *
	 * @return void
	 */
	public function testNoTierForLineUnitWarnsAndKeepsLine(): void
	{
		$this->createLine(0.01, 'Ramette');

		$report = $this->runService($this->foundGrid(array(
			new SupplierPriceTier($this->quantity, 'Feuille', 0.02),
		)), false);

		$this->assertSame(0, $report->updated);
		$this->assertSame(0, $report->created);
		$this->assertGreaterThanOrEqual(1, $report->countWarnings());
		$this->assertEqualsWithDelta(0.01, (float) $this->readLine()->unitprice, 0.0001);
	}
}
