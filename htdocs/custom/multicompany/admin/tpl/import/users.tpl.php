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

global $db, $form, $dbimport, $toselect;

print '<div>'.info_admin($langs->trans("MulticompanyImportUsersInfo"), 0, 0, '1', 'clearboth').'</div>';

$disabled = false;

$arrayofselected = (is_array($toselect) ? $toselect : array());

// we must not import users on the main entity if it is MULTICOMPANY_TRANSVERSE_MODE not activated
if (!getDolGlobalInt('MULTICOMPANY_TRANSVERSE_MODE') && (!getDolGlobalInt('MULTICOMPANY_IMPORT_TO_ENTITY') || getDolGlobalInt('MULTICOMPANY_IMPORT_TO_ENTITY') == 1)) {
	print '<div>'.dol_htmloutput_mesg($langs->trans("ErrorPleaseSelectAnotherEntity"), '', 'error', 1).'</div>';
	$disabled = true;
}
if (getDolGlobalInt('MULTICOMPANY_IMPORT_USERS_LOCKED')) {
	$disabled = true;
}

$tablediff = getFieldsDiffFromTable($db, $dbimport, 'user');
if (!empty($tablediff)) {
	print '<div>'.dol_htmloutput_mesg($langs->trans("WarningTableFieldsDiffExists", $dbimport->prefix().'user', implode(', ', $tablediff)), '', 'warning', 1).'</div>';
}

// old users list
$olduserslist = new Import($db, $dbimport);
$olduserslist->tablename = 'user';
$selectfields = array('login', 'firstname', 'lastname', 'statut');
$ret = $olduserslist->fetchFromOldTable($selectfields);
if ($ret < 0) {
	dol_htmloutput_mesg($olduserslist->error, '', 'error');
}

// new users list
$newuserslist = new Import($db, $dbimport);
$newuserslist->tablename = 'user';
$ret = $newuserslist->getListByElement();
if ($ret < 0) {
	dol_htmloutput_mesg($newuserslist->error, '', 'error');
}

?>

<!-- START PHP TEMPLATE ADMIN IMPORT USERS -->

<div class="tagtable centpercent">
	<div class="tagtr liste_titre">
		<div class="tagtd padding-left5" align="left">
			<span class="fa fa-info-circle"></span><span class="import-title"><?php echo $langs->trans('LockImportUsersInfo'); ?></span>
		</div>
		<div class="tagtd padding-left5" align="right">
			<?php echo ajax_mcconstantonoff('MULTICOMPANY_IMPORT_USERS_LOCKED', '', 0, 0, 0, 1); ?>
		</div>
	</div>
</div>

<form method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
<input type="hidden" name="token" value="<?php echo newToken(); ?>">
<input type="hidden" name="action" value="setusers">
<input type="hidden" name="step" value="users">

<div class="tagtable centpercent">
	<div class="tagtr liste_titre">
		<div class="tagtd padding-left5" align="left">
			<span class="fa fa-user"></span><span class="import-title"><?php echo $langs->trans('OldUsers'); ?></span>
		</div>
		<div class="tagtd padding-left5" align="left">
			<span class="fa fa-user"></span><span class="import-title"><?php echo $langs->trans('NewUsers'); ?></span>
		</div>
		<div class="tagtd padding-left5" align="center">
			<span class="import-title"><?php echo $langs->trans('SelectToCreate'); ?></span>
		</div>
	</div>
	<div class="tagtr liste_titre">
		<div class="tagtd padding-left5" align="left"></div>
		<div class="tagtd padding-left5" align="left"></div>
		<div class="tagtd padding-left5" align="center">
			<input id="selectall" class="flat checkforselectall" type="checkbox" name="selectall" value=""<?php echo (!empty($toselect) ? ' checked="checked"' : '').($disabled ? ' disabled' : ''); ?>>
		</div>
	</div>

<?php

if (!empty($olduserslist->oldrows)) {

	// we must not import users on the main entity if it is MULTICOMPANY_TRANSVERSE_MODE not activated
	$fromentity = (getDolGlobalInt('MULTICOMPANY_TRANSVERSE_MODE') ? 1 : (getDolGlobalInt('MULTICOMPANY_IMPORT_TO_ENTITY') ? getDolGlobalInt('MULTICOMPANY_IMPORT_TO_ENTITY') : 1));

	foreach($olduserslist->oldrows as $olduserdata) {

		$olduserstatic = new User($dbimport);
		$olduserstatic->firstname = $olduserdata['firstname'];
		$olduserstatic->lastname = $olduserdata['lastname'];

		print '<div class="tagtr oddeven">';
		print '<div class="tagtd padding-left5" align="left">';
		if (empty($olduserdata['statut'])) {
			print '<span class="opacitymedium classfortooltip" title="'.$langs->trans('LoginAccountDisableInDolibarr').'"><s>';
		}
		print '<b>'.$olduserstatic->getFullName($langs).'</b> ('.$olduserdata['login'].')';
		if (empty($olduserdata['statut'])) {
			print '</s></span>';
		}
		print '<input type="hidden" name="oldusers['.$olduserdata['rowid'].']" value="'.$olduserdata['rowid'].'">';
		print '</div>';
		print '<div class="tagtd padding-left5" align="left">';

		$selected = '';
		if (!empty($newuserslist->rows)) {
			$selected = (array_key_exists($olduserdata['rowid'], $newuserslist->rows) ? $newuserslist->rows[$olduserdata['rowid']] : '');
		}

		$tocreate = 0;
		if (in_array($olduserdata['rowid'], $arrayofselected)) {
			$tocreate = 1;
		}

		if (empty($disabled)) {
			print $form->select_dolusers($selected, 'newuser_'.$olduserdata['rowid'], 0, '', $disabled, '', '', $fromentity, 0, 0, '', 0, '', 'selectusers widthcentpercentminusx maxwidth400');
		} else {
			$newuserstatic = new User($db);
			$newuserstatic->fetch($selected);
			print $newuserstatic->getNomUrl(1);
		}

		print '</div>';
		print '<div class="tagtd padding-left5" align="center">';
		print '<input id="user_'.$olduserdata['rowid'].'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$olduserdata['rowid'].'"'.($tocreate ? ' checked="checked"' : '').($disabled ? ' disabled' : '').'>';
		print '</div>';
		print '</div>';
	}
}

print '</div>';

// Boutons actions
print '<div class="tabsAction">';
print '<input type="submit" id="save" name="save" class="butAction linkobject" value="'.$langs->trans("Save").'" '.(!empty($disabled) ? 'disabled' : '').' />';
if (empty($disabled) && getDolGlobalInt('MULTICOMPANY_IMPORT_USERS_READY')) {
	print dolGetButtonAction('', $langs->trans("Reset"), 'delete', $_SERVER["PHP_SELF"].'?action=delusers&step=users&token='.newToken());
}
print '</div>';

print '</form>'."\n";

if (empty($disabled)) {
?>

<script type="text/javascript">
jQuery(document).ready(function() {
	var userselected = jQuery(".checkforselect");
	var selectall = jQuery(".checkforselectall");
	userselected.change(function () {
		var id = parseInt($(this).attr('id').match(/[0-9]+$/g));
		if ($(this).is(':checked')) {
			$('#newuser_' + id).attr('disabled','disabled');
		} else {
			$('#newuser_' + id).removeAttr('disabled');
		}
	});
	selectall.change(function () {
		if ($(this).is(':checked')) {
			$('.checkforselect').prop('checked', true);
			$('.selectusers').attr('disabled','disabled');
		} else {
			$('.checkforselect').prop('checked', false);
			$('.selectusers').removeAttr('disabled');
		}
	});
});
</script>

<?php } ?>

<!-- END PHP TEMPLATE ADMIN IMPORT USERS -->
