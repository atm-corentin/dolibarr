<?php
declare(strict_types=1);

require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Read and normalize the default proposal products configuration.
 */
class CliChaumeilPropalDefaultLineConfig
{
	/**
	 * Configuration key storing the CSV product IDs.
	 *
	 * @var string
	 */
	public const CONFIG_KEY = 'CLICHAUMEIL_DEFAULT_PROPAL_PRODUCT_ID';

	/**
	 * Database handler.
	 *
	 * @var DoliDB
	 */
	private DoliDB $db;

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
	 * Return configured product IDs as a normalized list.
	 *
	 * @return int[]
	 */
	public function getConfiguredProductIds(): array
	{
		$rawValue = trim(getDolGlobalString(self::CONFIG_KEY));
		if ($rawValue === '') {
			return array();
		}

		$normalizedIds = array();
		$rawIds = explode(',', $rawValue);

		foreach ($rawIds as $rawId) {
			$productId = (int) trim($rawId);
			if ($productId <= 0) {
				continue;
			}

			$normalizedIds[$productId] = $productId;
		}

		return array_values($normalizedIds);
	}

	/**
	 * Tell whether at least one configured product exists.
	 *
	 * @return bool
	 */
	public function hasConfiguredProducts(): bool
	{
		return count($this->getConfiguredProductIds()) > 0;
	}

	/**
	 * Load one configured product.
	 *
	 * The method does not log or emit user messages. Callers decide how to
	 * handle missing records to avoid duplicated logging.
	 *
	 * @param int $productId Product identifier.
	 * @return Product|null
	 */
	public function fetchConfiguredProductById(int $productId): ?Product
	{
		if ($productId <= 0) {
			return null;
		}

		$product = new Product($this->db);
		$fetchResult = $product->fetch($productId);
		if ($fetchResult <= 0) {
			return null;
		}

		return $product;
	}

	/**
	 * Load all configured products found in database.
	 *
	 * Missing products are silently skipped. Callers remain responsible for
	 * logging and user feedback.
	 *
	 * @return array<int,Product>
	 */
	public function fetchConfiguredProducts(): array
	{
		$products = array();

		foreach ($this->getConfiguredProductIds() as $productId) {
			$product = $this->fetchConfiguredProductById($productId);
			if ($product === null) {
				continue;
			}

			$products[$productId] = $product;
		}

		return $products;
	}
}
