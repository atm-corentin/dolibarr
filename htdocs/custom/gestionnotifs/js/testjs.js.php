<?php 

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 

global $langs;

	
?>

$(document).ready(function(){
	var href = location.href;



	menu = '<div class="inline-block tabsElem" >';
		menu += '<a id="tab_notification" class="tabunactive tab inline-block relative_div_" >';
			menu += '<?php echo $langs->trans("Notifications")?>';
			menu += '<span class="badge marginleftonlyshort">10</span>';
		menu += '</a>';
	menu += '</div>';
	menu += '<div class="inline-block tabsElem" >';
		menu += '<a id="tab_commentaire" class="tabunactive tab inline-block relative_div_">';
			menu += '<?php echo $langs->trans("Comments")?>';
			menu += '<span class="badge marginleftonlyshort">10</span>';
		menu += '</a>';
	menu += '</div>';

	$('div.tabs').append(menu);


	

	// if(href.indexOf('socid') != -1){
	// 	var id = href.split('socid=');
	// 	id = id[1].split('&');
	// 	id = id[0];
		
	// 	menu = '<div class="inline-block tabsElem" >';
	// 		menu += '<a id="tab_notification" class="tabunactive tab inline-block relative_div_" href="<?php echo dol_buildpath("/gestionnotifs/tiers/notification.php?module_notif=societe&socid=",2); ?>'+id+'">';
	// 			menu += '<?php echo $langs->trans("Notifications")?>';
	// 			menu += '<span class="badge marginleftonlyshort">10</span>';
	// 		menu += '</a>';
	// 	menu += '</div>';
	// 	menu += '<div class="inline-block tabsElem" >';
	// 		menu += '<a id="tab_commentaire" class="tabunactive tab inline-block relative_div_" href="<?php echo dol_buildpath("/gestionnotifs/tiers/commentaire.php?module_notif=societe&socid=",2); ?>'+id+'">';
	// 			menu += '<?php echo $langs->trans("Comments")?>';
	// 			menu += '<span class="badge marginleftonlyshort">10</span>';
	// 		menu += '</a>';
	// 	menu += '</div>';
	// 	$('div.tabs').append(menu);

	// } 



	if(href.indexOf('?id') != -1){
		var id = href.split('?id=');
		id = id[1].split('&');
		id = id[0];
		console.log('id'+id);

		menu = '<div class="inline-block tabsElem" >';
			menu += '<a id="tab_notification" class="tabunactive tab inline-block relative_div_" href="<?php echo dol_buildpath("/gestionnotifs/projets/notification.php?id=",2); ?>'+id+'">';
				menu += '<?php echo $langs->trans("Notifications")?>';
				menu += '<span class="badge marginleftonlyshort">10</span>';
			menu += '</a>';
		menu += '</div>';
		menu += '<div class="inline-block tabsElem" >';
			menu += '<a id="tab_commentaire" class="tabunactive tab inline-block relative_div_" href="<?php echo dol_buildpath("/gestionnotifs/projets/commentaire.php?id=",2); ?>'+id+'">';
				menu += '<?php echo $langs->trans("Comments")?>';
				menu += '<span class="badge marginleftonlyshort">10</span>';
			menu += '</a>';
		menu += '</div>';
		$('div.tabs').append(menu);
	}
	if(href.indexOf('?facid') != -1){
		var id = href.split('?facid=');
		id = id[1].split('&');
		id = id[0];
		console.log('id'+id);

		menu = '<div class="inline-block tabsElem" >';
			menu += '<a id="tab_notification" class="tabunactive tab inline-block relative_div_" href="<?php echo dol_buildpath("/gestionnotifs/factures/notification.php?facid=",2); ?>'+id+'">';
				menu += '<?php echo $langs->trans("Notifications")?>';
				menu += '<span class="badge marginleftonlyshort">10</span>';
			menu += '</a>';
		menu += '</div>';
		menu += '<div class="inline-block tabsElem" >';
			menu += '<a id="tab_commentaire" class="tabunactive tab inline-block relative_div_" href="<?php echo dol_buildpath("/gestionnotifs/factures/commentaire.php?facid=",2); ?>'+id+'">';
				menu += '<?php echo $langs->trans("Comments")?>';
				menu += '<span class="badge marginleftonlyshort">10</span>';
			menu += '</a>';
		menu += '</div>';
		$('div.tabs').append(menu);
	}

	if(href.indexOf('?id') != -1){
		var id = href.split('?id=');
		id = id[1].split('&');
		id = id[0];

		menu = '<div class="inline-block tabsElem" >';
			menu += '<a id="tab_notification" class="tabunactive tab inline-block relative_div_" href="<?php echo dol_buildpath("/gestionnotifs/products/notification.php?id=",2); ?>'+id+'">';
				menu += '<?php echo $langs->trans("Notifications")?>';
				menu += '<span class="badge marginleftonlyshort">10</span>';
			menu += '</a>';
		menu += '</div>';
		menu += '<div class="inline-block tabsElem" >';
			menu += '<a id="tab_commentaire" class="tabunactive tab inline-block relative_div_" href="<?php echo dol_buildpath("/gestionnotifs/products/commentaire.php?id=",2); ?>'+id+'">';
				menu += '<?php echo $langs->trans("Comments")?>';
				menu += '<span class="badge marginleftonlyshort">10</span>';
			menu += '</a>';
		menu += '</div>';
		$('div.tabs').append(menu);
	}
})

<?php 


?>
$(document).ready(function(){
})