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
 * \brief   Maps a Dolibarr packaging unit label to an ANTALIS order unit code.
 */

declare(strict_types=1);

/**
 * Maps the Dolibarr "conditionnement_unite_de_prix" label to an ANTALIS orderUnit code.
 *
 * Order unit codes come from the ANTALIS doc (AntalisStockAndPriceEnquiry V1.4 §2.3).
 * Any unknown label returns null on purpose: never guess Lot/M2 mappings.
 */
final class AntalisOrderUnitMapper
{
	/** @var array<string,string> Normalised label => ANTALIS order unit code. */
	private const MAP = array(
		'feuille' => 'ZSH',
		'feuilles' => 'ZSH',
		'ramette' => 'ZRM',
		'ramettes' => 'ZRM',
		'un' => 'ST',
		'piece' => 'ST',
		'pieces' => 'ST',
		'carton' => 'KAR',
		'cartons' => 'KAR',
		'rouleau' => 'ROL',
		'rouleaux' => 'ROL',
		'palette' => 'PAL',
		'palettes' => 'PAL',
		'bundle' => 'ZBL',
		'bundles' => 'ZBL',
	);

	/**
	 * Map a source packaging unit label to an ANTALIS order unit code.
	 *
	 * @param string $source Raw Dolibarr packaging unit label.
	 * @return string|null ANTALIS code, or null if not mappable.
	 */
	public function map(string $source): ?string
	{
		$key = $this->normalize($source);

		return self::MAP[$key] ?? null;
	}

	/**
	 * Normalise a label: trim, lowercase, strip accents.
	 *
	 * @param string $value Raw label.
	 * @return string Normalised key.
	 */
	private function normalize(string $value): string
	{
		$value = mb_strtolower(trim($value));

		return strtr(
			$value,
			array(
				'à' => 'a', 'â' => 'a', 'ä' => 'a',
				'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
				'î' => 'i', 'ï' => 'i',
				'ô' => 'o', 'ö' => 'o',
				'ù' => 'u', 'û' => 'u', 'ü' => 'u',
				'ç' => 'c',
			)
		);
	}
}
