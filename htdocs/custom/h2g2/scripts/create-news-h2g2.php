#!/usr/bin/env php
<?php
/* Copyright (C) 2020-2021 Hugo Allegaert <hugo@code42.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/h2g2/scripts/create-news-h2g2.php
 *        \ingroup    h2g2
 *      \brief      create news from h2g2 for all users
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

// Global variables
$version = '1.0';
$error = 0;


// -------------------- START OF YOUR CODE HERE --------------------
@set_time_limit(0); // No timeout for this script
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 1); // Set this define to 0 if you want to lock your script when dolibarr setup is "locked to admin user only".

// Load Dolibarr environment
$res = 0;
// Try master.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/master.inc.php")) { $res = @include substr($tmp, 0, ($i + 1))."/master.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/master.inc.php")) { $res = @include dirname(substr($tmp, 0, ($i + 1)))."/master.inc.php";
}
// Try master.inc.php using relative path
if (!$res && file_exists("../master.inc.php")) { $res = @include "../master.inc.php";
}
if (!$res && file_exists("../../master.inc.php")) { $res = @include "../../master.inc.php";
}
if (!$res && file_exists("../../../master.inc.php")) { $res = @include "../../../master.inc.php";
}
if (!$res) {
	print "Include of master fails";
	exit(-1);
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";

if ($argc < 4) {// Check parameters
	print "Usage: ".$script_file.":\n";
	print "- param1 = [Title]       Title for the news (exemple: 'News of the month')\n";
	print "- param2 = [Content]     Content for the news (exemple: '1. Hello 2. Bye')\n";
	print "- param3 = [Entity]      Entity where news will be publish\n";
	print "- param4 = [ArticleId]   ID of the related article (If none, no article will be linked)\n";
	print "- param5 = [User]        User that create the news (If none, 1 will be the default value)\n";

	exit(-1);
}

print '--- start'."\n";

require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

$title = substr(json_encode($argv[1]), 1, -1);
$content = substr(json_encode($argv[2]), 1, -1);
$entity = $argv[3];

if (!isset($argv[5])) {
	$user_id = 1;
} else {
	$user_id = intval($argv[5]);
}

$actioncomm = new ActionComm($db);

$actioncomm->userownerid = $user_id;
$actioncomm->label = $title;
$actioncomm->note = $content;
$actioncomm->type_code = 448310;
$conf->entity = intval($entity);

if (isset($argv[4])) {
	$actioncomm->fk_element = intval($argv[4]);
	$actioncomm->elementtype = 'article@lareponse';
}

$res = $actioncomm->create($user);

// Request to set all users news viewed at 0
$sql = "UPDATE " . MAIN_DB_PREFIX . "user_extrafields SET news_viewed = 0";
$sql .= " WHERE news_viewed = 1";
$sql .= " AND !isnull (news_viewed)";
$resql_users = $db->query($sql, 0, 'ddl');

if ($res > 0) {
	print "News created with id ".$res."\n";
	print '--- end ok'."\n";
} else {
	print '--- end error'."\n";
	exit(-1);
}

exit(0);
