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
 * \file    class/SupplierPriceSync/SupplierPriceSyncConstants.php
 * \ingroup clichaumeil
 * \brief   Centralised constants for the supplier price synchronisation feature.
 */

declare(strict_types=1);

/**
 * Holds every technical constant of the supplier price sync subsystem.
 *
 * No magic string is allowed outside this class.
 */
final class SupplierPriceSyncConstants
{
	/** @var string Supplier code of the first connector. */
	public const SUPPLIER_ANTALIS = 'ANTALIS';

	/** @var string Separator used for the cron recipients list. */
	public const MAIL_SEPARATOR = ';';

	/** @var int Active supplier price status. */
	public const STATUS_ACTIVE = 1;

	/** @var int Inactive (closed) supplier price status. */
	public const STATUS_INACTIVE = 0;

	/** @var float Tolerance used when comparing two unit prices. */
	public const PRICE_EPSILON = 0.000001;

	/** @var float Tolerance used when matching an API tier quantity to a Dolibarr line. */
	public const QUANTITY_EPSILON = 0.0001;

	/** @var int Default number of lines sent per ANTALIS SOAP request. */
	public const DEFAULT_ANTALIS_BATCH_SIZE = 50;

	/** @var int Default max share (%) of scanned lines that a single run may close. */
	public const DEFAULT_MAX_CLOSURE_RATIO = 50;

	// --- ANTALIS customerPricesCheck error codes (doc AntalisCustomerPrices V1.2 §3.5) ---
	/** @var string No error. */
	public const API_OK = '00';
	/** @var string Generic error. */
	public const API_GENERIC_ERROR = '01';
	/** @var string Unknown product. */
	public const API_UNKNOWN_PRODUCT = '04';
	/** @var string Product not available. */
	public const API_NOT_AVAILABLE = '16';
	/** @var string Back-end down. */
	public const API_BACKEND_DOWN = '90';

	// --- Internal issue codes ---
	/** @var string API unit cannot be mapped to a Dolibarr packaging label. */
	public const ISSUE_UNMAPPED_ORDER_UNIT = 'UNMAPPED_ORDER_UNIT';
	/** @var string Supplier reference unknown on the API side. */
	public const ISSUE_REFERENCE_NOT_FOUND = 'REFERENCE_NOT_FOUND';
	/** @var string API returned a negative price. */
	public const ISSUE_NEGATIVE_PRICE = 'NEGATIVE_PRICE';
	/** @var string API returned no negotiated (personal) price for the product. */
	public const ISSUE_MISSING_PERSONAL_PRICE = 'MISSING_PERSONAL_PRICE';
	/** @var string API price unit inconsistent (personalPriceUnit != thresholdQtyUnit, or Dolibarr line unit differs). */
	public const ISSUE_UNIT_MISMATCH = 'UNIT_MISMATCH';
	/** @var string Closure threshold reached: further closures suspended this run. */
	public const ISSUE_CLOSURE_THRESHOLD = 'CLOSURE_THRESHOLD';
	/** @var string API unreachable or fatal back-end error. */
	public const ISSUE_API_UNAVAILABLE = 'API_UNAVAILABLE';
	/** @var string Generic per-product API error. */
	public const ISSUE_API_LINE_ERROR = 'API_LINE_ERROR';
	/** @var string Dolibarr write failed. */
	public const ISSUE_DOLIBARR_UPDATE_FAILED = 'DOLIBARR_UPDATE_FAILED';
	/** @var string Report email could not be sent. */
	public const ISSUE_MAIL_FAILED = 'MAIL_FAILED';
	/** @var string Invalid cron recipient email. */
	public const ISSUE_INVALID_CRON_RECIPIENT = 'INVALID_CRON_RECIPIENT';
	/** @var string Mandatory configuration missing. */
	public const ISSUE_MISSING_CONFIGURATION = 'MISSING_CONFIGURATION';

	// --- Dolibarr configuration constant names ---
	/** @var string */
	public const CONST_BASE_URL = 'CLICHAUMEIL_SUPPLIER_ANTALIS_BASE_URL';
	/** @var string */
	public const CONST_HTTP_LOGIN = 'CLICHAUMEIL_SUPPLIER_ANTALIS_HTTP_LOGIN';
	/** @var string */
	public const CONST_HTTP_PASSWORD = 'CLICHAUMEIL_SUPPLIER_ANTALIS_HTTP_PASSWORD';
	/** @var string */
	public const CONST_THIRDPARTY_ID = 'CLICHAUMEIL_SUPPLIER_ANTALIS_THIRDPARTY_ID';
	/** @var string */
	public const CONST_CUSTOMER_ID = 'CLICHAUMEIL_SUPPLIER_ANTALIS_CUSTOMER_ID';
	/** @var string */
	public const CONST_USER_CODE = 'CLICHAUMEIL_SUPPLIER_ANTALIS_USER_CODE';
	/** @var string */
	public const CONST_DELIVERY_ADDRESS_ID = 'CLICHAUMEIL_SUPPLIER_ANTALIS_DELIVERY_ADDRESS_ID';

	// --- Generic sync settings (supplier-agnostic) ---
	/** @var string Dry-run flag: when set, the run computes but writes nothing. */
	public const CONST_DRY_RUN = 'CLICHAUMEIL_SUPPLIER_PRICE_SYNC_DRY_RUN';
	/** @var string Max share (%) of scanned lines a single run may close (>=100 disables the guard). */
	public const CONST_MAX_CLOSURE_RATIO = 'CLICHAUMEIL_SUPPLIER_PRICE_SYNC_MAX_CLOSURE_RATIO';

	/**
	 * Pure constants holder — must never be instantiated.
	 */
	private function __construct()
	{
	}
}
