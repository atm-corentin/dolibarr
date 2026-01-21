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
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';

dol_include_once("/ganttproadvanced/lib/ganttproadvanced.lib.php");
dol_include_once("/ganttproadvanced/class/ganttproadvanced.class.php");
dol_include_once("/ganttproadvanced/class/ganttproadvancedutils.class.php");
dol_include_once('/ganttproadvanced/core/modules/modganttproadvanced.class.php');

$langs->loadLangs(array('admin', 'projects', 'mails', 'ganttproadvanced@ganttproadvanced'));

if (!$conf->projet->enabled || !$conf->ganttproadvanced->enabled || empty($user->rights->ganttproadvanced->lire)) {
	accessforbidden();
}


// show error for test

// ini_set('display_startup_errors', 1);
// ini_set('display_errors', 1);
// error_reporting(-1);


// ------------------------------------------------------------------------------------------- OBJECTS
$project 		= new Project($db);
$projectstatic 	= new Project($db);
$objtask 		= new Task($db);
$taskstatic 	= new Task($db);
$userstatic     = new User($db);
$extrafields 	= new ExtraFields($db);
$userextrafields 	= new ExtraFields($db);
$ganttproadvanced  		= new ganttproadvanced($db);
$ganttproadvancedutils  = new ganttproadvancedutils($db);
$form 			= new Form($db);
$formproject 	= new FormProjets($db);
$formcompany   	= new FormCompany($db);
$formother 		= new FormOther($db);

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

$viewmode = 'byresource';
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

$tabviewhour = 1;

$returned = $ganttproadvanced->ganttproadvancedselectUsersThatSignedAsTasksContacts($sql_proj, $sql_tasktypes, $search_affecteduser);
$selectusers_html = $returned['html'];
$selectusers_array = $returned['array'];

if(!$search_affecteduser){
	// $ids_users = array_keys($selectusers_array);
	// $search_affecteduser = [0=>$ids_users[0]];
}

$data_test = array();

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
$sql .= ' , t.fk_statut, ef.ganttproadvancedcolor as color, "task" as typetable, rp.title as projectlabel, s.nom as tiersname';

if($ganttproadvanced->showhoursingantt){
	$sql .= ', t.dateo as dateo, t.datee as datee';
}else{
	$sql .= ', DATE(t.dateo) as dateo, DATE(t.datee) as datee';
}

if($trellotasksplus_enabled) {
	$sql .= ' , trcol.label as trellotasksplusstatus';
}

$sql .= ', rp.ref as ref_projet';

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


$sql .= ', ( SELECT COUNT(c.rowid) FROM '.MAIN_DB_PREFIX.'kanban_commnts as c WHERE t.rowid = c.fk_task) as nb_comments ';

$sql .= ", GROUP_CONCAT(DISTINCT COALESCE(tp.rowid, ''),'::',  COALESCE(tp.fk_user, ''),'::', COALESCE(tp.".$ganttproadvanced->column_task_duration.", '') SEPARATOR ',') AS timespasse ";


$sql .= ', (';

	$sql .= "SELECT GROUP_CONCAT(fk_user,'::', rowid,'::',date_debut,'::',date_fin,'::',halfday) as nb_holida";
    $sql .= " FROM ".MAIN_DB_PREFIX."holiday";
    $sql .= " WHERE entity IN (".getEntity('holiday').")";
    // $sql .= " WHERE fk_user = ".(int) $user_id;
    $sql .= " AND statut IN (".Holiday::STATUS_VALIDATED.",".Holiday::STATUS_APPROVED.")";

	$sql .= ' AND (';
		$sql .= ' (CAST(date_debut as date) BETWEEN CAST(t.dateo as date) AND CAST(t.datee as date))';
		$sql .= ' OR (CAST(date_fin as date) BETWEEN CAST(t.dateo as date) AND CAST(t.datee as date))';
		$sql .= ' OR (CAST(date_debut as date) < CAST(t.dateo as date) AND CAST(date_fin as date) > CAST(t.datee as date))';
	$sql .= ')';

    $sql .= " AND fk_user IN (SELECT ec.fk_socpeople FROM ".MAIN_DB_PREFIX."element_contact AS ec , ".MAIN_DB_PREFIX."c_type_contact AS tce WHERE ec.fk_c_type_contact=tce.rowid AND tce.element='project_task' AND ec.element_id=t.rowid)";
$sql .= ') as holiday';


$sql .= ' FROM '.MAIN_DB_PREFIX.'projet_task as t';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task_extrafields as ef on (t.rowid = ef.fk_object)';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_task as rt on (ef.ganttproadvancedrelatedtask = rt.rowid)';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.$ganttproadvanced->table_task_time.' as tm on (t.rowid = tm.'.$ganttproadvanced->column_fk_task.')';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as rp on (rp.rowid = t.fk_projet)';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s on (s.rowid = rp.fk_soc)';

$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as elem ON (t.rowid = elem.element_id AND elem.fk_c_type_contact IN (SELECT rowid FROM ".MAIN_DB_PREFIX."c_type_contact WHERE element='project_task')) ";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc ON (tc.rowid = elem.fk_c_type_contact AND tc.element='project_task') ";
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
	$sql .= ')';

}


$sql .= ' AND (t.datee is NOT NULL)';
$sql .= ' AND (t.dateo is NOT NULL)';

if($search_category>0) $sql .= ' AND t.fk_projet IN (SELECT cp.fk_project FROM '.MAIN_DB_PREFIX.'categorie_project as cp WHERE cp.fk_categorie='.$search_category.')';

if($trellotasksplus_enabled)
	if($sql_tagstrello) $sql .= ' AND (ef.trellotasksplus_colomn IN ('.$sql_tagstrello.') '.(in_array($trellodefaultcolumnid, $search_tagstrello) ? ' OR ef.trellotasksplus_colomn IS NULL OR ef.trellotasksplus_colomn=0 OR ef.trellotasksplus_colomn=""' : '').')';


$sql .= ' GROUP BY t.rowid ';
$sql .= $groupby;



$ganttproadvanced->modify_parenttasks = 0;
$ganttproadvanced->t_colortaskbyuser = 1;
$ganttproadvanced->coloredbyuser = 1;

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
	    // $ganttproadvanced->ganttproadvancedExportToExcel($filename, $html);
	    // exit();
	}
}

$sql .= $db->plimit($limit + 1, $offset);

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
$dt_departement=array();
$datatimeconsombyuser = array();
$idsparentsdata=array();
$data_time=array();

$maxdate = '';
$mindate = '';

$spacebetweenmarker = 6; // In Hours

if ($resql) {
	$i = 0;
	$j = 0;
	$num = $db->num_rows($resql);
	while ($i < min($num, $limit)) {
		$str_affect_id='';

		$obj = $db->fetch_object($resql);

		if($obj->typetable == 'project' && !isset($existparentproject[$obj->rowid])) {
			$projectwithnotask[$obj->rowid] = $obj->rowid;
		}

		// d('ref_projet: '.$obj->ref_projet, 0);

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

		$arrholiday = array();
		$dataholiday = array();
		
		if(!empty($obj->holiday)){
			$holidays = explode(',', $obj->holiday);
			if($holidays){
				foreach ($holidays as $key => $idsholiday) {
					$arrholiday = explode('::', $idsholiday);
					$fk_user = $arrholiday[0];
					$fk_holiday = $arrholiday[1];
					$h_start = strtotime($arrholiday[2]);
					$h_end = strtotime($arrholiday[3]);
					$halfday = !empty($arrholiday[4]) ? $arrholiday[4] : 1;

					$nbDays = 0;
				
				   	// Convert timestamps to DateTime objects
		            $startDate = new DateTime();
		            $startDate->setTimestamp($h_start);

		            $endDate = new DateTime();
		            $endDate->setTimestamp($h_end);

					if($h_start>=$_start && $h_end <= $_end){
			            // Calculate total days between dates
			            $nbDays = $endDate->diff($startDate)->days + 1;
					}elseif($h_start<$_start && $h_end <= $_end){
			            // Calculate total days between dates
			            $nbDays = $endDate->diff($startDate)->days + 1;
					}elseif($h_start>=$_start && $h_start<$_end && $h_end > $_end){
			            // Calculate total days between dates
			            $nbDays = $endDate->diff($startDate)->days + 1;
					}

					$nbDays = num_open_day($h_start, $h_end, 0, 1, $halfday);
					if($nbDays>0){
						// $_end = strtotime('+ '.$nbDays.' day ', $_end);
						$dataholiday[$fk_user] += $nbDays;
					}
				}
			}
		}

		$duration = 0;
		if($_end && $_start) {
			// $datediff 	= $_end-$_start;
			// $duration 	= round($datediff / (60 * 60 * 24));

			$duration = $ganttproadvanced->calculateWeekdaysWithOrWithoutWeekEnd($_start, $_end);
		}

		$data['id'] 		= $ganttid;	
		// $data['end_date'] 	= dol_print_date($end_date, "%Y-%m-%d");
		// $data['end_date'] 	= dol_print_date($db->jdate($end_date), "%Y-%m-%d %H:%M", 'tzserver');
		$data['end_date'] 	= dol_print_date($db->jdate($end_date), "%Y-%m-%d %H:%M", 'tzserver');
		$data['ref'] 		= $obj->ref;

		$data['text'] 		= $obj->title;	

		// if($obj->typetable == 'project') {
		// }

		$description = dol_htmlentitiesbr_decode($obj->description);	
		$description = str_replace('&#39;', "'", $description);

		$data['description'] = $description;
		
		$data['projectid']	= $obj->projectid;	
		$data['ref_projet'] = $obj->ref_projet;
		$data['budget']	= $obj->budget ? price2num($obj->budget) : '';	
		$data['progress']	= $obj->progress ? ($obj->progress/100) : 0;
		$data['sortorder'] 	= 10;	
		// $data['start_date'] = dol_print_date($obj->dateo, "%Y-%m-%d");	
		$data['start_date'] = dol_print_date($db->jdate($obj->dateo), "%Y-%m-%d %H:%M", 'tzserver');	
		// $data['start_date'] = dol_print_date($db->jdate($obj->dateo), "%Y-%m-%d %H:%M", 'tzserver');	
		$data['duration'] 	= $duration;	
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
		// d('label_relatedtask: '.$obj->label_relatedtask, 0);
		// $data['color'] 		= $obj->color;
		$data['ganttproadvancedrelatedtask'] = $obj->ganttproadvancedrelatedtask ? $obj->ganttproadvancedrelatedtask : 0;
		$data['ganttproadvancedtyperelation'] = $obj->ganttproadvancedtyperelation ? $obj->ganttproadvancedtyperelation : 0;
		$data['ganttproadvancednumberdayslate'] = $obj->ganttproadvancednumberdayslate ? $obj->ganttproadvancednumberdayslate : 0;
		$data['label_typerelation'] = ($obj->ganttproadvancedtyperelation && $obj->ganttproadvancedtyperelation != "NULL") ? dol_escape_js($ganttproadvanced->typesrelation[$obj->ganttproadvancedtyperelation]) : '';
		$data['label_relatedtask'] = $obj->label_relatedtask;
		$data['color'] 		= $ganttproadvanced->p_projectcolor;

		$data['duration_effective']	= ($obj->duration_effective) ? convertSecondToTime($obj->duration_effective, $timespentoutputformat) : '--:--';	
		
		$data['note_private'] 	= $obj->note_private;	
		$data['projectlabel'] = $obj->projectlabel;
		$data['tiersname'] = $obj->tiersname;

		$data['task_rowid'] = 0;
		// $data['timespasse'] = $obj->timespasse;


		$timespasse = explode(',', $obj->timespasse);
		// d('Task '.$obj->ref, 0);
		// d($timespasse, 0);
		$datatimeconsom = array();
		if($timespasse){
			foreach ($timespasse as $key => $linetime) {
				$arrtime = explode('::', $linetime);
				if(!empty($arrtime[0])){
					// d('id_time: '.$arrtime[0], 0);
					// d('fk_user: '.$arrtime[1], 0);
					// d('time: '.convertSecondToTime($arrtime[2], 'allhourmin'), 0);
					$datatimeconsom[$arrtime[1]] += $arrtime[2];
					$datatimeconsombyuser[$arrtime[1]] += $arrtime[2];
				}
			}
		}
		// d($datatimeconsom, 0);

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

		foreach ($tmarr as $tmpk => $c_detail) {
			
			if(!$c_detail) continue;
			
			$d = explode("::", $c_detail);
			// $d[0] : Type contact
			// $d[1] : User id
			// $d[2] : Color Hex
			// $d[3] : User Name
			// $d[4] : Type Code
			// $d[5] : Departement


			if(!$d[0]) continue;
			$departement = $d[5];
			$data['departement'] = $departement ? $userextrafields->showOutputField('cpsdepartment', $departement, '', $userstatic->table_element) : '';
			$dt_departement[$d[1]] = $data['departement'];

			// -------------------------------------------------------------------------------------------------- Affected user
			if($ganttproadvanced->coloredbyuser) {

				$tmpcol = (isset($d[2]) && $d[2]) ? '#'.$d[2] : $ganttproadvanced->colorgristask;


				if($d[0] == $ganttproadvanced->t_typecontact) {

					if($sql_users && in_array($d[1], $search_affecteduser) || !$sql_users){
						$data['color'] = $tmpcol;
					}

					$affect_id = (int) $d[1];
					$data['affected_user'] = $affect_id;

					// $userextrafields->
					$data['affected_user'] = $affect_id;

					$users_tasks[$affect_id][$obj->rowid] = $obj->rowid;

					if($d[3]) {
						// $nameofresource = $d[3];

						$data['affected_nameuser'] .= '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$affect_id.'" target="_blank">';
		            	$data['affected_nameuser'] .= img_picto($langs->trans('User'), 'user', '').' ';
		            	$data['affected_nameuser'] .= $d[3];
		            	$data['affected_nameuser'] .= '</a>';
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

		// d('<br><br><br>Task '.$data['ref'], 0);

		// d('contactscontributors: ', 0);
		// d($contactscontributors, 0);

		// d('datatimeconsom: ', 0);
		// d($datatimeconsom, 0);

		// foreach ($datatimeconsom as $key => $time) {
		// 	d('user '.$key.' : '.convertSecondToTime($time, 'allhourmin'), 0);
		// }


		// -------------------------------------------------------------------------------------------------- Par Ressource Contributeurs
		// $taskalready_hascontributor = false;

		if($ganttproadvanced->byressource_type_contacts == 'per_principal_contributeur') {
			$contactscontributors = (!empty($data['contacts'])) ? explode(',', $data['contacts']) : array();
			// d('datatimeconsom of task in foreach contact: '.$obj->ref, 0);

			foreach ($contactscontributors as $tmpkey => $contact_user_id) {

				if(!empty($dt_departement[$contact_user_id])){
					$data['departement'] = $dt_departement[$contact_user_id];
				}else{
					$data['departement']='';
				}

				// d('Task: '.$data['ref'], 0);
				if(!empty($datatimeconsom[$contact_user_id])){
					$data['duration_effective']	= convertSecondToTime($datatimeconsom[$contact_user_id], $timespentoutputformat);
				}else{
					$data['duration_effective'] = '--:--';
				}
				
				$origindata = $data;

				$parent = 'project_contributeur_'.$contact_user_id;


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
					$tmpdata['duration_effective'] = '--:--';
					$tmpdata['note_private'] = '';
					$tmpdata['type'] = 'task';
					$tmpdata['bar_height'] = '21';
					$tmpdata['ref_projet']='';

					if(!empty($datatimeconsom[$contact_user_id])){
						$tmpdata['duration_effective']	= convertSecondToTime($datatimeconsom[$contact_user_id], $timespentoutputformat);
					}

					$idsparentsdata[$i] = $contact_user_id;
					
					$byresourceusersids[$contact_user_id] = $contact_user_id;
					$all_data[] = $tmpdata;

					$_data 	.= json_encode($tmpdata).',';
				}

				if(!empty($dataholiday[$contact_user_id])){
					$nbdaysconge = $dataholiday[$contact_user_id];
					$d_end = strtotime('+ '.$nbdaysconge.' day ', $_end);
					$data['end_date'] 	= dol_print_date($d_end, "%Y-%m-%d %H:%M", 'tzserver');
				}

				$origindata['parent'] = $parent;
				$origindata['id'] = $origindata['id']."_".$contact_user_id;
				$all_data[] = $origindata;

				$_data 	.= json_encode($origindata).',';
			}
		}

		// -------------------------------------------------------------------------------------------------- Par Ressource Principal

		$isuseraffected = (isset($data['affected_user'])) ? (int) $data['affected_user'] : 0;
		$parent = 'project'.$isuseraffected;

		if(!isset($byresourceusersids[$isuseraffected])) {
			if(
				($ganttproadvanced->byressource_type_contacts == 'per_principal') 
				|| 
				($ganttproadvanced->byressource_type_contacts == 'per_principal_contributeur' && !empty($data['affected_nameuser']))
			) 
			{

				$data['duration_effective']	= ($datatimeconsom[$isuseraffected]) ? convertSecondToTime($datatimeconsom[$isuseraffected], $timespentoutputformat) : '--:--';	

				if(!empty($dt_departement[$isuseraffected])){
					$data['departement'] = $dt_departement[$isuseraffected];
				}else{
					$data['departement']='';
				}

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

				if(!empty($dataholiday[$isuseraffected])){

					$nbdaysconge = $dataholiday[$isuseraffected];
					$d_end = strtotime('+ '.$nbdaysconge.' day ', $_end);
					$tmpdata['end_date'] = dol_print_date($d_end, "%Y-%m-%d %H:%M", 'tzserver');
					$data['end_date'] = dol_print_date($d_end, "%Y-%m-%d %H:%M", 'tzserver');
				}

				if(!empty($datatimeconsom[$isuseraffected])){
					$tmpdata['duration_effective']	= ($datatimeconsom[$isuseraffected]) ? convertSecondToTime($datatimeconsom[$isuseraffected], $timespentoutputformat) : '--:--';	
				}

				// $byresourceusersids[$isuseraffected] = $isuseraffected;
				$all_data[] = $tmpdata;

				$_data 	.= json_encode($tmpdata).',';
			}
		}

		$data['parent'] = $parent;

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

		$data_test[] = $data;
		$all_data[] = $data;
		$i++;
		$j++;

	}
} else {
	// print_r($db->lasterror());
}


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

llxHeader($moreheadcss.$moreheadjs, $modname, '', '', '', '', array(), '', 0, 0);


?>
<style>
	<?php
	if(!$ganttproadvanced->default_hide_leftmenu){
		print '.side-nav, #id-left { display: none; }';
		print 'div.fiche { margin-left: 15px; margin-right: 15px; }';
	}
	if(!empty($ganttproadvanced->marker_bar_color)){
		print '.gantt_marker.markertask.gantt_scale_cell { background-color: '.$ganttproadvanced->marker_bar_color.'; }';
	}
	if(!$ganttproadvanced->use_task_dependencies) {
		print '#ganttproadvanced .gantt_task_line .gantt_link_control .gantt_link_point { display: none;}';
	}
	if($ganttproadvanced->use_abnovo_datejalon) {
		print 'input[name="ganttproadvanceddatejalon"] {
		    pointer-events: none;
		    user-select: none;
		    background-color: #f3f3f3;
		}';
	}
	?>
	#id-right { padding-top: 0; }
	div.tabs { margin-top: 0; }
	table.table-fiche-title { margin-bottom: 0px; }
	div.tabBar { padding: 7px 0 0; }
		.month_year_datepicker .ui-datepicker-calendar {
	 	 display: none;
	}
</style>
<script>
	$(function(){

        $('.ganttproadvancedfilterdiv .date_picker').datepicker({
            dateFormat: "mm/yy",
            changeMonth: true,
            changeYear: true,
			autoclose: true,

			onChangeMonthYear: function (year, month) {
				// $(this).datepicker('hide');
			},

            onClose: function(dateText, inst) {

                var m = inst.selectedMonth;
                var y = inst.selectedYear;

                $(this).datepicker('setDate', new Date(y, m, 1)).trigger('change');
                
                $('#'+inst.id+'month').val(m+1);
                $('#'+inst.id+'year').val(y);

				// setTimeout(function(){
                // 	inst.dpDiv.removeClass('month_year_datepicker');
				// },1000);

             	// $('.date_picker').focusout();
            },

            beforeShow : function(input, inst) {

            	$('#ui-datepicker-div').addClass('month_year_datepicker');
                // inst.dpDiv.addClass('month_year_datepicker');

                if ((datestr = $(this).val()).length > 0) {
                    year = datestr.substring(datestr.length-4, datestr.length);
                    month = datestr.substring(0, 2);

                    $(this).datepicker('option', 'defaultDate', new Date(year, month-1, 1));
                    $(this).datepicker('setDate', new Date(year, month-1, 1));

                    // $(".ui-datepicker-calendar").hide();

                    
                }
            }
        });
	});
</script>
<?php

echo '<style>.gantt_marker.markertask.gantt_scale_cell{width:7px !important;cursor: help;border-right: transparent !important;}</style>';


echo '<div class="ganttmarkerstyles">';
if(empty($ganttproadvanced->marker_bar_color)){
	echo '<style>'.$stylemarker.'</style>';
}
echo '</div>';

// llxHeader(array(), $modname);

$modganttproadvanced = new modganttproadvanced($db);
$mve = $modganttproadvanced->version;

echo '<link rel="stylesheet" href="css/librarygantt.css?v='.$mve.'">';
echo '<link rel="stylesheet" href="css/ganttproadvanced_custom.css?v='.$mve.'">';

$tmpmethod = (!empty($totproject) && ((int) $totproject <= $maximumprojecttosendurl)) ? 'GET' : 'POST';

echo '<form method="'.$tmpmethod.'" action="'.$_SERVER["PHP_SELF"].'" id="FormProjSearch" class="ganttproadvancedformindex">';
	echo '<input type="hidden" name="token" value="'.(empty($_SESSION['newtoken']) ? '' : $_SESSION['newtoken']).'">';
	echo '<input type="hidden" id="viewmode" name="viewmode" value="'.$viewmode.'" />';
	echo '<input type="hidden" id="users_tasks" name="users_tasks" value="'.$users_tasks.'" />';
	echo '<input type="hidden" id="ganttproadvancedforcereloadpage" value="0" />';
	// echo '<input type="hidden" id="sql_proj" name="sql_proj" value="'.$sql_proj.'" />';

	if((isset($conf->trellotasks) && $conf->trellotasks->enabled) || $trellotasksplus_enabled){
		print_fiche_titre($modname);
		// $head = GanttKanbanAdminPrepareHead($tosendinurl);
		// dol_fiche_head($head, 'gantt', '', -1,  '');
	} else {
		print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'projecttask', 0, '', '', $limit);
	}

	$type_filtre = isset($conf->global->GANTTPROADVANCED_FILTRE_BY_TYPE) ? $conf->global->GANTTPROADVANCED_FILTRE_BY_TYPE : '';
	$type_filtre = $type_filtre ? explode(',', $type_filtre) : [];

	echo '<fieldset id="fieldsetgantt">';
		echo '<legend align="right" class="openclosebtn"><i class="fas fa-filter"></i> ';
			echo '<a class="closesearch"><span class="fas fa-angle-up"></span></a>';
			echo '<a class="opensearch unvisible"><span class="fas fa-angle-down"></span></a>';
		echo '</legend>';
		
		if(!empty($type_filtre)){
			echo '<div class="ganttproadvancedfiltercategories">';
				if (!empty($type_filtre) && in_array('Customer', $type_filtre)) {
				    echo '<span class="">';
				    echo '&nbsp'.$langs->trans("Customer").': ';
				    $filtreselectclient = ((floatval(DOL_VERSION) < 18) ? '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1' : '((s.client:IN:1,2,3) AND (s.status:=:1))');
		            echo $form->select_company($search_customer, 'search_customer', $filtreselectclient, 1, $__showtype = 0, $__forcecombo = 0, $__events = array(), $__limit = 0, $__morecss = 'width200 maxwidth200');
				    echo '</span>';
				}

			    $morefilter_users = ((floatval(DOL_VERSION) < 21) ? ' AND u.statut <> 0' : 'u.statut:<>:0');

				if (!empty($type_filtre) && in_array('Projectmanageruser', $type_filtre)) {
				    echo '<span class="">';
				    echo $langs->trans("Projectmanageruser").': ';
				    echo '&nbsp'.$form->select_dolusers($search_userid, "search_userid", 1, $exclude = null, $disabled = 0, $include = '', $enableonly = '', $force_entity = '0', $maxlength = 0, $showstatus = 0, $morefilter_users, $_show_every = 0, $_enableonlytext = '', $_morecss = 'width200 maxwidth200');
				    echo '</span>';
				}

				if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire && !empty($type_filtre) && in_array('Tagscategories', $type_filtre)) {
				    echo '<span class="ganttproadvancedfiltercategory">';
				    echo '&nbsp'.$langs->trans("Categories").': '.$ganttproadvanced->SelectFilterCategory($search_category);
				    echo '</span>';
				}

				if (!empty($trellotasksplus_enabled) && !empty($type_filtre) && in_array('Tagstrello', $type_filtre)) {
				    echo '<span class="ganttproadvancedfiltercategory">';
				    echo '&nbsp'.$langs->trans("tagstrellotasksplus").': '.$ganttproadvanced->SelectFilterTagsTrello($search_tagstrello, 'minwidth200 width250');
				    echo '</span>';
				}
				
			echo '</div>';
		}

		echo '<div class="titre ganttproadvancedfilterdiv">';
			echo '<span class="filterspan ganttproadvancedfilterstatus">';
				// echo $langs->trans("Status").': ';
				echo $ganttproadvanced->selectstatus('search_status', $search_status, $_morecss = 'minwidth75imp maxwidth75');
				// echo ajax_combobox('search_status');
			echo '</span>';

			echo '<span class="filterspan projects">';
				// echo $langs->trans("Projects").': ';

				if($user->admin || !empty($conf->global->DOLIBARR_PLATEFORME_DEMO_MODULES)) {
					echo '<a class="externopenlink" target="_blank" href="'.dol_buildpath('/ganttproadvanced/admin/configuration.php',1).'">';
					echo img_picto($langs->trans('SortOrder'), 'setup', ' class="linkobject"');
					echo '</a>';
				}

				echo '<span id="ganttproadvancedselectprojectsauthorized">';
				echo $ganttproadvanced->ganttproadvancedSelectProjectsAuthorized($search_projects, $search_category, $search_status, $search_userid, $search_customer, false, 0, $search_debut, $search_fin);
				echo '</span>';

				// print '<a class="butAction selectallprojects" id="selectallprojects" href="'.dol_buildpath('/ganttproadvanced/index.php?showall=1'.$param,1).'" >'.$langs->trans('All').'</a>';
				print '<a class="butAction selectallprojects" id="selectallprojects" href="#" >'.$langs->trans('All').'</a>';
				print '<a class="butAction selectallprojects" id="selectnoneprojects" href="#" >'.$langs->trans('None').'</a>';

			echo '</span>';

			echo '<span class="filterspan ">';
				echo info_admin($langs->trans('ContactType').' ('.$langs->trans('Tasks').')', 1);
				echo $ganttproadvanced->selectMultipleTypeContact($objtask, $search_tasktype, 'search_tasktype', 'internal', 'rowid', $_showempty = 0, $_multiple = true);
			echo '</span>';

			echo '<span class="filterspan ">';
				echo img_picto($langs->trans('Contacts').' ('.$langs->trans('Tasks').')', 'user', '').' ';
				echo '<span id="ganttproadvanced_users_as_taskcontact">';
					echo $selectusers_html;
				echo '</span>';
			echo '</span>';


			echo '<span class="filterspan ">';
				echo $langs->trans('From').' ';
				echo '<input type="text" class="date_picker width50 center" autocomplete="off" value="'.dol_print_date($search_debut, '%m/%Y').'" onKeyUp="changeInputDatePickerData(this)" onchange="submitFormWhenChange(1)" id="debut" name="debut">';
				echo '<input type="hidden" id="debutmonth" name="debutmonth" value="'.$debutmonth.'">';
				echo '<input type="hidden" id="debutyear" name="debutyear" value="'.$debutyear.'">';

				echo '<span class="marginleftonly">';
					echo $langs->trans('to').' ';
					echo '<input type="text" class="date_picker width50 center" autocomplete="off" value="'.dol_print_date($search_fin, '%m/%Y').'" onKeyUp="changeInputDatePickerData(this)" onchange="submitFormWhenChange(1)" id="fin" name="fin">';
					echo '<input type="hidden" id="finmonth" name="finmonth" value="'.$finmonth.'">';
					echo '<input type="hidden" id="finyear" name="finyear" value="'.$finyear.'">';
				echo '</span>';
			echo '</span>';


			echo '<span class="filterspan ">';
			echo '<button type="submit" class="butAction liste_titre button_search reposition" name="button_search_x" value="x"><span class="fa fa-search"></span></button>';
			echo '<button type="submit" class="butAction liste_titre button_removefilter reposition" name="button_removefilter_x" value="x"><span class="fa fa-ban"></span></button>';
			echo '</span>';


			echo '<span class="filterspan ganttproadvancedscale">';
						
				// echo '<a onclick="ZoomInNow()" class="marginrightonly"><i class="fas fa-search-plus"></i> '.$langs->trans('Now').'</a>';
				echo '<a onclick="ZoomInNow()" class="marginrightonly" title="'.$langs->trans('Now').'"><i class="fas fa-search-plus"></i></a>';


				$radio = '';
				if($ganttproadvanced->showhoursingantt){
					$radio .= '<label><input type="radio" name="scale" value="hour" />'.$langs->trans('Hour').'</label>';
				}
				$radio .= '<label><input type="radio" name="scale" value="day" />'.$langs->trans('Day').'</label>';
				$radio .= '<label><input type="radio" name="scale" value="week"/>'.$langs->trans('Week').'</label>';
				$radio .= '<label><input type="radio" name="scale" value="month"/>'.$langs->trans('Month').'</label>';
				$radio .= '<label><input type="radio" name="scale" value="quarter"/>'.$langs->trans('Quadri').'</label>';
				$radio .= '<label><input type="radio" name="scale" value="year"/>'.$langs->trans('Year').'</label>';
				$radio = str_replace('value="'.$search_scale.'"', 'value="'.$search_scale.'" checked', $radio);

				echo $radio;

				echo '<span class="info_beside_scales marginrightonly classfortooltip">';
				$textinfo = $langs->trans('UseCtrlWheelInOrderToZoom');
				echo info_admin($textinfo, 1);
				echo '</span>';
			echo '</span>';


			// $categoryArray = $form->select_all_categories(Categorie::TYPE_PROJECT, "", "", 64, 0, 1);
			// echo $formcategory->getFilterBox(Categorie::TYPE_PROJECT, $search_category_array);
		echo '</div>';

	echo '</fieldset>';

	if((isset($conf->trellotasks) && $conf->trellotasks->enabled) || $trellotasksplus_enabled || $ganttproadvanced->viewingtasksbyresources){
		$head = GanttKanbanAdminPrepareHead($tosendinurl);
		dol_fiche_head($head, 'liverescheduleddata', '', -1,  '');
	}

	echo '<input type="hidden" id="ganttproadvanced_windowganttloaded" name="ganttproadvanced_windowganttloaded" value="0" />';
echo '</form>';

echo '<div class="clear"></div>';

echo '<script src="js/librarygantt.js?v='.$mve.'"></script>';

$clstoadd = ($ganttproadvanced->default_empeche_add_subtask) ? 'hide_add_subtask' : '';
$clstoadd .= ($ganttproadvanced->show_marker_bar_only_at_top) ? ' show_marker_bar_only_at_top' : ' show_marker_bar_in_taskrow_position';
$clstoadd .= ($viewmode == 'byresource' && $ganttproadvanced->byressource_type_contacts == 'per_principal_contributeur') ? ' viewmodebyresource_per_principal_contributeur' : '';

echo '<div class="ganttproadvancedcontainer '.$clstoadd.'">';
	echo '<div id="ganttproadvanced" data-scale="" class="countproject_'.(!empty($totproject) ? $totproject : '').' gantt'.$viewmode.'">';
		echo '<div class="ganttproadvancedloading">';
			echo '<div class="ganttproadvancedlds-ripple">';
				echo '<div></div>';
				echo '<div></div>';
			print '</div>';
		print '</div>';
	echo '</div>';
echo '</div>';

echo '<div class="bottomactions">';
	// echo '<span class="filterspan showhidecolumns">';
	// 	echo $langs->trans("Show").' / ';
	// 	echo $langs->trans("Hide").': ';
	// 	echo $ganttproadvanced->SelectColumnsToShow();
	// echo '</span>';

	echo '<span class="filterspan exportbuttons">';
		$classhide = 'hidden';
		if(count($search_projects) > 0 && $num > 1 && $viewmode != 'byresource') $classhide = '';
		echo '<a class="butAction '.$classhide.'" href="'.dol_buildpath("/ganttproadvanced/index.php?action=pdf&format=A3".$param,1).'" target="_blank" class="btn-pdf"><i class="fas fa-file-pdf"></i> '.$langs->trans("A3").'</a>';
		echo '<a class="butAction '.$classhide.'" href="'.dol_buildpath("/ganttproadvanced/index.php?action=pdf&format=A4".$param,1).'" target="_blank" class="btn-pdf"><i class="fas fa-file-pdf"></i> '.$langs->trans("A4").'</a>';
		echo '<a class="butAction '.$classhide.'" href="'.dol_buildpath("/ganttproadvanced/index.php?action=xls&format=A4".$param,1).'" class="btn-pdf"><i class="fas fa-file-excel"></i> '.$langs->trans("XLS").'</a>';
	echo '</span>';

echo '<div>';

require_once 'ganttproadvanced_script-functions.php';
require_once 'ganttproadvanced_script.php';
$selected_columns = array('dureeffectiv','departement','projet','start_date','end_date');

?>
<script>
	$(document).ready(function(){
		var selectedcolumns = getColumnsToShow('<?php echo json_encode($selected_columns); ?>');
		gantt.config.columns = selectedcolumns;
	})
</script>
<?php
llxFooter();
$db->close();