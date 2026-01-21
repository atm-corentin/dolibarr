<?php 

if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

dol_include_once('/gestionnotifs/class/gt_comments.class.php');

global $langs;

$langs->load('gestionnotifs@gestionnotifs');
$modname = $langs->trans("Comments");

// llxHeader(array(), $modname,'','','','',$morejs,0,0);
// die("En cours de traitement ...");



$id = GETPOST('id');
$ref = GETPOST('ref');
$action = GETPOST('action');
$page 	= GETPOST("page",'int');
// $withproject = GETPOST('withproject', 'int');
$withproject = 1;

$form = new Form($db);
$object = new Task($db);
$projectstatic  = new Project($db);
// $object->fetch($id);
// $projectstatic->fetch($id);


if ($id > 0 || !empty($ref)) {
	if ($object->fetch($id, $ref) > 0) {
		if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_TASK) && method_exists($object, 'fetchComments') && empty($object->comments)) {
			$object->fetchComments();
		}
		$projectstatic->fetch($object->fk_project);
		if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) {
			$projectstatic->fetchComments();
		}
		if (!empty($projectstatic->socid)) {
			$projectstatic->fetch_thirdparty();
		}

		$object->project = clone $projectstatic;
	} else {
		dol_print_error($db);
	}
}



$comments = new gt_comments($db);
$comments2 = new gt_comments($db);
$returned = $comments->getCommentContent(false);

$name_module = 'project_task';


$usercancreate = $user->rights->gestionnotifs->creer || $user->admin;
$comment_type = (int) GETPOST("comment_type", 'int');

$comment = $returned['comment'];
$users_affected = $returned['users_affected'];

if($action == 'create'){
	// $comment = GETPOST('comment');
	if(!empty($comment)){
		$data = [
			'fk_user' => $user->id,
			'fk_module' => $id,
			'date' => date('Y-m-d H:i:s'),
			'comment' => $db->escape($comment),
			'users_affected' => $users_affected,
			'comment_type' => $comment_type,
			'name_module' => 'project_task',
			'entity'      =>  $conf->entity,
		];
		$commentaire = new gt_comments($db);
		$result = $commentaire->create(1,$data);
		if($result){
			$commentaire->sendMailWhenReplyComment($object, $result, $_SERVER['HTTP_REFERER']);
			header('Location:commentaire.php?id='.$id);
			exit;
		}
	}
}

if($action == 'reponder'){
	// $comment = GETPOST('comment');
	$fk_comment = GETPOST('fk_comment');
	if(!empty($comment)){
		$data = [
			'fk_user' => $user->id,
			'fk_module' => $id,
			'date' => date('Y-m-d H:i:s'),
			'comment' => $db->escape($comment),
			'users_affected' => $users_affected,
			'comment_type' => $comment_type,
			'fk_comment' => $fk_comment,
			'name_module' => 'project_task',
			'entity'      =>  $conf->entity,
		];
		$commentaire = new gt_comments($db);
		$result = $commentaire->create(1,$data);
		if($result){
			$commentaire->sendMailWhenReplyComment($object, $result, $_SERVER['HTTP_REFERER']);
			header('Location:commentaire.php?id='.$id);
			exit;
		}
	}
}

if($action == 'update'){
	$entity = GETPOST('entity')?GETPOST('entity'):$conf->entity;
	// $comment = GETPOST('comment');
	$id_edit = GETPOST('id_edit');
	if(!empty($comment)){
		$data = [
			'fk_user' => $user->id,
			'fk_module' => $id,
			'comment' => $db->escape($comment),
			'users_affected' => $users_affected,
			'comment_type' => $comment_type,
			'name_module' => 'project_task',
			'entity'      =>  $entity,
		];
		$commentaire = new gt_comments($db);
		$commentaire->fetch($id_edit);
		$result = $commentaire->update($id_edit,$data);
		if($result){
			$commentaire->sendMailWhenReplyComment($object, $result, $_SERVER['HTTP_REFERER']);
			header('Location:commentaire.php?id='.$id);
			exit;
		}
	}
}

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {
    
	$comment = new gt_comments($db);

    $comment->fetch(GETPOST('id_delete'));
    $error = $comment->delete();

    header('Location: commentaire.php?id='.$id);
    exit;
}
if ($action == 'confirm_delete_ol' && GETPOST('confirm') == 'yes' ) {
    
	$id_delete = GETPOST('id_delete');
    $comment = new gt_comments($db);
	if($comment->getcountrows(' AND name_module ="project_task" AND fk_module ='.$id.' AND fk_comment='.$id_delete) > 0){
		$comments =new gt_comments($db);
		$comments->fetchAll('','',0,0,' AND name_module ="project_task" AND fk_module ='.$id.' AND fk_comment='.$id_delete);
		foreach ($comments->rows as $key => $value) {
			$comment = new gt_comments($db);
		    $comment->fetch($value->rowid);
		    $comment->delete();
		}
	}
    $comment = new gt_comments($db);
    $comment->fetch($id_delete);
    $error = $comment->delete();

    if ($error == 1) {

        header('Location: commentaire.php?id='.$id.'&page='.$page);
        exit;
    }
    else {      
        header('Location: card.php?delete=1&page='.$page);
        exit;
    }
}

$morejs  = array();

$title = $object->ref . ' - ' . $langs->trans("Notes");
if (!empty($withproject)) {
	$title .= ' | ' . $langs->trans("Project") . (!empty($projectstatic->ref) ? ': '.$projectstatic->ref : '')  ;
}
$help_url = '';

llxHeader("", $title, $help_url);

if($action == "delete"){
	$id_delete = GETPOST('id_delete');
    print $form->formconfirm("commentaire.php?id=".$id."&id_delete=".$id_delete."&page=".$page,$langs->trans('Confirmation') , $langs->trans('ConfirmDeleteObject'),"confirm_delete", 'commentaire.php?page='.$page, 0, 1);
}
$my_limit 	= 5;

// $limit 	= $conf->liste_limit+1;
$sortfield 		= GETPOST("sortfield",'alpha');
$sortorder 		= GETPOST("sortorder",'alpha');
$page 	= GETPOST("page",'int');
$limit  = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;

$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "date";
if (!$sortorder) $sortorder = "DESC";
$param = "";
if ($limit > 0 && $limit != $my_limit) $param.='&limit='.$limit;
$filter = ' AND name_module ="project_task" AND fk_module ='.$id.' AND (fk_comment IS Null or fk_comment="")';

$nbrtotal = $comments->fetchAll('DESC','date',$limit+1,$offset,$filter);
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST) || 1>0)
{
	$nbtotalofrecords = $comments2->fetchAll($sortorder, $sortfield,0,0,$filter);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}
	$userWrite = $projectstatic->restrictedProjectArea($user, 'write');
	
	if (!empty($withproject)) {
		// Tabs for project
		$tab = 'tasks';
		$head = project_prepare_head($projectstatic);
		print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'));

		$param = (isset($mode) && $mode == 'mine' ? '&mode=mine' : '');
		// Project card

		$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Title
		$morehtmlref .= $projectstatic->title;
		// Thirdparty
		if (isset($projectstatic->thirdparty->id) && $projectstatic->thirdparty->id > 0) {
			$morehtmlref .= '<br>'.$projectstatic->thirdparty->getNomUrl(1, 'project');
		}
		$morehtmlref .= '</div>';

		// Define a complementary filter for search of next/prev ref.
		if (empty($user->rights->projet->all->lire)) {
			$objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
			$projectstatic->next_prev_filter = " rowid IN (".$db->sanitize(count($objectsListId) ?join(',', array_keys($objectsListId)) : '0').")";
		}

		dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		// Usage
		if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES) || empty($conf->global->PROJECT_HIDE_TASKS) || isModEnabled('eventorganization')) {
			print '<tr><td class="tdtop">';
			print $langs->trans("Usage");
			print '</td>';
			print '<td>';
			if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
				print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_opportunity ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectFollowOpportunity");
				print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
				print '<br>';
			}
			if (empty($conf->global->PROJECT_HIDE_TASKS)) {
				print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_task ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectFollowTasks");
				print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
				print '<br>';
			}
			if (empty($conf->global->PROJECT_HIDE_TASKS) && !empty($conf->global->PROJECT_BILL_TIME_SPENT)) {
				print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_bill_time ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectBillTimeDescription");
				print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
				print '<br>';
			}
			if (isModEnabled('eventorganization')) {
				print '<input type="checkbox" disabled name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_organize_event ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("EventOrganizationDescriptionLong");
				print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
			}
			print '</td></tr>';
		}

		// Visibility
		print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
		if ($projectstatic->public) {
			print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
			print $langs->trans('SharedProject');
		} else {
			print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
			print $langs->trans('PrivateProject');
		}
		print '</td></tr>';

		// Budget
		print '<tr><td>'.$langs->trans("Budget").'</td><td>';
		if (strcmp($projectstatic->budget_amount, '')) {
			print price($projectstatic->budget_amount, '', $langs, 1, 0, 0, $conf->currency);
		}
		print '</td></tr>';

		// Date start - end project
		print '<tr><td>'.$langs->trans("Dates").'</td><td>';
		$start = dol_print_date($projectstatic->date_start, 'day');
		print ($start ? $start : '?');
		$end = dol_print_date($projectstatic->date_end, 'day');
		print ' - ';
		print ($end ? $end : '?');
		if ($projectstatic->hasDelay()) {
			print img_warning("Late");
		}
		print '</td></tr>';

		// Other attributes
		$cols = 2;
		//include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent tableforfield">';

		// Description
		print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
		print nl2br($projectstatic->description);
		print '</td></tr>';

		// Categories
		if (isModEnabled('categorie')) {
			print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
			print $form->showCategories($projectstatic->id, 'project', 1);
			print "</td></tr>";
		}

		print '</table>';

		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();

		print '<br>';
	}

	// $param .= '&id='.$id;

	$head = task_prepare_head($object);
	print dol_get_fiche_head($head, 'tab_commentairetask', $langs->trans('Task'), -1, 'projecttask', 0, '', 'reposition');


	$param = (GETPOST('withproject') ? '&withproject=1' : '');
	$linkback = GETPOST('withproject') ? '<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>' : '';

	if (!GETPOST('withproject') || empty($projectstatic->id)) {
		$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1);
		$object->next_prev_filter = " fk_projet IN (".$db->sanitize($projectsListId).")";
	} else {
		$object->next_prev_filter = " fk_projet = ".$projectstatic->id;
	}

	$morehtmlref = '';

	// Project
	if (empty($withproject)) {
		$morehtmlref .= '<div class="refidno">';
		$morehtmlref .= $langs->trans("Project").': ';
		$morehtmlref .= $projectstatic->getNomUrl(1);
		$morehtmlref .= '<br>';

		// Third party
		$morehtmlref .= $langs->trans("ThirdParty").': ';
		$morehtmlref .= $projectstatic->thirdparty->getNomUrl(1);
		$morehtmlref .= '</div>';
	}

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param);


print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'" id="list_ov" >';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input name="pagem" type="hidden" value="'.$page.'">';
	print '<input name="offsetm" type="hidden" value="'.$offset.'">';
	print '<input name="limitm" type="hidden" value="'.$limit.'">';
	print '<input name="filterm" type="hidden" value="'.$filter.'">';
	print '<input name="id" type="hidden" value="'.$id.'">';
	print '<input name="action_pdf" class="action_pdf" type="hidden" value="">';
    print '<input type="hidden" name="withproject" value="'.$withproject.'">';


	print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbrtotal, $nbtotalofrecords, 'title_accountancy.png', 0, '', '', $limit);
print '</form>';

print '<div class="comments_list">';
	print '<table class="noborder" width="100%">';

		$newcommentfiles = $comments->getJoinedFiles($comments);
		// d($newcommentfiles,0);
		if($comments->rows && count($comments->rows)>0){
			for ($i=0; $i < count($comments->rows); $i++) { 
				$item = $comments->rows[$i];
				$user_ = new User($db);
				$user_->fetch($item->fk_user);

				print '<tr>';
					print '<td>'.$user_->getNomUrl(-3, '', 0, 0, 0, 0, '', 'paddingright valigntextbottom').'</td>';
					print '<td>';
						print '<div class="comemnt_parent">';
							print '<span ><b>'.$user_->firstname.' '.$user_->lastname.'</b></span>';
							print '<span class="date_comment">'.date('d/m/Y H:i',strtotime($item->date)).'</span>';
							if($user->id == $item->fk_user){
								print '<a class="editcomment" id="editcomment_'.$item->rowid.'" data-id="'.$item->rowid.'">';
									print img_edit($langs->trans('Edit'));
								print '</a>';
								print '<a class="annulcomment" id="annulcomment_'.$item->rowid.'" data-id="'.$item->rowid.'">';
									print '<span class="fa fa-remove" title="'.$langs->trans('Cancel').'"></span>';
								print '</a>';
								print '<a href="commentaire.php?id='.$id.'&id_delete='.$item->rowid.'&action=delete" class="removecomment">'.img_delete($langs->trans('Delete')).'</a>';
							}
							print '<a class="repond" id="repond_'.$item->rowid.'" data-id="'.$item->rowid.'">'.$langs->trans("repond").'</a>';
							if($comments->getcountrows(' AND name_module ="project_task" AND fk_module ='.$id.' AND fk_comment='.$item->rowid) > 0){
								print '<a class="down"><i class="fas fa-chevron-down"></i></a>';
								print '<a class="up"><i class="fas fa-chevron-up"></i></a>';
							}
							print '<br>';
							// print '<span id="text_comment_'.$item->rowid.'" class="text_comment" >'.dol_string_onlythesehtmltags($item->comment).'</span>';
							print '<span id="text_comment_'.$item->rowid.'" class="text_comment" >'.dol_string_onlythesehtmltags($item->comment);
							$filesdiv = $comments->getJoinedFiles($item);
							print $filesdiv;
							print '</span>';

							print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
								print '<input type="hidden" name="action" value="update" />';
							    print '<input type="hidden" name="id" value="'.$id.'" />';
							    print '<input type="hidden" class="id_edit" name="id_edit" value="'.$item->rowid.'" />';
								print '<input type="hidden" name="entity" value="'.$item->entity.'" />';
								print '<input type="hidden" name="page" value="'.$page.'" />';
								print '<div id="edit_comment_'.$item->rowid.'" class="edit_comment">';
									print $comments->dolEditorWithSelectUsers($item, 'txtcomment_update_', $item->comment, $item->rowid, $item->fk_user);
									print $filesdiv;
									// print '<textarea class="comment" name="comment" onchange="textarea_autosize(this)" placeholder="'.$langs->trans('your_comment').'">'.$item->comment.'</textarea>'; 
									print '<input type="submit" value="'.$langs->trans("sauvg").'" class="sauvg">';
								print '</div>';
							print '</form>';

							print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';

								print '<input type="hidden" name="action" value="reponder" />';
							    print '<input type="hidden" name="id" value="'.$id.'" />';
							    print '<input type="hidden" class="fk_comment" name="fk_comment" value="'.$item->rowid.'" />';

								print '<div id="repond_comment_'.$item->rowid.'" class="repond_comment">';
									print $user->getNomUrl(-3, '', 0, 0, 0, 0, '', 'paddingright valigntextbottom');
									print $comments->dolEditorWithSelectUsers($comments, 'txtcomment_reponder_', '', $item->rowid, $item->fk_user);
									print $newcommentfiles;
									// print '<textarea class="comment" name="comment" onchange="textarea_autosize(this)" placeholder="'.$langs->trans('your_comment').'"></textarea>';
									print '<div class="action_repond"> ';
										print '<input type="submit" value="'.$langs->trans("sauvg").'" class="btnAction">';
										print '<a class="bntAction cancel">Annuler</a>';
									print '</div>';
								print '</div>';

							print '</form>';
							$cmt = new gt_comments($db);
							print $cmt->getcommentschild('project_task', $id, $item->rowid, 'id', $srch_comment = '', $newcommentfiles, false);
						print '</div>';
					print '</td>';
				print '</tr>';	
			}
		}

	    print '<input type="hidden" name="action" value="create" />';
	    print '<input type="hidden" name="id" value="'.$id.'" />';
		print '<tr>';
			print '<td>'.$user->getNomUrl(-3, '', 0, 0, 0, 0, '', 'paddingright valigntextbottom').'</td>';
			print '<td>';
				print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
					print '<input type="hidden" name="action" value="create" />';
				    print '<input type="hidden" name="id" value="'.$id.'" />';
					print '<input type="hidden" name="page" value="'.$page.'" />';
					print $comments->dolEditorWithSelectUsers($comments, 'txtcomment');
					print $newcommentfiles;
					// print '<textarea class="comment" name="comment" onchange="textarea_autosize(this)" placeholder="'.$langs->trans('your_comment').'"></textarea>';
					print '<input type="submit" value="'.$langs->trans("sauvg").'" class="btnAction">';
				print '</form>';
			print '</td>';
		print '</tr>';
	print '</table>';
print '</div>'



?>

<script>
	$(document).ready(function() {
	})
</script>

<?php

llxFooter();
