<?php
/* Copyright (C) 2022	Regis Houssin	<regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

/*global $usercanread, $usercancreate, $usercandelete, $usercanvalidate, $usercansend, $usercanreopen, $usercanunvalidate;
global $permissionnote, $permissiondellink, $permissiontoedit;
global $disableedit, $disablemove, $disableremove;

if (empty($user->rights->multicompany->invoice->read)) {
	$usercanread = false;
}
if (empty($user->rights->multicompany->invoice->write)) {
	$usercancreate = false;

	$permissionnote = $usercancreate; // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $usercancreate;	// Used by the include of actions_dellink.inc.php
	$permissiontoedit = $usercancreate; // Used by the include of actions_lineupdown.inc.php

	// for object lines
	$disableedit = true;
	$disablemove = true;
	$disableremove = true;
}
if (empty($user->rights->multicompany->invoice->delete)) {
	$usercandelete = false;
}
if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($usercancreate))
	|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->multicompany->invoice_advance->validate))) {
	$usercanvalidate = false;
}
if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($usercanread))
	|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->multicompany->invoice_advance->send))) {
	$usercansend = false;
}
if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($usercancreate))
	|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->multicompany->invoice_advance->reopen))) {
	$usercanreopen = false;
}
if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($usercancreate))
	|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->multicompany->invoice_advance->unvalidate))) {
	$usercanunvalidate = false;
}*/

?>
