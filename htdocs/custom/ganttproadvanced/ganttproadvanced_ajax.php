<?php
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");       // For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php"); // For "custom" 

require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

dol_include_once('/ganttproadvanced/class/ganttproadvanced.class.php');
dol_include_once('/ganttproadvanced/class/ganttproadvancedutils.class.php');

global $conf, $langs, $user;

$langs->loadlangs(array('projects', 'companies', 'other', 'ganttproadvanced@ganttproadvanced'));

$project = new Project($db);
$objtask = new Task($db);
$ganttproadvanced = new ganttproadvanced($db);
$ganttproadvancedutils = new ganttproadvancedutils($db);
$tmpuser = new User($db);
$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($objtask->table_element);

$colordefaulttask = $ganttproadvanced->coloredbyuser ? $ganttproadvanced->colorgristask : $ganttproadvanced->defaultcolortask;

$ajxaction = GETPOST('ajxaction', 'alpha');
$users_tasks = GETPOST("users_tasks", 'alpha');


$result = array();

$result['typemsg'] = 'error';
$result['error'] = 0;
$result['msg'] = '';
$result['csstoadd'] = '';

$colortask = $ganttproadvanced->colorgristask;

$color_changed = false;

$oldcolor = '';
$result = [];




if(
	($ajxaction == 'deleteobject' && !$user->rights->projet->supprimer) ||
	($ajxaction == 'closeobject' && !$user->rights->ganttproadvanced->ferme) || 
	(in_array($ajxaction, ['createtask','updatetask','clonetask','updateproject']) && !$user->rights->projet->creer)
) {
	$result['typemsg'] = 'error';
	$result['msg'] = $langs->trans('NotEnoughPermissions');
}



if($ajxaction == 'createtask' && $user->rights->projet->creer){


	$task	= GETPOST('task');
	$parent	= $task['parent'];

	$error = 0;

	$objtask_parent = 0;
	$projectid 	 = 0;

	$data = $task;

	if(strpos($parent, 'task') !== false) {
		// --------------------------------- Parent is Task
		$tmptask = new Task($db);
		$tid = str_replace("task","",$parent);
		$tmptask->fetch($tid);

		$objtask_parent = $tid;
		$projectid = $tmptask->fk_project;

	} else {
		// --------------------------------- Parent is Project
		$pid = str_replace("project","",$parent);
		$projectid = $pid;
	}

	$start_date = $task['start_date'];
	$end_date 	= $task['end_date'];
	$note_private 	= $task['note_private'];

	$date_array = date_parse($start_date);
	$date_start = dol_mktime($date_array['hour'], $date_array['minute'], 0, $date_array['month'], $date_array['day'], $date_array['year']);
	

	$date_array = date_parse($end_date);
	// $date_end = dol_mktime($date_array['hour'], $date_array['minute'], 0, $date_array['month'], $date_array['day'], $date_array['year']);
	$date_end = dol_mktime(23, 59, 0, $date_array['month'], $date_array['day'], $date_array['year']);

	$budget  = $task['budget'] ? price2num($task['budget']) : '';
	$progress = number_format($task['progress'],2)*100;
	$planned_workload = $task['planned_workload'];

	// foreach ($extrafields->attributes[$objtask->table_element]['label'] as $key => $label) {
	// 	$oprions_.$key = 	$task[$key];
	// }

	$contacts = $task['contacts'];


	$plannedworkload=0;
	if($task['planned_workload']){
		$planned_workload = explode(':', $task['planned_workload']);
		$planned_workload_h = $planned_workload[0]*3600;
		$planned_workload_m = $planned_workload[1]*60;
		$plannedworkload = $planned_workload_h+$planned_workload_m;
	}

	$defaultref = '';
	$obj = empty($conf->global->PROJECT_TASK_ADDON) ? 'mod_task_simple' : $conf->global->PROJECT_TASK_ADDON;
	if (!empty($conf->global->PROJECT_TASK_ADDON) && is_readable(DOL_DOCUMENT_ROOT."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.".php")) {
		require_once DOL_DOCUMENT_ROOT."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.'.php';
		$modTask = new $obj;
		$project->fetch($projectid);
		$project->fetch_thirdparty();
		$defaultref = $modTask->getNextValue($project->thirdparty, null);
	}

	if (is_numeric($defaultref) && $defaultref <= 0) {
		$defaultref = '';
	}


	// $date_start = '2022-01-04';
	// $date_end = '2022-01-05';


	if (!$error) {
		// $tmparray = $objtaskparent;
		// $projectid = $tmparray[0];
		// if (empty($projectid)) {
		// 	$projectid = $id; // If projectid is ''
		// }
		// $objtask_parent = $tmparray[1];
		// if (empty($objtask_parent)) {
		// 	$objtask_parent = 0; // If task_parent is ''
		// }

		$objtask = new Task($db);

		$objtask->fk_project = $projectid;
		$objtask->ref = $defaultref;
		$objtask->label = $task['text'];
		$objtask->description = $task['description'];
		$objtask->budget_amount = $task['budget'];
		// $objtask->description = $description;
		$objtask->planned_workload = $plannedworkload;
		$objtask->fk_task_parent = $objtask_parent;
		$objtask->date_c = dol_now();
		$objtask->date_start = $date_start;
		$objtask->date_end = $date_end;
		$objtask->progress = $progress;
		// $objtask->note_private = $note_private;

		// Fill array 'array_options' with data from add form
		// $ret = $extrafields->setOptionalsFromPost(null, $objtask);


		if (isset($extrafields->attributes[$objtask->table_element]['label']) && is_array($extrafields->attributes[$objtask->table_element]['label'])) {
			$extralabels = $extrafields->attributes[$objtask->table_element]['label'];
		}

		if (is_array($extralabels)) {
			// Get extra fields
			foreach ($extralabels as $key => $value) {
				// if($extrafields->attributes[$objtask->table_element]['enabled'][$key] && ($extrafields->attributes[$objtask->table_element]['list'][$key] == 1 || $extrafields->attributes[$objtask->table_element]['list'][$key] == 3)){
					if($key != 'ganttproadvancedcolor'){
						$key_type = $extrafields->attributes[$objtask->table_element]['type'][$key];
						if ($key_type == 'separate') {
							continue;
						}

						$enabled = 1;
						if (isset($extrafields->attributes[$objtask->table_element]['enabled'][$key])) {	// 'enabled' is often a condition on module enabled or not
							$enabled = dol_eval($extrafields->attributes[$objtask->table_element]['enabled'][$key], 1, 1, '1');
						}

						$visibility = 1;
						if (isset($extrafields->attributes[$objtask->table_element]['list'][$key])) {		// 'list' is option for visibility
							$visibility = dol_eval($extrafields->attributes[$objtask->table_element]['list'][$key], 1, 1, '1');
						}

						$perms = 1;
						if (isset($extrafields->attributes[$objtask->table_element]['perms'][$key])) {
							$perms = dol_eval($extrafields->attributes[$objtask->table_element]['perms'][$key], 1, 1, '1');
						}
						if (empty($enabled)) {
							continue;
						}
						if (empty($visibility)) {
							continue;
						}
						if (empty($perms)) {
							continue;
						}

						if (in_array($key_type, array('date'))) {
							// Clean parameters

							$date_array = date_parse($task[$key]);
							$value_key = dol_mktime($date_array['hour'], $date_array['minute'], 0, $date_array['month'], $date_array['day'], $date_array['year']);
			
						} elseif (in_array($key_type, array('datetime'))) {

							$date_array = date_parse($task[$key]);
							$value_key = dol_mktime($date_array['hour'], $date_array['minute'],  $date_array['sec'], $date_array['month'], $date_array['day'], $date_array['year']);
				
						} elseif (in_array($key_type, array('checkbox', 'chkbxlst'))) {
							$value_arr = $task[$key]; // check if an array
							if (!empty($value_arr)) {
								$value_key = implode(',', $value_arr);
							} else {
								$value_key = '';
							}
						} elseif (in_array($key_type, array('price', 'double'))) {
							$value_arr = $task[$key];
							$value_key = price2num($value_arr);
						} else {
							$value_key = $task[$key];
						}

						$objtask->array_options["options_".$key] = $value_key;
					}
				// }
			}
		}



		// unset($data['start_date']);
		// unset($data['end_date']);
		$objtaskid = $objtask->create($user);

		$result['ref_task'] = $objtask->ref;
		
		if ($objtaskid > 0) {
			$tasks = new Task($db);
			$tasks->fetch($objtaskid);

			if($note_private){
				$res = $tasks->update_note(dol_html_entity_decode($note_private, ENT_QUOTES | ENT_HTML5, 'UTF-8', 1), '_private');
			}

			if($contacts){
				$contacts = explode(',', $contacts);
				$projects = new Project($db);
				$projects->fetch($tasks->fk_project);
				$contacts_projet = $projects->liste_contact(-1, 'internal', 0, 'PROJECTCONTRIBUTOR');
				if($contacts && count($contacts)>0){
					foreach ($contacts as $key => $value) {
						if($value != $task['affected_user']){
							$res = $tasks->add_contact($value, 'TASKCONTRIBUTOR', 'internal', 1);
							$d = $projects->add_contact($value, 'PROJECTCONTRIBUTOR', 'internal', 1);
						}

						if($objtask->fk_task_parent){
							$taskparent = new Task($db);
							$taskparent->fetch($objtask->fk_task_parent);
							$tmp_res = $taskparent->add_contact($value, 'TASKCONTRIBUTOR', 'internal', 1);
						}
					}
					
				}
			}

			// if(!$ganttproadvanced->coloredbyuser) {
			// 	$res = $objtask->add_contact($user->id, 'TASKEXECUTIVE', 'internal', 1);
			// }


			// // $data['id'] = 'task'.$objtaskid;
			// $data['projectid'] = $projectid;
			// // $data['start_date'] = dol_print_date($date_start, "%Y-%m-%d");
			// // $data['end_date'] = dol_print_date($date_end, "%Y-%m-%d");
			// $data['$target'] = 'task'.$objtaskid;
			// // d($data);

			// $result['task'] = $data;

			$result['msg'] = $langs->trans('Notify_TASK_CREATE').' : '.$objtask->ref.($objtask->label ? ' - '.$objtask->label : '');
			$result['taskid'] = 'task'.$objtaskid;
			$result['projectid'] = $projectid;
			$result['typemsg'] = 'warning';
			
		}
	}
}

elseif($ajxaction == 'clonetask' && $user->rights->projet->creer){

	$task	= GETPOST('task');
	$newtask	= GETPOST('newtask');
	$tid = str_replace("task","",$task['id']);
	$tnewid = str_replace("task","",$newtask['id']);

	$taskstatic = new Task($db);
	$taskstatic->fetch($tid);

	$oldcopy = new Task($db);
	$objtaskid = $oldcopy->fetch($tnewid);
	
	if ($objtaskid > 0) {
		
		$sql = ' SELECT * FROM '.MAIN_DB_PREFIX.'kanban_commnts';
		$sql .= ' WHERE fk_task='.$tid;
		$sql .= ' ORDER BY rowid ASC';
		// d($sql, false);
		$resql = $db->query($sql);
		if($resql){
			$num = $db->num_rows($resql);
			if($num>0){
				while ($obj = $db->fetch_object($resql)) {
					$lincomments .=  '("'.$db->escape($obj->comment).'", '.$tnewid.', '.$obj->fk_user.', "'.$obj->date.'"),';
				}

				$sql2 = 'INSERT INTO `'.MAIN_DB_PREFIX.'kanban_commnts` (`comment`, `fk_task`, `fk_user`, `date`) VALUES';
				$lincomments = substr($lincomments, 0, -1);
				$sql2 .= $lincomments;
				// d($sql2);
				$resql2 = $db->query($sql2);
			}
		}

		$sql = ' SELECT * FROM '.MAIN_DB_PREFIX.'task_tagskanban';
		$sql .= ' WHERE fk_task='.$tid;
		$sql .= ' ORDER BY rowid ASC';

		$resql = $db->query($sql);
		if($resql){
			$num = $db->num_rows($resql);
			if($num>0){
				while ($obj = $db->fetch_object($resql)) {
					$linetiquettes .=  '('.$obj->fk_tag.', '.$tnewid.'),';
				}

				$sql2 = 'INSERT INTO `'.MAIN_DB_PREFIX.'task_tagskanban` (`fk_tag`, `fk_task`) VALUES';
				$linetiquettes = substr($linetiquettes, 0, -1);
				$sql2 .= $linetiquettes;
				$resql2 = $db->query($sql2);
			}
		}

		$sql = ' SELECT * FROM '.MAIN_DB_PREFIX.'checklistkanban';
		$sql .= ' WHERE fk_task='.$tid;
		$sql .= ' ORDER BY rowid ASC';

		$resql = $db->query($sql);
		if($resql){
			$num = $db->num_rows($resql);
			if($num>0){
				while ($obj = $db->fetch_object($resql)) {
					$linecheckedlist .=  '("'.$db->escape($obj->label).'", '.$tnewid.', '.$obj->checked.'),';
				}

				$sql2 = 'INSERT INTO `'.MAIN_DB_PREFIX.'checklistkanban` (`label`, `fk_task`, `checked`) VALUES';
				$linecheckedlist = substr($linecheckedlist, 0, -1);
				$sql2 .= $linecheckedlist;
				$resql2 = $db->query($sql2);
			}
		}		
	}
}

elseif($ajxaction == 'updatetask' && $user->rights->projet->creer){


	$task	= GETPOST('task');
	$objtask = new Task($db);

	$error = 0;

	$tid = str_replace("task","",$task['id']);

	$objtask->fetch($tid);
    $objtask->oldcopy = clone $objtask;

	$start_date = $task['start_date'];
	$end_date 	= $task['end_date'];

	$date_arr_start = date_parse($start_date);
	$date_start = dol_mktime($date_arr_start['hour'], $date_arr_start['minute'], 0, $date_arr_start['month'], $date_arr_start['day'], $date_arr_start['year']);

	$date_arr_end = date_parse($end_date);
	// $date_end = dol_mktime($date_arr_end['hour'], $date_arr_end['minute'], 0, $date_arr_end['month'], $date_arr_end['day'], $date_arr_end['year']);
	
	$endhour = 23;
	$endmin = 59;

	if($ganttproadvanced->showhoursingantt){
		$endhour = $date_arr_end['hour'];
		$endmin = 0;
	}
	$date_end = dol_mktime($endhour, $endmin, 0, $date_arr_end['month'], $date_arr_end['day'], $date_arr_end['year']);

	$_start 	= $date_start;
	$_end 		= $date_end;

	$duration = 0;
	$duration_hour = 0;
	if($_end && $_start) {
		// $datediff 	= $_end-$_start;
		// $duration 	= round($datediff / (60 * 60 * 24));

		// d('_start: '.$db->idate($_start));
		// d('_end: '.$db->idate($_end));

		$duration = $ganttproadvanced->calculateWeekdaysWithOrWithoutWeekEnd($_start, $_end);
		$duration_hour = $ganttproadvanced->calculateWeekHoursWithOrWithoutWeekEnd($_start, $_end);

		$result['duration_day'] = $duration;
		$result['duration_hour'] = $duration_hour;
	}


	if($ganttproadvanced->max_period_task_month && !isset($ganttproadvanced->projects_excluded[$objtask->fk_project])) {
		if($date_arr_start['month'] != $date_arr_end['month'] || $date_arr_start['year'] != $date_arr_end['year']) {
			$result['msg'] = '<b>'.$objtask->ref.($objtask->label ? ' - '.$objtask->label : ''). '</b> : '.$langs->trans('minperiodtask').' <b>'.$langs->trans('Activated').'</b>';
			$result['typemsg'] = 'error';
			echo json_encode($result);
			die;
		}
	}

	$res = 0;

	$contacts = $task['contacts'];
	// if (!empty($date_start) && !empty($date_end) && $date_start > $date_end) {
	// 	$date_end = date("Y-m-d", ($date_end ."+1 days"));
	// }
	$plannedworkload=0;
	$planned_workload = $task['planned_workload'];
	if($planned_workload){
		$planned_workload = explode(':', $task['planned_workload']);
		$planned_workload_h = (int) $planned_workload[0]*3600;
		$planned_workload_m = (int) $planned_workload[1]*60;

		$plannedworkload = $planned_workload_h+$planned_workload_m;
	}

	$progress = number_format($task['progress'],2)*100;

	$objtask->label = $task['text'];
	$objtask->description = $task['description'];
	$objtask->date_start = $date_start;
	$objtask->date_end 	= $date_end;
	$objtask->progress 	= $progress;
	$objtask->planned_workload = $plannedworkload;
	$objtask->progress = $progress;
	$objtask->budget_amount = $task['budget'];
	$contacts=$task['contacts'];
	$note_private = $task['note_private'];
	$oldcolor = $objtask->array_options["options_ganttproadvancedcolor"];

	$objtask->array_options["options_ganttproadvancedcolor"] = $task['color'];

	if($objtask){
		$contacts_task = $objtask->liste_contact(-1, 'internal', 1, 'TASKCONTRIBUTOR');
		$contacts = explode(',', $contacts);
		$projects = new Project($db);
		$projects->fetch($objtask->fk_project);

		if($ganttproadvanced->use_task_dependencies) {
			$projectprogress = (float) GETPOST("projectprogress");
			$newprogress = number_format($projectprogress,2)*100;

			$oldprogress = isset($projects->array_options["options_ganttproadvancedprojectprogress"]) ? $projects->array_options["options_ganttproadvancedprojectprogress"] : 0;
			if($oldprogress != $newprogress) {
				$projects->array_options["options_ganttproadvancedprojectprogress"] = $newprogress;
				$projects->update($user);
			}
		}

		if($contacts && count($contacts)>0){
			foreach ($contacts as $key => $value) {
				// if($contacts_task && !in_array($value, $contacts_task)){
				// }
				$d = $projects->add_contact($value, 'PROJECTCONTRIBUTOR', 'internal', 1);
				$res = $objtask->add_contact($value, 'TASKCONTRIBUTOR', 'internal', 1);
			}

			$contact_supp = array_diff($contacts_task, $contacts);
			if($contact_supp){
				$listId = implode(',', $contact_supp);
				if(!empty($listId)){
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_contact";
					$sql .= " WHERE element_id = ".((int) $objtask->id);
					$sql .= " AND fk_socpeople IN (".$db->sanitize($listId).")";
					$sql .= " AND fk_c_type_contact IN (SELECT rowid FROM ".MAIN_DB_PREFIX."c_type_contact WHERE code='TASKCONTRIBUTOR')";
					$resql = $db->query($sql);
				}
			}

			
		}
	}


	if (isset($extrafields->attributes[$objtask->table_element]['label']) && is_array($extrafields->attributes[$objtask->table_element]['label'])) {
		$extralabels = $extrafields->attributes[$objtask->table_element]['label'];
	}


	if (is_array($extralabels)) {
		// Get extra fields
		foreach ($extralabels as $key => $value) {
			// if($extrafields->attributes[$objtask->table_element]['enabled'][$key] && ($extrafields->attributes[$objtask->table_element]['list'][$key] == 1 || $extrafields->attributes[$objtask->table_element]['list'][$key] == 3)){
				if($key != 'ganttproadvancedcolor'){
					$key_type = $extrafields->attributes[$objtask->table_element]['type'][$key];
					if ($key_type == 'separate') {
						continue;
					}

					// $enabled = 1;
					// if (isset($extrafields->attributes[$objtask->table_element]['enabled'][$key])) {	// 'enabled' is often a condition on module enabled or not
					// 	$enabled = dol_eval($extrafields->attributes[$objtask->table_element]['enabled'][$key], 1, 1, '1');
					// }

					// $visibility = 1;
					// if (isset($extrafields->attributes[$objtask->table_element]['list'][$key])) {		// 'list' is option for visibility
					// 	$visibility = dol_eval($extrafields->attributes[$objtask->table_element]['list'][$key], 1, 1, '1');
					// }

					// $perms = 1;
					// if (isset($extrafields->attributes[$objtask->table_element]['perms'][$key])) {
					// 	$perms = dol_eval($extrafields->attributes[$objtask->table_element]['perms'][$key], 1, 1, '1');
					// }
					// if (empty($enabled)) {
					// 	continue;
					// }
					// if (empty($visibility)) {
					// 	continue;
					// }
					// if (empty($perms)) {
					// 	continue;
					// }

					if (in_array($key_type, array('date'))) {
						// Clean parameters

						$date_array = date_parse($task[$key]);
						$value_key = dol_mktime($date_array['hour'], $date_array['minute'], 0, $date_array['month'], $date_array['day'], $date_array['year']);
		
					} elseif (in_array($key_type, array('datetime'))) {

						$date_array = date_parse($task[$key]);
						$value_key = dol_mktime($date_array['hour'], $date_array['minute'],  $date_array['sec'], $date_array['month'], $date_array['day'], $date_array['year']);
			
					} elseif (in_array($key_type, array('checkbox', 'chkbxlst'))) {
						$value_arr = (isset($task[$key]) && $task[$key]) ? $task[$key] : ''; // check if an array
						if (!empty($value_arr)) {
							$value_key = implode(',', $value_arr);
						} else {
							$value_key = '';
						}
					} elseif (in_array($key_type, array('price', 'double'))) {
						$value_arr = (isset($task[$key]) && $task[$key]) ? $task[$key] : '';
						$value_key = price2num($value_arr);
					} else {
						$value_key = (isset($task[$key]) && $task[$key]) ? $task[$key] : '';
					}
					$objtask->array_options["options_".$key] = $value_key;

				}
			// }
		}
	}
	$res = $objtask->update($user);

	$result['ganttproadvancedjstoapply'] = 0;

	if($res > 0) {

		$res = $objtask->update_note(dol_html_entity_decode($note_private, ENT_QUOTES | ENT_HTML5, 'UTF-8', 1), '_private');

		$res = $objtask->insertExtraFields();


		$result['task_progress'] = (intval($objtask->progress)/100);

		if($ganttproadvanced->use_task_dependencies) {

			$tmpobjtasktocheck = $objtask;

			if(isset($objtask->array_options['options_ganttproadvancedrelatedtask']) && $objtask->array_options['options_ganttproadvancedrelatedtask'] > 0) {
				$tmpobjtask = new Task($db);
				$tmpreqtask = $tmpobjtask->fetch($objtask->array_options['options_ganttproadvancedrelatedtask']);

				if($tmpreqtask > 0) {
					$tmpobjtasktocheck = $tmpobjtask;
				}
			}
			$ganttproadvancedutils->checkRecursivlyRelationsAndProjectDates($tmpobjtasktocheck);

			$result['ganttproadvancedjstoapply'] = $ganttproadvancedjstoapply;
		}

		$result['msg'] = '';
		$result['typemsg'] = 'warning';
	} else {
		// d($objtask);
		$errormsg = $langs->trans('Error');
		if($objtask->error)
			$errormsg = $langs->trans($objtask->error);
		else {
			if(isset($objtask->errors[0]))
				$errormsg = $langs->trans($objtask->errors[0]);
		}
		$result['msg'] = $errormsg;
		// $result['msg'] = $objtask->error;
	}
}

elseif($ajxaction == 'updateproject' && $user->rights->projet->creer){

	$task = GETPOST('task');
	$result = [];
	$object = new Project($db);
	$objid = str_replace("project", "", $task['projectid']);

	$object->fetch($objid);
    $object->oldcopy = clone $object;
	
	$progress 		= number_format($task['progress'],2)*100;
	$oldprogress 	= $object->array_options["options_ganttproadvancedprojectprogress"];

	$needupdate = 0; 

	if($object->title != $task['text'])
		$needupdate = 1;
	if($object->description != $task['description'] || $object->note_private != $task['note_private'])
		$needupdate = 2;
	if($oldprogress != $progress) {
		$needupdate = 3;
	}
	
	$object->title 			= $task['text'];
	$object->description 	= $task['description'];
	$object->note_private 	= $task['note_private'];
	// $object->opp_percent 	= $progress;
	$object->array_options["options_ganttproadvancedprojectprogress"] = $progress;

	// if($ganttproadvanced->minmaxtasksdatetoproject) {
	// 	$start_date = $task['start_date'];
	// 	$end_date 	= $task['end_date'];

	// 	$date_array = date_parse($start_date);
	// 	$date_start = dol_mktime($date_array['hour'], $date_array['minute'], 0, $date_array['month'], $date_array['day'], $date_array['year']);

	// 	$date_array = date_parse($end_date);
	// 	// $date_end = dol_mktime($date_array['hour'], $date_array['minute'], 0, $date_array['month'], $date_array['day'], $date_array['year']);
	// 	$date_end = dol_mktime(23, 59, 0, $date_array['month'], $date_array['day'], $date_array['year']);

	// 	$object->date_start 	= $date_start;
	// 	$object->date_end 		= $date_end;
	// }
	
	if($needupdate) {
		$res = $object->update($user);
		if($res > 0) {
			$result['msg'] = '';
			$result['typemsg'] = 'warning';
		} else {
			$result['msg'] = $object->error;
			// $result['msg'] = $langs->trans($object->error);
		}
	}
}

elseif($ajxaction == 'deleteobject' && $user->rights->projet->supprimer){

	$task = GETPOST('task');

	if(strpos($task['id'], 'task') !== false) {
		// ---------------------------------  is Task
		$tmpobj = new Task($db);
		$objid = str_replace("task", "", $task['id']);
		$type = 'task';
	} else {
		// ---------------------------------  is Project
		$tmpobj = new Project($db);
		$objid = str_replace("project", "", $task['id']);
		$type = 'project';
	}

	if($tmpobj->fetch($objid)) {

		if ($tmpobj->delete($user) > 0) {
			$result['typemsg'] = 'warning';
			
			if($type == 'project') {
				$result['msg'] = $langs->trans('DELETEInDolibarr', 1).' : '.$tmpobj->ref.($tmpobj->label ? '-'.$tmpobj->label : '');
			} else {
				$result['msg'] = $langs->trans('Notify_TASK_DELETE').' : '.$tmpobj->ref.($tmpobj->label ? '-'.$tmpobj->label : '');
			}

		} else {
			$result['error'] = 1;
			$result['typemsg'] = 'error';
			// $result['msg'] = $langs->trans('TaskHasChild');
			// $result['msg'] = $langs->trans($tmpobj->error);
			if($type == 'project') {
				$result['msg'] = $langs->trans($tmpobj->error);
			} else {
				$result['msg'] = $langs->trans($tmpobj->error);
				// $result['msg'] = $langs->trans('TaskHasChild');
			}
		}
	} 

	// else {
	// 	setEventMessages($objtask->error, $objtask->errors, 'errors');
	// 	$action = '';
	// }
}

elseif($ajxaction == "getOrSetRelatedTasks"){

	$currentmodiftask = (int) GETPOST('currentmodiftask');
	$relatedtask = (int) GETPOST('relatedtask');

	global $currentmodiftask;

	// ------------------------------------------------------------------------------------------------------
	$returned = array();
	$returned['tobedisabled'] = array();

	// ------------------------------------------------------------------------------------------------------
	$returned['html'] = $ganttproadvancedutils->ganttproadvancedselectProjectTasks($relatedtask);


	// ------------------------------------------------------------------------------------------------------
	if($currentmodiftask > 0) {
    	$returned['tobedisabled'] = $ganttproadvancedutils->checkIfTheSelectedTaskAlreadyExistInTheTree($currentmodiftask, $relatedtask);
	}

	// d($arrayfullhierars,0);

	echo json_encode($returned);
	die;
}

elseif($ajxaction == "changeStartEndDate"){

	$id = (int) GETPOST('id');
	$typerelation = GETPOST('typerelation');
	$id_relatedtask = (int) GETPOST('relatedtask');
	$numberdayslate = GETPOST('numberdayslate');


	$task = new Task($db);
    $relatedtask = new Task($db);

    if($id > 0) {
    	$res = $task->fetch($id);
    }

    $res = $relatedtask->fetch($id_relatedtask);

    $returned = array('date_start' => '', 'date_end' => '');

    if(!$id) {
		$start_date = GETPOST('start_date');
		$end_date = GETPOST('end_date');

		if($start_date) {
			$tmpdate = explode('/', $start_date);
			$start_date = $tmpdate[2].'-'.$tmpdate[1].'-'.$tmpdate[0];
		}
		if($end_date) {
			$tmpdate = explode('/', $end_date);
			$end_date = $tmpdate[2].'-'.$tmpdate[1].'-'.$tmpdate[0];
		}

    	// $date_array = date_parse($start_date);
		// $date_start = dol_mktime($date_array['hour'], $date_array['minute'], 0, $date_array['month'], $date_array['day'], $date_array['year']);

		// $date_array = date_parse($end_date);
		// $date_end 	= dol_mktime(23, 59, 0, $date_array['month'], $date_array['day'], $date_array['year']);

		$task->date_start = strtotime($start_date);
		$task->date_end = strtotime($end_date);
    }


    if($task->date_start && $task->date_end && $relatedtask->date_start && $relatedtask->date_end) {
		$returned = $ganttproadvancedutils->clschangeStartEndDate($task, $relatedtask, $typerelation, $numberdayslate, 'js');
    }

    // $arrayfullhierars = $ganttproadvancedutils->checkIfTheSelectedTaskAlreadyExistInTheTree($id);

	// // d($fulltree);
    // if($arrayfullhierars && isset($arrayfullhierars[$id_relatedtask])) {
	// 	$returned['msgerror'] = $langs->trans('YouCantLinkToThisTask');
	// 	echo json_encode($returned);
	// 	die;
    // }

	echo json_encode($returned);
	die;
}

// close project
elseif($ajxaction == 'closeobject' && $user->rights->ganttproadvanced->ferme){

	$task		= GETPOST('task');
	// $id = GETPOST('id', 'int');

	if(strpos($task['id'], 'project') !== false) {
		
		// ---------------------------------  is Project
		$tmpobj = new Project($db);
		$objid = str_replace("project", "", $task['id']);
		$type = 'project';

	}

	if($tmpobj->fetch($objid)) {

		$tache = new Task($db);
		$tache->fetch($id);

		$tache->progress = price2num(GETPOST('progress', 'alphanohtml'));
		// d('prog'.$tache->progress);
		$tache->update($user);
		
	} 

	// else {
	// 	setEventMessages($objtask->error, $objtask->errors, 'errors');
	// 	$action = '';
	// }
}

elseif($ajxaction == 'hideshowcolumn'){
	$columns = GETPOST('columns') ? GETPOST('columns') : [];

	$userparametres = array();
	$userparametres['GANTTPROADVANCED_GANTT_COLUMS_TO_SHOW'] = json_encode($columns);

	$res = dol_set_user_param($db, $conf, $user, $userparametres);

	// $res = dolibarr_set_const($db, 'GANTTPROADVANCED_COLUMS_TO_SHOW', $columns, 'chaine', 0, '', $conf->entity);
}

elseif($ajxaction == 'get_user_field_color'){
	$user_id = GETPOST('user_id', 'int');

	$resuser = $tmpuser->fetch($user_id);

	$colortask = ($resuser && $tmpuser->color) ? '#'.$tmpuser->color : $colortask;

	$result['colortask'] = $colortask;
}

elseif($ajxaction == 'changeordertasks'){
	$projectid		= GETPOST('projectid');
	$arrsubtasks	= GETPOST('arrsubtasks');

	$error = 0;

	if(!is_array($arrsubtasks)) $error++;

	if(!$error) {

		$tmpstringids = implode(',', $arrsubtasks);
		$tmpstringids = str_replace('task', '', $tmpstringids);

		if($tmpstringids) {

			$arrtasksids = explode(',', $tmpstringids);

			require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
			
			$row = new GenericObject($db);
			$row->table_element_line = 'projet_task';
			$row->fk_element = 'fk_projet';
			$row->id = $projectid;

			$row->line_ajaxorder($arrtasksids); // This update field rank or position in table row->table_element_line
		}
	}
}

elseif($ajxaction == 'changewidthgrid'){
	$new_width = GETPOST('new_width');

	$resusrs = dol_set_user_param($db, $conf, $user, ['GANTTPROADVANCED_GANTT_GRID_WIDTH' => $new_width]);
	// d('resusrs :'. $resusrs);
}

elseif($ajxaction == 'getnbtasks'){
	
	$nbtasks = 0;
	$userid = GETPOST('userid', 'int');
	$projectid = GETPOST('projectid', 'int');
	$start_date = GETPOST('start_date', 'alpha');
	$end_date = GETPOST('end_date', 'alpha');

	$date_array = date_parse($start_date);
	$date_start = dol_mktime($date_array['hour'], $date_array['minute'], 0, $date_array['month'], $date_array['day'], $date_array['year']);
	
	$date_array = date_parse($end_date);
	$date_end = dol_mktime($date_array['hour'], $date_array['minute'], 0, $date_array['month'], $date_array['day'], $date_array['year']);

	$sql = 'SELECT COUNT(t.rowid) as nbtasks FROM '.MAIN_DB_PREFIX.'projet_task as t';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'element_contact ON element_id = t.rowid';
	$sql .= ' WHERE 1>0';
	// $sql .= ' WHERE t.fk_projet='.((int) $projectid);
	$sql .= ' AND fk_c_type_contact = '.$ganttproadvanced->t_typecontact.' AND fk_socpeople='.$userid;
	$sql .= ' AND ( CAST(t.dateo as date) BETWEEN "'.$db->idate($date_start).'" AND "'.$db->idate($date_end).'" ';
	$sql .= ' OR CAST(t.datee as date) BETWEEN "'.$db->idate($date_start).'" AND "'.$db->idate($date_end).'" )';
	// $sql .= ' GROUP BY t.rowid';
	$resql = $db->query($sql);
	if($resql){
		while ($obj = $db->fetch_object($resql)) {
			$nbtasks = $obj->nbtasks;
		}
	}
	echo $nbtasks;
}

// ------------------------------------------------------------------------------------------------------------------------------ Create Or Edit task
if(in_array($ajxaction, ['createtask', 'updatetask'])) {

	$nameuseraffected = '';

	$result['csstoadd'] = '';
	
	if($ganttproadvanced->coloredbyuser) {

		$namefield = 'affected_user';

		// d($objtask);

		$s = "DELETE FROM  ".MAIN_DB_PREFIX."element_contact WHERE element_id = ".((int) $objtask->id)." AND fk_c_type_contact = ".$ganttproadvanced->t_typecontact.";";
        $resql = $db->query($s);
		// $s = "DELETE FROM  ".MAIN_DB_PREFIX."element_contact WHERE element_id = ".((int) $objtask->fk_project)." AND fk_c_type_contact IN (SELECT rowid FROM llx_c_type_contact WHERE `code` = 'PROJECTCONTRIBUTOR' AND `element` = 'project' AND `source` = 'internal');";
		// $resql = $db->query($s);

		if(isset($task[$namefield]) && $task[$namefield] > 0) {

			$project->id = $objtask->fk_project;
			$tmp_res = $project->add_contact($task[$namefield], 'PROJECTCONTRIBUTOR', 'internal', 1);

			if($objtask->fk_task_parent){
				$taskparent = new Task($db);
				$taskparent->fetch($objtask->fk_task_parent);
				// $s = "DELETE FROM  ".MAIN_DB_PREFIX."element_contact WHERE element_id = ".((int) $objtask->fk_task_parent)." AND fk_c_type_contact = ".$ganttproadvanced->t_typecontact.";";
        		// $resql = $db->query($s);
				$tmp_res = $taskparent->add_contact($task[$namefield], 'TASKCONTRIBUTOR', 'internal', 1);
			}

			$tmp_res = $objtask->add_contact($task[$namefield], $ganttproadvanced->t_typecontact, 'internal', 1);

			$usertogetcolor = $task[$namefield];

			$resuser = (isset($task[$namefield]) && $task[$namefield] > 0) ? $tmpuser->fetch($task[$namefield]) : 0;

	    	$iduser = 0;
	    	if($resuser) {
	    		$iduser = $tmpuser->id;
	    		// d('tmpuser->color : '.$tmpuser->color,0);
	    		// d('task["color"] : '.$task["color"],0);
	    		// d('fk_user : '.$task[$namefield],0);
		    	if($tmpuser->color != str_replace('#', '', $task['color']) && (($ganttproadvanced->changecolortaskbyadmin && $user->admin) || !$ganttproadvanced->changecolortaskbyadmin)) {
	        		$color_changed = true;
			    	$tmpuser->color = str_replace('#', '', $task['color']);
			    	$resuser = $tmpuser->update($user, 1);
	        	}

	        	$nameorder = (!empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION) ? 1 : 0);

		    	$nameuseraffected = '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$tmpuser->id.'" target="_blank">';
	        	$nameuseraffected .= img_picto($langs->trans('User'), 'user', '').' ';
		    	$nameuseraffected .= '<span class="lastname">';
		    	$nameuseraffected .= (($nameorder == 1) ? $tmpuser->firstname : $tmpuser->lastname);
		    	$nameuseraffected .= '</span>';
		    	$nameuseraffected .= '<span class="firstname">';
		    	$nameuseraffected .= ' '.(($nameorder == 1) ? $tmpuser->lastname : $tmpuser->firstname);
		    	$nameuseraffected .= '</span>';
	        	$nameuseraffected .= '</a>';

	        	if($color_changed) {
					$users_tasks = json_decode(base64_decode($users_tasks));
					$iduser = $tmpuser->id;
					$tmptasks = (array) $users_tasks;
					if(isset($tmptasks[$tmpuser->id])) {
						$tmparray = (array) $tmptasks[$tmpuser->id];
						unset($tmparray[$objtask->id]);

						if(count($tmparray) > 0) {
							$result['changed_colors'] = $tmparray;
						}
					}
				}
	    	}

	    	if($iduser > 0) {
				$result['csstoadd'] .= 'body .colormarker_user'.$iduser.'{background-color: '.($task['color']  ? $task['color'] : $colordefaulttask).' !important} ';
			}
	    	// $colortask = ($resuser && $tmpuser->color) ? '#'.$tmpuser->color : $colortask;
		}


		//// ----------------------------------------------------------------------------- FOR ALL CONTACT TYPE
		// foreach($ganttproadvanced->_data_typecontact as $id_type => $name_type) { 

		// 	$namefield = 'affected_user_'.$id_type;

		// 	if(isset($task[$namefield]) && $task[$namefield] > 0) {
		// 		$tmp_res = $objtask->add_contact($task[$namefield], $id_type, 'internal', 1);
		// 		// d($objtask->error);
		// 	}

		// 	if($ganttproadvanced->t_typecontact == $id_type) {
		// 		$usertogetcolor = $task[$namefield];
		// 	}

		// }

		// $search_tasktype = GETPOST("search_tasktype", 'alpha') ? GETPOST("search_tasktype", 'alpha') : $ganttproadvanced->t_typecontact;


		$search_affecteduser 	= GETPOST("search_affecteduser", 'array');
		$search_projects 		= GETPOST("search_projects", 'array');
		$search_tasktype 		= GETPOST("search_tasktype", 'array');

		// d($search_tasktype);

		// -----------------------------------------------------------------------
		$sql_proj = implode(",", $search_projects);

		// -----------------------------------------------------------------------
		$tmptasktypes = implode('","', $search_tasktype);
		$sql_tasktypes = '"'.$tmptasktypes.'"';
		// -----------------------------------------------------------------------

		$returned = $ganttproadvanced->ganttproadvancedselectUsersThatSignedAsTasksContacts($sql_proj, $sql_tasktypes, $search_affecteduser);
		
		$result['selectusers'] = $returned['html'];

    } else {
    	if($oldcolor != $task['color']) {
			$result['csstoadd'] .= 'body .colormarker_task'.$objtask->id.'{background-color: '.($task['color']  ? $task['color'] : $colordefaulttask).' !important} ';
		}
    }

	$colortask = (($ganttproadvanced->changecolortaskbyadmin && $user->admin) || !$ganttproadvanced->changecolortaskbyadmin) ? $task['color'] : $colordefaulttask;

	$result['affected_nameuser'] = $nameuseraffected;
	$result['colortask'] = $colortask;
}

elseif($ajxaction == "refreshfilter") {

	$search_customer		= GETPOST("search_customer", 'int');
	$search_userid			= GETPOST("search_userid", 'int');
	$search_category		= GETPOST("search_category", 'int');
	$search_status			= GETPOST("search_status", 'int');
	$search_projects		= GETPOST("search_projects", 'array');
	$search_tasktype		= GETPOST("search_tasktype", 'array');
	$search_affecteduser 	= GETPOST("search_affecteduser", 'array');
	$debutyear				= GETPOST("debutyear", 'int');
	$debutmonth				= GETPOST("debutmonth", 'int');
	$finyear				= GETPOST("finyear", 'int');
	$finmonth				= GETPOST("finmonth", 'int');

	global $selectallornone, $projectstoselectafterrefresh;

	$selectallornone = GETPOST("selectallornone", 'int');
	$projectstoselectafterrefresh = array();
	// ------------------------------------------------------------------ Project Select

	$search_debut = dol_mktime(0, 0, 0, $debutmonth, 1, $debutyear);
	$search_fin = dol_get_last_day($finyear, $finmonth);
	$returned = $ganttproadvanced->ganttproadvancedSelectProjectsAuthorized($search_projects, $search_category, $search_status, $search_userid, $search_customer, false, 0, $search_debut, $search_fin);
	$result['selectprojects'] = $returned;
	// d($result);

	// ------------------------------------------------------------------ Affect User Select
	$tmpprojects = ($projectstoselectafterrefresh) ? $projectstoselectafterrefresh : ([-1]);
	$sql_proj = implode(",", $tmpprojects);
	$tmptasktypes = implode('","', $search_tasktype);
	$sql_tasktypes = '"'.$tmptasktypes.'"';
	$returned = $ganttproadvanced->ganttproadvancedselectUsersThatSignedAsTasksContacts($sql_proj, $sql_tasktypes, $search_affecteduser);
	
	$result['selectusers'] = $returned['html'];
}



if($result)
echo json_encode($result);


// ------------------------------------------------------------------------------------------------------------------------------ Mise a jour commentaires
if($ajxaction == "loadcomment"){

	$fk_task = GETPOST('id_task');
	$comments = array();
	$html = '';
	$sql = ' SELECT * FROM '.MAIN_DB_PREFIX.'kanban_commnts';
	$sql .= ' WHERE fk_task='.$fk_task;
	$sql .= ' ORDER BY rowid DESC';
	$resql = $db->query($sql);
	if($resql){
		while ($obj = $db->fetch_object($resql)) {
			$us = new User($db);
			$us->fetch($obj->fk_user);
			$date = $db->jdate($obj->date);
			$comments[]['user'] = $us->getNomUrl(-2);
			$comments[]['comment'] = nl2br($obj->comment);
			$comments[]['date'] = dol_print_date($date, 'dayhour');

			$html .= '<div id="kanban_comment_'.$obj->rowid.'">';
				$html .= '<div class="kanban_user_comment">';
					$html .= $us->getNomUrl(-2);
				$html .= '</div>';
				$html .= '<div class="kanban_show_comment" id="kanban_comment_'.$obj->rowid.'">';
					$html .= '<div class="kanban_comment_infouser">';
						$html .= '<strong>'.$us->getFullname($langs).'</strong> ';
						$html .= $langs->trans('at').' '.dol_print_date($date, 'dayhour');
					$html .= '</div>';
					$html .= '<div class="kanban_comment_value">';
						$html .= '<span class="show_comment">'.nl2br($obj->comment).'</span>';
						$html .= '<textarea placeholder="'.$langs->trans('Comment').'..." onKeyUp="ganttproadvanced_textarea_autosize(this)" class="update_comment comment_'.$obj->rowid.'" id="txt_comment">'.$obj->comment.'</textarea>';
					$html .= '</div>';
					$html .= '<div class="btn_comment">';

						if($user->id == $obj->fk_user){

							$html .= '<div class="update_comment">';
								$html .= '<a class="butAction savecomment" data-id="'.$obj->rowid.'" data-task="'.$fk_task.'" onclick="updatecomment(this)">'.$langs->trans('Save').'</a>';
								$html .= '<a class="butAction cancelupdatecomment" onclick="cancelupdatecomment(this)">'.$langs->trans('Cancel').'</a>';
							$html .= '</div>';
							$html .= '<div class="show_comment">';
								$html .= '<a class="edit_comment cursorpointer_task" data-id="'.$obj->rowid.'" data-task="'.$fk_task.'" onclick="editcomment(this)">'.img_edit().' '.$langs->trans('Modify').'</a>';
								$html .= '<a class="delete_comment cursorpointer_task" data-id="'.$obj->rowid.'" data-task="'.$fk_task.'" onclick="deletecomment(this)">'.img_delete().' '.$langs->trans('Delete').'</a>';
							$html .= '</div>';
						}

					$html .= '</div>';
				$html .= '</div>';
			$html .= '</div>';
		}
	}

	echo $html;
}

elseif($ajxaction == 'savecomment'){
	$fk_task = GETPOST('id_task');
	$comment = GETPOST('comment');
	$fk_user = $user->id;
	$now = dol_now();
	$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'kanban_commnts (fk_task, fk_user, comment, date) VALUES (';
		$sql .= $fk_task>0 ? $db->escape($fk_task) : 'NULL';
		$sql .= ','. ($fk_user>0 ? $db->escape($fk_user) : 'NULL');
		$sql .= ','. ($comment ? '"'.$db->escape($comment).'"' : 'NULL');
		$sql .= ', "'.$db->idate($now).'"';
	$sql .= ')';
	$resql = $db->query($sql);
	$result=[];
	if($resql){
		$result['msg'] = $langs->trans('COMMENT_TASK_CREATE');
		$result['typemsg'] = 'warning';
		echo json_encode($result);
	}else{
		$result['msg'] = $langs->trans('COMMENT_TASK_ECHECK_CREATE');
		$result['typemsg'] = 'error';
		echo json_encode($result);
	}
}

elseif($ajxaction == 'addcomment'){

	$fk_task = GETPOST('id_task');
	$fk_user = $user->id;
	$now = dol_now();

	$taskobj = new Task($db);
	$taskobj->fetch($fk_task);

	$html ='';
	$html.= '<div class="window-overlay" id="popcomments">';
		$html.= '<div id="kanban_comments">';
			$html.= '<a class="kanban_close_comments cursorpointer" onclick="closecomments(this)"><span class="fas fa-times"></span></a>';
			$html.= '<div class="title_commnts">';
				$html.= '<span>'.$langs->trans('Comments').'</span>';
				$html.= '<span class="marginleftonly">';
				$html.= $taskobj->getNomUrl(1).' '.($taskobj->label ? ' - '.$taskobj->label : '');
				$html.= '</span>';
			$html.= '</div>';
			$html.= '<div class="kanban_body_comments">';
				$html.= '<div class="kanban_new_comment">';
					$html.= '<input type="hidden" value="'.$fk_task.'" id="id_task">';
					$html.= '<div class="kanban_user_comment">';
						$html.= $user->getNomUrl(-2);
					$html.= ' </div>';
					$html.= '<form>';
					$html.= '<div class="kanban_txt_comment">';
						$html.= '<textarea placeholder="'.$langs->trans('Comment').'..." onkeypress="keypressComment(this)" id="txt_comment"></textarea>';
						$html.= '<a class="butAction savecomment" onclick="savecomment(this)">'.$langs->trans('Save').'</a>';
						$html.= '<a class="butAction cancelcomment" onclick="cancelcomment(this)">'.$langs->trans('Cancel').'</a>';
					$html.= '</div>';
					$html.= '</form>';
				$html.= '</div>';
				$html.= '<div class="kanban_list_comments">';
				$html.= '</div>';
			$html.= '</div>';
		$html.= '</div>';
	$html.= '</div>';
	echo $html;
}

elseif($ajxaction == 'updatecomment'){
	$comment = GETPOST('comment');
	$id_comment = GETPOST('id_comment');

	$now = dol_now();
	$sql = 'UPDATE '.MAIN_DB_PREFIX.'kanban_commnts set comment="'.$db->escape($comment).'"';
	$sql .= ' WHERE rowid ='.$id_comment;
	$resql = $db->query($sql);
	$result=[];
	if($resql){
		$result['msg'] = $langs->trans('COMMENT_TASK_MODIFY');
		$result['typemsg'] = 'warning';
		echo json_encode($result);
	}else{
		$result['msg'] = $langs->trans('COMMENT_TASK_ECHECK_UPDATE');
		$result['typemsg'] = 'error';
		echo json_encode($result);
	}
}

elseif($ajxaction == 'deletecomment'){
	$id_comment = GETPOST('id_comment');
	$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'kanban_commnts WHERE rowid='.$id_comment;
	$resql = $db->query($sql);
	$result=[];
	if($resql){
		$result['msg'] = $langs->trans('COMMENT_TASK_DELETE');
		$result['typemsg'] = 'warning';
		echo json_encode($result);
	}else{
		$result['msg'] = $langs->trans('COMMENT_TASK_ECHECK_DELETE');
		$result['typemsg'] = 'error';
	}
	echo json_encode($result);
}

elseif($ajxaction == 'getabnovomoduleetapedate'){
	$tasktxtwithid = GETPOST('tasktxtwithid', 'alpha');
	$abnovomoduleetape = (int) GETPOST('abnovomoduleetape', 'int');

    $etapedate = "";
    $projectid = 0;

	if(strpos($tasktxtwithid, 'task') !== false) {
		$tmptask = new Task($db);
		$tid = str_replace("task","",$tasktxtwithid);
		$tmptask->fetch($tid);

		$projectid = (int) $tmptask->fk_project;

	} else if(strpos($tasktxtwithid, 'project') !== false) {
		$tid = str_replace("project","",$tasktxtwithid);
		$projectid = (int) $tid;
	}

	if($projectid > 0) {
		$sql = "SELECT o.etat_date 
				FROM ".MAIN_DB_PREFIX."abnovomoduleongletetatavancement as o
			 	WHERE o.module = 'project'
		 	 	AND o.fk_module = ".(int) $projectid."
		 	 	AND o.fk_etat = ".(int) $abnovomoduleetape."
				";
				
 		$res = $db->query($sql);
    	if ($res) {
            $obj = $db->fetch_object($res);
            if ($obj) {
		        $etapedate = dol_print_date($db->idate($obj->etat_date), "day");
            }
        }

	}

    echo $etapedate;
	die;
}




