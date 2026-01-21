<?php
/* Copyright (C) 2023 SuperAdmin
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
 * \file    themequarantedeux/lib/themequarantedeux.lib.php
 * \ingroup themequarantedeux
 * \brief   Library files with common functions for ThemeQuarantedeux
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function themequarantedeuxAdminPrepareHead()
{
	global $langs, $conf, $user;

	$langs->load("themequarantedeux@themequarantedeux");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/themequarantedeux/admin/ihm.php", 1);
	$head[$h][1] = '<i class="fas fa-brush"></i> '.$langs->trans("GUISetup");
	$head[$h][2] = 'ihm';
	$h++;

	$head[$h][0] = dol_buildpath("/themequarantedeux/admin/setup.php", 1);
	$head[$h][1] = '<i class="fas fa-cog"></i> '.$langs->trans("Setup");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/themequarantedeux/admin/scripturl.php", 1);
	$head[$h][1] = '<i class="fas fa-cog"></i> '.$langs->trans("ManageUrls");
	$head[$h][2] = 'scripturl';
	$h++;

	$head[$h][0] = dol_buildpath("/themequarantedeux/admin/hidetopmenu.php", 1);
	$head[$h][1] = '<i class="fas fa-cog"></i> '.$langs->trans("HideTopMenu");
	$head[$h][2] = 'hidetopmenu';
	$h++;

	if ($conf->h2g2->enabled) {
		dol_include_once('/h2g2/lib/h2g2.lib.php');
		$langs->load('h2g2@h2g2');
		if ($user->admin && isH2G2InstalledWithMinVersion('15.0.03')) {
			// H2G2 migration page only for admin
			$head[$h][0] = dol_buildpath('/h2g2/admin/migration_page.php?module=modThemeQuaranteDeux&modulePath=/themequarantedeux/core/modules/modThemeQuaranteDeux.class.php', 1);
			$head[$h][1] = '<i class="fas fa-wrench"></i> ' . $langs->trans("ThemeQuaranteDeuxMigrationPageTitle");
			$head[$h][2] = 'migration';
			$h++;

			// H2G2 information page
			$head[$h][0] = dol_buildpath('/h2g2/admin/information_page.php?module=modThemeQuaranteDeux&modulePath=/themequarantedeux/core/modules/modThemeQuaranteDeux.class.php', 1);
			$head[$h][1] = '<i class="fas fa-info-circle"></i> ' . $langs->trans("ThemeQuaranteDeuxInformationPageTitle");
			$head[$h][2] = 'information';
			$h++;

			// Changelog page
			$head[$h][0] = dol_buildpath("/themequarantedeux/admin/changelog.php", 1);
			$head[$h][1] = '<i class="fas fa-info-circle"></i> ' . $langs->trans("ThemeQuarantedeuxChangelog");
			$head[$h][2] = 'changelog';
			$h++;
		}
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'themequarantedeux');

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function ihm42_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/themequarantedeux/admin/ihm.php?mode=other", 1);
	$head[$h][1] = $langs->trans("LanguageAndPresentation");
	$head[$h][2] = 'other';
	$h++;

	$head[$h][0] = dol_buildpath("/themequarantedeux/admin/ihm.php?mode=template", 1);
	$head[$h][1] = $langs->trans("SkinAndColors");
	$head[$h][2] = 'template';
	$h++;

	$head[$h][0] = dol_buildpath("/themequarantedeux/admin/ihm.php?mode=dashboard", 1);
	$head[$h][1] = $langs->trans("Dashboard");
	$head[$h][2] = 'dashboard';
	$h++;

	if (empty($conf->multicompany->enabled)) { // if multicompany not enable -> display menu
		$head[$h][0] = dol_buildpath("/themequarantedeux/admin/ihm.php?mode=login", 1);
		$head[$h][1] = $langs->trans("LoginPage");
		$head[$h][2] = 'login';
		$h++;
	} else if ($conf->entity == 1) { // if we are in configuration entity
		$head[$h][0] = dol_buildpath("/themequarantedeux/admin/ihm.php?mode=login", 1);
		$head[$h][1] = $langs->trans("LoginPage");
		$head[$h][2] = 'login';
		$h++;
	}

	$head[$h][0] = dol_buildpath("/themequarantedeux/admin/ihm.php?mode=css", 1);
	$head[$h][1] = $langs->trans("CSSPage");
	$head[$h][2] = 'css';
	$h++;

	$head[$h][0] = dol_buildpath("/themequarantedeux/admin/ihm.php?mode=theme42", 1);
	$head[$h][1] = $langs->trans("Theme42");
	$head[$h][2] = 'theme42';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'ihm_admin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'ihm_admin', 'remove');


	return $head;
}

/**
 *	Return the max allowed for file upload.
 *  Analyze among: upload_max_filesize, post_max_size, MAIN_UPLOAD_DOC
 *
 *  @return	array		Array with all max size for file upload
 */
function getMaxFileSizeArrayTheme()
{
	global $conf;

	$max = $conf->global->MAIN_UPLOAD_DOC; // In Kb
	$maxphp = @ini_get('upload_max_filesize'); // In unknown
	if (preg_match('/k$/i', $maxphp)) {
		$maxphp = preg_replace('/k$/i', '', $maxphp);
		$maxphp = $maxphp * 1;
	}
	if (preg_match('/m$/i', $maxphp)) {
		$maxphp = preg_replace('/m$/i', '', $maxphp);
		$maxphp = $maxphp * 1024;
	}
	if (preg_match('/g$/i', $maxphp)) {
		$maxphp = preg_replace('/g$/i', '', $maxphp);
		$maxphp = $maxphp * 1024 * 1024;
	}
	if (preg_match('/t$/i', $maxphp)) {
		$maxphp = preg_replace('/t$/i', '', $maxphp);
		$maxphp = $maxphp * 1024 * 1024 * 1024;
	}
	$maxphp2 = @ini_get('post_max_size'); // In unknown
	if (preg_match('/k$/i', $maxphp2)) {
		$maxphp2 = preg_replace('/k$/i', '', $maxphp2);
		$maxphp2 = $maxphp2 * 1;
	}
	if (preg_match('/m$/i', $maxphp2)) {
		$maxphp2 = preg_replace('/m$/i', '', $maxphp2);
		$maxphp2 = $maxphp2 * 1024;
	}
	if (preg_match('/g$/i', $maxphp2)) {
		$maxphp2 = preg_replace('/g$/i', '', $maxphp2);
		$maxphp2 = $maxphp2 * 1024 * 1024;
	}
	if (preg_match('/t$/i', $maxphp2)) {
		$maxphp2 = preg_replace('/t$/i', '', $maxphp2);
		$maxphp2 = $maxphp2 * 1024 * 1024 * 1024;
	}
	// Now $max and $maxphp and $maxphp2 are in Kb
	$maxmin = $max;
	$maxphptoshow = $maxphptoshowparam = '';
	if ($maxphp > 0) {
		$maxmin = min($maxmin, $maxphp);
		$maxphptoshow = $maxphp;
		$maxphptoshowparam = 'upload_max_filesize';
	}
	if ($maxphp2 > 0) {
		$maxmin = min($maxmin, $maxphp2);
		if ($maxphp2 < $maxphp) {
			$maxphptoshow = $maxphp2;
			$maxphptoshowparam = 'post_max_size';
		}
	}

	return array('max'=>$max, 'maxmin'=>$maxmin, 'maxphptoshow'=>$maxphptoshow, 'maxphptoshowparam'=>$maxphptoshowparam);
}
