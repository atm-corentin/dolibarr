<?php
/* Copyright (C) 2026 ATM Consulting
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    class/SupplierPriceSync/Antalis/AntalisCustomerPricesConnector.php
 * \ingroup clichaumeil
 * \brief   ANTALIS SOAP connector for the customerPricesCheck service.
 */

declare(strict_types=1);

require_once __DIR__ . '/../SupplierPriceSyncConstants.php';
require_once __DIR__ . '/../Contract/SupplierPriceConnectorInterface.php';
require_once __DIR__ . '/../ValueObject/SupplierProductRequest.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceTier.php';
require_once __DIR__ . '/../ValueObject/SupplierProductPriceGrid.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceGridFetchResult.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceSyncIssue.php';
require_once __DIR__ . '/AntalisConnectorConfig.php';
require_once __DIR__ . '/AntalisOrderUnitMapper.php';

/**
 * Connects to the ANTALIS SOAP "customerPricesCheck" service and normalises its answers.
 *
 * One request = one product; the service returns its full price grid (all thresholds).
 * The SOAP call lives in callSoap(); the pure normalisation lives in
 * normalizeResponse() so it can be unit-tested against captured payloads.
 */
final class AntalisCustomerPricesConnector implements SupplierPriceConnectorInterface
{
	/** @var string Bundled WSDL file shipped with the module. */
	private const WSDL_FILE = __DIR__ . '/antalis-customerprices.wsdl';

	/** @var string Mandatory enquiry type (customer prices). */
	private const ENQUIRY_TYPE = 'CPR';

	/** @var int SOAP connection timeout in seconds. */
	private const SOAP_CONNECTION_TIMEOUT = 30;

	/** @var AntalisConnectorConfig|null Connector configuration (null in test seam). */
	private ?AntalisConnectorConfig $config;

	/** @var AntalisOrderUnitMapper Unit mapper (API code => Dolibarr label). */
	private AntalisOrderUnitMapper $orderUnitMapper;

	/** @var SoapClient|null Lazily built SOAP client, reused across chunks. */
	private ?SoapClient $client = null;

	/**
	 * @param AntalisConnectorConfig|null $config          Connector configuration.
	 * @param AntalisOrderUnitMapper      $orderUnitMapper Unit mapper.
	 */
	public function __construct(?AntalisConnectorConfig $config, AntalisOrderUnitMapper $orderUnitMapper)
	{
		$this->config = $config;
		$this->orderUnitMapper = $orderUnitMapper;
	}

	/**
	 * Build a connector usable for normalisation tests (no SOAP config).
	 *
	 * @param AntalisOrderUnitMapper $orderUnitMapper Unit mapper.
	 * @return self
	 */
	public static function forTesting(AntalisOrderUnitMapper $orderUnitMapper): self
	{
		return new self(null, $orderUnitMapper);
	}

	/**
	 * Return the supplier code handled by this connector.
	 *
	 * @return string
	 */
	public function getCode(): string
	{
		return SupplierPriceSyncConstants::SUPPLIER_ANTALIS;
	}

	/**
	 * Return the recommended batch size.
	 *
	 * @return int
	 */
	public function getRecommendedBatchSize(): int
	{
		return SupplierPriceSyncConstants::DEFAULT_ANTALIS_BATCH_SIZE;
	}

	/**
	 * customerPricesCheck returns the full grid: tiers are authoritative.
	 *
	 * @return bool
	 */
	public function supportsTierDiscovery(): bool
	{
		return true;
	}

	/**
	 * Fetch the full price grids for a chunk of product requests.
	 *
	 * @param SupplierProductRequest[] $products Product requests of a single chunk.
	 * @return SupplierPriceGridFetchResult
	 * @throws RuntimeException When the connector has no configuration.
	 */
	public function fetchPriceGrids(array $products): SupplierPriceGridFetchResult
	{
		if ($this->config === null) {
			throw new RuntimeException('AntalisCustomerPricesConnector used without configuration');
		}

		$detailRows = array();
		$lineMap = array();
		$lineNr = 0;
		foreach ($products as $product) {
			$lineNr++;
			$detailRows[] = array('lineNr' => $lineNr, 'productID' => $product->supplierRef);
			$lineMap[$lineNr] = $product;
		}

		if ($detailRows === array()) {
			return new SupplierPriceGridFetchResult(array(), array(), false);
		}

		try {
			$response = $this->callSoap($detailRows);
		} catch (SoapFault $fault) {
			dol_syslog('AntalisCustomerPricesConnector::fetchPriceGrids SOAP fault: ' . $fault->getMessage(), LOG_ERR);
			$issue = new SupplierPriceSyncIssue(
				SupplierPriceSyncIssue::SEVERITY_ERROR,
				SupplierPriceSyncConstants::ISSUE_API_UNAVAILABLE,
				$fault->getMessage()
			);

			return new SupplierPriceGridFetchResult(array(), array($issue), true);
		}

		return $this->normalizeResponse($response, $lineMap);
	}

	/**
	 * Perform the SOAP customerPricesCheck call.
	 *
	 * @param array<int,array<string,mixed>> $detailRows Built input detail rows.
	 * @return object SOAP response object.
	 * @throws SoapFault When the SOAP transport or service fails.
	 */
	private function callSoap(array $detailRows): object
	{
		$input = array(
			'enquiryType' => self::ENQUIRY_TYPE,
			'userCode' => $this->config->getUserCode(),
			'customerID' => $this->config->getCustomerId(),
			'AntalisDeliveryAddressID' => $this->config->getDeliveryAddressId(),
			'detailRow' => $detailRows,
		);

		return $this->soapClient()->customerPricesCheck($input);
	}

	/**
	 * Build (once) and return the SOAP client, reused across chunks.
	 *
	 * Avoids re-parsing the WSDL on every batch of a large catalogue.
	 *
	 * @return SoapClient
	 */
	private function soapClient(): SoapClient
	{
		if ($this->client === null) {
			$this->client = new SoapClient(self::WSDL_FILE, array(
				'login' => $this->config->getHttpLogin(),
				'password' => $this->config->getHttpPassword(),
				'location' => $this->config->getBaseUrl(),
				'trace' => 0,
				'exceptions' => true,
				'soap_version' => SOAP_1_1,
				'cache_wsdl' => WSDL_CACHE_MEMORY,
				'connection_timeout' => self::SOAP_CONNECTION_TIMEOUT,
			));
		}

		return $this->client;
	}

	/**
	 * Normalise a SOAP response into product grids and issues.
	 *
	 * @param object                            $response SOAP response object.
	 * @param array<int,SupplierProductRequest> $lineMap  Map lineNr => product request.
	 * @return SupplierPriceGridFetchResult
	 */
	private function normalizeResponse(object $response, array $lineMap): SupplierPriceGridFetchResult
	{
		$grids = array();
		$issues = array();
		$seenLines = array();

		$headerError = isset($response->errorID) ? (string) $response->errorID : '';
		if ($headerError === SupplierPriceSyncConstants::API_BACKEND_DOWN) {
			$issues[] = new SupplierPriceSyncIssue(
				SupplierPriceSyncIssue::SEVERITY_ERROR,
				SupplierPriceSyncConstants::ISSUE_API_UNAVAILABLE,
				$headerError
			);

			return new SupplierPriceGridFetchResult(array(), $issues, true);
		}

		$detailRows = $this->toArray($response->detailRow ?? array());
		foreach ($detailRows as $row) {
			$rowLineNr = isset($row->lineNr) ? (int) $row->lineNr : 0;
			if (!isset($lineMap[$rowLineNr])) {
				continue;
			}
			$product = $lineMap[$rowLineNr];
			$seenLines[$rowLineNr] = true;
			$errorId = isset($row->errorID) ? (string) $row->errorID : '';

			switch ($errorId) {
				case SupplierPriceSyncConstants::API_OK:
					$grid = $this->buildFoundGrid($row, $product, $issues);
					if ($grid !== null) {
						$grids[] = $grid;
					}
					break;

				case SupplierPriceSyncConstants::API_NOT_AVAILABLE:
					$grids[] = SupplierProductPriceGrid::absent($product->supplierRef);
					break;

				case SupplierPriceSyncConstants::API_UNKNOWN_PRODUCT:
					$issues[] = $this->productIssue(
						SupplierPriceSyncConstants::ISSUE_REFERENCE_NOT_FOUND,
						$product
					);
					$grids[] = SupplierProductPriceGrid::error($product->supplierRef);
					break;

				case SupplierPriceSyncConstants::API_BACKEND_DOWN:
					$issues[] = new SupplierPriceSyncIssue(
						SupplierPriceSyncIssue::SEVERITY_ERROR,
						SupplierPriceSyncConstants::ISSUE_API_UNAVAILABLE,
						$errorId
					);

					return new SupplierPriceGridFetchResult($grids, $issues, true);

				default:
					$issues[] = $this->productIssue(
						SupplierPriceSyncConstants::ISSUE_API_LINE_ERROR,
						$product
					);
					$grids[] = SupplierProductPriceGrid::error($product->supplierRef);
					break;
			}
		}

		foreach ($lineMap as $lineNr => $product) {
			if (!isset($seenLines[$lineNr])) {
				$issues[] = $this->productIssue(SupplierPriceSyncConstants::ISSUE_API_LINE_ERROR, $product);
				$grids[] = SupplierProductPriceGrid::error($product->supplierRef);
			}
		}

		return new SupplierPriceGridFetchResult($grids, $issues, false);
	}

	/**
	 * Build a found grid from an OK detail row, filtering out unusable thresholds.
	 *
	 * Returns null (with an issue appended) when no usable tier remains, so the
	 * service performs no mutation on a product whose negotiated price is missing.
	 *
	 * @param object                   $row     SOAP detail row (errorID = 00).
	 * @param SupplierProductRequest  $product Matching product request.
	 * @param SupplierPriceSyncIssue[] $issues  Issues accumulator (by reference).
	 * @return SupplierProductPriceGrid|null
	 */
	private function buildFoundGrid(object $row, SupplierProductRequest $product, array &$issues): ?SupplierProductPriceGrid
	{
		$tiers = array();
		foreach ($this->toArray($row->threshold ?? array()) as $threshold) {
			$quantity = isset($threshold->thresholdQty) ? (float) $threshold->thresholdQty : 0.0;
			$personalUnitPrice = isset($threshold->personalUnitPrice) ? (float) $threshold->personalUnitPrice : 0.0;
			$personalPriceQty = isset($threshold->personalPriceQty) ? (float) $threshold->personalPriceQty : 0.0;
			$apiUnit = isset($threshold->thresholdQtyUnit) ? (string) $threshold->thresholdQtyUnit : '';
			$personalPriceUnit = isset($threshold->personalPriceUnit) ? (string) $threshold->personalPriceUnit : '';

			if ($personalUnitPrice <= 0.0 || $personalPriceQty <= 0.0) {
				$issues[] = $this->productIssue(SupplierPriceSyncConstants::ISSUE_MISSING_PERSONAL_PRICE, $product);
				continue;
			}

			// The normalised price is "per personalPriceUnit" but is attached to a line
			// whose quantity is in thresholdQtyUnit. If those differ, the price would be
			// expressed in the wrong unit: skip the tier rather than write a wrong price.
			if ($personalPriceUnit !== '' && strcasecmp(trim($personalPriceUnit), trim($apiUnit)) !== 0) {
				$issues[] = $this->productIssue(SupplierPriceSyncConstants::ISSUE_UNIT_MISMATCH, $product);
				continue;
			}

			$normalizedUnitPrice = $personalUnitPrice / $personalPriceQty;
			if ($normalizedUnitPrice < 0.0) {
				$issues[] = $this->productIssue(SupplierPriceSyncConstants::ISSUE_NEGATIVE_PRICE, $product);
				continue;
			}

			$label = $this->orderUnitMapper->dolibarrLabel($apiUnit);
			if ($label === null) {
				$issues[] = $this->productIssue(SupplierPriceSyncConstants::ISSUE_UNMAPPED_ORDER_UNIT, $product);
				$label = '';
			}

			$tiers[] = new SupplierPriceTier($quantity, $label, $normalizedUnitPrice);
		}

		if ($tiers === array()) {
			return null;
		}

		return SupplierProductPriceGrid::found($product->supplierRef, $tiers);
	}

	/**
	 * Build a per-product issue (warning for unit/price gaps, error otherwise).
	 *
	 * @param string                  $code    Issue code.
	 * @param SupplierProductRequest $product Product concerned.
	 * @return SupplierPriceSyncIssue
	 */
	private function productIssue(string $code, SupplierProductRequest $product): SupplierPriceSyncIssue
	{
		$warnings = array(
			SupplierPriceSyncConstants::ISSUE_UNMAPPED_ORDER_UNIT,
			SupplierPriceSyncConstants::ISSUE_MISSING_PERSONAL_PRICE,
			SupplierPriceSyncConstants::ISSUE_UNIT_MISMATCH,
		);
		$severity = in_array($code, $warnings, true)
			? SupplierPriceSyncIssue::SEVERITY_WARNING
			: SupplierPriceSyncIssue::SEVERITY_ERROR;

		return new SupplierPriceSyncIssue(
			$severity,
			$code,
			'',
			$product->supplierRef,
			$product->productRef,
			0.0
		);
	}

	/**
	 * Coerce a SOAP value that may be a single object or an array into an array.
	 *
	 * @param mixed $value SOAP value (detailRow or threshold).
	 * @return array<int,object>
	 */
	private function toArray($value): array
	{
		if (is_array($value)) {
			return array_values($value);
		}
		if (is_object($value)) {
			return array($value);
		}

		return array();
	}
}
