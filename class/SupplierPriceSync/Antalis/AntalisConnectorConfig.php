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
 * \file    class/SupplierPriceSync/Antalis/AntalisConnectorConfig.php
 * \ingroup clichaumeil
 * \brief   ANTALIS connector configuration built from Dolibarr constants.
 */

declare(strict_types=1);

require_once __DIR__ . '/../SupplierPriceSyncConstants.php';
require_once __DIR__ . '/../Contract/SupplierConfigInterface.php';

/**
 * Holds and validates the ANTALIS configuration read from Dolibarr constants.
 */
final class AntalisConnectorConfig implements SupplierConfigInterface
{
	/**
	 * @param string $baseUrl           API/WSDL base URL or SOAP location.
	 * @param string $httpLogin         HTTP basic login.
	 * @param string $httpPassword      HTTP basic password.
	 * @param int    $thirdpartyId      ANTALIS third party id (fk_soc).
	 * @param string $customerId        ANTALIS customerID.
	 * @param string $userCode          ANTALIS userCode.
	 * @param string $deliveryAddressId Optional AntalisDeliveryAddressID.
	 */
	private function __construct(
		private readonly string $baseUrl,
		private readonly string $httpLogin,
		private readonly string $httpPassword,
		private readonly int $thirdpartyId,
		private readonly string $customerId,
		private readonly string $userCode,
		private readonly string $deliveryAddressId
	) {
	}

	/**
	 * Build the configuration from Dolibarr global constants.
	 *
	 * @return self
	 * @throws RuntimeException When a mandatory constant is missing or empty.
	 */
	public static function fromGlobals(): self
	{
		$baseUrl = getDolGlobalString(SupplierPriceSyncConstants::CONST_BASE_URL);
		$httpLogin = getDolGlobalString(SupplierPriceSyncConstants::CONST_HTTP_LOGIN);
		$httpPassword = getDolGlobalString(SupplierPriceSyncConstants::CONST_HTTP_PASSWORD);
		$thirdpartyId = getDolGlobalInt(SupplierPriceSyncConstants::CONST_THIRDPARTY_ID);
		$customerId = getDolGlobalString(SupplierPriceSyncConstants::CONST_CUSTOMER_ID);
		$userCode = getDolGlobalString(SupplierPriceSyncConstants::CONST_USER_CODE);
		$deliveryAddressId = getDolGlobalString(SupplierPriceSyncConstants::CONST_DELIVERY_ADDRESS_ID);

		$missing = array();
		if ($baseUrl === '') {
			$missing[] = SupplierPriceSyncConstants::CONST_BASE_URL;
		}
		if ($httpLogin === '') {
			$missing[] = SupplierPriceSyncConstants::CONST_HTTP_LOGIN;
		}
		if ($httpPassword === '') {
			$missing[] = SupplierPriceSyncConstants::CONST_HTTP_PASSWORD;
		}
		if ($thirdpartyId <= 0) {
			$missing[] = SupplierPriceSyncConstants::CONST_THIRDPARTY_ID;
		}
		if ($customerId === '') {
			$missing[] = SupplierPriceSyncConstants::CONST_CUSTOMER_ID;
		}
		if ($userCode === '') {
			$missing[] = SupplierPriceSyncConstants::CONST_USER_CODE;
		}

		if ($missing !== array()) {
			throw new RuntimeException('Missing ANTALIS configuration: ' . implode(', ', $missing));
		}

		return new self($baseUrl, $httpLogin, $httpPassword, $thirdpartyId, $customerId, $userCode, $deliveryAddressId);
	}

	/**
	 * Return the supplier code.
	 *
	 * @return string
	 */
	public function getCode(): string
	{
		return SupplierPriceSyncConstants::SUPPLIER_ANTALIS;
	}

	/**
	 * Return the human-readable supplier label.
	 *
	 * @return string
	 */
	public function getLabel(): string
	{
		return 'ANTALIS';
	}

	/**
	 * Return the ANTALIS third party id.
	 *
	 * @return int
	 */
	public function getSupplierThirdpartyId(): int
	{
		return $this->thirdpartyId;
	}

	/**
	 * Return the API/WSDL base URL or SOAP location.
	 *
	 * @return string
	 */
	public function getBaseUrl(): string
	{
		return $this->baseUrl;
	}

	/**
	 * Return the HTTP basic login.
	 *
	 * @return string
	 */
	public function getHttpLogin(): string
	{
		return $this->httpLogin;
	}

	/**
	 * Return the HTTP basic password.
	 *
	 * @return string
	 */
	public function getHttpPassword(): string
	{
		return $this->httpPassword;
	}

	/**
	 * Return the ANTALIS customerID.
	 *
	 * @return string
	 */
	public function getCustomerId(): string
	{
		return $this->customerId;
	}

	/**
	 * Return the ANTALIS userCode.
	 *
	 * @return string
	 */
	public function getUserCode(): string
	{
		return $this->userCode;
	}

	/**
	 * Return the optional AntalisDeliveryAddressID.
	 *
	 * @return string
	 */
	public function getDeliveryAddressId(): string
	{
		return $this->deliveryAddressId;
	}
}
