<?php
declare(strict_types=1);

require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
require_once __DIR__ . '/CliChaumeilPropalDefaultLineConfig.class.php';

/**
 * Handle the default protected proposal lines business rules.
 */
class CliChaumeilPropalDefaultLineService
{
	/**
	 * Line extrafield key.
	 *
	 * @var string
	 */
	public const EXTRAFIELD_KEY = 'clichaumeil_default_inserted';

	/**
	 * Line extrafield runtime array key.
	 *
	 * @var string
	 */
	public const EXTRAFIELD_OPTION_KEY = 'options_clichaumeil_default_inserted';

	/**
	 * Permission module key.
	 *
	 * @var string
	 */
	public const RIGHT_MODULE = 'clichaumeil';

	/**
	 * Permission feature key.
	 *
	 * @var string
	 */
	public const RIGHT_FEATURE = 'propal_default_line';

	/**
	 * Permission action key.
	 *
	 * @var string
	 */
	public const RIGHT_ACTION = 'manage';

	/**
	 * Default injected quantity.
	 *
	 * @var int
	 */
	public const DEFAULT_QTY = 1;

	/**
	 * Database handler.
	 *
	 * @var DoliDB
	 */
	private DoliDB $db;

	/**
	 * Configuration reader.
	 *
	 * @var CliChaumeilPropalDefaultLineConfig
	 */
	private CliChaumeilPropalDefaultLineConfig $config;

	/**
	 * Constructor.
	 *
	 * @param DoliDB                                    $db     Database handler.
	 * @param CliChaumeilPropalDefaultLineConfig|null   $config Optional config reader.
	 */
	public function __construct(DoliDB $db, ?CliChaumeilPropalDefaultLineConfig $config = null)
	{
		$this->db = $db;
		$this->config = $config ?: new CliChaumeilPropalDefaultLineConfig($db);
	}

	/**
	 * Check whether creation flow should attempt automatic injections.
	 *
	 * @param Propal $propal Proposal object.
	 * @return bool
	 */
	public function shouldInjectOnCreate(Propal $propal): bool
	{
		if ((int) $propal->status !== Propal::STATUS_DRAFT) {
			return false;
		}

		if ((int) $propal->id <= 0) {
			return false;
		}

		return $this->config->hasConfiguredProducts();
	}

	/**
	 * Inject all configured products missing from the proposal.
	 *
	 * Errors are logged once and surfaced as warnings without aborting the
	 * surrounding create/clone flow.
	 *
	 * @param Propal $propal Proposal object.
	 * @param User   $user   Current user.
	 * @return array<int,string|int>
	 */
	public function injectConfiguredLines(Propal $propal, User $user): array
	{
		$results = array();
		$configuredIds = $this->config->getConfiguredProductIds();
		$configuredProducts = $this->config->fetchConfiguredProducts();

		foreach ($configuredIds as $productId) {
			if (!isset($configuredProducts[$productId])) {
				$this->notifyWarning(
					'CLICHAUMEIL_DEFAULT_PROPAL_LINE_MISSING_PRODUCT',
					array($productId),
					__METHOD__ . ' missing configured product id=' . $productId . ' propal_id=' . (int) $propal->id,
					LOG_WARNING
				);
				$results[$productId] = 'missing_product';
				continue;
			}

			if ($this->hasAnyLineForProduct($propal, $productId)) {
				$results[$productId] = 'already_present';
				continue;
			}

			$results[$productId] = $this->injectSingleConfiguredLine($propal, $configuredProducts[$productId], $user);
		}

		return $results;
	}

	/**
	 * Synchronize protected lines after clone.
	 *
	 * @param Propal $source Source proposal.
	 * @param Propal $clone  Cloned proposal.
	 * @param User   $user   Current user.
	 * @return array<int,string|int>
	 */
	public function markConfiguredCloneLines(Propal $source, Propal $clone, User $user): array
	{
		$results = array();
		$configuredIds = $this->config->getConfiguredProductIds();
		$configuredProducts = $this->config->fetchConfiguredProducts();

		foreach ($configuredIds as $productId) {
			if ($this->sourceHasProduct($source, $productId)) {
				$cloneLine = $this->findBestCloneCandidateLine($clone, $productId);
				if ($cloneLine === null) {
					$this->notifyWarning(
						'CLICHAUMEIL_DEFAULT_PROPAL_LINE_CLONE_MARK_FAILED',
						array($productId),
						__METHOD__ . ' clone candidate line not found for product id=' . $productId . ' clone_id=' . (int) $clone->id,
						LOG_WARNING
					);
					$results[$productId] = 'clone_line_not_found';
					continue;
				}

				$markResult = $this->markProtectedLine($cloneLine, $user);
				if ($markResult < 0) {
					$results[$productId] = 'clone_mark_failed';
					continue;
				}

				$results[$productId] = 'clone_marked';
				continue;
			}

			if (!isset($configuredProducts[$productId])) {
				$this->notifyWarning(
					'CLICHAUMEIL_DEFAULT_PROPAL_LINE_MISSING_PRODUCT',
					array($productId),
					__METHOD__ . ' missing configured product during clone id=' . $productId . ' clone_id=' . (int) $clone->id,
					LOG_WARNING
				);
				$results[$productId] = 'missing_product';
				continue;
			}

			if ($this->hasAnyLineForProduct($clone, $productId)) {
				$results[$productId] = 'already_present';
				continue;
			}

			$results[$productId] = $this->injectSingleConfiguredLine($clone, $configuredProducts[$productId], $user);
		}

		return $results;
	}

	/**
	 * Inject one configured product line.
	 *
	 * @param Propal  $propal  Proposal object.
	 * @param Product $product Product to inject.
	 * @param User    $user    Current user.
	 * @return int|string
	 */
	public function injectSingleConfiguredLine(Propal $propal, Product $product, User $user)
	{
		global $mysoc;

		if (!is_object($propal->thirdparty) && method_exists($propal, 'fetch_thirdparty')) {
			$propal->fetch_thirdparty();
		}

		if (!is_object($mysoc) || !is_object($propal->thirdparty)) {
			$this->notifyWarning(
				'CLICHAUMEIL_DEFAULT_PROPAL_LINE_INJECT_FAILED',
				array($product->ref),
				__METHOD__ . ' missing seller or buyer context for product id=' . (int) $product->id . ' propal_id=' . (int) $propal->id,
				LOG_ERR
			);
			return 'inject_failed';
		}

		$buyingPriceData = $this->resolveBuyingPriceData($propal, $product, $user);
		$lineRank = count((array) $propal->lines) + 1;

		$addLineResult = $propal->addline(
			(string) $product->description,
			(float) $product->price,
			(float) self::DEFAULT_QTY,
			(string) get_default_tva($mysoc, $propal->thirdparty, $product->id),
			0.0,
			0.0,
			(int) $product->id,
			0.0,
			'HT',
			0,
			0,
			(int) $product->type,
			$lineRank,
			0,
			0,
			(int) $buyingPriceData['fk_fournprice'],
			$buyingPriceData['pa_ht']
		);

		if ($addLineResult <= 0) {
			$this->notifyWarning(
				'CLICHAUMEIL_DEFAULT_PROPAL_LINE_INJECT_FAILED',
				array($product->ref),
				__METHOD__ . ' addline failed for product id=' . (int) $product->id . ' propal_id=' . (int) $propal->id . ' error=' . $propal->error,
				LOG_ERR
			);
			return 'inject_failed';
		}

		$insertedLine = $this->findLineById($propal, (int) $addLineResult);
		if ($insertedLine === null) {
			$reloadResult = $propal->fetch((int) $propal->id);
			if ($reloadResult > 0) {
				$propal->fetch_thirdparty();
				$insertedLine = $this->findLineById($propal, (int) $addLineResult);
			}
		}

		if ($insertedLine === null) {
			$this->notifyWarning(
				'CLICHAUMEIL_DEFAULT_PROPAL_LINE_INSERT_LOOKUP_FAILED',
				array((string) $product->ref),
				__METHOD__ . ' unable to reload inserted line id=' . (int) $addLineResult . ' propal_id=' . (int) $propal->id,
				LOG_ERR
			);
			return 'line_not_found_after_insert';
		}

		$markResult = $this->markProtectedLine($insertedLine, $user);
		if ($markResult < 0) {
			return 'mark_failed';
		}

		return (int) $insertedLine->id;
	}

	/**
	 * Resolve buying price defaults as close as possible to the native manual line creation flow.
	 *
	 * @param Propal  $propal  Proposal object.
	 * @param Product $product Product object.
	 * @param User    $user    Current user.
	 * @return array{fk_fournprice:int,pa_ht:float}
	 */
	private function resolveBuyingPriceData(Propal $propal, Product $product, User $user): array
	{
		$resolvedData = array(
			'fk_fournprice' => 0,
			'pa_ht' => 0.0,
		);

		if (!isModEnabled('margin') || !(bool) $user->hasRight('margins', 'creer')) {
			return $resolvedData;
		}

		$buyPrice = $propal->defineBuyPrice((float) $product->price, 0.0, (int) $product->id);
		if (!is_numeric($buyPrice) || (float) $buyPrice < 0) {
			dol_syslog(
				__METHOD__ . ' unable to resolve buy price for product id=' . (int) $product->id . ' propal_id=' . (int) $propal->id,
				LOG_WARNING
			);
			return $resolvedData;
		}

		$resolvedData['pa_ht'] = (float) price2num((string) $buyPrice, 'MU');

		if (!in_array(getDolGlobalString('MARGIN_TYPE'), array('1', 'pmp', 'costprice'), true)) {
			return $resolvedData;
		}

		$productFournisseur = new ProductFournisseur($this->db);
		$findMinPriceResult = $productFournisseur->find_min_price_product_fournisseur((int) $product->id);
		if ($findMinPriceResult < 0) {
			dol_syslog(
				__METHOD__ . ' unable to resolve supplier price for product id=' . (int) $product->id . ' error=' . $productFournisseur->error,
				LOG_WARNING
			);
			return $resolvedData;
		}

		if ($findMinPriceResult <= 0) {
			return $resolvedData;
		}

		$minSupplierUnitPrice = (float) price2num((string) $productFournisseur->fourn_unitprice, 'MU');
		$mustLinkSupplierPrice = getDolGlobalString('MARGIN_TYPE') === '1'
			|| abs($resolvedData['pa_ht'] - $minSupplierUnitPrice) < 0.000001;

		if ($mustLinkSupplierPrice) {
			$resolvedData['fk_fournprice'] = (int) $productFournisseur->product_fourn_price_id;
			$resolvedData['pa_ht'] = $minSupplierUnitPrice;
		}

		return $resolvedData;
	}

	/**
	 * Tell whether the source proposal contains the given product.
	 *
	 * @param Propal $propal    Proposal object.
	 * @param int    $fkProduct Product identifier.
	 * @return bool
	 */
	public function sourceHasProduct(Propal $propal, int $fkProduct): bool
	{
		return count($this->findLinesByProductId($propal, $fkProduct)) > 0;
	}

	/**
	 * Return all lines matching a product identifier.
	 *
	 * @param Propal $propal    Proposal object.
	 * @param int    $fkProduct Product identifier.
	 * @return CommonObjectLine[]
	 */
	public function findLinesByProductId(Propal $propal, int $fkProduct): array
	{
		$matchingLines = array();

		foreach ((array) $propal->lines as $line) {
			if ((int) $line->fk_product !== $fkProduct) {
				continue;
			}

			$matchingLines[] = $line;
		}

		return $matchingLines;
	}

	/**
	 * Return the best candidate line in the clone for a product.
	 *
	 * @param Propal $clone     Cloned proposal.
	 * @param int    $fkProduct Product identifier.
	 * @return CommonObjectLine|null
	 */
	public function findBestCloneCandidateLine(Propal $clone, int $fkProduct): ?CommonObjectLine
	{
		$candidates = $this->findLinesByProductId($clone, $fkProduct);
		if (empty($candidates)) {
			return null;
		}

		foreach ($candidates as $candidate) {
			if ($this->isProtectedLine($candidate)) {
				return $candidate;
			}
		}

		return $candidates[0];
	}

	/**
	 * Persist the protection marker on one line.
	 *
	 * @param CommonObjectLine $line Line object.
	 * @param User             $user Current user.
	 * @return int
	 */
	public function markProtectedLine(CommonObjectLine $line, User $user): int
	{
		$line->fetch_optionals();
		$line->array_options[self::EXTRAFIELD_OPTION_KEY] = 1;

		$updateResult = $line->updateExtraField(self::EXTRAFIELD_KEY, '', $user);
		if ($updateResult <= 0) {
			$this->notifyWarning(
				'CLICHAUMEIL_DEFAULT_PROPAL_LINE_CLONE_MARK_FAILED',
				array((string) $line->id),
				__METHOD__ . ' updateExtraField failed for line id=' . (int) $line->id . ' error=' . $line->error,
				LOG_ERR
			);
			return -1;
		}

		$line->array_options[self::EXTRAFIELD_OPTION_KEY] = 1;

		return 1;
	}

	/**
	 * Tell whether a line is protected.
	 *
	 * @param CommonObjectLine $line Line object.
	 * @return bool
	 */
	public function isProtectedLine(CommonObjectLine $line): bool
	{
		$currentValue = $line->array_options[self::EXTRAFIELD_OPTION_KEY] ?? null;
		return ((int) $currentValue) === 1;
	}

	/**
	 * Tell whether the user can manage protected lines.
	 *
	 * @param User $user Current user.
	 * @return bool
	 */
	public function canManageProtectedLine(User $user): bool
	{
		return (bool) $user->hasRight(self::RIGHT_MODULE, self::RIGHT_FEATURE, self::RIGHT_ACTION);
	}

	/**
	 * Guard a delete attempt on one line.
	 *
	 * @param Propal $propal Proposal object.
	 * @param int    $lineId Target line identifier.
	 * @param User   $user   Current user.
	 * @return bool
	 */
	public function guardDeleteLine(Propal $propal, int $lineId, User $user): bool
	{
		$line = $this->findLineById($propal, $lineId);
		return $this->guardLineAction(
			$line,
			$user,
			'CLICHAUMEIL_DEFAULT_PROPAL_LINE_DELETE_FORBIDDEN',
			'delete forbidden on protected line id=' . $lineId . ' propal_id=' . (int) $propal->id . ' user_id=' . (int) $user->id
		);
	}

	/**
	 * Guard an edit attempt on one line.
	 *
	 * @param Propal $propal Proposal object.
	 * @param int    $lineId Target line identifier.
	 * @param User   $user   Current user.
	 * @return bool
	 */
	public function guardEditLine(Propal $propal, int $lineId, User $user): bool
	{
		$line = $this->findLineById($propal, $lineId);
		return $this->guardLineAction(
			$line,
			$user,
			'CLICHAUMEIL_DEFAULT_PROPAL_LINE_EDIT_FORBIDDEN',
			'edit forbidden on protected line id=' . $lineId . ' propal_id=' . (int) $propal->id . ' user_id=' . (int) $user->id
		);
	}

	/**
	 * Guard a quick-price attempt on one line.
	 *
	 * @param Propal $propal Proposal object.
	 * @param int    $lineId Target line identifier.
	 * @param User   $user   Current user.
	 * @return bool
	 */
	public function guardQuickCustomerPrice(Propal $propal, int $lineId, User $user): bool
	{
		$line = $this->findLineById($propal, $lineId);
		return $this->guardLineAction(
			$line,
			$user,
			'CLICHAUMEIL_DEFAULT_PROPAL_LINE_QUICK_PRICE_FORBIDDEN',
			'quick price forbidden on protected line id=' . $lineId . ' propal_id=' . (int) $propal->id . ' user_id=' . (int) $user->id
		);
	}

	/**
	 * Guard a delete attempt directly on a line object.
	 *
	 * @param CommonObjectLine|null $line            Target line object.
	 * @param User                  $user            Current user.
	 * @param bool                  $emitUserMessage True to emit a UI error.
	 * @return bool
	 */
	public function guardDeleteLineObject(?CommonObjectLine $line, User $user, bool $emitUserMessage = true): bool
	{
		return $this->guardLineAction(
			$line,
			$user,
			'CLICHAUMEIL_DEFAULT_PROPAL_LINE_DELETE_FORBIDDEN',
			'delete forbidden on protected line object id=' . (int) ($line->id ?? 0) . ' user_id=' . (int) $user->id,
			$emitUserMessage
		);
	}

	/**
	 * Guard an edit attempt directly on a line object.
	 *
	 * @param CommonObjectLine|null $line            Target line object.
	 * @param User                  $user            Current user.
	 * @param bool                  $emitUserMessage True to emit a UI error.
	 * @return bool
	 */
	public function guardEditLineObject(?CommonObjectLine $line, User $user, bool $emitUserMessage = true): bool
	{
		return $this->guardLineAction(
			$line,
			$user,
			'CLICHAUMEIL_DEFAULT_PROPAL_LINE_EDIT_FORBIDDEN',
			'edit forbidden on protected line object id=' . (int) ($line->id ?? 0) . ' user_id=' . (int) $user->id,
			$emitUserMessage
		);
	}

	/**
	 * Return all protected line identifiers for one proposal.
	 *
	 * @param Propal $propal Proposal object.
	 * @return int[]
	 */
	public function getProtectedLineIds(Propal $propal): array
	{
		$lineIds = array();

		foreach ((array) $propal->lines as $line) {
			if (!$this->isProtectedLine($line)) {
				continue;
			}

			$lineIds[] = (int) $line->id;
		}

		return $lineIds;
	}

	/**
	 * Find one line by identifier inside the already loaded proposal lines.
	 *
	 * @param Propal $propal Proposal object.
	 * @param int    $lineId Line identifier.
	 * @return CommonObjectLine|null
	 */
	private function findLineById(Propal $propal, int $lineId): ?CommonObjectLine
	{
		foreach ((array) $propal->lines as $line) {
			if ((int) $line->id !== $lineId) {
				continue;
			}

			return $line;
		}

		return null;
	}

	/**
	 * Tell whether any line already uses the given product.
	 *
	 * @param Propal $propal    Proposal object.
	 * @param int    $productId Product identifier.
	 * @return bool
	 */
	private function hasAnyLineForProduct(Propal $propal, int $productId): bool
	{
		return count($this->findLinesByProductId($propal, $productId)) > 0;
	}

	/**
	 * Apply the protected line policy for one line action.
	 *
	 * @param CommonObjectLine|null $line            Target line object.
	 * @param User                  $user            Current user.
	 * @param string                $translationKey  Translation key used for UI feedback.
	 * @param string                $logMessage      Log message.
	 * @param bool                  $emitUserMessage True to emit a UI error message.
	 * @return bool
	 */
	private function guardLineAction(?CommonObjectLine $line, User $user, string $translationKey, string $logMessage, bool $emitUserMessage = true): bool
	{
		if ($line === null) {
			return true;
		}

		if (!$this->isProtectedLine($line)) {
			return true;
		}

		if ($this->canManageProtectedLine($user)) {
			return true;
		}

		if ($emitUserMessage) {
			$this->notifyError($translationKey, array((string) $line->id), __METHOD__ . ' ' . $logMessage);
		} else {
			dol_syslog(__METHOD__ . ' ' . $logMessage, LOG_WARNING);
		}

		return false;
	}

	/**
	 * Emit one warning to UI and logs.
	 *
	 * @param string $translationKey Translation key.
	 * @param array  $translationArgs Translation arguments.
	 * @param string $logMessage Log message.
	 * @param int    $logLevel Log level.
	 * @return void
	 */
	private function notifyWarning(string $translationKey, array $translationArgs, string $logMessage, int $logLevel): void
	{
		global $langs;

		$message = $langs->trans($translationKey, ...$translationArgs);
		setEventMessages($message, null, 'warnings');
		dol_syslog($logMessage, $logLevel);
	}

	/**
	 * Emit one blocking error to UI and logs.
	 *
	 * @param string $translationKey Translation key.
	 * @param array  $translationArgs Translation arguments.
	 * @param string $logMessage Log message.
	 * @return void
	 */
	private function notifyError(string $translationKey, array $translationArgs, string $logMessage): void
	{
		global $langs;

		$message = $langs->trans($translationKey, ...$translationArgs);
		setEventMessages($message, null, 'errors');
		dol_syslog($logMessage, LOG_WARNING);
	}
}
