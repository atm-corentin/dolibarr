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

require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';

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

	$head[$h][0] = dol_buildpath("/clichaumeil/admin/commissions.php", 1);
	$head[$h][1] = $langs->trans("CliChaumeilCommissions");
	$head[$h][2] = 'commissions';
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
 * Trigger executed by externalaccess module to let other modules add controllers.
 *
 * @param object $controllerContext The controller context object from externalaccess (it's the "$this" from the calling file)
 * @param User $user The Dolibarr user object
 * @param Translate $langs The Dolibarr lang object
 * @param Conf $conf The Dolibarr conf object
 * @return int                                <0 if KO, 0 if OK
 */
function externalAccessInitController($controllerContext, $user, $langs, $conf): int
{

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
