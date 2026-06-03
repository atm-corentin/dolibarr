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
 * \file    class/SupplierPriceSync/Antalis/AntalisOrderUnitMapper.php
 * \ingroup clichaumeil
 * \brief   Maps an ANTALIS unit code to a Dolibarr packaging unit label.
 */

declare(strict_types=1);

/**
 * Maps an ANTALIS unit code (thresholdQtyUnit) to a Dolibarr packaging label.
 *
 * Unit codes come from the ANTALIS doc (AntalisCustomerPrices V1.2 §3.4) and the
 * SAP UOM table. Used when creating a new tier to fill the Dolibarr packaging
 * extrafield. Any unknown code returns null on purpose: never guess a label.
 */
final class AntalisOrderUnitMapper
{
	/**
	 * ANTALIS unit code => Dolibarr French packaging label.
	 *
	 * Labels are aligned (singular form) with Chaumeil's existing supplier-price
	 * unit dictionary (conditionnement_unite_de_prix): Feuille, Ramette, Un, Carton…
	 * so they match the stored labels and do not raise spurious unit-divergence
	 * warnings on the very first synchronisation run.
	 *
	 * @var array<string,string>
	 */
	private const MAP = array(
		'ZSH' => 'Feuille',
		'ZRM' => 'Ramette',
		'PAL' => 'Palette',
		'ST' => 'Un',
		'EA' => 'Un',
		'ROL' => 'Rouleau',
		'KG' => 'Kilo',
		'ZBL' => 'Liasse',
		'KAR' => 'Carton',
		'M2' => 'M2',
		'ZBX' => 'Lot',
	);

	/**
	 * Map an ANTALIS unit code to a Dolibarr packaging unit label.
	 *
	 * @param string $apiUnit Raw ANTALIS unit code (e.g. "ZSH", "ST").
	 * @return string|null Dolibarr label, or null if the code is unknown.
	 */
	public function dolibarrLabel(string $apiUnit): ?string
	{
		$key = strtoupper(trim($apiUnit));

		return self::MAP[$key] ?? null;
	}
}
