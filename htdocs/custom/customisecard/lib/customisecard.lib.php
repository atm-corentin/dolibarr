<?php
/* Copyright (C) 2021 SuperAdmin <root@roor.fr>
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
 * \file    customisecard/lib/customisecard.lib.php
 * \ingroup customisecard
 * \brief   Library files with common functions for CustomiseCard
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function customisecardAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("customisecard@customisecard");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/customisecard/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/customisecard/admin/personalization.php", 1);
	$head[$h][1] = $langs->trans("CustomiseCardPersonalization");
	$head[$h][2] = 'personalization';
	$h++;

	if ($conf->advancedproductsearch->enabled ?? false) {
		$head[$h][0] = dol_buildpath("/customisecard/admin/advancedproductsearch.php", 1);
		$head[$h][1] = '<span class="fa fa-cog"></span> ' . $langs->trans("CustomiseCardAdvancedProductSearch");
		$head[$h][2] = 'advancedproductsearch';
		$h++;
	}

	$head[$h][0] = dol_buildpath("/customisecard/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// H2G2 migration page only for admin
	$head[$h][0] = dol_buildpath('/h2g2/admin/migration_page.php?module=modCustomiseCard&modulePath=/customisecard/core/modules/modCustomiseCard.class.php', 1);
	$head[$h][1] = '<i class="fas fa-wrench"></i>&nbsp;' . $langs->trans("MigrationPageTitle");
	$head[$h][2] = 'migration';
	$h++;

	$head[$h][0] = dol_buildpath("/customisecard/admin/information_page.php", 1);
	$head[$h][1] = $langs->trans("InformationPage");
	$head[$h][2] = 'information';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'customisecard');

	return $head;
}

/**
 * Get customisecard module version
 *
 * @return string               Module version
 */
function getCustomisecardModuleVersion()
{
	global $db;

	dol_include_once('customisecard/core/modules/modCustomiseCard.class.php');
	$module = new modCustomiseCard($db);

	return $module->getVersion();
}
