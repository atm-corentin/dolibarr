<?php
/* Copyright (C) 2018 SuperAdmin
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
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB')) define('NOREQUIREDB', '1');
if (!defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
//if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN')) define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];$tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/../main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");


// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

global $langs;
$langs->load("lareponse@lareponse", "main");
?>

// Lock the ajax request if there is one pending
$(window).data('ajaxready', true);

// Detect scroll event
$(window).scroll(function () {

	// If there is one ajax request pending, to not send another request
	if ($(window).data('ajaxready') == false) return;

	// Get the comment section
	var commentSection = document.querySelector('#comments');

	if (commentSection != undefined) {

		// Get the data attributes
		const totalComment = commentSection.dataset.totalComment;
		const commentLimit = commentSection.dataset.limit;
		const commentOffset = commentSection.querySelectorAll('.commentBox').length;
		const commentOrder = commentSection.dataset.order;
		const articleId = commentSection.dataset.articleId;

		// Check if max comments are displayed
		if (commentOffset >= totalComment) return;

		// Check if the comment container is scrolled down
		if ($(window).scrollTop() >= $(commentSection).offset().top + $(commentSection).outerHeight() - window.innerHeight) {

			// Display loader
			var loader = document.querySelector("#commentLoader");
			loader.style.display = "block";

			// Set ajax not ready because a request is pending
			$(window).data('ajaxready', false);

			// ajax call get data from server and append to the div
			$.ajax({
				url: '<?php echo dol_buildpath('/lareponse/ajax/get_comment.php', 1)?>'
					+ '?articleId=' + articleId
					+ '&commentLimit=' + commentLimit
					+ '&commentOffset=' + commentOffset
					+ '&commentOrder=' + commentOrder,
				type: 'get',
				success: function (res) {
					if (res != undefined) {
						res = JSON.parse(res);
						var comments = res.comments;
						for (var i = 0; i < comments.length; i++) {
							// Reload the comment section
							commentSection = document.querySelector('#comments');
							const nbCommentBox = commentSection.querySelectorAll('.commentBox').length;

							// Copy the last child
							var commentBox = commentSection.lastChild;
							var newCommentBox = commentBox.cloneNode(true);

							var commentValue = comments[i];

							// Change the id of the new comment box
							newCommentBox.setAttribute("id", "commentBox" + (nbCommentBox + 1));

							// Change the author
							var commentAuthor = newCommentBox.querySelector(".commentAuthor");
							while (commentAuthor.childNodes.length > 1) {
								commentAuthor.removeChild(commentAuthor.lastChild);
							}
							commentAuthor.firstElementChild.innerHTML = commentValue.author;
							// Reload the author tooltip
							jQuery(".classfortooltip").tooltip({
								show: {collision: "flipfit", effect: 'toggle', delay: 50},
								hide: {delay: 50},
								tooltipClass: "mytooltip",
								content: function () {
									return $(this).prop('title');		/* To force to get title as is */
								}
							});

							// Change the date
							newCommentBox.querySelector(".commentDate").firstElementChild.innerHTML = commentValue.date;

							// Change the date
							newCommentBox.querySelector(".commentContent").firstElementChild.innerHTML = commentValue.content;

							// Change the delete link by replacing the id of the href attribute
							var deleteLinkElement = newCommentBox.querySelector(".commentDeleteLink").firstElementChild
							var deleteLink = deleteLinkElement.getAttribute("href");
							var newDeleteLink = deleteLink.substring(0, deleteLink.indexOf("&commentid=")) + "&commentid=" + commentValue.rowid;
							deleteLinkElement.setAttribute("href", newDeleteLink);

							// Add the new comment to the comment section
							commentSection.appendChild(newCommentBox);
						}
					}

					// Hide the loader
					loader.style.display = "none";

					// Set ajax request as ready
					$(window).data('ajaxready', true);
				}
			});
		}
	}
});

$(document).ready(function () { // When document is ready, start checking whenever the favorite star is clicked
	var isManagingFavorite = false;

	$('#cardFavStar').click("i", function (data) {
		var fk_user = $(data.currentTarget).attr("user-id");
		var fk_article = $(data.currentTarget).attr("article-id");
		if (isManagingFavorite == false) {
			isManagingFavorite = true;
			$.ajax({
				url: '<?php echo dol_buildpath('/lareponse/ajax/modify_favorites_table.php', 1)?>',
				type: 'POST',
				data: {
					'user_id': fk_user,
					'article_id': fk_article,
					'token': '<?php echo $_SESSION['newtoken']; ?>'
				},
				success: function (res) {
					res = JSON.parse(res);
					if (res['success'] == true) {
						// #161
						var element = document.querySelector("#cardFavStar i");
						if (!element.classList.contains("isfavorite")) { // if the element doesn't have the class, we add it
							element.classList.add("isfavorite");
						} else { // if the element have the class .isFavorite
							element.classList.remove("isfavorite");
						}
						// end #161
					}
				}
			}).done(isManagingFavorite = false);
		}
	});

	$('#clipboardcopytoken').click(function (data) {
		// Get the token value
		var token = $('#clipboardcopytoken').attr('data-token');
		if (token) {
			var dolbuildpath = '<?php print dol_buildpath('/lareponse/public/public_article.php', 2) . '?token='; ?>';
			// Create a textarea in order to copy the token to the clipboard
			var textArea = document.createElement("textarea");
			textArea.value = dolbuildpath + token;
			document.body.appendChild(textArea);
			textArea.select();
			document.execCommand('copy');
			if (document.execCommand('copy')) {
				Swal.fire(
					'<?php echo $langs->trans("LaReponseSuccess") ?>',
					'<?php echo $langs->trans("LaReponseSuccessfullycopied") ?>',
					'success'
				)
			} else {
				Swal.fire(
					'<?php echo $langs->trans("LaReponseError") ?>',
					'<?php echo $langs->trans("LaReponseSuccessfullyNotcopied") ?>',
					'error'
				)
			}
			document.body.appendChild(textArea).style.display = 'none';
		}
	});
});

window.onload = function () {
	generateToC(".lareponse_article", "#ToC");
}

document.addEventListener('DOMContentLoaded', function() {
	let lareponseTagsIcon = document.getElementById('lareponse-tags-icon');
	let tagsSection = document.getElementById('tags-section');

	let lareponseArticleIcon = document.getElementById('lareponse-article-icon');
	let commentSection = document.getElementById('comment-section');

	if (lareponseTagsIcon && tagsSection) {
		lareponseTagsIcon.addEventListener('click', function() {
			if (tagsSection.classList.contains('lareponse-tags-hidden')) {
				tagsSection.classList.remove('lareponse-tags-hidden');
				lareponseTagsIcon.className = 'fa fa-chevron-up';
			} else {
				tagsSection.classList.add('lareponse-tags-hidden');
				lareponseTagsIcon.className = 'fa fa-chevron-down';
			}
		});
	}

	if (lareponseArticleIcon && commentSection) {
		lareponseArticleIcon.addEventListener('click', function() {
			if (commentSection.classList.contains('lareponse-article-hidden')) {
				commentSection.classList.remove('lareponse-article-hidden');
				lareponseArticleIcon.className = 'fa fa-chevron-up';
			} else {
				commentSection.classList.add('lareponse-article-hidden');
				lareponseArticleIcon.className = 'fa fa-chevron-down';
			}
		});
	}
});

/**
 * Display a confirmation popup before executing a specified action.
 *
 * @param {string} action - The action to execute (e.g., 'export', 'clone', 'open', etc.). It will be appended to the current URL as a query parameter.
 * @param {string} popupTitle - The title displayed in the confirmation popup.
 * @param {string} color - (Optionnal) color of the confirm button (must be with #)
 */
function callToAction(action, popupTitle, color = '') {
	var swalOptions = {
		icon: 'question',
		title: popupTitle,
		showConfirmButton: true,
		confirmButtonText: '<?php echo $langs->trans('Yes'); ?>',
		showCancelButton: true,
		cancelButtonText: '<?php echo $langs->trans('Cancel'); ?>'
	}

	if (color) swalOptions.confirmButtonColor = color;

	Swal.fire(swalOptions).then((result) => {
		if (result.isConfirmed) {
			window.location.href += '&action=' + action;
		}
	});
}

/**
 * Save the current article to the database.
 *
 * @param {int} quit  0 (default) => stays on the current page. 1 => redirects to the article list page after saving.
 */
function saveArticle(quit = 0) {
	var articleId = new URLSearchParams(window.location.search).get('id');

	let contentSelector = $('#content').val();
	if ($('iframe.cke_wysiwyg_frame').length > 0) contentSelector = $('iframe.cke_wysiwyg_frame')[0].contentWindow.document.querySelector('body').innerHTML;
	$.ajax({
		url: '<?php echo dol_buildpath('/lareponse/ajax/save_article.php', 1); ?>',
		type: 'POST',
		data: {
			'id': articleId,
			'title': $('#title').val(),
			'private': $('#private').val(),
			'content': contentSelector,
			'list_tag': $('#list_tag').val(),
		},
		success: function (res) {
			res = JSON.parse(res);
			if (res.success) {
				if (quit) {
					window.location.href = '<?php echo dol_buildpath('/lareponse/article_list.php?idmenu=76&mainmenu=lareponse', 1); ?>';
				} else {
					Swal.fire({
						icon: 'success',
						title: `<?php echo $langs->trans('ArticleUpdate'); ?>`,
						showConfirmButton: false,
						timer: 1000,
						timerProgressBar: true
					});
				}
			} else {
				Swal.fire({
					icon: 'error',
					title: `<?php echo $langs->trans('ErrorArticleUpdate'); ?>`
				});
			}
		}
	});
}
