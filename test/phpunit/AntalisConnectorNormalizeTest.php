<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * @file    test/phpunit/AntalisConnectorNormalizeTest.php
 * @brief   Unit tests for AntalisConnector::normalizeResponse (ACHT-2-ANTALIS).
 *
 * Fixtures reproduce the real preprod payload (ref 264910, qty 500, unit ST:
 * materialPrice 16.05 => unit 0.0321). normalizeResponse is private and reached
 * through ReflectionMethod: it is pure logic (no SOAP, no DB).
 *
 * Run: phpunit htdocs/custom/clichaumeil/test/phpunit/AntalisConnectorNormalizeTest.php
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
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierPriceLineResult.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierPriceFetchResult.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/ValueObject/SupplierPriceCandidate.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Antalis/AntalisOrderUnitMapper.php';
require_once dirname(__FILE__) . '/../../class/SupplierPriceSync/Antalis/AntalisConnector.php';

/**
 * Tests for AntalisConnector::normalizeResponse.
 */
class AntalisConnectorNormalizeTest extends CommonClassTest
{
	/**
	 * Build a candidate for a given reference and quantity.
	 *
	 * @param string $ref     Supplier reference.
	 * @param float  $qty     Quantity.
	 * @param string $unitSrc Source order unit label.
	 * @return SupplierPriceCandidate
	 */
	private function candidate(string $ref, float $qty, string $unitSrc = 'Pièces'): SupplierPriceCandidate
	{
		return new SupplierPriceCandidate(1, 10, 'P1', 185, $ref, $qty, 0.048, 1, $unitSrc);
	}

	/**
	 * Invoke the private normalizeResponse method.
	 *
	 * @param object                            $response SOAP response object.
	 * @param array<int,SupplierPriceCandidate> $lineMap  Line map.
	 * @return SupplierPriceFetchResult
	 */
	private function invoke(object $response, array $lineMap): SupplierPriceFetchResult
	{
		$connector = AntalisConnector::forTesting(new AntalisOrderUnitMapper());
		$method = new ReflectionMethod($connector, 'normalizeResponse');
		$method->setAccessible(true);

		return $method->invoke($connector, $response, $lineMap);
	}

	/**
	 * 0000 normalises materialPrice / quantity (real payload).
	 *
	 * @return void
	 */
	public function testSuccessNormalizesMaterialPriceDividedByQuantity(): void
	{
		$response = (object) array('detailRow' => array(
			(object) array('lineNr' => 1, 'productID' => '264910', 'materialPrice' => 16.05, 'quantity' => 500.0, 'errorID' => '0000'),
		));

		$result = $this->invoke($response, array(1 => $this->candidate('264910', 500.0)));

		$this->assertFalse($result->fatalError);
		$this->assertSame(SupplierPriceLineResult::STATE_SUCCESS, $result->results[0]->state);
		$this->assertEqualsWithDelta(0.0321, $result->results[0]->normalizedUnitPrice, 0.0001);
	}

	/**
	 * 0016 (not available) yields a close result without issue.
	 *
	 * @return void
	 */
	public function testCode0016Close(): void
	{
		$response = (object) array('detailRow' => array((object) array('lineNr' => 1, 'errorID' => '0016')));

		$result = $this->invoke($response, array(1 => $this->candidate('X', 1.0)));

		$this->assertSame(SupplierPriceLineResult::STATE_CLOSE, $result->results[0]->state);
		$this->assertSame(array(), $result->issues);
	}

	/**
	 * 0004 (unknown product) yields a close result plus a reference issue.
	 *
	 * @return void
	 */
	public function testCode0004CloseWithIssue(): void
	{
		$response = (object) array('detailRow' => array((object) array('lineNr' => 1, 'errorID' => '0004')));

		$result = $this->invoke($response, array(1 => $this->candidate('X', 1.0)));

		$this->assertSame(SupplierPriceLineResult::STATE_CLOSE, $result->results[0]->state);
		$this->assertNotEmpty($result->issues);
		$this->assertSame(SupplierPriceSyncConstants::ISSUE_REFERENCE_NOT_FOUND, $result->issues[0]->code);
	}

	/**
	 * 0090 (back-end down) sets the fatal flag.
	 *
	 * @return void
	 */
	public function testCode0090Fatal(): void
	{
		$response = (object) array('detailRow' => array((object) array('lineNr' => 1, 'errorID' => '0090')));

		$result = $this->invoke($response, array(1 => $this->candidate('X', 1.0)));

		$this->assertTrue($result->fatalError);
	}

	/**
	 * A single detailRow object (not an array) is handled.
	 *
	 * @return void
	 */
	public function testSingletonDetailRowIsHandled(): void
	{
		$response = (object) array('detailRow' => (object) array('lineNr' => 1, 'materialPrice' => 10.0, 'quantity' => 100.0, 'errorID' => '0000'));

		$result = $this->invoke($response, array(1 => $this->candidate('X', 100.0)));

		$this->assertCount(1, $result->results);
		$this->assertEqualsWithDelta(0.1, $result->results[0]->normalizedUnitPrice, 0.0001);
	}

	/**
	 * A requested line missing from the response raises an issue.
	 *
	 * @return void
	 */
	public function testMissingRequestedLineRaisesIssue(): void
	{
		$response = (object) array('detailRow' => array());

		$result = $this->invoke($response, array(1 => $this->candidate('X', 1.0)));

		$this->assertNotEmpty($result->issues);
		$this->assertSame(SupplierPriceSyncConstants::ISSUE_API_LINE_ERROR, $result->issues[0]->code);
	}
}
