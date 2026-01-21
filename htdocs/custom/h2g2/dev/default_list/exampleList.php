<?php
/* Copyright (C) 2022     Fabien FERNANDES ALVES  <fabien@code42.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       dev/default_list/exampleList.php
 *        \ingroup    h2g2
 *        \brief      This an example of a listing for a dolibarr object
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) { $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) { $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) { $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) { $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) { $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php";
}
if (!$res) { die("Include of main fails");
}

// Inclusion
dol_include_once('/h2g2/lib/default_list.lib.php');
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php'; // TODO : You must adapt this include to your object

// Avoid editor errors
global $db, $langs, $user, $conf, $hookmanager;

// Load translation files required by the page
$langs->loadLangs(array('other'));

// TODO : You must adapt all variables of this section
// Initialize variable
$context = 'exampleinvoicelist'; // Context of the page
$permissiontoread = $user->rights->facture->lire;
$permissiontoadd = $user->rights->facture->creer;
$permissiontodelete = $user->rights->facture->supprimer;
$newBtnUrl = dol_buildpath('/interventionplus/interventionplus_card.php', 2).'?action=create'; // Url to redirect to with the add button
$help_url="EN:Module_Notification|FR:Module_Notification_FR|ES:Módulo_Notification";
$title = $langs->trans('ExampleInvoiceList');
$ajaxUrl = dol_buildpath("/h2g2/dev/default_list/ajax_get_example_list.php", 1); // Url of the ajax file to get values
$ajaxMassactionUrl = dol_buildpath("/h2g2/dev/default_list/ajax_massaction_example_list.php", 1); // Url of ajax file to execute massactions
$picto = 'information.png@h2g2';
$arrayofmassactions = array(
	'demo_fast',
	'demo_slow',
);

// Initialize technical objects
$object = new Facture($db); // TODO : You must adapt the object to yours
$extrafields = new ExtraFields($db);
$form = new Form($db);
$diroutputmassaction = $conf->supercotrolia->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($context)); // Note that conf->hooks_modules contains array
$extrafields->fetch_name_optionals_label($object->table_element); // Fetch optionals attributes and labels
$object->fields = dol_sort_array($object->fields, 'position'); // Sort object fields in function of position
$arrayfields = getArrayFieldsForListing($context, $object, $extrafields); // Get the list of fields that can be selected / that will be shown

// #62
$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Security check
if (empty($conf->supercotrolia->enabled)) { accessforbidden('Module not enabled');
}
if ($user->socid > 0) { accessforbidden(); // Protection for external user
}

/*
 * Actions
 */

// Selection of new fields
$contextpage = $context;
require DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

/*
 * View
 */

// Output page
// --------------------------------------------------------------------
$arrayjs = array('/h2g2/js/default_list.js.php');
$arraycss = array('/h2g2/css/default_list.css');

llxHeader('', $title, $help_url, '', 0, 0, $arrayjs, $arraycss);

// Form to select fields to display
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $context); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">'; // Used for field selection
print '<input type="hidden" name="action" value="list">'; // Used for code42 theme
print '<input type="hidden" name="contextpage" value="'.$context.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

// Display the table header with title
// --------------------------------------------------------------------
print listHeader($title, $newBtnUrl, $permissiontoadd, $arrayofmassactions, $picto);

// Opening table
// --------------------------------------------------------------------
print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table id="defaultlist" data-ajax="'.$ajaxUrl.'" data-ajax-massaction="'.$ajaxMassactionUrl.'" class="tagtable nobottomiftotal liste">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr id="searchFieldsContainer" class="liste_titre_filter">';
foreach ($object->fields as $key => $val) {
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') { $cssforfield .= ($cssforfield ? ' ' : '').'center';
	} elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) { $cssforfield .= ($cssforfield ? ' ' : '').'center';
	} elseif (in_array($val['type'], array('timestamp'))) { $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	} elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') { $cssforfield .= ($cssforfield ? ' ' : '').'right';
	}
	if (!empty($arrayfields['t.'.$key]['checked'])) {
		print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').'">';
		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) { print $form->selectarray('search_'.$key, $val['arrayofkeyval'], '', $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth100', 1);
		} elseif (strpos($val['type'], 'integer:') === 0) {
			print $object->showInputField($val, 't.'.$key, '', '', '', 'search_', 'maxwidth125', 1);
		} elseif (!preg_match('/^(date|timestamp)/', $val['type'])) { print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="">';
		}
		print '</td>';
	}
}
// Extra fields
require DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>';

// Fields title label
// --------------------------------------------------------------------
print '<tr id="filterFieldsContainer" class="liste_titre">';
foreach ($object->fields as $key => $val) {
	$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
	if ($key == 'status') { $cssforfield .= ($cssforfield ? ' ' : '').'center';
	} elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) { $cssforfield .= ($cssforfield ? ' ' : '').'center';
	} elseif (in_array($val['type'], array('timestamp'))) { $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	} elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') { $cssforfield .= ($cssforfield ? ' ' : '').'right';
	}
	if (!empty($arrayfields['t.'.$key]['checked'])) {
		$moreattrib = ($cssforfield ? 'class="'.$cssforfield.'" ' : '');
		$moreattrib.= 'data-key="'.$key.'"';
		print getTitleFieldOfList($val['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', '', $moreattrib, '', '', ($cssforfield ? $cssforfield.' ' : ''))."\n";
	}
}
// Extra fields
require DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Action column
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', '', '', 'center maxwidthsearch ')."\n";
print '</tr>';

// Display the loader
// --------------------------------------------------------------------
print displayLoader($arrayfields);

// End of table
print '</table>';
print '</div>';

print '</form>';

// #62
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// End of page
llxFooter();
$db->close();
