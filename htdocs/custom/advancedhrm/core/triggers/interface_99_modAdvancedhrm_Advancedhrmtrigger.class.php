<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		core/triggers/interface_99_modMyodule_Attachmentstrigger.class.php
 * 	\ingroup	attachments
 * 	\brief		Sample trigger
 * 	\remarks	You can create other triggers by copying this one
 * 				- File name should be either:
 * 					interface_99_modAttachments_Mytrigger.class.php
 * 					interface_99_all_Mytrigger.class.php
 * 				- The file must stay in core/triggers
 * 				- The class name must be InterfaceMytrigger
 * 				- The constructor method must be named InterfaceMytrigger
 * 				- The name property name must be Mytrigger
 */



/**
*  Class of triggers for Zapier module
*/
require_once (__DIR__.'/../../class/compteurHolidayManager.php');
class InterfaceAdvancedhrmtrigger extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "technic";
		$this->description = "Zapier triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = self::VERSION_DEVELOPMENT;
		$this->picto = 'zapier';
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string        $action     Event action code
	 * @param CommonObject  $object     Object
	 * @param User          $user       Object user
	 * @param Translate     $langs      Object langs
	 * @param Conf          $conf       Object conf
	 * @return int                      <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		global $db ;

		$logtriggeraction = false;
		$sql = '';
		if ($action != '') {
			$actions = explode('_', $action);
			$sql = 'SELECT rowid, url FROM '.MAIN_DB_PREFIX.'zapier_hook';
			$sql .= ' WHERE module="'.$this->db->escape(strtolower($actions[0])).'" AND action="'.$this->db->escape(strtolower($actions[1])).'"';
			//setEventMessages($sql, null);
		}

		switch ($action) {
			case 'HOLIDAY_CREATE':
				if(getDolGlobalString('ADVANCEDHRM_REQUESTDAY_MAXLIMIT_CHECK')) {

					$leaveTypeArray = explode(',', getDolGlobalString('ADVANCEDHRM_REQUESTDAY_MAXLIMIT_CHECK'));
					$leaveStartDate = $object->date_debut;
					$leaveEndDate = $object->date_fin;
					$leaveType = $object->fk_type;

					if (in_array($leaveType, $leaveTypeArray)) {

						$numberOfDays = compteurHolidayManager::calculateDaysDifference($leaveStartDate, $leaveEndDate);
						$nbLeave = $object->getCPforUser($object->fk_user, $leaveType);
						$daysOverLimit = $nbLeave - $numberOfDays;
						if ($daysOverLimit < 0) {
							setEventMessage($langs->trans('NotEnoughLeaveBalance'),'errors');
							dol_syslog($langs->trans('NotEnoughLeaveBalance'), LOG_ERR);
							return -1;
						}
					}
				}
				break;

			case  'EXPENSE_REPORT_DET_CREATE' :

				$Tuser = array();
				$Tuser = GETPOST('users','array');

				// ajout des users sur cette expenseReportDet
				if (!empty($Tuser)){
					$errors = 0;
					dol_include_once('advancedhrm/class/expenseReportDetUser.class.php');
					$db->begin();

					foreach ($Tuser as $u){
						$expUserDet = new ExpenseReportDetUser($db);
						$expUserDet->fk_expensereportdet = $object->id;
						$expUserDet->fk_user = $u;
						$res = $expUserDet->create($user);
						if ($res < 0 ){
							$errors++;
						}
					}
					if ($errors !== 0){
						$db->rollback();
						setEventMessage("error" . $db->lasterror);
					}else{
						$db->commit();
					}
				}
				break;

			case  'EXPENSE_REPORT_DET_MODIFY' :
				$error = 0;

				$lineId = GETPOST('rowid','int');
				$Tuser = array();
				$Tuser = GETPOST('users','array');
				//var_dump($object->id, $lineId, $Tuser,$_POST);exit;
				dol_include_once('advancedhrm/class/expenseReportDetUser.class.php');

				$expUserDet = new ExpenseReportDetUser($db);
				//$db->begin();
				$sql = " DELETE FROM ".MAIN_DB_PREFIX."expensereportdet_user WHERE fk_expensereportdet =".$lineId;

				$result = $db->query($sql);

				if ($result){
					// ajout des users sur cette expenseReportDet
					if (!empty($Tuser)){
						$errors = 0;
						foreach ($Tuser as $u){
							$expUserDet = new ExpenseReportDetUser($db);
							$expUserDet->fk_expensereportdet = $lineId;
							$expUserDet->fk_user = $u;
							$res = $expUserDet->create($user);
							if ($res < 0 ){
								$errors++;
							}
						}
					}
				}else{

					dol_syslog("Error delete expensereportdet '".$this->name."' for action '.$action.' launched by ".__FILE__." id=".$object->id);
				}
				if ($error > 0){
					setEventMessage($langs->trans("ErrAddUserTexpensereportDet",'errors'));
					return 0; // will aboard on top caller (updateline)
				}
				return $result;
				break;


			case  'EXPENSE_REPORT_DET_DELETE' :
				$error = 0;

				$lineId = GETPOST('rowid','int');
				$Tuser = array();

				dol_include_once('advancedhrm/class/expenseReportDetUser.class.php');

				$expUserDet = new ExpenseReportDetUser($db);

				$sql = " DELETE FROM ".MAIN_DB_PREFIX."expensereportdet_user WHERE fk_expensereportdet =".$lineId;

				$result = $db->query($sql);

				if (!$result){
					$error++;
					dol_syslog("Error delete expensereportdet '".$this->name."' for action '.$action.' launched by ".__FILE__." id=".$object->id);
				}

				if ($error > 0){
					setEventMessage($langs->trans("ErrAddUserTexpensereportDet",'errors'));
					return 0; // will aboard on top caller (updateline)
				}
				return $result;
				break;


		}
		if ($logtriggeraction) {
			dol_syslog("Trigger '".$this->name."' for action '.$action.' launched by ".__FILE__." id=".$object->id);
		}
		return 0;
	}
}
