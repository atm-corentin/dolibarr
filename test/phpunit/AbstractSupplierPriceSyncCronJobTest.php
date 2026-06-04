<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * @file    test/phpunit/AbstractSupplierPriceSyncCronJobTest.php
 * @brief   Integration tests for AbstractSupplierPriceSyncCronJob (ACHT-2-ANTALIS).
 *
 * Real database (begin/rollback), with injected fake config + fake connector.
 *
 * Run: phpunit htdocs/custom/clichaumeil/test/phpunit/AbstractSupplierPriceSyncCronJobTest.php
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
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Cron/AbstractSupplierPriceSyncCronJob.php';

/**
 * Minimal config for the cron test (reuses the supplier id created by the test).
 */
class CronTestConfig implements SupplierConfigInterface
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
 * Connector returning a single canned result, ignoring the products.
 */
class CronTestConnector implements SupplierPriceConnectorInterface
{
	/** @var SupplierPriceGridFetchResult Canned result. */
	private SupplierPriceGridFetchResult $canned;

	/**
	 * @param SupplierPriceGridFetchResult $canned Canned result.
	 */
	public function __construct(SupplierPriceGridFetchResult $canned)
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
	 * Update-only connector.
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
	 * @param SupplierProductRequest[] $products Products received.
	 * @return SupplierPriceGridFetchResult
	 */
	public function fetchPriceGrids(array $products): SupplierPriceGridFetchResult // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	{
		return $this->canned;
	}
}

/**
 * Testable cron exposing injectable config/connector and a config-failure switch.
 */
class TestableSupplierPriceSyncCronJob extends AbstractSupplierPriceSyncCronJob
{
	/** @var SupplierConfigInterface Injected config. */
	public SupplierConfigInterface $injectedConfig;

	/** @var SupplierPriceConnectorInterface Injected connector. */
	public SupplierPriceConnectorInterface $injectedConnector;

	/** @var bool When true, buildConfig() throws (missing-configuration path). */
	public bool $failConfig = false;

	/**
	 * Build the supplier configuration.
	 *
	 * @return SupplierConfigInterface
	 * @throws RuntimeException When failConfig is set.
	 */
	protected function buildConfig(): SupplierConfigInterface
	{
		if ($this->failConfig) {
			throw new RuntimeException('missing config');
		}

		return $this->injectedConfig;
	}

	/**
	 * Build the supplier connector.
	 *
	 * @param SupplierConfigInterface $config Supplier configuration.
	 * @return SupplierPriceConnectorInterface
	 */
	protected function buildConnector(SupplierConfigInterface $config): SupplierPriceConnectorInterface
	{
		return $this->injectedConnector;
	}
}

/**
 * Tests for AbstractSupplierPriceSyncCronJob.
 */
class AbstractSupplierPriceSyncCronJobTest extends CommonClassTest
{
	/** @var int Supplier id created for the test. */
	private int $supplierId = 0;

	/** @var string Supplier ref created for the test. */
	private string $supplierRef = '';

	/** @var float Quantity used. */
	private float $quantity = 100.0;

	/**
	 * Create a supplier with one active product line priced at 0.048.
	 *
	 * @return void
	 */
	private function createLine(): void
	{
		global $db, $user;

		$supplier = new Societe($db);
		$supplier->name = 'TEST CRON SUPPLIER';
		$supplier->fournisseur = 1;
		$supplier->code_fournisseur = 'TESTCRON' . uniqid();
		$this->supplierId = (int) $supplier->create($user);

		$product = new Product($db);
		$product->ref = 'TEST_CRON_' . $this->supplierId;
		$product->label = 'Test product cron';
		$product->type = Product::TYPE_PRODUCT;
		$product->status_buy = 1;
		$productId = (int) $product->create($user);

		$this->supplierRef = 'CRONREF_' . $productId;
		$product->add_fournisseur($user, $this->supplierId, $this->supplierRef, $this->quantity);
	}

	/**
	 * Build a testable cron with the given canned connector result.
	 *
	 * @param SupplierPriceGridFetchResult $canned Canned result.
	 * @return TestableSupplierPriceSyncCronJob
	 */
	private function buildCron(SupplierPriceGridFetchResult $canned): TestableSupplierPriceSyncCronJob
	{
		global $db;
		$cron = new TestableSupplierPriceSyncCronJob($db);
		$cron->injectedConfig = new CronTestConfig($this->supplierId);
		$cron->injectedConnector = new CronTestConnector($canned);

		return $cron;
	}

	/**
	 * A successful run returns 0, fills the output and persists the last-run constant.
	 *
	 * @return void
	 */
	public function testSuccessReturnsZeroAndPersistsLastRun(): void
	{
		$this->createLine();
		$canned = new SupplierPriceGridFetchResult(
			array(SupplierProductPriceGrid::found($this->supplierRef, array(
				new SupplierPriceTier($this->quantity, '', 0.0321),
			))),
			array(),
			false
		);

		$cron = $this->buildCron($canned);
		$result = $cron->run('');

		$this->assertSame(0, $result);
		$this->assertNotSame('', $cron->output);

		$raw = getDolGlobalString(SupplierPriceSyncConstants::CONST_LASTRUN_PREFIX . 'ANTALIS');
		$this->assertNotSame('', $raw);
		$decoded = json_decode($raw, true);
		$this->assertIsArray($decoded);
		$this->assertArrayHasKey('summary', $decoded);
		$this->assertFalse($decoded['dryRun']);
	}

	/**
	 * An API failure returns -1 and still fills the output.
	 *
	 * @return void
	 */
	public function testApiFailureReturnsMinusOne(): void
	{
		$this->createLine();
		$issue = new SupplierPriceSyncIssue(
			SupplierPriceSyncIssue::SEVERITY_ERROR,
			SupplierPriceSyncConstants::ISSUE_API_UNAVAILABLE,
			'down'
		);
		$canned = new SupplierPriceGridFetchResult(array(), array($issue), true);

		// Empty recipients => no mail is attempted on failure.
		$result = $this->buildCron($canned)->run('');

		$this->assertSame(-1, $result);
	}

	/**
	 * An invalid recipient list returns -1 before any sync.
	 *
	 * @return void
	 */
	public function testInvalidRecipientReturnsMinusOne(): void
	{
		$this->createLine();
		$canned = new SupplierPriceGridFetchResult(array(), array(), false);

		$result = $this->buildCron($canned)->run('not-an-email');

		$this->assertSame(-1, $result);
	}

	/**
	 * A missing configuration returns -1.
	 *
	 * @return void
	 */
	public function testMissingConfigurationReturnsMinusOne(): void
	{
		global $db;
		$cron = new TestableSupplierPriceSyncCronJob($db);
		$cron->failConfig = true;

		$result = $cron->run('');

		$this->assertSame(-1, $result);
	}
}
