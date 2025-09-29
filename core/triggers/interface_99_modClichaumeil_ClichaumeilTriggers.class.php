<?php
/* Copyright (C) 2023		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2025		Grégory Maza             <gregory.maza@atm-consulting.fr>
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
 * \file    core/triggers/interface_99_modClichaumeil_ClichaumeilTriggers.class.php
 * \ingroup clichaumeil
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modClichaumeil_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
require_once __DIR__.'/../../class/chaumeilrfa.class.php';


/**
 *  Class of triggers for Clichaumeil module
 */
class InterfaceClichaumeilTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		parent::__construct($db);
		$this->family = "demo";
		$this->description = "Clichaumeil triggers.";
		$this->version = self::VERSIONS['dev'];
		$this->picto = 'clichaumeil@clichaumeil';
	}

	/**
	 * Function called when a Dolibarr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('clichaumeil')) {
			return 0; // If module is not enabled, we do nothing
		}

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

		// You can isolate code for each action in a separate method: this method should be named like the trigger in camelCase.
		// For example : COMPANY_CREATE => public function companyCreate($action, $object, User $user, Translate $langs, Conf $conf)

		// Or you can execute some code here
		switch ($action) {  // @phan-suppress-current-line PhanNoopSwitchCases
			case 'LINEORDER_INSERT':
			case 'LINEORDER_MODIFY':
			case 'LINEPROPAL_INSERT':
			case 'LINEPROPAL_MODIFY':

			//Clean fields
			$height = 0;
			$length = 0;
			if (!empty($object->array_options["options_clichaumeil_height"]) && !empty($object->array_options["options_clichaumeil_length"])){
				$height = abs(price2num($object->array_options["options_clichaumeil_height"]));
				$length = abs(price2num($object->array_options["options_clichaumeil_length"]));
				$object->array_options["options_clichaumeil_height"] = $height;
				$object->array_options["options_clichaumeil_length"] = $length;
			}


			if ($height > 0 && $length > 0) {
				// Get rowid from c_units dictionary for the 'CM2' code
				$object->fk_unit = (int)dol_getIdFromCode($this->db, 'CM2', 'c_units', 'code', 'rowid');
				if ($object->fk_unit <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
					return -1;
				}
				$object->qty = (float)$height * (float)$length;
				setEventMessages($langs->trans('SurfaceRecalculatedInCm2'), null, 'mesgs');

			}
			//For escape infinity loop ! use notriggers 1 !
			$result = $object->update($user, 1);
			if ($result <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
				return -1;
			}

			default:
				dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);
				break;

			case 'ORDER_VALIDATE':

				//Check for massaction
				if (empty($object->thirdparty)){
					$object->fetch_thirdparty();
				}

				//Check extrafield(Thirdparty) ref_required & field object->ref_client(Commande)
				$customerRefRequired = $object->thirdparty->array_options['options_clichaumeil_ref_required'];
				$customerRefCommande = $object->ref_client;

				if ($customerRefRequired == 1 && empty($customerRefCommande)){
					setEventMessages($langs->trans('CliChaumeilCustomerRefRequired',$object->getNomUrl()), null, 'errors');
					return -1;
				}
		}

		return 0;
	}
}
