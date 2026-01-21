<?php
/* Copyright (C) 2020 SuperAdmin
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
 * \file    hidetopmenu/lib/hidetopmenu.lib.php
 * \ingroup hidetopmenu
 * \brief   Library files with common functions for hidetopmenu
 */

/**
 * Get all entries on the top menu from database.
 *
 * @return array        Menu entries
 */
function getTopMenuEntriesFromDatabase()
{
	global $db, $conf;

	$ret = array();
	$sql = "SELECT module, mainmenu, titre FROM ".MAIN_DB_PREFIX."menu WHERE type = 'top' AND entity = $conf->entity";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$ret[$obj->module] = array('title' => $obj->titre, 'mainmenu' => $obj->mainmenu);
		}
	}

	return $ret;
}

/**
 * Get all entries on the top menu based on eldy menu.
 *
 * @return array        Menu entries
 */
function getTopMenuEntries()
{
	global $conf;

	$entries = array();

	// Add static core modules entries (based on eldy menu)
	$entries['members'] = array('title' => "MenuMembers", 'mainmenu' => "members");
	$entries['companies'] = array('title' => "ThirdParties", 'mainmenu' => "companies");
	$entries['products'] = array('title' => (!empty($conf->product->enabled) && !empty($conf->service->enabled))
		? (array("TMenuProducts", " | ", "TMenuServices"))
		: (!empty($conf->product->enabled) ? "TMenuProducts" : "TMenuServices"), 'mainmenu' => "products");
	$entries['mrp'] = array('title' => "TMenuMRP", 'mainmenu' => "mrp");
	$entries['project'] = array('title' => (empty($conf->global->PROJECT_USE_OPPORTUNITIES) || $conf->global->PROJECT_USE_OPPORTUNITIES == 2)
		? (($conf->global->PROJECT_USE_OPPORTUNITIES == 2) ? "Leads" : "Projects")
		: "Projects", 'mainmenu' => "project");
	$entries['commercial'] = array('title' => "Commercial", 'mainmenu' => "commercial");
	$entries['billing'] = array('title' =>  "MenuFinancial", 'mainmenu' => "billing");
	$entries['bank'] = array('title' =>  "MenuBankCash", 'mainmenu' => "bank");
	$entries['accountancy'] = array('title' =>  "MenuAccountancy", 'mainmenu' => "accountancy");
	$entries['hrm'] = array('title' =>  "HRM", 'mainmenu' => "hrm");
	$entries['tools'] = array('title' =>  "Tools", 'mainmenu' => "tools");

	// Dolibarr 14 delete ticket top menu from database so we need this check to make it appear on the list
	if (versioncompare(array(DOL_VERSION), array('14.0.0')) >= 0 && ($conf->ticket->enabled ?? false)) {
		$entries['ticket'] = array('title' =>  "Ticket", 'mainmenu' => "ticket");
	}

	// Add external modules entries
	$additionalEntries = getTopMenuEntriesFromDatabase();
	if (count($additionalEntries) > 0) {
		$entries = array_merge($entries, $additionalEntries);
	}

	return $entries;
}
