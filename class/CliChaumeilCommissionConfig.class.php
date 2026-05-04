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
	public const DICTIONARY_TABLE = 'c_clichaumeil_commission_coeff';

	public const LEGACY_COEFF_MARCHE_PUBLIC = 'CLICHAUMEIL_COMMISSION_COEFF_MARCHE_PUBLIC';
	public const LEGACY_COEFF_SOUS_TRAITANCE = 'CLICHAUMEIL_COMMISSION_COEFF_SOUS_TRAITANCE';
	public const LEGACY_COEFF_NOUVEAU = 'CLICHAUMEIL_COMMISSION_COEFF_NOUVEAU';
	public const LEGACY_COEFF_ANCIEN = 'CLICHAUMEIL_COMMISSION_COEFF_ANCIEN';
	public const LEGACY_PRINT_MANAGEMENT_COEFF_MARCHE_PUBLIC = 'CLICHAUMEIL_COMMISSION_PRINT_MANAGEMENT_COEFF_MARCHE_PUBLIC';
	public const LEGACY_PRINT_MANAGEMENT_COEFF_SOUS_TRAITANCE = 'CLICHAUMEIL_COMMISSION_PRINT_MANAGEMENT_COEFF_SOUS_TRAITANCE';
	public const LEGACY_PRINT_MANAGEMENT_COEFF_NOUVEAU = 'CLICHAUMEIL_COMMISSION_PRINT_MANAGEMENT_COEFF_NOUVEAU';
	public const LEGACY_PRINT_MANAGEMENT_COEFF_ANCIEN = 'CLICHAUMEIL_COMMISSION_PRINT_MANAGEMENT_COEFF_ANCIEN';

	public const COEFF_MARCHE_PUBLIC = 'CLICHAUMEIL_COMMISSION_COEFF_MARCHE_PUBLIC';
	public const COEFF_SOUS_TRAITANCE = 'CLICHAUMEIL_COMMISSION_COEFF_SOUS_TRAITANCE';
	public const COEFF_NOUVEAU = 'CLICHAUMEIL_COMMISSION_COEFF_NOUVEAU';
	public const COEFF_ANCIEN = 'CLICHAUMEIL_COMMISSION_COEFF_ANCIEN';
	public const PRINT_MANAGEMENT_COEFF_MARCHE_PUBLIC = 'CLICHAUMEIL_COMMISSION_PRINT_MANAGEMENT_COEFF_MARCHE_PUBLIC';
	public const PRINT_MANAGEMENT_COEFF_SOUS_TRAITANCE = 'CLICHAUMEIL_COMMISSION_PRINT_MANAGEMENT_COEFF_SOUS_TRAITANCE';
	public const PRINT_MANAGEMENT_COEFF_NOUVEAU = 'CLICHAUMEIL_COMMISSION_PRINT_MANAGEMENT_COEFF_NOUVEAU';
	public const PRINT_MANAGEMENT_COEFF_ANCIEN = 'CLICHAUMEIL_COMMISSION_PRINT_MANAGEMENT_COEFF_ANCIEN';

	public const GROUP_COMMERCIAL = 'CLICHAUMEIL_COMMISSION_GROUP_COMMERCIAL';
	public const GROUP_MANAGER_COMMERCIAL = 'CLICHAUMEIL_COMMISSION_GROUP_MANAGER_COMMERCIAL';
	public const GROUP_PRINT_MANAGER = 'CLICHAUMEIL_COMMISSION_GROUP_PRINT_MANAGER';
	public const GROUP_PRINT_MANAGEMENT = 'CLICHAUMEIL_COMMISSION_GROUP_PRINT_MANAGEMENT';

	public const CAT_MARCHE_PUBLIC = 'CLICHAUMEIL_COMMISSION_CAT_MARCHE_PUBLIC';
	public const CAT_SOUS_TRAITANCE = 'CLICHAUMEIL_COMMISSION_CAT_SOUS_TRAITANCE';
	public const CAT_NOUVEAU = 'CLICHAUMEIL_COMMISSION_CAT_NOUVEAU';
	public const CAT_ANCIEN = 'CLICHAUMEIL_COMMISSION_CAT_ANCIEN';

	public const DEFAULT_COEFF_MARCHE_PUBLIC = 1.5;
	public const DEFAULT_COEFF_SOUS_TRAITANCE = 1.5;
	public const DEFAULT_COEFF_NOUVEAU = 3.3;
	public const DEFAULT_COEFF_ANCIEN = 1.5;
	public const DEFAULT_PRINT_MANAGEMENT_COEFF_MARCHE_PUBLIC = 0.5;
	public const DEFAULT_PRINT_MANAGEMENT_COEFF_SOUS_TRAITANCE = 0.5;
	public const DEFAULT_PRINT_MANAGEMENT_COEFF_NOUVEAU = 0.5;
	public const DEFAULT_PRINT_MANAGEMENT_COEFF_ANCIEN = 0.5;

	/**
	 * @return array<string,float>
	 */
	public static function getDefaultCommercialCoefficients(): array
	{
		return array(
			self::COEFF_MARCHE_PUBLIC => self::DEFAULT_COEFF_MARCHE_PUBLIC,
			self::COEFF_SOUS_TRAITANCE => self::DEFAULT_COEFF_SOUS_TRAITANCE,
			self::COEFF_NOUVEAU => self::DEFAULT_COEFF_NOUVEAU,
			self::COEFF_ANCIEN => self::DEFAULT_COEFF_ANCIEN,
		);
	}

	/**
	 * @return array<string,float>
	 */
	public static function getDefaultPrintManagementCoefficients(): array
	{
		return array(
			self::PRINT_MANAGEMENT_COEFF_MARCHE_PUBLIC => self::DEFAULT_PRINT_MANAGEMENT_COEFF_MARCHE_PUBLIC,
			self::PRINT_MANAGEMENT_COEFF_SOUS_TRAITANCE => self::DEFAULT_PRINT_MANAGEMENT_COEFF_SOUS_TRAITANCE,
			self::PRINT_MANAGEMENT_COEFF_NOUVEAU => self::DEFAULT_PRINT_MANAGEMENT_COEFF_NOUVEAU,
			self::PRINT_MANAGEMENT_COEFF_ANCIEN => self::DEFAULT_PRINT_MANAGEMENT_COEFF_ANCIEN,
		);
	}

	/**
	 * @return array<string,float>
	 */
	public static function getDefaultCoefficients(): array
	{
		return self::getDefaultCommercialCoefficients() + self::getDefaultPrintManagementCoefficients();
	}

	/**
	 * @return array<string,string>
	 */
	public static function getLegacyCoefficientConstantMap(): array
	{
		return array(
			'commercial_marche_public' => self::LEGACY_COEFF_MARCHE_PUBLIC,
			'commercial_sous_traitance' => self::LEGACY_COEFF_SOUS_TRAITANCE,
			'commercial_nouveau' => self::LEGACY_COEFF_NOUVEAU,
			'commercial_ancien' => self::LEGACY_COEFF_ANCIEN,
			'print_management_marche_public' => self::LEGACY_PRINT_MANAGEMENT_COEFF_MARCHE_PUBLIC,
			'print_management_sous_traitance' => self::LEGACY_PRINT_MANAGEMENT_COEFF_SOUS_TRAITANCE,
			'print_management_nouveau' => self::LEGACY_PRINT_MANAGEMENT_COEFF_NOUVEAU,
			'print_management_ancien' => self::LEGACY_PRINT_MANAGEMENT_COEFF_ANCIEN,
		);
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function getDefaultCoefficientDictionaryRows(): array
	{
		return array(
			array(
				'code' => 'COMMERCIAL_MARCHE_PUBLIC',
				'role_code' => 'commercial',
				'customer_tag' => 'marche_public',
				'label_key' => 'CliChaumeilCommissionLabelCommercialMarchePublic',
				'coefficient' => self::DEFAULT_COEFF_MARCHE_PUBLIC,
				'legacy_const' => self::LEGACY_COEFF_MARCHE_PUBLIC,
			),
			array(
				'code' => 'COMMERCIAL_SOUS_TRAITANCE',
				'role_code' => 'commercial',
				'customer_tag' => 'sous_traitance',
				'label_key' => 'CliChaumeilCommissionLabelCommercialSousTraitance',
				'coefficient' => self::DEFAULT_COEFF_SOUS_TRAITANCE,
				'legacy_const' => self::LEGACY_COEFF_SOUS_TRAITANCE,
			),
			array(
				'code' => 'COMMERCIAL_NOUVEAU',
				'role_code' => 'commercial',
				'customer_tag' => 'nouveau',
				'label_key' => 'CliChaumeilCommissionLabelCommercialNouveau',
				'coefficient' => self::DEFAULT_COEFF_NOUVEAU,
				'legacy_const' => self::LEGACY_COEFF_NOUVEAU,
			),
			array(
				'code' => 'COMMERCIAL_ANCIEN',
				'role_code' => 'commercial',
				'customer_tag' => 'ancien',
				'label_key' => 'CliChaumeilCommissionLabelCommercialAncien',
				'coefficient' => self::DEFAULT_COEFF_ANCIEN,
				'legacy_const' => self::LEGACY_COEFF_ANCIEN,
			),
			array(
				'code' => 'PRINT_MANAGEMENT_MARCHE_PUBLIC',
				'role_code' => 'print_management',
				'customer_tag' => 'marche_public',
				'label_key' => 'CliChaumeilCommissionLabelPrintManagementMarchePublic',
				'coefficient' => self::DEFAULT_PRINT_MANAGEMENT_COEFF_MARCHE_PUBLIC,
				'legacy_const' => self::LEGACY_PRINT_MANAGEMENT_COEFF_MARCHE_PUBLIC,
			),
			array(
				'code' => 'PRINT_MANAGEMENT_SOUS_TRAITANCE',
				'role_code' => 'print_management',
				'customer_tag' => 'sous_traitance',
				'label_key' => 'CliChaumeilCommissionLabelPrintManagementSousTraitance',
				'coefficient' => self::DEFAULT_PRINT_MANAGEMENT_COEFF_SOUS_TRAITANCE,
				'legacy_const' => self::LEGACY_PRINT_MANAGEMENT_COEFF_SOUS_TRAITANCE,
			),
			array(
				'code' => 'PRINT_MANAGEMENT_NOUVEAU',
				'role_code' => 'print_management',
				'customer_tag' => 'nouveau',
				'label_key' => 'CliChaumeilCommissionLabelPrintManagementNouveau',
				'coefficient' => self::DEFAULT_PRINT_MANAGEMENT_COEFF_NOUVEAU,
				'legacy_const' => self::LEGACY_PRINT_MANAGEMENT_COEFF_NOUVEAU,
			),
			array(
				'code' => 'PRINT_MANAGEMENT_ANCIEN',
				'role_code' => 'print_management',
				'customer_tag' => 'ancien',
				'label_key' => 'CliChaumeilCommissionLabelPrintManagementAncien',
				'coefficient' => self::DEFAULT_PRINT_MANAGEMENT_COEFF_ANCIEN,
				'legacy_const' => self::LEGACY_PRINT_MANAGEMENT_COEFF_ANCIEN,
			),
		);
	}

	/**
	 * @param Translate $langs Language handler
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
