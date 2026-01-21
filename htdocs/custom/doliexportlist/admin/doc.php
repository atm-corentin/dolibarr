<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2017 AXeL <contact.axel.dev@gmail.com>
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
 * 	\file		admin/about.php
 * 	\ingroup	doliexportlist
 * 	\brief		This file is an example about page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/doliexportlist.lib.php';

// Translations
$langs->load("admin");
$langs->load("doliexportlist@doliexportlist");

// Access control
if (! $user->admin) {
    accessforbidden();
}

/*
 * View
 */
$page_name = "Documentation";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = doliexportlistAdminPrepareHead();
dol_fiche_head(
    $head,
    'doc',
    $langs->trans("Module513000Name"),
    0,
    'doliexportlist@doliexportlist'
);

// Documentation page goes here

// How to use it
print load_fiche_titre($langs->trans("HowToUseIt"), '', 'title_question@doliexportlist');

print '<p>'.$langs->trans("HowToUseItDesc").'</p>';
print '<br>';

print '<center>';
print img_picto('', 'doc/list_buttons_mode@doliexportlist');
print '<br>';
print '<p>'.$langs->trans("HowToUseItMore").'</p>';
print '</center>';

// Export
print load_fiche_titre($langs->trans("HowExportWorks"), '', 'title_export@doliexportlist');

print '<p>'.$langs->trans("HowExportWorksDesc").'</p>';
print '<br>';

print '<center>';
print img_picto('', 'doc/export_process@doliexportlist');
print '</center>';

// Import
print load_fiche_titre($langs->trans("HowImportWorks"), '', 'title_import@doliexportlist');

print '<p>'.$langs->trans("HowImportWorksDesc").'</p>';
print '<br>';

print '<center>';
print img_picto('', 'doc/import_process@doliexportlist');
print '</center>';

print '<br>';
print '<p>'.$langs->trans("HowImportWorksNotes").'</p>';

// Need help
print load_fiche_titre($langs->trans("INeedSomeHelp"), '', 'title_question@doliexportlist');

print '<p>'.$langs->trans("INeedSomeHelpDesc").'</p>';
print '<br>';

dol_fiche_end();

llxFooter();

$db->close();