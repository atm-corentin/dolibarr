$(document).ready(function() {
	
	$('.cancel').click(function() {
		$(this).parents('.repond_comment').find('textarea').val('');
		$(this).parents('.repond_comment').hide();
	})

	$('.repond').click(function(){
		$('.repond_comment').hide();
		var id = $(this).data("id");
		$('#repond_comment_'+id).show();
		$('#repond_comment_'+id+' .comment').focus();
	});

	$('.editcomment').click(function(){
		$('.edit_comment').hide();
		$('.annulcomment').hide();
		$('.editcomment').show();
		var id = $(this).data("id");
		// console.log(id);
		$('#annulcomment_'+id).show();
		$('#text_comment_'+id).hide();
		$('#edit_comment_'+id).show();
		$(this).hide();
	});

	$('.annulcomment').click(function(){
		var id = $(this).data("id");
		$('#text_comment_'+id).show();
		var comment =$('#text_comment_'+id).html();
		$('#edit_comment_'+id).find('.comment').val(comment);
		$('#edit_comment_'+id).hide();

		$('#editcomment_'+id).show();
		$('#annulcomment_'+id).hide();
	});

	$('.comments_list .down').click(function(){
		$(this).hide();
		$(this).parent().find('.comment_fild').show();
		$(this).parent().find('.up').show();
	});

	$('.comments_list .up').click(function(){
		$(this).hide();
		$(this).parent().find('.comment_fild').hide();
		$(this).parent().find('.down').show();
	});

	$('#notif_memebre .down').click(function(){
		$(this).hide();
		$(this).parent().find('.champs_edit').show();
		$(this).parent().find('.up').show();
	});

	$('#notif_memebre .up').click(function(){
		$(this).hide();
		$(this).parent().find('.champs_edit').hide();
		$(this).parent().find('.down').show();
	});
	// $('<div class="inline-block notifs"><div class="inline-block"><div class="classfortooltip inline-block login_block_elem inline-block" style="padding: 0px; padding: 0px; padding-right: 3px !important;" title=""><a class="get_notif"><span class="fa fa-bell atoplogin valignmiddle"></span></a></div></div></div>').prependTo('.login_block_other');
	$('.get_notif').click(function(){
		// $html ='<div class="dropdown-menu popup_notif">dfdfgf</div>';
		// $('.notifs').append($html);
		// $('.notifs').find('.dropdown-menu').show();

	});

	$(document).click(function(){
		$('.notifs').find('.dropdown-menu').hide();
	});


	var cid = location.search.split('cid=')[1];
	if(Math.floor(cid) == cid && $.isNumeric(cid)) {
		var $container = $("html,body");
		var $scrollTo = $('#text_comment_'+cid);
		
		if($scrollTo.length > 0) {
			$([document.documentElement, $container]).animate({
		        scrollTop: $scrollTo.offset().top
		    }, 1500);
		}

	    $scrollTo.addClass('actif_comment_link');

	    setTimeout(function() {$scrollTo.removeClass('actif_comment_link')}, 3000);
	}

});


function textarea_autosize_notifs(){
    $(".comments_list textarea").each(function(textarea) {
        $(this).css('height', 'auto');
        $(this).css('resize', 'none');
    }).on('input', function () {
        $(this).css('height', 'auto');
        $(this).height($(this)[0].scrollHeight);
    });
}
