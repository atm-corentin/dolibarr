<?php
/* Copyright (C) 2025		SuperAdmin
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
 * \file    clichaumeil/lib/clichaumeil.lib.php
 * \ingroup clichaumeil
 * \brief   Library files with common functions for Clichaumeil
 */

/**
 * Prepare admin pages header
 *
 * @return array<array{string,string,string}>
 */
function clichaumeilAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("clichaumeil@clichaumeil");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/clichaumeil/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Contract");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/clichaumeil/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'clichaumeil@clichaumeil');
	complete_head_from_modules($conf, $langs, null, $head, $h, 'clichaumeil@clichaumeil', 'remove');

	return $head;
}

/**
 * Ensure supplier proposal extrafield definition uses stable keys and migrate data if needed
 *
 * @return void
 */
function clichaumeilNormalizeSupplierProposalStatusExtrafield()
{
	static $normalized = false;

	if ($normalized) {
		return;
	}

	global $db, $langs, $conf;

	dol_include_once('/core/class/extrafields.class.php');
	dol_include_once('/core/class/translate.class.php');

	$attrname = 'clichaumeil_supplierstatut';
	$elementtype = 'supplier_proposal';

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label($elementtype, true, $attrname);

	if (empty($extrafields->attributes[$elementtype]['label'][$attrname])) {
		$normalized = true;
		return;
	}

	$langs->load('clichaumeil@clichaumeil');

	$currentOptions = $extrafields->attributes[$elementtype]['param'][$attrname]['options'] ?? array();
	$expectedOptions = clichaumeilGetSupplierStatusOptions();

	$needsUpdate = false;
	foreach ($expectedOptions as $key => $label) {
		if (!isset($currentOptions[$key])) {
			$needsUpdate = true;
			break;
		}
	}

	if ($needsUpdate) {
		$attr = $extrafields->attributes[$elementtype];

		$moreparams = array(
			'css' => $attr['css'][$attrname] ?? '',
			'cssview' => $attr['cssview'][$attrname] ?? '',
			'csslist' => $attr['csslist'][$attrname] ?? ''
		);

		$extrafields->update(
			$attrname,
			$attr['label'][$attrname],
			$attr['type'][$attrname],
			$attr['size'][$attrname],
			$elementtype,
			$attr['unique'][$attrname] ?? 0,
			$attr['required'][$attrname] ?? 0,
			$attr['pos'][$attrname] ?? 0,
			array('options' => $expectedOptions),
			$attr['alwayseditable'][$attrname] ?? 0,
			$attr['perms'][$attrname] ?? '',
			$attr['list'][$attrname] ?? '',
			$attr['help'][$attrname] ?? '',
			$attr['default'][$attrname] ?? '',
			$attr['computed'][$attrname] ?? '',
			$attr['entityid'][$attrname] ?? '',
			$attr['langfile'][$attrname] ?? '',
			$attr['enabled'][$attrname] ?? '1',
			$attr['totalizable'][$attrname] ?? 0,
			$attr['printable'][$attrname] ?? 0,
			$moreparams
		);

		clichaumeilUpdateSupplierStatusStoredValues();
	}

	$normalized = true;
}

/**
 * Return expected select options (key => label)
 *
 * @return array<string,string>
 */
function clichaumeilGetSupplierStatusOptions() : array
{
	global $langs;

	$langs->load('clichaumeil@clichaumeil');

	return array(
		'CLICHAUMEIL_PENDING_FILE' => $langs->trans('CLICHAUMEIL_PENDING_FILE'),
		'CLICHAUMEIL_FILE_RECEIVED' => $langs->trans('CLICHAUMEIL_FILE_RECEIVED'),
	);
}

/**
 * Build a mapping of stored labels to normalized keys
 *
 * @return array<string,string>
 */
function clichaumeilGetSupplierStatusLabelMap() : array
{
	global $conf;

	dol_include_once('/core/class/translate.class.php');

	$map = array(
		'CLICHAUMEIL_PENDING_FILE' => 'CLICHAUMEIL_PENDING_FILE',
		'CLICHAUMEIL_FILE_RECEIVED' => 'CLICHAUMEIL_FILE_RECEIVED',
	);

	$moduleRoot = dirname(__DIR__);
	$langDir = $moduleRoot . '/langs';

	if (is_dir($langDir)) {
		$entries = scandir($langDir);
		if ($entries !== false) {
			foreach ($entries as $code) {
				if ($code === '.' || $code === '..') {
					continue;
				}
				$fullPath = $langDir . '/' . $code;
				if (!is_dir($fullPath)) {
					continue;
				}
				$tmpLang = new Translate('', $conf);
				$tmpLang->setDefaultLang($code);
				$tmpLang->loadLangs(array('clichaumeil@clichaumeil'));
				$map[$tmpLang->transnoentities('CLICHAUMEIL_PENDING_FILE')] = 'CLICHAUMEIL_PENDING_FILE';
				$map[$tmpLang->transnoentities('CLICHAUMEIL_FILE_RECEIVED')] = 'CLICHAUMEIL_FILE_RECEIVED';
			}
		}
	}

	return array_filter($map);
}

/**
 * Normalize stored extrafield values to use stable keys
 *
 * @return void
 */
function clichaumeilUpdateSupplierStatusStoredValues()
{
	global $db;

	$map = clichaumeilGetSupplierStatusLabelMap();
	if (empty($map)) {
		return;
	}

	foreach ($map as $oldValue => $newValue) {
		if ($oldValue === $newValue || $oldValue === '') {
			continue;
		}

		$sql = "UPDATE " . $db->prefix() . "supplier_proposal_extrafields";
		$sql .= " SET clichaumeil_supplierstatut = '" . $db->escape($newValue) . "'";
		$sql .= " WHERE clichaumeil_supplierstatut = '" . $db->escape($oldValue) . "'";

		$db->query($sql);
	}
}

/**
 * Trigger executed by externalaccess module to let other modules add controllers.
 *
 * @param EAccessController $controllerContext The controller context object from externalaccess (it's the "$this" from the calling file)
 * @param User $user The Dolibarr user object
 * @param Translate $langs The Dolibarr lang object
 * @param Conf $conf The Dolibarr conf object
 * @return int                                <0 if KO, 0 if OK
 */
function externalAccessInitController($controllerContext, $user, $langs, $conf) : int {

	// Register supplier_proposal list controller
	$newControllerKey = 'supplier_proposal';
	$newControllerPath = dol_buildpath('/clichaumeil/www/controllers/supplierProposal.controller.php');
	$newControllerClass = 'SupplierProposalController';

	$controllerContext->addControllerDefinition(
		$newControllerKey,
		$newControllerPath,
		$newControllerClass
	);

	// Register supplier_proposal_card detail controller
	$cardControllerKey = 'supplier_proposal_card';
	$cardControllerPath = dol_buildpath('/clichaumeil/www/controllers/supplierProposalCard.controller.php');
	$cardControllerClass = 'SupplierProposalCardController';

	$controllerContext->addControllerDefinition(
		$cardControllerKey,
		$cardControllerPath,
		$cardControllerClass
	);

	return 0; // 0 = OK (tells Dolibarr the trigger ran successfully)
}
