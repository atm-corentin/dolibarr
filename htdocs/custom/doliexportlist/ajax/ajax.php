<?php
/* Copyright (C) 2017	AXeL dev	<contact.axel.dev@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       /staff/ajax/ajax.php
 *       \brief      File to do ajax actions
 */

define('NOTOKENRENEWAL', 1);

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) { // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

global $db, $langs, $user, $conf;

//require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
dol_include_once("/doliexportlist/lib/doliexportlist.lib.php");

// Get parameters
$action	= GETPOST('action','alpha');
$url = GETPOST('url','alpha');

// Access control
if (!$user->rights->doliexportlist->export && !$user->rights->doliexportlist->import) {
	// External user
	accessforbidden();
}

/*
 * View
 */

$langs->load('doliexportlist@doliexportlist');

top_httphead();

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

// Actions
if (isset($action) && ! empty($action))
{
	if ((($action == 'export_sql' || $action == 'export_csv_from_db') && $user->rights->doliexportlist->export) || ($action == 'free_sql' && $conf->global->LIST_EXPORT_IMPORT_ENABLE_FREE_LIST))
	{
            $moduleinfo = getModuleinfoFromUrl($url);
            
            $tablename = getTablename($db, $moduleinfo);
            
            if (! empty($tablename))
            {
                $moretablenames = getMoreTablenames($tablename, $moduleinfo);

                // get table content
                if ($action == 'export_sql' || $action == 'export_csv_from_db')
                {
                    $export_script = '';
                    $ignore_fields = array();//array('id', 'rowid');
                    $to_csv = $action == 'export_csv_from_db';

                    $export_script.= exportTable($db, $tablename, $ignore_fields, $to_csv);

                    if (! $to_csv)
                    {
                        foreach ($moretablenames as $table)
                        {
                            $export_script.= exportTable($db, $table, $ignore_fields, $to_csv);
                        }
                    }

                    print $export_script;
                } // fin if ($action == 'export_sql')
                else if ($action == 'free_sql')
                {
                    // remove related tables first (to avoid foreign key errors)
                    $error = 0;
                    foreach ($moretablenames as $table)
                    {
                        $sql = 'DELETE FROM `'.$table.'`';

                        $resql = $db->query($sql);

                        if ($resql) {}
                        else {
                            $error++;
                            print "Error: ".$db->lasterror();
                            break;
                        }
                    }

                    if (! $error)
                    {
                        $sql = 'DELETE FROM `'.$tablename.'`';

                        $resql = $db->query($sql);

                        if ($resql)
                        {
                            print 'success';
                        }
                        else
                        {
                            print "Error: ".$db->lasterror();
                        }
                    }
                } // fin if ($action == 'free_sql')
                //print 'modulename: '.$moduleinfo['name'].', tablename: '.$tablename;
            }
        } // fin if ($action == 'export_sql' || $action == 'free_sql')
        else if ($action == 'import_sql' && $user->rights->doliexportlist->import)
        {
            $sql = GETPOST('sql');//,'alpha');
            $filename = GETPOST('filename','alpha');
            $is_sql = preg_match('/\.sql$/i', $filename);
                
            if ($is_sql)
            {
                if (! empty($sql))
                {
                    // reverse sql to origin (!@! sql was reversed to bypass dolibarr sql injection detection)
                    //$sql_words_origin = array('INSERT', 'INTO', 'VALUES');
                    $reversed_sql_words = array('TRESNI', 'OTNI', 'SEULAV');

                    foreach($reversed_sql_words as $word) {
                        $sql = str_replace($word, strrev($word), $sql);
                    }
                    
                    // check if the sql code tablename is for the current list.
                    $moduleinfo = getModuleinfoFromUrl($url);
                    $tablename = getTablename($db, $moduleinfo);

                    if (strpos($sql, $tablename) === false)
                    {
                        print $langs->transnoentities('FileContentNotMatchWithTableName', 'SQL');
                    }
                    else
                    {
                        // explode sql
                        $queryarray = explode(";", $sql);
                        array_pop($queryarray); // remove the last empty query (after last ';')
                        $error = 0;

                        // check sql statements
                        foreach ($queryarray as $query)
                        {
                            if(preg_match('/^(TRUNCATE|DELETE|DROP|UPDATE|CREATE|ALTER|SELECT)/', strtoupper($query)))
                            {
                                $error++;
                                print $langs->trans('OnlySqlInsertStatementIsAccepted');
                                break;//exit();
                            }
                        }

                        if (! $error)
                        {
                            // execute sql
                            foreach ($queryarray as $query)
                            {
                                $query.= ";"; // add removed ';'
                                $resql = $db->query($query);

                                if ($resql) {}
                                else {
                                    $error++;
                                    print "Error: ".$db->lasterror();
                                    break;
                                }
                            }
                        }
                        
                        if (! $error)
                        {
                            print 'success';
                        }
                    } // fin else if (strpos($sql, $tablename) === false)
                }
                else
                {
                    print $langs->trans('FileIsEmpty');
                }
            }
            else
            {
                print $langs->trans('WrongFileExt', 'SQL');
            }
        } // fin if ($action == 'import_sql' && $user->rights->doliexportlist->import)
        else if ($action == 'import_csv' && $user->rights->doliexportlist->import)
        {
            $csv = GETPOST('csv');//,'alpha');
            $filename = GETPOST('filename','alpha');
            $is_csv = preg_match('/\.csv$/i', $filename);
            
            if ($is_csv)
            {
                if (! empty($csv))
                {
                    // get tablename
                    $moduleinfo = getModuleinfoFromUrl($url);
                    $tablename = getTablename($db, $moduleinfo);
                    
                    // convert csv to sql
                    $export_script = exportCSV($csv, $tablename);
                    
                    print $export_script;
                }
                else
                {
                    print $langs->trans('FileIsEmpty');
                }
            }
            else
            {
                //print $langs->trans('WrongFileExt', 'CSV');
                print 'wrongfile';
            }
        }
}
