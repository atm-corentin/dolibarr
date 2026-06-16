<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr> */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../../../master.inc.php';
require_once dirname(__FILE__).'/../../../../supplier_proposal/class/supplier_proposal.class.php';
require_once dirname(__FILE__).'/../../../../comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../class/Subcontracting/CliChaumeilSubcontractingBuyPricePropagationService.class.php';
require_once dirname(__FILE__).'/../../../../../test/phpunit/CommonClassTest.class.php';

/**
 * Integration tests for CliChaumeilSubcontractingBuyPricePropagationService.
 *
 * Setup is performed via direct SQL inserts on llx_propal, llx_propaldet,
 * llx_supplier_proposal, llx_supplier_proposaldet and llx_element_element so the
 * service can be exercised against the validated-document SQL UPDATE branch with
 * controlled rang/fk_product to test ambiguity behavior. Avoids the heavier
 * Dolibarr business-object create/valid flows.
 *
 * @backupGlobals disabled
 */
class CliChaumeilSubcontractingBuyPricePropagationTest extends CommonClassTest
{
	/**
	 * Make sure a usable thirdparty row exists (returns the first existing rowid, or creates one).
	 *
	 * Tests are wrapped in a transaction by CommonClassTest::setUpBeforeClass / tearDownAfterClass,
	 * so any insert here is rolled back at the end of the suite.
	 *
	 * @return int Thirdparty id usable as a foreign key.
	 */
	private function ensureTestThirdparty(): int
	{
		global $db;
		$resql = $db->query('SELECT rowid FROM '.$db->prefix().'societe ORDER BY rowid ASC LIMIT 1');
		$this->assertNotFalse($resql);
		$row = $db->fetch_object($resql);
		$this->assertNotEmpty($row, 'No usable thirdparty available for the test fixture.');
		return (int) $row->rowid;
	}

	/**
	 * Return an existing product id usable as a foreign key, or 0 if no product exists.
	 *
	 * Returning 0 keeps the fk_product nullable side of the schema valid while letting the
	 * matching logic still pair lines (both sides hold the same 0).
	 *
	 * @return int Product id (existing row or 0).
	 */
	private function ensureTestProduct(): int
	{
		global $db;
		$resql = $db->query('SELECT rowid FROM '.$db->prefix().'product ORDER BY rowid ASC LIMIT 1');
		if (!$resql) {
			return 0;
		}
		$row = $db->fetch_object($resql);
		return $row ? (int) $row->rowid : 0;
	}

	/**
	 * Insert a validated propal with the given lines (one row per array entry).
	 *
	 * Each line shares the same fk_product (first existing product or 0), rang=100,
	 * special_code=0 — minimal setup to drive the matching logic in the service.
	 *
	 * @param array<int,array{subprice:float,buy_price_ht:float}> $lines One spec per line.
	 * @return array{propal:Propal,line_ids:array<int,int>}
	 */
	private function insertValidatedPropal(array $lines): array
	{
		global $db;
		$socId     = $this->ensureTestThirdparty();
		$productId = $this->ensureTestProduct();
		$sql = 'INSERT INTO '.$db->prefix().'propal (entity, ref, ref_client, datec, fk_soc, fk_statut)';
		$sql .= " VALUES (1, 'TEST_ST6_".uniqid()."', '', NOW(), ".$socId.', 1)';
		$this->assertTrue((bool) $db->query($sql), 'Insert propal failed: '.$db->lasterror());
		$propalId = (int) $db->last_insert_id($db->prefix().'propal');

		$lineIds = array();
		foreach ($lines as $line) {
			$sql = 'INSERT INTO '.$db->prefix().'propaldet';
			$sql .= ' (fk_propal, fk_product, label, description, qty, subprice, tva_tx, special_code, rang, product_type, buy_price_ht)';
			$sql .= ' VALUES ('.$propalId.', '.$productId.", 'ST-6 Test', 'ST-6 Test', 1, ".((float) $line['subprice']).', 20, 0, 100, 0, '.((float) $line['buy_price_ht']).')';
			$this->assertTrue((bool) $db->query($sql), 'Insert propaldet failed: '.$db->lasterror());
			$lineIds[] = (int) $db->last_insert_id($db->prefix().'propaldet');
		}

		$propal = new Propal($db);
		$this->assertGreaterThan(0, $propal->fetch($propalId));
		$this->assertGreaterThanOrEqual(0, $propal->fetch_lines());

		return array('propal' => $propal, 'line_ids' => $lineIds);
	}

	/**
	 * Insert a supplier proposal with one line carrying the given buy price and link it to the parent.
	 *
	 * @param float      $buyPrice         Supplier-quoted unit price written on subprice.
	 * @param Propal     $parent           Parent client document.
	 * @param float|null $buyPriceHtColumn Optional override for the buy_price_ht column (defaults to $buyPrice).
	 * @return SupplierProposal
	 */
	private function insertSupplierProposalLinkedTo(float $buyPrice, Propal $parent, ?float $buyPriceHtColumn = null): SupplierProposal
	{
		global $db;
		$socId            = $this->ensureTestThirdparty();
		$productId        = $this->ensureTestProduct();
		$buyPriceHtColumn = $buyPriceHtColumn ?? $buyPrice;
		$sql = 'INSERT INTO '.$db->prefix().'supplier_proposal (entity, ref, ref_supplier, datec, fk_soc, fk_statut)';
		$sql .= " VALUES (1, 'TEST_ST6_SP_".uniqid()."', '', NOW(), ".$socId.', 1)';
		$this->assertTrue((bool) $db->query($sql), 'Insert supplier_proposal failed: '.$db->lasterror());
		$spId = (int) $db->last_insert_id($db->prefix().'supplier_proposal');

		$sql = 'INSERT INTO '.$db->prefix().'supplier_proposaldet';
		$sql .= ' (fk_supplier_proposal, fk_product, label, description, qty, subprice, tva_tx, special_code, rang, product_type, buy_price_ht)';
		$sql .= ' VALUES ('.$spId.', '.$productId.", 'ST-6 SP', 'ST-6 SP', 1, ".((float) $buyPrice).', 20, 0, 100, 0, '.((float) $buyPriceHtColumn).')';
		$this->assertTrue((bool) $db->query($sql), 'Insert supplier_proposaldet failed: '.$db->lasterror());

		$sql = 'INSERT INTO '.$db->prefix().'element_element (fk_source, sourcetype, fk_target, targettype)';
		$sql .= ' VALUES ('.((int) $parent->id).", 'propal', ".$spId.", 'supplier_proposal')";
		$this->assertTrue((bool) $db->query($sql), 'Insert element_element failed: '.$db->lasterror());

		$sp = new SupplierProposal($db);
		$this->assertGreaterThan(0, $sp->fetch($spId));
		$sp->getLinesArray();
		return $sp;
	}

	/**
	 * Validated parent: propagation writes buy_price_ht and leaves subprice unchanged.
	 *
	 * @return void
	 */
	public function testValidatedPropagationUpdatesBuyPriceOnly(): void
	{
		global $db, $user;
		$context = $this->insertValidatedPropal(array(array('subprice' => 100.0, 'buy_price_ht' => 0.0)));
		$sp      = $this->insertSupplierProposalLinkedTo(70.0, $context['propal']);

		$service = new CliChaumeilSubcontractingBuyPricePropagationService($db);
		$report  = $service->propagate($context['propal'], $sp, $user);

		$this->assertSame(1, $report['updated']);
		$row = $db->fetch_object($db->query('SELECT subprice, buy_price_ht FROM '.$db->prefix().'propaldet WHERE rowid='.$context['line_ids'][0]));
		$this->assertEquals(70.0, (float) $row->buy_price_ht);
		$this->assertEquals(100.0, (float) $row->subprice);
	}

	/**
	 * A buy price of 0 is propagated and overwrites the previous non-zero value.
	 *
	 * @return void
	 */
	public function testZeroBuyPriceIsPropagated(): void
	{
		global $db, $user;
		$context = $this->insertValidatedPropal(array(array('subprice' => 100.0, 'buy_price_ht' => 42.0)));
		$sp      = $this->insertSupplierProposalLinkedTo(0.0, $context['propal']);

		$service = new CliChaumeilSubcontractingBuyPricePropagationService($db);
		$report  = $service->propagate($context['propal'], $sp, $user);

		$this->assertSame(1, $report['updated']);
		$row = $db->fetch_object($db->query('SELECT buy_price_ht FROM '.$db->prefix().'propaldet WHERE rowid='.$context['line_ids'][0]));
		$this->assertEquals(0.0, (float) $row->buy_price_ht);
	}

	/**
	 * Supplier subprice is what gets propagated, not the buy_price_ht column.
	 *
	 * @return void
	 */
	public function testSubpricePrevailsOverBuyPriceHtColumn(): void
	{
		global $db, $user;
		$context = $this->insertValidatedPropal(array(array('subprice' => 100.0, 'buy_price_ht' => 0.0)));
		// subprice = 70 (what the subcontractor quoted), buy_price_ht column = 12.34 (legacy/inherited).
		$sp = $this->insertSupplierProposalLinkedTo(70.0, $context['propal'], 12.34);

		$service = new CliChaumeilSubcontractingBuyPricePropagationService($db);
		$report  = $service->propagate($context['propal'], $sp, $user);

		$this->assertSame(1, $report['updated']);
		$row = $db->fetch_object($db->query('SELECT buy_price_ht FROM '.$db->prefix().'propaldet WHERE rowid='.$context['line_ids'][0]));
		$this->assertEquals(70.0, (float) $row->buy_price_ht);
	}

	/**
	 * Ambiguous parent lines (same product+rang+special_code) abort with RuntimeException
	 * so the workflow can roll back the transaction.
	 *
	 * @return void
	 */
	public function testAmbiguousParentLinesThrows(): void
	{
		global $db, $user;
		$context = $this->insertValidatedPropal(array(
			array('subprice' => 100.0, 'buy_price_ht' => 0.0),
			array('subprice' => 110.0, 'buy_price_ht' => 0.0),
		));
		$sp = $this->insertSupplierProposalLinkedTo(50.0, $context['propal']);

		$service = new CliChaumeilSubcontractingBuyPricePropagationService($db);

		$this->expectException(RuntimeException::class);
		$service->propagate($context['propal'], $sp, $user);
	}

	/**
	 * Draft parent: discountrules markup recompute updates subprice and buy_price_ht.
	 *
	 * Markup formula on a 20% rate: subprice = buyPrice / (1 - 0.20) = buyPrice / 0.8.
	 * With buyPrice = 80 → expected subprice = 100.
	 *
	 * @return void
	 */
	public function testDraftPropagationRecomputesSubpriceViaDiscountrules(): void
	{
		global $db, $user, $conf;
		$globalsBackup = array(
			'DISCOUNTRULES_USE_MARKUP_MARGIN_RATE' => (string) getDolGlobalString('DISCOUNTRULES_USE_MARKUP_MARGIN_RATE'),
			'DISCOUNTRULES_MARKUP_MARGIN_RATE'    => (string) getDolGlobalString('DISCOUNTRULES_MARKUP_MARGIN_RATE'),
			'DISCOUNTRULES_MINIMUM_RATE'          => (string) getDolGlobalString('DISCOUNTRULES_MINIMUM_RATE'),
		);
		$conf->global->DISCOUNTRULES_USE_MARKUP_MARGIN_RATE = 1;
		$conf->global->DISCOUNTRULES_MARKUP_MARGIN_RATE     = 0;
		$conf->global->DISCOUNTRULES_MINIMUM_RATE           = '20';

		try {
			$context  = $this->insertDraftPropal(array(array('subprice' => 250.0, 'buy_price_ht' => 0.0)));
			$sp       = $this->insertSupplierProposalLinkedTo(80.0, $context['propal']);
			$service  = new CliChaumeilSubcontractingBuyPricePropagationService($db);

			$report = $service->propagate($context['propal'], $sp, $user);

			$this->assertSame(1, $report['updated']);
			$row = $db->fetch_object($db->query('SELECT subprice, buy_price_ht FROM '.$db->prefix().'propaldet WHERE rowid='.$context['line_ids'][0]));
			$this->assertEquals(80.0, (float) $row->buy_price_ht);
			$this->assertEquals(100.0, (float) $row->subprice);
		} finally {
			foreach ($globalsBackup as $key => $value) {
				$conf->global->$key = $value;
			}
		}
	}

	/**
	 * Insert a draft propal (fk_statut=0) with the given lines.
	 *
	 * @param array<int,array{subprice:float,buy_price_ht:float}> $lines One spec per line.
	 * @return array{propal:Propal,line_ids:array<int,int>}
	 */
	private function insertDraftPropal(array $lines): array
	{
		global $db;
		$socId     = $this->ensureTestThirdparty();
		$productId = $this->ensureTestProduct();
		$sql = 'INSERT INTO '.$db->prefix().'propal (entity, ref, ref_client, datec, fk_soc, fk_statut)';
		$sql .= " VALUES (1, 'TEST_ST6_DRAFT_".uniqid()."', '', NOW(), ".$socId.', 0)';
		$this->assertTrue((bool) $db->query($sql), 'Insert draft propal failed: '.$db->lasterror());
		$propalId = (int) $db->last_insert_id($db->prefix().'propal');

		$lineIds = array();
		foreach ($lines as $line) {
			$sql = 'INSERT INTO '.$db->prefix().'propaldet';
			$sql .= ' (fk_propal, fk_product, label, description, qty, subprice, tva_tx, special_code, rang, product_type, buy_price_ht)';
			$sql .= ' VALUES ('.$propalId.', '.$productId.", 'ST-6 Draft', 'ST-6 Draft', 1, ".((float) $line['subprice']).', 20, 0, 100, 0, '.((float) $line['buy_price_ht']).')';
			$this->assertTrue((bool) $db->query($sql), 'Insert draft propaldet failed: '.$db->lasterror());
			$lineIds[] = (int) $db->last_insert_id($db->prefix().'propaldet');
		}

		$propal = new Propal($db);
		$this->assertGreaterThan(0, $propal->fetch($propalId));
		$this->assertGreaterThanOrEqual(0, $propal->fetch_lines());

		return array('propal' => $propal, 'line_ids' => $lineIds);
	}
}
