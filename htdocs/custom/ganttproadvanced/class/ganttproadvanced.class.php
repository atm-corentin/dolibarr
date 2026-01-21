<?php 
// ini_set('display_startup_errors', 1);
// ini_set('display_errors', 1);
// error_reporting(-1);

dol_include_once('/core/lib/admin.lib.php');
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

class ganttproadvanced
{

    const STATUS_UNLASTSEARCH = 1;
    const STATUS_LASTSEARCH = 2;
    const STATUS_ALL = 3;

    const TYPE_NULL = 0;
    const TYPE_END_START = 1;
    const TYPE_END_END = 2;
    const TYPE_START_START = 3;
    const TYPE_START_END = 4;

    public $columnstoshow;

    public $table_task_time;
    public $column_fk_task;
    public $column_task_duration;
    public $use_abnovo_for_datejalon;

    public function __construct($db)
    {   
        global $langs, $conf, $user;

        $langsLoad=array('projects', 'users', 'companies', 'other', 'holiday');
        $langs->loadLangs($langsLoad);

        $this->db = $db;

        $this->trellotasksplus_enabled = (isset($conf->trellotasksplus) && $conf->trellotasksplus->enabled) ? 1 : 0;

        $this->columnstoshow = [
            // 'text'          => $langs->trans('Tasks'),
            // 'duration'      => $langs->trans('Duration').' ('.strtolower(substr($langs->trans("Day"),0,1)).')',
            'duration'      => $langs->trans('Duration').' ('.$langs->trans("Days").')',
            'durationhour'      => $langs->trans('Duration').' ('.$langs->trans("Hours").')',
            'start_date'    => $langs->trans('DateDebCP'),
            'end_date'      => $langs->trans('DateFinCP'),
            // 'end_date'      => $langs->trans('DateFinCP'),
            'progress'      => $langs->trans('Progress').' (%)',
            'dureeffectiv'  => $langs->trans('dureeffectiv'). ' (H:m)',
            'percentdure'   => $langs->trans('percentdure').' (%'.$langs->trans('Time').')',
        ];

        if($this->trellotasksplus_enabled) {
            $langs->load('trellotasksplus@trellotasksplus');
            $this->columnstoshow['trellotasksplus_status'] = $langs->trans('tagstrellotasksplus');
        }

        $this->typesrelation = [
            // $this::TYPE_NULL => $langs->trans('TYPE_NULL'),
            $this::TYPE_END_START => $langs->trans('TYPE_END_START'),
            $this::TYPE_END_END => $langs->trans('TYPE_END_END'),
            $this::TYPE_START_START => $langs->trans('TYPE_START_START'),
            $this::TYPE_START_END => $langs->trans('TYPE_START_END')
        ];
        
        $this->typesganttrelation = [
            $this::TYPE_END_START => 0,
            $this::TYPE_END_END => 2,
            $this::TYPE_START_START => 1,
            $this::TYPE_START_END => 3,
        ];


        $objtask = new Task($this->db);

        // $this->default_status = isset($conf->global->GANTTPROADVANCED_PROJECT_DEFAULTSTATUS) ? $conf->global->GANTTPROADVANCED_PROJECT_DEFAULTSTATUS : 99;
        $this->default_status = 99;
        $this->p_sortfield = isset($conf->global->GANTTPROADVANCED_PROJECT_SORTFIELD) ? $conf->global->GANTTPROADVANCED_PROJECT_SORTFIELD : 'p.ref';
        $this->p_sortorder = isset($conf->global->GANTTPROADVANCED_PROJECT_SORTORDER) ? $conf->global->GANTTPROADVANCED_PROJECT_SORTORDER : 'ASC';
        $this->t_colortaskbyuser = isset($conf->global->GANTTPROADVANCED_COLOR_TASK_BY_USER_FIELD_COLOR) ? $conf->global->GANTTPROADVANCED_COLOR_TASK_BY_USER_FIELD_COLOR : 0;
        $this->showprojectsevenwithoutdates = isset($conf->global->GANTTPROADVANCED_SHOW_PROJECTS_EVEN_WITHOUT_DATES) ? (int) $conf->global->GANTTPROADVANCED_SHOW_PROJECTS_EVEN_WITHOUT_DATES : 0;
        $this->viewingtasksbyresources = isset($conf->global->GANTTPROADVANCED_VIEWING_TASKS_BY_RESOURCES) ? (int) $conf->global->GANTTPROADVANCED_VIEWING_TASKS_BY_RESOURCES : 1;
        $this->t_typecontact = isset($conf->global->GANTTPROADVANCED_TYPE_CONTACT_TO_BASE_ON) ? (int) $conf->global->GANTTPROADVANCED_TYPE_CONTACT_TO_BASE_ON : $this->getdefaultTypeContact();
        $this->p_projectcolor = isset($conf->global->GANTTPROADVANCED_PROJECT_CHART_COLOR) ? '#'.$conf->global->GANTTPROADVANCED_PROJECT_CHART_COLOR : '#34495e';
        $this->t_showuserinchart = isset($conf->global->GANTTPROADVANCED_SHOW_USER_CHART_TASK) ? $conf->global->GANTTPROADVANCED_SHOW_USER_CHART_TASK : 0;
        $this->showweekend = isset($conf->global->GANTTPROADVANCED_SHOW_WEEKEND) ? $conf->global->GANTTPROADVANCED_SHOW_WEEKEND : 1;
        $this->excludeweekend = isset($conf->global->GANTTPROADVANCED_EXCLUDE_WEEKENDS_WHEN_CALCULATING_DURATION) ? $conf->global->GANTTPROADVANCED_EXCLUDE_WEEKENDS_WHEN_CALCULATING_DURATION : 0;
        $this->changecolortaskbyadmin = isset($conf->global->GANTTPROADVANCED_CHANGE_COLORTASK_BY_ADMIN) ? $conf->global->GANTTPROADVANCED_CHANGE_COLORTASK_BY_ADMIN : 0;
        $this->scroll_currentday = isset($conf->global->GANTTPROADVANCED_AUTOMATICALLY_SCROLL_To_CURRENT_DAY) ? $conf->global->GANTTPROADVANCED_AUTOMATICALLY_SCROLL_To_CURRENT_DAY : 0;
        $this->default_zoom = isset($conf->global->GANTTPROADVANCED_DEFAULT_ZOOM_BY) ? $conf->global->GANTTPROADVANCED_DEFAULT_ZOOM_BY : 'day';
        $this->refreshpageautomatically = isset($conf->global->GANTTPROADVANCED_REFRESH_PAGE_AUTOMATICALLY) ? (int) $conf->global->GANTTPROADVANCED_REFRESH_PAGE_AUTOMATICALLY : 0;

        $this->default_hide_leftmenu = isset($conf->global->GANTTPROADVANCED_PROJECT_HIDE_LEFTMENU) ? (int) $conf->global->GANTTPROADVANCED_PROJECT_HIDE_LEFTMENU : '';
        $this->default_empeche_add_subtask = isset($conf->global->GANTTPROADVANCED_EMPECHE_ADD_SUBTASK) ? (int) $conf->global->GANTTPROADVANCED_EMPECHE_ADD_SUBTASK : '';
        $this->show_marker_bar_only_at_top = isset($conf->global->GANTTPROADVANCED_SHOW_MARKER_BAR_ONLY_AT_TOP) ? (int) $conf->global->GANTTPROADVANCED_SHOW_MARKER_BAR_ONLY_AT_TOP : 0;
        $this->marker_bar_color = isset($conf->global->GANTTPROADVANCED_MARKER_BAR_COLOR) ? '#'.$conf->global->GANTTPROADVANCED_MARKER_BAR_COLOR : '';
        $this->hide_finished_task = isset($conf->global->GANTTPROADVANCED_HIDE_FINISHED_TASK) ? (int) $conf->global->GANTTPROADVANCED_HIDE_FINISHED_TASK : '';
        $this->select_one_project = 1;
        $this->bydefaultproject = isset($conf->global->GANTTPROADVANCED_SELECT_ONE_PROJECT) ? $conf->global->GANTTPROADVANCED_SELECT_ONE_PROJECT : 'one';
        $this->byressource_type_contacts = isset($conf->global->GANTTPROADVANCED_BYRESSOURCE_TYPE_CONTACTS) ? $conf->global->GANTTPROADVANCED_BYRESSOURCE_TYPE_CONTACTS : 'per_principal';
        $this->use_task_dependencies = isset($conf->global->GANTTPROADVANCED_USE_TASK_DEPENDENCIES) ? (int) $conf->global->GANTTPROADVANCED_USE_TASK_DEPENDENCIES : 1;
        $this->modify_parenttasks = isset($conf->global->GANTTPROADVANCED_MODIFY_PARENT_TASKS) ? (int) $conf->global->GANTTPROADVANCED_MODIFY_PARENT_TASKS : 0;
        $this->minmaxtasksdatetoproject = isset($conf->global->SET_MIN_MAX_TASKS_DATES_TO_PROJECT) ? (int) $conf->global->SET_MIN_MAX_TASKS_DATES_TO_PROJECT : 0;

        $this->max_period_task_month = isset($conf->global->GANTTPROADVANCED_PERIODTASK_IN_MONTH) ? (int) $conf->global->GANTTPROADVANCED_PERIODTASK_IN_MONTH : '';
        $this->excluded_project_tag = isset($conf->global->GANTTPROADVANCED_EXCLUDED_PROJECT_TAG) ? $conf->global->GANTTPROADVANCED_EXCLUDED_PROJECT_TAG : 0;
        $this->typefiltre = isset($conf->global->GANTTPROADVANCED_FILTRE_BY_TYPE) ? $conf->global->GANTTPROADVANCED_FILTRE_BY_TYPE : '';

        $this->gantt_grid_width = (isset($user->conf->GANTTPROADVANCED_GANTT_GRID_WIDTH) && $user->conf->GANTTPROADVANCED_GANTT_GRID_WIDTH > 0) ? $user->conf->GANTTPROADVANCED_GANTT_GRID_WIDTH : 0;

        $this->keep_recent_filter = isset($conf->global->GANTTPROADVANCED_KEEP_RECENT_FILTER) ? $conf->global->GANTTPROADVANCED_KEEP_RECENT_FILTER : 1;
        $this->viewhoursingantt = isset($conf->global->GANTTPROADVANCED_VIEW_HOURS_IN_GANTT) ? $conf->global->GANTTPROADVANCED_VIEW_HOURS_IN_GANTT : 0;

        $this->default_onglet = isset($conf->global->GANTTPROADVANCED_DEFAULT_ONGLET) ? $conf->global->GANTTPROADVANCED_DEFAULT_ONGLET : 'viewgantt';
        $this->showgridresourceintablivehours = isset($conf->global->GANTTPROADVANCED_SHOW_GRIDRESOURCE_IN_TAB_LIVEHOURS) ? $conf->global->GANTTPROADVANCED_SHOW_GRIDRESOURCE_IN_TAB_LIVEHOURS : '';


        $this->use_abnovo_datejalon = !empty($conf->abnovomodule->enabled) ? 1 : 0;

        $this->extrafieldstohide = array_flip(['ganttproadvancedcolor', 'ganttproadvancedtyperelation']);
        
        if(!empty($this->use_abnovo_datejalon)) {
            // $this->extrafieldstohide['ganttproadvanceddatejalon'] = count($this->extrafieldstohide);
        }

        $projectsids = [];

        if($this->excluded_project_tag) {
            $categstatic = new Categorie($this->db);
            $categstatic->id = $this->excluded_project_tag;
            $projectsids = $categstatic->getObjectsInCateg(Categorie::TYPE_PROJECT, 1);
            // if($tmpprojectids) { $projectsids = array_merge($projectsids, array_filter($tmpprojectids)); } // for multiple tags
        }

        $this->projects_excluded = ($projectsids && is_array($projectsids)) ? array_flip($projectsids) : [];
        // d($this->projects_excluded);

        $this->coloredbyuser = ($this->t_colortaskbyuser && $this->t_typecontact > 0) ? 1 : 0;

        $this->nametypecontact = $langs->trans("AffectedTo");

        $this->defaultcolortask = '#16a085';
        // $this->colorgristask = '#dcdcdc';
        $this->colorgristask = '#cbcbcb';

        $this->_data_affecteduser = '';
        $this->_data_typecontact = array();

        if($this->coloredbyuser) {
            $this->_data_affecteduser = $this->selectForaffecteduserection();
            $this->_data_typecontact = $objtask->liste_type_contact('internal', 'position', 0, 1);
            // d($_data_typecontact);
        }

        $this->typedate = array('datec' => $langs->trans('DateCreationShort'), 'dateo' => $langs->trans('DateStart'), 'datee' => $langs->trans('DateEnd'));
        
        $this->table_task_time = 'projet_task_time';
        $this->column_fk_task = 'fk_task';
        $this->column_task_duration = 'task_duration';
        $this->column_task_time_date = 'task_datehour';

        if(floatval(DOL_VERSION) >= 18) {
            $this->table_task_time      = 'element_time';
            $this->column_fk_task       = 'fk_element';
            $this->column_task_duration = 'element_duration';
            $this->column_task_time_date = 'element_datehour';
        }

        // Tag Trelltasksplus
        $this->trelloshowtaskinfirstcolomn = isset($conf->global->TRELLOTASKSPLUS_SHOW_TASKNOSTATUS_IN_FIRSTCOLOMN) ? (int) $conf->global->TRELLOTASKSPLUS_SHOW_TASKNOSTATUS_IN_FIRSTCOLOMN : 1;

        // Show Hours in gantt - Akku
        $this->showhoursingantt = isset($conf->global->GANTTPROADVANCED_SHOW_HOURS_IN_GANTT) ? (int) $conf->global->GANTTPROADVANCED_SHOW_HOURS_IN_GANTT : 0;
        $this->taskworkingtime = isset($conf->global->GANTTPROADVANCED_TASK_WORKING_TIME) ? $conf->global->GANTTPROADVANCED_TASK_WORKING_TIME : '09:00 - 17:00';

    }

    public function getdefaultTypeContact($code='TASKEXECUTIVE')
    {
        $sql = "SELECT tc.rowid";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc";
        $sql .= " WHERE tc.element='project_task'";
        $sql .= " AND tc.source='internal'";
        $sql .= " AND tc.code='".$code."'"; 

        //print $sql;
        $id_type_contact=0;
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $id_type_contact = $obj->rowid;
            }
        }

        return $id_type_contact;
    }

    public function typeDate($value='',$name='typedate', $showempty=1)
    {
        
        $select = Form::selectarray($name, $this->typedate, $value, $showempty);
        
        return $select;
    }
    
    public function SelectColumnsToShow($selected='')
    {
        global $langs, $conf, $form, $user;

        $selected_columns = array('duration','durationhour','start_date','end_date');

        if(isset($user->conf->GANTTPROADVANCED_GANTT_COLUMS_TO_SHOW)) {
            $selected_columns = json_decode($user->conf->GANTTPROADVANCED_GANTT_COLUMS_TO_SHOW);
        }

        // if(isset($conf->global->GANTTPROADVANCED_COLUMS_TO_SHOW)) {
        //     $default_columns = ($conf->global->GANTTPROADVANCED_COLUMS_TO_SHOW != -1) ? array('duration','start_date','end_date') : [];
        //     $selected_columns = $conf->global->GANTTPROADVANCED_COLUMS_TO_SHOW ? json_decode($conf->global->GANTTPROADVANCED_COLUMS_TO_SHOW) : $default_columns;
        // }

        $html = '<select class="selected_columns minwidth500imp " name="selected_columns[]" multiple onchange="HideShowColumns(this)">';
        // $html .= '<option value="text" disabled selected>'.$langs->trans('Tasks').'</option>';
        foreach ($this->columnstoshow as $key => $name) {
            
            if($key == 'durationhour' && empty($this->showhoursingantt)){
                continue;
            }
            $slctd = (is_array($selected_columns) && in_array($key, $selected_columns)) ? 'selected' : '';
            $html .= '<option value="'.$key.'" '.$slctd.'>'.$name.'</option>';
        }
        $html .= '</select>';
        
        return $html;
    }
    
    public function SelectFilterCategory($selected='')
    {
        global $langs, $form;
        
        $categoryArray = $form->select_all_categories(Categorie::TYPE_PROJECT, "", "", 64, 0, 1);

        $html = '<select class="search_category minwidth75imp maxwidth150" name="search_category" id="search_category">';
        $html .= '<option value="-1">&nbsp;&nbsp;</option>';

        if($categoryArray) {
            foreach ($categoryArray as $idcateg => $labelcateg) {
                $html .= '<option value="'.$idcateg.'"' ;
                if($selected == $idcateg) $html .= 'selected';
                $html .= '>';
                $html .= dol_trunc($labelcateg,100);
                $html .= '</option>';
                 $html.='<script>';
                $html.='$(document).ready(function(){';
                    $html.='$(".search_category").select2();';
                $html.='});';
                $html.='</script>';
            }
        }
        
        $html .= '</select>';
        
        return $html;
    }

    public function SelectFilterTagsTrello($value=[], $morecss = 'minwidth100')
    {
        global $langs, $conf, $form;

        $data = array();
        
        $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'trellotasksplus_columns';
        $sql .= ' WHERE entity='.$conf->entity;
        $resql = $this->db->query($sql);

        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $data[$obj->rowid] = $obj->label;
            }
        }
        return $form->multiselectarray('search_tagstrello', $data, $value, 0, 0, $morecss);
    }

    public function getTasksOfProjectPdf(&$sortedtasks, &$indextasks, &$inc, $parent, &$lines, &$level, $var, $showproject, $projectsListId = '', $addordertick = 0, $projectidfortotallink = 0, $filterprogresscalc = '', $showbilltime = 0, $arrayfields = array())
    {
        global $user, $langs, $conf, $db, $hookmanager;
        global $projectstatic, $taskstatic, $extrafields;

        $lastprojectid = 0;

        $projectsArrayId = explode(',', $projectsListId);
        // if ($filterprogresscalc !== '') {
        //     foreach ($lines as $key => $line) {
        //         if (!empty($line->planned_workload) && !empty($line->duration)) {
        //             $filterprogresscalc = str_replace(' = ', ' == ', $filterprogresscalc);
        //             if (!eval($filterprogresscalc)) {
        //                 unset($lines[$key]);
        //             }
        //         }
        //     }
        //     $lines = array_values($lines);
        // }
        $numlines = count($lines);

        $colorproject   = $this->p_projectcolor;
        $colordefaulttask = $this->coloredbyuser ? $this->colorgristask : $this->defaultcolortask;

        $format = GETPOST('format', 'alpha');

        $caradays = '';

        $version18 = (floatval(DOL_VERSION) >= 18) ? 1 : 0;

        for ($i = 0; $i < $numlines; $i++) {
            if ($parent == 0 && $level >= 0) {
                $level = 0; // if $level = -1, we dont' use sublevel recursion, we show all lines
            }

            $obj = $lines[$i];

            // fk_task_parent
            // d($obj,0);

            // if ((isset($obj->fk_parent) && $obj->fk_parent == $parent) || $level < 0) {       // if $level = -1, we dont' use sublevel recursion, we show all lines
            if ((isset($obj->fk_task_parent) && $obj->fk_task_parent == $parent) || $level < 0) {       // if $level = -1, we dont' use sublevel recursion, we show all lines
                // Show task line.
                $showline = 1;
                $showlineingray = 0;

                if ($showline) {
                    // Break on a new project
                    if ($parent == 0 && $obj->fk_project != $lastprojectid) {
                        $var = !$var;
                        $lastprojectid = $obj->fk_project;
                    }

                    $indextasks++;

                    $projectstatic->id = $obj->fk_project;
                    $projectstatic->ref = $obj->projectref;
                    $projectstatic->public = $obj->public;
                    $projectstatic->title = $obj->projectlabel;
                    $projectstatic->usage_bill_time = $obj->usage_bill_time;
                    $projectstatic->status = $obj->projectstatus;

                    $taskstatic->id = $obj->id;
                    $taskstatic->ref = $obj->ref;
                    $taskstatic->label = '';
                    $taskstatic->projectstatus = $obj->projectstatus;
                    $taskstatic->progress = $obj->progress;
                    $taskstatic->fk_statut = $obj->status;
                    $taskstatic->date_start = $obj->date_start;
                    $taskstatic->date_end = $obj->date_end;
                    $taskstatic->datee = $obj->date_end; // deprecated
                    $taskstatic->planned_workload = $obj->planned_workload;
                    $taskstatic->duration_effective = $version18 ? $obj->duration_effective : $obj->duration;
                    $taskstatic->budget_amount = $obj->budget_amount;


                    // --------------------------------------------------------------------------------------------------------------------------------------

                    $noformatstart = (int) ($obj->date_start ? ($obj->date_start) : ($projectstatic->date_start));
                    $noformatend = (int) ($obj->date_end ? ($obj->date_end) : $obj->date_start);
                    $dstart = $noformatstart; $dend = $noformatend;
                    $dend = ($dend ? $dend : $dstart);

                    
                    // $datediff = $noformatend - $noformatstart;
                    // $duration = round($datediff / (60 * 60 * 24));
                    // if($duration <= 1)  $Duration = '1'.$caradays;
                    // else  $Duration = $duration . $caradays;

                    $duration = $this->calculateWeekdaysWithOrWithoutWeekEnd($dstart, $dend);
                    $Duration = $duration . $caradays;

                    $nameinpdf = '';

                    $totcar = 45;
                    if($format == 'A3') $totcar = 54;

                    $label = dol_htmlentitiesbr_decode($obj->label ? $obj->label : $obj->ref);
                    if(strlen($label) > $totcar) $label = substr($label, 0, $totcar).'...';
                    // if($level == 0) $nameinpdf .= '-';
                    for ($k = 0; $k < $level; $k++) {
                        if($k > 4) break;
                        $nameinpdf .= '&nbsp;';
                    }
                    $nameinpdf .= dol_htmlentities($label);

                    $arr = array();
                    $arr['task_name_pdf'] = $nameinpdf;
                    $arr['task_name'] = $obj->ref.' - '.$obj->label;
                    $arr['task_start_date'] = $dstart;
                    $arr['task_end_date'] = $dend;
                    $arr['task_duration'] = $Duration;
                    $arr['task_percent'] = ($obj->progress ? number_format($obj->progress,0) : 0);
                    $arr['task_ref'] = $obj->ref;

                    $color = $colordefaulttask;
                    if($obj->array_options['options_ganttproadvancedcolor'] && $obj->array_options['options_ganttproadvancedcolor']) {
                        $color = $obj->array_options['options_ganttproadvancedcolor'];
                    }

                    if($this->coloredbyuser) {
                    }

                    $arr['task_color'] = $color;
                   
                    $sortedtasks[$indextasks] = $arr;
                    // --------------------------------------------------------------------------------------------------------------------------------------




                    if (!$showlineingray) {
                        $inc++;
                    }

                    if ($level >= 0) { // Call sublevels
                        $level++;
                        if ($obj->id) {
                            $this->getTasksOfProjectPdf($sortedtasks, $indextasks, $inc, $obj->id, $lines, $level, $var, $showproject, $projectsListId, $addordertick, $projectidfortotallink, $filterprogresscalc, $showbilltime, $arrayfields);
                        }
                        $level--;
                    }

                }
            } else {
                //$level--;
            }
        }


        return $sortedtasks;
    }

    // public function ganttproadvancedCheckProjectDates($task, $project)
    // {   
    //     global $conf, $user, $projectsobjs, $tasksobjs, $tasksdates, $projectdates, $alreadytasksids, $projectids;

    //     $updated = false;

    //     if($task->date_start && $project->date_start && $task->date_start < $project->date_start) {
    //         $project->date_start = $task->date_start;
    //         $updated = true;
    //     }

    //     elseif($task->date_end && $project->date_end && $task->date_end > $project->date_end) {
    //         $project->date_end = $task->date_end;
    //         $updated = true;
    //     }


    //     if($updated) {

    //         $projectids[$project->id] = $project;

    //         // $projectdates[$project->id]['start'] = $project->date_start;
    //         // $projectdates[$project->id]['end'] = $project->date_end;


    //         // $resp = $project->update($user);
    //         // if($resp > 0) {
    //             // $projectdates[$project->id]['start'] = dol_print_date($project->date_start, "%Y-%m-%d", 'tzserver');
    //             // $projectdates[$project->id]['end'] = dol_print_date($project->date_end, "%Y-%m-%d", 'tzserver');
    //         // }

    //     }


    //     return ($updated ? 1 : 0);
    // }

    public function selectMultipleTypeContact($object, $selected = '', $htmlname = 'type', $source = 'internal', $sortorder = 'position', $showempty = 0, $multiple = false)
    {
        global $user, $langs;

        $out = '';
        if (is_object($object) && method_exists($object, 'liste_type_contact')) {
            $lesTypes = $object->liste_type_contact($source, $sortorder, 1, 1);

            // $out .= '<select class="flat width150 maxwidth150 valignmiddle'.($morecss ? ' '.$morecss : '').'" name="'.$htmlname.'[]" id="'.$htmlname.'" multiple onchange="this.form.submit()">';


            $out .= '<select class="flat width100 maxwidth100 valignmiddle" name="'.$htmlname.''.($multiple ? '[]' : '').'" id="'.$htmlname.'" '. ($multiple ? 'multiple' : '') .'>';
            if ($showempty) {
                $out .= '<option value="0">&nbsp;</option>';
            }
            foreach ($lesTypes as $key => $value) {

                $out .= '<option value="'.$key.'"';

                if($multiple && in_array($key, $selected) || (!$multiple && $key == $selected)) {
                    $out .= 'selected';  
                } 

                $out .= '>'.$value.'</option>';

            }
            $out .= "</select>";

            if ($user->admin) {
                // $out .= ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
            }

            // $out .= ajax_combobox($htmlname);

            $out .= "\n";
        }
        if (empty($output)) {
            return $out;
        } else {
            print $out;
        }
    }

    public function selectstatus($name="search_status", $search_status='', $morecss='minwidth75imp maxwidth150 selectarrowonleft')
    {
        global $langs;

        $status = $search_status != '' ? $search_status : $this->default_status;

        $form = new Form($this->db);
        $projectstatic = new Project($this->db);

        $arrayofstatus = array();
        $arrayofstatus['99'] = $langs->trans("NotClosed").' ('.$langs->trans('Draft').' + '.$langs->trans('Opened').')';
        if(!empty($projectstatic->statuts_short)){
            foreach ($projectstatic->statuts_short as $key => $val) {
                $arrayofstatus[$key] = $langs->trans($val);
            }
        }
        
        $arrayofstatus[Project::STATUS_CLOSED] = $langs->trans("Closed");
        $selectstatus = $form->selectarray($name, $arrayofstatus, $status, 0, 0, 0, '', 0, 0, 0, '', $morecss);

        return $selectstatus;
    }
    
    public function selectForaffecteduserection($mode="")
    {
        global $conf, $user, $langs;

        $_data_affecteduser = '';

        $sql = "SELECT DISTINCT u.rowid, u.lastname as lastname, u.firstname, u.statut as status, u.login, u.admin, u.entity, u.photo";
        $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
        if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entity as e ON e.rowid = u.entity";
            
            $sql .= " WHERE u.entity IS NOT NULL";
        } else {
            if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
                $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ug";
                $sql .= " ON ug.fk_user = u.rowid";
                $sql .= " WHERE ug.entity = ".$conf->entity;
            } else {
                $sql .= " WHERE u.entity IN (0, ".$conf->entity.")";
            }
        }
        if (!empty($user->socid)) {
            $sql .= " AND u.fk_soc = ".((int) $user->socid);
        }

        $sql .= " AND u.statut <> 0";
        $resql = $this->db->query($sql);
        if ($resql)
        {
            while ($obj = $this->db->fetch_object($resql))
            {
                $data = array();
                $data['key'] = $obj->rowid;
                $data['label'] = $obj->lastname.' '.$obj->firstname;

                if($mode)
                    $_data_affecteduser .= '<option value="'.$obj->rowid.'">'.$obj->lastname.' '.$obj->firstname.'</option>';
                else
                    $_data_affecteduser  .= json_encode($data).',';
            }
        }

        return $_data_affecteduser;
    }
    
    public function ganttproadvancedselectUsersThatSignedAsTasksContacts($sql_proj = '', $sql_tasktypes = '', $search_affecteduser = array())
    {
        global $conf, $langs, $form;


        $returned = array();
        $arr_users = array();

        $sql = 'SELECT DISTINCT(elem.fk_socpeople) ';
        $sql .= ', u.rowid, u.lastname as lastname, u.firstname, u.statut as status, u.login, u.admin, u.entity, u.photo';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'element_contact as elem';
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON (u.rowid = elem.fk_socpeople) ";
        $sql .= ' WHERE fk_c_type_contact IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'c_type_contact WHERE element = "project_task"';
            if($sql_tasktypes && $sql_tasktypes != '""') {
                $sql .= '  AND code IN ('.$sql_tasktypes.')';
            }
        $sql .= ')';
        
        if($sql_proj) {
            $sql .= ' AND element_id IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'projet_task WHERE fk_projet IN ('.$sql_proj.'))';
        }
        // if($sql_tasktypes) {
        //     // $sql .= ' AND fk_c_type_contact IN ('.$sql_tasktypes.')';
        // }

        // echo $sql;

        $sql .= " AND u.statut <> 0";

        $resql = $this->db->query($sql);

        $userstatic = new User($this->db);
        $fullNameMode = 0;
        if (empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)) {
            $fullNameMode = 1; //Firstname+lastname
        }

        // $html = '<select class="select_affecteduser minwidth75imp width300 maxwidth300" name="search_affecteduser[]" multiple onchange="this.form.submit()">';
        $html = '<select class="select_affecteduser minwidth75imp width150 maxwidth150" id="search_affecteduser" name="search_affecteduser[]" multiple onchange="ganttproadvanced_SubmitFormOnChange()">';
        if ($resql)
        {
            $i=0;
            while ($obj = $this->db->fetch_object($resql))
            {
                $arr_users[$obj->fk_socpeople] = $obj->fk_socpeople;

                $userstatic->id = $obj->rowid;
                $userstatic->lastname = $obj->lastname;
                $userstatic->firstname = $obj->firstname;
                $userstatic->photo = $obj->photo;
                $userstatic->statut = $obj->status;
                $userstatic->entity = $obj->entity;
                $userstatic->admin = $obj->admin;

                $html .= '<option value="'.$obj->fk_socpeople.'"' ;
                if(in_array($obj->fk_socpeople, $search_affecteduser)) $html .= 'selected';
                $html .= '>';

                $html .= $userstatic->getFullName($langs, $fullNameMode, -1, $maxlength = 0);
                if (empty($obj->firstname) && empty($obj->lastname)) {
                    $html .= $obj->login;
                }

                $html .= '</option>';
                $i++;
            }
        }
        $html .= '</select>';

        // ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        // if (!is_object($form)) {
        //     require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
        //     $form = new Form($this->db);
        // }

        // $msgcheckbox = $form->textwithpicto('', $langs->trans('CheckThisToSearchByAllContactType'), 1, 'info');

        // $checked = $searchallcontact ? 'checked' : '';
        // $srchcontacts = '<label class="marginleftonly" for="searchallcontact"><input type="checkbox" name="searchallcontact" id="searchallcontact" '.$checked.' value="1">'.$msgcheckbox.'</label>';
        // $srchcontacts .= "
        // <script>
        // $(document).ready(function() {
        //     $('#searchallcontact').on('change', function() {
        //         $('.select_affecteduser').val(0);
        //         $('.ganttproadvancedformindex').submit();
        //     });
        // });
        // </script>
        // ";

        // $html .= $srchcontacts;
        // ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------

        $returned['html'] = $html;
        $returned['array'] = $arr_users;

        // d($arr_users);
        return $returned;
    }
  
    public function ganttproadvancedSelectProjectsAuthorized($selected = array(), $search_category = 0, $search_status = 0,$search_userid=0, $search_customer=0, $return_only_one = false, $showall=0, $start="", $end="")
    {
        global $langs, $user, $conf, $selectallornone, $projectstoselectafterrefresh;

        $project = new Project($this->db);

        $sortfield = isset($conf->global->GANTTPROADVANCED_PROJECT_SORTFIELD) ? $conf->global->GANTTPROADVANCED_PROJECT_SORTFIELD : 'p.ref';
        $sortorder = isset($conf->global->GANTTPROADVANCED_PROJECT_SORTORDER) ? $conf->global->GANTTPROADVANCED_PROJECT_SORTORDER : 'ASC';

        $sql = "SELECT DISTINCT p.rowid, p.ref, p.title";
        $sql .= ', (SELECT COUNT(pt.rowid) FROM '.MAIN_DB_PREFIX.'projet_task as pt WHERE pt.fk_projet = p.rowid) as nmbroftasks ';

        $sql .= ' FROM '.MAIN_DB_PREFIX.'projet as p';

        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_project as cp ON cp.fk_project = p.rowid ';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'element_contact as c ON (p.rowid = c.element_id)';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as t ON (c.fk_c_type_contact = t.rowid)';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task as ts ON p.rowid = ts.fk_projet';

        $sql .= " WHERE";
        $sql .= " p.entity IN (".getEntity('project').")";


        if (!isset($user->rights->projet->all->lire)) {
            $projectsListId = $project->getProjectsAuthorizedForUser($user, 0, 1);
            $sql .= " AND p.rowid IN (".$projectsListId.")";
        }
        // $sql .= ' AND p.fk_statut != '.Project::STATUS_CLOSED;

        if($search_status != 100){
            if ($search_status == 99) {
                $sql .= " AND p.fk_statut <> 2";
            } else {
                $sql .= " AND p.fk_statut = ".((int) $search_status);
            }
        }

        if($search_category > 0){
            $sql .= ' AND cp.fk_categorie = '.$search_category;
        }

        if($search_userid > 0){
            $sql .= ' AND t.code ="PROJECTLEADER" AND t.source = "internal" AND t.element ="project" AND c.fk_socpeople ='.$search_userid;
        }

        if($search_customer > 0){
            $sql .= ' AND p.fk_soc ='.$search_customer;
        }

        // $sql .= ' AND p.dateo IS NOT NULL AND p.datee IS NOT NULL';

        if($start && $end){
            $sql .= ' AND (';
            $sql .= ' (CAST(p.dateo as date) BETWEEN "'.$this->db->idate($start).'" AND "'.$this->db->idate($end).'")';
            $sql .= ' OR ';
            $sql .= ' (CAST(p.datee as date) BETWEEN "'.$this->db->idate($start).'" AND "'.$this->db->idate($end).'")';

            if($this->showprojectsevenwithoutdates) {
                $sql .= ' OR (p.datee is NULL AND p.dateo is NULL)';
            }
            $sql .= ' OR ';
            $sql .= ' (CAST(ts.dateo as date) BETWEEN "'.$this->db->idate($start).'" AND "'.$this->db->idate($end).'")';
            $sql .= ' OR ';
            $sql .= ' (CAST(ts.datee as date) BETWEEN "'.$this->db->idate($start).'" AND "'.$this->db->idate($end).'")';
            $sql .= ' OR ';
            $sql .= ' (CAST(ts.dateo as date) < "'.$this->db->idate($start).'" AND CAST(ts.datee as date) > "'.$this->db->idate($end).'")';

            $sql .= ')';

        }


        $sortfield .= ', rowid';

        $sql .= $this->db->order($sortfield, $sortorder);

        if($return_only_one && !$showall) {
            $sql .= ' LIMIT 1 ';
        }
        // echo $sql;

        $txttasks = $langs->trans('Tasks');

        $returns = array();

        $html = '';
        $totproject = 0;
        $totslctdproject = 0;

        $resql = $this->db->query($sql);
        // $select = '<select class="select_proj_visibl minwidth75imp width300 maxwidth300" name="search_projects[]" multiple onchange="this.form.submit()">';
        $select = '<select class="select_proj_visibl minwidth75imp width300 maxwidth300" name="search_projects[]" multiple onchange="ganttproadvanced_refreshfilter()">';
        // $select .= '<option value="-1">'.$langs->Trans("All").'</option>';
        if ($resql)
        {
            while ($obj = $this->db->fetch_object($resql))
            {
                if($return_only_one) { 
                    if($showall){
                        $returns[$obj->rowid]=$obj->ref; 
                    }else{
                        $returns = array($obj->rowid => $obj->ref); 
                        break;
                    }
                }

                $select .= '<option value="'.$obj->rowid.'"' ;

                $optionselected = '';

                if($selectallornone) {
                    if($selectallornone == 1)
                        $optionselected = 'selected'; // 2 : None
                } else {
                    if(!is_array($selected) && $selected == $obj->rowid) $optionselected = 'selected';
                    elseif(is_array($selected) && in_array($obj->rowid, $selected)) $optionselected = 'selected';
                }

                $select .= $optionselected;

                $select .= '>';
                $select .= $obj->ref;
                if($obj->ref && $obj->title)
                    $select .= ' - ';
                // $select .= $obj->title;
                $select .= dol_trunc($obj->title,100);

                // if($obj->nmbroftasks) {
                    $select .= ' ('. $obj->nmbroftasks. ' '.$txttasks.')';
                // }

                $select .= '</option>';

                $totproject++;
                if($optionselected) {
                    $totslctdproject++;
                    $projectstoselectafterrefresh[$obj->rowid] = $obj->rowid;
                }
            }
        }
        $select .= '</select>';

        // if($totproject >= 0) {
            $html .= '<span class="small" title="'.$langs->trans('Projects').'">';
                $html .= $totslctdproject;
                $html .= '/';
                $html .= '<span class="opacitymedium">';
                $html .= $totproject;
                $html .= '</span>';
            $html .= '</span>';
        // }
        
        $html .= $select;
        

        if($return_only_one) { 
            return $returns;
        }

        return $html;
    }


    public function selectDurationGanttpro($prefix, $iSecond = '', $disabled = 0, $typehour = 'select', $minunderhours = 0, $nooutput = 0)
    {
        // phpcs:enable
        global $langs;

        $retstring = '<span class="nowraponall">';

        $hourSelected = 0;
        $minSelected = 0;

        // Hours
        if ($iSecond != '') {
            require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

            $hourSelected = convertSecondToTime($iSecond, 'allhour');
            $minSelected = convertSecondToTime($iSecond, 'min');
        }

        if ($typehour == 'select') {
            $retstring .= '<select class="flat '.$prefix.' '.$prefix.'hour" id="select_'.$prefix.'hour" name="'.$prefix.'hour"'.($disabled ? ' disabled' : '').'>';
            for ($hour = 0; $hour < 25; $hour++) {  // For a duration, we allow 24 hours
                $retstring .= '<option value="'.$hour.'"';
                if ($hourSelected == $hour) {
                    $retstring .= " selected";
                }
                $retstring .= ">".$hour."</option>";
            }
            $retstring .= "</select>";
        } elseif ($typehour == 'text' || $typehour == 'textselect') {
            $retstring .= '<input placeholder="'.$langs->trans('HourShort').'" type="number" min="0" name="'.$prefix.'hour"'.($disabled ? ' disabled' : '').' class="flat maxwidth50 inputhour" value="'.(($hourSelected != '') ? ((int) $hourSelected) : '').'">';
        } else {
            return 'BadValueForParameterTypeHour';
        }

        if ($typehour != 'text') {
            $retstring .= ' '.$langs->trans('HourShort');
        } else {
            $retstring .= '<span class="">:</span>';
        }

        // Minutes
        if ($minunderhours) {
            $retstring .= '<br>';
        } else {
            $retstring .= '<span class="hideonsmartphone">&nbsp;</span>';
        }

        if ($typehour == 'select' || $typehour == 'textselect') {
            $retstring .= '<select class="flat '.$prefix.'min" id="select_'.$prefix.'min" name="'.$prefix.'min"'.($disabled ? ' disabled' : '').'>';
            for ($min = 0; $min <= 55; $min = $min + 5) {
                $retstring .= '<option value="'.$min.'"';
                if ($minSelected == $min) {
                    $retstring .= ' selected';
                }
                $retstring .= '>'.$min.'</option>';
            }
            $retstring .= "</select>";
        } elseif ($typehour == 'text') {
            $retstring .= '<input placeholder="'.$langs->trans('MinuteShort').'" type="number" min="0" name="'.$prefix.'min"'.($disabled ? ' disabled' : '').' class="flat maxwidth50 inputminute " value="'.(($minSelected != '') ? ((int) $minSelected) : '').'">';
        }

        if ($typehour != 'text') {
            $retstring .= ' '.$langs->trans('MinuteShort');
        }

        $retstring.="</span>";

        if (!empty($nooutput)) {
            return $retstring;
        }

        return $retstring;
    }

    public function selectPercentGanttpro($selected = 0, $htmlname = 'percent', $disabled = 0, $increment = 5, $start = 0, $end = 100, $showempty = 0)
    {
        // phpcs:enable
        $return = '<select class="flat '.$htmlname.'" name="'.$htmlname.'" '.($disabled ? 'disabled' : '').'>';
        if ($showempty) { 
            $return .= '<option value="-1"'.(($selected == -1 || $selected == '') ? ' selected' : '').'>&nbsp;</option>';
        }
        for ($i = $start; $i <= $end; $i += $increment) {
            if ($selected != '' && (int) $selected == $i) {
                $return .= '<option value="'.$i.'" selected>';
            } else {
                $return .= '<option value="'.$i.'">';
            }
            $return .= $i.' % ';
            $return .= '</option>';
        }
        $return .= '</select>';

        return $return;
    }

    public function totalPlannedWorkload($id)
    {
        $error = 0;
        $total = 0;

        $sql = "SELECT rowid, planned_workload";
        $sql .= " FROM ".MAIN_DB_PREFIX."projet_task";
        $sql .= " WHERE fk_task_parent = ".((int) $id);

        $resql = $this->db->query($sql);

        if (!$resql) {
            $error++; $this->errors[] = "Error ".$this->db->lasterror();
        } else {
            while ($obj = $this->db->fetch_object($resql)){
                if ($obj) {
                    $total += $obj->planned_workload;
                    $sous_total = $this->totalPlannedWorkload($obj->rowid);
                    $total += $sous_total;
                }
            }
            $this->db->free($resql);
        }

        if (!$error) {
            return $total;
        } else {
            return 0;
        }
    }

    public function totalProgress($id)
    {
        $error = 0;
        $ret = 0;

        $sql = "SELECT rowid, progress";
        $sql .= " FROM ".MAIN_DB_PREFIX."projet_task";
        $sql .= " WHERE fk_task_parent = ".((int) $id);

        $resql = $this->db->query($sql);
        $j=0;
        $total=0;
        if (!$resql) {
            $error++; $this->errors[] = "Error ".$this->db->lasterror();
        } else {
            while ($obj = $this->db->fetch_object($resql)){
                if ($obj) {
                    $j++;
                    $total += $obj->progress;
                    $totalProgress = $this->totalProgress($obj->rowid);
                    $sous_total = $totalProgress['total'];
                    $j+=$totalProgress['nb'];
                    $total += $sous_total;
                }
            }
            $this->db->free($resql);
        }

        if (!$error) {
            return ['total'=>$total, 'nb'=>$j];
        } else {
            return 0;
        }
    }


    public function showInputFieldGanttpro($extrafields, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '', $objectid = 0, $extrafieldsobjectkey = '', $showempty=1)
    {
        global $conf, $langs, $form;

        $mode=0;

        if (!is_object($form)) {
            require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
            $form = new Form($this->db);
        }

        $out = '';

        if (!preg_match('/options_$/', $keyprefix)) {   // Because we work on extrafields, we add 'options_' to prefix if not already added
            $keyprefix = $keyprefix.'options_';
        }

        if (!empty($extrafieldsobjectkey)) {
            $label = $extrafields->attributes[$extrafieldsobjectkey]['label'][$key];
            $type = $extrafields->attributes[$extrafieldsobjectkey]['type'][$key];
            $size = $extrafields->attributes[$extrafieldsobjectkey]['size'][$key];
            $default = $extrafields->attributes[$extrafieldsobjectkey]['default'][$key];
            $computed = $extrafields->attributes[$extrafieldsobjectkey]['computed'][$key];
            $unique = $extrafields->attributes[$extrafieldsobjectkey]['unique'][$key];
            $required = $extrafields->attributes[$extrafieldsobjectkey]['required'][$key];
            $param = $extrafields->attributes[$extrafieldsobjectkey]['param'][$key];
            $perms = dol_eval($extrafields->attributes[$extrafieldsobjectkey]['perms'][$key], 1, 1, '1');
            $langfile = $extrafields->attributes[$extrafieldsobjectkey]['langfile'][$key];
            $list = dol_eval($extrafields->attributes[$extrafieldsobjectkey]['list'][$key], 1, 1, '1');
            $totalizable = $extrafields->attributes[$extrafieldsobjectkey]['totalizable'][$key];
            $help = $extrafields->attributes[$extrafieldsobjectkey]['help'][$key];
            $hidden = (empty($list) ? 1 : 0); // If empty, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, extrafields must be filtered by caller)
        } else {
            // Old usage
            $label = $extrafields->attribute_label[$key];
            $type = $extrafields->attribute_type[$key];
            $list = $extrafields->attribute_list[$key];
            $hidden = (empty($list) ? 1 : 0); // If empty, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)
        }

        if ($computed) {
            if (!preg_match('/^search_/', $keyprefix)) {
                return '<span class="opacitymedium">'.$langs->trans("AutomaticallyCalculated").'</span>';
            } else {
                return '';
            }
        }

        if (empty($morecss)) {
            if ($type == 'date') {
                $morecss = 'minwidth100imp';
            } elseif ($type == 'datetime' || $type == 'link') {
                $morecss = 'minwidth200imp';
            } elseif (in_array($type, array('int', 'integer', 'double', 'price'))) {
                $morecss = 'maxwidth75';
            } elseif ($type == 'password') {
                $morecss = 'maxwidth100';
            } elseif ($type == 'url') {
                $morecss = 'minwidth400';
            } elseif ($type == 'boolean') {
                $morecss = '';
            } elseif ($type == 'radio') {
                $morecss = 'width25';
            } else {
                if (empty($size) || round($size) < 12) {
                    $morecss = 'minwidth100';
                } elseif (round($size) <= 48) {
                    $morecss = 'minwidth200';
                } else {
                    $morecss = 'minwidth400';
                }
            }
        }
        if ($type == 'select') {
            $out = '';
                $out .= '<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '').'>';
                if($showempty)
                $out .= '<option value="0">&nbsp;</option>';
                foreach ($param['options'] as $key => $val) {
                    if ((string) $key == '') {
                        continue;
                    }
                    $valarray = explode('|', $val);
                    $val = $valarray[0];
                    $parent = '';
                    if (!empty($valarray[1])) {
                        $parent = $valarray[1];
                    }
                    $out .= '<option value="'.$key.'"';
                    $out .= (((string) $value == (string) $key) ? ' selected' : '');
                    $out .= (!empty($parent) ? ' parent="'.$parent.'"' : '');
                    $out .= '>';
                    if ($langfile && $val) {
                        $out .= $langs->trans($val);
                    } else {
                        $out .= $val;
                    }
                    $out .= '</option>';
                }
            $out .= '</select>';
        } elseif ($type == 'sellist') {
            $out = '';
            // if (!empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_EXTRAFIELDS_DISABLE_SELECT2)) {
            //  include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
            //  $out .= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
            // }

            $out .= '<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '').'>';
            if (is_array($param['options'])) {
                $tmpparamoptions = array_keys($param['options']);
                $paramoptions = preg_split('/[\r\n]+/', $tmpparamoptions[0]);

                $InfoFieldList = explode(":", $paramoptions[0], 5);
                // 0 : tableName
                // 1 : label field name
                // 2 : key fields name (if different of rowid)
                // optional parameters...
                // 3 : key field parent (for dependent lists). How this is used ?
                // 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value. Or use USF on the second line.
                // 5 : string category type. This replace the filter.
                // 6 : ids categories list separated by comma for category root. This replace the filter.
                // 7 : sort field (not used here but used into format for commobject)

                // If there is a filter, we extract it by taking all content inside parenthesis.
                if (! empty($InfoFieldList[4])) {
                    $pos = 0;   // $pos will be position of ending filter
                    $parenthesisopen = 0;
                    while (substr($InfoFieldList[4], $pos, 1) !== '' && ($parenthesisopen || $pos == 0 || substr($InfoFieldList[4], $pos, 1) != ':')) {
                        if (substr($InfoFieldList[4], $pos, 1) == '(') {
                            $parenthesisopen++;
                        }
                        if (substr($InfoFieldList[4], $pos, 1) == ')') {
                            $parenthesisopen--;
                        }
                        $pos++;
                    }
                    $tmpbefore = substr($InfoFieldList[4], 0, $pos);
                    $tmpafter = substr($InfoFieldList[4], $pos + 1);
                    //var_dump($InfoFieldList[4].' -> '.$pos); var_dump($tmpafter);
                    $InfoFieldList[4] = $tmpbefore;
                    if ($tmpafter !== '') {
                        $InfoFieldList = array_merge($InfoFieldList, explode(':', $tmpafter));
                    }

                    // Fix better compatibility with some old extrafield syntax filter "(field=123)"
                    $reg = array();
                    if (preg_match('/^\(?([a-z0-9]+)([=<>]+)(\d+)\)?$/i', $InfoFieldList[4], $reg)) {
                        $InfoFieldList[4] = '('.$reg[1].':'.$reg[2].':'.$reg[3].')';
                    }

                    //var_dump($InfoFieldList);
                }

                //$Usf = empty($paramoptions[1]) ? '' :$paramoptions[1];

                $parentName = '';
                $parentField = '';
                $keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2].' as rowid');

                if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
                    list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
                    $keyList .= ', '.$parentField;
                }
                if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
                    if (strpos($InfoFieldList[4], 'extra.') !== false) {
                        $keyList = 'main.'.$InfoFieldList[2].' as rowid';
                    } else {
                        $keyList = $InfoFieldList[2].' as rowid';
                    }
                }

                $filter_categorie = false;
                if (count($InfoFieldList) > 5) {
                    if ($InfoFieldList[0] == 'categorie') {
                        $filter_categorie = true;
                    }
                }

                if ($filter_categorie === false) {
                    $fields_label = explode('|', $InfoFieldList[1]);
                    if (is_array($fields_label)) {
                        $keyList .= ', ';
                        $keyList .= implode(', ', $fields_label);
                    }

                    $sqlwhere = '';
                    $sql = "SELECT ".$keyList;
                    $sql .= ' FROM '.$this->db->prefix().$InfoFieldList[0];

                    // Add filter from 4th field
                    if (!empty($InfoFieldList[4])) {
                        // can use current entity filter
                        if (strpos($InfoFieldList[4], '$ENTITY$') !== false) {
                            $InfoFieldList[4] = str_replace('$ENTITY$', (string) $conf->entity, $InfoFieldList[4]);
                        }
                        // can use SELECT request
                        if (strpos($InfoFieldList[4], '$SEL$') !== false) {
                            $InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
                        }

                        // current object id can be use into filter
                        if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
                            $InfoFieldList[4] = str_replace('$ID$', (string) $objectid, $InfoFieldList[4]);
                        } else {
                            $InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
                        }

                        // We have to join on extrafield table
                        $errstr = '';
                        if (strpos($InfoFieldList[4], 'extra.') !== false) {
                            $sql .= ' as main, '.$this->db->sanitize($this->db->prefix().$InfoFieldList[0]).'_extrafields as extra';
                            $sqlwhere .= " WHERE extra.fk_object = main.".$this->db->sanitize($InfoFieldList[2]);
                            $sqlwhere .= " AND " . forgeSQLFromUniversalSearchCriteria($InfoFieldList[4], $errstr, 1);
                        } else {
                            $sqlwhere .= " WHERE " . forgeSQLFromUniversalSearchCriteria($InfoFieldList[4], $errstr, 1);
                        }
                    } else {
                        $sqlwhere .= ' WHERE 1=1';
                    }

                    // Add Usf filter on second line
                    /*
                     if ($Usf) {
                     $errorstr = '';
                     $sqlusf .= forgeSQLFromUniversalSearchCriteria($Usf, $errorstr);
                     if (!$errorstr) {
                     $sqlwhere .= $sqlusf;
                     } else {
                     $sqlwhere .= " AND invalid_usf_filter_of_extrafield";
                     }
                     }
                     */

                    // Some tables may have field, some other not. For the moment we disable it.
                    if (in_array($InfoFieldList[0], array('tablewithentity'))) {
                        $sqlwhere .= ' AND entity = '.((int) $conf->entity);
                    }
                    $sql .= $sqlwhere;
                    //print $sql;

                    $sql .= ' ORDER BY '.implode(', ', $fields_label);

                    dol_syslog(get_class($this).'::showInputField type=sellist', LOG_DEBUG);
                    $resql = $this->db->query($sql);
                    if ($resql) {
                        $out .= '<option value="0">&nbsp;</option>';
                        $num = $this->db->num_rows($resql);
                        $i = 0;
                        while ($i < $num) {
                            $labeltoshow = '';
                            $obj = $this->db->fetch_object($resql);

                            // Several field into label (eq table:code|label:rowid)
                            $notrans = false;
                            $fields_label = explode('|', $InfoFieldList[1]);
                            if (is_array($fields_label) && count($fields_label) > 1) {
                                $notrans = true;
                                foreach ($fields_label as $field_toshow) {
                                    $labeltoshow .= $obj->$field_toshow.' ';
                                }
                            } else {
                                $labeltoshow = $obj->{$InfoFieldList[1]};
                            }

                            if ($value == $obj->rowid) {
                                if (!$notrans) {
                                    foreach ($fields_label as $field_toshow) {
                                        $translabel = $langs->trans($obj->$field_toshow);
                                        $labeltoshow = $translabel.' ';
                                    }
                                }
                                $out .= '<option value="'.$obj->rowid.'" selected>'.$labeltoshow.'</option>';
                            } else {
                                if (!$notrans) {
                                    $translabel = $langs->trans($obj->{$InfoFieldList[1]});
                                    $labeltoshow = $translabel;
                                }
                                if (empty($labeltoshow)) {
                                    $labeltoshow = '(not defined)';
                                }

                                if (!empty($InfoFieldList[3]) && $parentField) {
                                    $parent = $parentName.':'.$obj->{$parentField};
                                }

                                $out .= '<option value="'.$obj->rowid.'"';
                                $out .= ($value == $obj->rowid ? ' selected' : '');
                                $out .= (!empty($parent) ? ' parent="'.$parent.'"' : '');
                                $out .= '>'.$labeltoshow.'</option>';
                            }

                            $i++;
                        }
                        $this->db->free($resql);
                    } else {
                        print 'Error in request '.$sql.' '.$this->db->lasterror().'. Check setup of extra parameters.<br>';
                    }
                } else {
                    require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
                    $data = $form->select_all_categories(Categorie::$MAP_ID_TO_CODE[$InfoFieldList[5]], '', 'parent', 64, $InfoFieldList[6], 1, 1);
                    $out .= '<option value="0">&nbsp;</option>';
                    if (is_array($data)) {
                        foreach ($data as $data_key => $data_value) {
                            $out .= '<option value="'.$data_key.'"';
                            $out .= ($value == $data_key ? ' selected' : '');
                            $out .= '>'.$data_value.'</option>';
                        }
                    }
                }
            }
            $out .= '</select>';
        }
        return $out;
    }


    public function showInputField($key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '', $objectid = 0, $extrafieldsobjectkey = '', $mode = 0)
    {
        global $conf, $langs, $form;

        if (!is_object($form)) {
            require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
            $form = new Form($this->db);
        }

        $out = '';

        if (!preg_match('/options_$/', $keyprefix)) {   // Because we work on extrafields, we add 'options_' to prefix if not already added
            $keyprefix = $keyprefix.'options_';
        }

        if (!empty($extrafieldsobjectkey)) {
            $label = $this->attributes[$extrafieldsobjectkey]['label'][$key];
            $type = $this->attributes[$extrafieldsobjectkey]['type'][$key];
            $size = $this->attributes[$extrafieldsobjectkey]['size'][$key];
            $default = $this->attributes[$extrafieldsobjectkey]['default'][$key];
            $computed = $this->attributes[$extrafieldsobjectkey]['computed'][$key];
            $unique = $this->attributes[$extrafieldsobjectkey]['unique'][$key];
            $required = $this->attributes[$extrafieldsobjectkey]['required'][$key];
            $param = $this->attributes[$extrafieldsobjectkey]['param'][$key];
            $perms = dol_eval($this->attributes[$extrafieldsobjectkey]['perms'][$key], 1, 1, '1');
            $langfile = $this->attributes[$extrafieldsobjectkey]['langfile'][$key];
            $list = dol_eval($this->attributes[$extrafieldsobjectkey]['list'][$key], 1, 1, '1');
            $totalizable = $this->attributes[$extrafieldsobjectkey]['totalizable'][$key];
            $help = $this->attributes[$extrafieldsobjectkey]['help'][$key];
            $hidden = (empty($list) ? 1 : 0); // If empty, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)
        } else {
            // Old usage
            $label = $this->attribute_label[$key];
            $type = $this->attribute_type[$key];
            $list = $this->attribute_list[$key];
            $hidden = (empty($list) ? 1 : 0); // If empty, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)
        }

        if ($computed) {
            if (!preg_match('/^search_/', $keyprefix)) {
                return '<span class="opacitymedium">'.$langs->trans("AutomaticallyCalculated").'</span>';
            } else {
                return '';
            }
        }

        if (empty($morecss)) {
            if ($type == 'date') {
                $morecss = 'minwidth100imp';
            } elseif ($type == 'datetime' || $type == 'link') {
                $morecss = 'minwidth200imp';
            } elseif (in_array($type, array('int', 'integer', 'double', 'price'))) {
                $morecss = 'maxwidth75';
            } elseif ($type == 'password') {
                $morecss = 'maxwidth100';
            } elseif ($type == 'url') {
                $morecss = 'minwidth400';
            } elseif ($type == 'boolean') {
                $morecss = '';
            } elseif ($type == 'radio') {
                $morecss = 'width25';
            } else {
                if (empty($size) || round($size) < 12) {
                    $morecss = 'minwidth100';
                } elseif (round($size) <= 48) {
                    $morecss = 'minwidth200';
                } else {
                    $morecss = 'minwidth400';
                }
            }
        }

        if (in_array($type, array('date'))) {
            $tmp = explode(',', $size);
            $newsize = $tmp[0];
            $showtime = 0;

            // Do not show current date when field not required (see selectDate() method)
            if (!$required && $value == '') {
                $value = '-1';
            }

            if ($mode == 1) {
                // search filter on a date extrafield shows two inputs to select a date range
                $prefill = array(
                    'start' => isset($value['start']) ? $value['start'] : '',
                    'end'   => isset($value['end'])   ? $value['end']   : ''
                );
                $out = '<div ' . ($moreparam ? $moreparam : '') . '><div class="nowrap">';
                $out .= $form->selectDate($prefill['start'], $keyprefix.$key.$keysuffix.'_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
                $out .= '</div><div class="nowrap">';
                $out .= $form->selectDate($prefill['end'], $keyprefix.$key.$keysuffix.'_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
                $out .= '</div></div>';
            } else {
                // TODO Must also support $moreparam
                $out = $form->selectDate($value, $keyprefix.$key.$keysuffix, $showtime, $showtime, $required, '', 1, (($keyprefix != 'search_' && $keyprefix != 'search_options_') ? 1 : 0), 0, 1);
            }
        } elseif (in_array($type, array('datetime'))) {
            $tmp = explode(',', $size);
            $newsize = $tmp[0];
            $showtime = 1;

            // Do not show current date when field not required (see selectDate() method)
            if (!$required && $value == '') {
                $value = '-1';
            }

            if ($mode == 1) {
                // search filter on a date extrafield shows two inputs to select a date range
                $prefill = array(
                    'start' => isset($value['start']) ? $value['start'] : '',
                    'end'   => isset($value['end'])   ? $value['end']   : ''
                );
                $out = '<div ' . ($moreparam ? $moreparam : '') . '><div class="nowrap">';
                $out .= $form->selectDate($prefill['start'], $keyprefix.$key.$keysuffix.'_start', 1, 1, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"), 'tzuserrel');
                $out .= '</div><div class="nowrap">';
                $out .= $form->selectDate($prefill['end'], $keyprefix.$key.$keysuffix.'_end', 1, 1, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"), 'tzuserrel');
                $out .= '</div></div>';
            } else {
                // TODO Must also support $moreparam
                $out = $form->selectDate($value, $keyprefix.$key.$keysuffix, $showtime, $showtime, $required, '', 1, (($keyprefix != 'search_' && $keyprefix != 'search_options_') ? 1 : 0), 0, 1, '', '', '', 1, '', '', 'tzuserrel');
            }
        } elseif (in_array($type, array('int', 'integer'))) {
            $tmp = explode(',', $size);
            $newsize = $tmp[0];
            $out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" maxlength="'.$newsize.'" value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').'>';
        } elseif (preg_match('/varchar/', $type)) {
            $out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" maxlength="'.$size.'" value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').'>';
        } elseif (in_array($type, array('mail', 'phone', 'url'))) {
            $out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
        } elseif ($type == 'text') {
            if (!preg_match('/search_/', $keyprefix)) {     // If keyprefix is search_ or search_options_, we must just use a simple text field
                require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                $doleditor = new DolEditor($keyprefix.$key.$keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, false, ROWS_5, '90%');
                $out = $doleditor->Create(1);
            } else {
                $out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
            }
        } elseif ($type == 'html') {
            if (!preg_match('/search_/', $keyprefix)) {     // If keyprefix is search_ or search_options_, we must just use a simple text field
                require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                $doleditor = new DolEditor($keyprefix.$key.$keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, !empty($conf->fckeditor->enabled) && $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_5, '90%');
                $out = $doleditor->Create(1);
            } else {
                $out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
            }
        } elseif ($type == 'boolean') {
            if (empty($mode)) {
                $checked = '';
                if (!empty($value)) {
                    $checked = ' checked value="1" ';
                } else {
                    $checked = ' value="1" ';
                }
                $out = '<input type="checkbox" class="flat valignmiddle'.($morecss ? ' '.$morecss : '').' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.$checked.' '.($moreparam ? $moreparam : '').'>';
            } else {
                $out .= $form->selectyesno($keyprefix.$key.$keysuffix, $value, 1, false, 1);
            }
        } elseif ($type == 'price') {
            if (!empty($value)) {       // $value in memory is a php numeric, we format it into user number format.
                $value = price($value);
            }
            $out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'> '.$langs->getCurrencySymbol($conf->currency);
        } elseif ($type == 'double') {
            if (!empty($value)) {       // $value in memory is a php numeric, we format it into user number format.
                $value = price($value);
            }
            $out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'> ';
        } elseif ($type == 'checkbox') {
            $value_arr = $value;
            if (!is_array($value)) {
                $value_arr = explode(',', $value);
            }
            $out = $form->multiselectarray($keyprefix.$key.$keysuffix, (empty($param['options']) ?null:$param['options']), $value_arr, '', 0, '', 0, '100%');
        } elseif ($type == 'radio') {
            $out = '';
            foreach ($param['options'] as $keyopt => $val) {
                $out .= '<input class="flat '.$morecss.'" type="radio" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '');
                $out .= ' value="'.$keyopt.'"';
                $out .= ' id="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'"';
                $out .= ($value == $keyopt ? 'checked' : '');
                $out .= '/><label for="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'">'.$langs->trans($val).'</label><br>';
            }
        } elseif ($type == 'chkbxlst') {
            if (is_array($value)) {
                $value_arr = $value;
            } else {
                $value_arr = explode(',', $value);
            }

            if (is_array($param['options'])) {
                $param_list = array_keys($param['options']);
                $InfoFieldList = explode(":", $param_list[0]);
                $parentName = '';
                $parentField = '';
                // 0 : tableName
                // 1 : label field name
                // 2 : key fields name (if differ of rowid)
                // 3 : key field parent (for dependent lists)
                // 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
                // 5 : id category type
                // 6 : ids categories list separated by comma for category root
                $keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2].' as rowid');

                if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
                    list ($parentName, $parentField) = explode('|', $InfoFieldList[3]);
                    $keyList .= ', '.$parentField;
                }
                if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
                    if (strpos($InfoFieldList[4], 'extra.') !== false) {
                        $keyList = 'main.'.$InfoFieldList[2].' as rowid';
                    } else {
                        $keyList = $InfoFieldList[2].' as rowid';
                    }
                }

                $filter_categorie = false;
                if (count($InfoFieldList) > 5) {
                    if ($InfoFieldList[0] == 'categorie') {
                        $filter_categorie = true;
                    }
                }

                if ($filter_categorie === false) {
                    $fields_label = explode('|', $InfoFieldList[1]);
                    if (is_array($fields_label)) {
                        $keyList .= ', ';
                        $keyList .= implode(', ', $fields_label);
                    }

                    $sqlwhere = '';
                    $sql = "SELECT ".$keyList;
                    $sql .= ' FROM '.MAIN_DB_PREFIX.$InfoFieldList[0];
                    if (!empty($InfoFieldList[4])) {
                        // can use current entity filter
                        if (strpos($InfoFieldList[4], '$ENTITY$') !== false) {
                            $InfoFieldList[4] = str_replace('$ENTITY$', $conf->entity, $InfoFieldList[4]);
                        }
                        // can use SELECT request
                        if (strpos($InfoFieldList[4], '$SEL$') !== false) {
                            $InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
                        }

                        // current object id can be use into filter
                        if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
                            $InfoFieldList[4] = str_replace('$ID$', $objectid, $InfoFieldList[4]);
                        } elseif (preg_match("#^.*list.php$#", $_SERVER["PHP_SELF"])) {
                            // Pattern for word=$ID$
                            $word = '\b[a-zA-Z0-9-\.-_]+\b=\$ID\$';

                            // Removing space arount =, ( and )
                            $InfoFieldList[4] = preg_replace('# *(=|\(|\)) *#', '$1', $InfoFieldList[4]);

                            $nbPreg = 1;
                            // While we have parenthesis
                            while ($nbPreg != 0) {
                                // Init des compteurs
                                $nbPregRepl = $nbPregSel = 0;
                                // On retire toutes les parenthèses sans = avant
                                $InfoFieldList[4] = preg_replace('#([^=])(\([^)^(]*('.$word.')[^)^(]*\))#', '$1 $3 ', $InfoFieldList[4], -1, $nbPregRepl);
                                // On retire les espaces autour des = et parenthèses
                                $InfoFieldList[4] = preg_replace('# *(=|\(|\)) *#', '$1', $InfoFieldList[4]);
                                // On retire toutes les parenthèses avec = avant
                                $InfoFieldList[4] = preg_replace('#\b[a-zA-Z0-9-\.-_]+\b=\([^)^(]*('.$word.')[^)^(]*\)#', '$1 ', $InfoFieldList[4], -1, $nbPregSel);
                                // On retire les espaces autour des = et parenthèses
                                $InfoFieldList[4] = preg_replace('# *(=|\(|\)) *#', '$1', $InfoFieldList[4]);

                                // Calcul du compteur général pour la boucle
                                $nbPreg = $nbPregRepl + $nbPregSel;
                            }

                            // Si l'on a un AND ou un OR, avant ou après
                            preg_match('#(AND|OR|) *('.$word.') *(AND|OR|)#', $InfoFieldList[4], $matchCondition);
                            while (!empty($matchCondition[0])) {
                                // If the two sides differ but are not empty
                                if (!empty($matchCondition[1]) && !empty($matchCondition[3]) && $matchCondition[1] != $matchCondition[3]) {
                                    // Nobody sain would do that without parentheses
                                    $InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
                                } else {
                                    if (!empty($matchCondition[1])) {
                                        $boolCond = (($matchCondition[1] == "AND") ? ' AND TRUE ' : ' OR FALSE ');
                                        $InfoFieldList[4] = str_replace($matchCondition[0], $boolCond.$matchCondition[3], $InfoFieldList[4]);
                                    } elseif (!empty($matchCondition[3])) {
                                        $boolCond = (($matchCondition[3] == "AND") ? ' TRUE AND ' : ' FALSE OR');
                                        $InfoFieldList[4] = str_replace($matchCondition[0], $boolCond, $InfoFieldList[4]);
                                    } else {
                                        $InfoFieldList[4] = " TRUE ";
                                    }
                                }

                                // Si l'on a un AND ou un OR, avant ou après
                                preg_match('#(AND|OR|) *('.$word.') *(AND|OR|)#', $InfoFieldList[4], $matchCondition);
                            }
                        } else {
                            $InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
                        }

                        // We have to join on extrafield table
                        if (strpos($InfoFieldList[4], 'extra.') !== false) {
                            $sql .= ' as main, '.MAIN_DB_PREFIX.$InfoFieldList[0].'_extrafields as extra';
                            $sqlwhere .= " WHERE extra.fk_object=main.".$InfoFieldList[2]." AND ".$InfoFieldList[4];
                        } else {
                            $sqlwhere .= " WHERE ".$InfoFieldList[4];
                        }
                    } else {
                        $sqlwhere .= ' WHERE 1=1';
                    }
                    // Some tables may have field, some other not. For the moment we disable it.
                    if (in_array($InfoFieldList[0], array('tablewithentity'))) {
                        $sqlwhere .= " AND entity = ".((int) $conf->entity);
                    }
                    // $sql.=preg_replace('/^ AND /','',$sqlwhere);
                    // print $sql;

                    $sql .= $sqlwhere;
                    dol_syslog(get_class($this).'::showInputField type=chkbxlst', LOG_DEBUG);
                    $resql = $this->db->query($sql);
                    if ($resql) {
                        $num = $this->db->num_rows($resql);
                        $i = 0;

                        $data = array();

                        while ($i < $num) {
                            $labeltoshow = '';
                            $obj = $this->db->fetch_object($resql);

                            $notrans = false;
                            // Several field into label (eq table:code|libelle:rowid)
                            $fields_label = explode('|', $InfoFieldList[1]);
                            if (is_array($fields_label)) {
                                $notrans = true;
                                foreach ($fields_label as $field_toshow) {
                                    $labeltoshow .= $obj->$field_toshow.' ';
                                }
                            } else {
                                $labeltoshow = $obj->{$InfoFieldList[1]};
                            }
                            $labeltoshow = dol_trunc($labeltoshow, 45);

                            if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
                                foreach ($fields_label as $field_toshow) {
                                    $translabel = $langs->trans($obj->$field_toshow);
                                    if ($translabel != $obj->$field_toshow) {
                                        $labeltoshow = dol_trunc($translabel, 18).' ';
                                    } else {
                                        $labeltoshow = dol_trunc($obj->$field_toshow, 18).' ';
                                    }
                                }

                                $data[$obj->rowid] = $labeltoshow;
                            } else {
                                if (!$notrans) {
                                    $translabel = $langs->trans($obj->{$InfoFieldList[1]});
                                    if ($translabel != $obj->{$InfoFieldList[1]}) {
                                        $labeltoshow = dol_trunc($translabel, 18);
                                    } else {
                                        $labeltoshow = dol_trunc($obj->{$InfoFieldList[1]}, 18);
                                    }
                                }
                                if (empty($labeltoshow)) {
                                    $labeltoshow = '(not defined)';
                                }

                                if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
                                    $data[$obj->rowid] = $labeltoshow;
                                }

                                if (!empty($InfoFieldList[3]) && $parentField) {
                                    $parent = $parentName.':'.$obj->{$parentField};
                                }

                                $data[$obj->rowid] = $labeltoshow;
                            }

                            $i++;
                        }
                        $this->db->free($resql);

                        $out = $form->multiselectarray($keyprefix.$key.$keysuffix, $data, $value_arr, '', 0, '', 0, '100%');
                    } else {
                        print 'Error in request '.$sql.' '.$this->db->lasterror().'. Check setup of extra parameters.<br>';
                    }
                } else {
                    require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
                    $data = $form->select_all_categories(Categorie::$MAP_ID_TO_CODE[$InfoFieldList[5]], '', 'parent', 64, $InfoFieldList[6], 1, 1);
                    $out = $form->multiselectarray($keyprefix.$key.$keysuffix, $data, $value_arr, '', 0, '', 0, '100%');
                }
            }
        } elseif ($type == 'link') {
            $param_list = array_keys($param['options']); // $param_list='ObjectName:classPath'
            $showempty = (($required && $default != '') ? 0 : 1);
            $out = $form->selectForForms($param_list[0], $keyprefix.$key.$keysuffix, $value, $showempty, '', '', $morecss);
        } elseif ($type == 'password') {
            // If prefix is 'search_', field is used as a filter, we use a common text field.
            $out = '<input style="display:none" type="text" name="fakeusernameremembered">'; // Hidden field to reduce impact of evil Google Chrome autopopulate bug.
            $out .= '<input autocomplete="new-password" type="'.($keyprefix == 'search_' ? 'text' : 'password').'" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'>';
        }
        if (!empty($hidden)) {
            $out = '<input type="hidden" value="'.$value.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"/>';
        }
        /* Add comments
         if ($type == 'date') $out.=' (YYYY-MM-DD)';
         elseif ($type == 'datetime') $out.=' (YYYY-MM-DD HH:MM:SS)';
         */
        /*if (! empty($help) && $keyprefix != 'search_options_') {
            $out .= $form->textwithpicto('', $help, 1, 'help', '', 0, 3);
        }*/
        return $out;
    }

    public function calculateWeekdaysWithOrWithoutWeekEnd($date_start = 0, $date_end = 0){

        $noformatstart = (int) $date_start;
        $noformatend = (int) $date_end;
        
        $noformatend = $noformatend ? $noformatend : $noformatstart;

        $Duration = 1;

        if(!$date_start || !$date_end) return $Duration;

        $taskworkingtime = $this->taskworkingtime;
        $worktime = explode('|', $taskworkingtime);


        if(!empty($worktime[0])){
            $hourstart = explode('-', $worktime[0]);
            if(!empty($hourstart)){
                
                $hstart1 = (int)explode(':', $hourstart[0])[0];
                $mstart1 = (int)explode(':', $hourstart[0])[1];

                $hend1 = (int)explode(':', $hourstart[1])[0];
                $mend1 = (int)explode(':', $hourstart[1])[1];
            }
        }


        if($this->excludeweekend > 0) {
            // Convert timestamps to DateTime objects
            $startDate = new DateTime();
            $startDate->setTimestamp($noformatstart);

            $endDate = new DateTime();
            $endDate->setTimestamp($noformatend);

            // Calculate total days between dates
            $totalDays = $endDate->diff($startDate)->days + 1;

            // Calculate full weeks and remaining days
            $fullWeeks = floor($totalDays / 7);
            $remainingDays = $totalDays % 7;

            // Calculate weekdays by excluding weekends
            $weekdays = ($fullWeeks * 5) + min($remainingDays, 5 - (($startDate->format('N') > 5) ? 0 : 5 - $startDate->format('N')));

            $dt = dol_mktime(0, 0, 0, $startDate->format('m'), $startDate->format('d'), $startDate->format('Y'));
            $date = new DateTime();
            $date->setTimestamp($dt);

            // $weekdays = 0;
            // if($totalDays == 1){
            //     if($startDate->format('N') < 6 && (int)$startDate->format('H') >= (int)$hstart1 && (int)$endDate->format('H') <= (int)$hend1){
            //         $weekdays++;
            //     }
            // }else{
            //     if($startDate->format('N') < 6 && (int)$startDate->format('H') <= (int)$hend1){
            //         $weekdays++;
            //     }
            //     for ($i=0; $i < $totalDays; $i++) { 
            //         $date->modify('+1 day');
            //         if ($date->format('N') < 6) {
            //             if(($date->format('Y-m-d') == $endDate->format('Y-m-d'))){
            //                 if(
            //                     (int)$endDate->format('H') >= (int)$hstart1 && (int)$endDate->format('H') <= (int)$hend1
            //                 ){
            //                     $weekdays++;
            //                 }
            //             }else{
            //                 $weekdays++;
            //             }
            //         }
            //     }
            // }


            $weekdays = 0;
            if($startDate->format('Y-m-d') == $endDate->format('Y-m-d')){
                if(
                    (($this->excludeweekend > 0 && $startDate->format('N') < 6) || empty($this->excludeweekend)) 
                    && (int)$startDate->format('H') >= (int)$hstart1 && (int)$endDate->format('H') <= (int)$hend1){
                    $weekdays++;
                }
            }else{
                if(($this->excludeweekend > 0 && $startDate->format('N') < 6) || empty($this->excludeweekend)) {
                    if((int)$startDate->format('H') > $hstart1){
                        if((int)$startDate->format('H') < (int)$hend1){
                            $weekdays++;
                        }
                    }else{
                        $weekdays++;
                    }
                }
                for ($i=0; $i < $totalDays-1; $i++) { 
                    $date->modify('+1 day');
                    if (($this->excludeweekend > 0 && $date->format('N') < 6) || empty($this->excludeweekend)) {
                        if(($date->format('Y-m-d') == $endDate->format('Y-m-d'))){
                            if(
                                (int)$endDate->format('H') > (int)$hstart1
                            ){
                                $weekdays++;
                            }
                        }else{
                            $weekdays++;
                        }
                    }
                }
            }


            // Set the duration string
            $Duration = ($weekdays <= 1 ? '1' : $weekdays);

        } else {

            $datediff = $noformatend - $noformatstart;
            // d('noformatstart 1: '.$noformatstart, 0);
            // d('noformatend 1: '.$noformatend, 0);
            $duration = round($datediff / (60 * 60 * 24));
            // d('duration 1: '.$duration, 0);
            if($duration <= 1)  $Duration = '1';
            else  $Duration = $duration;

        }
        
        return $Duration;
    }

    public function calculateWeekHoursWithOrWithoutWeekEnd($date_start = 0, $date_end = 0){

        $noformatstart = (int) $date_start;
        $noformatend = (int) $date_end;
        
        $noformatend = $noformatend ? $noformatend : $noformatstart;

        $Duration = 1;

        $taskworkingtime = $this->taskworkingtime;
        $worktime = explode('|', $taskworkingtime);


        if(!empty($worktime[0])){
            $hourstart = explode('-', $worktime[0]);
            if(!empty($hourstart)){
                
                $hstart1 = (int)explode(':', $hourstart[0])[0];
                $mstart1 = (int)explode(':', $hourstart[0])[1];

                $hend1 = (int)explode(':', $hourstart[1])[0];
                $mend1 = (int)explode(':', $hourstart[1])[1];
            }
        }

        if(!empty($worktime[1])){
            $hourstart = explode('-', $worktime[1]);
            if(!empty($hourstart)){
                $hstart2 = (int)explode(':', $hourstart[0])[0];
                $hend2 = (int)explode(':', $hourstart[1])[0];
            }
        }


        if(!$date_start || !$date_end) return $Duration;

         // Convert timestamps to DateTime objects
        $startDate = new DateTime();
        $startDate->setTimestamp($noformatstart);

        $endDate = new DateTime();
        $endDate->setTimestamp($noformatend);

        // // Calculate total days between dates
        $totalDays = $endDate->diff($startDate)->days + 1;

        // Hours numbers in day od task
        $nbhourinday = ($hend1 - $hstart1);
        $hours = 0;

        $dt = dol_mktime(0, 0, 0, $startDate->format('m'), $startDate->format('d'), $startDate->format('Y'));
        $date = new DateTime();
        $date->setTimestamp($dt);

        $weekdays = 0;
        if($startDate->format('Y-m-d') == $endDate->format('Y-m-d')){
            if(
                (($this->excludeweekend > 0 && $startDate->format('N') < 6) || empty($this->excludeweekend)) 
                && (int)$startDate->format('H') >= (int)$hstart1 && (int)$endDate->format('H') <= (int)$hend1){
                // $weekdays++;
                $hours += ($endDate->format('N')-$startDate->format('N'));
            }
        }else{
            // $weekdays++;
            if(($this->excludeweekend > 0 && $startDate->format('N') < 6) || empty($this->excludeweekend)) {
                if((int)$startDate->format('H') > $hstart1){
                    if((int)$startDate->format('H') < (int)$hend1){
                        $hours += ((int)$hend1 - (int)$startDate->format('H'));
                    }
                    // d('h start in date '.$startDate->format('d/m/Y H:M').' = '.(int)$startDate->format('H'). 'by h_start: '.$hstart1. ' AND h_end: '.$hend1.' nhours in day = '.(((int)$startDate->format('H') < (int)$hend1) ? (int)$hend1 - (int)$startDate->format('H') : 0), 0);
                    // d('hours: '.$hours, 0);
                }else{
                    $hours += ((int)$hend1 - (int)$hstart1);
                }
            }
            for ($i=0; $i < $totalDays-1; $i++) { 
                $date->modify('+1 day');
                if (($this->excludeweekend > 0 && $date->format('N') < 6) || empty($this->excludeweekend)) {
                    if(($date->format('Y-m-d') == $endDate->format('Y-m-d'))){
                        if(
                            (int)$endDate->format('H') >= (int)$hstart1
                        ){
                            if((int)$endDate->format('H') < $hend1){
                                $hours += ((int)$endDate->format('H') - (int)$hstart1);
                                // d('h end in date '.$endDate->format('d/m/Y H:M').' = '.(int)$endDate->format('H'). 'by h_start: '.$hstart1. ' AND h_end: '.$hend1.' nhours in day = '.((int)$endDate->format('H') - (int)$hstart1), 0);
                                // d('hours: '.$hours, 0);
                            }
                            else{
                                // d('h end in date '.$endDate->format('d/m/Y H:M').' = '.(int)$endDate->format('H'). 'by h_start: '.$hstart1. ' AND h_end: '.$hend1.' nhours in day = '.$nbhourinday, 0);
                                $hours += $nbhourinday;
                                // d('hours: '.$hours, 0);
                            }
                        }
                        

                    }else{
                        $hours += $nbhourinday;
                        // d('hours: '.$hours, 0);
                    }
                }
            }
        }

        $durationh = $hours;

        return $durationh;
    }

    public function select_types($selected=0,$name='select_',$multiple=0,$showempty=1,$id=''){
        global $conf,$langs;
        $html = '';
        $nodatarole = '';
        $id = (!empty($id)) ? $id : $name;
        $multi= '';
        $objet = "label";
        if($multiple){
            $multi = 'multiple';
            $name = $name.'[]';
        }
        if (!is_array($selected)) {
            $selected = array($selected);
        }

        $array_types=array(
            'Projectmanageruser' => $langs->trans('Projectmanageruser'),
            'Customer' => $langs->trans('Customer'),
            'Tagscategories' => $langs->trans('Categories'),
        );

        if($this->trellotasksplus_enabled){
            $array_types['Tagstrello'] = $langs->trans('tagstrellotasksplus');
        }

        $html.='<select class="flat minwidth400" id="'.$id.'" name="'.$name.'" '.$nodatarole.' '.$multi.'>';
        if ($showempty) $html.='<option value="-1">&nbsp;</option>';
            if($array_types) {
                foreach ($array_types as $key => $type) {
                    $html .= '<option value="'.$key.'"' ;
                    if(!empty($selected) && in_array($key, $selected)) $html .= 'selected';
                    $html .= '>';
                    $html .= $type;
                    $html .= '</option>';
                }
            }
        $html.='</select>';
        $html.='<style>#s2id_select_'.$name.'{ width: 100% !important;}</style>';
        $html.='<script>';
            $html.='$(document).ready(function(){';
                $html.='$("#typefiltre").select2();';
            $html.='});';
        $html.='</script>';

        return $html;
    }


    /**
     *  Return the balance of annual leave of a user
     *
     *  @param  int     $user_id    User ID
     *  @param  int     $fk_type    Filter on type
     *  @return ?float              Balance of annual leave if OK, null if KO.
     */
    public function getNbHolidayForUser($user_id='', $fk_type = 0, $start='', $end='')
    {
        $data = array();

        $sql = "SELECT fk_user, COUNT(rowid) as nb_holiday";
        $sql .= " FROM ".MAIN_DB_PREFIX."holiday";
        $sql .= " WHERE entity IN ".getEntity('holiday').")";
        // $sql .= " WHERE fk_user = ".(int) $user_id;
        $sql .= " AND statut IN (".Holiday::STATUS_VALIDATED.",".Holiday::STATUS_APPROVED.")";
        if ($fk_type > 0) {
            $sql .= " AND fk_type = ".(int) $fk_type;
        }
        if($start){
            $sql .= " AND date_debut BETWEEN '".$this->db->idate($start)."' AND '".$this->db->idate($end)."'";
        }
        if($end){
            $sql .= " AND date_fin BETWEEN '".$this->db->idate($start)."' AND '".$this->db->idate($end)."'";
        }

        $sql .= " AND fk_user IN (SELECT fk_socpeople FROM ".MAIN_DB_PREFIX."element_contact AS c , ".MAIN_DB_PREFIX."c_type_contact AS t WHERE c.fk_c_type_contact=t.rowid AND t.element='project_task')";
        $sql .= " GROUP BY fk_user";

        $result = $this->db->query($sql);
        if ($result) {
            $obj = $this->db->fetch_object($result);
            //return number_format($obj->nb_holiday,2);
            while ($obj = $db->fetch_object($resql)) {
                $data[$obj->fk_user] += $obj->nb_holiday;
            }
        }
        return $data;
    }


    // public function ganttproadvancedExportToExcel($filename='Releve', $html="", $societe = '')
        // {
        //     global $conf, $langs;

        //     $file = "export_excel2007.modules.php";
        //     $classname = "ExportExcel2007";
        //     require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        //     require_once DOL_DOCUMENT_ROOT.'/includes/phpoffice/phpspreadsheet/src/autoloader.php';
        //     require_once DOL_DOCUMENT_ROOT.'/includes/Psr/autoloader.php';
        //     require_once PHPEXCELNEW_PATH.'Spreadsheet.php';
        //     require_once DOL_DOCUMENT_ROOT."/core/modules/export/export_excel2007.modules.php";
        //     $objmodel = new ExportExcel2007($db);

        //     $reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
        //     $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            
        //     $dirname = $conf->ganttproadvanced->dir_output;
        //     // Open file
        //     dol_mkdir($dirname);

        //     $res = fopen($dirname."/filexlsx.html", 'w');
        //     $res = fwrite($res, $html);

        //     $filename_xls = dol_htmlentitiesbr_decode($filename);

        //     libxml_use_internal_errors(true);
        //     $spreadsheet = $reader->loadIntoExisting($dirname."/filexlsx.html", $spreadsheet);
        //     libxml_clear_errors();
            
        //     dol_delete_file($dirname."/filexlsx.html");

        //     $titlesheet = dol_trunc($filename_xls, $_max = 31);

        //     $spreadsheet->getSheet(0)->setTitle($titlesheet);
        //     $spreadsheet->setActiveSheetIndex(0);

        //     $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

        //     // if($societe) {
        //     //      $filename_xls = dol_htmlentitiesbr_decode($societe->getFullName($langs)).' - '.$filename_xls;
        //     // }

        //     header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        //     header('Content-Disposition: attachment; filename='.$filename_xls.'.xlsx');
        //     ob_end_clean();
        //     $writer->save('php://output');
        //     ob_start();
    // }
    
    public function upgradeTheModule()
    {
        global $conf, $langs;

        dol_include_once('/ganttproadvanced/core/modules/modganttproadvanced.class.php');
        $modganttproadvanced = new modganttproadvanced($this->db);

        $lastversion    = $modganttproadvanced->version;
        $currentversion = dolibarr_get_const($this->db, 'GANTTPROADVANCED_LAST_VERSION_OF_MODULE', 0);

        if (!$currentversion || ($currentversion && $lastversion != $currentversion)){
            $res = $this->initTheModuleganttproadvanced($lastversion);
            if($res)
                dolibarr_set_const($this->db, 'GANTTPROADVANCED_LAST_VERSION_OF_MODULE', $lastversion, 'chaine', 0, '', 0);
            return 1;
        }
        return 0;
    }
    
    public function initTheModuleganttproadvanced($lastversion = '')
    {
        global $conf, $langs;

        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);

        $position = 27;
        $extrafields->addExtraField('ganttproadvancedcolor', $langs->trans('Color'), "varchar", $position++, 100, "projet_task", 0, 0, '#16a085', '', 0, '', 3);
        $extrafields->addExtraField('ganttproadvancedprojectprogress', $langs->trans('ProgressProject'), "int", $position++, 100, "projet", 0, 0, '0');

        // $visib_datejalon = $this->use_abnovo_datejalon ? 0 : 3;
        $visib_datejalon = 3;
        $extrafields->addExtraField('ganttproadvanceddatejalon', 'JalonDate', "date", $position++, 100, "projet_task", 0, 0, '', '', 0, '', $visib_datejalon);
        
        $params = '';
        $params2 = serialize(array('options' => $this->typesrelation));

        $extrafields->addExtraField('ganttproadvancedrelatedtask', 'RelatedToTask', "int", $position++, 100, "projet_task", 0, 0, '', $params, 0, '', 0);
        $extrafields->addExtraField('ganttproadvancedtyperelation', 'TypeRelation', "select", $position++, 100, "projet_task", 0, 0, '', $params2, 0, '', 0);
        $extrafields->addExtraField('ganttproadvancednumberdayslate', 'NumberDaysLate', "int", $position++, 100, "projet_task", 0, 0, '', $params, 0, '', 0);


        $sql = 'CREATE TABLE IF NOT EXISTS '.MAIN_DB_PREFIX.'kanban_commnts (
            rowid int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            comment text NULL,
            fk_task int NULL,
            fk_user int NULL,
            date datetime NULL
        );';
        $resql = $this->db->query($sql);

        return 1;
    }

}
?>