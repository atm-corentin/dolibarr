<?php 
dol_include_once('/core/lib/admin.lib.php');
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
dol_include_once("/ganttproadvanced/class/ganttproadvanced.class.php");


class ganttproadvancedutils
{

    public function __construct($db)
    {   
        global $langs, $conf;

        $this->db = $db;
    }


    public function clschangeStartEndDate($task, $relatedtask, $typesrelation, $numberdayslate=0, $forphporjs = 'php')
    {
        global $langs, $conf, $tasksdates, $tasksobjs;

        $data = array('date_start' => '', 'date_end' => '');

        $debugtask = false;
        $onedaysecond = 86400;

        // -------------------------------------------------------------------------------------------------------------------

        $olddate_start = 0;
        $olddate_end = 0;

        if($task->id > 0) {

            if(isset($tasksobjs[$task->id])) {
                $task = $tasksobjs[$task->id];
            }

            $olddate_start = $task->date_start;
            $olddate_end = $task->date_end;

            $task->date_start = strtotime(date("Y-m-d", ($task->date_start)) );
            $task->date_end = strtotime(date("Y-m-d", ($task->date_end)) );
        }


        // -------------------------------------------------------------------------------------------------------------------

        $relatedtask->date_start = strtotime(date("Y-m-d", ($relatedtask->date_start)) );
        $relatedtask->date_end = strtotime(date("Y-m-d", ($relatedtask->date_end)) );

        $date_start = $task->date_start;
        $date_end = $task->date_end;

        $olddate_start = $task->date_start;
        $olddate_end = $task->date_end;

        // -------------------------------------------------------------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------------

        $durationtask = (int) $date_end - (int) $date_start;

        $secondslate = (int) ((int)($numberdayslate)*$onedaysecond);

        // -------------------------------------------------------------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------------

        $datafilled = false;

        if($typesrelation == ganttproadvanced::TYPE_START_START || $typesrelation == ganttproadvanced::TYPE_END_START){

            $date_start = ($typesrelation == ganttproadvanced::TYPE_START_START) ? $relatedtask->date_start : $relatedtask->date_end;

            if($secondslate > 0){
                $date_start = $date_start + $secondslate;
            }

            $date_end = ($date_start+$durationtask);

            $datafilled = true;
        }

        elseif($typesrelation == ganttproadvanced::TYPE_END_END || $typesrelation == ganttproadvanced::TYPE_START_END){

            $date_end = ($typesrelation == ganttproadvanced::TYPE_END_END) ? $relatedtask->date_end : $relatedtask->date_start;

            if($secondslate > 0){
                $date_end = $date_end + $secondslate;
            }

            $date_start = ($date_end-$durationtask);

            $datafilled = true;
        }


        // if($task->id == 300 && $debugtask) {
        //     d('date_start 4: '.date("Y-m-d H:i", $date_start),0);
        //     d('date_end 4: '.date("Y-m-d H:i", $date_end),0);
        // }


        if($date_end) {
            $date_end = (int) $date_end + 86340;
        }

        if($datafilled && $olddate_start != $date_start && $olddate_end != $date_end) {

            $tasksdates[$task->id]['start'] = $date_start;
            $tasksdates[$task->id]['end'] = $date_end;

            $task->date_start = $date_start;
            $task->date_end = $date_end;

            if($task->id) {
                $tasksobjs[$task->id] = $task;
            }
        }


        // -------------------------------------------------------------------------------------------------------------------
        // -------------------------------------------------------------------------------------------------------------------

        // $data['date_start'] = $date_start ? dol_print_date($date_start, "day") : '';
        // $data['date_end'] = $date_end ? dol_print_date($date_end, "day") : '';

        $format = 'd/m/Y';


        $data['date_start'] = $date_start ? date($format, $date_start) : '';
        $data['date_end'] = $date_end ? date($format, $date_end) : '';


        return $data;
    }

    public function checkRecursivlyRelationsAndProjectDates($currentobjtask)
    {
        global $conf, $user, $langs;

        $projectsobjs       = array();
        $tasksobjs          = array();
        $tasksdates         = array();
        $projectdates       = array();
        $alreadytasksids    = array();
        $projectids         = array();
        $ganttproadvancedjstoapply  = '';
        
        $ganttproadvanced = new ganttproadvanced($this->db);

        global $projectsobjs, $tasksobjs, $tasksdates, $projectdates, $alreadytasksids, $ganttproadvancedjstoapply, $projectids;

        $this->checkRecursivlyRelations($currentobjtask);

        $ganttproadvancedjstoapply = '';

        $dependencytaskmsg = '';

        // d($tasksdates,0);
        if(is_array($tasksdates) && count($tasksdates) > 0) {
            foreach ($tasksdates as $objid => $data) {

                // if(isset($tasksobjs[$objid])) {
                //     $tmpobjtask = $tasksobjs[$objid];
                // } else {
                //     $tmpobjtask = new Task($this->db);
                //     $tmpobjtask->fetch($objid);
                // }

                $tmpobjtask = new Task($this->db);
                $restask = $tmpobjtask->fetch($objid);

                // d('tmpobjtask->date_start : '.$tmpobjtask->date_start,0);
                // d('data[start] : '.$data['start'],0);
                // d('tmpobjtask->date_end : '.$tmpobjtask->date_end,0);
                // d('data[end] : '.$data['end'],0);

                if($restask > 0 && ($tmpobjtask->date_start != $data['start'] || $tmpobjtask->date_end != $data['end'])) {

                    $tmpobjtask->date_start = $data['start'];
                    $tmpobjtask->date_end = $data['end'];

                    $restask = $tmpobjtask->update($user);

                    if($restask > 0) {


                        $ganttproadvancedjstoapply .= 'var tmptaskid = "task'.$objid.'";'."\n";

                        $ganttproadvancedjstoapply .= 'if (gantt.isTaskExists(tmptaskid)) {'."\n";
                            $ganttproadvancedjstoapply .= 'var tmptask = gantt.getTask(tmptaskid);'."\n";
                            // $ganttproadvancedjstoapply .= 'tmptask.start_date = new Date("'.dol_print_date(($tmpobjtask->date_start), "%Y-%m-%d", 'tzserver').'");'."\n";
                            // $ganttproadvancedjstoapply .= 'tmptask.end_date = new Date("'.dol_print_date(($tmpobjtask->date_end), "%Y-%m-%d", 'tzserver').'");'."\n";
                            $ganttproadvancedjstoapply .= 'tmptask.start_date = new Date("'.date("Y-m-d", ($tmpobjtask->date_start)).'");'."\n";
                            $ganttproadvancedjstoapply .= 'tmptask.end_date = new Date("'.date("Y-m-d", ($tmpobjtask->date_end)).'");'."\n";

                            $ganttproadvancedjstoapply .= 'tmptask.start_date.setHours(0,0,0,0);'."\n";
                            $ganttproadvancedjstoapply .= 'tmptask.end_date.setHours(0,0,0,0);'."\n";

                            $ganttproadvancedjstoapply .= 'gantt.refreshTask(tmptaskid,tmptask);'."\n\n";

                            // $taskname = $tmpobjtask->ref. ($tmpobjtask->label ? ' - '.$tmpobjtask->label : '');
                            $taskname = $tmpobjtask->label;
                            $dependencytaskmsg .= ($dependencytaskmsg) ? ', '.$taskname : $taskname;

                        $ganttproadvancedjstoapply .= '}'."\n";
                    }

                }

            }

            if($ganttproadvancedjstoapply && $dependencytaskmsg) {
                $ganttproadvancedjstoapply .= '$(".gantt_message_area .gantt-info").remove();';
                $ganttproadvancedjstoapply .= 'gantt.message({type:"warning", text:"'.$langs->trans("ThereIsADependencyOfTaskFor").' : '.$dependencytaskmsg.'"});';
                $ganttproadvancedjstoapply .= 'gantt.selectTask(tmptaskid);';
            }
        }

        // d($projectids);

        if($ganttproadvanced->minmaxtasksdatetoproject > 0 && is_array($projectids) && count($projectids) > 0) {
            foreach ($projectids as $objid => $objproject) {

                $sql2 = 'SELECT MIN(dateo) as date_start, MAX(datee) as date_end FROM '.MAIN_DB_PREFIX.'projet_task WHERE fk_projet = '.(int) $objid.';';
                $resql2 = $this->db->query($sql2);

                if ($resql2)
                {
                    while ($obj = $this->db->fetch_object($resql2))
                    {   
                        global $user;

                        $tmpdate_start = $this->db->jdate($obj->date_start);
                        $tmpdate_end = $this->db->jdate($obj->date_end);

                        // d('tmpdate_start : '.dol_print_date($tmpdate_start, 'day'),0);
                        // d('tmpdate_end : '.dol_print_date($tmpdate_end, 'day'),0);
                        // d('objproject->date_start : '.dol_print_date($objproject->date_start, 'day'),0);
                        // d('objproject->date_end : '.dol_print_date($objproject->date_end, 'day'),0);

                        if($objproject->date_start != $tmpdate_start || $objproject->date_end != $tmpdate_end) {

                            $objproject->date_start = $this->db->jdate($obj->date_start);
                            $objproject->date_end = $this->db->jdate($obj->date_end);

                            $resproj = $objproject->update($user);
                        
                            if($resproj > 0) {
                                $ganttproadvancedjstoapply .= 'var tmptaskid = "project'.$objid.'";'."\n";
                                $ganttproadvancedjstoapply .= 'if (gantt.isTaskExists(tmptaskid)) {'."\n";

                                    $ganttproadvancedjstoapply .= 'var tmptask = gantt.getTask(tmptaskid);'."\n";
                                    // $ganttproadvancedjstoapply .= 'tmptask.start_date = new Date("'.dol_print_date(($objproject->date_start), "%Y-%m-%d", 'tzserver').'");'."\n";
                                    // $ganttproadvancedjstoapply .= 'tmptask.end_date = new Date("'.dol_print_date(($objproject->date_end), "%Y-%m-%d", 'tzserver').'");'."\n";
                                    $ganttproadvancedjstoapply .= 'tmptask.start_date = new Date("'.date("Y-m-d", ($objproject->date_start)).'");'."\n";
                                    $ganttproadvancedjstoapply .= 'tmptask.end_date = new Date("'.date("Y-m-d", ($objproject->date_end)).'");'."\n";

                                    $ganttproadvancedjstoapply .= 'tmptask.start_date.setHours(0,0,0,0);'."\n";
                                    $ganttproadvancedjstoapply .= 'tmptask.end_date.setHours(0,0,0,0);'."\n";

                                    $ganttproadvancedjstoapply .= 'gantt.refreshTask(tmptaskid,tmptask);'."\n\n";

                                $ganttproadvancedjstoapply .= '}'."\n";
                            }
                        }

                    }
                }

                if($ganttproadvancedjstoapply) {
                    $ganttproadvancedjstoapply .= ' setTimeout(function(){ gantt.render(); },800);'."\n\n";
                    // $ganttproadvancedjstoapply .= 'gantt.refreshData();'."\n\n";
                    // $ganttproadvancedjstoapply .= 'gantt.showDate('.$gotothisdate.');'."\n\n";
                }

            }
        }

    }

    public function checkRecursivlyRelations($currentobjtask)
    {
        global $conf, $user, $projectsobjs, $tasksobjs, $tasksdates, $projectdates, $alreadytasksids, $projectids;

        $ganttproadvanced = new ganttproadvanced($this->db);
        
        if(!$currentobjtask->id || !$currentobjtask->date_start || !$currentobjtask->date_end) return -1;

        // -----------------------------------------------------------------------------------------------------
        // if(isset($projectsobjs[$currentobjtask->fk_project])) {
        //     $project = $projectsobjs[$currentobjtask->fk_project];
        // } else {
        //     $project = new Project($this->db);
        //     $project->fetch($currentobjtask->fk_project);
            
        //     $projectsobjs[$currentobjtask->fk_project] = $project;
        // }
        // $this->ganttproadvancedCheckProjectDates($currentobjtask, $project);


        // -----------------------------------------------------------------------------------------------------
        if($ganttproadvanced->minmaxtasksdatetoproject > 0 && !isset($projectids[$currentobjtask->fk_project])) {
            $project = new Project($this->db);
            $project->fetch($currentobjtask->fk_project);

            $projectids[$project->id] = $project;
        }

        // -----------------------------------------------------------------------------------------------------
        $sql = 'SELECT t.rowid as id, t.dateo as date_start, t.datee as date_end, t.fk_projet as fk_project';
        $sql .= ', ex_t.ganttproadvancedtyperelation, ex_t.ganttproadvancednumberdayslate ';

        $sql .= ' FROM '.MAIN_DB_PREFIX.'projet_task as t';

        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as p ON (p.rowid = t.fk_projet) ';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task_extrafields as ex_t ON (t.rowid = ex_t.fk_object) ';

        $sql .= ' WHERE t.entity IN (0,'.(int) $conf->entity.')';

        // $sql .= ' AND p.fk_statut < '.Project::STATUS_CLOSED;

        $sql .= ' AND ex_t.ganttproadvancedrelatedtask = '.(int) $currentobjtask->id;
        $sql .= ' AND ex_t.ganttproadvancedtyperelation > 0 ';
        // -----------------------------------------------------------------------------------------------------

        // echo $sql;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $i=0;
            while ($obj = $this->db->fetch_object($resql))
            {   
                $taskid = $obj->id;

                if($obj->date_start && $obj->date_end && $currentobjtask->date_start && $currentobjtask->date_end) {

                    $obj->date_start = strtotime($obj->date_start);
                    $obj->date_end = strtotime($obj->date_end);

                    $this->clschangeStartEndDate($obj, $currentobjtask, $obj->ganttproadvancedtyperelation, $obj->ganttproadvancednumberdayslate);
                }

                // ---------------------------------------------------------------------------------------------
                if(isset($alreadytasksids[$taskid])) continue; // Avoid Loop

                $alreadytasksids[$taskid] = $taskid;

                // ---------------------------------------------------------------------------------------------
                $this->checkRecursivlyRelations($obj);

                $i++;
            }
        }
        else {
            dol_print_error($this->db);
        }

        // -----------------------------------------------------------------------------------------------------
        

        return 1;
    }

    public function ganttproadvancedselectProjectTasks($relatedtask = 0, $useempty=1)
    {
        global $user, $langs, $currentmodiftask;

        $arrayfullhierars = array();
        $lastprojectid = 0;

        global $arrayfullhierars, $lastprojectid;

        if($currentmodiftask > 0) {
            $arrayfullhierars[$currentmodiftask] = $currentmodiftask;
        }

        $html = '';
        
        $modetask = 0; $modeproject = 0;
        $tasksarray = $this->ganttproadvancedGetTasksArray($modetask ? $user : 0, $modeproject ? $user : 0, $projectid = 0, 0, $mode = 0, '', $filteronprojstatus = -1);
        
        if ($tasksarray) {
            $html .= '<select class="flat maxwidth300" name="options_ganttproadvancedrelatedtask" id="options_ganttproadvancedrelatedtask" onchange="changeStartEndDate(\'task'.$relatedtask.'\')">';
            if ($useempty) {
                $html .= '<option value="0">&nbsp;</option>';
            }
            $j = 0;
            $level = 0;
            $html .= $this->ganttproadvanced_pLineSelect($j, 0, $tasksarray, $level, $relatedtask, $projectid, $disablechildoftaskid = 0);
            $html .= '</select>';

            // $html .= ajax_combobox($htmlname);
        } else {
            $html .= '<div class="warning">'.$langs->trans("NoProject").'</div>';
        }

        return $html;
    }

    public function checkIfTheSelectedTaskAlreadyExistInTheTree($task_id = 0, $relatedtask = 0)
    {
        global $arrayfullhierars;

        $sql = ' SELECT fk_object, ganttproadvancedrelatedtask ';

        $sql .= ' FROM (SELECT * FROM '.MAIN_DB_PREFIX.'projet_task_extrafields';

        $sql .= ' order by ganttproadvancedrelatedtask, fk_object) projet_task_extrafields_stored,';

        $sql .= ' (SELECT @fulltreehierarchical := "'.$task_id.'") fulltreeinitialisation';

        $sql .= ' WHERE find_in_set(ganttproadvancedrelatedtask, @fulltreehierarchical)';

        $sql .= ' AND length(@fulltreehierarchical := concat(@fulltreehierarchical, ",", fk_object));';

        $resql = $this->db->query($sql);
        
        // if(!$resql) d($this->db->lasterror(),0);
        
        if ($resql)
        {
            while ($obj = $this->db->fetch_object($resql))
            {
                if($obj->fk_object != $relatedtask)
                    $arrayfullhierars[$obj->fk_object] = $obj->fk_object;
            }

        }

        // d($arrayfullhierars,0);

        return $arrayfullhierars;
    }


    /**
     * Write lines of a project (all lines of a project if parent = 0)
     *
     * @param   int     $inc                    Cursor counter
     * @param   int     $parent                 Id of parent task we want to see
     * @param   array   $lines                  Array of task lines
     * @param   int     $level                  Level
     * @param   int     $selectedtask           Id selected task
     * @param   int     $selectedproject        Id selected project
     * @param   int     $disablechildoftaskid   1=Disable task that are child of the provided task id
     * @return  void
     */
    private function ganttproadvanced_pLineSelect(&$inc, $parent, $lines, $level = 0, $selectedtask = 0, $selectedproject = 0, $disablechildoftaskid = 0)
    {
        global $langs, $user, $conf, $arrayfullhierars, $currentmodiftask, $lastprojectid;

        $htmlline = '';

        $numlines = count($lines);
        for ($i = 0; $i < $numlines; $i++) {
            if ($lines[$i]->fk_parent == $parent) {
                //var_dump($selectedproject."--".$selectedtask."--".$lines[$i]->fk_project."_".$lines[$i]->id);     // $lines[$i]->id may be empty if project has no lines

                // Break on a new project
                // if ($parent == 0) { // We are on a task at first level
                //     if ($lines[$i]->fk_project != $lastprojectid) { // Break found on project
                //         if ($i > 0) {
                //             $htmlline .= '<option value="0" disabled>----------</option>';
                //         }
                //         $htmlline .= '<option value="'.$lines[$i]->fk_project.'_0"';
                //         if ($selectedproject == $lines[$i]->fk_project) {
                //             $htmlline .= ' selected';
                //         }

                //         $labeltoshow = $lines[$i]->projectref;
                //         //$labeltoshow .= ' '.$lines[$i]->projectlabel;
                //         if (empty($lines[$i]->public)) {
                //             //$labeltoshow .= ' <span class="opacitymedium">('.$langs->trans("Visibility").': '.$langs->trans("PrivateProject").')</span>';
                //             $labeltoshow = img_picto($lines[$i]->projectlabel, 'project', 'class="pictofixedwidth"').$labeltoshow;
                //         } else {
                //             //$labeltoshow .= ' <span class="opacitymedium">('.$langs->trans("Visibility").': '.$langs->trans("SharedProject").')</span>';
                //             $labeltoshow = img_picto($lines[$i]->projectlabel, 'projectpub', 'class="pictofixedwidth"').$labeltoshow;
                //         }

                //         $htmlline .= ' data-html="'.dol_escape_htmltag($labeltoshow).'"';
                //         $htmlline .= '>'; // Project -> Task
                //         $htmlline .= $labeltoshow;
                //         $htmlline .= "</option>\n";

                //         $lastprojectid = $lines[$i]->fk_project;
                //         $inc++;
                //     }
                // }

                $newdisablechildoftaskid = $disablechildoftaskid;

                // $htmlline .= task
                if (isset($lines[$i]->id)) {        // We use isset because $lines[$i]->id may be null if project has no task and are on root project (tasks may be caught by a left join). We enter here only if '0' or >0
                    // Check if we must disable entry
                    $disabled = 0;
                    if ($disablechildoftaskid && (($lines[$i]->id == $disablechildoftaskid || $lines[$i]->fk_parent == $disablechildoftaskid))) {
                        $disabled++;
                        if ($lines[$i]->fk_parent == $disablechildoftaskid) {
                            $newdisablechildoftaskid = $lines[$i]->id; // If task is child of a disabled parent, we will propagate id to disable next child too
                        }
                    }

                    if(empty($lines[$i]->date_start) || empty($lines[$i]->date_end) || isset($arrayfullhierars[$lines[$i]->id])) {
                        $disabled++;
                    }

                    if($currentmodiftask > 0 && $currentmodiftask == $lines[$i]->ganttproadvancedrelatedtask) {
                        $disabled++;
                    }

                    if(empty($lastprojectid) || !empty($lastprojectid) && $lines[$i]->fk_project > 0 && $lastprojectid != $lines[$i]->fk_project) {
                        $htmlline .= '<optgroup label="'.$lines[$i]->projectlabel.'">';
                    }

                    elseif(!empty($lastprojectid) && $lines[$i]->fk_project > 0 && $lastprojectid != $lines[$i]->fk_project) {
                        $htmlline .= ' </optgroup>';
                    }


                    $htmlline .= '<option value="'.$lines[$i]->id.'"';
                    // if (($lines[$i]->id == $selectedtask) || ($lines[$i]->fk_project.'_'.$lines[$i]->id == $selectedtask)) {
                    if ($disabled) {
                        $htmlline .= ' disabled';
                    }
                    // d('selectedtask : '.$selectedtask,0);
                    if (($lines[$i]->id == $selectedtask && !$disabled)) {
                        $htmlline .= ' selected';
                    }

                    $labeltoshow = $lines[$i]->projectref;
                    $labeltoshow = '';
                    $labeltoshow .= ' '.$lines[$i]->projectlabel;
                    if (empty($lines[$i]->public)) {
                        //$labeltoshow .= ' <span class="opacitymedium">('.$langs->trans("Visibility").': '.$langs->trans("PrivateProject").')</span>';
                        // $labeltoshow = img_picto($lines[$i]->projectlabel, 'project', 'class="pictofixedwidth"').$labeltoshow;
                        $labeltoshow = $labeltoshow;
                    } else {
                        //$labeltoshow .= ' <span class="opacitymedium">('.$langs->trans("Visibility").': '.$langs->trans("SharedProject").')</span>';
                        // $labeltoshow = img_picto($lines[$i]->projectlabel, 'projectpub', 'class="pictofixedwidth"').$labeltoshow;
                        $labeltoshow = $labeltoshow;
                    }
                    if ($lines[$i]->id) {
                        $labeltoshow .= ' > ';
                    }
                    for ($k = 0; $k < $level; $k++) {
                        // $labeltoshow .= "&nbsp;&nbsp;&nbsp;";
                    }

                    $taskdates = '(';
                    if($lines[$i]->date_start) {
                        $taskdates .= dol_print_date($lines[$i]->date_start, "day");
                    } else {
                        $taskdates .= '?';
                    }
                    $taskdates .= ' - ';
                    if($lines[$i]->date_end) {
                        $taskdates .= dol_print_date($lines[$i]->date_end, "day");
                    }
                    $taskdates .= ')';

                    // $labeltoshow .= $lines[$i]->ref.' - '.$lines[$i]->label.' '.$taskdates;
                    $labeltoshow .= $lines[$i]->label.' '.$taskdates;

                    $htmlline .= ' data-html="'.dol_escape_htmltag($labeltoshow).'"';
                    $htmlline .= '>';
                    $htmlline .= $labeltoshow;
                    $htmlline .= "</option>\n";

                    $lastprojectid = ($lines[$i]->fk_project > 0) ? $lines[$i]->fk_project : $lastprojectid;

                    $inc++;
                }

                $level++;
                if ($lines[$i]->id) {
                    $htmlline .= $this->ganttproadvanced_pLineSelect($inc, $lines[$i]->id, $lines, $level, $selectedtask, $selectedproject, $newdisablechildoftaskid);
                }
                $level--;
            }
        }

        return $htmlline;
    }

    /**
     * Return list of tasks for all projects or for one particular project
     * Sort order is on project, then on position of task, and last on start date of first level task
     *
     * @param   User    $usert              Object user to limit tasks affected to a particular user
     * @param   User    $userp              Object user to limit projects of a particular user and public projects
     * @param   int     $projectid          Project id
     * @param   int     $socid              Third party id
     * @param   int     $mode               0=Return list of tasks and their projects, 1=Return projects and tasks if exists
     * @param   string  $filteronproj       Filter on project ref or label
     * @param   string  $filteronprojstatus Filter on project status ('-1'=no filter, '0,1'=Draft+Validated only)
     * @param   string  $morewherefilter    Add more filter into where SQL request (must start with ' AND ...')
     * @param   string  $filteronprojuser   Filter on user that is a contact of project
     * @param   string  $filterontaskuser   Filter on user assigned to task
     * @param   array   $extrafields        Show additional column from project or task
     * @param   int     $includebilltime    Calculate also the time to bill and billed
     * @param   array   $search_array_options Array of search
     * @param   int     $loadextras         Fetch all Extrafields on each task
     * @param   int     $loadRoleMode       1= will test Roles on task;  0 used in delete project action
     * @return  array                       Array of tasks
     */
    public function ganttproadvancedGetTasksArray($usert = null, $userp = null, $projectid = 0, $socid = 0, $mode = 0, $filteronproj = '', $filteronprojstatus = '-1', $morewherefilter = '', $filteronprojuser = 0, $filterontaskuser = 0, $extrafields = array(), $includebilltime = 0, $search_array_options = array(), $loadextras = 0, $loadRoleMode = 1)
    {
        global $conf, $hookmanager;

        $tasks = array();

        $ganttproadvanced = new ganttproadvanced($this->db);
        $newcondition = ($includebilltime && $ganttproadvanced->table_task_time == "element_time") ? " AND tt.elementtype = 'task' " : "";

        //print $usert.'-'.$userp.'-'.$projectid.'-'.$socid.'-'.$mode.'<br>';

        // List of tasks (does not care about permissions. Filtering will be done later)
        $sql = "SELECT ";
        if ($filteronprojuser > 0 || $filterontaskuser > 0) {
            $sql .= " DISTINCT"; // We may get several time the same record if user has several roles on same project/task
        }
        $sql .= " p.rowid as projectid, p.ref, p.title as plabel, p.public, p.fk_statut as projectstatus, p.usage_bill_time,";
        $sql .= " t.rowid as taskid, t.ref as taskref, t.label, t.description, t.fk_task_parent, t.duration_effective, t.progress, t.fk_statut as status,";
        $sql .= " t.dateo as date_start, t.datee as date_end, t.planned_workload, t.rang,";
        $sql .= " t.description, ";
        $sql .= " t.budget_amount, ";
        $sql .= " s.rowid as thirdparty_id, s.nom as thirdparty_name, s.email as thirdparty_email,";
        $sql .= " p.fk_opp_status, p.opp_amount, p.opp_percent, p.budget_amount as project_budget_amount";
        $sql .= " ,efpt.ganttproadvancedrelatedtask";
        if (!empty($extrafields->attributes['projet']['label'])) {
            foreach ($extrafields->attributes['projet']['label'] as $key => $val) {
                $sql .= ($extrafields->attributes['projet']['type'][$key] != 'separate' ? ",efp.".$key." as options_".$key : '');
            }
        }
        if (!empty($extrafields->attributes['projet_task']['label'])) {
            foreach ($extrafields->attributes['projet_task']['label'] as $key => $val) {
                $sql .= ($extrafields->attributes['projet_task']['type'][$key] != 'separate' ? ",efpt.".$key." as options_".$key : '');
            }
        }
        if ($includebilltime) {
            $sql .= ", SUM(tt.".$ganttproadvanced->column_task_duration." * ".$this->db->ifsql("invoice_id IS NULL", "1", "0").") as tobill, SUM(tt.".$ganttproadvanced->column_task_duration." * ".$this->db->ifsql("invoice_id IS NULL", "0", "1").") as billed";
        }

        $sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as efp ON (p.rowid = efp.fk_object)";

        if ($mode == 0) {
            if ($filteronprojuser > 0) {
                $sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
                $sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
            }
            $sql .= ", ".MAIN_DB_PREFIX."projet_task as t";
            if ($includebilltime) {
                $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$ganttproadvanced->table_task_time." as tt ON tt.".$ganttproadvanced->column_fk_task." = t.rowid";
            }
            if ($filterontaskuser > 0) {
                $sql .= ", ".MAIN_DB_PREFIX."element_contact as ec2";
                $sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc2";
            }
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_extrafields as efpt ON (t.rowid = efpt.fk_object)";
            $sql .= " WHERE p.entity IN (".getEntity('project').")";
            $sql .= " AND t.fk_projet = p.rowid";
        } elseif ($mode == 1) {
            if ($filteronprojuser > 0) {
                $sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
                $sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
            }
            if ($filterontaskuser > 0) {
                $sql .= ", ".MAIN_DB_PREFIX."projet_task as t";
                if ($includebilltime) {
                    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$ganttproadvanced->table_task_time." as tt ON tt.".$ganttproadvanced->column_fk_task." = t.rowid";
                }
                $sql .= ", ".MAIN_DB_PREFIX."element_contact as ec2";
                $sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc2";
            } else {
                $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t on t.fk_projet = p.rowid";
                if ($includebilltime) {
                    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$ganttproadvanced->table_task_time." as tt ON tt.".$ganttproadvanced->column_fk_task." = t.rowid";
                }
            }
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_extrafields as efpt ON (t.rowid = efpt.fk_object)";
            $sql .= " WHERE p.entity IN (".getEntity('project').")";
        } else {
            return 'BadValueForParameterMode';
        }

        if ($filteronprojuser > 0) {
            $sql .= " AND p.rowid = ec.element_id";
            $sql .= " AND ctc.rowid = ec.fk_c_type_contact";
            $sql .= " AND ctc.element = 'project'";
            $sql .= " AND ec.fk_socpeople = ".((int) $filteronprojuser);
            $sql .= " AND ec.statut = 4";
            $sql .= " AND ctc.source = 'internal'";
        }
        if ($filterontaskuser > 0) {
            $sql .= " AND t.fk_projet = p.rowid";
            $sql .= " AND p.rowid = ec2.element_id";
            $sql .= " AND ctc2.rowid = ec2.fk_c_type_contact";
            $sql .= " AND ctc2.element = 'project_task'";
            $sql .= " AND ec2.fk_socpeople = ".((int) $filterontaskuser);
            $sql .= " AND ec2.statut = 4";
            $sql .= " AND ctc2.source = 'internal'";
        }
        if ($socid) {
            $sql .= " AND p.fk_soc = ".((int) $socid);
        }
        if ($projectid) {
            $sql .= " AND p.rowid IN (".$this->db->sanitize($projectid).")";
        }
        if ($filteronproj) {
            $sql .= natural_search(array("p.ref", "p.title"), $filteronproj);
        }
        if ($filteronprojstatus && $filteronprojstatus != '-1') {
            $sql .= " AND p.fk_statut IN (".$this->db->sanitize($filteronprojstatus).")";
        }
        if ($morewherefilter) {
            $sql .= $morewherefilter;
        }

        $sql .= $newcondition;
        
        // Add where from extra fields
        $extrafieldsobjectkey = 'projet_task';
        $extrafieldsobjectprefix = 'efpt.';
        global $db; // needed for extrafields_list_search_sql.tpl
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
        // Add where from hooks
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        if ($includebilltime) {
            $sql .= " GROUP BY p.rowid, p.ref, p.title, p.public, p.fk_statut, p.usage_bill_time,";
            $sql .= " t.datec, t.dateo, t.datee, t.tms,";
            $sql .= " t.rowid, t.ref, t.label, t.description, t.fk_task_parent, t.duration_effective, t.progress, t.fk_statut,";
            $sql .= " t.dateo, t.datee, t.planned_workload, t.rang,";
            $sql .= " t.description, ";
            $sql .= " t.budget_amount, ";
            $sql .= " s.rowid, s.nom, s.email,";
            $sql .= " p.fk_opp_status, p.opp_amount, p.opp_percent, p.budget_amount";
            if (!empty($extrafields->attributes['projet']['label'])) {
                foreach ($extrafields->attributes['projet']['label'] as $key => $val) {
                    $sql .= ($extrafields->attributes['projet']['type'][$key] != 'separate' ? ",efp.".$key : '');
                }
            }
            if (!empty($extrafields->attributes['projet_task']['label'])) {
                foreach ($extrafields->attributes['projet_task']['label'] as $key => $val) {
                    $sql .= ($extrafields->attributes['projet_task']['type'][$key] != 'separate' ? ",efpt.".$key : '');
                }
            }
        }


        // $sql .= " ORDER BY p.ref, t.rang, t.dateo";
        $sql .= " ORDER BY p.rowid DESC";

        $task = new Task($this->db);

        // print $sql;exit;
        $resql = $this->db->query($sql);

        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            // Loop on each record found, so each couple (project id, task id)
            while ($i < $num) {
                $error = 0;

                $obj = $this->db->fetch_object($resql);

                if ($loadRoleMode) {
                    if ((!$obj->public) && (is_object($userp))) {    // If not public project and we ask a filter on project owned by a user
                        if (!$task->getUserRolesForProjectsOrTasks($userp, 0, $obj->projectid, 0)) {
                            $error++;
                        }
                    }
                    if (is_object($usert)) {                            // If we ask a filter on a user affected to a task
                        if (!$task->getUserRolesForProjectsOrTasks(0, $usert, $obj->projectid, $obj->taskid)) {
                            $error++;
                        }
                    }
                }

                if (!$error) {
                    $tasks[$i] = new Task($this->db);
                    $tasks[$i]->id = $obj->taskid;
                    $tasks[$i]->ref = $obj->taskref;
                    $tasks[$i]->fk_project      = $obj->projectid;
                    $tasks[$i]->projectref      = $obj->ref;
                    $tasks[$i]->projectlabel = $obj->plabel;
                    $tasks[$i]->projectstatus = $obj->projectstatus;

                    $tasks[$i]->fk_opp_status = $obj->fk_opp_status;
                    $tasks[$i]->opp_amount = $obj->opp_amount;
                    $tasks[$i]->opp_percent = $obj->opp_percent;
                    $tasks[$i]->budget_amount = $obj->budget_amount;
                    $tasks[$i]->project_budget_amount = $obj->project_budget_amount;
                    $tasks[$i]->usage_bill_time = $obj->usage_bill_time;

                    $tasks[$i]->label = $obj->label;
                    $tasks[$i]->description = $obj->description;
                    $tasks[$i]->fk_parent = $obj->fk_task_parent; // deprecated
                    $tasks[$i]->fk_task_parent = $obj->fk_task_parent;
                    $tasks[$i]->duration        = $obj->duration_effective;
                    $tasks[$i]->planned_workload = $obj->planned_workload;

                    if ($includebilltime) {
                        $tasks[$i]->tobill = $obj->tobill;
                        $tasks[$i]->billed = $obj->billed;
                    }

                    $tasks[$i]->progress        = $obj->progress;
                    $tasks[$i]->fk_statut = $obj->status;
                    $tasks[$i]->public = $obj->public;
                    $tasks[$i]->date_start = $this->db->jdate($obj->date_start);
                    $tasks[$i]->date_end        = $this->db->jdate($obj->date_end);
                    $tasks[$i]->rang            = $obj->rang;

                    $tasks[$i]->socid           = $obj->thirdparty_id; // For backward compatibility
                    $tasks[$i]->thirdparty_id = $obj->thirdparty_id;
                    $tasks[$i]->thirdparty_name = $obj->thirdparty_name;
                    $tasks[$i]->thirdparty_email = $obj->thirdparty_email;
                    $tasks[$i]->ganttproadvancedrelatedtask = $obj->ganttproadvancedrelatedtask;

                    if (!empty($extrafields->attributes['projet']['label'])) {
                        foreach ($extrafields->attributes['projet']['label'] as $key => $val) {
                            if ($extrafields->attributes['projet']['type'][$key] != 'separate') {
                                $tasks[$i]->{'options_'.$key} = $obj->{'options_'.$key};
                            }
                        }
                    }

                    if (!empty($extrafields->attributes['projet_task']['label'])) {
                        foreach ($extrafields->attributes['projet_task']['label'] as $key => $val) {
                            if ($extrafields->attributes['projet_task']['type'][$key] != 'separate') {
                                $tasks[$i]->{'options_'.$key} = $obj->{'options_'.$key};
                            }
                        }
                    }

                    if ($loadextras) {
                        $tasks[$i]->fetch_optionals();
                    }
                }

                $i++;
            }
            $this->db->free($resql);
        } else {
            dol_print_error($this->db);
        }

        return $tasks;
    }

    public function ganttproadvancedTimesheetperdayGetTasks(&$arrayoftasks, $usert = null, $userp = null, $projectid = 0, $socid = 0, $mode = 0, $filteronproj = '', $filteronprojstatus = '-1', $morewherefilter = '', $filteronprojuser = 0, $filterontaskuser = 0, $extrafields = array(), $includebilltime = 0, $search_array_options = array(), $loadextras = 0)
    {
        global $conf, $hookmanager, $db;

        $tasks = array();

        $ganttproadvanced = new ganttproadvanced($this->db);
        $newcondition = ($includebilltime && $ganttproadvanced->table_task_time == "element_time") ? " AND tt.elementtype = 'task' " : "";

        $now = dol_now();

        //print $usert.'-'.$userp.'-'.$projectid.'-'.$socid.'-'.$mode.'<br>';

        // List of tasks (does not care about permissions. Filtering will be done later)
        $sql = "SELECT ";
        $sql .= " p.rowid as projectid, p.ref";
        $sql .= " ,t.datee ,t.rowid as taskid,t.fk_task_parent";

        $sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as efp ON (p.rowid = efp.fk_object)";

        if ($mode == 0) {
            if ($filteronprojuser > 0) {
                $sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
                $sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
            }
            $sql .= ", ".MAIN_DB_PREFIX."projet_task as t";
            if ($includebilltime) {
                $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$ganttproadvanced->table_task_time." as tt ON tt.".$ganttproadvanced->column_fk_task." = t.rowid";
            }
            if ($filterontaskuser > 0) {
                $sql .= ", ".MAIN_DB_PREFIX."element_contact as ec2";
                $sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc2";
            }
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_extrafields as efpt ON (t.rowid = efpt.fk_object)";
            $sql .= " WHERE p.entity IN (".getEntity('project').")";
            $sql .= " AND t.fk_projet = p.rowid";

        } elseif ($mode == 1) {
            if ($filteronprojuser > 0) {
                $sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
                $sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
            }
            if ($filterontaskuser > 0) {
                $sql .= ", ".MAIN_DB_PREFIX."projet_task as t";
                if ($includebilltime) {
                    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$ganttproadvanced->table_task_time." as tt ON tt.".$ganttproadvanced->column_fk_task." = t.rowid";
                }
                $sql .= ", ".MAIN_DB_PREFIX."element_contact as ec2";
                $sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc2";
            } else {
                $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t on t.fk_projet = p.rowid";
                if ($includebilltime) {
                    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$ganttproadvanced->table_task_time." as tt ON tt.".$ganttproadvanced->column_fk_task." = t.rowid";
                }
            }
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_extrafields as efpt ON (t.rowid = efpt.fk_object)";
            $sql .= " WHERE p.entity IN (".getEntity('project').")";
        } else {
            return 'BadValueForParameterMode';
        }

        if ($filteronprojuser > 0) {
            $sql .= " AND p.rowid = ec.element_id";
            $sql .= " AND ctc.rowid = ec.fk_c_type_contact";
            $sql .= " AND ctc.element = 'project'";
            $sql .= " AND ec.fk_socpeople = ".((int) $filteronprojuser);
            $sql .= " AND ec.statut = 4";
            $sql .= " AND ctc.source = 'internal'";
        }
        if ($filterontaskuser > 0) {
            $sql .= " AND t.fk_projet = p.rowid";
            $sql .= " AND p.rowid = ec2.element_id";
            $sql .= " AND ctc2.rowid = ec2.fk_c_type_contact";
            $sql .= " AND ctc2.element = 'project_task'";
            $sql .= " AND ec2.fk_socpeople = ".((int) $filterontaskuser);
            $sql .= " AND ec2.statut = 4";
            $sql .= " AND ctc2.source = 'internal'";
        }
        if ($socid) {
            $sql .= " AND p.fk_soc = ".((int) $socid);
        }
        if ($projectid) {
            $sql .= " AND p.rowid IN (".$db->sanitize($projectid).")";
        }
        if ($filteronproj) {
            $sql .= natural_search(array("p.ref", "p.title"), $filteronproj);
        }
        if ($filteronprojstatus && $filteronprojstatus != '-1') {
            $sql .= " AND p.fk_statut IN (".$db->sanitize($filteronprojstatus).")";
        }
        if ($morewherefilter) {
            $sql .= $morewherefilter;
        }

        $sql .= $newcondition;

        // echo $sql;
        dol_syslog(get_class($this)."::getTasksArray", LOG_DEBUG);
        $resql = $db->query($sql);

        $arrayoftasks = array();
        $arrayoftasks['all_tasks'] = array();
        $arrayoftasks['old_tasks'] = array();
        $arrayoftasks['parent_tasks'] = array();

        $tmptasks = array();

        $minus30days = date('Y-m-d', strtotime('-30 days'));

        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;
            // Loop on each record found, so each couple (project id, task id)
            while ($i < $num) {
                $error = 0;

                $obj = $db->fetch_object($resql);


                if ((!$obj->public) && (is_object($userp))) {   // If not public project and we ask a filter on project owned by a user
                    if (!$this->getUserRolesForProjectsOrTasks($userp, 0, $obj->projectid, 0)) {
                        $error++;
                    }
                }
                if (is_object($usert)) {                            // If we ask a filter on a user affected to a task
                    if (!$this->getUserRolesForProjectsOrTasks(0, $usert, $obj->projectid, $obj->taskid)) {
                        $error++;
                    }
                }

                if (!$error) {
                    // $arrayoftasks[$obj->taskid] = $obj->taskid;

                    if($obj->datee && $obj->datee < $minus30days) {
                        $arrayoftasks['old_tasks'][$obj->taskid] = $obj->taskid;
                    }

                    if($obj->fk_task_parent > 0) {
                        $arrayoftasks['parent_tasks'][$obj->fk_task_parent] = $obj->fk_task_parent;
                    }

                    $arrayoftasks['all_tasks'][$obj->taskid] = $obj->taskid;
                }


                $i++;
            }
            $db->free($resql);
        } else {
            dol_print_error($db);
        }

        return $arrayoftasks;
    }
}