//#265
/*
    This file is called in a <script> tag.
    document.currentScript refers to the <script> tag itself.
*/
let menuPos = document.currentScript.dataset.menuPos;
$(document).ready(function () {
	let noteElement = $('a#addNote');
	let hiddenElement = $('a#addNote').clone(); // clone item to put it on the top

	if (!(window.matchMedia("(orientation: portrait)").matches && menuPos == 2)) {
		hiddenElement.css('display', 'none');
		hiddenElement.css('position', 'static');
		$('ul.tmenu').append(hiddenElement);

		noteElement.removeAttr('id'); // because the script take the id so we avoid error
		noteElement.removeAttr('style');
		$('div.inline-block.login_block_elem.login_block_elem_name').prepend(noteElement);
	}
});