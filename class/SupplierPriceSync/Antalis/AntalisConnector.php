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
 * \file    class/SupplierPriceSync/Antalis/AntalisConnector.php
 * \ingroup clichaumeil
 * \brief   ANTALIS SOAP connector for the stockPriceCheck price enquiry service.
 */

declare(strict_types=1);

require_once __DIR__ . '/../SupplierPriceSyncConstants.php';
require_once __DIR__ . '/../Contract/SupplierPriceConnectorInterface.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceCandidate.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceLineResult.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceFetchResult.php';
require_once __DIR__ . '/../ValueObject/SupplierPriceSyncIssue.php';
require_once __DIR__ . '/AntalisConnectorConfig.php';
require_once __DIR__ . '/AntalisOrderUnitMapper.php';

/**
 * Connects to the ANTALIS SOAP "stockPriceCheck" service and normalises its answers.
 *
 * The SOAP call lives in fetchPrices(); the pure normalisation lives in
 * normalizeResponse() so it can be unit-tested against captured payloads.
 */
final class AntalisConnector implements SupplierPriceConnectorInterface
{
	/** @var string Bundled WSDL file shipped with the module. */
	private const WSDL_FILE = __DIR__ . '/antalis-stockpricecheck.wsdl';

	/** @var string Mandatory enquiry type (stock + price). */
	private const ENQUIRY_TYPE = 'S+P';

	/** @var string Standard order type. */
	private const ORDER_TYPE = 'N';

	/** @var AntalisConnectorConfig|null Connector configuration (null in test seam). */
	private ?AntalisConnectorConfig $config;

	/** @var AntalisOrderUnitMapper Order unit mapper. */
	private AntalisOrderUnitMapper $orderUnitMapper;

	/**
	 * @param AntalisConnectorConfig|null $config          Connector configuration.
	 * @param AntalisOrderUnitMapper      $orderUnitMapper Order unit mapper.
	 */
	public function __construct(?AntalisConnectorConfig $config, AntalisOrderUnitMapper $orderUnitMapper)
	{
		$this->config = $config;
		$this->orderUnitMapper = $orderUnitMapper;
	}

	/**
	 * Build a connector usable for normalisation tests (no SOAP config).
	 *
	 * @param AntalisOrderUnitMapper $orderUnitMapper Order unit mapper.
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
	 * ANTALIS cannot enumerate price tiers: discovery is unsupported.
	 *
	 * @return bool
	 */
	public function supportsTierDiscovery(): bool
	{
		return false;
	}

	/**
	 * Fetch prices for a chunk of candidates through the SOAP service.
	 *
	 * @param SupplierPriceCandidate[] $candidates Candidates of a single chunk.
	 * @return SupplierPriceFetchResult
	 * @throws RuntimeException When the connector has no configuration.
	 */
	public function fetchPrices(array $candidates): SupplierPriceFetchResult
	{
		if ($this->config === null) {
			throw new RuntimeException('AntalisConnector used without configuration');
		}

		$issues = array();
		$detailRows = array();
		$lineMap = array();
		$lineNr = 0;

		foreach ($candidates as $candidate) {
			$orderUnit = $this->orderUnitMapper->map($candidate->orderUnitSource);
			if ($orderUnit === null) {
				$issues[] = new SupplierPriceSyncIssue(
					SupplierPriceSyncIssue::SEVERITY_WARNING,
					SupplierPriceSyncConstants::ISSUE_UNMAPPED_ORDER_UNIT,
					$candidate->orderUnitSource,
					$candidate->supplierRef,
					$candidate->productRef,
					$candidate->quantity
				);
				continue;
			}

			$lineNr++;
			$detailRows[] = array(
				'lineNr' => $lineNr,
				'productID' => $candidate->supplierRef,
				'orderUnit' => $orderUnit,
				'quantity' => $candidate->quantity,
				'deliveryDate' => dol_print_date(dol_now(), '%Y-%m-%dT%H:%M:%S'),
			);
			$lineMap[$lineNr] = $candidate;
		}

		if ($detailRows === array()) {
			return new SupplierPriceFetchResult(array(), $issues, false);
		}

		try {
			$response = $this->callSoap($detailRows);
		} catch (SoapFault $fault) {
			dol_syslog('AntalisConnector::fetchPrices SOAP fault: ' . $fault->getMessage(), LOG_ERR);
			$issues[] = new SupplierPriceSyncIssue(
				SupplierPriceSyncIssue::SEVERITY_ERROR,
				SupplierPriceSyncConstants::ISSUE_API_UNAVAILABLE,
				$fault->getMessage()
			);

			return new SupplierPriceFetchResult(array(), $issues, true);
		}

		$fetch = $this->normalizeResponse($response, $lineMap);

		return new SupplierPriceFetchResult($fetch->results, array_merge($issues, $fetch->issues), $fetch->fatalError);
	}

	/**
	 * Perform the SOAP stockPriceCheck call.
	 *
	 * @param array<int,array<string,mixed>> $detailRows Built input detail rows.
	 * @return object SOAP response object.
	 * @throws SoapFault When the SOAP transport or service fails.
	 */
	private function callSoap(array $detailRows): object
	{
		$options = array(
			'login' => $this->config->getHttpLogin(),
			'password' => $this->config->getHttpPassword(),
			'location' => $this->config->getBaseUrl(),
			'trace' => 0,
			'exceptions' => true,
			'soap_version' => SOAP_1_1,
			'cache_wsdl' => WSDL_CACHE_NONE,
			'connection_timeout' => 30,
		);

		$client = new SoapClient(self::WSDL_FILE, $options);

		$input = array(
			'enquiryType' => self::ENQUIRY_TYPE,
			'userCode' => $this->config->getUserCode(),
			'customerID' => $this->config->getCustomerId(),
			'AntalisDeliveryAddressID' => $this->config->getDeliveryAddressId(),
			'orderType' => self::ORDER_TYPE,
			'detailRow' => $detailRows,
		);

		return $client->stockPriceCheck($input);
	}

	/**
	 * Normalise a SOAP response into line results and issues.
	 *
	 * @param object                          $response SOAP response object.
	 * @param array<int,SupplierPriceCandidate> $lineMap  Map lineNr => candidate.
	 * @return SupplierPriceFetchResult
	 */
	private function normalizeResponse(object $response, array $lineMap): SupplierPriceFetchResult
	{
		$results = array();
		$issues = array();
		$seenLines = array();

		$detailRows = $this->toArray($response->detailRow ?? array());

		foreach ($detailRows as $row) {
			$rowLineNr = isset($row->lineNr) ? (int) $row->lineNr : 0;
			if (!isset($lineMap[$rowLineNr])) {
				continue;
			}
			$candidate = $lineMap[$rowLineNr];
			$seenLines[$rowLineNr] = true;
			$errorId = isset($row->errorID) ? (string) $row->errorID : '';

			switch ($errorId) {
				case SupplierPriceSyncConstants::API_OK:
					$materialPrice = isset($row->materialPrice) ? (float) $row->materialPrice : 0.0;
					$unitPrice = $candidate->quantity > 0 ? $materialPrice / $candidate->quantity : 0.0;
					if ($unitPrice < 0) {
						$issues[] = $this->lineIssue(
							SupplierPriceSyncIssue::SEVERITY_ERROR,
							SupplierPriceSyncConstants::ISSUE_NEGATIVE_PRICE,
							$candidate
						);
						$results[] = SupplierPriceLineResult::error($candidate->supplierRef, $candidate->quantity);
						break;
					}
					$results[] = SupplierPriceLineResult::success($candidate->supplierRef, $candidate->quantity, $unitPrice);
					break;

				case SupplierPriceSyncConstants::API_NOT_AVAILABLE:
					$results[] = SupplierPriceLineResult::close($candidate->supplierRef, $candidate->quantity);
					break;

				case SupplierPriceSyncConstants::API_UNKNOWN_PRODUCT:
					$issues[] = $this->lineIssue(
						SupplierPriceSyncIssue::SEVERITY_ERROR,
						SupplierPriceSyncConstants::ISSUE_REFERENCE_NOT_FOUND,
						$candidate
					);
					$results[] = SupplierPriceLineResult::close($candidate->supplierRef, $candidate->quantity);
					break;

				case SupplierPriceSyncConstants::API_UNKNOWN_UNIT:
					$issues[] = $this->lineIssue(
						SupplierPriceSyncIssue::SEVERITY_ERROR,
						SupplierPriceSyncConstants::ISSUE_UNMAPPED_ORDER_UNIT,
						$candidate
					);
					$results[] = SupplierPriceLineResult::error($candidate->supplierRef, $candidate->quantity);
					break;

				case SupplierPriceSyncConstants::API_ILLEGAL_QTY:
					$issues[] = $this->lineIssue(
						SupplierPriceSyncIssue::SEVERITY_ERROR,
						SupplierPriceSyncConstants::ISSUE_ILLEGAL_QUANTITY,
						$candidate
					);
					$results[] = SupplierPriceLineResult::error($candidate->supplierRef, $candidate->quantity);
					break;

				case SupplierPriceSyncConstants::API_ANTALINK:
					$issues[] = $this->lineIssue(
						SupplierPriceSyncIssue::SEVERITY_ERROR,
						SupplierPriceSyncConstants::ISSUE_ANTALINK_PROFILE,
						$candidate
					);
					$results[] = SupplierPriceLineResult::error($candidate->supplierRef, $candidate->quantity);
					break;

				case SupplierPriceSyncConstants::API_BACKEND_DOWN:
					$issues[] = new SupplierPriceSyncIssue(
						SupplierPriceSyncIssue::SEVERITY_ERROR,
						SupplierPriceSyncConstants::ISSUE_API_UNAVAILABLE,
						$errorId
					);

					return new SupplierPriceFetchResult($results, $issues, true);

				default:
					$issues[] = $this->lineIssue(
						SupplierPriceSyncIssue::SEVERITY_ERROR,
						SupplierPriceSyncConstants::ISSUE_API_LINE_ERROR,
						$candidate
					);
					$results[] = SupplierPriceLineResult::error($candidate->supplierRef, $candidate->quantity);
					break;
			}
		}

		foreach ($lineMap as $lineNr => $candidate) {
			if (!isset($seenLines[$lineNr])) {
				$issues[] = $this->lineIssue(
					SupplierPriceSyncIssue::SEVERITY_ERROR,
					SupplierPriceSyncConstants::ISSUE_API_LINE_ERROR,
					$candidate
				);
			}
		}

		return new SupplierPriceFetchResult($results, $issues, false);
	}

	/**
	 * Build a per-line issue from a candidate.
	 *
	 * @param string                  $severity  Issue severity.
	 * @param string                  $code      Issue code.
	 * @param SupplierPriceCandidate $candidate Candidate concerned.
	 * @return SupplierPriceSyncIssue
	 */
	private function lineIssue(string $severity, string $code, SupplierPriceCandidate $candidate): SupplierPriceSyncIssue
	{
		return new SupplierPriceSyncIssue(
			$severity,
			$code,
			$candidate->supplierRef,
			$candidate->supplierRef,
			$candidate->productRef,
			$candidate->quantity
		);
	}

	/**
	 * Coerce a SOAP value that may be a single object or an array into an array.
	 *
	 * @param mixed $value SOAP detailRow value.
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
