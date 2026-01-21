<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020 SuperAdmin
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    h2g2/migration_page.php
 * \ingroup h2g2
 * \brief   Migration page for all Code 42 modules extending TheGalaxy.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

use \h2g2\MigrationManager;

dol_include_once('/h2g2/class/migrationmanager.class.php');
dol_include_once('/h2g2/lib/h2g2.lib.php');

global $db, $langs, $user;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
require_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('h2g2'));

// Translations
$langs->loadLangs(array("errors", "admin", "h2g2@h2g2"));

// Load module informations
$module = GETPOST('module', 'alpha');               // Module name
$modulePath = GETPOST('modulePath', 'alpha');       // /contratplus/core/modules/modContratPlus.class.php (%2Fcontratplus%2Fcore%2Fmodules%2FmodContratPlus.class.php)
$action = GETPOST('action', 'alpha');

$err = 0;
$errMsg = '';
$urlOptions = 'module=' . $module . '&modulePath=' . $modulePath;

if ($module && $modulePath) {
	// Include and create instance of the module
	dol_include_once($modulePath);
	if (class_exists($module)) {
		$moduleInstance = new $module($db);
		if (!is_subclass_of($moduleInstance, 'TheGalaxy')) {
			$err++;
			$errMsg = $langs->trans('MigrationPageExtendError');
		}

		$versionInstalled = MigrationManager::getLastVersionInstalled($moduleInstance->rights_class);
		$mm = new MigrationManager($db, $module, $moduleInstance->rights_class, $versionInstalled, $moduleInstance->versionList, $moduleInstance->migrationPath);

		if ($mm) {
			$migrationFiles = $mm->getMigrationFiles();
			if (empty($migrationFiles[$versionInstalled])) {
				$versionCorrected = $mm->correctInstalledVersions($moduleInstance);
				if (!empty($versionCorrected)) {
					setEventMessages($langs->trans("H2G2CorrectedVersions", implode(",", $versionCorrected)), "", "warnings");
					exit(header("Location: " . $_SERVER["PHP_SELF"] . "?module=" . $module . "&modulePath=" . $modulePath));
				}
			}
			$versionMigrationFile = $migrationFiles[$versionInstalled];
			$prevVersion = $mm->getPrevVersion();
			$nextVersion = $mm->getNextVersion();
		} else {
			$err++;
			$errMsg = $langs->trans('MigrationManagerLoadingError');
		}
	} else {
		$err++;
		$errMsg = $langs->trans('MigrationPageLoadingModuleError');
	}
} else {
	$err++;
	$errMsg = $langs->trans('MigrationPageLoadingModuleError');
}

/*
 * Actions
 */

if ($err <= 0) {
	if ($action == 'previewRollback' || $action == 'previewReset') {
		// We load the actual version down script
		dol_include_once($versionMigrationFile['includepath']);
		$migrationObj = new $versionMigrationFile['classname']($db);
		$migrationObj->down();
		$queries = $migrationObj->getQueries();

		// If initial version we reset by down and up the module
		if ($mm->getPrevVersion() == $migrationObj->version) {
			$migrationObj->up();
			$queries = array_merge($queries, $migrationObj->getQueries());
		}
	}

	if ($action == 'previewUpdate') {
		// We load the next version update script
		if ($nextVersion && $migrationFiles[$nextVersion]) {
			dol_include_once($migrationFiles[$nextVersion]['includepath']);
			$migrationObj = new $migrationFiles[$nextVersion]['classname']($db);
			$migrationObj->up();
			$queries = $migrationObj->getQueries();
		}
	}

	if ($user->admin) {
		if ($action == 'executeRollback') {
			// We execute the actual down script
			if ($prevVersion && $migrationFiles[$versionInstalled]) {
				$mm->executeManualRollback($migrationFiles[$versionInstalled]);

				// Redirect after execution
				setEventMessage($langs->trans('MigrationExecuted', $prevVersion), 'mesgs');
				header('Location:' . $_SERVER['PHP_SELF'] . '?' . $urlOptions);
				exit;
			} else {
				// Error handling
				setEventMessage($langs->trans('MigrationExecutionError', $prevVersion), 'errors');
			}
		}

		if ($action == 'executeReset') {
			// We execute the actual down script
			if ($prevVersion && $migrationFiles[$versionInstalled]) {
				$mm->executeManualReset($migrationFiles[$versionInstalled]);

				// Redirect after execution
				setEventMessage($langs->trans('MigrationExecuted', $prevVersion), 'mesgs');
				header('Location:' . $_SERVER['PHP_SELF'] . '?' . $urlOptions);
				exit;
			} else {
				// Error handling
				setEventMessage($langs->trans('MigrationExecutionError', $prevVersion), 'errors');
			}
		}

		if ($action == 'executeUpdate') {
			// We execute the next version up script
			if ($nextVersion && $migrationFiles[$nextVersion]) {
				$mm->executeManualUpdate($migrationFiles[$nextVersion]);

				// Redirect after execution
				setEventMessage($langs->trans('MigrationExecuted', $nextVersion), 'mesgs');
				header('Location:' . $_SERVER['PHP_SELF'] . '?' . $urlOptions);
				exit;
			} else {
				// Error handling
				setEventMessage($langs->trans('MigrationExecutionError', $nextVersion), 'errors');
			}
		}
	}
}

/*
 * View
 */

$form = new Form($db);

$page_name = "MigrationManager";
$arrayofcss = array('h2g2/css/highlight.min.css', 'h2g2/css/migration.css', '//cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css');
$arrayofjs = array('h2g2/js/highlight.min.js', '//cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js');
llxHeader('', $langs->trans($page_name), '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre($langs->trans($page_name), '', 'migration@h2g2');

// Dynamic header configuration using generateMigrationTabHeader hook for context h2g2
$reshook = $hookmanager->executeHooks('generateMigrationTabHeader', array('module' => $module));
if ($hookmanager->resArray) {
	// Display the dol_fiche_head
	$head = $hookmanager->resArray['head'] ?? "";
	$activeTab = $hookmanager->resArray['active_tab'] ?? "";
	$langsFile = $hookmanager->resArray['langs'] ?? "";
	$icon = $hookmanager->resArray['header_icon'] ?? "";
	dol_fiche_head($head, $activeTab, $icon, 0, $langsFile);
}

if ($err > 0) {
	print $errMsg;
} else {
	print '<table class="noborder centpercent">';
	print '<tbody>';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans('Key') . '</td>';
	print '<td>' . $langs->trans('Value') . '</td>';
	print '</tr>';

	// Module name
	print '<tr>';
	print '<td>' . $langs->trans('ModuleName') . '</td>';
	print '<td>' . $moduleInstance->getName() . '</td>';
	print '</tr>';

	// Module version (db)
	print '<tr>';
	print '<td>' . $langs->trans('DBModuleVersion') . '</td>';
	print '<td>' . $versionInstalled . '</td>';
	print '</tr>';

	// Module version (module)
	print '<tr>';
	print '<td>' . $langs->trans('InstalledModuleVersion') . '</td>';
	print '<td>' . $moduleInstance->version . '</td>';
	print '</tr>';

	// Version list
	print '<tr>';
	print '<td>' . $langs->trans('ModuleVersionList') . '</td>';
	print '<td>' . implode(' / ', $moduleInstance->versionList) . '</td>';
	print '</tr>';

	// If initial version we execute a reset else rollback
	if ($prevVersion && $mm->getPrevVersion() == $versionInstalled) {
		print '<tr>';
		print '<td>' . $langs->trans('RefreshTo', $prevVersion) . '</td>';
		print '<td><a href="' . $_SERVER['PHP_SELF'] . '?action=previewReset&' . $urlOptions . '" class="butAction"><i class="fas fa-eye"></i>&nbsp;' . $langs->trans('ActionPreview') . '</a></td>';
		print '</tr>';
	} else {
		print '<tr>';
		print '<td>' . $langs->trans('RollbackTo', $prevVersion) . '</td>';
		print '<td><a href="' . $_SERVER['PHP_SELF'] . '?action=previewRollback&' . $urlOptions . '" class="butAction"><i class="fas fa-eye"></i>&nbsp;' . $langs->trans('ActionPreview') . '</a></td>';
		print '</tr>';
	}

	if ($nextVersion) {
		print '<tr>';
		print '<td>' . $langs->trans('UpdateTo', $nextVersion) . '</td>';
		print '<td><a href="' . $_SERVER['PHP_SELF'] . '?action=previewUpdate&' . $urlOptions . '" class="butAction"><i class="fas fa-eye"></i>&nbsp;' . $langs->trans('ActionPreview') . '</a></td>';
		print '</tr>';
	} else {
		print '<tr>';
		print '<td>' . $langs->trans('UpdateTo', '...') . '</td>';
		print '<td class="opacitymedium">' . $langs->trans('NoVersionToUpdateTo') . '</td>';
		print '</tr>';
	}

	print '</tbody>';
	print '</table>';

	// Load action to execute
	if ($action == 'previewRollback') {
		$actionToExecute = 'Rollback';
		$displayedVersion = $prevVersion;
	} else if ($action == 'previewReset') {
		$actionToExecute = 'Reset';
		$displayedVersion = $prevVersion;
	} else if ($action == 'previewUpdate') {
		$actionToExecute = 'Update';
		$displayedVersion = $nextVersion;
	}

	if (!empty($actionToExecute)) {
		if (is_array($queries)) {
			print '<div class="migrationPreview">';
			print '<div class="migrationPreview__title">' . $langs->trans('Migration' . $actionToExecute . 'Preview', $displayedVersion) . '</div>';
			print '<div class="migrationPreview__content">';
			print '<p>' . $langs->trans('MigrationPreviewDesc') . '</p>';
			print '<pre><code class="language-sql">';
			foreach ($queries as $query) {
				print $query . ";\n";
			}
			print '</code></pre>';
			print '</div>';
			print '<div class="migrationPreview__buttons">';
			print '<a href="' . $_SERVER['PHP_SELF'] . '?' . $urlOptions . '" class="butAction"><i class="fas fa-times"></i>&nbsp;' . $langs->trans('Cancel') . '</a>';
			if ($user->admin) {
				print '<a href="' . $_SERVER['PHP_SELF'] . '?action=execute' . $actionToExecute . '&' . $urlOptions . '" class="butAction"><i class="fas fa-check"></i>&nbsp;' . $langs->trans('Execute') . '</a>';
			} else {
				print '<a href="#" class="butActionRefused" title="' . $langs->trans('MustBeAdmin') . '"><i class="fas fa-check"></i>&nbsp;' . $langs->trans('Execute') . '</a>';
			}
			print '</div>';
			print '<script>hljs.highlightAll();</script>'; // Highlight with js using : https://highlightjs.org/
			print '</div>';
		} else {
			print $langs->trans('LoadQueriesError');
		}
	}

	// Migration history
	$versions = MigrationManager::getVersionHistory($moduleInstance->rights_class);

	print '<div class="historyContainer">';

	print '<div class="titre inline-block"><i class="fas fa-history"></i>&nbsp;' . $langs->trans('MigrationHistory') . '</div>';

	print '<table id="historyTable" class="display">';
	print '<thead>';
	print '<tr>';
	print '<th>' . $langs->trans('Version') . '</th>';
	print '<th>' . $langs->trans('Action') . '</th>';
	print '<th>' . $langs->trans('Date') . '</th>';
	print '</tr>';
	print '</thead>';
	print '<tbody>';
	foreach ($versions as $version) {
		print '<tr class="mpaction-' . $version->action . '">';
		print '<th>' . $version->module_version . '</th>';
		print '<th>';
		switch ($version->action) {
			case 'update':
				print '<i class="fas fa-long-arrow-alt-up"></i>&nbsp;' . $langs->trans('MPActionUpdate');
				break;
			case 'rollback':
				print '<i class="fas fa-undo"></i>&nbsp;' . $langs->trans('MPActionRollback');
				break;
			case 'reset':
				print '<i class="fas fa-sync-alt"></i>&nbsp;' . $langs->trans('MPActionReset');
				break;
			default:
				print '<i class="fas fa-plus"></i>&nbsp;' . $langs->trans('MPActionCreate');
				break;
		}
		print '</th>';
		print '<th>' . dol_print_date($version->date_creation, 'dayhour') . '</th>';
		print '</tr>';
	}
	print '</tbody>';
	print '</table>';
	print '</div>';

	print '<script>$(document).ready(function () {
                $(\'#historyTable\').DataTable({
                    order: [[ 2, "asc" ]],
                    language: {
                        url: "' . getDatatableLanguageUrl() . '"
                    },
                });
            });
        </script>';
}

// Page end
llxFooter();
$db->close();
