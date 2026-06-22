<?php
declare(strict_types=1);

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr> */

require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

/**
 * VT-25 — Recompute cloned-line buy price (PA) from the product cost price.
 *
 * For each cloned line carrying a product (fk_product > 0): buy_price_ht becomes
 * product.cost_price when strictly positive, else 0; fk_product_fournisseur_price
 * is cleared; the hidden line extrafield clichaumeil_cost_source records the origin.
 * No PMP / minimum-supplier / BOM / defineBuyPrice() fallback. Free lines are left
 * untouched. Designed to run inside the core clone transaction: returning -1 makes
 * the createFrom hook roll back the whole clone.
 */
class CliChaumeilCloneCostPriceService
{
	/** @var string Origin when the product cost price was strictly positive. */
	public const SOURCE_PRODUCT_COST_PRICE = 'product_cost_price';

	/** @var string Origin when the product cost price was missing or zero. */
	public const SOURCE_PRODUCT_COST_PRICE_ZEROED = 'product_cost_price_zeroed';

	/** @var string Hidden line extrafield attribute name. */
	public const EXTRAFIELD_COST_SOURCE = 'clichaumeil_cost_source';

	/** @var string Hidden line extrafield array_options key. */
	public const EXTRAFIELD_COST_SOURCE_OPTION = 'options_clichaumeil_cost_source';

	/** @var DoliDB Database handler. */
	private $db;

	/** @var string[] Error markers collected for the caller (hook -> hookmanager). */
	public $errors = array();

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Recompute the buy price of every product line of a freshly cloned document.
	 *
	 * @param CommonObject $clone Cloned Propal or Commande (post-insertion).
	 * @param User         $user  Author of the extrafield write.
	 * @return int 0 on success (incl. unsupported type / missing id), -1 on SQL or missing-product error.
	 */
	public function recalculateCloneLines(CommonObject $clone, User $user): int
	{
		global $langs;

		$table = $this->resolveLineTable($clone);
		if ($table === '') {
			return 0;
		}

		if (empty($clone->id)) {
			dol_syslog(__METHOD__ . ' clone without id, nothing to do', LOG_WARNING);
			return 0;
		}

		$langs->load('clichaumeil@clichaumeil');

		// Reload to get persisted line rowids and array_options.
		if ($clone->fetch($clone->id) <= 0) {
			$this->errors[] = $langs->trans('CLICHAUMEIL_CLONE_COST_RECALC_FAILED');
			dol_syslog(__METHOD__ . ' clone reload failed for ' . get_class($clone) . ' id=' . (int) $clone->id, LOG_ERR);
			return -1;
		}
		if ($clone->fetch_lines() < 0) {
			$this->errors[] = $langs->trans('CLICHAUMEIL_CLONE_COST_RECALC_FAILED');
			dol_syslog(__METHOD__ . ' fetch_lines failed for ' . get_class($clone) . ' id=' . (int) $clone->id, LOG_ERR);
			return -1;
		}

		if (!is_array($clone->lines) || empty($clone->lines)) {
			return 0;
		}

		$productCache = array();

		foreach ($clone->lines as $line) {
			$fkProduct = (int) $line->fk_product;
			if ($fkProduct <= 0) {
				continue;
			}

			if (!isset($productCache[$fkProduct])) {
				$product = new Product($this->db);
				$resfetch = $product->fetch($fkProduct);
				if ($resfetch <= 0) {
					$this->errors[] = $langs->trans('CLICHAUMEIL_CLONE_COST_PRODUCT_NOT_FOUND');
					$reason = ($resfetch === 0) ? 'not found' : 'SQL error (' . $this->db->lasterror() . ')';
					dol_syslog(__METHOD__ . ' product ' . $reason . ': doc=' . get_class($clone) . ' id=' . (int) $clone->id
						. ' line=' . (int) $line->id . ' fk_product=' . $fkProduct, LOG_ERR);
					return -1;
				}
				$productCache[$fkProduct] = $product;
			}
			$product = $productCache[$fkProduct];

			if ((float) $product->cost_price > 0) {
				$buyPrice = price2num($product->cost_price, 'MU');
				$source   = self::SOURCE_PRODUCT_COST_PRICE;
			} else {
				$buyPrice = 0;
				$source   = self::SOURCE_PRODUCT_COST_PRICE_ZEROED;
			}

			$sql = 'UPDATE ' . $this->db->prefix() . $table;
			$sql .= ' SET buy_price_ht = ' . ((float) $buyPrice) . ', fk_product_fournisseur_price = NULL';
			$sql .= ' WHERE rowid = ' . ((int) $line->id);
			if (!$this->db->query($sql)) {
				$this->errors[] = $langs->trans('CLICHAUMEIL_CLONE_COST_RECALC_FAILED');
				dol_syslog(__METHOD__ . ' SQL update failed: doc=' . get_class($clone) . ' id=' . (int) $clone->id
					. ' line=' . (int) $line->id . ' error=' . $this->db->lasterror(), LOG_ERR);
				return -1;
			}

			// No trigger fired (matches markProtectedLine convention): the origin is traced by
			// the extrafield value, and clone post-processing must not spawn line triggers.
			$line->array_options[self::EXTRAFIELD_COST_SOURCE_OPTION] = $source;
			if ($line->updateExtraField(self::EXTRAFIELD_COST_SOURCE, '', $user) <= 0) {
				$this->errors[] = $langs->trans('CLICHAUMEIL_CLONE_COST_RECALC_FAILED');
				dol_syslog(__METHOD__ . ' updateExtraField failed: doc=' . get_class($clone) . ' id=' . (int) $clone->id
					. ' line=' . (int) $line->id, LOG_ERR);
				return -1;
			}
		}

		return 0;
	}

	/**
	 * Resolve the line table name for a supported cloned document.
	 *
	 * @param CommonObject $clone Cloned object.
	 * @return string 'propaldet', 'commandedet', or '' if unsupported.
	 */
	private function resolveLineTable(CommonObject $clone): string
	{
		if ($clone instanceof Propal) {
			return 'propaldet';
		}
		if ($clone instanceof Commande) {
			return 'commandedet';
		}
		return '';
	}
}
