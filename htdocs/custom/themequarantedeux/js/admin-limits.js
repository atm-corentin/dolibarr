//#265
/*
    This file is called in a <script> tag.
    document.currentScript refers to the <script> tag itself.
*/
$(document).ready(function () {
	$('body#mainbody > div#id-container > div#id-right > div.fiche').addClass('admin-limits');
});