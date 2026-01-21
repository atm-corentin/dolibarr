<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2016  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Thibault FOUCART        <support@ptibogxiv.net>
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
 */

/**
 *       \file       htdocs/adherents/subscription.php
 *       \ingroup    member
 *       \brief      tab for Adding, editing, deleting a member's memberships
 */

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

dol_include_once('/gestionnotifs/class/gt_comments.class.php');
dol_include_once('/gestionnotifs/class/gt_notifcs.class.php');

if (empty($conf->gestionnotifs->enabled) || (!$user->rights->gestionnotifs->lire && !$user->admin)) accessforbidden();

$langs->loadLangs(array("sendings", "companies", "bills", 'deliveries', 'orders', 'stocks', 'other', 'propal', 'users', 'admin', 'projects', 'products', 'suppliers', 'compta'));
$langs->load('gestionnotifs@gestionnotifs');
//$_GET['optioncss'] = 'print';

$action 	= GETPOST('action', 'aZ09');
$confirm 	= GETPOST('confirm', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$toselect 	= GETPOST('toselect', 'array');
$id 		= GETPOST('rowid', 'int') ? GETPOST('rowid', 'int') : GETPOST('id', 'int');
$ref 		= GETPOST('ref', 'alphanohtml');
$typeid 	= GETPOST('typeid', 'int');
$withmail 	= GETPOST('withmail', 'int');
$cancel 	= GETPOST('cancel');
$optioncss 	= GETPOST('optioncss', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'gestionnotifs_comments_list';

// $sendrecumasse 	= GETPOST('sendrecumasse');

$srch_name_module 	= GETPOST('srch_name_module', 'alpha');
$srch_fk_module 	= GETPOST('srch_fk_module', 'int');
$srch_comment 		= GETPOST('srch_comment', 'alpha');
$search_date_start 	= dol_mktime(0, 0, 0, GETPOST('search_start_datemonth', 'int'), GETPOST('search_start_dateday', 'int'), GETPOST('search_start_dateyear', 'int'));
$search_date_end 	= dol_mktime(0, 0, 0, GETPOST('search_end_datemonth', 'int'), GETPOST('search_end_dateday', 'int'), GETPOST('search_end_dateyear', 'int'));

// $srch_comment_type 	= GETPOST('srch_comment_type', 'int') ? (int) GETPOST('srch_comment_type', 'int') : $gtc::PRIVATE;
$srch_comment_type 	= GETPOST('srch_comment_type', 'int') ? (int) GETPOST('srch_comment_type', 'int') : 0;

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
	$sortfield = "tms";
}
if (!$sortorder) {
	$sortorder = "DESC";
}

$form 	   = new Form($db);
$formfile  = new FormFile($db);
$formmail  = new FormMail($db);
$object 	= new gt_comments($db);
$gt_comments = new gt_comments($db);
$gtc = new gt_comments($db);

$now = dol_now();
// $res = dolibarr_set_const($db, 'GTNOTIF_LAST_TIME_VIEWED_COMMENTS_BY_USER_'.$user->id, $db->idate($now), 'chaine', 0, '', $conf->entity);
// fetch optionals attributes and labels

$errmsg = '';

$usercancreate = $user->rights->gestionnotifs->creer;
$usercandelete = $user->rights->gestionnotifs->supprimer;

//Select mail models is same action as presend
if (GETPOST('modelselected')) {
	$action = 'presend';
}

$arrayfields = array(
	'obj.name_module' 	=> array('label'=> $langs->trans("Type"), 'checked'=> 1),
	'obj.fk_module' 	=> array('label'=> $langs->trans("LinkedObject"), 'checked'=> 1),
	'obj.tms' 			=> array('label'=> $langs->trans("LastComment"), 'checked'=> 1),
	'count_comment' 	=> array('label'=> $langs->trans("Total"), 'checked'=> 1),
);



$arr_srch = [$gtc::PRIVATE, $gtc::PUBLIC, -1];
if(!$user->admin && !in_array($srch_comment_type, $arr_srch)) {
	$srch_comment_type = -1;
} elseif($user->admin && empty($srch_comment_type)) {
	$srch_comment_type = -2;
}

if(!empty($conf->societedealeco->enabled)) {
	$srch_comment_type = -2;
}


/*
* Actions
*/

if (GETPOST('cancel', 'alpha')) {
	$action = 'list'; $massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'predelete') {
	$massaction = '';
}


$arrayofselected = is_array($toselect) ? $toselect : array();

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$srch_fk_module		= '';
	$srch_name_module 	= '';
	$srch_comment 		= '';
	$search_date_start 	= '';
	$search_date_end 	= '';
	$toselect 			= '';

	$srch_array_options = array();
}

// Selection of new fields
include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Acknowledged objects en masse
if (($massaction == 'preacknowledged') && $usercancreate) {
	$error = 0;
	$nbchanged = 0;
	
	$db->begin();
	
	foreach ($toselect as $obj_comment) {
		$tempobj = explode(':', $obj_comment);

		if(isset($tempobj[0]) && isset($tempobj[1])) {
			$gt_comments->setViewedComment($tempobj[0], $tempobj[1]);
			$nbchanged++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("RecordsModified", $nbchanged), null, 'mesgs');
		$db->commit();
	} else {
		$db->rollback();
	}
}

// // Delete objects en masse
// if ($massaction == 'predelete') {
// 	$error = 0;
// 	$nbchanged = 0;
// 	$ttr = 0;
// 	$ids = '';
// 	$db->begin();

// 	if($usercandelete){
// 		$nbchanged = count($toselect);
// 		foreach ($toselect as $recuid) {
// 			$ids = ($ids != '') ? $ids.','.$recuid : $recuid;
// 		}
// 		if($ids != '') {
// 			$result = $db->query("DELETE FROM ".MAIN_DB_PREFIX.$object->table_element." WHERE rowid IN (".$ids.") ");
// 		    if (!$result) setEventMessages($db->lasterror(), null, 'errors');
// 		}

// 		if (!$error) {
// 			setEventMessages($langs->trans("RecordsDeleted", $nbchanged), null, 'mesgs');
// 			$db->commit();
// 		} else {
// 			$db->rollback();
// 		}
// 	}
// 	else{
// 		setEventMessages($langs->trans("NotAuthorized"), null, 'warnings');
// 	}
// 	$massaction='';
// 	header('Location: '.$_SERVER['PHP_SELF']);
// 	exit();
// }


$sql = 'SELECT obj.fk_module, obj.name_module, COUNT(obj.comment) AS count_comment, MAX(obj.tms) as tms FROM '.MAIN_DB_PREFIX.'gt_comments as obj';
$sql .= " WHERE obj.entity IN (0,".(int) $conf->entity.")";

$sql .= ($srch_name_module != '')  ? " AND obj.name_module = '".$db->escape($srch_name_module)."'" : "";
$sql .= ($srch_comment != '')  ? " AND obj.comment like '%".$db->escape($srch_comment)."%'" : "";
$sql .= $srch_fk_module > 0 ? ' AND obj.fk_module = '.(int) $srch_fk_module : '';

if ($search_date_start) {
	$sql .= " AND CAST(obj.tms as date) >= '".dol_print_date($search_date_start, "%Y-%m-%d")."'";
}
if ($search_date_end) {
	$sql .= " AND CAST(obj.tms as date) <= '".dol_print_date($search_date_end, "%Y-%m-%d")."'";
}

$sql .= $gtc->filtebyCommentType($srch_comment_type);

$sql .= ' GROUP BY obj.name_module, obj.fk_module';

$sql .= $db->order($sortfield, $sortorder);

// echo $sql;

// Count total nb of records with no order and no limits
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST) || 1>0) {
	$resql = $db->query($sql);
	if ($resql) {
		$nbtotalofrecords = $db->num_rows($resql);
	} else {
		dol_print_error($db);
	}
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}
// Add limit
$sql .= $db->plimit($limit + 1, $offset);

$reslistobj = $db->query($sql);
$num = $reslistobj ? $db->num_rows($reslistobj) : 0;


$title = $langs->trans("Comments").': '.$langs->trans("LinkedObject");

/*
 * View
 */

$help_url = "";


llxHeader("", $title, $help_url);

global $array_count_object_comments;

// if(is_array($array_count_object_comments) && count($array_count_object_comments) > 0) {
// 	$res = dolibarr_set_const($db, 'GTNOTIF_LAST_TIME_VIEWED_COMMENTS_BY_USER_'.$user->id, $db->idate($now, 'tzuserrel'), 'chaine', 0, '', $conf->entity);
// }


$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
if ($srch_name_module) {
	$param .= "&srch_name_module=".urlencode($srch_name_module);
}
if ($srch_comment) {
	$param .= "&srch_comment=".urlencode($srch_comment);
}
if ($srch_fk_module) {
	$param .= "&srch_fk_module=".urlencode($srch_fk_module);
}
if ($search_date_start != '') {
	$param .= '&search_date_start='.urlencode($search_date_start);
}
if ($search_date_end != '') {
	$param .= '&search_date_end='.urlencode($search_date_end);
}

if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}

$arrayofmassactions = array();

if (!empty($usercancreate)) {
	$arrayofmassactions['preacknowledged'] = img_picto('', 'preview', 'class="pictofixedwidth"').' '.$langs->trans("ACKNOWLEDGED");
} 
// if($usercandelete)
	// $arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').' '.$langs->trans("Delete");

$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<div class="gestionnotifs">';

print '<form method="POST" id="gestionnotifslistallcomments" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';

	$morehtmlrightbeforearrow = '';
	$newcardbutton = '<span class="butAction"" onclick="gestionnotifs_all_comments(\'show\')">'.$langs->trans("Show").' '.$langs->trans("All").'</span>';
	$newcardbutton .= '<span class="butAction" onclick="gestionnotifs_all_comments(\'hide\')">'.$langs->trans("Hide").' '.$langs->trans("All").'</span>';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $_num=-1, $_nbtotalofrecords=-1, $object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1, $morehtmlrightbeforearrow);

	// print $gtc->inputRadioCommentType($srch_comment_type, 'srch_comment_type', $show_public = false);
	$cc = $gtc->countByCommentType();

	$n = 'srch_comment_type';

	$st = '<div class="gestionnotifs_commentstype">';

	if($user->admin) {
		$st .= '<label class="marginrightonly">';
		$st .= '<input type="radio" name="'.$n.'" value="-2"> '.$langs->trans('All');
		if($cc['all'] > 0) {

			$st .= '<span class="opacitymedium">';
			$st .= '('.$cc['all'].')';
			$st .= '</span>';
		}
		$st .= '</label>';
	}

 	$st .= '<label class="marginrightonly">';
 	$st .= '<input type="radio" name="'.$n.'" value="'.$gtc::PRIVATE.'"> '.$gtc->comment_types[$gtc::PRIVATE];
	if($cc['private'] > 0) {

		$st .= '<span class="opacitymedium">';
		$st .= '('.$cc['private'].')';
		$st .= '</span>';
	}
	$st .= '</label>';

	$st .= '<label class="marginrightonly">';
	// $st .= '<input type="radio" name="'.$n.'" value="'.$gtc::PUBLIC.'"> '.$langs->trans('Public');
	$st .= '<input type="radio" name="'.$n.'" value="'.$gtc::PUBLIC.'"> Public';
	if($cc['public'] > 0) {

		$st .= '<span class="opacitymedium">';
		$st .= '('.$cc['public'].')';
		$st .= '</span>';
	}
	$st .= '</label>';

	// $st .= '<label class="marginrightonly">';
	// $st .= '<input type="radio" name="'.$n.'" value="'.$gtc::PUBLIC_WITH_MAIL.'"> '.$gtc->comment_types[$gtc::PUBLIC_WITH_MAIL];
	// $st  .= '</label>';

	$st .= '<label class="marginrightonly">';
	// $st .= '<input type="radio" name="'.$n.'" value="-1"> '.$gtc->comment_types[$gtc::PRIVATE].' & '.$langs->trans('Public');
	$st .= '<input type="radio" name="'.$n.'" value="-1"> '.$gtc->comment_types[$gtc::PRIVATE].' & Public';
	if($cc['public_private'] > 0) {

		$st .= '<span class="opacitymedium">';
		$st .= '('.$cc['public_private'].')';
		$st .= '</span>';
	}
	$st .= '</label>';


 	$st = str_replace('value="'.$srch_comment_type.'"', ' value="'.$srch_comment_type.'" checked', $st);

 	if(empty($conf->societedealeco->enabled))
 		print $st;

	$langs->load('members');

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	if ($massactionbutton) {
		$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
	}

	// print '<div class="right">';
	// 	print '<a href="./list.php?action=list_pdf'.$param.'" target="_blank" class="butAction"><i class="fa fa-file-pdf-o" style="color:#fff;"></i> '.$langs->trans('export_pdf').'</a>';
	// 	print '<a href="./list.php?action=list_excel'.$param.'" class="butAction nomarginleft"><i class="fa fa-file-excel-o" style="color:#fff;"></i> '.$langs->trans('export_excel').'</a>';
	// print '</div><br>';

	/*
 	* List objects
 	*/
	print '<table class="tagtable nobottomiftotal liste gestionnotifs_commentslist">'."\n";

	// Line for filters fields
	print '<tr class="liste_titre_filter">';

		// Type 
		if (!empty($arrayfields['obj.name_module']['checked'])) {
			print '<td class="liste_titre left width100">';
        		print $object->selectNameModules($srch_name_module, 'srch_name_module', true).'</td>';
			print '</td>';
		}

		print '<td class="liste_titre left">';
		print '</td>';

		// Object 
		if (!empty($arrayfields['obj.fk_module']['checked'])) {
			print '<td class="liste_titre left">';
        		// print $object->selectNameModules($srch_name_module, 'srch_name_module', true).'</td>';
			print '</td>';
		}

		// LastComment
		if (!empty($arrayfields['obj.tms']['checked'])) {
			print '<td class="liste_titre width150 center">';
			print '<div class="nowrap">';
				print $form->selectDate($search_date_start, 'search_start_date', 0, 0, 1, "search_form", 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
			print '</div>';
			
			print '<div class="nowrap">';
				print $form->selectDate($search_date_end, 'search_end_date', 0, 0, 1, "search_form", 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
			print '</div>';
			print '</td>';
		}

		if (!empty($arrayfields['count_comment']['checked'])) {
			print '<td class="liste_titre"></td>';
		}

		print '<td class="liste_titre">';
		print '<input class="flat maxwidth200" type="text" name="srch_comment" value="'.dol_escape_htmltag($srch_comment).'">';
		print '</td>';


		// Action column
		print '<td class="liste_titre right">';
			$searchpicto = $form->showFilterButtons();
			print $searchpicto;
		print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';

		// Type 
		if (!empty($arrayfields['obj.name_module']['checked'])) {
			print_liste_field_titre($arrayfields['obj.name_module']['label'], $_SERVER["PHP_SELF"], 'obj.name_module', '', $param, '', $sortfield, $sortorder, 'left width150 minwidth150 nowraponall ');
		}
		print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'left ');

		// Object 
		if (!empty($arrayfields['obj.fk_module']['checked'])) {
			print_liste_field_titre($arrayfields['obj.fk_module']['label'], $_SERVER["PHP_SELF"], 'obj.fk_module', '', $param, '', $sortfield, $sortorder, 'left width200 minwidth200 nowraponall ');
		}

		// LastComment
		if (!empty($arrayfields['obj.tms']['checked'])) {
			print_liste_field_titre($arrayfields['obj.tms']['label'], $_SERVER["PHP_SELF"], 'tms', '', $param, '', $sortfield, $sortorder, 'center width100 nowraponall ');
		}

		if (!empty($arrayfields['count_comment']['checked'])) {
			print_liste_field_titre($arrayfields['count_comment']['label'], $_SERVER["PHP_SELF"], 'count_comment', '', $param, '', $sortfield, $sortorder, 'right width50 nowraponall ');
		}
		// print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'left width50 ');
		print_liste_field_titre($langs->trans("Comments"), $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'left ');


		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
		// $totalarray['nbfield']++;

		// print '<th class="action center"></th>';
	print "</tr>\n";

	$i = 0;


	$ardolv = explode(".", DOL_VERSION);
	$dolvs = $ardolv[0];

	if ($reslistobj) {

		print '<tr class="oddeven">';
		print "</tr>\n";
		

		while ($i < min($num, $limit)) {
			$obj = $db->fetch_object($reslistobj);
			
			$error = 0;
			
			// d($obj->name_module,0);

			if(!$obj->name_module || !isset($gtc->class_modules[$obj->name_module])) $error++;

			$res = 0;
			
			if(isset($gtc->class_modules[$obj->name_module])) {
				$objectmod = new $gtc->class_modules[$obj->name_module]($db);
				$res = $objectmod->fetch($obj->fk_module);
			}
			
			if(!$res) $error++;

			if(!$error) {
				
				$morecss = (isset($array_count_object_comments[(int)$obj->fk_module.'::'.$obj->name_module])) ? 'new_comment' : '';

				print '<tr class="oddeven '.$morecss.'">';

				// Type.
				if (!empty($arrayfields['obj.name_module']['checked'])) {
					print '<td>';
						print $gtc->name_modules[$obj->name_module];
					print '</td>';
				}

				print '<td class="gestionnotifs_newcomment">';
					if($morecss) {
						print '<span class="fa fa-comment valignmiddle marginleftonly comment red"></span>';
					}
				print '</td>';

				// Object.
				if (!empty($arrayfields['obj.fk_module']['checked'])) {
					print '<td>';
						if($res > 0)
							print $objectmod->getNomUrl(1);
					print '</td>';
				}


				// LastComment
				if (!empty($arrayfields['obj.tms']['checked'])) {
					// print '<td class="center nowraponall">'.dol_print_date($db->idate($obj->tms), 'dayhour').'</td>';
					print '<td class="center nowraponall">'.date('d/m/Y H:i',strtotime($obj->tms)).'</td>';
				}

				if (!empty($arrayfields['count_comment']['checked'])) {
					print '<td class="right view_comments nowraponall">';
						if($res > 0) {
							echo  '<span class="opacitymedium marginrightonly">('.(int)$obj->count_comment.')</span>';
							if($obj->name_module == "project_task"){
								print '<a href="'.dol_buildpath('/gestionnotifs/tasks/commentaire.php?id='.$obj->fk_module, 1).'" target="_blank">';
							}else{
								print '<a href="'.dol_buildpath('/gestionnotifs/'.$obj->name_module.'/commentaire.php?id='.$obj->fk_module, 1).'" target="_blank">';
							}

								$iconimg = ($dolvs < 11) ? 'view' : 'external-link-alt';
								print img_picto($langs->trans('Comments'), $iconimg, ' class="linkobject"');
							print '</a>';
						}
					print '</td>';
				}

				// Comments
				print '<td class="left">';
				print '<span class="span_comments" data-loaded="0" >';
				print '</span>';
				print '</td>';

				// Action column
				print '<td class="center showhidecomments nowraponall">';
					print '<span class="downtop down" onclick="gestionnotifs_show_comments(this,'.$obj->fk_module.', \''.$obj->name_module.'\')">';
					// print img_picto('', 'chevron-down', 'class="pictofixedwidth"');
					print '<i class="fas fa-chevron-down"></i>';
					print '</span>';
					print '<span class="downtop top" onclick="gestionnotifs_hide_comments(this,'.$obj->fk_module.', \''.$obj->name_module.'\')">';
					// print img_picto('', 'chevron-top', 'class="pictofixedwidth"');
					print '<i class="fas fa-chevron-up"></i>';
					print '</span>';

					if ($massactionbutton || $massaction) {
						$selected = (in_array($obj->ref, $arrayofselected)) ? 1 : 0;
						print '<input id="cb'.$obj->ref.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->name_module.':'.$obj->fk_module.'"'.($selected ? ' checked="checked"' : '').'>';
					}
				print '</td>';
				print "</tr>";
			}

			$i++;
		}

		// // If no record found
		// if ($num == 0) {
		// 	$colspan = $totalarray['nbfield'];
		// 	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
		// }else{
		// 	include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';
		// }

		$db->free($resql);

	} else {
		dol_print_error($db);
	}
	print "</table>";
print '</form>';
print '</div>';

// print '<div class="opacitymedium left" style="font-size:12px;">';
// $sql = '';

// $tmpvar = 'GTNOTIF_LAST_TIME_VIEWED_COMMENTS_BY_USER_'.$user->id;
// $last_comment_viewed = isset($conf->global->$tmpvar) ? $conf->global->$tmpvar : '';

// $sql = 'SELECT obj.fk_module, obj.name_module, COUNT(obj.rowid) as total_unreded FROM '.MAIN_DB_PREFIX.'gt_comments as obj';
// $sql .= " WHERE obj.entity IN (0,".(int) $conf->entity.")";

// if ($last_comment_viewed) {
// 	$sql .= " AND obj.tms >= <b>'".$last_comment_viewed."' </b>";
// }

// $sql .= " AND obj.fk_user != '".$user->id."'";

// $searchinuser = " ( users_affected = ".$user->id;
// $searchinuser .= " OR users_affected LIKE '".$user->id.",%'";
// $searchinuser .= " OR users_affected LIKE '%,".$user->id.",%'";
// $searchinuser .= " OR users_affected LIKE '%,".$user->id. "')";

// $sql .= ' AND ((obj.comment_type = '.gt_comments::PRIVATE.' AND '.$searchinuser.')';
// $sql .= ' OR (comment_type = '.gt_comments::PRIVATE.' AND fk_user = '.$user->id.')';
// $sql .= ' OR (obj.comment_type = '.gt_comments::PUBLIC_WITH_MAIL.' OR comment_type = '.gt_comments::PUBLIC.'))';

// $sql .= ' GROUP BY obj.name_module, obj.fk_module';

// echo $sql;

// print '</div>';

// echo '<br>';
// print '<div class="opacitymedium right">';
// echo $db->idate($now);
// print '</div>';


?>
<script type="text/javascript" class="gestionnotifsscript">

	function gestionnotifs_all_comments(action){
		var toclick = 'down';
		if(action === 'hide') toclick = 'top';

		var i = 0;
		$('.gestionnotifs_commentslist tr.oddeven').each(function(i, row){
			// setTimeout( function() { 
				$(row).find('.showhidecomments .downtop.'+toclick).click();
			// }, 500*i);
			i++;
	    });
	}

	function gestionnotifs_hide_comments(spanrow, fk_module, name_module){
		var span_comments = $(spanrow).parent('td').parent('tr').find('span.span_comments');

		span_comments.hide();
		$(spanrow).hide();
		$(spanrow).parent('td').find('span.down').show();
	}

	function gestionnotifs_show_comments(spanrow, fk_module, name_module){

		var span_comments = $(spanrow).parent('td').parent('tr').find('span.span_comments');
		var tr_comment = $(spanrow).parent('td').parent('tr');
		var srch_comment = '<?php echo dol_escape_js($srch_comment); ?>';
		var srch_comment_type = '<?php echo dol_escape_js($srch_comment_type); ?>';

		check = span_comments.attr('data-loaded');

		if(check === '0') {
			$.ajax({
		        data:{
		        	'ajxaction': 'get_comments'
		        	,'fk_module': fk_module
		        	,'name_module': name_module
		        	,'srch_comment': srch_comment
		        	,'srch_comment_type': srch_comment_type
		        },
		        url:"<?php echo dol_escape_js(dol_buildpath('/gestionnotifs/ajax/get_comments.php', 1)); ?>",
		        type:'POST',
		        dataType:'json',
		        success:function(returned){
		            if(returned) {
						span_comments.attr('data-loaded', 1);
						span_comments.html(returned);
						tr_comment.find(".classfortooltip").tooltip({
							show: { collision: "flipfit", effect:'toggle', delay:50 },
							hide: { delay: 50 }, 	/* If I enable effect:'toggle' here, a bug appears: the tooltip is shown when collpasing a new dir if it was shown before */
							tooltipClass: "mytooltip",
							content: function () {
	              				return $(this).prop('title');		/* To force to get title as is */
	          				}
	            		});
		            }
		        }
	    	});
		}

		$(spanrow).hide();
		$(spanrow).parent('td').find('span.top').show();
		span_comments.show();
	}

	$(window).on('load', function() {
		// $('span.gestionnotifs_calc_unreded_comments').hide();
	});
</script>
<?php
llxFooter();



// End of page
$db->close();
