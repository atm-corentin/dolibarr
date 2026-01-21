<?php
/*  Copyright (C) 2025      Open-DSI             <support@open-dsi.fr>
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
 */

/**
 *  \file       htdocs/sirene/core/triggers/interface_99_modSirene_Sirene.class.php
 *  \ingroup    sirene
 *  \brief      File of class of triggers for sirene module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
dol_include_once('/sirene/class/sirene.class.php');


/**
 *  Class of triggers for Sirene module
 */
class InterfaceSirene extends DolibarrTriggers
{
	public $family = 'sirene';
	public $description = "Triggers of this module catch triggers event for the Sirene module.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'technic';

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->sirene) || empty($conf->sirene->enabled)) {
			return 0; // Module not active, we do nothing
		}

		switch ($action) {
			case 'COMPANY_CREATE':
			//case 'COMPANY_MODIFY':
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

				/**
				 * @var CommonObject	$object
				 */
				if (!empty($object->array_options['options_sirene_company_admin_status']) &&
					in_array($object->array_options['options_sirene_company_admin_status'], [ Sirene::COMPANY_ADMIN_STATUS_OPENED, Sirene::COMPANY_ADMIN_STATUS_CLOSED ])
				) {
					$object->array_options['options_sirene_update_date'] = dol_now();
					$result = $object->insertExtraFields();
					if ($result < 0) {
						return -1;
					}
				}
				break;
		}

		return 0;
	}
}
