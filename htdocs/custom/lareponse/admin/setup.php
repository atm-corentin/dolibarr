<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020 SuperAdmin
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lareponse/admin/setup.php
 * \ingroup lareponse
 * \brief   Lareponse setup page.
 */

if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1'); // Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

dol_include_once('/lareponse/lib/lareponse.lib.php');
dol_include_once('/lareponse/lib/lareponse_setup.lib.php');

global $langs, $user, $db, $conf;

// Translations
$langs->loadLangs(array("admin", "lareponse@lareponse"));

// Access control
if (!$user->rights->lareponse->configure) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$sections = array(
	'wizard' => array(
		'label' => 'SetupWizard',
		'params' => array(
			'LAREPONSE_WIZARD_ACTIVE' => array('label' => 'ActivateDemoMode', 'action' => 'setwizardactive', 'type' => 'switch'),
			'LAREPONSE_WIZARD_INDEX' => array('label' => 'wizardindex', 'type' => 'text'),
			'LAREPONSE_WIZARD_ARTICLE_LIST' => array('label' => 'wizardarticlelist', 'type' => 'text'),
			'LAREPONSE_WIZARD_ARTICLE_ASSISTANT' => array('label' => 'LaReponseWizardArticleAssistant', 'type' => 'text'),
		)
	),
	'articles' => array(
		'label' => 'LaReponseWidgetNumberTitle',
		'params' => array(
			'LA_REPONSE_PROTECT_TAG_DELETION' => array('label' => 'LaReponseActivateTagDeletionProtection', 'action' => 'setprotecttagdeletion', 'type' => 'switch'),
			'LA_REPONSE_WIDGET_NUMBER' => array('label' => 'LaReponseWidgetNumberNext', 'action' => 'LA_REPONSE_WIDGET_NUMBER', 'type' => 'input', 'min' => 0, 'max' => 100),
			'LAREPONSE_DISPLAY_IMAGES_IN_TOOLTIP' => array('label' => 'LaReponseDisplayImagesInTooltip', 'action' => 'setdisplayimgtooltip', 'type' => 'switch'),
			'LAREPONSE_PREVIEW_TOOLTIP' => array('label' => 'LaReponseArticlePreviewTooltip', 'action' => 'LAREPONSE_PREVIEW_TOOLTIP', 'type' => 'input', 'min' => 5)
		)
	),
	'notification' => array(
		'label' => 'LaReponseSetupNotifications'
	),
	'comments' => array(
		'label' => 'LaReponseSetupComments',
		'params' => array(
			'LAREPONSE_COMMENT_NUMBER' => array('label' => 'LaReponseSetupCommentNumber', 'action' => 'LAREPONSE_COMMENT_NUMBER', 'type' => 'input', 'min' => 3)
		)
	),
	'public' => array(
		'label' => 'LaReponseSetupPublic',
		'params' => array(
			'LAREPONSE_PUBLIC_BANNER_COLOR' => array('label' => 'LaReponseSetupBannerColorLabel', 'action' => 'LAREPONSE_PUBLIC_BANNER_COLOR','type' => 'color'),
		)
	),
    'toc' => array(
        'label' => 'LaReponseSetupToc',
        'params' => array(
            'LAREPONSE_TOC_FOLD_DEFAULT' => array('label' => 'LareponseFoldTocByDefault', 'action' => 'setfoldtocactive', 'type' => 'switch')
        )
    )
);

if (isset($conf->categorie->enabled) && $conf->categorie->enabled) {
	$sections['tags'] = array(
		'label' => 'SetupTags',
		'params' => array(
			'LAREPONSE_TAG_CATEGORIES_ACTIVE' => array('label' => 'TagsCategories', 'action' => 'settagcategoriesactive','type' => 'switch'),
		)
	);

	if (isset($conf->gestionparc->enabled) && $conf->gestionparc->enabled) {
		$sections['tags']['params']['LAREPONSE_TAG_GESTIONPARC_ACTIVE'] = array('label' => 'TagsGestionParc', 'action' => 'settaggestionparcactive','type' => 'switch');
	}
}

if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled) {
	$sections['wysiwyg'] = array(
		'label' => 'Module2000Name' //Module2000Name = WYSIWYG module name
	);
}
/*
 * Action
 */

if (strpos($action, 'set') !== false) {
	$value = GETPOST('value', 'none');
	$constName = '';

	if ($action == 'setlareponsewysiwygmode') {
		$constName = 'LAREPONSE_WYSIWYG_MODE';
	} else {
		foreach ($sections as $section) {
			if (array_key_exists('params', $section)) {
				foreach ($section['params'] as $key => $param) {
					if (array_key_exists('action', $param) && $param['action'] === $action) {
						$constName = $key;
					}
				}
			}
		}
	}

	if (!empty($constName)) {
		if ((float) DOL_VERSION >= 10) {
			dolibarr_set_const($db, $constName, $value, (is_numeric($value) ? 'integer' : 'string'), 0, '', $conf->entity);
		} else {
			dolibarr_set_const($db, $constName, $value, $conf->entity);
		}
	}
}

if (!empty(GETPOST('submit'))) {
	$posts = array_diff_key($_POST, array_flip(['token', 'previewLRWYSIWYGMode', 'submit'])); // create a copy without 'token', 'previewLRWYSIWYGMode', 'submit'

	foreach ($posts as $key => $value) {
		if ((float) DOL_VERSION >= 10) {
			dolibarr_set_const($db, $key, $value, (is_numeric($value) ? 'integer' : 'string'), 0, '', $conf->entity);
		} else {
			dolibarr_set_const($db, $key, $value, $conf->entity);
		}
	}
}

/*
 * View
 */

$page_name = $langs->trans('ConfugurationOfModule', $langs->trans('Lareponse'));
llxHeader('', $page_name, '', '', 0, 0, '', array('/lareponse/css/wysiwyg.css'));
// Subheader
$linkback = '<a href="' . ( $backtopage ? : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1' ) . '">' . $langs->trans("BackToModuleList") . '</a>';

$head = lareponseAdminPrepareHead();

print load_fiche_titre($page_name, $linkback, 'lareponse_black_50@lareponse');

print dol_get_fiche_head($head, 'settings', $page_name, -3, 'lareponse_black@lareponse');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
if ((float) DOL_VERSION >= 11) {
	print '<input type="hidden" name="token" value="' . newToken() . '">';
} else {
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
}

foreach ($sections as $section => $sectionParams) {
	print '<h1>' . $langs->trans($sectionParams['label']) . '</h1>';
	print '<table class="noborder" width="100%"><tbody>';
	print '<tr class="liste_titre">';
	print '<td style="width: 50% ;"><h4>' . $langs->trans('Parameter') . '</h4></td>';
	print '<td style="width: 50% ;" align="center">' . $langs->trans('Value') . '</td>';
	print '</tr>';
	if ($section == 'notification') {
		print '<tr class="oddeven"><td>' . $langs->trans('LaReponseSetupNotificationSend') . '</td>';
		print '<td align="center"><a href="' . $_SERVER['PHP_SELF'] . '?action=' . $value['action'] . '&value=' . !getDolGlobalInt('LAREPONSE_NOTIFICATION_CHECK') . '">';
		print (getDolGlobalInt('LAREPONSE_NOTIFICATION_CHECK') ? img_picto($langs->trans("Activated"), 'switch_on') : img_picto($langs->trans("Disabled"), 'switch_off'));
		print '</a></td>';
		print '</tr>';


		if ($action == 'edit') {
			print '<tr class="oddeven"><td>' . $langs->trans('LaReponseSetupTimeBeforeNotificationSend') . ' ' . getHelpIcon($langs->trans("LaReponseSetupTimeBeforeNotificationSendHelp")) . '</td>';
			// If cron frequency is not the same has this const, we update the const (This may be the case where we updated cron and not this const)
			$cronFrequency = getArticleNotificationCronFrequency();
			$cronFrequencyInMinutes = ($cronFrequency["frequency"] * $cronFrequency["unit"] / 60); // Unit frequency in minutes equals to 60, so we have to divide by 60 to get frequency in minutes
			if (getDolGlobalInt("LAREPONSE_NOTIFICATION_CHECK") != $cronFrequencyInMinutes) dolibarr_set_const($db, 'LAREPONSE_NOTIFICATION_CHECK', $cronFrequencyInMinutes, 'int', 0, '', $conf->entity);
			print '<td align="center"><input class="width75" name="LAREPONSE_TIME_BEFORE_NOTIFICATION_CHECK" type="number" pattern="^[0-9]*$" value="' . (getDolGlobalInt("LAREPONSE_TIME_BEFORE_NOTIFICATION_CHECK") ? : ($cronFrequency['frenquency'] ? : 5)) . '" min="5"></td>';
			print '</tr>';
			print '<tr class="oddeven"><td>' . $langs->trans('LaReponseSetupEmailTemplateUsedForNotification') . ' ' . getHelpIcon($langs->trans("LaReponseSetupEmailTemplateUsedForNotificationHelp")) . '</td>';
			// Print select input to choose mail template to use for notification
			// Const for template is linked to any template, we add a warning next to the const
			if (empty(getLaReponseEmailTemplate(getDolGlobalInt("LAREPONSE_EMAIL_TEMPLATE_FOR_NOTIFICATIONS")))) $templateWarning = "<span class='fa fa-exclamation-triangle' style='color: #e3bd00' title='" . $langs->trans("LaReponseSetupWarningTemplateEmpty") . "'></span>";
			print '<td align="center">';
			$form = new Form($db);
			print $form->selectarray('LAREPONSE_EMAIL_TEMPLATE_FOR_NOTIFICATIONS', getArticleTemplateList(), ($conf->global->LAREPONSE_EMAIL_TEMPLATE_FOR_NOTIFICATIONS ?? ''));
			print '</td>';
			print '</tr>';
		} else {
			print '<tr class="oddeven"><td>' . $langs->trans('LaReponseSetupTimeBeforeNotificationSend') . ' ' . getHelpIcon($langs->trans("LaReponseSetupTimeBeforeNotificationSendHelp")) . '</td>';
			print '<td align="center" width="70%">' . getDolGlobalInt("LAREPONSE_TIME_BEFORE_NOTIFICATION_CHECK") . '</td>';
			print '</tr>';
			print '<tr class="oddeven"><td>' . $langs->trans('LaReponseSetupEmailTemplateUsedForNotification') . ' ' . getHelpIcon($langs->trans("LaReponseSetupEmailTemplateUsedForNotificationHelp")) . '</td>';
            $emailtemplate = getLaReponseEmailTemplate(getDolGlobalInt("LAREPONSE_EMAIL_TEMPLATE_FOR_NOTIFICATIONS"));
			print '<td align="center" width="70%">' . (!empty($emailtemplate) ? $emailtemplate->label : '') . '</td>';
			print '</tr>';
		}
	} elseif ($section == 'wysiwyg') {
		print '<tr class="oddeven"><td>' . $langs->trans('LaReponseWYSIWYGReduceToolbar') . '</td>';
		if (isset($conf->global->LAREPONSE_WYSIWYG_MODE) && $conf->global->LAREPONSE_WYSIWYG_MODE == 'custom') {
			print '<td align="center"><a href="'.$_SERVER['PHP_SELF'] . '?action=setlareponsewysiwygmode&value=default">';
			print img_picto($langs->trans("Activated"), 'switch_on');
			print '</a></td>';
			$mode = 'custom';
		} else {
			print '<td align="center"><a href="'.$_SERVER['PHP_SELF'] . '?action=setlareponsewysiwygmode&value=custom">';
			print img_picto($langs->trans("Disabled"), 'switch_off');
			print '</a></td>';
			$mode = 'default';
		}
		print '</tr>';

		print '<tr class="oddeven"><td>' . $langs->trans('Preview') . '</td><td class="wysiwyg_' . $conf->global->LAREPONSE_WYSIWYG_MODE . '">';
		$doleditor = new DolEditor('previewLRWYSIWYGMode', '', '', 50, 'Full', 'In', true, true, true, ROWS_7, '90%');
		$doleditor->Create();
		print '</td></tr>';
	} else {
		foreach ($sectionParams['params'] as $param => $value) {
			print '<tr class="oddeven">';
			print '<td >' . $langs->trans($value['label']).'</td>';
			switch ($value['type']) {
				case 'switch':
					print '<td align="center"><a href="' . $_SERVER['PHP_SELF'] . '?action=' . $value['action'] . '&value=' . !getDolGlobalInt($param) . '">';
					print (getDolGlobalInt($param) ? img_picto($langs->trans("Activated"), 'switch_on') : img_picto($langs->trans("Disabled"), 'switch_off'));
					print '</a></td>';
					break;
				case 'input':
					if ($action == 'edit') {
						$max = (!empty($value['max']) ? 'max ="' . $value['max'] . '"' : ""); // max value of input : max="..."
						$min = (!empty($value['min']) ? 'min ="' . $value['min'] . '"' : 'min="0"'); // min value of input : min="..."
						print '<td align="center"><input class="width75" name="' . $value['action'] . '" type="number" pattern="^[0-9]*$" value="' . (getDolGlobalInt($param) ? : (!empty($value['min']) ? $value['min'] : 0)) . '"' . $min . " " . $max  . '></td>';
					} else {
						print '<td align="center" width="70%">' . getDolGlobalInt($param) . '</td>';
					}
					break;
				case 'color':
					$const = GetDolGlobalString($param);
					if ($action == 'edit') {
						$formother = new Formother($db);
						print '<td align="center" width="70%">' . $formother->selectColor(colorArrayToHex(colorStringToArray((!empty($const) ? $const : ''), array())), $value['action']) . '</td>';
					} else {
						print '<td align="center" width="70%"><div style="width: 60px; height: 25px; background-color: #' . $const . '; border-radius: 2px"></div></td>';
					}
					break;
				default:
					print '<td align="center" width="70%">';
					if ($action == 'edit') {
						$doleditor = new DolEditor($param, getDolGlobalString($param), '', 142, 'dolibarr_notes', 'In', false, true, true, ROWS_4, '90%');
						$doleditor->Create();
					} else {
						print getDolGlobalString($param);
					}
					print '</td>';
					break;
			}
			print '</tr>';
		}
	}
	print '</tbody></table>';
	print '</br>';
}

print '<div class="tabsAction">';
if ($action != 'edit') {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=edit" class="button">'.$langs->trans('Modify').'</a>';
} else {
	print '<input class="button" type="submit" name="submit" value="'.$langs->trans('Save').'">';
	print '<a href="'.$_SERVER['PHP_SELF'] .'" class="button">'.$langs->trans('Cancel').'</a>';
}
print '</div>';
print '</form>';

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
