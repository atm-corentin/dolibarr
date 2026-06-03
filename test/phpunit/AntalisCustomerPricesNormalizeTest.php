<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * @file    test/phpunit/AntalisCustomerPricesNormalizeTest.php
 * @brief   Unit tests for AntalisCustomerPricesConnector::normalizeResponse (ACHT-2-ANTALIS).
 *
 * Fixtures reproduce the real preprod customerPricesCheck payload (ref 264910:
 * personalUnitPrice 32.09 / personalPriceQty 1000 => unit 0.03209). normalizeResponse
 * is private and reached through ReflectionMethod: it is pure logic (no SOAP, no DB).
 *
 * Run: phpunit htdocs/custom/clichaumeil/test/phpunit/AntalisCustomerPricesNormalizeTest.php
 *
 * @backupGlobals          disabled
 * @backupStaticAttributes enabled
 */

declare(strict_types=1);

global $conf, $user, $langs, $db;

require_once dirname(__FILE__) . '/../../../../master.inc.php';
require_once dirname(__FILE__) . '/../../../../../test/phpunit/CommonClassTest.class.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/SupplierPriceSyncConstants.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierPriceSyncIssue.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierProductRequest.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierPriceTier.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierProductPriceGrid.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierPriceGridFetchResult.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Antalis/AntalisOrderUnitMapper.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Antalis/AntalisCustomerPricesConnector.php';

/**
 * Tests for AntalisCustomerPricesConnector::normalizeResponse.
 */
class AntalisCustomerPricesNormalizeTest extends CommonClassTest
{
	/**
	 * Build a product request.
	 *
	 * @param string $ref Supplier reference.
	 * @return SupplierProductRequest
	 */
	private function request(string $ref): SupplierProductRequest
	{
		return new SupplierProductRequest(10, 185, $ref, 'P1');
	}

	/**
	 * Build a threshold object.
	 *
	 * @param float  $qty             Threshold quantity.
	 * @param string $unit            Threshold unit code.
	 * @param float  $personalUnit    Personal unit price.
	 * @param float  $personalPriceQty Personal price quantity.
	 * @return object
	 */
	private function threshold(float $qty, string $unit, float $personalUnit, float $personalPriceQty): object
	{
		return (object) array(
			'thresholdQty' => $qty,
			'thresholdQtyUnit' => $unit,
			'personalUnitPrice' => $personalUnit,
			'personalPriceQty' => $personalPriceQty,
			'personalPriceUnit' => $unit,
		);
	}

	/**
	 * Invoke the private normalizeResponse method.
	 *
	 * @param object                            $response SOAP response object.
	 * @param array<int,SupplierProductRequest> $lineMap  Line map.
	 * @return SupplierPriceGridFetchResult
	 */
	private function invoke(object $response, array $lineMap): SupplierPriceGridFetchResult
	{
		$connector = AntalisCustomerPricesConnector::forTesting(new AntalisOrderUnitMapper());
		$method = new ReflectionMethod($connector, 'normalizeResponse');
		$method->setAccessible(true);

		return $method->invoke($connector, $response, $lineMap);
	}

	/**
	 * 00 normalises personalUnitPrice / personalPriceQty (real payload).
	 *
	 * @return void
	 */
	public function testFoundNormalizesPersonalUnitPrice(): void
	{
		$response = (object) array('errorID' => '00', 'detailRow' => array(
			(object) array('lineNr' => 1, 'productID' => '264910', 'errorID' => '00', 'threshold' => array(
				$this->threshold(500.0, 'ST', 32.09, 1000.0),
			)),
		));

		$result = $this->invoke($response, array(1 => $this->request('264910')));

		$this->assertFalse($result->fatalError);
		$this->assertCount(1, $result->grids);
		$this->assertSame(SupplierProductPriceGrid::STATE_FOUND, $result->grids[0]->state);
		$this->assertCount(1, $result->grids[0]->tiers);
		$this->assertEqualsWithDelta(0.03209, $result->grids[0]->tiers[0]->normalizedUnitPrice, 0.00001);
		$this->assertSame(500.0, $result->grids[0]->tiers[0]->quantity);
		$this->assertSame('Un', $result->grids[0]->tiers[0]->unitLabel);
	}

	/**
	 * Several thresholds yield several tiers.
	 *
	 * @return void
	 */
	public function testMultipleThresholds(): void
	{
		$response = (object) array('errorID' => '00', 'detailRow' => array(
			(object) array('lineNr' => 1, 'errorID' => '00', 'threshold' => array(
				$this->threshold(500.0, 'ST', 32.09, 1000.0),
				$this->threshold(2500.0, 'ST', 28.00, 1000.0),
			)),
		));

		$result = $this->invoke($response, array(1 => $this->request('264910')));

		$this->assertCount(2, $result->grids[0]->tiers);
		$this->assertEqualsWithDelta(0.028, $result->grids[0]->tiers[1]->normalizedUnitPrice, 0.00001);
	}

	/**
	 * 16 (not available) yields an absent grid (no issue).
	 *
	 * @return void
	 */
	public function testNotAvailableIsAbsent(): void
	{
		$response = (object) array('errorID' => '00', 'detailRow' => array(
			(object) array('lineNr' => 1, 'errorID' => '16'),
		));

		$result = $this->invoke($response, array(1 => $this->request('X')));

		$this->assertSame(SupplierProductPriceGrid::STATE_ABSENT, $result->grids[0]->state);
		$this->assertSame(array(), $result->issues);
	}

	/**
	 * 04 (unknown product) yields an error grid plus a reference issue (no mutation).
	 *
	 * @return void
	 */
	public function testUnknownProductIsErrorWithIssue(): void
	{
		$response = (object) array('errorID' => '00', 'detailRow' => array(
			(object) array('lineNr' => 1, 'errorID' => '04'),
		));

		$result = $this->invoke($response, array(1 => $this->request('X')));

		$this->assertSame(SupplierProductPriceGrid::STATE_ERROR, $result->grids[0]->state);
		$this->assertNotEmpty($result->issues);
		$this->assertSame(SupplierPriceSyncConstants::ISSUE_REFERENCE_NOT_FOUND, $result->issues[0]->code);
	}

	/**
	 * 90 (back-end down) on a detail row sets the fatal flag.
	 *
	 * @return void
	 */
	public function testBackendDownFatal(): void
	{
		$response = (object) array('errorID' => '00', 'detailRow' => array(
			(object) array('lineNr' => 1, 'errorID' => '90'),
		));

		$result = $this->invoke($response, array(1 => $this->request('X')));

		$this->assertTrue($result->fatalError);
	}

	/**
	 * A missing negotiated price skips the tier and raises a warning (no grid).
	 *
	 * @return void
	 */
	public function testMissingPersonalPriceSkipsTier(): void
	{
		$response = (object) array('errorID' => '00', 'detailRow' => array(
			(object) array('lineNr' => 1, 'errorID' => '00', 'threshold' => array(
				$this->threshold(500.0, 'ST', 0.0, 1000.0),
			)),
		));

		$result = $this->invoke($response, array(1 => $this->request('X')));

		$this->assertSame(array(), $result->grids);
		$this->assertNotEmpty($result->issues);
		$this->assertSame(SupplierPriceSyncConstants::ISSUE_MISSING_PERSONAL_PRICE, $result->issues[0]->code);
		$this->assertFalse($result->issues[0]->isError());
	}

	/**
	 * Real ANTALIS pattern: a product priced in several units returns several thresholds
	 * (same thresholdQty, different personalPriceUnit). Each is kept and labelled by its
	 * personalPriceUnit (the commercial unit), not thresholdQtyUnit. No mismatch issue.
	 *
	 * @return void
	 */
	public function testMultiUnitThresholdsKeptLabeledByPriceUnit(): void
	{
		$commercial = (object) array(
			'thresholdQty' => 2500.0,
			'thresholdQtyUnit' => 'ZSH',
			'personalUnitPrice' => 9.76,
			'personalPriceQty' => 1.0,
			'personalPriceUnit' => 'ZRM',
		);
		$base = (object) array(
			'thresholdQty' => 2500.0,
			'thresholdQtyUnit' => 'ZSH',
			'personalUnitPrice' => 0.02,
			'personalPriceQty' => 1.0,
			'personalPriceUnit' => 'ZSH',
		);
		$response = (object) array('errorID' => '00', 'detailRow' => array(
			(object) array('lineNr' => 1, 'errorID' => '00', 'threshold' => array($commercial, $base)),
		));

		$result = $this->invoke($response, array(1 => $this->request('266402')));

		$this->assertSame(array(), $result->issues);
		$this->assertCount(2, $result->grids[0]->tiers);
		$this->assertSame('Ramette', $result->grids[0]->tiers[0]->unitLabel);
		$this->assertEqualsWithDelta(9.76, $result->grids[0]->tiers[0]->normalizedUnitPrice, 0.0001);
		$this->assertSame('Feuille', $result->grids[0]->tiers[1]->unitLabel);
		$this->assertEqualsWithDelta(0.02, $result->grids[0]->tiers[1]->normalizedUnitPrice, 0.0001);
	}

	/**
	 * An unmapped unit still creates the tier with an empty label and a warning.
	 *
	 * @return void
	 */
	public function testUnmappedUnitWarnsButKeepsTier(): void
	{
		$response = (object) array('errorID' => '00', 'detailRow' => array(
			(object) array('lineNr' => 1, 'errorID' => '00', 'threshold' => array(
				$this->threshold(500.0, 'XYZ', 32.09, 1000.0),
			)),
		));

		$result = $this->invoke($response, array(1 => $this->request('X')));

		$this->assertCount(1, $result->grids[0]->tiers);
		$this->assertSame('', $result->grids[0]->tiers[0]->unitLabel);
		$this->assertSame(SupplierPriceSyncConstants::ISSUE_UNMAPPED_ORDER_UNIT, $result->issues[0]->code);
	}

	/**
	 * A single threshold object (not an array) is handled.
	 *
	 * @return void
	 */
	public function testSingletonThresholdIsHandled(): void
	{
		$response = (object) array('errorID' => '00', 'detailRow' => (object) array(
			'lineNr' => 1, 'errorID' => '00', 'threshold' => $this->threshold(100.0, 'ST', 10.0, 100.0),
		));

		$result = $this->invoke($response, array(1 => $this->request('X')));

		$this->assertCount(1, $result->grids[0]->tiers);
		$this->assertEqualsWithDelta(0.1, $result->grids[0]->tiers[0]->normalizedUnitPrice, 0.0001);
	}

	/**
	 * A header back-end-down error sets the fatal flag.
	 *
	 * @return void
	 */
	public function testHeaderBackendDownFatal(): void
	{
		$response = (object) array('errorID' => '90', 'detailRow' => array());

		$result = $this->invoke($response, array(1 => $this->request('X')));

		$this->assertTrue($result->fatalError);
		$this->assertSame(SupplierPriceSyncConstants::ISSUE_API_UNAVAILABLE, $result->issues[0]->code);
	}
}
