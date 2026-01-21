<?php

if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);

$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';


dol_include_once('/ganttproadvanced/class/ganttproadvanced.class.php');

$ganttproadvanced       = new ganttproadvanced($db);
$formcompany    = new FormCompany($db);
$task           = new Task($db);
$formother      = new FormOther($db);

// Translations
$langs->load("ganttproadvanced@ganttproadvanced");
$langs->load("project");
$langs->load("admin");
$langs->load("ecm");
$langs->load("projects");


// Parameters
$action = GETPOST('action', 'alpha');

if(!empty($action)){
    if (! $user->admin) accessforbidden();
}

$typefiltre = GETPOST('typefiltre', 'array') ? implode(',', GETPOST('typefiltre', 'array')) : '';

$p_sortfield = GETPOST('p_sortfield','alpha') ? GETPOST('p_sortfield','alpha') : 'p.ref';
$p_sortorder = GETPOST('p_sortorder','alpha') ? GETPOST('p_sortorder','alpha') : 'ASC';
$default_status = GETPOST('default_status') ? GETPOST('default_status') : 99;

$t_colortaskbyuser = GETPOST('t_colortaskbyuser','alpha') ? GETPOST('t_colortaskbyuser','alpha') : 0;
$t_typecontact = GETPOST('t_typecontact','alpha') ? GETPOST('t_typecontact','alpha') : '';

$p_projectcolor = GETPOST("p_projectcolor", 'alphanohtml') != '' ? GETPOST("p_projectcolor", 'alphanohtml') : '#34495e';
$t_showuserinchart = GETPOST('t_showuserinchart','int') ? GETPOST('t_showuserinchart','int') : 0;
$showweekend = GETPOST('showweekend','int') ? GETPOST('showweekend','int') : 0;
$excludeweekend = GETPOST('excludeweekend','int') ? GETPOST('excludeweekend','int') : 0;
$changecolortaskbyadmin = GETPOST('changecolortaskbyadmin','int') ? GETPOST('changecolortaskbyadmin','int') : 0;
$show_marker_bar_only_at_top = GETPOST('show_marker_bar_only_at_top','int') ? GETPOST('show_marker_bar_only_at_top','int') : 0;
$marker_bar_color = GETPOST("marker_bar_color", 'alphanohtml') != '' ? GETPOST("marker_bar_color", 'alphanohtml') : '';
$scroll_currentday = GETPOST('scroll_currentday','int') ? GETPOST('scroll_currentday','int') : 0;
$default_zoom = GETPOST('default_zoom','alpha') ? GETPOST('default_zoom','alpha') : 'day';
$show_leftmenu=GETPOST('show_leftmenu');
$max_period_task_month=GETPOST('periodtask');
$default_empeche_add_subtask=GETPOST('empecheaddsubtask');
$hide_finished_task=GETPOST('finishedtask');
// $select_one_project=GETPOST('selectoneproject');
$bydefaultproject = GETPOST('bydefaultproject','alpha') ? GETPOST('bydefaultproject','alpha') : 'one';
$byressource_type_contacts = GETPOST('byressource_type_contacts','alpha') ? GETPOST('byressource_type_contacts','alpha') : 'per_principal';
$use_task_dependencies=GETPOST('use_task_dependencies');
$modify_parenttasks=GETPOST('modify_parenttasks');
$minmaxtasksdatetoproject=GETPOST('minmaxtasksdatetoproject');
$excluded_project_tag = GETPOST('excluded_project_tag', 'int') ? GETPOST('excluded_project_tag', 'int') : 0;
$refreshpageautomatically = GETPOST('refreshpageautomatically');
$keep_recent_filter=GETPOST('keep_recent_filter');
$default_onglet=GETPOST('default_onglet', 'alpha');

$showgridresourceintablivehours=GETPOST('showgridresourceintablivehours', 'int');

// Show Hours in gantt - Akku
$taskworkingtime = GETPOST("taskworkingtime", 'alpha') != '' ? GETPOST("taskworkingtime", 'alpha') : '';

/*
 * Actions
 */

if(!empty($action)){

    $error = 0;

    if($action == "update"){

        if(!dolibarr_set_const($db, "GANTTPROADVANCED_PROJECT_SORTFIELD", $p_sortfield, 'chaine', 0, '', $conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_PROJECT_SORTORDER", $p_sortorder, 'chaine', 0, '', $conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_TYPE_CONTACT_TO_BASE_ON", $t_typecontact, 'chaine', 0, '', $conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_PROJECT_CHART_COLOR", $p_projectcolor, 'chaine', 0, '', $conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_DEFAULT_ZOOM_BY", $default_zoom, 'chaine', 0, '', $conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_EXCLUDED_PROJECT_TAG", $excluded_project_tag, 'chaine', 0, '', $conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_FILTRE_BY_TYPE", $typefiltre, 'chaine', 0, '', $conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_SELECT_ONE_PROJECT", $bydefaultproject, 'chaine', 0, '', $conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_BYRESSOURCE_TYPE_CONTACTS", $byressource_type_contacts, 'chaine', 0, '', $conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_PROJECT_DEFAULTSTATUS", $default_status, 'int', 0, '', $conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_MARKER_BAR_COLOR", $marker_bar_color, 'chaine', 0, '', $conf->entity))
            $error++;
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_DEFAULT_ONGLET", $default_onglet, 'chaine', 0, '', $conf->entity))
            $error++;

        // Show Hours in gantt - Akku
        if($ganttproadvanced->showhoursingantt){
            if(!dolibarr_set_const($db, "GANTTPROADVANCED_TASK_WORKING_TIME", $taskworkingtime, 'chaine', 0, '', $conf->entity))
                $error++;
        }



        dol_include_once('/ganttproadvanced/core/modules/modganttproadvanced.class.php');
        $modganttproadvanced = new modganttproadvanced($db);

        $modganttproadvanced->delete_menus();
        $modganttproadvanced->insert_menus();

    } elseif($action == 'set_showprojectsevenwithoutdates') {
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_SHOW_PROJECTS_EVEN_WITHOUT_DATES", (int) GETPOST('value'), 'chaine', 0, '', $conf->entity))
            $error++;
    } elseif($action == 'set_viewingtasksbyresources') {
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_VIEWING_TASKS_BY_RESOURCES", (int) GETPOST('value'), 'chaine', 0, '', $conf->entity))
            $error++;
    } elseif($action == 'set_viewhoursingantt') {
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_VIEW_HOURS_IN_GANTT", (int) GETPOST('value'), 'chaine', 0, '', $conf->entity))
            $error++;
    }  elseif($action == 'set_viewtasksbyressources') {
        // if(!dolibarr_set_const($db, "GANTTPROADVANCED_SHOW_PROJECTS_EVEN_WITHOUT_DATES", (int) GETPOST('value'), 'chaine', 0, '', $conf->entity))
            $error++;
    } elseif($action == 'set_colorize') {
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_COLOR_TASK_BY_USER_FIELD_COLOR", $t_colortaskbyuser, 'chaine', 0, '', $conf->entity))
            $error++;
    } elseif($action == 'set_showuserinchart') {
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_SHOW_USER_CHART_TASK", $t_showuserinchart, 'chaine', 0, '', $conf->entity))
            $error++;
    } elseif($action == 'set_showweekend') {
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_SHOW_WEEKEND", $showweekend, 'chaine', 0, '', $conf->entity))
            $error++;
    } elseif($action == 'set_excludeweekend') {
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_EXCLUDE_WEEKENDS_WHEN_CALCULATING_DURATION", $excludeweekend, 'chaine', 0, '', $conf->entity))
            $error++;
    } elseif($action == 'set_changecolortaskbyadmin') {
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_CHANGE_COLORTASK_BY_ADMIN", $changecolortaskbyadmin, 'chaine', 0, '', $conf->entity))
            $error++;
    }  elseif($action == 'set_show_marker_bar_only_at_top') {
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_SHOW_MARKER_BAR_ONLY_AT_TOP", $show_marker_bar_only_at_top, 'chaine', 0, '', $conf->entity))
            $error++;
    } elseif($action == 'set_scroll_currentday') {
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_AUTOMATICALLY_SCROLL_To_CURRENT_DAY", $scroll_currentday, 'chaine', 0, '', $conf->entity))
            $error++;
    }elseif($action == 'set_show_leftmenu'){
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_PROJECT_HIDE_LEFTMENU", $show_leftmenu, 'chaine', 0, '', $conf->entity))
            $error++;
    }elseif($action == 'set_periodtask'){
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_PERIODTASK_IN_MONTH", $max_period_task_month, 'chaine', 0, '', $conf->entity))
            $error++;
    }elseif($action == 'set_empecheaddsubtask'){
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_EMPECHE_ADD_SUBTASK", $default_empeche_add_subtask, 'chaine', 0, '', $conf->entity))
            $error++;
    }elseif($action == 'set_finishedtask'){
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_HIDE_FINISHED_TASK", $hide_finished_task, 'chaine', 0, '', $conf->entity))
            $error++;
    }
    // elseif($action == 'set_selectoneproject'){
    //     if(!dolibarr_set_const($db, "GANTTPROADVANCED_SELECT_ONE_PROJECT", $select_one_project, 'chaine', 0, '', $conf->entity))
    //         $error++;
    // }
    elseif($action == 'set_use_task_dependencies'){
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_USE_TASK_DEPENDENCIES", $use_task_dependencies, 'chaine', 0, '', $conf->entity))
            $error++;
    }elseif($action == 'set_modify_parenttasks'){
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_MODIFY_PARENT_TASKS", $modify_parenttasks, 'chaine', 0, '', $conf->entity))
            $error++;
    }elseif($action == 'set_minmaxtasksdatetoproject'){
        if(!dolibarr_set_const($db, "SET_MIN_MAX_TASKS_DATES_TO_PROJECT", $minmaxtasksdatetoproject, 'chaine', 0, '', $conf->entity))
            $error++;
    }
    elseif($action == 'set_refreshpageautomatically'){
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_REFRESH_PAGE_AUTOMATICALLY", $refreshpageautomatically, 'chaine', 0, '', $conf->entity))
            $error++;
    }elseif($action == 'set_keep_recent_filter'){
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_KEEP_RECENT_FILTER", $keep_recent_filter, 'chaine', 0, '', $conf->entity))
            $error++;
    }
    // Show Hours in gantt - Akku
    elseif($action == 'set_showgridresourceintablivehours') {
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_SHOW_GRIDRESOURCE_IN_TAB_LIVEHOURS", GETPOST('value'), 'chaine', 0, '', $conf->entity))
            $error++;
    }
    elseif($action == 'set_showhoursingantt') {

        if(empty(GETPOST('value', 'int'))){
            // if(isset($user->conf->GANTTPROADVANCED_GANTT_COLUMS_TO_SHOW)) {
            //     $selected_columns = json_decode($user->conf->GANTTPROADVANCED_GANTT_COLUMS_TO_SHOW, 1);
            //     if(is_array($selected_columns)){
                    
            //         $colomns = array_flip($selected_columns);
            //         unset($colomns['durationhour']);
                    
            //         $colomns = array_flip($colomns);
            //         $userparametres = array();

            //         d($colomns, 0);
            //         $userparametres['GANTTPROADVANCED_GANTT_COLUMS_TO_SHOW'] = json_encode($colomns);
            //         d($userparametres['GANTTPROADVANCED_GANTT_COLUMS_TO_SHOW'],0);
            //         $res = dol_set_user_param($db, $conf, $user, $userparametres);
            //     }
            // }
        }
        if(!dolibarr_set_const($db, "GANTTPROADVANCED_SHOW_HOURS_IN_GANTT", (int) GETPOST('value', 'int'), 'chaine', 0, '', $conf->entity))
            $error++;
    }



    if(!$error)
        setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    else
        setEventMessage($langs->trans("Error"), 'errors');

    header('Location: ./configuration.php');
    exit;
}



/*
 * View
 */
$page_name = $langs->trans('ModuleSetup').' '.$langs->trans('ganttproadvanced');
llxHeader('', $page_name);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'. $langs->trans("BackToModuleList").'</a>';
print_fiche_titre($page_name, $linkback);


// Setup page goes here
$form=new Form($db);

$var=false;

$p_sortfield = $ganttproadvanced->p_sortfield;
$p_sortorder = $ganttproadvanced->p_sortorder;
$t_colortaskbyuser = $ganttproadvanced->t_colortaskbyuser;
$showprojectsevenwithoutdates = $ganttproadvanced->showprojectsevenwithoutdates;
$viewingtasksbyresources = $ganttproadvanced->viewingtasksbyresources;
$viewhoursingantt = $ganttproadvanced->viewhoursingantt;
$t_typecontact = $ganttproadvanced->t_typecontact;
$p_projectcolor = str_replace('#', '', $ganttproadvanced->p_projectcolor);
$marker_bar_color = str_replace('#', '', $ganttproadvanced->marker_bar_color);
$t_showuserinchart = $ganttproadvanced->t_showuserinchart;
$showweekend = $ganttproadvanced->showweekend;
$excludeweekend = $ganttproadvanced->excludeweekend;
$refreshpageautomatically = $ganttproadvanced->refreshpageautomatically;
$keep_recent_filter = $ganttproadvanced->keep_recent_filter;

$changecolortaskbyadmin = $ganttproadvanced->changecolortaskbyadmin;
$show_marker_bar_only_at_top = $ganttproadvanced->show_marker_bar_only_at_top;

$scroll_currentday = $ganttproadvanced->scroll_currentday;
$default_zoom = $ganttproadvanced->default_zoom;
$default_hide_leftmenu = $ganttproadvanced->default_hide_leftmenu;
$max_period_task_month = $ganttproadvanced->max_period_task_month;
$default_empeche_add_subtask = $ganttproadvanced->default_empeche_add_subtask;
$hide_finished_task = $ganttproadvanced->hide_finished_task;
$use_task_dependencies = $ganttproadvanced->use_task_dependencies;
$modify_parenttasks = $ganttproadvanced->modify_parenttasks;
$minmaxtasksdatetoproject = $ganttproadvanced->minmaxtasksdatetoproject;
$excluded_project_tag = $ganttproadvanced->excluded_project_tag ? $ganttproadvanced->excluded_project_tag : 0;
$typefiltre = $ganttproadvanced->typefiltre ? explode(',', $ganttproadvanced->typefiltre) : [];
// $select_one_project = $ganttproadvanced->select_one_project;
$bydefaultproject = $ganttproadvanced->bydefaultproject;
$byressource_type_contacts = $ganttproadvanced->byressource_type_contacts;


// Show Hours in gantt - Akku
$showhoursingantt = $ganttproadvanced->showhoursingantt;
$taskworkingtime = $ganttproadvanced->taskworkingtime;


// d($typefiltre,0);
print '<div class="tabBar tabBarWithBottom">';

print '<div class="warning">';
    print $langs->trans('UseCtrlWheelInOrderToZoom');
print '</div>';
            
$submitbutton = '<tr><td colspan="2" align="left"><br><input type="submit" value="'.$langs->trans('Validate').'" name="bouton" class="button" /><br><br></td></tr>';

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update" />';
    print '<table class="border" width="100%">';

        print '<tr class="oddeven">';
            print '<td class="titlefield">'.$langs->trans('SortOrder').': '.$langs->trans('Projects').'</td>';
            print '<td>';
                $arr_filterby = array();

                $arr_filterby['p.ref'] = $langs->trans("Ref");
                $arr_filterby['p.title'] = $langs->trans("ProjectLabel");
                $arr_filterby['p.dateo'] = $langs->trans("DateStart");
                $arr_filterby['p.datee'] = $langs->trans("DateEnd");
                $arr_filterby['p.fk_statut'] = $langs->trans("Status");
                
                print $form->selectarray('p_sortfield', $arr_filterby, $p_sortfield, 0, 0, 0, '', 0, 0, 0, '', 'minwidth75imp maxwidth150 selectarrowonleft');
                $arr_sortfield = array();

                $arr_sortfield['asc'] = 'A-Z';
                $arr_sortfield['desc'] = 'Z-A';
                
                print $form->selectarray('p_sortorder', $arr_sortfield, $p_sortorder, 0, 0, 0, '', 0, 0, 0, '', 'minwidth75imp maxwidth150 selectarrowonleft');
            print '</td>';
        print '</tr>';
        
        print '<tr class="oddeven">';
            print '<td class="titlefield">'.$langs->trans('Color').' '.$langs->trans('Of').' '.$langs->trans('Project').'</td>';
            print '<td>';
                print $formother->selectColor(GETPOSTISSET('p_projectcolor') ? GETPOST('p_projectcolor', 'alphanohtml') : $p_projectcolor, 'p_projectcolor', null, 1, '', 'hideifnotset');
            print '</td>';
        print '</tr>';
        
        print '<tr class="oddeven">';
            print '<td class="titlefield">'.$langs->trans('DefaultZoomBy').'</td>';
            print '<td>';
                $array_scales = array();
                if(!empty($showhoursingantt)){
                    $array_scales['hour'] = $langs->trans('Hour');
                }
                $array_scales['day']     = $langs->trans("Day");
                $array_scales['week']    = $langs->trans("Week");
                $array_scales['month']   = $langs->trans("Month");
                $array_scales['quarter'] = $langs->trans("Quadri");
                $array_scales['year']    = $langs->trans("Year");

                print $form->selectarray('default_zoom', $array_scales, $default_zoom, 0, 0, 0, $actionslct = '', 0, 0, 0, '', 'maxwidth200', 1);
            print '</td>';
        print '</tr>';

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('AutomaticlyScrollToCurrentDay').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($scroll_currentday) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_scroll_currentday&scroll_currentday=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_scroll_currentday&scroll_currentday=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
            print '</td>';
        print '</tr>';

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('show_leftmenu').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($default_hide_leftmenu) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_show_leftmenu&show_leftmenu=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_show_leftmenu&show_leftmenu=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
                // print $form->selectyesno('t_colortaskbyuser', $t_colortaskbyuser, 0, false, 1);
            print '</td>';
        print '</tr>';

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('ShowWeekEnd').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($showweekend) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showweekend&showweekend=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showweekend&showweekend=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
                // print $form->selectyesno('t_colortaskbyuser', $t_colortaskbyuser, 0, false, 1);
            print '</td>';
        print '</tr>';

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('ExcludeWeekendFromDurationCalculGantt').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($excludeweekend) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_excludeweekend&excludeweekend=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_excludeweekend&excludeweekend=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
                // print $form->selectyesno('t_colortaskbyuser', $t_colortaskbyuser, 0, false, 1);
            print '</td>';
        print '</tr>';

        print '<tr>';
            print '<td class="titlefield nowraponall">'.$langs->trans('Refresh').' '.strtolower($langs->trans('Page').' '.$langs->trans('ECMTypeAuto')).'</td>';
            print '<td colspan="2">';
                if ($refreshpageautomatically) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_refreshpageautomatically&refreshpageautomatically=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition pull-left" href="'.$_SERVER['PHP_SELF'].'?action=set_refreshpageautomatically&refreshpageautomatically=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';




                    echo '<span class="config_filterspansearchremovefilter marginleftonly">';
                    print img_picto('', '1rightarrow').img_picto('', '1rightarrow').img_picto('', '1rightarrow');
                    echo '<button type="" disabled class="butAction liste_titre button_search reposition" name="button_search_x" value="x"><span class="fa fa-search"></span></button>';
                    echo '<button type="" disabled class="butAction liste_titre button_removefilter reposition" name="button_removefilter_x" value="x" style="margin: 0;"><span class="fa fa-ban"></span></button>';
                    echo '</span>';
                }
            print '</td>';
        print '</tr>';

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('KeepRecentFilter').'</td>';
            print '<td>';
                if ($keep_recent_filter) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_keep_recent_filter&keep_recent_filter=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_keep_recent_filter&keep_recent_filter=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
            print '</td>';
        print '</tr>';

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('SelectByDefaultAProject').'&nbsp;&nbsp;</td>';
            print '<td>';

                $array_bydefaultproject = array(
                    'none'  => $langs->trans("None").' '.$langs->trans("Project"),
                    'one'   => '1 '.$langs->trans("Project"),
                    'all'   => $langs->trans("AllProjects"),
                );

                print $form->selectarray('bydefaultproject', $array_bydefaultproject, $bydefaultproject, 0, 0, 0, $actionslct = '', 0, 0, 0, '', 'maxwidth200', 1);

                // if ($select_one_project) {
                //     print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_selectoneproject&selectoneproject=0">';
                //     print img_picto($langs->trans("Activated"), 'switch_on');
                //     print '</a>';
                // } else {
                //     print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_selectoneproject&selectoneproject=1">';
                //     print img_picto($langs->trans("Disabled"), 'switch_off');
                //     print '</a>';
                // }
            print '</td>';
        print '</tr>';


        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('FilterBy').'&nbsp;&nbsp;</td>';
            print '<td>';
               print $ganttproadvanced->select_types($typefiltre,'typefiltre',1 , 1,'typefiltre');
            print '</td>';
        print '</tr>';


        // print '<tr class="oddeven">';
        //     print '<td class="titlefield nowraponall">'.$langs->trans('DefaultStatus').'&nbsp;&nbsp;</td>';
        //     print '<td>';
        //         print $ganttproadvanced->selectstatus('default_status', $ganttproadvanced->default_status);
        //     print '</td>';
        // print '</tr>';

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('ShowProjectsEvenWithoutDates').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($showprojectsevenwithoutdates) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showprojectsevenwithoutdates&value=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showprojectsevenwithoutdates&value=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
                // print $form->selectyesno('t_colortaskbyuser', $t_colortaskbyuser, 0, false, 1);
            print '</td>';
        print '</tr>';

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('ViewingTasksByresources').'&nbsp;&nbsp;</td>';
            print '<td class="nowraponall">';
                if ($viewingtasksbyresources) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_viewingtasksbyresources&value=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';

                    $array_byressourcecontacts = array(
                        'per_principal'  => $langs->trans("PerPrincipalContact"),
                        'per_principal_contributeur'  => $langs->trans("PerPrincipalAndContributeurContact")
                    );

                    // print '<br>';
                    print $form->selectarray('byressource_type_contacts', $array_byressourcecontacts, $byressource_type_contacts, 0, 0, 0, $actionslct = '', 0, 0, 0, '', 'maxwidth500 width300 marginleftonly', 1);

                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_viewingtasksbyresources&value=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
                // print $form->selectyesno('t_colortaskbyuser', $t_colortaskbyuser, 0, false, 1);
            print '</td>';
        print '</tr>';
        










        print '<tr class=""><td colspan="2"><hr></td></tr>';

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('ColorTaskByUserFieldColor').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($t_colortaskbyuser) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_colorize&t_colortaskbyuser=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_colorize&t_colortaskbyuser=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
                // print $form->selectyesno('t_colortaskbyuser', $t_colortaskbyuser, 0, false, 1);
            print '</td>';
        print '</tr>';

        if($t_colortaskbyuser) {
            print '<tr class="oddeven">';
                print '<td class="titlefield "><span class="marginleftonly"> - '.$langs->trans('ContactTypeTasksFromWhereWeGetColor').'</span></td>';
                print '<td>';
                    $formcompany->selectTypeContact($task, $t_typecontact, 't_typecontact', 'internal', 'rowid', 1);
                print '</td>';
            print '</tr>';

            print '<tr class="oddeven">';
                print '<td class="titlefield nowraponall"><span class="marginleftonly"> - '.$langs->trans('ShowUserNameInChart').'</span>&nbsp;&nbsp;</td>';
                print '<td>';
                    if ($t_showuserinchart) {
                        print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showuserinchart&t_showuserinchart=0">';
                        print img_picto($langs->trans("Activated"), 'switch_on');
                        print '</a>';
                    } else {
                        print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showuserinchart&t_showuserinchart=1">';
                        print img_picto($langs->trans("Disabled"), 'switch_off');
                        print '</a>';
                    }
                print '</td>';
            print '</tr>';
        }

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('ChangeColorTaskByAdmin').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($changecolortaskbyadmin) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_changecolortaskbyadmin&changecolortaskbyadmin=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_changecolortaskbyadmin&changecolortaskbyadmin=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
            print '</td>';
        print '</tr>';

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('ShowMarkerBarOnlyAtTop').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($show_marker_bar_only_at_top) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_show_marker_bar_only_at_top&show_marker_bar_only_at_top=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_show_marker_bar_only_at_top&show_marker_bar_only_at_top=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
            print '</td>';
        print '</tr>';

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('ColorOfMarkerBar').'&nbsp;&nbsp;</td>';
            print '<td>';
                print $formother->selectColor(GETPOSTISSET('marker_bar_color') ? GETPOST('marker_bar_color', 'alphanohtml') : $marker_bar_color, 'marker_bar_color', null, 1, '', 'hideifnotset');
            print '</td>';
        print '</tr>';

        













        print '<tr class=""><td colspan="2"><hr></td></tr>';
        
        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('TaskDependencyManagement').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($use_task_dependencies) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_use_task_dependencies&use_task_dependencies=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_use_task_dependencies&use_task_dependencies=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
            print '</td>';
        print '</tr>';

        if($use_task_dependencies) {
            print '<tr class="oddeven">';
                print '<td class="titlefield nowraponall"><span class="marginleftonly"> - '.$langs->trans('setMinMaxTasksDatesToProject').'</span>&nbsp;&nbsp;</td>';
                print '<td>';
                    if ($minmaxtasksdatetoproject) {
                        print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_minmaxtasksdatetoproject&minmaxtasksdatetoproject=0">';
                        print img_picto($langs->trans("Activated"), 'switch_on');
                        print '</a>';
                    } else {
                        print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_minmaxtasksdatetoproject&minmaxtasksdatetoproject=1">';
                        print img_picto($langs->trans("Disabled"), 'switch_off');
                        print '</a>';
                    }
                print '</td>';
            print '</tr>';
        }

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('empechecreatesubtask').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($default_empeche_add_subtask) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_empecheaddsubtask&empecheaddsubtask=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_empecheaddsubtask&empecheaddsubtask=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
                // print $form->selectyesno('t_colortaskbyuser', $t_colortaskbyuser, 0, false, 1);
            print '</td>';
        print '</tr>';


        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('ModifyParentTasks').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($modify_parenttasks) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_modify_parenttasks&modify_parenttasks=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_modify_parenttasks&modify_parenttasks=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
            print '</td>';
        print '</tr>';


        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('TimeSpent').' : '.$langs->trans('HideFinishedTasks').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($hide_finished_task) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_finishedtask&finishedtask=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_finishedtask&finishedtask=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
            print '</td>';
        print '</tr>';

        













        print '<tr class=""><td colspan="2"><hr></td></tr>';

        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('minperiodtask').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($max_period_task_month) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_periodtask&periodtask=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_periodtask&periodtask=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
                if(!empty($max_period_task_month)){
                    print '<span class="marginleftonly">'.$langs->trans('ExcludeProjectWithTags').'</span>';

                    print '<span class="marginleftonly">';
                    $cate_arbo = $form->select_all_categories(Categorie::TYPE_PROJECT, '', 'parent', 64, 0, 1);
                    print img_picto('', 'category').$form->selectarray('excluded_project_tag', $cate_arbo, $excluded_project_tag, 1, $__key_in_label = 0, $__value_as_key = 0, $__moreparam = '', $__translate = 0, $__maxlen = 0, $__disabled = 0, $__sort = '', $__morecss = 'width300 ');
                    print '</span>';
                }

                // print $form->selectyesno('t_colortaskbyuser', $t_colortaskbyuser, 0, false, 1);
            print '</td>';
        print '</tr>';






        print '<tr class=""><td colspan="2"><hr></td></tr>';
        
        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('viewhours').'&nbsp;&nbsp;</td>';
            print '<td class="nowraponall">';
                if ($viewhoursingantt) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_viewhoursingantt&value=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_viewhoursingantt&value=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
                // print $form->selectyesno('t_colortaskbyuser', $t_colortaskbyuser, 0, false, 1);
            print '</td>';
        print '</tr>';

        // Show Hours in gantt - Akku
        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('showhoursingantt').'&nbsp;&nbsp;</td>';
            print '<td>';
                if ($showhoursingantt) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showhoursingantt&value=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showhoursingantt&value=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
            print '</td>';
        print '</tr>';
        
        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('ShowGridResourceInTabLivehours').'&nbsp;&nbsp;</td>';
            print '<td class="nowraponall">';
                if ($ganttproadvanced->showgridresourceintablivehours) {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showgridresourceintablivehours&value=0">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set_showgridresourceintablivehours&value=1">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                }
                // print $form->selectyesno('t_colortaskbyuser', $t_colortaskbyuser, 0, false, 1);
            print '</td>';
        print '</tr>';
        
        print '<tr class="oddeven">';
            print '<td class="titlefield nowraponall">'.$langs->trans('DefaultOnglet').'</td>';
            print '<td>';
                $array = array(
                    'viewgantt' => $langs->trans('viewgantt'),
                    'byresource' => $langs->trans('GanttByResource'),
                    'liverescheduleddata' => $langs->trans('liverescheduleddata')
                );
                if($showhoursingantt){
                    $array['livehours'] = $langs->trans('livehours');
                }
                $default_onglet = $ganttproadvanced->default_onglet;
                print $form->selectarray('default_onglet', $array, $default_onglet);
            print '</td>';
        print '</tr>';
        
        if($showhoursingantt){
            print '<tr class="oddeven">';
                print '<td class="titlefield">'.$form->textwithpicto($langs->trans('taskworkingtime'), $langs->trans("exemploftaskworkingtime")).'</td>';
                print '<td>';
                    print '<input name="taskworkingtime" value="'.$taskworkingtime.'">';
                print '</td>';
            print '</tr>';
        }

        print $submitbutton;

    print '</table>';


print '</form>';
dol_fiche_end(1);

print '</div>';



llxFooter();
$db->close();

