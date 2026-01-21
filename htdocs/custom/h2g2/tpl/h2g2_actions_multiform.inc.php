<?php
/** ACTIONS **/

// Actions to update all
if ($action == 'update' && !empty($user->admin)) {
	foreach ($forms as $form) {
		$form->saveConfFromPost(true);
	}
} else if ($action == "removeImage") {
	$const = GETPOST('const', 'alphanohtml');
	if (isset($const)) {
		if (dol_delete_file($conf->$moduleName->dir_output . '/uploads/' . $conf->global->$const)) {
			dolibarr_del_const($db, $const, $conf->entity);
			setEventMessages($langs->trans('H2G2ImageDeletionSuccess'), null);
		} else {
			setEventMessages($langs->trans('H2G2DeletionFailed'), null, 'errors');
		}
	}
}

include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

if (!empty($action) && $action != "edit") {
	$selectedForm = GETPOST("form-name", "alphanohtml");
	$moreParameters = (!empty($selectedForm) ? ("?form-name=" . $selectedForm) : "");
	exit(header("Location:" . $_SERVER['PHP_SELF'] . $moreParameters));
}
