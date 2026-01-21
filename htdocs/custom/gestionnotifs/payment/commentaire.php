<?php 

if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

dol_include_once('/gestionnotifs/class/gt_comments.class.php');

global $langs;

$langs->load('gestionnotifs@gestionnotifs');
$modname = $langs->trans("Comments");
$langs->loadLangs(array('bills', 'banks', 'companies'));

$object  = new Paiement($db);
$comments  = new gt_comments($db);
$comments2 = new gt_comments($db);

$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('socid', 'int'));
$object->fetch($id);
$action = GETPOST('action');
$page 	= GETPOST("page",'int');
$form = new Form($db);

$comments = new gt_comments($db);
$returned = $comments->getCommentContent(false);

$name_module = 'payment';


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
			'name_module' => 'payment',
			'entity'      =>  $conf->entity,
		];
		$commentaire = new gt_comments($db);
		$result = $commentaire->create(1,$data);
		if($result){
			$commentaire->sendMailWhenReplyComment($object, $result, $_SERVER['HTTP_REFERER']);
			header('Location:commentaire.php?socid='.$id);
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
			'name_module' => 'payment',
			'entity'      =>  $conf->entity,
		];
		$commentaire = new gt_comments($db);
		$result = $commentaire->create(1,$data);
		if($result){
			$commentaire->sendMailWhenReplyComment($object, $result, $_SERVER['HTTP_REFERER']);
			header('Location:commentaire.php?socid='.$id);
			exit;
		}
	}
}

if($action == 'update'){
	$entity  = GETPOST('entity')?GETPOST('entity'):$conf->entity;
	// $comment = GETPOST('comment');
	$id_edit = GETPOST('id_edit');
	if(!empty($comment)){
		$data = [
			'fk_user' => $user->id,
			'fk_module' => $id,
			'comment' => $db->escape($comment),
			'users_affected' => $users_affected,
			'comment_type' => $comment_type,
			'name_module' => 'payment',
			'entity'      =>  $entity,
		];
		$commentaire = new gt_comments($db);
		$commentaire->fetch($id_edit);
		$result = $commentaire->update($id_edit,$data);
		if($result){
			$commentaire->sendMailWhenReplyComment($object, $result, $_SERVER['HTTP_REFERER']);
			header('Location:commentaire.php?socid='.$id);
			exit;
		}
	}
}

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {
    
	$comment = new gt_comments($db);

    $comment->fetch(GETPOST('id_delete'));
    $error = $comment->delete();

    header('Location: commentaire.php?socid='.$id);
    exit;
}
if ($action == 'confirm_delete_ol' && GETPOST('confirm') == 'yes' ) {
    
	$id_delete = GETPOST('id_delete');
    $comment = new gt_comments($db);
	if($comment->getcountrows(' AND name_module ="payment" AND fk_module ='.$id.' AND fk_comment='.$id_delete) > 0){
		$comments =new gt_comments($db);
		$comments->fetchAll('','',0,0,' AND name_module ="payment" AND fk_module ='.$id.' AND fk_comment='.$id_delete);
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

        header('Location: commentaire.php?socid='.$id.'&page='.$page);
        exit;
    }
    else {      
        header('Location: card.php?delete=1&page='.$page);
        exit;
    }
}

$morejs  = array();

$title = $langs->trans("Payment");

llxHeader(array(), $title,'','','','',$morejs,0,0);
// die("En cours de traitement ...");
if($action == "delete"){
	$id_delete = GETPOST('id_delete');
    print $form->formconfirm("commentaire.php?socid=".$id."&id_delete=".$id_delete."&page=".$page,$langs->trans('Confirmation') , $langs->trans('ConfirmDeleteObject'),"confirm_delete", 'commentaire.php?page='.$page, 0, 1);
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
$param ="";
if ($limit > 0 && $limit != $my_limit) $param.='&limit='.$limit;
$filter = ' AND name_module ="payment" AND fk_module ='.$id.' AND (fk_comment IS Null or fk_comment="")';

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
$param .= '&id='.$id;

$head = payment_prepare_head($object);
dol_fiche_head($head, 'tab_commentaire', $langs->trans("Payment"), -1, 'payment');


$linkback = '<a href="'.DOL_URL_ROOT.'/compta/paiement/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$shownav = 1;
if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;
dol_banner_tab($object, 'id', $linkback, $shownav, 'rowid');

print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'" id="list_ov" >';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input name="pagem" type="hidden" value="'.$page.'">';
	print '<input name="offsetm" type="hidden" value="'.$offset.'">';
	print '<input name="limitm" type="hidden" value="'.$limit.'">';
	print '<input name="filterm" type="hidden" value="'.$filter.'">';
	print '<input name="socid" type="hidden" value="'.$id.'">';
	print '<input name="action_pdf" class="action_pdf" type="hidden" value="">';

	print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbrtotal, $nbtotalofrecords, 'title_accountancy.png', 0, '', '', $limit);
print '</form>';

// print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $nbrtotal, $nbrtotal);


print '<div class="comments_list">';

		print '<table class="noborder" width="100%">';

		$newcommentfiles = $comments->getJoinedFiles($comments);

	   
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
									print '<a href="commentaire.php?socid='.$id.'&id_delete='.$item->rowid.'&action=delete" class="removecomment">'.img_delete($langs->trans('Delete')).'</a>';
								}
								print '<a class="repond" id="repond_'.$item->rowid.'" data-id="'.$item->rowid.'">'.$langs->trans("repond").'</a>';
								if($comments->getcountrows(' AND name_module ="payment" AND fk_module ='.$id.' AND fk_comment='.$item->rowid) > 0){
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
								    print '<input type="hidden" name="socid" value="'.$id.'" />';
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
								    print '<input type="hidden" name="socid" value="'.$id.'" />';
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
								print $cmt->getcommentschild('payment', $id, $item->rowid, 'id', $srch_comment = '', $newcommentfiles, false);
							print '</div>';
						print '</td>';
					print '</tr>';	
				}
			}

		    print '<input type="hidden" name="action" value="create" />';
		    print '<input type="hidden" name="socid" value="'.$id.'" />';
			print '<tr>';
				print '<td>'.$user->getNomUrl(-3, '', 0, 0, 0, 0, '', 'paddingright valigntextbottom').'</td>';
				print '<td>';
					print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
						print '<input type="hidden" name="action" value="create" />';
					    print '<input type="hidden" name="socid" value="'.$id.'" />';
						print '<input type="hidden" name="page" value="'.$page.'" />';
						print $comments->dolEditorWithSelectUsers($comments, 'txtcomment');
					print $newcommentfiles;
						// print '<textarea class="comment" name="comment" onchange="textarea_autosize(this)" placeholder="'.$langs->trans('your_comment').'"></textarea>';
						print '<input type="submit" value="'.$langs->trans("sauvg").'" class="btnAction">';
					print '</form>';
				print '</td>';
			print '</tr>';
		print '</table>';
print '</div>';


?>
<script>
	$(document).ready(function(){
	});
</script>

<?php

llxFooter();
