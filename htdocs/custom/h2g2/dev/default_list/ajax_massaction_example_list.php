<?php
/* Copyright (C) 2022 Fabien FERNANDES ALVES <fabien@code42.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *       \file       /dev/default_list/ajax_massaction_example_list.php
 *        \ingroup    h2g2
 *        \brief      Page to manage massactions for the example listing
 */

if (! defined('NOTOKENRENEWAL')) {           define('NOTOKENRENEWAL', '1');                // Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
}

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) { $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) { $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) { $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) { $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) { $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php";
}
if (!$res) { die("Include of main fails");
}

global $langs;

$action = GETPOST('action', 'alphanohtml');
$selected = GETPOST('selected', 'none');

if (!empty($action)) {
	switch ($action) {
		case 'demo_slow': // Example with a slow massaction
			sleep(1);
			$ret = array(
			'status' => 200,
			'success' => true,
			'selected' => $selected
			);
		break;
		case 'demo_fast': // Example with a faster massaction
			foreach ($selected as $id) {
				// We can do something foreach selected id
			}

			if (rand(0, 1)) {
				$ret = array(
				'status' => 200,
				'success' => true,
				'selected' => $selected
				);
			} else {
				$ret = array(
				'status' => 500,
				'success' => false,
				'selected' => $selected
				);
			}
		break;
		default :
			$ret = array(
			'status' => 400,
			'success' => false,
			'msg' => $langs->trans('MassactionNotImplemented', $action),
			'selected' => $selected
			);
		break;
	}
} else {
	$ret = array(
		'status' => 404,
		'success' => false,
		'msg' => $langs->trans('EmptyMassaction'),
		'selected' => $selected
	);
}

echo json_encode($ret);
