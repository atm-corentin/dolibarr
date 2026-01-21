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

print '<div>'.info_admin($langs->trans("MulticompanyImportParametersInfo"), 0, 0, '1', 'clearboth').'</div>';

?>

<!-- START PHP TEMPLATE ADMIN IMPORT THIRDPARTIES -->

<div class="tagtable centpercent">
	<div class="tagtr liste_titre">
		<div class="tagtd padding-left5" align="left">
			<span class="fa fa-cog"></span><span class="import-title"><?php echo $langs->trans('ParametersType'); ?></span>
		</div>
		<div class="tagtd padding-left5" align="center">
			<span class="import-title"><?php echo $langs->trans('Status'); ?></span>
		</div>
		<div class="tagtd padding-left5" align="center">
			<span class="import-title"><?php echo $langs->trans('Action'); ?></span>
		</div>
	</div>
	<div class="tagtr oddeven import-line">
		<div class="tagtd padding-left5" align="left">
			<?php echo $langs->trans('DuplicateConstants'); ?>
		</div>
		<div class="tagtd padding-left5" align="center">

		</div>
		<div class="tagtd padding-left5" align="center">

		</div>
	</div>
	<div class="tagtr oddeven import-line">
		<div class="tagtd padding-left5" align="left">
			<?php echo $langs->trans('DuplicateDictionaries'); ?>
		</div>
		<div class="tagtd padding-left5" align="center">

		</div>
		<div class="tagtd padding-left5" align="center">

		</div>
	</div>
</div>

<!-- END PHP TEMPLATE ADMIN IMPORT THIRDPARTIES -->
