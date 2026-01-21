<?php

function ganttproadvancedAdminPrepareHead()
{
    global $langs, $conf;
    
    $langs->load("ganttproadvanced@ganttproadvanced");
    
    $h = 0;
    $head = array();
    
    
    $head[$h][0] = dol_buildpath("/ganttproadvanced/admin/configuration.php", 1);
    $head[$h][1] = $langs->trans("Configuration");
    $head[$h][2] = 'configuration';
    $h++;
  
    return $head;
}


function GanttKanbanAdminPrepareHead($tosendinurl = '')
{
    global $langs, $conf;
    
    $langs->load("ganttproadvanced@ganttproadvanced");
    
    $h = 0;
    $head = array();
    
    
    $head[$h][0] = dol_buildpath("/ganttproadvanced/index.php?model=gantt".$tosendinurl, 1);
    $head[$h][1] = $langs->trans("viewgantt");
    $head[$h][2] = 'gantt';
    $h++;


    global $ganttproadvanced;

    if($ganttproadvanced->viewingtasksbyresources) {
        $head[$h][0] = dol_buildpath("/ganttproadvanced/index.php?viewmode=byresource".$tosendinurl, 1);
        $head[$h][1] = $langs->trans("GanttByResource");
        $head[$h][2] = 'byresource';
        $h++;
    }
    

    if($ganttproadvanced->viewhoursingantt) {
        $head[$h][0] = dol_buildpath("/ganttproadvanced/liverescheduleddata.php?viewmode=byresource".$tosendinurl, 1);
        $head[$h][1] = $langs->trans("liverescheduleddata");
        $head[$h][2] = 'liverescheduleddata';
        $h++;
    }
    
    if($ganttproadvanced->viewhoursingantt) {
        $head[$h][0] = dol_buildpath("/ganttproadvanced/livehours.php?viewmode=byresource".$tosendinurl, 1);
        $head[$h][1] = $langs->trans("livehours");
        $head[$h][2] = 'livehours';
        $h++;
    }
    

    if(isset($conf->trellotasksplus) && $conf->trellotasksplus->enabled){
        $head[$h][0] = dol_buildpath("/trellotasksplus/index.php?model=kanban".$tosendinurl, 1);
        $head[$h][1] = $langs->trans("viewkanban");
        $head[$h][2] = 'kanban';
        $h++;
    }
    elseif(isset($conf->trellotasks) && $conf->trellotasks->enabled){
        $head[$h][0] = dol_buildpath("/trellotasks/index.php?model=kanban".$tosendinurl, 1);
        $head[$h][1] = $langs->trans("viewkanban");
        $head[$h][2] = 'kanban';
        $h++;
    }
  
    return $head;
}

if (!function_exists("d")) {
    function d($array , $stop = true)
    {
        echo '<pre>';
        print_r($array);
        echo '</pre>';
        if($stop) die;
    }
}