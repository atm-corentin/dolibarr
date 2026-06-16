<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr> */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../../../master.inc.php';
require_once dirname(__FILE__).'/../../../../supplier_proposal/class/supplier_proposal.class.php';
require_once dirname(__FILE__).'/../../../../comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../class/Subcontracting/CliChaumeilSupplierProposalLineLinkService.class.php';
require_once dirname(__FILE__).'/../../../../../test/phpunit/CommonClassTest.class.php';

/**
 * Unit tests for CliChaumeilSupplierProposalLineLinkService.
 *
 * Focuses on the in-memory matching logic: persistent link, deterministic match,
 * ambiguity detection, and absence of match. No persistence is exercised.
 *
 * @backupGlobals disabled
 */
class CliChaumeilSupplierProposalLineLinkServiceTest extends CommonClassTest
{
	/**
	 * Build a supplier proposal line.
	 *
	 * @param int                       $id           Line id.
	 * @param int                       $fkProduct    Product id (0 for free lines).
	 * @param int                       $rang         Line rank.
	 * @param int                       $specialCode  Special code.
	 * @param array<string,mixed>|null  $arrayOptions Optional extrafield values.
	 * @return SupplierProposalLine
	 */
	private function makeSupplierLine(int $id, int $fkProduct, int $rang, int $specialCode = 0, ?array $arrayOptions = null): SupplierProposalLine
	{
		global $db;
		$line               = new SupplierProposalLine($db);
		$line->id           = $id;
		$line->rowid        = $id;
		$line->fk_product   = $fkProduct;
		$line->rang         = $rang;
		$line->special_code = $specialCode;
		$line->array_options = $arrayOptions ?? array();
		return $line;
	}

	/**
	 * Build a parent line stub.
	 *
	 * @param int $id          Line id.
	 * @param int $fkProduct   Product id.
	 * @param int $rang        Line rank.
	 * @param int $specialCode Special code.
	 * @return PropaleLigne
	 */
	private function makeParentLine(int $id, int $fkProduct, int $rang, int $specialCode = 0): PropaleLigne
	{
		global $db;
		$line               = new PropaleLigne($db);
		$line->id           = $id;
		$line->rowid        = $id;
		$line->fk_product   = $fkProduct;
		$line->rang         = $rang;
		$line->special_code = $specialCode;
		return $line;
	}

	/**
	 * Returns the parent line referenced by the persistent extrafield.
	 *
	 * @return void
	 */
	public function testReturnsPersistentLinkWhenPresent(): void
	{
		global $db;
		$service = new CliChaumeilSupplierProposalLineLinkService($db);

		$supplierLine = $this->makeSupplierLine(10, 42, 1, 0, array(
			'options_clichaumeil_source_element' => 'propal',
			'options_clichaumeil_source_line_id' => '777',
		));
		$parentLines  = array(
			$this->makeParentLine(555, 42, 1),
			$this->makeParentLine(777, 999, 9),
		);

		$result = $service->findParentLineMatch($supplierLine, $parentLines);

		$this->assertNotNull($result['line']);
		$this->assertSame(777, $result['line']->id);
		$this->assertFalse($result['ambiguous']);
	}

	/**
	 * Returns the deterministic match when fk_product+rang+special_code is unique.
	 *
	 * @return void
	 */
	public function testFallbackMatchesByProductRankAndSpecialCode(): void
	{
		global $db;
		$service = new CliChaumeilSupplierProposalLineLinkService($db);

		$supplierLine = $this->makeSupplierLine(10, 42, 1);
		$parentLines  = array(
			$this->makeParentLine(100, 42, 1),
			$this->makeParentLine(200, 99, 2),
		);

		$result = $service->findParentLineMatch($supplierLine, $parentLines);

		$this->assertNotNull($result['line']);
		$this->assertSame(100, $result['line']->id);
		$this->assertFalse($result['ambiguous']);
	}

	/**
	 * Flags ambiguous match when several parent lines satisfy the fallback criteria.
	 *
	 * @return void
	 */
	public function testFallbackFlagsAmbiguousMatch(): void
	{
		global $db;
		$service = new CliChaumeilSupplierProposalLineLinkService($db);

		$supplierLine = $this->makeSupplierLine(10, 42, 1);
		$parentLines  = array(
			$this->makeParentLine(100, 42, 1),
			$this->makeParentLine(200, 42, 1),
		);

		$result = $service->findParentLineMatch($supplierLine, $parentLines);

		$this->assertNull($result['line']);
		$this->assertTrue($result['ambiguous']);
	}

	/**
	 * Returns no match when no parent line satisfies the fallback criteria.
	 *
	 * @return void
	 */
	public function testFallbackReturnsNoMatch(): void
	{
		global $db;
		$service = new CliChaumeilSupplierProposalLineLinkService($db);

		$supplierLine = $this->makeSupplierLine(10, 42, 1);
		$parentLines  = array(
			$this->makeParentLine(100, 99, 2),
		);

		$result = $service->findParentLineMatch($supplierLine, $parentLines);

		$this->assertNull($result['line']);
		$this->assertFalse($result['ambiguous']);
	}

	/**
	 * Returns no match (not ambiguous) when the persistent link points to a missing line.
	 *
	 * @return void
	 */
	public function testPersistentLinkPointingToMissingLineReturnsNoMatch(): void
	{
		global $db;
		$service = new CliChaumeilSupplierProposalLineLinkService($db);

		$supplierLine = $this->makeSupplierLine(10, 42, 1, 0, array(
			'options_clichaumeil_source_element' => 'propal',
			'options_clichaumeil_source_line_id' => '12345',
		));
		$parentLines  = array(
			$this->makeParentLine(100, 42, 1),
		);

		$result = $service->findParentLineMatch($supplierLine, $parentLines);

		$this->assertNull($result['line']);
		$this->assertFalse($result['ambiguous']);
	}
}
