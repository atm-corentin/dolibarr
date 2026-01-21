<?php 

if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';


dol_include_once('/gestionnotifs/class/gt_notifcs.class.php');
dol_include_once('/gestionnotifs/class/gt_comments.class.php');

global $langs, $conf;
$langs->loadLangs(array('bills', 'companies', 'compta', 'products', 'banks', 'main', 'withdrawals'));
$langs->load('gestionnotifs@gestionnotifs');
$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$action = GETPOST('action');
$page 	= GETPOST("page",'int');
$form = new Form($db);


$object = new Facture($db);
$comments = new gt_comments($db);
$comments2 = new gt_comments($db);

// $object->fetch($id);
if ($id > 0 || !empty($ref)) {
	if ($action != 'add') {
		$ret = $object->fetch($id, $ref, '', '', isset($conf->global->INVOICE_USE_SITUATION) ? $conf->global->INVOICE_USE_SITUATION : '');
	}
}
$object->fetch_thirdparty();


$comments = new gt_comments($db);
$returned = $comments->getCommentContent(false);

$name_module = 'facture';


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
			'name_module' => 'facture',
			'entity' => $conf->entity,
		];
		$commentaire = new gt_comments($db);
		$result = $commentaire->create(1,$data);
		if($result){
			$commentaire->sendMailWhenReplyComment($object, $result, $_SERVER['HTTP_REFERER']);
			header('Location:commentaire.php?facid='.$id);
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
			'name_module' => 'facture',
			'entity' => $conf->entity,
		];
		$commentaire = new gt_comments($db);
		$result = $commentaire->create(1,$data);
		if($result){
			$commentaire->sendMailWhenReplyComment($object, $result, $_SERVER['HTTP_REFERER']);
			header('Location:commentaire.php?facid='.$id);
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
			'name_module' => 'facture',
			'entity'      =>  $entity,
		];
		$commentaire = new gt_comments($db);
		$commentaire->fetch($id_edit);
		$result = $commentaire->update($id_edit,$data);
		if($result){
			$commentaire->sendMailWhenReplyComment($object, $result, $_SERVER['HTTP_REFERER']);
			header('Location:commentaire.php?facid='.$id);
			exit;
		}
	}
}
if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {
    
	$comment = new gt_comments($db);

    $comment->fetch(GETPOST('id_delete'));
    $error = $comment->delete();

    header('Location: commentaire.php?facid='.$id);
    exit;
}
if ($action == 'confirm_delete_ol' && GETPOST('confirm') == 'yes' ) {
    
	$id_delete = GETPOST('id_delete');
    $comment = new gt_comments($db);

	if($comment->getcountrows(' AND name_module ="facture" AND fk_module ='.$id.' AND fk_comment='.$id_delete) > 0){
		$comments =new gt_comments($db);
		$comments->fetchAll('','',0,0,' AND name_module ="facture" AND fk_module ='.$id.' AND fk_comment='.$id_delete);
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

        header('Location: commentaire.php?facid='.$id.'&page='.$page);
        exit;
    }
    else {      
        header('Location: card.php?delete=1&page='.$page);
        exit;
    }
}




$title = $langs->trans('InvoiceCustomer')." - ".$langs->trans('Card');
$helpurl = "EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes";
llxHeader('', $title, $helpurl);
// die("En cours de traitement ...");
if($action == "delete"){
	$id_delete = GETPOST('id_delete');
    print $form->formconfirm("commentaire.php?facid=".$id."&id_delete=".$id_delete."&page=".$page,$langs->trans('Confirmation') , $langs->trans('ConfirmDeleteObject'),"confirm_delete", 'commentaire.php?facid='.$id.'&page='.$page, 0, 1);
}



$my_limit 	= 5;
// $conf->liste_limit+1;
$sortfield 		= GETPOST("sortfield",'alpha');
$sortorder 		= GETPOST("sortorder",'alpha');
$page 	= GETPOST("page",'int');
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "date";
if (!$sortorder) $sortorder = "DESC";
$param ="";
if ($limit > 0 && $limit != $my_limit) $param.='&limit='.$limit;
$filter = ' AND name_module ="facture" AND fk_module ='.$id.' AND (fk_comment IS Null or fk_comment="")';

$head = facture_prepare_head($object);
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

$param .= '&facid='.$id;
// print_r($object);die();
dol_fiche_head($head, 'tab_commentaire', $langs->trans("InvoiceCustomer"), -1, $object->picto);
$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
// Ref invoice
if ($object->status == $object::STATUS_DRAFT && ! $mysoc->isInEEC() && ! empty($conf->global->INVOICE_ALLOW_FREE_REF)) {
	$morehtmlref .= $form->editfieldkey("Ref", 'ref', $object->ref, $object, $usercancreate, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("Ref", 'ref', $object->ref, $object, $usercancreate, 'string', '', null, null, '', 1);
	$morehtmlref .= '<br>';
}
// Ref customer
$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', 0, 1);
$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', null, null, '', 1);
// Thirdparty
if($object->thirdparty){

	$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1, 'customer');
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) $morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherBills").'</a>)';
}
// Project
if (!empty($conf->projet->enabled))
{
	$langs->load("projects");
	$morehtmlref .= '<br>'.$langs->trans('Project').' ';
	if ($usercancreate)
	{
		if ($action != 'classify') {
			$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
		}
		if ($action == 'classify') {
			//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
			$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			$morehtmlref .= '<input type="hidden" name="action" value="classin">';
			$morehtmlref .= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
			$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			$morehtmlref .= '</form>';
		} else {
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
		}
	} else {
		if (!empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
			$morehtmlref .= $proj->ref;
			$morehtmlref .= '</a>';
		} else {
			$morehtmlref .= '';
		}
	}
}
$morehtmlref .= '</div>';

$object->totalpaye = !empty($totalpaye) ? $totalpaye : ''; // To give a chance to dol_banner_tab to use already paid amount to show correct status

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

// dol_banner_tab($object, 'facid',$linkback);
$modname = $langs->trans("Comments");
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'" id="list_ov" >';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input name="pagem" type="hidden" value="'.$page.'">';
	print '<input name="offsetm" type="hidden" value="'.$offset.'">';
	print '<input name="limitm" type="hidden" value="'.$limit.'">';
	print '<input name="filterm" type="hidden" value="'.$filter.'">';
	print '<input name="facid" type="hidden" value="'.$id.'">';
	print '<input name="action_pdf" class="action_pdf" type="hidden" value="">';

	print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbrtotal, $nbtotalofrecords, 'title_accountancy.png', 0, '', '', $limit);
print '</form>';
$langs->load('gestionnotifs@gestionnotifs');

 

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
								print '<a href="commentaire.php?facid='.$id.'&id_delete='.$item->rowid.'&action=delete" class="removecomment">'.img_delete($langs->trans('Delete')).'</a>';
							}
							print '<a class="repond" id="repond_'.$item->rowid.'" data-id="'.$item->rowid.'">'.$langs->trans("repond").'</a>';
							if($comments->getcountrows(' AND name_module ="facture" AND fk_module ='.$id.' AND fk_comment='.$item->rowid) > 0){
								print '<a class="down"><i class="fas fa-chevron-down"></i></a>';
								print '<a class="up"><i class="fas fa-chevron-up"></i></a>';
							}
							// print '<span id="text_comment_'.$item->rowid.'" class="text_comment" >'.dol_string_onlythesehtmltags($item->comment).'</span>';
							print '<span id="text_comment_'.$item->rowid.'" class="text_comment" >'.dol_string_onlythesehtmltags($item->comment);
							$filesdiv = $comments->getJoinedFiles($item);
							print $filesdiv;
							print '</span>';

							print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
								print '<input type="hidden" name="action" value="update" />';
							    print '<input type="hidden" name="facid" value="'.$id.'" />';
							    print '<input type="hidden" class="id_edit" name="id_edit" value="'.$item->rowid.'" />';
								print '<input type="hidden" name="page" value="'.$page.'" />';
								print '<input type="hidden" name="entity" value="'.$item->entity.'" />';
								print '<div id="edit_comment_'.$item->rowid.'" class="edit_comment">';
									print $comments->dolEditorWithSelectUsers($item, 'txtcomment_update_', $item->comment, $item->rowid, $item->fk_user);
									print $filesdiv;
									// print '<textarea class="comment" name="comment" onchange="textarea_autosize(this)" placeholder="'.$langs->trans('your_comment').'">'.dol_string_onlythesehtmltags($item->comment).'</textarea>';
									print '<input type="submit" value="'.$langs->trans("sauvg").'" class="sauvg">';
								print '</div>';
							print '</form>';

							print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';

								print '<input type="hidden" name="action" value="reponder" />';
							    print '<input type="hidden" name="facid" value="'.$id.'" />';
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
							print $cmt->getcommentschild('facture', $id, $item->rowid, 'id', $srch_comment = '', $newcommentfiles, false);
						print '</div>';
					print '</td>';
				print '</tr>';	
			}
		}

	    print '<input type="hidden" name="action" value="create" />';
	    print '<input type="hidden" name="rowid" value="'.$id.'" />';
		print '<tr>';
			print '<td>'.$user->getNomUrl(-3, '', 0, 0, 0, 0, '', 'paddingright valigntextbottom').'</td>';
			print '<td>';
				print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
					print '<input type="hidden" name="action" value="create" />';
				    // print '<input type="hidden" name="rowid" value="'.$id.'" />';
				    print '<input type="hidden" name="facid" value="'.$id.'" />';
					print '<input type="hidden" name="page" value="'.$page.'" />';
					print $comments->dolEditorWithSelectUsers($comments, 'txtcomment', $value = '', $row_id = '', $object->fk_user_author, $object);
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
	});
</script>
<?php

llxFooter();
