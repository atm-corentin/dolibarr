<?php
/* Copyright (C) 2014-2024	Regis Houssin	<regis.houssin@inodbox.com>
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
 *
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

global $db, $form, $dbimport;

print '<div>'.info_admin($langs->trans("MulticompanyImportProductsInfo"), 0, 0, '1', 'clearboth').'</div>';

$tablediff = getFieldsDiffFromTable($db, $dbimport, 'product');
if (!empty($tablediff)) {
	print '<div>'.dol_htmloutput_mesg($langs->trans("WarningTableFieldsDiffExists", $dbimport->prefix().'product', implode(', ', $tablediff)), '', 'warning', 1).'</div>';
}

?>

<!-- START PHP TEMPLATE ADMIN IMPORT PRODUCTS -->


<!-- END PHP TEMPLATE ADMIN IMPORT PRODUCTS -->
