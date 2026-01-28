<?php

$isCli = (PHP_SAPI === 'cli');
if ($isCli) {
	print "This script must be executed from the browser only.\n";
	exit(1);
}

require __DIR__ . '/../../../main.inc.php';

if (empty($user) || empty($user->id) || empty($user->admin)) {
	accessforbidden();
}

$langs->loadLangs(array('main', 'admin', 'clichaumeil@clichaumeil'));

$table = $db->prefix() . 'supplier_proposal';
$field = 'ref_supplier';

llxHeader('', 'Clichaumeil - Add ref_supplier on supplier_proposal');

print '<div class="fichecenter">';
print '<div class="titre">Clichaumeil - Add ref_supplier on supplier_proposal</div>';
print '<div class="underbanner clearboth"></div>';

$sqlCheck = "SHOW COLUMNS FROM " . $table . " LIKE '" . $db->escape($field) . "'";
$resCheck = $db->query($sqlCheck);
if (!$resCheck) {
	print '<div class="error">' . dol_escape_htmltag($db->lasterror()) . '</div>';
	print '</div>';
	llxFooter();
	exit;
}

if ($db->num_rows($resCheck) > 0) {
	print '<div class="ok">Column "' . dol_escape_htmltag($field) . '" already exists on ' . dol_escape_htmltag($table) . '.</div>';
	print '</div>';
	llxFooter();
	exit;
}

$db->begin();
$sqlAlter = "ALTER TABLE " . $table . " ADD COLUMN " . $field . " varchar(255) DEFAULT NULL AFTER ref_ext";
$resAlter = $db->query($sqlAlter);

if ($resAlter) {
	$db->commit();
	print '<div class="ok">Column "' . dol_escape_htmltag($field) . '" added to ' . dol_escape_htmltag($table) . '.</div>';
} else {
	$db->rollback();
	print '<div class="error">' . dol_escape_htmltag($db->lasterror()) . '</div>';
}

print '</div>';

llxFooter();
