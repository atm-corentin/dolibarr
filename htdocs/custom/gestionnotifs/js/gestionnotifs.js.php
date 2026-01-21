<?php 

if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);

if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', 1);

// define('ISLOADEDBYSTEELSHEET', '1');
// session_cache_limiter('public');


$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

dol_include_once('/gestionnotifs/class/gt_comments.class.php');

$langs->load('gestionnotifs@gestionnotifs');
top_httphead('text/javascript; charset=UTF-8');

?>
jQuery(document).ready(function() {

	$('.get_notif').click(function(){
		$url = '<?php echo dol_buildpath("/gestionnotifs/ajax/get_notifs.php",2); ?>';
		$.post($url, '', function(response) {
			$html = '<div class="dropdown-menu popup_notif">';
				$html += '<div id="notificationContainer" style="display: block;">';
					$html += '<div id="notificationTitle"><?php echo $langs->trans("cinq_notifs") ?></div>';
					$html += '<div id="notificationsBody" class="notifications">';
						$html += response;
					$html += '</div>';
				$html += '</div>';
				
				<!-- $html += '<div id="notificationFooter"><a href="#">See All</a></div>'; -->
			$html += '</div>';
			$('.notifs').append($html);
			$('.notifs').find('.dropdown-menu').show();
		}, 'json');

	});

	$('.comments_list .gestionnotifs_commentstype input[type=radio]').change(function() {
		var selectusers = $(this).parent('label').parent('div').parent().find('.gestionnotifsselectusers');
	    if (this.value == '<?php echo dol_escape_js(gt_comments::PRIVATE); ?>') {
	    	selectusers.removeClass('hidden');
	    }
	    else {
    		selectusers.addClass('hidden');
	    }
	});
	$('#gestionnotifslistallcomments .gestionnotifs_commentstype input[type=radio]').change(function() {
		$('#gestionnotifslistallcomments').submit();
	});
});

function trigger_upload_file($t){
	$($t).parent().find(".add_photo").trigger('click');
}
function change_upload_file($t){
	if ($($t).val() != "")
		$($t).parent().find('.add_joint').addClass("filledjoint");
	else
		$($t).parent().find('.add_joint').removeClass("filledjoint");

	if ($($t).parent().parent().parent().find('.textarea_comment').val() == "")
		$($t).parent().parent().parent().find('.comment_btn').attr('disabled', true);
	else
		$($t).parent().parent().parent().find('.comment_btn').attr('disabled', false);
}
function new_input_joint($t){
	 var cnt = $($t).parent();
	 $($t).parent().find('.add_plus').before('<div class="one_file"><span class="add_joint" onclick="trigger_upload_file(this)"><i class="fa fa-paperclip"></i></span><input class="add_photo" type="file" name="files[]" onchange="change_upload_file(this)"/></div>');
}
function to_delete_file($t, e, cmntId){
	e.preventDefault();

	var editorshow = $($t).parent('li').parent('ul').parent('.editfiles').parent('.files_joints').parent();

	if(!editorshow.hasClass('text_comment')) {
	    var files_href = $($t).attr("datafile");
	    var files_deleted = $($t).parent('li').parent('ul').parent('.editfiles').find('.files_deleted').val();

	    if(files_deleted == '')
	        $($t).parent('li').parent().parent().find('.files_deleted').val(files_href);            
	    else
	        $($t).parent('li').parent().parent().find('.files_deleted').val(files_deleted+','+files_href);
	    $($t).parent('li').remove();

	} else {
		document_preview($($t).attr("href"), $($t).attr("mime"), "<?php echo dol_escape_js($langs->transnoentities('Preview')); ?>");
		return false;
	}
}