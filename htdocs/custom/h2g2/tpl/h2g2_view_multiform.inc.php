<?php
/**
 * Required variables :
 *
 * 		string	$moduleName 		:	Module name
 * 		array	$forms 				:	Array of H2G2FormSetup to instantiate
 * 		array	$headerFunction 	:	Name of function that generate $head
 * 		string	$action 			:	Page action
 *
 * Optional variables
 * 		array	$jsArray 			:	Array of js file to include
 * 		array	$cssArray 			:	Array of css file to include
 * 		string	$tabPicto 			:	Picto that will be displayed
 * 		string	$pagePicto 			:	Picto that will be displayed at the left of admin tabs
 * 		string 	$linkBack 			:	Link back
 */

$form = new Form($db);

$jsArray = array_merge(array("h2g2/js/multiform_setup.js.php"), ($jsArray ?? array()));
$cssArray =  array_merge(array("h2g2/css/multiform_setup.css"), ($cssArray ?? array()));

llxHeader('', $langs->trans($pageName ?? "H2H2PageNameNotFound"), ($helpUrl ?? ""), "", 0, 0, $jsArray, $cssArray);

// Subhead
if (empty($linkBack)) $linkBack = '<a href="' . ($backtopage ?? DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($pageName), $linkBack, ($pagePicto ??''));

// Configuration header
if (!empty($headerFunction) && function_exists($headerFunction) && !empty($forms) && is_array($forms) && get_class($forms[0]) == "H2G2FormSetup") {
	$head = $headerFunction();
	print dol_get_fiche_head($head, 'pdf', $langs->trans($pageName), -1, ($tabPicto ?? ''));

	print "<span id='admin-help-button' data-help1='" . $langs->trans("H2G2HelpAdminTool1") . "' data-help2='" . $langs->trans("H2G2HelpAdminTool2") . "' data-help3='" . $langs->trans("H2G2HelpAdminTool3") . "' class='fa fa-question-circle'></span>";
	print "<div id='enable-form-buttons' class='tabbar'>";
	print "<ul id='H2G2-admin-tabs' class='flex-center'>";
	print "</ul>";

	// Print form setup entries depending on the action
	$activeFrom = GETPOST("form-name");
	if (empty($activeFrom)) $activeFrom = $forms[0]->title;
	$firstForm = true;
	$lastForm = false;
	foreach ($forms as $formNumber => $form) {
		if (($formNumber - 1) == count($forms)) $last = true;
		print "<div id='form-" . $form->title . "' class='H2G2-form ";
		if ($activeFrom != $form->title) print 'H2G2-hidden';
		print "'>";
		print $form->generateOutput($action == 'edit', $firstForm, $lastForm);
		print "</div>";
		$firstForm = false;
	}
	print "</div>";

	// Tabs action
	print "<div class='tabsAction'>";
	if ($action == 'edit') {
		print "<input type='hidden' id='h2g2-form-name' name='form-name' value='" . $activeFrom . "'>";
		print '<input class="button button-save" type="submit" value="' . $langs->trans("Save") . '">';
		print '<a class="button button-cancel" type="submit" href="' . $_SERVER['PHP_SELF'] . '">' . $langs->trans('Cancel') . '</a>';
	} else {
		print "<form method='POST' action='" . $_SERVER["PHP_SELF"] . "'>";
		print "<input type='hidden' name='token' value='" . newToken() . "'>";
		print "<input type='hidden' name='action' value='edit'>";
		print "<input type='hidden' id='h2g2-form-name' name='form-name' value='" . $activeFrom . "'>";
		print '<input type="submit" id="H2G2-button-modify" class="butAction" value="' . $langs->trans("Modify") . '">';
		print "</form>";
	}

	print "</div>";

	print '<div id="tutorial-overlay" class="H2G2-hidden"><div id="H2G2-highlight"></div>';
	print "<div class='H2G2-alert'>";
	print '<p id="H2G2-help-text"></p>';
	print '<div><button type="button" class="butAction" onclick="hideTutorialOverlay()">' . $langs->trans("H2G2Close") . '</button>';
	print '<button type="button" id="H2G2-button-help-next" class="butAction" onclick="hideTutorialOverlay()">' . $langs->trans("Next") . '</button></div>';
	print '</div></div>';
} else {
	print "<h3>" . $langs->trans("H2G2PageIsWrongConfigured") . "</h3>";

	// Add commentaries on variables
	$headerFunction .= " - " . $langs->trans("H2G2ErrorHeaderFunction");

	$arrayOfVariables = array(
		'forms' => 1,
		'moduleName' => 0,
		'pageName' => 0,
		'pagePicto' => 0,
		'tabPicto' => 0,
		'headerFunction' => 1,
		'jsArray' => 0,
		'cssArray' => 0,
		'linkBack' => 0,
	);

	print "<table class='noborder centpercent'>";
	print "<tr><th align='left'>" . $langs->trans("H2G2ErrorTableVariable") . "</th>";
	print "<th align='left'>" . $langs->trans("H2G2ErrorTableValue") . "</th>";
	print "<th align='right'>" . $langs->trans("H2G2ErrorTableRequired") . "</th>";
	print "</tr>";
	foreach ($arrayOfVariables as $variable => $required) {
		print '<tr><td>$' . $variable . '</td><td>';
		if (gettype($$variable) == "array") {
			if (!is_object($$variable[0])) print implode(" | ", $$variable);
			else if (get_class($$variable[0]) == "H2G2FormSetup") foreach ($$variable as $v) print $v->title . "<br>";
		} else print $$variable;
		print '</td><td align="right"><span class="fa fa-';
		if ($required) print 'check';
		else print 'times';
		print '"></td></span></tr>';
	}
}
