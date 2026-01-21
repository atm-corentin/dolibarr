<?php
/* Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 * or see https://www.gnu.org/
 */

/**
 *      \file       htdocs/core/class/antivir.class.php
 *      \brief      File of class to scan viruses
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
dol_include_once('/ganttproadvanced/class/ganttproadvanced.class.php');
dol_include_once('/ganttproadvanced/class/ganttproadvancedutils.class.php');

/**
 * Class Actionsganttproadvanced
 */
class Actionsganttproadvanced
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * Constructor
	 */
	public function __construct()
	{

	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $db, $user, $sldprogress, $newtask;

		if(empty($user->rights->ganttproadvanced->lire)) return 0;

		$withproject = GETPOST('withproject');
		$currentpage = explode(':', $parameters['context']);

		if(floatval(DOL_VERSION) < 17 && in_array('projecttaskcard', $currentpage) || in_array('projecttaskscard', $currentpage)) {

			if($action == 'confirm_clone'){
				$clone_file = GETPOST('clone_task_files');
				$clone_notes = GETPOST('clone_notes');
				$clone_contacts = GETPOST('clone_contacts');
				$id = GETPOST('id');
				$taskstatic = new Task($db);
				$taskstatic->fetch($id);
				$oldcopy = new Task($db);
				$result = $oldcopy->createFromClone($user, $taskstatic->id, $taskstatic->fk_project, $taskstatic->fk_task_parent, true, $clone_contacts, false, $clone_file, $clone_notes);
				if($result>0){
					header("Location: ".DOL_URL_ROOT.'/projet/tasks/task.php?id='.$result.($withproject ? '&withproject='.$withproject : '').(empty($mode) ? '' : '&mode='.$mode));
					exit();
				}
				else{
					setEventMessages($oldcopy->error, $oldcopy->errors, 'errors');
					$action = '';
				}
			}

		}

		

	}
	
	
	public function completeTabsHead($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user, $executedscript;
		
		if(empty($user->rights->ganttproadvanced->lire)) return 0;

 		if(!isset($conf->global->GANTTPROADVANCED_HIDE_FINISHED_TASK) || (isset($conf->global->GANTTPROADVANCED_HIDE_FINISHED_TASK) && !$conf->global->GANTTPROADVANCED_HIDE_FINISHED_TASK)) return 0;

		$currentpage = explode(':', $parameters['context']);

		if((in_array('timesheetperdaycard', $currentpage) || in_array('timesheetpermonthcard', $currentpage) || in_array('timesheetperweekcard', $currentpage))) {

			global $db, $user;

			if($executedscript) return 0;

			require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
			$socid = 0;
			if ($user->societe_id > 0) $socid=$user->societe_id;

			$search_usertoprocessid = GETPOST('search_usertoprocessid', 'int') ? GETPOST('search_usertoprocessid', 'int') : $user->id;

			// $morewherefilter = ' AND DATEDIFF(NOW(), t.datee) >= 30 AND t.datee < NOW() ';
			$morewherefilter = '';

			$ganttproadvancedutils = new ganttproadvancedutils($db);

			$arrayoftasks = array();
			
			$ganttproadvancedutils->ganttproadvancedTimesheetperdayGetTasks($arrayoftasks, 0, 0, 0, $socid, 0, '', $_onlyopenedproject = 1, $morewherefilter, ($search_usertoprocessid ? $search_usertoprocessid : 0));

			$all_tasks 		= $arrayoftasks['all_tasks'];
			$old_tasks 		= $arrayoftasks['old_tasks'];
			$parent_tasks 	= $arrayoftasks['parent_tasks'];

			// d($all_tasks);

			if(is_array($all_tasks) && count($all_tasks) > 0) {
				?>

				<style type="text/css">
				<?php
				foreach ($old_tasks as $id_task => $id_task2) {
					echo 'table.liste tr[data-taskid="'.$id_task.'"] { display:none; }';
				}
				?>
				</style>

				<script type="text/javascript">
					jQuery(document).ready(function() {
					<?php 
					if(is_array($all_tasks) && count($all_tasks) > 0) {
						foreach ($all_tasks as $key => $id_task){ 
							?>
							var jsidtask = '<?php echo (int)$id_task; ?>';
						 	items = $('table.liste tr[data-taskid="' + jsidtask + '"]');
							
							<?php
							if(isset($old_tasks[$id_task])) {
								?>
							 	items.addClass('ganttproadvanced_hiddentask hidden');
								<?php 
							}
							if(isset($parent_tasks[$id_task])) {
								?>
							 	items.addClass('ganttproadvanced_activity_cantedit');
								<?php 
							}
						}
					}
					?>

					if(!$('#buttonshowhidetask').length)
	            	 $(".colorbacktimesheet").append('<a class="button valignmiddle" id="buttonshowhidetask" onclick="clickedbuttonshowhidetask()"><?php echo $langs->trans('FinishedTasks'); ?></a>');

					});

					function clickedbuttonshowhidetask(that) {
						$('tr.oddeven.ganttproadvanced_hiddentask').toggle("hidden");	
					}    
				</script>
				
				<?php
			}

			$executedscript = true;

		}

	}


	
	public function formContactTpl($parameters, &$object, &$action, $hookmanager)
	{
		global $db, $user;

		if(empty($user->rights->ganttproadvanced->lire)) return 0;

		$ganttproadvanced = new ganttproadvanced($db);

		$currentpage = explode(':', $parameters['context']);

		if(!in_array('contacttpl', $currentpage) || !$ganttproadvanced->t_typecontact) return 0;

	    $codetypeco = '';

	    $sqltype = "SELECT tc.code FROM ".MAIN_DB_PREFIX."c_type_contact as tc WHERE tc.rowid='".$ganttproadvanced->t_typecontact."'";
	    $resqltype = $db->query($sqltype);
		if ($resqltype) {
			while ($obj = $db->fetch_object($resqltype)) {
				$codetypeco = $obj->code;
			}
		}

		if(!$codetypeco) return 0;

		$preventadd = false;

	    $tab = $object->liste_contact(-1, 'internal', 0, $codetypeco);
	    if(is_array($tab) && count($tab) > 0) $preventadd = true;


	    if($preventadd) {
			?>
			<script type="text/javascript">
				$(document).ready(function() {
					var selecttypecontact = $('form[action*="projet/tasks/contact.php?id="] select[name="type"]');
					selecttypecontact.find('option[value="<?php echo (int) $ganttproadvanced->t_typecontact; ?>"]').remove();
				});
			</script>
			<?php
	    }

	    return 0;

	}


	
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $db, $user, $sldprogress;

		$withproject = GETPOST('withproject');

		if(empty($user->rights->ganttproadvanced->lire)) return 0;
		
		if($parameters['currentcontext'] == 'projectcard' && $object->date_start && $object->date_end){

			require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
			
			$start = dol_getdate($object->date_start);
			$end = dol_getdate($object->date_end);
			
			$statusprojet = (isset($object->statut)) ? $object->statut : $object->status;

			echo '<a href="'.dol_buildpath('ganttproadvanced/index.php?idp='.$object->id.'&debutyear='.$start['year'].'&debutmonth='.$start['mon'].'&finyear='.$end['year'].'&finmonth='.$end['mon'].'&search_status='.$statusprojet, 1).'" class="butAction badge-status1" target="_blank" ><span class="fa fa-stream valignmiddle"></span> '.$langs->trans('viewgantt').'</a>';
		}

		elseif(floatval(DOL_VERSION) < 17 && $parameters['currentcontext'] == 'projecttaskcard') {

			echo '<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$object->id.'&action=cloner'.($withproject ? '&withproject='.$withproject : '').'" class="butAction" >'.$langs->trans('ToClone').'</a>';

			if($action == 'cloner'){
				$form = new Form($db);
				
					$formquestion = array(
						'text' => $langs->trans("ConfirmClone"),
						array('type' => 'checkbox', 'name' => 'clone_contacts', 'label' => $langs->trans("CloneContacts"), 'value' => true),
						array('type' => 'checkbox', 'name' => 'clone_notes', 'label' => $langs->trans("CloneNotes"), 'value' => true),
						array('type' => 'checkbox', 'name' => 'clone_task_files', 'label' => $langs->trans("CloneTaskFiles"), 'value' => false)
					);

					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id.($withproject ? '&withproject='.$withproject : ''), $langs->trans("ToClone"), $langs->trans("ConfirmCloneTask"), "confirm_clone", $formquestion, '', 1, 250, 590);
			}
		}

	}


	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $db, $user, $sldprogress;

		// if(empty($user->rights->ganttproadvanced->lire)) return 0;
		
		$currentpage = explode(':', $parameters['context']);
		
		if(in_array('projecttaskcard', $currentpage) || in_array('projecttaskscard', $currentpage)) {

			$ganttproadvanced = new ganttproadvanced($db);

			$sldprogress = GETPOST('progress', 'alphanohtml');
			
			$action = GETPOST('action', 'alpha');
			$id 	= GETPOST('id', 'int');

			if($action == 'edit' && $id > 0) {
				$task = new Task($db);
				$task->fetch($id);
				$sldprogress = $task->progress;
			}

			$formother = new FormOther($db);
			$selectprogress = $formother->select_percent($sldprogress, 'progress', 0, 1, 0, 100, 1);

			if($ganttproadvanced->coloredbyuser && ($action == 'create' || $action == 'edit')) { ?>
				<style type="text/css">.project_task_extras_ganttproadvancedcolor {display: none;}</style>
			<?php } ?>
			<script type="text/javascript">
				$(document).ready(function(){
					if($('#options_ganttproadvancedcolor').length > 0)
        			$('#options_ganttproadvancedcolor').attr('type', 'color');
					var progress = $('form select[name="progress"]');
					if(progress.length > 0) {
						progress.parent('td').html('<?php echo $selectprogress; ?>');
					}
					var color = $('table.border td[id*="project_task_extras_ganttproadvancedcolor_"]').text();
				    if(color) {
				        $('table.border td[id*="project_task_extras_ganttproadvancedcolor_"]').html('<span class="span-color" style="background-color: '+color+';color: transparent;">'+color+'</span>');
				    }
				});
			</script>
			<?php

		}
	}

}
