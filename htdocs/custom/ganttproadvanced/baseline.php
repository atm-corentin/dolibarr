<?php
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");       // For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
    // require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

dol_include_once("/ganttproadvanced/lib/ganttproadvanced.lib.php");
dol_include_once("/ganttproadvanced/class/ganttproadvanced.class.php");
dol_include_once("/ganttproadvanced/class/ganttproadvancedutils.class.php");
dol_include_once('/ganttproadvanced/core/modules/modganttproadvanced.class.php');

$langs->loadLangs(array('admin', 'projects', 'mails', 'ganttproadvanced@ganttproadvanced'));

if (!$conf->projet->enabled || !$conf->ganttproadvanced->enabled || empty($user->rights->ganttproadvanced->lire)) {
	accessforbidden();
}


// ------------------------------------------------------------------------------------------- OBJECTS
$project 		= new Project($db);
$projectstatic 	= new Project($db);
$objtask 		= new Task($db);
$taskstatic 	= new Task($db);
$userextrafields 	= new ExtraFields($db);
$extrafields 	= new ExtraFields($db);
$ganttproadvanced  		= new ganttproadvanced($db);
$ganttproadvancedutils  = new ganttproadvancedutils($db);
$form 			= new Form($db);
$formproject 	= new FormProjets($db);
$formcompany   	= new FormCompany($db);
$formother 		= new FormOther($db);
$userstatic     = new User($db);

$extrafields->fetch_name_optionals_label($objtask->table_element);
$userextrafields->fetch_name_optionals_label($userstatic->table_element);


$ganttproadvanced->upgradeTheModule();

// ------------------------------------------------------------------------------------------- Test
// // $projectsobjs = array();
// // $tasksobjs = array();
// // $tasksdates = array();
// // $projectdates = array();

// $tmpobjtask = new Task($db);
// $tmpobjtask->fetch(264);

// $ganttproadvancedutils->checkRecursivlyRelationsAndProjectDates($tmpobjtask);
// $ganttproadvancedutils->checkIfTheSelectedTaskAlreadyExistInTheTree(297);

// // d($tmpobjtask);
// // d($projectdates);
// // die;
// ------------------------------------------------------------------------------------------- End Test



// ------------------------------------------------------------------------------------------- 


$fin = strtotime(" +3 months");
$dt_fin = dol_getdate($fin);

$removefilter = (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) ? 1 : 0;

$searst = (GETPOST('search_status') != '') ? 1 : 0;

if(!$ganttproadvanced->keep_recent_filter) {
	$searst = 1;
}
// ------------------------------------------------------------------------------------------- User last search

$latestsearch_status = (!$searst && isset($user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_STATUS)) ? (int) $user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_STATUS : 99;
$latestsearch_projects = (!$searst && isset($user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_PROJECTS)) ? explode(',', $user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_PROJECTS) : [];
$latestsearch_affecteduser = (!$searst && isset($user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_AFFECTEDUSER)) ? explode(',', $user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_AFFECTEDUSER) : [];
$latestsearch_debutyear = (!$searst && isset($user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_DEBUTYEAR)) ? $user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_DEBUTYEAR : date('Y');
$latestsearch_debutmonth = (!$searst && isset($user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_DEBUTMONTH)) ? $user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_DEBUTMONTH : date('m');
$latestsearch_finyear = (!$searst && isset($user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_FINYEAR)) ? $user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_FINYEAR : $dt_fin['year'];
$latestsearch_finmonth = (!$searst && isset($user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_FINMONTH)) ? $user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_FINMONTH : $dt_fin['mon'];
$latestsearch_scale = (!$searst && isset($user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_SCALE)) ? $user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_SCALE : $ganttproadvanced->default_zoom;
$latestsearch_tasktype = (!$searst && isset($user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_TASKTYPE)) ? explode(',', $user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_TASKTYPE) : [];
$latestsearch_tagstrello = (!$searst && isset($user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_TAGSTRELLO)) ? explode(',', $user->conf->GANTTPROADVANCED_GANTT_LATEST_SEARCH_TAGSTRELLO) : [];

$action 		= GETPOST('action', 'aZ09');
$showall 		= GETPOST('showall', 'int') ? GETPOST('showall', 'int') : 0;
$massaction 	= GETPOST('massaction', 'alpha');
$show_files 	= GETPOST('show_files', 'int');
$confirm 		= GETPOST('confirm', 'alpha');
$toselect 		= GETPOST('toselect', 'array');
$format        	= GETPOST('format', 'alpha');
$id 			= GETPOST('id', 'int');
$ref 			= GETPOST('ref', 'alpha');
$taskref 		= GETPOST('taskref', 'alpha');

// $limit 			= GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$limit 			= GETPOST('limit', 'int') ? GETPOST('limit', 'int') : 1000;
$viewmode 		= (GETPOST('viewmode', 'alpha') && $ganttproadvanced->viewingtasksbyresources) ? GETPOST('viewmode', 'alpha') : 'gantt';
$viewmode       = 'byresource';
$sortfield 		= GETPOST('sortfield', 'aZ09comma');
$sortorder 		= GETPOST('sortorder', 'aZ09comma');
$action 		= GETPOST('action');
$page 			= GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $page = 0; }
$offset 		= $limit * $page;
$pageprev 		= $page - 1;
$pagenext 		= $page + 1;
$backtopage 	= GETPOST('backtopage', 'alpha');
$cancel 		= GETPOST('cancel', 'alpha');

$submitedform = GETPOST('debutyear', 'int') ? 1 : 0;
$ids_projets = GETPOST('idp') ? array(0=>GETPOST('idp', 'int')) : [];

$search_category 		= GETPOST("search_category", 'int');
$search_customer 		= GETPOST("search_customer", 'int');
$search_userid 			= GETPOST("search_userid", 'int');
$search_tagstrello 		= GETPOST("search_tagstrello", 'array') ? GETPOST("search_tagstrello", 'array') : $latestsearch_tagstrello;
$search_tasktype 		= GETPOST("search_tasktype", 'array') ? GETPOST("search_tasktype", 'array') : $latestsearch_tasktype;
$search_status 			= (GETPOST("search_status", 'int') != '') ? GETPOST("search_status", 'int') : $latestsearch_status;
$search_projects 		= GETPOST("search_projects", 'array') ? GETPOST("search_projects", 'array') : ($ids_projets ? $ids_projets : $latestsearch_projects);
$search_affecteduser 	= $submitedform ? GETPOST("search_affecteduser", 'array') : $latestsearch_affecteduser;
$debutyear 				= GETPOST('debutyear', 'int') ? GETPOST('debutyear', 'int') : $latestsearch_debutyear;
$debutmonth 			= GETPOST('debutmonth', 'int') ? GETPOST('debutmonth', 'int') : $latestsearch_debutmonth;
$finyear 				= GETPOST('finyear', 'int') ? GETPOST('finyear', 'int') : $latestsearch_finyear;
$finmonth 				= GETPOST('finmonth', 'int') ? GETPOST('finmonth', 'int') : $latestsearch_finmonth;
$search_scale 			= GETPOST("scale", 'alpha') ? GETPOST("scale", 'alpha') : $latestsearch_scale;

###################################################################################################################### Filter MAX & MIN

$search_mindate = '';
$search_maxdate = '';


$search_debut = dol_mktime(0, 0, 0, $debutmonth, 1, $debutyear);
$search_fin = dol_get_last_day($finyear, $finmonth);


if ($search_status == '' && $search_status != '0') $search_status = $ganttproadvanced->default_status; // 100 = All

$filterprojstatus = '';
if($search_status != 100){
    if ($search_status == 99) {
        $filterprojstatus .= " AND p.fk_statut <> 2";
    } else {
        $filterprojstatus .= " AND p.fk_statut = ".((int) $search_status);
    }
}

$showall = ($ganttproadvanced->bydefaultproject == 'all' && empty($search_projects)) ? 1 : $showall;

$projectsListId = '';
if($ganttproadvanced->bydefaultproject != 'none' || $showall > 0) {

	$projectsListId = $ganttproadvanced->ganttproadvancedSelectProjectsAuthorized(0, $search_category, $search_status, $search_userid, $search_customer, true, $showall);
}

if(is_array($projectsListId)){
    $keyp = key($projectsListId);
    if(empty($search_projects) && $ganttproadvanced->bydefaultproject == 'one' && $keyp > 0) $search_projects[] = $keyp;
    if($showall > 0 && $projectsListId) {
    	$keyp = array_keys($projectsListId);
    	$search_projects = $keyp;
    }
}


###################################################################################################################### Empty Filter
$emptyfilter = false;
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { 
	$emptyfilter = true;
	$search_affecteduser = array();
	$search_projects = array();
	// $search_tasktype = array();
	// $search_status = 99;
	$search_mindate = '';
	$search_maxdate = '';
	$search_customer = '';
	$search_userid = '';
	$search_category = '';

	// $debutyear = $latestsearch_debutyear;
	// $debutmonth = $latestsearch_debutmonth;
	// $finyear = $latestsearch_finyear;
	// $finmonth = $latestsearch_finmonth;

	$debutyear = date('Y'); 
	$debutmonth = date('m'); 
	$finyear = $dt_fin['year']; 
	$finmonth = $dt_fin['mon'];

	// $debutyear = '';
	// $debutmonth = '';
	// $finyear = '';
	// $finmonth = '';
	// $search_debut = '';
	// $search_fin = '';

	$search_tasktype    = [];
	$search_tagstrello    = [];
}

if(!$ganttproadvanced->keep_recent_filter) {
	$emptyfilter = true;
}

###################################################################################################################### Save personnal parameter
$userparametres = array();

// $userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_PROJECTS'] = '';
// $userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_AFFECTEDUSER'] = '';
// $userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_STATUS'] = '';
// $userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_DEBUTYEAR'] = '';
// $userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_DEBUTMONTH'] = '';
// $userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_FINYEAR'] = '';
// $userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_FINMONTH'] = '';
// $userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_SCALE'] = '';

$tmpsearchprojects = GETPOST("search_projects", 'array');

if($showall || (!empty($search_projects) && $ganttproadvanced->bydefaultproject != 'none')) {
	$tmpsearchprojects = $search_projects;
}
if($tmpsearchprojects && !$emptyfilter) {
	$userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_PROJECTS'] = implode(',', $tmpsearchprojects);
}
if($searst && !$emptyfilter) {
	$userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_STATUS'] = GETPOST("search_status", 'int').'.0';
}
if($searst && !$emptyfilter) {
	$userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_AFFECTEDUSER'] = implode(',', GETPOST("search_affecteduser", 'array'));
}
if($searst && !$emptyfilter) {
	$userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_DEBUTYEAR'] = GETPOST("debutyear", 'int');
}
if($searst && !$emptyfilter) {
	$userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_DEBUTMONTH'] = GETPOST("debutmonth", 'int');
}
if($searst && !$emptyfilter) {
	$userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_FINYEAR'] = GETPOST("finyear", 'int');
}
if($searst && !$emptyfilter) {
	$userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_FINMONTH'] = GETPOST("finmonth", 'int');
}
if($searst && !$emptyfilter) {
	$userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_SCALE'] = GETPOST("scale", 'alpha');
}
if($searst && !$emptyfilter) {
	$userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_TASKTYPE'] = implode(',', GETPOST("search_tasktype", 'array'));
}
if($searst && !$emptyfilter) {
	$userparametres['GANTTPROADVANCED_GANTT_LATEST_SEARCH_TAGSTRELLO'] = implode(',', GETPOST("search_tagstrello", 'array'));
}

// d($userparametres);
if($userparametres) {
	$resusrs = dol_set_user_param($db, $conf, $user, $userparametres);
}
// d($resusrs);




$sortfield = str_replace('p.', '', $ganttproadvanced->p_sortfield);
$sortorder = str_replace('p.', '', $ganttproadvanced->p_sortorder);

if ($id > 0) {
	$result = $project->fetch($id);
	if ($result < 0) {
		setEventMessages(null, $project->errors, 'errors');
	}
	$result = $project->fetch_thirdparty();
	if ($result < 0) {
		setEventMessages(null, $project->errors, 'errors');
	}
	$result = $project->fetch_optionals();
	if ($result < 0) {
		setEventMessages(null, $project->errors, 'errors');
	}
}

$plannedworkloadoutputformat = 'allhourmin';
$timespentoutputformat = 'allhourmin';
if (!empty($conf->global->PROJECT_PLANNED_WORKLOAD_FORMAT)) {
	$plannedworkloadoutputformat = $conf->global->PROJECT_PLANNED_WORKLOAD_FORMAT;
}
if (!empty($conf->global->PROJECT_TIMES_SPENT_FORMAT)) {
	$timespentoutputformat = $conf->global->PROJECT_TIME_SPENT_FORMAT;
}

$sql_proj = implode(",", $search_projects);
$sql_users = (count($search_affecteduser) > 0) ? implode(",", $search_affecteduser) : '';
// $sql_tasktypes = (count($search_tasktype) > 0) ? implode(",", $search_tasktype) : '';
// $sql_tasktypes = $search_tasktype;
// $sql_tasktypes = $search_tasktype ? $search_tasktype : $ganttproadvanced->t_typecontact;


$sql_tasktypes = '';

if($search_tasktype) {
	$tmptasktypes = implode('","', $search_tasktype);
	$sql_tasktypes = '"'.$tmptasktypes.'"';
}

$sql_tagstrello = '';

if($search_tagstrello) {
	$tmptagstrello = implode('","', $search_tagstrello);
	$sql_tagstrello = '"'.$tmptagstrello.'"';
}

$returned = $ganttproadvanced->ganttproadvancedselectUsersThatSignedAsTasksContacts($sql_proj, $sql_tasktypes, $search_affecteduser);
$selectusers_html = $returned['html'];
$selectusers_array = $returned['array'];

if(!$search_affecteduser){
	// $ids_users = array_keys($selectusers_array);
	// $search_affecteduser = [0=>$ids_users[0]];
}


// ------------------------------------------------------------------------------------------- Trellotasksplus
$trellotasksplus_enabled = $ganttproadvanced->trellotasksplus_enabled;

$trellodefaultcolumnid = 0;
$trellodefaultcolumnlabel = '';

if($trellotasksplus_enabled && $ganttproadvanced->trelloshowtaskinfirstcolomn) {
	$trellosql = "SELECT o.rowid, o.label FROM ".MAIN_DB_PREFIX."trellotasksplus_columns as o";
	$trellosql .= " WHERE o.entity IN (0,".(int) $conf->entity.") ORDER BY rowid ASC limit 1";

	$trelloresql = $db->query($trellosql);
    if ($trelloresql) {
        $numrows = $db->num_rows($trelloresql);
        if ($numrows) {
            $obj = $db->fetch_object($trelloresql);
            $trellodefaultcolumnid = $obj->rowid;
            $trellodefaultcolumnlabel = $obj->label;
        }
	}
}

$users_tasks = array();


// ------------------------------------------------------------------------------------------- SQL Tâches


$groupbybudget = (floatval(DOL_VERSION) < 15) ? "" : ',t.budget_amount';

$groupby = '';
$groupby .= ',t.rang,t.ref,t.label,t.description,t.fk_projet '.$groupbybudget.',t.fk_task_parent,t.dateo,t.datee,t.progress,t.planned_workload,t.duration_effective,t.note_private';
$groupby .= ',t.fk_statut,ef.ganttproadvancedcolor,rp.ref,rp.title,rt.ref,rt.label';

if($trellotasksplus_enabled) {
	$groupby .= ',trcol.label';
}


$budget_amount = (floatval(DOL_VERSION) < 15) ? "0" : 't.budget_amount';

$sql = 'SELECT t.rowid, abs(t.rang) as rang, t.ref, t.label as title, t.description, t.fk_projet as projectid, '.$budget_amount.' as budget, t.fk_task_parent as fk_parent, t.progress, t.planned_workload, t.duration_effective, t.note_private';
if($ganttproadvanced->showhoursingantt){
	$sql .= ', t.dateo as dateo, t.datee as datee';
}else{
	$sql .= ', DATE(t.dateo) as dateo, DATE(t.datee) as datee';
}
$sql .= ' , t.fk_statut, ef.ganttproadvancedcolor as color, "task" as typetable, rp.title as projectlabel, s.nom as tiersname';

if($trellotasksplus_enabled) {
	$sql .= ' , trcol.label as trellotasksplusstatus';
}


$sql .= ', CONCAT(rp.ref, " > ",rt.ref, " - ", rt.label) as label_relatedtask';
// $sql .= ', CONCAT_WS(" - ", p.ref, rt.label)  as label_relatedtask';
// if($ganttproadvanced->coloredbyuser) {
	// $sql .= 'tc.libelle as type_contact_name, elem.fk_socpeople as contact_user_id, CONCAT(lastname, " ", firstname) as contact_user_name, u.color as contact_user_color, ';
	
	$nameorder = (!empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION) ? 1 : 0);
	$fullnamesql = ($nameorder == 1) ? "CONCAT(firstname, ' ', lastname)" : "CONCAT(lastname, ' ', firstname)";

	$sql .= ", GROUP_CONCAT( DISTINCT ";
	$sql .= " COALESCE(elem.fk_c_type_contact, '')";
	$sql .= " ,'::',COALESCE(elem.fk_socpeople, '')";
	$sql .= " ,'::',COALESCE(u.color, '')";
	$sql .= " ,'::',COALESCE(".$fullnamesql.", '')";
	$sql .= " ,'::',COALESCE(tc.code, '') ";

	if(!empty($userextrafields->attributes[$userstatic->table_element]['label']['cpsdepartment'])){
		$sql .= " ,'::',COALESCE(ue.cpsdepartment, '') ";
	}
	$sql .= " SEPARATOR ',') AS contacts_detail ";

	// $sql .= ", GROUP_CONCAT(DISTINCT COALESCE(elem.fk_c_type_contact, '') SEPARATOR ',') AS contacts_detail ";
	// $sql .= ", GROUP_CONCAT(DISTINCT COALESCE(elem.fk_c_type_contact, ''),'::',COALESCE(elem.fk_socpeople, ''),'::',COALESCE(u.color, '') SEPARATOR ',') AS contacts_detail ";
// }


if(!empty($ganttproadvanced->use_abnovo_datejalon)) {
	$sql .= ', abnovoonglet.etat_date as ganttproadvanceddatejalon';
	$groupby .= ', abnovoonglet.etat_date';
}

foreach ($extrafields->attributes[$objtask->table_element]['label'] as $key => $label) {
	if($key != 'ganttproadvancedcolor'){
		if(($key != 'ganttproadvanceddatejalon' && $ganttproadvanced->use_abnovo_datejalon) || !$ganttproadvanced->use_abnovo_datejalon){
			$sql .= ', ef.'.$key.' as '.$key;
			$groupby .= ', ef.'.$key;
		}
	}
}

$newcondition = ($ganttproadvanced->table_task_time == "element_time") ? " AND tm.elementtype = 'task' " : "";

// $sql .= ', SUM(tm.thm * tm.'.$ganttproadvanced->column_task_duration.'/3600) as couttotal';
$sql .= ', ( SELECT SUM(tm.thm * (tm.'.$ganttproadvanced->column_task_duration.'/3600)) FROM '.MAIN_DB_PREFIX.$ganttproadvanced->table_task_time.' as tm WHERE t.rowid = tm.'.$ganttproadvanced->column_fk_task.' '.$newcondition.') as couttotal ';

$sql .= ", GROUP_CONCAT(DISTINCT COALESCE(tp.rowid, ''),'::',  COALESCE(tp.fk_user, ''),'::', COALESCE(tp.".$ganttproadvanced->column_task_duration.", '') ,'::', COALESCE(tp.".$ganttproadvanced->column_task_time_date.", '') SEPARATOR ',') AS timespasse ";

$sql .= ', ( SELECT COUNT(c.rowid) FROM '.MAIN_DB_PREFIX.'kanban_commnts as c WHERE t.rowid = c.fk_task) as nb_comments ';


// $sql .= ', (';

// 	$sql = "SELECT GROUP_CONCAT(fk_user,'::', COUNT(rowid) as nb_holiday)";
//     $sql .= " FROM ".MAIN_DB_PREFIX."holiday";
//     $sql .= " WHERE entity IN ".getEntity('holiday').")";
//     // $sql .= " WHERE fk_user = ".(int) $user_id;
//     $sql .= " AND statut IN (".Holiday::STATUS_VALIDATED.",".Holiday::STATUS_APPROVED.")";

// 	$sql .= ' AND (';
// 		$sql .= ' (CAST(date_debut as date) BETWEEN CAST(t.dateo as date) AND CAST(t.datee as date))';
// 		$sql .= ' OR (CAST(date_fin as date) BETWEEN CAST(t.dateo as date) AND CAST(t.datee as date))';
// 		$sql .= ' OR (CAST(date_debut as date) < CAST(t.dateo as date) AND CAST(date_fin as date) > CAST(t.datee as date))';
// 	$sql .= ')';

//     $sql .= " AND fk_user IN (SELECT ec.fk_socpeople FROM ".MAIN_DB_PREFIX."element_contact AS ec , ".MAIN_DB_PREFIX."c_type_contact AS tce WHERE c.fk_c_type_contact=tce.rowid AND tce.element='project_task' AND tce.element_id=t.rowid)";
//     $sql .= " GROUP BY fk_user";

// $sql .= ') as holiday';


$sql .= ' FROM '.MAIN_DB_PREFIX.'projet_task as t';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task_extrafields as ef on (t.rowid = ef.fk_object)';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task as rt on (ef.ganttproadvancedrelatedtask = rt.rowid)';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.$ganttproadvanced->table_task_time.' as tm on (t.rowid = tm.'.$ganttproadvanced->column_fk_task.')';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as rp on (rp.rowid = t.fk_projet)';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s on (s.rowid = rp.fk_soc)';

$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as elem ON (t.rowid = elem.element_id) ";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc ON (tc.rowid = elem.fk_c_type_contact) ";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON (u.rowid = elem.fk_socpeople) ";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user_extrafields as ue ON (ue.fk_object = u.rowid) ";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."kanban_commnts as c ON (t.rowid = c.fk_task)";
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.$ganttproadvanced->table_task_time.' as tp ON tp.'.$ganttproadvanced->column_fk_task.' = t.rowid';

if($trellotasksplus_enabled) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'trellotasksplus_columns as trcol ON ef.trellotasksplus_colomn = trcol.rowid ';
}

if(!empty($ganttproadvanced->use_abnovo_datejalon)) {
	$sql .= ' 
	LEFT JOIN '.MAIN_DB_PREFIX.'abnovomoduleongletetatavancement as abnovoonglet ON 
	(abnovoonglet.fk_module = t.fk_projet AND abnovoonglet.module = "project"  AND abnovoonglet.fk_etat = ef.abnovomoduleetape)
  	';
}


if($ganttproadvanced->coloredbyuser) {
}


$sql .= ' WHERE t.entity = '.$conf->entity;
$sql .= ($sql_proj != '') ? ' AND t.fk_projet IN ('.$sql_proj.')' : ' AND 1<0 ';


if($sql_users){
	if($sql_tasktypes && $sql_tasktypes != '""') {
        $sql .= '  AND (tc.code IN ('.$sql_tasktypes.') OR tc.code is NULL)';
    }
	$sql .= ' AND u.rowid IN ('.$sql_users.')';
}

if($search_debut && $search_fin){
	$sql .= ' AND (';
	$sql .= ' (CAST(t.dateo as date) BETWEEN "'.$db->idate($search_debut).'" AND "'.$db->idate($search_fin).'")';
	$sql .= ' OR ';
	$sql .= ' (CAST(t.datee as date) BETWEEN "'.$db->idate($search_debut).'" AND "'.$db->idate($search_fin).'")';
	$sql .= ' OR ';
	$sql .= ' (CAST(t.dateo as date) < "'.$db->idate($search_debut).'" AND CAST(t.datee as date) > "'.$db->idate($search_fin).'")';

		// $sql .= ' OR';
		// $sql .= ' (CAST(rp.dateo as date) BETWEEN "'.$db->idate($search_debut).'" AND "'.$db->idate($search_fin).'")';
		// $sql .= ' OR ';
		// $sql .= ' (CAST(rp.datee as date) BETWEEN "'.$db->idate($search_debut).'" AND "'.$db->idate($search_fin).'")';

		// if($ganttproadvanced->showprojectsevenwithoutdates) {
		// 	$sql .= ' OR (rp.datee is NULL AND rp.dateo is NULL)';
		// }
	$sql .= ')';
}


$sql .= ' AND (t.datee is NOT NULL)';
$sql .= ' AND (t.dateo is NOT NULL)';

if($search_category>0) $sql .= ' AND t.fk_projet IN (SELECT cp.fk_project FROM '.MAIN_DB_PREFIX.'categorie_project as cp WHERE cp.fk_categorie='.$search_category.')';

if($trellotasksplus_enabled)
	if($sql_tagstrello) $sql .= ' AND (ef.trellotasksplus_colomn IN ('.$sql_tagstrello.') '.(in_array($trellodefaultcolumnid, $search_tagstrello) ? ' OR ef.trellotasksplus_colomn IS NULL OR ef.trellotasksplus_colomn=0 OR ef.trellotasksplus_colomn=""' : '').')';

// if($ganttproadvanced->coloredbyuser) {
// }

// if($ganttproadvanced->coloredbyuser && $sql_users) {
// }

$sql .= ' GROUP BY t.rowid ';
$sql .= $groupby;








if($viewmode != 'byresource') {

// ---------------------------------------------;
$sql .= ' UNION '; 
// ---------------------------------------------;








// ------------------------------------------------------------------------------------------- SQL Projets

$groupby = '';
$groupby .= ',p.ref,p.title,p.description,p.rowid,p.budget_amount,p.dateo,p.datee,ef.ganttproadvancedprojectprogress,p.note_private,p.fk_statut';


$sql .= 'SELECT p.rowid, "0" as rang, p.ref, p.title as title, p.description, p.rowid as projectid, p.budget_amount as budget, "0" as fk_parent, ef.ganttproadvancedprojectprogress as progress, "NULL" as planned_workload';
$sql .= ', DATE(p.dateo) as dateo, DATE(p.datee) as datee';
// $sql .= ', "NULL" as duration_effective';
$sql .= ', ( SELECT SUM(pt.duration_effective) FROM '.MAIN_DB_PREFIX.'projet_task as pt WHERE p.rowid = pt.fk_projet ) as duration_effective ';

$sql .= ', p.note_private';

$sql .= ' , p.fk_statut, "#d35400" as color,"project" as typetable, p.title as projectlabel, s.nom as tiersname';

if($trellotasksplus_enabled) {
	$sql .= ' , "NULL" as trellotasksplusstatus';
}

$sql .= ', "NULL" as couttotal';
$sql .= ', "NULL" as nb_comments';

$sql .= ', "NULL" as label_relatedtask';
// if($ganttproadvanced->coloredbyuser) {
	// $sql .= ',"" as type_contact_name, "0" as contact_user_id, "" as contact_user_name, "d35400" as contact_user_color';
	$sql .= ' , "NULL" as contacts_detail';
// }


foreach ($extrafields->attributes[$objtask->table_element]['label'] as $key => $label) {
	if($key != 'ganttproadvancedcolor')
	$sql .= ', "NULL" as '.$key;
}


$sql .= ' FROM '.MAIN_DB_PREFIX.'projet as p';
// $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task as rt on (rt.fk_projet = p.rowid)';
// $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task_time as tm on (rt.rowid = tm.'.$ganttproadvanced->column_fk_task.')';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_extrafields as ef on (p.rowid = ef.fk_object)';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s on (s.rowid = p.fk_soc)';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'element_contact as ec ON (p.rowid = ec.element_id)';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_contact as tc ON (ec.fk_c_type_contact = tc.rowid)';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_project as cp ON cp.fk_project = p.rowid ';

// ---------------------------------------------------------- TO NOT SHOW PROJECT WITHOUT ANY TASK
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task AS pt ON p.rowid = pt.fk_projet ';





// $sql .= ' LEFT JOIN (';

// 		$sql = "SELECT fk_user, COUNT(rowid) as nb_holiday";
//         $sql .= " FROM ".MAIN_DB_PREFIX."holiday";
//         $sql .= " WHERE entity IN ".getEntity('holiday').")";
//         // $sql .= " WHERE fk_user = ".(int) $user_id;
//         $sql .= " AND statut IN (".Holiday::STATUS_VALIDATED.",".Holiday::STATUS_APPROVED.")";
//         if ($fk_type > 0) {
//             $sql .= " AND fk_type = ".(int) $fk_type;
//         }
//         if($start){
//             $sql .= " AND date_debut BETWEEN '".$this->db->idate($start)."' AND '".$this->db->idate($end)."'";
//         }
//         if($end){
//             $sql .= " AND date_fin BETWEEN '".$this->db->idate($start)."' AND '".$this->db->idate($end)."'";
//         }

//         $sql .= " AND fk_user IN (SELECT fk_socpeople FROM ".MAIN_DB_PREFIX."element_contact AS c , ".MAIN_DB_PREFIX."c_type_contact AS t WHERE c.fk_c_type_contact=t.rowid AND t.element='project_task')";
//         $sql .= " GROUP BY fk_user";

//         $result = $this->db->query($sql);
//         if ($result) {
//             $obj = $this->db->fetch_object($result);
//             //return number_format($obj->nb_holiday,2);
//             while ($obj = $db->fetch_object($resql)) {
//                 $data[$obj->fk_user] += $obj->nb_holiday;
//             }
//         }
//         return $data;


// $sql .= ') as holiday ON holiday.fk_task = t.r';






$sql .= ' WHERE p.entity = '.$conf->entity;
$sql .= ($sql_proj != '') ? ' AND p.rowid IN ('.$sql_proj.')' : ' AND 1<0';
$sql .= $filterprojstatus;

if (empty($user->rights->projet->all->lire)) {
	// $sql .= " AND p.rowid IN (".$db->sanitize($projectsListId).")";
}

if($sql_users){
    $sql .=' AND tc.source = "internal" AND tc.element ="project" AND ec.fk_socpeople IN ('.$sql_users.')';
}

if($search_debut && $search_fin){
	$sql .= ' AND (';
	$sql .= ' (CAST(p.dateo as date) BETWEEN "'.$db->idate($search_debut).'" AND "'.$db->idate($search_fin).'")';
	$sql .= ' OR ';
	$sql .= ' (CAST(p.datee as date) BETWEEN "'.$db->idate($search_debut).'" AND "'.$db->idate($search_fin).'")';

	if($ganttproadvanced->showprojectsevenwithoutdates) {
		$sql .= ' OR (p.datee is NULL AND p.dateo is NULL)';
	}
		$sql .= ' OR ';
		$sql .= ' (CAST(pt.dateo as date) BETWEEN "'.$db->idate($search_debut).'" AND "'.$db->idate($search_fin).'")';
		$sql .= ' OR ';
		$sql .= ' (CAST(pt.datee as date) BETWEEN "'.$db->idate($search_debut).'" AND "'.$db->idate($search_fin).'")';
		$sql .= ' OR ';
		$sql .= ' (CAST(pt.dateo as date) < "'.$db->idate($search_debut).'" AND CAST(pt.datee as date) > "'.$db->idate($search_fin).'")';
	$sql .= ')';

}

if($search_category > 0){
    $sql .= ' AND cp.fk_categorie = '.$search_category;
}
if($search_customer > 0){
    $sql .= ' AND p.fk_soc ='.$search_customer;
}
if($search_userid > 0){
    $sql .= ' AND tc.code = "PROJECTLEADER" AND tc.source = "internal" AND tc.element = "project" AND ec.fk_socpeople ='.$search_userid;
}


$sql .= ' GROUP BY p.rowid ';
$sql .= $groupby;


// ---------------------------------------------------------- TO NOT SHOW PROJECT WITHOUT ANY TASK
// $sql .= ' HAVING COUNT(pt.rowid) > 0';



} else {
	$ganttproadvanced->modify_parenttasks = 0;
	$ganttproadvanced->t_colortaskbyuser = 1;
	$ganttproadvanced->coloredbyuser = 1;
} // END IF NOT BY RESOURCE

// --------------------------------------------------------------------------------------------------------;

$sql .= ' ORDER BY typetable DESC, abs(rang) '.$sortorder.', '.$sortfield.' '.$sortorder;

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST) || 1>0) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);

	if (($page * $limit) > $nbtotalofrecords) {
		$page = 0;
		$offset = 0;
	}
}

$colordefaulttask = $ganttproadvanced->coloredbyuser ? $ganttproadvanced->colorgristask : $ganttproadvanced->defaultcolortask;

$projectids = array();

// if($search_projects > 0) $projectstatic->fetch($search_projects);

if(count($search_projects) > 0) {

	if ($action == 'pdf') {
		require_once 'ganttproadvanced_export.php';
	}
	elseif($action == 'xls'){
	    $filename = $langs->trans("ganttproadvanced").'.xls';

		require_once 'ganttproadvanced_export.php';

	    header("Content-Type: application/xls");
	    header("Content-Disposition: attachment; filename=".$filename."");
	    echo $html;
	    die(); 
	}
}

$sql .= $db->plimit($limit + 1, $offset);

// echo $sql.'<br><br>';

$resql = $db->query($sql);

$stylemarker = '';

$_data 	= '';
$_links = '';

$taskobject = new Task($db);

$nametypecontact = $ganttproadvanced->nametypecontact;

if(!$resql) {
	setEventMessages($db->lasterror().'<br>'.$sql, null, 'errors');
}



$month_full = array();
$month_short = array();
$month_veryshort = array();

$all_data = array();
$tmpusersnames = array();
$tmparrusersaffected = array();
$alldatesjalon=array();
$existjalons=array();
$existparentproject=array();
$projectwithnotask=array();
$projecttasksminmaxdates=array();
$byresourceusersids=array();

$maxdate = '';
$mindate = '';

$datesproj = array();
$durationparent = array();

$spacebetweenmarker = 6; // In Hours
$departementresource = array();
$all_data_resource = array();

$_data_resource = '';

if ($resql) {
	$i = 0;
	$num = $db->num_rows($resql);
	while ($i < min($num, $limit)) {
		$str_affect_id='';

		$obj = $db->fetch_object($resql);

		if($obj->typetable == 'project' && !isset($existparentproject[$obj->rowid])) {
			$projectwithnotask[$obj->rowid] = $obj->rowid;
		}

		
		// // -------------------------------------------------------------------------------------------------------------------- (ONLY FOR TEST) IF EMPTY DATES SET MIN / MAX TASKS DATES
		// if($obj->typetable == 'project' && !$obj->dateo && !$obj->datee && isset($projecttasksminmaxdates[$obj->rowid])) {
		// 	if(isset($projecttasksminmaxdates[$obj->projectid]['mindate'])) {
		// 		$obj->dateo = $projecttasksminmaxdates[$obj->projectid]['mindate'];
		// 	}

		// 	if(isset($projecttasksminmaxdates[$obj->projectid]['maxdate'])) {
		// 		$obj->datee = $projecttasksminmaxdates[$obj->projectid]['maxdate'];
		// 	}

		// } else {
		// 	if(!isset($projecttasksminmaxdates[$obj->projectid]['mindate']) || (isset($projecttasksminmaxdates[$obj->projectid]['mindate']) && $obj->dateo < $projecttasksminmaxdates[$obj->projectid]['mindate'])) {
		// 		$projecttasksminmaxdates[$obj->projectid]['mindate'] = $obj->dateo;
		// 	}

		// 	if(!isset($projecttasksminmaxdates[$obj->projectid]['maxdate']) || (isset($projecttasksminmaxdates[$obj->projectid]['maxdate']) && $obj->datee > $projecttasksminmaxdates[$obj->projectid]['maxdate'])) {
		// 		$projecttasksminmaxdates[$obj->projectid]['maxdate'] = $obj->datee;
		// 	}
		// }

		$data 	= array(); $links 	= array();
		$dateo = '';
		if($obj->dateo){
			$dd = dol_getdate($db->jdate($obj->dateo));
			if($dd){
				$dateo = dol_get_last_day($dd['year'], $dd['mon']);
			}
		}
		$ganttid 	= $obj->typetable.$obj->rowid;
		$end_date 	= $obj->datee ? $obj->datee : ($dateo ? $db->idate($dateo) : '');

		// $end_date 	= $obj->datee ? $obj->datee.' 23:59' : $db->idate($dateo);
		$_start 	= $obj->dateo ? strtotime($obj->dateo) : '';
		$_end 		= $end_date ? strtotime($end_date) : '';

		$duration = 0;
		$duration_hour = 0;
		if($_end && $_start) {
			$duration = $ganttproadvanced->calculateWeekdaysWithOrWithoutWeekEnd($_start, $_end);
			$duration_hour = $ganttproadvanced->calculateWeekHoursWithOrWithoutWeekEnd($_start, $_end);
		}

		$data['id'] 		= $ganttid;	
		// $data['end_date'] 	= dol_print_date($end_date, "%Y-%m-%d");
		// $data['end_date'] 	= dol_print_date($db->jdate($end_date), "%Y-%m-%d %H:%M", 'tzserver');
		$data['end_date'] 	= dol_print_date($db->jdate($end_date), "%Y-%m-%d %H:%M", 'tzserver');
		$data['ref'] 		= $obj->ref;
		$data['ref_project'] 		= $obj->ref_project;

		$data['text'] 		= $obj->title;	
		// if($obj->typetable == 'project') {
		// }


		$description = dol_htmlentitiesbr_decode($obj->description);	
		$description = str_replace('&#39;', "'", $description);

		$data['description'] = $description;
		
		$data['projectid']	= $obj->projectid;	
		$data['budget']	= $obj->budget ? price2num($obj->budget) : '';	
		$data['progress']	= $obj->progress ? ($obj->progress/100) : 0;
		$data['sortorder'] 	= 10;	
		// $data['start_date'] = dol_print_date($obj->dateo, "%Y-%m-%d");	
		$data['start_date'] = dol_print_date($db->jdate($obj->dateo), "%Y-%m-%d %H:%M", 'tzserver');	
		// $data['start_date'] = dol_print_date($db->jdate($obj->dateo), "%Y-%m-%d %H:%M", 'tzserver');	
		$data['duration'] 	= $duration;	
		$data['duration_day'] 	= $duration;	
		$data['duration_hour'] 	= $duration_hour;	
		// if($obj->typetable == 'project') $data['type'] 		= $obj->typetable;

		$data['type'] 		= $obj->typetable;
		$data['couttotal']  = $obj->couttotal ? price($obj->couttotal, '2','', 1, 1, 2) : '';
		$data['nb_comments']= $obj->nb_comments;

		// if((($end_date && $end_date > $maxdate) || !$maxdate) && !$search_maxdate) {
		// 	// $maxdate = date("Y-m-d", strtotime ($end_date ."+1 days"));
		// 	$maxdate = $db->idate($dateo);
		// }
		// if((($obj->dateo && $obj->dateo < $mindate) || !$mindate) && !$search_mindate) {
		// 	$mindate = date("Y-m-d", strtotime ($obj->dateo ."-2 days"));
		// }


		// d($data,0);

		// if($obj->ganttproadvanceddatejalon){
		// 	$tmpdate = $db->jdate($obj->ganttproadvanceddatejalon);
		// 	if($tmpdate > 0) {
		// 		$alldatesjalon[$ganttid]['date']=$obj->ganttproadvanceddatejalon;
		// 		if($ganttproadvanced->coloredbyuser) {
		// 			$stylemarker .= '.colormarker_'.$ganttid.'{background-color: '.($obj->color  ? $obj->color : $colordefaulttask).' !important} ';
		// 		} else {

		// 		}
		// 	}
		// 	// if($tmpdate > 0) {
		// 	// 	d($db->jdate($obj->ganttproadvanceddatejalon),0);
		// 	// 	d($ganttid.' - '.dol_print_date($db->jdate($obj->ganttproadvanceddatejalon), '%Y-%m-%d'),0);
		// 	// }
		// }
		// d($extrafields->attributes[$objtask->table_element]);
		if($extrafields->attributes[$objtask->table_element]['label']){
			foreach ($extrafields->attributes[$objtask->table_element]['label'] as $key => $label) {
				if($extrafields->attributes[$objtask->table_element]['enabled'][$key] && ($extrafields->attributes[$objtask->table_element]['list'][$key] == 1 || $extrafields->attributes[$objtask->table_element]['list'][$key] == 3)){
					// if($key != 'ganttproadvancedcolor' && $key != 'ganttproadvanceddatejalon' && $key != 'ganttproadvancedtyperelation'){
					if(!isset($ganttproadvanced->extrafieldstohide[$key]) && $key != 'ganttproadvanceddatejalon'){
						if(
							$extrafields->attributes[$objtask->table_element]['type'][$key] == 'select' 
							|| $extrafields->attributes[$objtask->table_element]['type'][$key] == 'sellist' 
							|| $extrafields->attributes[$objtask->table_element]['type'][$key] == 'date' 
							|| $extrafields->attributes[$objtask->table_element]['type'][$key] == 'datetime'
							|| $extrafields->attributes[$objtask->table_element]['type'][$key] == 'link'
						){

							$defaultvalue = '';
							if($trellotasksplus_enabled && $key == 'trellotasksplus_colomn') {
								$defaultvalue = $trellodefaultcolumnid;
							}


							$data[$key] = $obj->$key ? $obj->$key : $defaultvalue;
						} 
						else {
							$data[$key] = $obj->$key ? $extrafields->showOutputField($key, $obj->$key, '', $objtask->table_element) : '';
						}
					}
					if($key == 'ganttproadvanceddatejalon') $data[$key] = $obj->$key;

					if(isset($obj->$key) && $extrafields->attributes[$objtask->table_element]['type'][$key] != 'date' && $extrafields->attributes[$objtask->table_element]['type'][$key] != 'datetime') {
						$data['options_'.$key] = $extrafields->showOutputField($key, $obj->$key, '', $objtask->table_element);
					}
				}
			}
		}
		// $data['color'] 		= $obj->color;
		$data['ganttproadvancedrelatedtask'] = $obj->ganttproadvancedrelatedtask ? $obj->ganttproadvancedrelatedtask : 0;
		$data['ganttproadvancedtyperelation'] = $obj->ganttproadvancedtyperelation ? $obj->ganttproadvancedtyperelation : 0;
		$data['ganttproadvancednumberdayslate'] = $obj->ganttproadvancednumberdayslate ? $obj->ganttproadvancednumberdayslate : 0;
		$data['label_typerelation'] = ($obj->ganttproadvancedtyperelation && $obj->ganttproadvancedtyperelation != "NULL") ? dol_escape_js($ganttproadvanced->typesrelation[$obj->ganttproadvancedtyperelation]) : '';
		$data['label_relatedtask'] = $obj->label_relatedtask;
		$data['color'] 		= $ganttproadvanced->p_projectcolor;

		$data['duration_effective_time']	= !empty($obj->duration_effective) ? $obj->duration_effective : 0;	
		$data['duration_effective']	= ($obj->duration_effective) ? convertSecondToTime($obj->duration_effective, $timespentoutputformat) : '--:--';	
		
		$data['note_private'] 	= $obj->note_private;	
		$data['projectlabel'] = $obj->projectlabel;
		$data['tiersname'] = $obj->tiersname;

		$data['task_rowid'] = 0;


		$timespasse = explode(',', $obj->timespasse);
		// d('Task '.$obj->ref, 0);
		// d($timespasse, 0);
		$datatimeconsom = array();
		$daysconsombyuser = array();
		if($timespasse){
			foreach ($timespasse as $key => $linetime) {
				$arrtime = explode('::', $linetime);
				if(!empty($arrtime[0])){
					// d('id_time: '.$arrtime[0], 0);
					// d('fk_user: '.$arrtime[1], 0);
					// d('duration: '.$arrtime[2], 0);
					// d('date: '.$arrtime[3], 0);
					// d('time: '.convertSecondToTime($arrtime[2], 'allhourmin'), 0);
					$date_start = dol_print_date($db->jdate($arrtime[3]), '%d-%m-%Y %H:%i');
					$datatimeconsom[$arrtime[1]] += $arrtime[2];
					$daysconsombyuser[$arrtime[1]][] = array('date'=>$arrtime[3], 'time'=>$arrtime[2]);
				}
			}
		}

		$data['owner_id'] = 'departement_other';

		if($obj->typetable == 'project') {
			$data['typetable'] = 'project';
			$data['open'] 		= true;
			$data['parent'] 	= 0;
			$projectids[$obj->rowid] = $obj->rowid;
			$data['percentdure'] = '';

			$data['currentprojectid'] = $obj->rowid;
			$data['trellotasksplusstatus'] = '';

			$data['duration'] += $durationparent[$ganttid]['duration_day'];
			$data['duration_day'] += $durationparent[$ganttid]['duration_day'];
			$data['duration_hour'] += $durationparent[$ganttid]['duration_hour'];

			if(empty($data['start_date']) && !empty(min($datesproj[$ganttid]['start']))){
				$data['start_date'] = dol_print_date(min($datesproj[$ganttid]['start']), "%Y-%m-%d %H:%M", 'tzserver');
			}
			if(empty($data['end_date']) && !empty(min($datesproj[$ganttid]['end']))){
				$data['end_date'] = dol_print_date(max($datesproj[$ganttid]['end']), "%Y-%m-%d %H:%M", 'tzserver');
			}

		} else {

			$data['task_rowid'] = $obj->rowid;

			$percentdure = $obj->planned_workload>0 ? (round($obj->duration_effective / $obj->planned_workload * 100, 2).' %') : '';

			$data['percentdure'] = $percentdure;
			$data['typetable'] = 'task';

			$data['currentprojectid'] = $obj->projectid;
			// $total_effective[$obj->projectid] += $obj->duration_effective;
			$existparentproject[$obj->projectid] = 1;

			$parent = 'task'.$obj->fk_parent;
			if(!$obj->fk_parent) $parent = 'project'.$obj->projectid;

			$data['parent'] = $parent;

			$durationparent[$parent]['duration_day'] += $data['duration_day'];
			$durationparent[$parent]['duration_hour'] += $duration_hour;

			$datesproj[$parent]['start'][] = $_start;
			$datesproj[$parent]['end'][] = $_end;

			// $taskobject->id = $obj->rowid;
			// $taskobject->ref = $obj->ref;
			// $taskobject->label = $obj->title;
			// $taskobject->fk_statut = $obj->fk_statut;
			// $taskobject->progress = $obj->progress;
			// $taskobject->planned_workload = $obj->planned_workload;
			// $taskobject->duration_effective = $obj->duration_effective;
			// $taskobject->fk_task_parent = $obj->fk_task_parent;

			// $data['open'] 		= false;
			$data['open'] 		= true;

			// $data['note_private'] 	= $obj->note_private;	
			$data['planned_workload']	= ($obj->planned_workload != '') ? convertSecondToTime($obj->planned_workload, 'allhourmin') : '--:--';	
			// $data['duration_effective']	= ($obj->duration_effective) ? convertSecondToTime($obj->duration_effective, $timespentoutputformat) : '--:--';	

			if($obj->typetable == 'task') {
				$total_plannedworload = $ganttproadvanced->totalPlannedWorkload($obj->rowid);
				if($total_plannedworload){
					$data['planned_workload'] = $total_plannedworload ? convertSecondToTime($total_plannedworload, 'allhourmin') : '--:--';
				}

				if(!$ganttproadvanced->modify_parenttasks) {

					$total_progress = $ganttproadvanced->totalProgress($obj->rowid); // To be optimised
					if($total_progress['total'] && $total_progress['nb']){
						$newprogress = ($total_progress['total']/$total_progress['nb']);
						$data['progress'] = $newprogress ? ($newprogress/100) : 0;
					}
				}
			}

        	$data['color'] = $obj->color  ? $obj->color : $colordefaulttask;

        	$contacts_data = array();

        	$data['affected_nameuser'] = '';
        	$str_affect_id='';
			
			$cd = $obj->contacts_detail;
			$tmarr = explode(",", $cd);

			$affect_id = 0;

			$color = '';

			$hascontacttype = 0;
				
			$data['affected_user'] = 0;

			if($ganttproadvanced->coloredbyuser) {
				$data['color'] = $ganttproadvanced->colorgristask;
			}

			if(empty($tmarr)){

				if(empty($departementresource['departement_other'])){
					$departementresource['departement_other'] = 1;
					$data_resource = array('id'=>'departement_other', 'text'=>$langs->trans('NotDefined'), 'parent'=>null);
					$all_data_resource[] = $data_resource;
					$_data_resource .= json_encode($data_resource).',';
				}
			}

			foreach ($tmarr as $tmpk => $c_detail) {
				
				if(!$c_detail) continue;
				
				$d = explode("::", $c_detail);
				// $d[0] : Type contact
				// $d[1] : User id
				// $d[2] : Color Hex
				// $d[3] : User Name
				// $d[4] : Type Code
				// $d[5] : Département

				if(!$d[0]) continue;
				
				$departement = $d[5];


				$data['departement'] = $departement ? $userextrafields->showOutputField('cpsdepartment', $departement, '', $userstatic->table_element) : $langs->trans('NotDefined');
				$data['id_departement'] = $departement ? $departement : 'other';




				$id_departement = $data['id_departement'];
				if(empty($departementresource['departement_'.$id_departement]) && !empty($d[3])){
					$departementresource['departement_'.$id_departement] = 1;
					$data_resource = array('id'=>'departement_'.$id_departement, 'text'=>$data['departement'], 'parent'=>null);
					$all_data_resource[] = $data_resource;
					$_data_resource .= json_encode($data_resource).',';
				}

				if($ganttproadvanced->byressource_type_contacts == 'per_principal_contributeur' && !empty($d[3])){
					if(empty($departementresource['contact_'.$d[1]])){
						$id_departemnt = $data['id_departement'];

						$data_resource = array('id'=>'contact_'.$d[1], 'text'=>(!empty($d[3]) ? $d[3] : ($langs->trans('NotDefined'))), 'parent'=>'departement_'.$id_departemnt);
						$_data_resource .= json_encode($data_resource).',';
						$all_data_resource[] = $data_resource;
						$departementresource['contact_'.$d[1]] = 1;
					}
				}

				// -------------------------------------------------------------------------------------------------- Affected user
				if($ganttproadvanced->coloredbyuser) {

					$tmpcol = (isset($d[2]) && $d[2]) ? '#'.$d[2] : $ganttproadvanced->colorgristask;


					if($d[0] == $ganttproadvanced->t_typecontact) {

						if($sql_users && in_array($d[1], $search_affecteduser) || !$sql_users){
							$data['color'] = $tmpcol;
						}

						$affect_id = (int) $d[1];
						$data['affected_user'] = $affect_id;


						$users_tasks[$affect_id][$obj->rowid] = $obj->rowid;

						if($d[3]) {
							// $nameofresource = $d[3];
			            	$data['affected_fullnameuser'] = $d[3];

							$data['affected_nameuser'] .= '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$affect_id.'" target="_blank">';
			            	$data['affected_nameuser'] .= img_picto($langs->trans('User'), 'user', '').' ';
			            	$data['affected_nameuser'] .= $d[3];
			            	$data['affected_nameuser'] .= '</a>';

			            	if(empty($departementresource['contact_'.$d[1]])){
								$id_departemnt = $data['id_departement'];
								$data_resource = array('id'=>'contact_'.$d[1], 'text'=>(!empty($d[3]) ? $d[3] : ($langs->trans('NotDefined'))), 'parent'=>'departement_'.$id_departemnt);
								$_data_resource .= json_encode($data_resource).',';
								$all_data_resource[] = $data_resource;
								$departementresource['contact_'.$d[1]] = 1;
							}
			            	// $data['affected_nameuser'] .= ' <input type="text" class="colorthumb" disabled="" style="background-color: '.$data['color'].'" value="'.$data['color'].'">';
						}

						// $data['type_contact_name'] = $d[4] ? $d[4] : $nametypecontact;

						// $alldatesjalon[$ganttid]['user']=$affect_id;

						$hascontacttype = 1;
						// break;
					}
				}

				// -------------------------------------------------------------------------------------------------- Contacts
				if($d[1] && $d[1] != $data['affected_user'] && $d[4] != $ganttproadvanced->t_typecontact){
					$str_affect_id .= $d[1].',';
				}

				if(!empty($d[1]) && !empty($d[3])) { // 1 : rowid - 3 : User fullname
					$tmpusersnames[$d[1]] = $d[3];
				}

			}



			if($obj->ganttproadvanceddatejalon){
				$tmpdate = $db->jdate($obj->ganttproadvanceddatejalon);
				if($tmpdate > 0) {


					// if(($obj->ganttproadvanceddatejalon > $maxdate || !$maxdate) && !$search_maxdate) {
					// 	$maxdate = date("Y-m-d", strtotime ($obj->ganttproadvanceddatejalon ."+1 days"));
					// }
					// if(($obj->ganttproadvanceddatejalon < $mindate || !$mindate) && !$search_mindate) {
					// 	$mindate = date("Y-m-d", strtotime ($obj->ganttproadvanceddatejalon ."-2 days"));
					// }

					$alldatesjalon[$ganttid]['user']=$affect_id;

					if(!isset($existjalons[$obj->ganttproadvanceddatejalon])) {
						$alldatesjalon[$ganttid]['date']=$obj->ganttproadvanceddatejalon;
						$existjalons[$obj->ganttproadvanceddatejalon] = $spacebetweenmarker;
					} else {
						$alldatesjalon[$ganttid]['date']=date("Y-m-d H:s", strtotime ($obj->ganttproadvanceddatejalon ."+".(int)$existjalons[$obj->ganttproadvanceddatejalon]." hours"));
						$existjalons[$obj->ganttproadvanceddatejalon] += $spacebetweenmarker;
					}


					if($ganttproadvanced->coloredbyuser) {
						if($affect_id && !isset($tmparrusersaffected[$affect_id])) {
							$stylemarker .= 'body .colormarker_user'.$affect_id.'{background-color: '.$data['color'].' !important}'."\n";
							$tmparrusersaffected[$affect_id] = $affect_id;
						}
					} else {
						$stylemarker .= 'body .colormarker_'.$ganttid.'{background-color: '.$data['color'].' !important}'."\n";
					}
				}
				// if($tmpdate > 0) {
				// 	d($db->jdate($obj->ganttproadvanceddatejalon),0);
					// d($ganttid.' - '.dol_print_date($db->jdate($obj->ganttproadvanceddatejalon), '%Y-%m-%d'),0);
				// }
			}

			
			$str_affect_id = $str_affect_id ? substr($str_affect_id, 0, -1) : '';
			$data['contacts'] = $str_affect_id;
			// $links['color'] 	= '#f39c12';



			if($trellotasksplus_enabled) {
				$data['trellotasksplusstatus'] = $obj->trellotasksplusstatus ? $obj->trellotasksplusstatus : $trellodefaultcolumnlabel;
			}

		}

		if($num == 1) {
			// $data['type'] 		= 'task';
		} 
		$data['bar_height'] = '15';
		// if($obj->typetable == 'project' && !isset($existparentproject[$obj->rowid])) {
		if($obj->typetable == 'project') {
			$data['type'] = 'task';
			$projectwithnotask[$obj->rowid] = $obj->rowid;
			$data['bar_height'] = '21';
		}


		// -------------------------------------------------------------------------------------------------- Par Ressource Contributeurs
		// $taskalready_hascontributor = false;

		if($obj->typetable == 'task' && $viewmode == 'byresource' && $ganttproadvanced->byressource_type_contacts == 'per_principal_contributeur') {
			$contactscontributors = (!empty($data['contacts'])) ? explode(',', $data['contacts']) : array();
			// d($contactscontributors,0);

			foreach ($contactscontributors as $tmpkey => $contact_user_id) {
				$origindata = $data;

				$parent = 'project_contributeur_'.$contact_user_id;

				// d('Task: '.$data['ref'], 0);
				if(!empty($datatimeconsom[$contact_user_id])){
					$data['duration_effective_time']	= empty($datatimeconsom[$contact_user_id]) ? $datatimeconsom[$contact_user_id] : 0;	
					$data['duration_effective']	= convertSecondToTime($datatimeconsom[$contact_user_id], $timespentoutputformat);
					$data['daysconsombyuser'] = !empty($daysconsombyuser[$contact_user_id]) ? $daysconsombyuser[$contact_user_id] : array();
				}else{
					$data['duration_effective'] = '--:--';
				}

				if(!isset($byresourceusersids[$contact_user_id])) {
					$tmpdata = $data;
					$tmpdata['id'] 		= $parent;	
					// $tmpdata['ref'] 		= isset($data['affected_nameuser']) ? $data['affected_nameuser'] : $nameofresource;
					// $tmpdata['ref'] 		= !empty($data['affected_nameuser']) ? $data['affected_nameuser'] : $langs->trans('User').' '.mb_strtolower($langs->trans('NotDefined'));
					$tmpdata['ref'] 		= !empty($tmpusersnames[$contact_user_id]) ? $tmpusersnames[$contact_user_id] : ($langs->trans('NotDefined'));
					$tmpdata['text'] 		= '';
					$tmpdata['color'] = $ganttproadvanced->p_projectcolor;
					$tmpdata['typetable'] = 'project';
					$tmpdata['open'] 		= true;
					$tmpdata['parent'] 	= 0;
					$tmpdata['percentdure'] = '';
					$tmpdata['contacts'] = '';
					$tmpdata['trellotasksplusstatus'] = '';
					$tmpdata['planned_workload'] = '';
					$tmpdata['percentdure'] = '';
					$tmpdata['duration_effective'] = '';
					$tmpdata['note_private'] = '';
					$tmpdata['type'] = 'task';
					$tmpdata['bar_height'] = '21';
					$tmpdata['owner_id'] = 'contact_'.$contact_user_id;

					if(!empty($datatimeconsom[$contact_user_id])){
						$tmpdata['duration_effective_time']	= empty($datatimeconsom[$contact_user_id]) ? $datatimeconsom[$contact_user_id] : 0;	
						$tmpdata['duration_effective']	= convertSecondToTime($datatimeconsom[$contact_user_id], $timespentoutputformat);
						$tmpdata['daysconsombyuser'] = !empty($daysconsombyuser[$contact_user_id]) ? $daysconsombyuser[$contact_user_id] : array();
					}

					$byresourceusersids[$contact_user_id] = $contact_user_id;
					$all_data[] = $tmpdata;

					$data['owner_id'] = 'contact_'.$contact_user_id;
					$_data 	.= json_encode($tmpdata).',';
				}

				$origindata['parent'] = $parent;
				$origindata['id'] = $origindata['id']."_".$contact_user_id;
				$all_data[] = $origindata;

				$_data 	.= json_encode($origindata).',';

			}
		}

		// -------------------------------------------------------------------------------------------------- Par Ressource Principal
		if($obj->typetable == 'task' && $viewmode == 'byresource') {


			$isuseraffected = (isset($data['affected_user'])) ? (int) $data['affected_user'] : 0;
			$parent = 'project'.$isuseraffected;

			$data['daysconsombyuser'] = !empty($daysconsombyuser[$isuseraffected]) ? $daysconsombyuser[$isuseraffected] : array();
			$data['duration_effective_time']	= !empty($datatimeconsom[$isuseraffected]) ? $datatimeconsom[$isuseraffected] : 0;	
			$data['duration_effective']	= ($datatimeconsom[$isuseraffected]) ? convertSecondToTime($datatimeconsom[$isuseraffected], $timespentoutputformat) : '--:--';	
			
			if(!isset($byresourceusersids[$isuseraffected])) {
				if(
					($ganttproadvanced->byressource_type_contacts == 'per_principal') 
					|| 
					($ganttproadvanced->byressource_type_contacts == 'per_principal_contributeur' && !empty($data['affected_nameuser']))
				) 
				{
					$tmpdata = $data;
					$tmpdata['id'] 		= $parent;	
					// $tmpdata['ref'] 		= isset($data['affected_nameuser']) ? $data['affected_nameuser'] : $nameofresource;
					// $tmpdata['ref'] 		= !empty($data['affected_nameuser']) ? $data['affected_nameuser'] : $langs->trans('User').' '.mb_strtolower($langs->trans('NotDefined'));
					$tmpdata['ref'] 		= !empty($data['affected_nameuser']) ? $data['affected_nameuser'] : ($langs->trans('NotDefined'));
					$tmpdata['text'] 		= '';
					$tmpdata['color'] = $ganttproadvanced->p_projectcolor;
					$tmpdata['typetable'] = 'project';
					$tmpdata['open'] 		= true;
					$tmpdata['parent'] 	= 0;
					$tmpdata['percentdure'] = '';
					$tmpdata['contacts'] = '';
					$tmpdata['trellotasksplusstatus'] = '';
					$tmpdata['planned_workload'] = '';
					$tmpdata['percentdure'] = '';
					$tmpdata['duration_effective'] = '';
					$tmpdata['note_private'] = '';
					$tmpdata['type'] = 'task';
					$tmpdata['bar_height'] = '21';
					$tmpdata['owner_id'] = 'contact_'.$isuseraffected;

					if(!empty($datatimeconsom[$isuseraffected])){
						$tmpdata['daysconsombyuser'] = !empty($daysconsombyuser[$isuseraffected]) ? $daysconsombyuser[$isuseraffected] : array();
						$tmpdata['duration_effective_time']	= !empty($datatimeconsom[$isuseraffected]) ? $datatimeconsom[$isuseraffected] : 0;	
						$tmpdata['duration_effective']	= ($datatimeconsom[$isuseraffected]) ? convertSecondToTime($datatimeconsom[$isuseraffected], $timespentoutputformat) : '--:--';	
					}
					$byresourceusersids[$isuseraffected] = $isuseraffected;
					$all_data[] = $tmpdata;

					$_data 	.= json_encode($tmpdata).',';

					// if(empty($departementresource['contact_'.$isuseraffected])){
					// 	$id_departemnt = $obj->cpsdepartment>0 ? $obj->cpsdepartment : 'other';
					// 	$data_resource = array('id'=>'contact_'.$isuseraffected, 'text'=>(!empty($data['affected_fullnameuser']) ? $data['affected_fullnameuser'] : ($langs->trans('NotDefined'))), 'parent'=>'departement_'.$id_departemnt);
					// 	$all_data_resource[] = $data_resource;
					// 	$_data_resource .= json_encode($data_resource).',';
					// 	$departementresource['contact_'.$isuseraffected] = 1;
					// }
				}
			}

			$data['owner_id'] = $isuseraffected ? 'contact_'.$isuseraffected : 'departement_other';
			$data['parent'] = $parent;

		}
		// -------------------------------------------------------------------------------------------------- 
		

		if(!$ganttproadvanced->use_task_dependencies) {
			$links['id'] 		= $ganttid;	
			$links['source'] 	= $parent;	
			$links['target'] 	= $ganttid;	
			$links['type'] 		= 1;
		} else {
			if($obj->ganttproadvancedrelatedtask > 0) {
				$links['id'] 		= $ganttid;	
				$links['source'] 	= 'task'.$obj->ganttproadvancedrelatedtask;	
				$links['target'] 	= $ganttid;	
				$links['type'] 		= isset($ganttproadvanced->typesganttrelation[$obj->ganttproadvancedtyperelation]) ? $ganttproadvanced->typesganttrelation[$obj->ganttproadvancedtyperelation] : 0;
			}
		}


		// $data['oldcopy']=$data;
		$_links .= json_encode($links).',';
		$_data 	.= json_encode($data).',';
		// if($obj->rowid == 3) d($data);
		// if($obj->typetable == 'project') d($data ,false);
		$all_data[] = $data;

		$i++;

	}
} else {
	// print_r($db->lasterror());
}

$_baslines = '{ "id": 1, "task_id": "task24", "start_date": "2025-04-15 00:00:00", "duration": 1, "end_date": "2025-04-15 00:00:00" },{ "id": 2, "task_id": "task24", "start_date": "2025-04-15 00:00:00", "duration": 1, "end_date": "2025-04-16 00:00:00" },{ "id": 3, "task_id": "task24", "start_date": "2025-04-15 00:00:00", "duration": 1, "end_date": "2025-04-16 00:00:00" },{ "id": 4, "task_id": "task26", "start_date": "2025-07-06 00:00:00", "duration": 1, "end_date": "2025-07-10 00:00:00" },{ "id": 5, "task_id": "task26", "start_date": "2025-07-11 00:00:00", "duration": 1, "end_date": "2025-07-12 00:00:00" },{ "id": 6, "task_id": "task26", "start_date": "2025-07-13 00:00:00", "duration": 1, "end_date": "2025-07-14 00:00:00" },{ "id": 7, "task_id": "task26", "start_date": "2025-07-15 00:00:00", "duration": 3, "end_date": "2025-07-18 00:00:00" },{ "id": 8, "task_id": "task26", "start_date": "2025-07-19 00:00:00", "duration": 1, "end_date": "2025-07-20 00:00:00" },{ "id": 9, "task_id": "task27", "start_date": "2025-07-10 00:00:00", "duration": 3, "end_date": "2025-07-13 00:00:00" },{ "id": 10, "task_id": "task27", "start_date": "2025-07-14 00:00:00", "duration": 2, "end_date": "2025-07-16 00:00:00" },{ "id": 11, "task_id": "task27", "start_date": "2025-07-16 00:00:00", "duration": 1, "end_date": "2025-07-17 00:00:00" },{ "id": 13, "task_id": "task27", "start_date": "2025-07-03 00:00:00", "duration": 1, "end_date": "2025-07-04 00:00:00" },{ "id": 14, "task_id": "task27", "start_date": "2025-07-05 00:00:00", "duration": 1, "end_date": "2025-07-06 00:00:00" },{ "id": 22, "task_id": "task25", "start_date": "2025-07-11 00:00:00", "duration": 1, "end_date": "2025-07-12 00:00:00" },{ "id": 23, "task_id": "task25", "start_date": "2025-07-12 00:00:00", "duration": 1, "end_date": "2025-07-13 00:00:00" },{ "id": 24, "task_id": "task25", "start_date": "2025-07-13 00:00:00", "duration": 1, "end_date": "2025-07-14 00:00:00" },{ "id": 25, "task_id": "task25", "start_date": "2025-07-14 00:00:00", "duration": 1, "end_date": "2025-07-15 00:00:00" },{ "id": 26, "task_id": "task25", "start_date": "2025-07-15 00:00:00", "duration": 1, "end_date": "2025-07-16 00:00:00" },{ "id": 27, "task_id": "task25", "start_date": "2025-07-16 00:00:00", "duration": 1, "end_date": "2025-07-17 00:00:00" },{ "id": 28, "task_id": "task25", "start_date": "2025-07-17 00:00:00", "duration": 1, "end_date": "2025-07-18 00:00:00" },{ "id": 29, "task_id": "task25", "start_date": "2025-07-18 00:00:00", "duration": 1, "end_date": "2025-07-19 00:00:00" },{ "id": 30, "task_id": "task25", "start_date": "2025-07-19 00:00:00", "duration": 1, "end_date": "2025-07-20 00:00:00" },{ "id": 31, "task_id": "task25", "start_date": "2025-07-20 00:00:00", "duration": 1, "end_date": "2025-07-21 00:00:00" },{ "id": 32, "task_id": "task25", "start_date": "2025-07-21 00:00:00", "duration": 1, "end_date": "2025-07-22 00:00:00" },{ "id": 33, "task_id": "task25", "start_date": "2025-07-22 00:00:00", "duration": 1, "end_date": "2025-07-23 00:00:00" },{ "id": 34, "task_id": "task27", "start_date": "2025-07-08 00:00:00", "duration": 1, "end_date": "2025-07-09 00:00:00" }';

// d($all_data, 0);
// foreach ($all_data as $key => $value) {
// 	if($value['typetable'] == 'project'){
// 		$value['duration_effective'] = isset($total_effective[$value['projectid']]) ? convertSecondToTime($total_effective[$value['projectid']], $timespentoutputformat) : '--:--';
// 		$all_data[$key]['duration_effective'] = isset($total_effective[$value['projectid']]) ? convertSecondToTime($total_effective[$value['projectid']], $timespentoutputformat) : '--:--';
// 	}

// 	$_data 	.= json_encode($value).',';

// }
// $nowdate = date("Y-m-d");

// if($mindate && $mindate > $nowdate) $mindate = date("Y-m-d", strtotime ($nowdate ."-2 days"));
// if($maxdate && $maxdate < $nowdate) $maxdate = date("Y-m-d", strtotime ($nowdate ."+2 days"));

// if(!$search_mindate) $search_mindate = $mindate;
// if(!$search_maxdate) $search_maxdate = $maxdate;

// $users_tasks = base64_encode(json_encode($users_tasks));

// d('search_mindate : '.$search_mindate,0);
// d('search_maxdate : '.$search_maxdate,0);
// d(($all_data),0);

// d(($alldatesjalon));
// d($users_tasks);

// // ------------------------------------------------------------------------------- For Pagination ...
// if(!empty($search_projects) && !isset($projectids[$search_projects])) {
// 	$data 	= array();

// 	$ganttid 	= 'project'.$search_projects;
// 	$end_date 	= $projectstatic->datee ? $projectstatic->datee : date("Y-m-d", strtotime ($projectstatic->dateo ."+1 days"));
// 	$_start 	= strtotime($projectstatic->dateo);
// 	$_end 		= strtotime($end_date);
// 	$datediff 	= $_end-$_start;
// 	$duration 	= round($datediff / (60 * 60 * 24));

// 	$data['id'] 		= $ganttid;	
// 	// $data['end_date'] 	= dol_print_date($end_date, "%Y-%m-%d");
// 	$data['text'] 		= $projectstatic->label;	
// 	$data['projectid']	= $search_projects;	
// 	$data['progress']	= $projectstatic->progress ? ($projectstatic->progress/100) : 0;
// 	$data['sortorder'] 	= 10;	
// 	$data['start_date'] = dol_print_date($projectstatic->dateo, "%d-%m-%Y");	
// 	$data['duration'] 	= $duration;	
// 	// if($projectstatic->typetable == 'project') $data['type'] 		= $projectstatic->typetable;
// 	$data['type'] 		= 'project';


// 	$data['open'] 		= true;
// 	$data['parent'] 	= 0;

// 	$_data 	.= json_encode($data).',';
// }

// $param = '&id='.$project->id;
// $param .= ($search_category > 0) ? '&search_category='.$search_category : '';
// $param .= $search_projects ? '&search_projects='.$search_projects : '';

// $maximumprojecttosendurl = 200;

// $totproject = count($search_projects);
// if (!empty($search_projects) && (int) $totproject <= $maximumprojecttosendurl) {
// 	foreach ($search_projects as $srchobj) {
// 		$param .= '&search_projects[]='.urlencode($srchobj);
// 	}
// }
// if (!empty($search_affecteduser)) {
// 	foreach ($search_affecteduser as $srchobj) {
// 		$param .= '&search_affecteduser[]='.urlencode($srchobj);
// 	}
// }
// $param .= $start > 0 ? '&year_start='.$start : '';
// $param .= $end > 0 ? '&year_end='.$end : '';


$users_tasks = base64_encode(json_encode($users_tasks));

$maximumprojecttosendurl = 200;

$param = '';

$param .= ($search_customer > 0) ? '&search_customer='.$search_customer : '';
$param .= ($search_userid > 0) ? '&search_userid='.$search_userid : '';
$param .= ($search_category > 0) ? '&search_category='.$search_category : '';
$param .= $search_scale ? '&search_scale='.$search_scale : '';
$param .= $search_status ? '&search_status='.$search_status : '';
// $param .= ($sql_proj && (int) $totproject <= $maximumprojecttosendurl) ? '&sql_proj='.$sql_proj : '';
$param .= $users_tasks ? '&users_tasks='.$users_tasks : '';
$param .= $search_maxdate ? '&search_maxdate='.$search_maxdate : '';
$param .= $action ? '&action='.$action : '';

$param .= '&debutyear='.$debutyear;
$param .= '&debutmonth='.$debutmonth;
$param .= '&finmonth='.$finmonth;
$param .= '&finyear='.$finyear;

if (!empty($search_projects) && (int) count($search_projects) <= $maximumprojecttosendurl) {
	$param .= '&search_projects[]=' . implode('&search_projects[]=', $search_projects);
}
if (!empty($search_affecteduser)) {
	$param .= '&search_affecteduser[]=' . implode('&search_affecteduser[]=', $search_affecteduser);
}
if (!empty($search_tasktype)) {
	$param .= '&search_tasktype[]=' . implode('&search_tasktype[]=', $search_tasktype);
}
if (!empty($search_tagstrello)) {
	$param .= '&search_tagstrello[]=' . implode('&search_tagstrello[]=', $search_tagstrello);
}

$tosendinurl = $param;


$param .= '&sortfield='.$sortfield;
$param .= '&sortorder='.$sortorder;
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}

if(!$ganttproadvanced->default_hide_leftmenu)
	$conf->dol_hide_leftmenu = 1;

$modname = $langs->trans('ganttproadvanced');

if((isset($conf->trellotasks) && $conf->trellotasks->enabled) || $trellotasksplus_enabled){
	$modname = $langs->trans('Tasks');
}


$moreheadcss = '';
$moreheadjs = '';

//$morejs=array();
// $morejs = array('includes/jquery/plugins/blockUI/jquery.blockUI.js', 'core/js/blockUI.js'); // Used by ecm/tpl/enabledfiletreeajax.tpl.pgp
// if (empty($conf->global->MAIN_ECM_DISABLE_JS)) {
// 	$morejs[] = "includes/jquery/plugins/jqueryFileTree/jqueryFileTree.js";
// }

// $moreheadjs .= '<script type="text/javascript">'."\n";
// $moreheadjs .= 'var indicatorBlockUI = \''.DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif".'\';'."\n";
// // $moreheadjs .= '$(document).ready(function() { 
// // 	$.pleaseBePatient("'.$langs->trans('PleaseBePatient').'"); 
// // });';
// $moreheadjs .= '</script>'."\n";

llxHeader($moreheadcss.$moreheadjs, $modname, '', '', '', '', array(), '', 0, 0);

print '<script src="https://docs.dhtmlx.com/gantt/codebase/dhtmlxgantt.js?v=9.0.13"></script>';
print '<link rel="stylesheet" href="https://docs.dhtmlx.com/gantt/codebase/dhtmlxgantt.css?v=9.0.13">';
print '<link rel="stylesheet" href="https://docs.dhtmlx.com/gantt/samplescommon/controls_styles.css?v=9.0.13">';
print '<script src="https://docs.dhtmlx.com/gantt/samples/common/data_baselines.js?v=9.0.13"></script>';


print '	
	<div class="gantt_control">
		<label title="Change how the baselines will be rendered">Baselines displayed on:</label>
		<select class="reorder_mode gantt_input_styled" onchange=changeBaselineRender(this.value)>
			<option value="">Off</option>
			<option value="taskRow">Same row</option>
			<option value="separateRow" selected>Separate row</option>
			<option value="individualRow">Individual rows</option>
		</select>
		<label title="Change the height of a task bar">Change task bar height:</label>
		<input type=number class="gantt_input_styled" value="20" oninput="changeBarHeight(this.value)" onwheel="changeBarHeight(this.value)">
	</div>

	<div id="gantt_here" style="width:100%; height:calc(100vh - 52px);"></div>';
?>

<script>
	gantt.config.date_format = "%Y-%m-%d %H:%i:%s";

	gantt.config.lightbox.milestone_sections = gantt.config.lightbox.sections = [
		{ name: "description", height: 38, map_to: "text", type: "textarea", focus: true },
		{ name: "time", type: "duration", map_to: "auto" },
		{ name: "baselines", height: 100, type: "baselines", map_to: "baselines" },
	];

	gantt.config.resize_rows = true;
	gantt.config.row_height = 30;
	gantt.config.min_task_grid_row_height = 10;
	gantt.config.open_split_tasks = true;

	gantt.config.baselines.render_mode = "separateRow";

	function changeBaselineRender(value){
		gantt.config.baselines.render_mode = value;
		gantt.batchUpdate(function () {
			gantt.eachTask(function(task){
				// function to recalculate row height
				gantt.adjustTaskHeightForBaselines(task)
			});
		});
	}

	function changeBarHeight(value){
		var id = gantt.getSelectedId();
		if (!id){
				gantt.message("Select a task!")
				return;
		}
		var task = gantt.getTask(id);
		task.bar_height = +value;
		gantt.adjustTaskHeightForBaselines(task);
		gantt.render();
	}

	gantt.templates.task_class = function (start, end, task) {
		if (task.planned_end) {
			var classes = ['has-baseline'];
			if (end.getTime() > task.planned_end.getTime()) {
				classes.push('overdue');
			}
			return classes.join(' ');
		}
	};

	gantt.templates.rightside_text = function (start, end, task) {
		if (task.planned_end) {
			if (end.getTime() > task.planned_end.getTime()) {
				var overdue = Math.ceil(Math.abs((end.getTime() - task.planned_end.getTime()) / (24 * 60 * 60 * 1000)));
				var text = "<b>Overdue: " + overdue + " days</b>";
				return text;
			}
		}
	};
	var data = {
		data: [ <?php echo $_data; ?> ],
		links: [ <?php echo $_links; ?> ],
		baselines: [ <?php echo $_baslines; ?> ]
	};

	console.log(data);
	console.log(taskData);

	gantt.init("gantt_here");
	gantt.parse(data);

</script>

<script>
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	ga('create', 'UA-11031269-1', 'auto');
	ga('send', 'pageview');
</script>