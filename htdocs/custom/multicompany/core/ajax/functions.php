<?php
/* Copyright (C) 2011-2023	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Herve Prot		<herve.prot@symeos.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       /multicompany/core/ajax/functions.php
 *       \brief      File to return ajax result
 */

if (! defined('NOREQUIREMENU'))         define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))         define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))         define('NOREQUIREAJAX', '1');
//if (! defined('NOREQUIRESOC'))        define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))       define('NOREQUIRETRAN', '1');
if (! defined('NOREQUIREHOOK'))         define('NOREQUIREHOOK', '1');
if (! defined('CSRFCHECK_WITH_TOKEN'))  define('CSRFCHECK_WITH_TOKEN', '1'); // Token is required even in GET mode

if (isset($_POST['action']) && $_POST['action'] === 'getEntityLogo') {
	if (!defined('NOLOGIN')) {
		define('NOLOGIN', '1');		// This means this output page does not require to be logged.
	}
	$entity = (!empty($_POST['id']) ? (int) $_POST['id'] : 1);
	if (is_numeric($entity)) {
		define("DOLENTITY", $entity);
	}
} else {
	if (!defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', '1'); // Disables token renewal
	}
}

$res=@include("../../../main.inc.php");						// For root directory
if (empty($res) && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php")) {
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
}
if (empty($res)) {
	$res=@include("../../../../main.inc.php");		// For "custom" directory
}

dol_include_once('/multicompany/class/actions_multicompany.class.php', 'ActionsMulticompany');
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";

$id			= GETPOST('id', 'int');			// id of entity
$action		= GETPOST('action', 'alpha');	// action method
$type		= GETPOST('type', 'alpha');		// type of action
$element	= GETPOST('element', 'alpha');	// type of element
$fk_element	= GETPOST('fk_element', 'int');	// id of element

$template	= GETPOST('template', 'int');
if (GETPOSTISSET('entities')) {
	$entities = json_decode(GETPOST('entities', 'none'), true);
}

/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead('application/json');

if (empty($conf->multicompany->enabled)) {
	$db->close();
	http_response_code(403);
}

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

// Registering the location of boxes
if (!empty($action) && is_numeric($id)) {
	if ($action == 'switchEntity' && !empty($user->login)) {
		dol_syslog("multicompany action=".$action." entity=".$id, LOG_DEBUG);
		$object = new ActionsMulticompany($db);
		echo $object->switchEntity($id);
	} elseif ($action == 'getEntityUrl' && !empty($user->login) && getDolGlobalInt('MULTICOMPANY_FEATURES_LEVEL') && getDolGlobalInt('MULTICOMPANY_OPEN_NEW_SESSION_AFTER_SWITCHING')) {
		dol_syslog("multicompany action=".$action." entity=".$id, LOG_DEBUG);
		$object = new ActionsMulticompany($db);
		$object->getInfo($id);
		if (!empty($object->url)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
			$cryptkey = (!empty($conf->file->instance_unique_id) ? $conf->file->instance_unique_id : $conf->file->cookie_cryptkey);
			$objectstatic = new GenericObject($db);
			$securitycode = getRandomPassword(true);
			$objectstatic->setValueFrom('pass_temp', $securitycode, 'user', $user->id);
			$ret = json_encode(
				array(
					'status' => 'success',
					'id' => ((int) $object->id),
					'url' => $object->url,
					'securitycode' => base64_encode(dolEncrypt($securitycode, $cryptkey))
				)
			);
		} else {
			$ret = json_encode(
				array(
					'status' => 'error',
					'id' => ((int) $object->id)
				)
			);
		}
		echo $ret;
	} elseif ($action == 'setStatusEnable' && !empty($user->admin) && empty($user->entity)) {
		dol_syslog("multicompany action=".$action." type=".$type." entity=".$id, LOG_DEBUG);
		$object = new ActionsMulticompany($db);
		$fieldname = ($type == 'activetemplate' ? 'active' : $type);
		echo $object->setStatus($id, $fieldname, 1);
	} elseif ($action == 'setStatusDisable' && !empty($user->admin) && empty($user->entity)) {
		dol_syslog("multicompany action=".$action." type=".$type." entity=".$id, LOG_DEBUG);
		$object = new ActionsMulticompany($db);
		$fieldname = ($type == 'activetemplate' ? 'active' : $type);
		$ret = $object->setStatus($id, $fieldname, 0);
		if ($ret == 1 && $type == 'active') {
			$ret = $object->setStatus($id, 'visible', 0);
		}
		echo $ret;
	} elseif ($action == 'deleteEntity' && $id != 1 && !empty($user->admin) && empty($user->entity)) {
		dol_syslog("multicompany action=".$action." entity=".$id, LOG_DEBUG);
		$object = new ActionsMulticompany($db);
		echo $object->deleteEntity($id);
	} elseif ($action == 'setColOrder' && !empty($user->admin) && empty($user->entity)) {
		$id = (int) $id;
		$direction = GETPOST('dir', 'aZ');
		$colOrder = array('id' => $id, 'direction' => $direction);
		if (dolibarr_set_const($db, 'MULTICOMPANY_COLORDER', json_encode($colOrder), 'chaine', 0, '', 0) > 0) {
			$ret = json_encode(array('status' => 'success'));
		} else {
			$ret = json_encode(array('status' => 'error'));
		}
		echo $ret;
	} elseif ($action == 'setColHidden' && !empty($user->admin) && empty($user->entity)) {
		$state = GETPOST('state', 'aZ');
		$colHidden = (!empty($conf->global->MULTICOMPANY_COLHIDDEN) ? json_decode($conf->global->MULTICOMPANY_COLHIDDEN, true) : array());
		if ($state == 'visible') {
			$colHidden = array_diff($colHidden, array(intval($id)));
		} else if ($state == 'hidden') {
			array_push($colHidden, intval($id));
		}
		sort($colHidden);
		if (dolibarr_set_const($db, 'MULTICOMPANY_COLHIDDEN', json_encode($colHidden), 'chaine', 0, '', 0) > 0) {
			$ret = json_encode(array('status' => 'success'));
		} else {
			$ret = json_encode(array('status' => 'error'));
		}
		echo $ret;
	} elseif ($action == 'modifyEntity' && ((!empty($user->admin) && empty($user->entity)) || !empty($user->rights->multicompany->thirdparty->write))) {
		if ($element == 'societe') {
			$object = new Societe($db);
			$ret = $object->fetch($fk_element);
			if ($ret > 0) {
				$object->oldcopy = clone $object;
				// To not set code if third party is not concerned. But if it had values, we keep them.
				if (empty($object->client) && empty($object->oldcopy->code_client))				$object->code_client='';
				if (empty($object->fournisseur) && empty($object->oldcopy->code_fournisseur))	$object->code_fournisseur='';
				$object->entity = $id;
				$ret = $object->update($object->id, $user, 0, $object->oldcopy->codeclient_modifiable(), $object->oldcopy->codefournisseur_modifiable(), 'update', 1);
				if ($ret > 0) {
					$ret = json_encode(array('status' => 'success'));
				} else {
					$ret = json_encode(array('status' => 'error', 'error' => $object->errors));
				}
			} else {
				$ret = json_encode(array('status' => 'error', 'error' => $object->errors));
			}
		} elseif ($element == 'contact') {
			require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
			$object = new Contact($db);
			$ret = $object->fetch($fk_element);
			if ($ret > 0) {
				$object->entity = $id;
				$ret = $object->update($object->id, $user, 1, 'update', 1);
				if ($ret > 0) {
					$ret = json_encode(array('status' => 'success'));
				} else {
					$ret = json_encode(array('status' => 'error', 'error' => $object->errors));
				}
			} else {
				$ret = json_encode(array('status' => 'error', 'error' => $object->errors));
			}
		} elseif ($element == 'project') {
			require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

			$object = new Project($db);
			$ret = $object->fetch($fk_element);
			if ($ret > 0) {

				$object->entity = $id;

				$ret = $object->setValueFrom('entity',$object->entity);
				if ($ret > 0) {
					$ret = json_encode(array('status' => 'success'));
				}
				else {
					$ret = json_encode(array('status' => 'error', 'error' => $object->errors));
				}
			}
			else {
				$ret = json_encode(array('status' => 'error', 'error' => $object->errors));
			}
		}
		echo $ret;
	} elseif ($action === 'getEntityOptions' && !empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT) && !empty($user->admin) && empty($user->entity)) {
		$object = new ActionsMulticompany($db);
		$object->getInfo($id);
		$entities = $object->getEntitiesList(false, false, true, true);
		echo json_encode(
			array(
				'status' => 'success',
				'options' => $object->options,
				'labels' => $entities
			)
		);
	} elseif ($action === 'duplicateUserGroupRights' && !empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT)
		&& !empty($user->admin) && empty($user->entity) && !empty($template) && !empty($entities)) {
			$multicompany = new ActionsMulticompany($db);
			$ret = $multicompany->duplicateUserGroupRights($id, $template, $entities);
			if ($ret > 0) {
				echo json_encode(array('status' => 'success'));
			} else {
				echo json_encode(
					array(
						'status' => 'error',
						'id' => $id,
						'template' => $template,
						'entities' => $entities
					)
				);
			}
	} elseif ($action === 'checkIfElementIsUsed'
		&& !empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) && !empty($conf->global->MULTICOMPANY_SHARING_BYELEMENT_ENABLED)
		&& !empty($user->admin) && empty($user->entity) && !empty($element) && !empty($entities)) {
			$result = array();
			if (!in_array($element, array('category'))) {
				$multicompany = new ActionsMulticompany($db);
				$elementdir = $elementname = $element;
				// For compatibility
				if ($elementname == 'thirdparty') {
					$elementdir = $elementname = 'societe';
				}
				require_once DOL_DOCUMENT_ROOT.'/'.$elementdir.'/class/'.$elementname.'.class.php';
				$classname = ucfirst($elementname);
				$staticobject = new $classname($db);
				foreach($entities as $entity) {
					$objectisused = $staticobject->isObjectUsed($id, $entity);
					if ($objectisused > 0) {
						$multicompany->getInfo($entity);
						$result[$entity] = $multicompany->label;
					}
				}
			}
			if (!empty($result)) {
				$langs->loadLangs(array('multicompany@multicompany'));
				echo json_encode(
					array(
						'status' => 'error',
						'error' => $langs->transnoentities("ErrorElementIsUsedBy", implode('<br>', $result))
					)
				);
			} else {
				echo json_encode(
					array(
						'status' => 'success',
						'element' => $element
					)
				);
			}
	} elseif ($action === 'getChartOfAccountsOfEntity' && !empty($user->rights->accounting->chartofaccount)) {
		$object = new ActionsMulticompany($db);
		$object->getInfo($id, true, 'CHARTOFACCOUNTS');
		if (!empty($object->constants['CHARTOFACCOUNTS'])) {
			echo json_encode(
				array(
					'status' => 'success',
					'chartofaccounts' => $object->constants['CHARTOFACCOUNTS']
				)
			);
		}
	} else if ($action === 'getEntityLogo' && empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX)) {
		$urllogo = DOL_URL_ROOT.'/theme/login_logo.png';
		if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
			$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;entity='.$id.'&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small);
		} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo)) {
			$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;entity='.$id.'&amp;modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo);
			$width = 128;
		} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/dolibarr_logo.png')) {
			$urllogo = DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/dolibarr_logo.png';
		} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png')) {
			$urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo.png';
		}
		$bgimg = null;
		$unsplashimg = null;
		if (!empty($conf->global->MULTICOMPANY_LOGIN_BACKGROUND_BY_ENTITY)) {
			if (!empty($conf->global->ADD_UNSPLASH_LOGIN_BACKGROUND)) {
				$unsplashimg = $conf->global->ADD_UNSPLASH_LOGIN_BACKGROUND;
			}
			if (!empty($conf->global->MAIN_LOGIN_BACKGROUND)) {
				$bgimg = DOL_URL_ROOT.'/viewimage.php?cache=1&noalt=1&entity='.$id.'&modulepart=mycompany&file=logos/'.urlencode($conf->global->MAIN_LOGIN_BACKGROUND);
			}
		}
		echo json_encode(
			array(
				'status' => 'success',
				'token' => newToken(),
				'urllogo' => dol_html_entity_decode($urllogo, null),
				'bgimg' => dol_html_entity_decode($bgimg, null),
				'unsplashimg' => dol_html_entity_decode($unsplashimg, null)
			)
		);
	}
	$db->close();
} else {
    $db->close();
    http_response_code(403);
}
