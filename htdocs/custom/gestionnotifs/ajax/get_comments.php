<?php
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

dol_include_once('/gestionnotifs/class/gt_comments.class.php');

global $conf, $langs, $user;

$langs->loadlangs(array('projects', 'companies', 'other'));

$comments = new gt_comments($db);
$gtc = new gt_comments($db);
$form = new Form($db);

$ajxaction = GETPOST('ajxaction', 'alpha');
$fk_module = GETPOST('fk_module', 'alpha');
$name_module = GETPOST('name_module', 'alpha');
$srch_comment = GETPOST('srch_comment', 'alpha');
$srch_comment_type = GETPOST('srch_comment_type', 'alpha');

$id = $fk_module;

$result = '';

if($ajxaction == 'get_comments'){

	$filter = ' AND name_module ="'.$name_module.'" AND fk_module ='.$id.' AND (fk_comment IS Null or fk_comment="")';
	if($srch_comment) $filter .= ' AND comment like "%'.$srch_comment.'%"';

	$filter .= $gtc->filtebyCommentType($srch_comment_type);

	$nbrtotal = $comments->fetchAll('DESC','date', 0,'', $filter);

	// $result .= '<script type="text/javascript" src="'.dol_buildpath('/gestionnotifs/js/gestionnotifs.js',1).'"></script>';
	$result .= '<script type="text/javascript">';
	$result .= "$('.comments_list .down').click(function(){
		$(this).hide();
		$(this).parent().find('.comment_fild').show();
		$(this).parent().find('.up').show();
	});

	$('.comments_list .up').click(function(){
		$(this).hide();
		$(this).parent().find('.comment_fild').hide();
		$(this).parent().find('.down').show();
	});";
	$result .= '</script>';
	$result .= '<div class="comments_list">';

	$newcommentfiles = $comments->getJoinedFiles($comments);

	if($comments->rows && count($comments->rows)>0){
		$result .= '<table class="noborder" width="100%">';
		for ($i=0; $i < count($comments->rows); $i++) { 
			$item = $comments->rows[$i];
			$user_ = new User($db);
			$user_->fetch($item->fk_user);

			$result .= '<tr class="oddeven">';
				$result .= '<td>'.$user_->getNomUrl(-3, '', 0, 0, 0, 0, '', 'paddingright valigntextbottom').'</td>';
				$result .= '<td>';
					$result .= '<div class="comemnt_parent">';
						$result .= '<span ><b>'.$user_->firstname.' '.$user_->lastname.'</b></span>';
						$result .= '<span class="date_comment">'.date('d/m/Y H:i',strtotime($item->date)).'</span>';
						if($user->id == $item->fk_user){
							$result .= '<a class="editcomment" id="editcomment_'.$item->rowid.'" data-id="'.$item->rowid.'">';
								$result .= img_edit($langs->trans('Edit'));
							$result .= '</a>';
							$result .= '<a class="annulcomment" id="annulcomment_'.$item->rowid.'" data-id="'.$item->rowid.'">';
								$result .= '<span class="fa fa-remove" title="'.$langs->trans('Cancel').'"></span>';
							$result .= '</a>';
							$result .= '<a href="commentaire.php?id='.$id.'&id_delete='.$item->rowid.'&action=delete" class="removecomment">'.img_delete($langs->trans('Delete')).'</a>';
						}
						$result .= '<a class="repond" id="repond_'.$item->rowid.'" data-id="'.$item->rowid.'">'.$langs->trans("repond").'</a>';
						if($comments->getcountrows(' AND name_module = "'.$name_module.'" AND fk_module ='.$id.' AND fk_comment='.$item->rowid) > 0){
							$result .= '<a class="down"><i class="fas fa-chevron-down"></i></a>';
							$result .= '<a class="up"><i class="fas fa-chevron-up"></i></a>';
						}
						$result .= '<br>';
						// $result .= '<span id="text_comment_'.$item->rowid.'" class="text_comment" >'.dol_string_onlythesehtmltags($item->comment).'</span>';
						$result .= '<span id="text_comment_'.$item->rowid.'" class="text_comment" >'.dol_string_onlythesehtmltags($item->comment);
						$filesdiv = $comments->getJoinedFiles($item);
						$result .= $filesdiv;
						$result .= '</span>';

						// $result .= '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
						// 	// $result .= '<input type="hidden" name="action" value="update" />';
						//  //    $result .= '<input type="hidden" name="id" value="'.$id.'" />';
						//  //    $result .= '<input type="hidden" class="id_edit" name="id_edit" value="'.$item->rowid.'" />';
						// 	// $result .= '<input type="hidden" name="entity" value="'.$item->entity.'" />';
						// 	// // $result .= '<input type="hidden" name="page" value="'.$page.'" />';
						// 	// $result .= '<div id="edit_comment_'.$item->rowid.'" class="edit_comment">';
						// 	// 	// $result .= $comments->dolEditorWithSelectUsers('txtcomment_update_', $item->comment, $item->rowid, $item->fk_user);
						// 	// 	// $result .= $filesdiv;
						// 	// 	// $result .= '<textarea class="comment" name="comment" onchange="textarea_autosize(this)" placeholder="'.$langs->trans('your_comment').'">'.dol_string_onlythesehtmltags($item->comment).'</textarea>';
						// 	// 	$result .= '<input type="submit" value="'.$langs->trans("sauvg").'" class="sauvg">';
						// 	// $result .= '</div>';
						// $result .= '</form>';

						// $result .= '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';

						// 	$result .= '<input type="hidden" name="action" value="reponder" />';
						//     $result .= '<input type="hidden" name="id" value="'.$id.'" />';
						//     $result .= '<input type="hidden" class="fk_comment" name="fk_comment" value="'.$item->rowid.'" />';

						// 	// $result .= '<div id="repond_comment_'.$item->rowid.'" class="repond_comment">';
						// 	// 	$result .= $user->getNomUrl(-3, '', 0, 0, 0, 0, '', 'paddingright valigntextbottom');
						// 	// 	$result .= $comments->dolEditorWithSelectUsers('txtcomment_reponder_', '', $item->rowid, $item->fk_user);
						// 	// 	// $result .= $comments->dolEditorWithSelectUsers('txtcomment_reponder_', '', $item->rowid, $item->fk_user);
						// 	// 	// $result .= '<textarea class="comment" name="comment" onchange="textarea_autosize(this)" placeholder="'.$langs->trans('your_comment').'"></textarea>';
						// 	// 	$result .= '<div class="action_repond"> ';
						// 	// 		$result .= '<input type="submit" value="'.$langs->trans("sauvg").'" class="btnAction">';
						// 	// 		$result .= '<a class="bntAction cancel">Annuler</a>';
						// 	// 	$result .= '</div>';
						// 	// $result .= '</div>';

						// $result .= '</form>';
						$cmt = new gt_comments($db);
						$result .= $cmt->getcommentschild($name_module, $id, $item->rowid, 'id', $srch_comment, $newcommentfiles, true);
					$result .= '</div>';
				$result .= '</td>';
			$result .= '</tr>';	
		}
		$result .= '</table>';

	} else {
		$result .= '<span class="opacitymedium">';
		$result .= str_repeat('<i class="fas fa-chevron-left"></i>',3);
		$result .= '</span>';
	}

	$result .= '</div>';
}


echo json_encode($result);
