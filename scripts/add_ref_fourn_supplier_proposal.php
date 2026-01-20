<?php
/* Add ref_fourn column to supplier_proposal if missing. */

$res = @include("../../main.inc.php"); // From htdocs directory.
if (! $res) {
	$res = @include("../../../main.inc.php"); // From custom directory.
}
if (! $res) {
	die('Failed to load Dolibarr environment.');
}

if (php_sapi_name() !== 'cli') {
	if (empty($user->admin)) {
		accessforbidden();
	}
}

$table = $db->prefix() . 'supplier_proposal';
$sql = "SHOW COLUMNS FROM " . $table . " LIKE 'ref_supplier'";
$resql = $db->query($sql);
if (! $resql) {
	print 'Error checking column: ' . $db->lasterror() . "\n";
	exit(1);
}

if ($db->num_rows($resql) > 0) {
	print "Column ref_supplier already exists on " . $table . ".\n";
	exit(0);
}

$alter = "ALTER TABLE " . $table . " ADD COLUMN ref_supplier varchar(255) DEFAULT NULL AFTER ref_ext";
if (! $db->query($alter)) {
	print 'Error adding column: ' . $db->lasterror() . "\n";
	exit(1);
}

print "Column ref_supplier added on " . $table . ".\n";
