<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2017 AXeL <contact.axel.dev@gmail.com>
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
 *	\file		lib/doliexportlist.lib.php
 *	\ingroup	doliexportlist
 *	\brief		This file is an example module library
 *				Put some comments here
 */

function doliexportlistAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("doliexportlist@doliexportlist");

    $h = 0;
    $head = array();

    /*$head[$h][0] = dol_buildpath("/doliexportlist/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Settings");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/doliexportlist/admin/doc.php", 1);
    $head[$h][1] = $langs->trans("Documentation");
    $head[$h][2] = 'doc';
    $h++;
    $head[$h][0] = dol_buildpath("/doliexportlist/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;*/

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@doliexportlist:/doliexportlist/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@doliexportlist:/doliexportlist/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'doliexportlist');

    return $head;
}

function getButton($picto, $title='', $alt='', $class='export', $width='20')
{
    $link = '<a href="#" class="'.$class.'" style="text-decoration: none;" title="'.$alt.'">';
    $endlink = '</a>';
    $img = ' <img src="'.$picto.'" title="'.$title.'" alt="'.$alt.'" style="vertical-align: middle;" width="'.$width.'" />';

    $button = $link . $img . $endlink;
    
    return $button;
}

function getCompactedButtons($formats, $name, $picto, $morebuttons=array())
{
    global $langs;
    
    $compacted = '<div class="dropdown-click">';
    $compacted.= '<label class="drop-btn button">';
    $compacted.= '<img class="align-middle" title="" alt="" src="'.$picto.'" width="18" />';
    $compacted.= '&nbsp;'.$name.'&nbsp;&nbsp;<img class="align-middle" title="" alt="" src="'.dol_buildpath('/doliexportlist/img/arrow-down.png', 1).'" /></label>';
    $compacted.= '<div class="dropdown-content dropdown-bottom">';
    
    // Formats
    foreach ($formats as $format)
    {
        if ($format->active)
        {
            $format->title = $langs->trans($format->title);
            $format->picto = dol_buildpath('/doliexportlist/img/'.$format->picto, 1);
            
            $compacted.= '<div class="'.$format->type.'" title="'.$format->format.'">';
            $compacted.= '<a href="#" style="min-width: 85px;" title="'.$format->title.'">';
            $compacted.= '<img src="'.$format->picto.'" title="'.$format->title.'" alt="'.$format->format.'" class="align-middle" width="20" />';
            if ($format->format == 'csvfromdb') {
                $format->format = 'csv';
            }
            $compacted.= '&nbsp;&nbsp;'.strtoupper($format->format);
            if (! empty($format->warning)) {
                $compacted.= '&nbsp;&nbsp;'.img_warning($langs->trans($format->warning));
            }
            $compacted.= '</a>';
            $compacted.= '</div>';
        }
    }
    
    // More buttons
    foreach ($morebuttons as $button)
    {
        if ($button['active'])
        {
            $button['title'] = $langs->trans($button['title']);
            $button['picto'] = dol_buildpath('/doliexportlist/img/'.$button['picto'], 1);
            
            $compacted.= '<div class="'.$button['class'].'" title="'.$button['alt'].'">';
            $compacted.= '<a href="#" style="min-width: 85px;" title="'.$button['title'].'">';
            $compacted.= '<img src="'.$button['picto'].'" title="'.$button['title'].'" alt="'.$button['alt'].'" class="align-middle" width="20" />';
            if ($button['alt'] == 'csvfromdb') {
                $button['alt'] = 'excel';
            }
            $compacted.= '&nbsp;&nbsp;'.strtoupper($button['alt']).'</a>';
            $compacted.= '</div>';
        }
    }
    
    $compacted.= '</div></div>';
    
    return $compacted;
}

function exportTable($db, $tablename, $ignore_fields, $to_csv=0)
{
    global $conf;

    $export = '';
    $delim = (! empty($conf->global->EXPORT_CSV_SEPARATOR_TO_USE)?$conf->global->EXPORT_CSV_SEPARATOR_TO_USE:';');

    $sql = 'SELECT * FROM '.$tablename;

    $resql = $db->query($sql);

    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

        if ($num > 0 && !$to_csv) {
            $export.= 'INSERT INTO `'.$tablename.'` VALUES ';
        }

        while ($obj = $db->fetch_object($resql))
        {
            // to csv
            if ($to_csv)
            {
                // get columns
                if ($i == 0)
                {
                    foreach($obj as $key => $value)
                    {
                        $export.= "\"".$key."\"".$delim;
                    }

                    $export = substr($export, 0, -1); // remove the last delimiter
                    $export.= PHP_EOL; // system EOL (End Of Line).
                }

                // get data/values
                foreach($obj as $key => $value)
                {
                    if (in_array($key, $ignore_fields) || is_null($value)) {
                        $export.= "\"null\"".$delim;
                    }
                    else {
                        $export.= "\"".str_replace($delim, '', $value)."\"".$delim; // remove delimiter from value to avoid import errors, no need to $db->escape() here, will be done on import
                    }
                }
                
                $export = substr($export, 0, -1); // remove the last delimiter
                
                if ($i < $num - 1) {
                    $export.= PHP_EOL; // system EOL (End Of Line).
                }
                
                $i++;
            }
            else
            {// sql
                $export.= '(';

                foreach($obj as $key => $value)
                {
                    if (in_array($key, $ignore_fields) || is_null($value)) {
                        $export.= "null,";
                    }
                    else {
                        $export.= "'".$db->escape($value)."',";
                    }
                }

                $export = substr($export, 0, -1); // remove the last ','
                $export.= '),';
            }
        }

        if ($num > 0 && !$to_csv) {//if (! empty($export)) {
            $export = substr($export, 0, -1); // remove the last ','
            $export.= ';';
        }
    }
    
    return $export;
}

function exportCSV($csv, $tablename)
{
    global $db, $conf;
    
    $export = '';
    $delim = (! empty($conf->global->EXPORT_CSV_SEPARATOR_TO_USE)?$conf->global->EXPORT_CSV_SEPARATOR_TO_USE:';');

    if (! empty($tablename))
    {
        $export.= 'INSERT INTO `'.$tablename.'` ';

        $rows = explode(PHP_EOL, $csv); // PHP_EOL which represents the current's system EOL (End Of Line).

        $i = 0;

        foreach($rows as $row)
        {
            $export.= '(';
            
            $row = trim($row); // clean row
            
            $cols = explode($delim, $row);
            
            foreach($cols as $col)
            {
                if ($i == 0) {
                    $export.= "`".$col."`,";
                }
                else if (is_null($col) || $col == 'null') {
                    $export.= "null,";
                }
                else {
                    $export.= "'".$db->escape($col)."',";
                }
            }

            $export = substr($export, 0, -1); // remove the last ','
            if ($i == 0) {
                $export.= ') VALUES ';
            }
            else {
                $export.= '),';
            }

            $i++;
        }

        if (! empty($export)) {
            $export = substr($export, 0, -1); // remove the last ','
            $export.= ';';
        }
    }
    
    return $export;
}

function getModuleinfoFromUrl($url)
{
    $moduleinfo = array();
    
    if (! empty($url))
    {
        $url_parts = parse_url($url);
        $path_parts = pathinfo($url_parts["path"]);
        $dir_parts = explode('/', $path_parts['dirname']);

        $dir_parts_count = count($dir_parts);
        $moduleinfo['name'] = $dir_parts[$dir_parts_count - 1];//end($dir_parts);
        $moduleinfo['name_before'] = $dir_parts[$dir_parts_count - 2];
    }
    
    return $moduleinfo;
}

function getTablename($db, $moduleinfo)
{
    $tablename = '';
    
    if (is_array($moduleinfo) && count($moduleinfo) > 0)
    {
        //$classfilename = '';
        //$classname = '';
        $modulename = $moduleinfo['name'];
        $modulename_before = $moduleinfo['name_before'];

        // try to get class file name (!@! pay attention to cases order !@!)
        switch ($modulename)
        {
            case 'product_returns':
                $classfilename = 'returnedProduct';
                $classname = 'ReturnedProduct';//ucfirst($classfilename);
                break;
            case 'timesheet':
                $classfilename = $modulename;
                $classname = ucfirst($classfilename);
                $modulename = 'staff';//$modulename_before;
                break;
            case 'bank':
                $classfilename = 'account';
                $classname = ucfirst($classfilename);
                $modulename = 'compta/bank';//$modulename_before.'/'.$modulename;
                break;
            case 'supplier_proposal':
                $classfilename = 'supplier_proposal';//$modulename;
                $classname = 'SupplierProposal';
                break;
            case 'facture':
                if ($modulename_before == 'fourn') {
                    $classfilename = 'fournisseur.facture';
                    $classname = 'FactureFournisseur';
                    $modulename = 'fourn';//$modulename_before;
                    break;
                }
            case 'propal':
                $classfilename = $modulename;
                $classname = ucfirst($classfilename);
                $modulename = $modulename_before.'/'.$modulename;
                break;
            case 'commande':
                if ($modulename_before == 'fourn') {
                    $classfilename = 'fournisseur.commande';
                    $classname = 'CommandeFournisseur';
                    $modulename = 'fourn';//$modulename_before;
                    break;
                }
            default:
                $classfilename = $modulename;
                $classname = ucfirst($classfilename);
        }

        $classpath = dol_buildpath('/'.$modulename.'/class/'.$classfilename.'.class.php');

        // try to get tablename
        if (is_file($classpath)) {
            include_once $classpath;

            $module = new $classname($db);

            $tablename = ! empty($module->table_element) ? MAIN_DB_PREFIX.$module->table_element : MAIN_DB_PREFIX.$classfilename;
        }
    }
    
    return $tablename;
}

function getMoreTablenames($tablename, $moduleinfo)
{
    // get more table names if needed/exists
    $moretablenames = array();
    
    if (is_array($moduleinfo) && count($moduleinfo) > 0)
    {
        switch ($moduleinfo['name'])
        {
            case 'timesheet':
                $moretablenames[] = MAIN_DB_PREFIX.'staff_timesheet_log';
                break;
            case 'propal':
            case 'commande':
            case 'facture':
                if ($moduleinfo['name_before'] == 'fourn') {
                    $moretablenames[] = $tablename.'_det';
                    break;
                }
            case 'supplier_proposal':
            default:
                $moretablenames[] = $tablename.'det';
                break;
        }
    }
    
    return $moretablenames;
}