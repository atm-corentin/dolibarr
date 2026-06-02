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
 * \file    class/SupplierPriceSync/Cron/AntalisSupplierPriceSyncCronJob.php
 * \ingroup clichaumeil
 * \brief   ANTALIS specialisation of the supplier price synchronisation cron.
 */

declare(strict_types=1);

$res = @include_once __DIR__ . '/../../../main.inc.php';
if (!$res) {
	$res = @include_once __DIR__ . '/../../../../main.inc.php';
}
if (!$res) {
	$res = @include_once __DIR__ . '/../../../../../main.inc.php';
}

require_once __DIR__ . '/AbstractSupplierPriceSyncCronJob.php';
require_once __DIR__ . '/../Contract/SupplierConfigInterface.php';
require_once __DIR__ . '/../Contract/SupplierPriceConnectorInterface.php';
require_once __DIR__ . '/../Antalis/AntalisConnectorConfig.php';
require_once __DIR__ . '/../Antalis/AntalisConnector.php';
require_once __DIR__ . '/../Antalis/AntalisOrderUnitMapper.php';

/**
 * Daily ANTALIS purchase price synchronisation cron job.
 */
class AntalisSupplierPriceSyncCronJob extends AbstractSupplierPriceSyncCronJob
{
	/**
	 * Build the ANTALIS configuration from Dolibarr constants.
	 *
	 * @return SupplierConfigInterface
	 * @throws RuntimeException When the configuration is incomplete.
	 */
	protected function buildConfig(): SupplierConfigInterface
	{
		return AntalisConnectorConfig::fromGlobals();
	}

	/**
	 * Build the ANTALIS SOAP connector.
	 *
	 * @param SupplierConfigInterface $config Supplier configuration.
	 * @return SupplierPriceConnectorInterface
	 */
	protected function buildConnector(SupplierConfigInterface $config): SupplierPriceConnectorInterface
	{
		'@phan-var AntalisConnectorConfig $config';

		return new AntalisConnector($config, new AntalisOrderUnitMapper());
	}
}
