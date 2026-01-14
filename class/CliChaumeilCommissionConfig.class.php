<?php

// Copyright (C) 2025 ATM Consulting
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.

/**
 * Configuration helpers for commission management.
 */
class CliChaumeilCommissionConfig
{
	public const COEFF_MARCHE_PUBLIC = 'CLICHAUMEIL_COMMISSION_COEFF_MARCHE_PUBLIC';
	public const COEFF_SOUS_TRAITANCE = 'CLICHAUMEIL_COMMISSION_COEFF_SOUS_TRAITANCE';
	public const COEFF_NOUVEAU = 'CLICHAUMEIL_COMMISSION_COEFF_NOUVEAU';
	public const COEFF_ANCIEN = 'CLICHAUMEIL_COMMISSION_COEFF_ANCIEN';

	public const GROUP_COMMERCIAL = 'CLICHAUMEIL_COMMISSION_GROUP_COMMERCIAL';
	public const GROUP_MANAGER_COMMERCIAL = 'CLICHAUMEIL_COMMISSION_GROUP_MANAGER_COMMERCIAL';
	public const GROUP_PRINT_MANAGER = 'CLICHAUMEIL_COMMISSION_GROUP_PRINT_MANAGER';

	public const CAT_MARCHE_PUBLIC = 'CLICHAUMEIL_COMMISSION_CAT_MARCHE_PUBLIC';
	public const CAT_SOUS_TRAITANCE = 'CLICHAUMEIL_COMMISSION_CAT_SOUS_TRAITANCE';
	public const CAT_NOUVEAU = 'CLICHAUMEIL_COMMISSION_CAT_NOUVEAU';
	public const CAT_ANCIEN = 'CLICHAUMEIL_COMMISSION_CAT_ANCIEN';

	public const DEFAULT_COEFF_MARCHE_PUBLIC = 1.5;
	public const DEFAULT_COEFF_SOUS_TRAITANCE = 1.5;
	public const DEFAULT_COEFF_NOUVEAU = 3.3;
	public const DEFAULT_COEFF_ANCIEN = 1.5;

	/**
	 * @return array<string,float>
	 */
	public static function getDefaultCoefficients(): array
	{
		return array(
			self::COEFF_MARCHE_PUBLIC => self::DEFAULT_COEFF_MARCHE_PUBLIC,
			self::COEFF_SOUS_TRAITANCE => self::DEFAULT_COEFF_SOUS_TRAITANCE,
			self::COEFF_NOUVEAU => self::DEFAULT_COEFF_NOUVEAU,
			self::COEFF_ANCIEN => self::DEFAULT_COEFF_ANCIEN,
		);
	}

	/**
	 * @return array<string,string>
	 */
	public static function getDefaultCategoryLabels(Translate $langs): array
	{
		return array(
			self::CAT_MARCHE_PUBLIC => $langs->trans('CliChaumeilCategoryMarchePublic'),
			self::CAT_SOUS_TRAITANCE => $langs->trans('CliChaumeilCategorySousTraitance'),
			self::CAT_NOUVEAU => $langs->trans('CliChaumeilCategoryNouveau'),
			self::CAT_ANCIEN => $langs->trans('CliChaumeilCategoryAncien'),
		);
	}

	/**
	 * @return array<string,string>
	 */
	public static function getDefaultCategoryRefExt(): array
	{
		return array(
			self::CAT_MARCHE_PUBLIC => 'CLICHAUMEIL_CAT_MARCHE_PUBLIC',
			self::CAT_SOUS_TRAITANCE => 'CLICHAUMEIL_CAT_SOUS_TRAITANCE',
			self::CAT_NOUVEAU => 'CLICHAUMEIL_CAT_NOUVEAU',
			self::CAT_ANCIEN => 'CLICHAUMEIL_CAT_ANCIEN',
		);
	}
}
