<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr> */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../../../master.inc.php';
require_once dirname(__FILE__).'/../../../../comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../../../commande/class/commande.class.php';
require_once dirname(__FILE__).'/../../../../product/class/product.class.php';
require_once dirname(__FILE__).'/../../class/CliChaumeilCloneCostPriceService.class.php';
require_once dirname(__FILE__).'/../../../../../test/phpunit/CommonClassTest.class.php';

/**
 * Integration tests for CliChaumeilCloneCostPriceService (VT-25).
 *
 * Fixtures are created via Product::create() + direct SQL inserts on propal/propaldet
 * and commande/commandedet, all inside the suite transaction rolled back at the end.
 * Requires the clichaumeil_cost_source extrafield to exist on propaldet/commandedet
 * (module must be installed); tests self-skip otherwise.
 *
 * @backupGlobals disabled
 */
class CliChaumeilCloneCostPriceServiceTest extends CommonClassTest
{
	/**
	 * Skip the whole class if the VT-25 extrafield is not deployed on the test DB.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		global $db;
		$extrafields = new ExtraFields($db);
		$extrafields->fetch_name_optionals_label('propaldet', true);
		$extrafields->fetch_name_optionals_label('commandedet', true);
		if (!isset($extrafields->attributes['propaldet']['type']['clichaumeil_cost_source'])
			|| !isset($extrafields->attributes['commandedet']['type']['clichaumeil_cost_source'])) {
			$this->markTestSkipped('clichaumeil_cost_source extrafield missing — re-run module init.');
		}
	}

	/**
	 * First existing thirdparty rowid (fixtures need a valid fk_soc).
	 *
	 * @return int
	 */
	private function firstThirdparty(): int
	{
		global $db;
		$resql = $db->query('SELECT rowid FROM '.$db->prefix().'societe ORDER BY rowid ASC LIMIT 1');
		$this->assertNotFalse($resql);
		$row = $db->fetch_object($resql);
		$this->assertNotEmpty($row, 'No usable thirdparty for the fixture.');
		return (int) $row->rowid;
	}

	/**
	 * Create a product with an exact cost_price (null allowed) and return its id.
	 *
	 * @param float|null $costPrice Exact cost_price to store.
	 * @return int Product id.
	 */
	private function createProductWithCostPrice(?float $costPrice): int
	{
		global $db, $user;
		$product = new Product($db);
		$product->ref = 'VT25_'.uniqid();
		$product->label = $product->ref;
		$product->type = Product::TYPE_PRODUCT;
		$product->status = 1;
		$product->status_buy = 1;
		$id = $product->create($user);
		$this->assertGreaterThan(0, $id, 'Product create failed: '.$product->error);
		$db->query('UPDATE '.$db->prefix().'product SET cost_price = '
			.($costPrice === null ? 'null' : (float) $costPrice).' WHERE rowid = '.(int) $id);
		return (int) $id;
	}

	/**
	 * Insert a propal with one line (fk_product / buy_price_ht controlled), fetched with lines.
	 *
	 * @param int   $fkProduct  Product id (0 for a free line).
	 * @param float $buyPriceHt Initial buy_price_ht on the line.
	 * @return array{propal:Propal,line_id:int}
	 */
	private function insertPropalWithLine(int $fkProduct, float $buyPriceHt): array
	{
		global $db;
		$socId = $this->firstThirdparty();
		$sql = 'INSERT INTO '.$db->prefix().'propal (entity, ref, ref_client, datec, fk_soc, fk_statut)';
		$sql .= " VALUES (1, 'VT25_PROP_".uniqid()."', '', NOW(), ".$socId.', 0)';
		$this->assertTrue((bool) $db->query($sql), 'Insert propal failed: '.$db->lasterror());
		$propalId = (int) $db->last_insert_id($db->prefix().'propal');

		$sql = 'INSERT INTO '.$db->prefix().'propaldet';
		$sql .= ' (fk_propal, fk_product, label, description, qty, subprice, tva_tx, special_code, rang, product_type, buy_price_ht, fk_product_fournisseur_price)';
		$sql .= ' VALUES ('.$propalId.', '.$fkProduct.", 'VT25', 'VT25', 1, 100, 20, 0, 1, 0, ".$buyPriceHt.', 12345)';
		$this->assertTrue((bool) $db->query($sql), 'Insert propaldet failed: '.$db->lasterror());
		$lineId = (int) $db->last_insert_id($db->prefix().'propaldet');

		$propal = new Propal($db);
		$this->assertGreaterThan(0, $propal->fetch($propalId));
		$this->assertGreaterThanOrEqual(0, $propal->fetch_lines());
		return array('propal' => $propal, 'line_id' => $lineId);
	}

	/**
	 * Insert a commande with one product line, fetched with lines.
	 *
	 * @param int   $fkProduct  Product id.
	 * @param float $buyPriceHt Initial buy_price_ht on the line.
	 * @return array{commande:Commande,line_id:int}
	 */
	private function insertCommandeWithLine(int $fkProduct, float $buyPriceHt): array
	{
		global $db;
		$socId = $this->firstThirdparty();
		$sql = 'INSERT INTO '.$db->prefix().'commande (entity, ref, ref_client, date_creation, fk_soc, fk_statut)';
		$sql .= " VALUES (1, 'VT25_CMD_".uniqid()."', '', NOW(), ".$socId.', 0)';
		$this->assertTrue((bool) $db->query($sql), 'Insert commande failed: '.$db->lasterror());
		$commandeId = (int) $db->last_insert_id($db->prefix().'commande');

		$sql = 'INSERT INTO '.$db->prefix().'commandedet';
		$sql .= ' (fk_commande, fk_product, label, description, qty, subprice, tva_tx, special_code, rang, product_type, buy_price_ht, fk_product_fournisseur_price)';
		$sql .= ' VALUES ('.$commandeId.', '.$fkProduct.", 'VT25', 'VT25', 1, 100, 20, 0, 1, 0, ".$buyPriceHt.', 12345)';
		$this->assertTrue((bool) $db->query($sql), 'Insert commandedet failed: '.$db->lasterror());
		$lineId = (int) $db->last_insert_id($db->prefix().'commandedet');

		$commande = new Commande($db);
		$this->assertGreaterThan(0, $commande->fetch($commandeId));
		$this->assertGreaterThanOrEqual(0, $commande->fetch_lines());
		return array('commande' => $commande, 'line_id' => $lineId);
	}

	/**
	 * Read clichaumeil_cost_source for a line.
	 *
	 * @param string $table  'propaldet' or 'commandedet'.
	 * @param int    $lineId Line rowid (fk_object in *_extrafields).
	 * @return string|null
	 */
	private function readCostSource(string $table, int $lineId): ?string
	{
		global $db;
		$resql = $db->query('SELECT clichaumeil_cost_source FROM '.$db->prefix().$table.'_extrafields WHERE fk_object = '.$lineId);
		if (!$resql) {
			return null;
		}
		$row = $db->fetch_object($resql);
		return $row ? $row->clichaumeil_cost_source : null;
	}

	/**
	 * Case 1: product cost_price = 12.34 → buy_price_ht=12.34, fk_fournprice NULL, source product_cost_price.
	 *
	 * @return void
	 */
	public function testPropalPositiveCostPrice(): void
	{
		global $db, $user;
		$fkProduct = $this->createProductWithCostPrice(12.34);
		$ctx = $this->insertPropalWithLine($fkProduct, 99.0);

		$service = new CliChaumeilCloneCostPriceService($db);
		$this->assertSame(0, $service->recalculateCloneLines($ctx['propal'], $user));

		$row = $db->fetch_object($db->query('SELECT buy_price_ht, fk_product_fournisseur_price FROM '.$db->prefix().'propaldet WHERE rowid='.$ctx['line_id']));
		$this->assertEquals(12.34, (float) $row->buy_price_ht);
		$this->assertNull($row->fk_product_fournisseur_price);
		$this->assertSame('product_cost_price', $this->readCostSource('propaldet', $ctx['line_id']));
	}

	/**
	 * Case 2: product cost_price = 0 → buy_price_ht=0, source product_cost_price_zeroed.
	 *
	 * @return void
	 */
	public function testPropalZeroCostPrice(): void
	{
		global $db, $user;
		$fkProduct = $this->createProductWithCostPrice(0.0);
		$ctx = $this->insertPropalWithLine($fkProduct, 99.0);

		$service = new CliChaumeilCloneCostPriceService($db);
		$this->assertSame(0, $service->recalculateCloneLines($ctx['propal'], $user));

		$row = $db->fetch_object($db->query('SELECT buy_price_ht, fk_product_fournisseur_price FROM '.$db->prefix().'propaldet WHERE rowid='.$ctx['line_id']));
		$this->assertEquals(0.0, (float) $row->buy_price_ht);
		$this->assertNull($row->fk_product_fournisseur_price);
		$this->assertSame('product_cost_price_zeroed', $this->readCostSource('propaldet', $ctx['line_id']));
	}

	/**
	 * Case 3: free line (no product) → buy price + fk_fournprice untouched, no extrafield written.
	 *
	 * @return void
	 */
	public function testPropalFreeLineUntouched(): void
	{
		global $db, $user;
		$ctx = $this->insertPropalWithLine(0, 42.0);

		$service = new CliChaumeilCloneCostPriceService($db);
		$this->assertSame(0, $service->recalculateCloneLines($ctx['propal'], $user));

		$row = $db->fetch_object($db->query('SELECT buy_price_ht, fk_product_fournisseur_price FROM '.$db->prefix().'propaldet WHERE rowid='.$ctx['line_id']));
		$this->assertEquals(42.0, (float) $row->buy_price_ht);
		$this->assertEquals(12345, (int) $row->fk_product_fournisseur_price);
		$this->assertNull($this->readCostSource('propaldet', $ctx['line_id']));
	}

	/**
	 * Case 4: commande client → same rules as proposal.
	 *
	 * @return void
	 */
	public function testCommandePositiveCostPrice(): void
	{
		global $db, $user;
		$fkProduct = $this->createProductWithCostPrice(15.50);
		$ctx = $this->insertCommandeWithLine($fkProduct, 88.0);

		$service = new CliChaumeilCloneCostPriceService($db);
		$this->assertSame(0, $service->recalculateCloneLines($ctx['commande'], $user));

		$row = $db->fetch_object($db->query('SELECT buy_price_ht, fk_product_fournisseur_price FROM '.$db->prefix().'commandedet WHERE rowid='.$ctx['line_id']));
		$this->assertEquals(15.50, (float) $row->buy_price_ht);
		$this->assertNull($row->fk_product_fournisseur_price);
		$this->assertSame('product_cost_price', $this->readCostSource('commandedet', $ctx['line_id']));
	}

	/**
	 * Case 5: line referencing a missing product → service returns -1 and records an error.
	 *
	 * @return void
	 */
	public function testMissingProductReturnsError(): void
	{
		global $db, $user;
		$resmax = $db->fetch_object($db->query('SELECT COALESCE(MAX(rowid),0)+1 AS bogus FROM '.$db->prefix().'product'));
		$bogus = (int) $resmax->bogus;
		$ctx = $this->insertPropalWithLine($bogus, 99.0);

		$service = new CliChaumeilCloneCostPriceService($db);
		$this->assertSame(-1, $service->recalculateCloneLines($ctx['propal'], $user));
		$this->assertNotEmpty($service->errors);
	}
}
