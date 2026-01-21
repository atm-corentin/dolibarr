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

include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

global $mc;

print '<div>'.info_admin($langs->trans("MulticompanyImportInfo"), 0, 0, '1', 'clearboth').'</div>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setvalue">';

$select_entity = $mc->select_entities(getDolGlobalInt('MULTICOMPANY_IMPORT_TO_ENTITY'), 'MULTICOMPANY_IMPORT_TO_ENTITY', ' tabindex="3"', true, array(1), false, false, '', 'minwidth100imp');

?>

<!-- START PHP TEMPLATE ADMIN IMPORT -->
<!-- Dolibarr database -->
<div class="tagtable centpercent">
	<div class="tagtr liste_titre">
		<div class="tagtd padding-left5" align="left">
			<span class="fa fa-database"></span><span class="import-title"><?php echo $langs->trans("DolibarrDatabase"); ?></span>
		</div>
		<div class="tagtd padding-left5" align="left"></div>
		<div class="tagtd padding-left5" align="left"></div>
	</div>
	<div class="tagtr oddeven">
		<div class="tagtd padding-left5" align="left">
			<label for="MULTICOMPANY_IMPORT_DB_NAME"><b><?php echo $langs->trans("DatabaseName"); ?></b></label>
		</div>
		<div class="tagtd padding-left5" align="left">
			<input type="text" id="MULTICOMPANY_IMPORT_DB_NAME" name="MULTICOMPANY_IMPORT_DB_NAME" value="<?php echo getDolGlobalString('MULTICOMPANY_IMPORT_DB_NAME'); ?>">
		</div>
		<div class="tagtd padding-left5" align="left">
			<?php echo $langs->trans("DatabaseName"); ?>
		</div>
	</div>
	<div class="tagtr oddeven">
		<!-- Driver type -->
		<div class="tagtd padding-left5" align="left">
			<label for="MULTICOMPANY_IMPORT_DB_TYPE"><b><?php echo $langs->trans("DriverType"); ?></b></label>
		</div>
		<div class="tagtd padding-left5" align="left">
			<?php
			$defaultype = getDolGlobalString('MULTICOMPANY_IMPORT_DB_TYPE', 'mysqli');
			$option = '';
			// Scan les drivers
			$dir = DOL_DOCUMENT_ROOT.'/core/db';
			$handle = opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					if (is_readable($dir."/".$file) && preg_match('/^(.*)\.class\.php$/i', $file, $reg)) {
						$type = $reg[1];
						if ($type === 'DoliDB') {
							continue; // Skip abstract class
						}
						$class = 'DoliDB'.ucfirst($type);
						include_once $dir."/".$file;
						if ($type == 'sqlite') {
							continue; // We hide sqlite because support can't be complete until sqlite does not manage foreign key creation after table creation (ALTER TABLE child ADD CONSTRAINT not supported)
						}
						if ($type == 'sqlite3') {
							continue; // We hide sqlite3 because support can't be complete until sqlite does not manage foreign key creation after table creation (ALTER TABLE child ADD CONSTRAINT not supported)
						}
						// Version min of database
						$note = '('.$class::LABEL.' >= '.$class::VERSIONMIN.')';
						// Switch to mysql if mysqli is not present
						if ($defaultype == 'mysqli' && !function_exists('mysqli_connect')) {
							$defaultype = 'mysql';
						}
						// Show line into list
						if ($type == 'mysql') {
							$testfunction = 'mysql_connect';
							$testclass = '';
						}
						if ($type == 'mysqli') {
							$testfunction = 'mysqli_connect';
							$testclass = '';
						}
						if ($type == 'pgsql') {
							$testfunction = 'pg_connect';
							$testclass = '';
						}
						if ($type == 'sqlite') {
							$testfunction = '';
							$testclass = 'PDO';
						}
						if ($type == 'sqlite3') {
							$testfunction = '';
							$testclass = 'SQLite3';
						}
						$option .= '<option value="'.$type.'"'.($defaultype == $type ? ' selected' : '');
						if ($testfunction && !function_exists($testfunction)) {
							$option .= ' disabled';
						}
						if ($testclass && !class_exists($testclass)) {
							$option .= ' disabled';
						}
						$option .= '>';
						$option .= $type.'&nbsp; &nbsp;';
						if ($note) {
							$option .= ' '.$note;
						}
						// Deprecated and experimental
						if ($type == 'mysql') {
							$option .= ' '.$langs->trans("Deprecated");
						} elseif ($type == 'sqlite') {
							$option .= ' '.$langs->trans("VersionExperimental");
						} elseif ($type == 'sqlite3') {
							$option .= ' '.$langs->trans("VersionExperimental");
						} elseif (!function_exists($testfunction)) {
							// No available
							$option .= ' - '.$langs->trans("FunctionNotAvailableInThisPHP");
						}
						$option .= '</option>';
					}
				}
			}
			?>
			<select id="MULTICOMPANY_IMPORT_DB_TYPE" name="MULTICOMPANY_IMPORT_DB_TYPE" class="flat">
				<?php print $option; ?>
			</select>
		</div>
		<div class="tagtd padding-left5" align="left">
			<?php echo $langs->trans("DatabaseType"); ?>
		</div>
	</div>
	<div class="tagtr oddeven hidesqlite">
		<div class="tagtd padding-left5" align="left">
			<label for="MULTICOMPANY_IMPORT_DB_HOST"><b><?php echo $langs->trans("DatabaseServer"); ?></b></label>
		</div>
		<div class="tagtd padding-left5" align="left">
			<input type="text" id="MULTICOMPANY_IMPORT_DB_HOST" name="MULTICOMPANY_IMPORT_DB_HOST" value="<?php echo getDolGlobalString('MULTICOMPANY_IMPORT_DB_HOST', 'localhost'); ?>">
		</div>
		<div class="tagtd padding-left5" align="left">
			<?php echo $langs->trans("ServerAddressDescription"); ?>
		</div>
	</div>
	<div class="tagtr oddeven hidesqlite">
		<div class="tagtd padding-left5" align="left">
			<label for="MULTICOMPANY_IMPORT_DB_PORT"><?php echo $langs->trans("Port"); ?></label>
		</div>
		<div class="tagtd padding-left5" align="left">
			<input type="text" name="MULTICOMPANY_IMPORT_DB_PORT" id="MULTICOMPANY_IMPORT_DB_PORT" value="<?php echo getDolGlobalString('MULTICOMPANY_IMPORT_DB_PORT'); ?>">
		</div>
		<div class="tagtd padding-left5" align="left">
			<?php echo $langs->trans("ServerPortDescription"); ?>
		</div>
	</div>
	<div class="tagtr oddeven hidesqlite">
		<div class="tagtd padding-left5" align="left">
			<label for="MULTICOMPANY_IMPORT_DB_PREFIX"><?php echo $langs->trans("DatabasePrefix"); ?></label>
		</div>
		<div class="tagtd padding-left5" align="left">
			<input type="text" id="MULTICOMPANY_IMPORT_DB_PREFIX" name="MULTICOMPANY_IMPORT_DB_PREFIX" value="<?php echo getDolGlobalString('MULTICOMPANY_IMPORT_DB_PREFIX', 'llx_'); ?>">
		</div>
		<div class="tagtd padding-left5" align="left">
			<?php echo $langs->trans("DatabasePrefixDescription"); ?>
		</div>
	</div>
	<div class="tagtr oddeven hidesqlite">
		<div class="tagtd padding-left5" align="left">
			<label for="MULTICOMPANY_IMPORT_DB_USER"><b><?php echo $langs->trans("Login"); ?></b></label>
		</div>
		<div class="tagtd padding-left5" align="left">
			<input type="text" id="MULTICOMPANY_IMPORT_DB_USER" name="MULTICOMPANY_IMPORT_DB_USER" value="<?php echo getDolGlobalString('MULTICOMPANY_IMPORT_DB_USER'); ?>">
		</div>
		<div class="tagtd padding-left5" align="left">
			<?php echo $langs->trans("AdminLogin"); ?>
		</div>
	</div>
	<div class="tagtr oddeven hidesqlite">
		<div class="tagtd padding-left5" align="left">
			<label for="MULTICOMPANY_IMPORT_DB_PASS"><b><?php echo $langs->trans("Password"); ?></b></label>
		</div>
		<div class="tagtd padding-left5" align="left">
			<input type="password" class="text-security" id="MULTICOMPANY_IMPORT_DB_PASS" autocomplete="off" name="MULTICOMPANY_IMPORT_DB_PASS" value="<?php echo getDolGlobalString('MULTICOMPANY_IMPORT_DB_PASS'); ?>">
		</div>
		<div class="tagtd padding-left5" align="left">
			<?php echo $langs->trans("AdminPassword"); ?>
		</div>
	</div>
	<div class="tagtr liste_titre">
		<div class="tagtd padding-left5" align="left">
			<span class="fa fa-globe"></span><span class="import-title"><?php echo $langs->trans("DestinationEntity"); ?></span>
		</div>
		<div class="tagtd padding-left5" align="left"></div>
		<div class="tagtd padding-left5" align="left"></div>
	</div>
	<div class="tagtr oddeven">
		<div class="tagtd padding-left5" align="left">
			<label for="MULTICOMPANY_IMPORT_TO_ENTITY"><b><?php echo $langs->trans("SelectEntity"); ?></b></label>
		</div>
		<div class="tagtd padding-left5" align="left">
			<?php echo $select_entity; ?>
		</div>
		<div class="tagtd padding-left5" align="left">
			<?php echo $langs->trans("DestinationEntityInfo"); ?>
		</div>
	</div>
	<div class="tagtr oddeven">
		<div class="tagtd padding-left5" align="left">
			<label for="MULTICOMPANY_IMPORT_KEY"><b><?php echo $langs->trans("ImportKey"); ?></b></label>
		</div>
		<div class="tagtd padding-left5" align="left">
			<input class="minwidth300 maxwidth400 widthcentpercentminusx" minlength="12" maxlength="128" type="text" id="MULTICOMPANY_IMPORT_KEY" name="MULTICOMPANY_IMPORT_KEY" value="<?php echo getDolGlobalString('MULTICOMPANY_IMPORT_KEY', getRandomPassword(true, null, 14)); ?>" autocomplete="off">
		</div>
		<div class="tagtd padding-left5" align="left">
			<?php echo $langs->trans("ImportKeyInfo"); ?>
		</div>
	</div>
</div>

<?php

print '</div>';

// Boutons actions
print '<div class="tabsAction">';
print '<input type="submit" id="save" name="save" class="butAction linkobject" value="'.$langs->trans("Save").'" />';
print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $_SERVER["PHP_SELF"].'?action=delvalue&token='.newToken());

print '</form>'."\n";

?>

<script type="text/javascript">
jQuery(document).ready(function() {
	var dbtype = jQuery("#MULTICOMPANY_IMPORT_DB_TYPE");
	var dbport = jQuery("#MULTICOMPANY_IMPORT_DB_PORT");
	// Automatically set default database ports and admin user
	if (dbtype.val() == 'sqlite' || dbtype.val() == 'sqlite3') {
		jQuery(".hidesqlite").hide();
	} else {
		jQuery(".hidesqlite").show();
	}
	if (dbtype.val() == 'mysql' || dbtype.val() == 'mysqli') {
		dbport.val(3306);
	} else if (dbtype.val() == 'pgsql') {
		dbport.val(5432);
	}
	dbtype.change(function () {
		if (dbtype.val() == 'sqlite' || dbtype.val() == 'sqlite3') {
			jQuery(".hidesqlite").hide();
		} else {
			jQuery(".hidesqlite").show();
		}
		// Automatically set default database ports and admin user
		if (dbtype.val() == 'mysql' || dbtype.val() == 'mysqli') {
			dbport.val(3306);
		} else if (dbtype.val() == 'pgsql') {
			dbport.val(5432);
		}
	});
});
</script>
<!-- END PHP TEMPLATE ADMIN IMPORT -->
