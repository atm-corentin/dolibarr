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
 *	\file       htdocs/extraitcompteclient/lib/extraitcompteclient.lib.php
 * 	\ingroup	extraitcompteclient
 *	\brief      Functions for the module extraitcompteclient
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function extraitcompteclient_admin_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/extraitcompteclient/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/extraitcompteclient/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About") . " / " . $langs->trans("Support");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/extraitcompteclient/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf,$langs,null,$head,$h,'extraitcompteclient_admin');

    return $head;
}
