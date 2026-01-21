<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 *	    \file       htdocs/extraitcompteclient/admin/about.php
 *		\ingroup    extraitcompteclient
 *		\brief      Page about of extraitcompteclient module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/extraitcompteclient/lib/extraitcompteclient.lib.php');
dol_include_once('/extraitcompteclient/lib/opendsi_common.lib.php');

$langs->load("admin");
$langs->load("extraitcompteclient@extraitcompteclient");
$langs->load("opendsi@extraitcompteclient");

if (!$user->admin) accessforbidden();


/**
 * View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ExtraitCompteClientSetup"),$linkback,'title_setup');
print "<br>\n";


$head=extraitcompteclient_admin_prepare_head();

print dol_get_fiche_head($head, 'changelog', $langs->trans("Module163030Name"), 0, 'opendsi@extraitcompteclient');

$changelog = opendsi_common_getChangeLog('extraitcompteclient');

print '<div class="moduledesclong">'."\n";
print (!empty($changelog) ? $changelog : $langs->trans("NotAvailable"));
print '<div>'."\n";

print dol_get_fiche_end();


llxFooter();

$db->close();
