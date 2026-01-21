<?php
/* Copyright (C) 2019 SuperAdmin
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    easydashboard/lib/easydashboard.lib.php
 * \ingroup easydashboard
 * \brief   Library files with common functions for easydashboard
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function easydashboardAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("easydashboard@easydashboard");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/easydashboard/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;
	$head[$h][0] = dol_buildpath("/easydashboard/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'easydashboard');

	return $head;
}

