<?php
/* Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *    \file       htdocs/sirene/lib/sirene.lib.php
 *    \ingroup    sirene
 *    \brief      Functions for the module sirene
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array                Array of tabs to show
 */
function sirene_admin_prepare_head()
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/sirene/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("SireneTabSirene");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/sirene/admin/setuprna.php", 1);
	$head[$h][1] = $langs->trans("SireneTabRna");
	$head[$h][2] = 'rna';
	$h++;

	$head[$h][0] = dol_buildpath("/sirene/admin/setupcodenaf.php", 1);
	$head[$h][1] = $langs->trans("SireneTabCodeNaf");
	$head[$h][2] = 'codenaf';
	$h++;

	$head[$h][0] = dol_buildpath("/sirene/admin/dictionaries.php", 1);
	$head[$h][1] = $langs->trans("Dictionary");
	$head[$h][2] = 'dictionaries';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'sirene_admin');

	$head[$h][0] = dol_buildpath("/sirene/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About") . " / " . $langs->trans("Support");
	$head[$h][2] = 'about';
	$h++;

	$head[$h][0] = dol_buildpath("/sirene/admin/changelog.php", 1);
	$head[$h][1] = $langs->trans("OpenDsiChangeLog");
	$head[$h][2] = 'changelog';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'sirene_admin', 'remove');

	return $head;
}

/**
 * Return dolibarr global constant string value
 *
 * @param string $key key to return value, return '' if not set
 * @param string $default value to return
 * @return string
 */
function getSireneDolGlobalString($key, $default = '')
{
	if (version_compare(DOL_VERSION, "15.0.0") < 0) {
		global $conf;
		// return $conf->global->$key ?? $default;
		return (string) (isset($conf->global->$key) ? $conf->global->$key : $default);
	}

	return getDolGlobalString($key, $default);
}

/**
 * Return dolibarr global constant int value
 *
 * @param string 	$key 		key to return value, return 0 if not set
 * @param int 		$default 	value to return
 * @return int
 */
function getSireneDolGlobalInt($key, $default = 0)
{
	if (version_compare(DOL_VERSION, "15.0.0") < 0) {
		global $conf;
		// return $conf->global->$key ?? $default;
		return (int) (isset($conf->global->$key) ? $conf->global->$key : $default);
	}

	return getDolGlobalInt($key, $default);
}