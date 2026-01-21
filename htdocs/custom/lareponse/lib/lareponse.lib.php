<?php
/* Copyright (C) 2020 SuperAdmin
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
 * \file    lareponse/lib/lareponse.lib.php
 * \ingroup lareponse
 * \brief   Library files with common functions for Lareponse
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

/**
 * Prepare admin pages header
 *
 * @return array
 */
function lareponseAdminPrepareHead()
{
	global $langs, $conf, $user;

	$langs->load("lareponse@lareponse");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/lareponse/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/lareponse/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// On vérifie que le module est activé
	if ($conf->h2g2->enabled) {
		dol_include_once('/h2g2/lib/h2g2.lib.php');
		$langs->load('h2g2@h2g2');

		if ($user->admin && isH2G2InstalledWithMinVersion('15.0.11')) {
			// H2G2 migration page only for admin
			$head[$h][0] = dol_buildpath('/h2g2/admin/migration_page.php?module=modLareponse&modulePath=/lareponse/core/modules/modLareponse.class.php', 1);
			$head[$h][1] = '<i class="fas fa-wrench"></i>&nbsp;' . $langs->trans("MigrationPageTitle");
			$head[$h][2] = 'migration';
			$h++;

			// H2G2 information page
			$langs->load('h2g2@h2g2');
			$head[$h][0] = dol_buildpath('/h2g2/admin/information_page.php?module=modLareponse&modulePath=/lareponse/core/modules/modLareponse.class.php', 1);
			$head[$h][1] = $langs->trans("InformationPage");
			$head[$h][2] = 'information';
			$h++;
		}
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'lareponse');

	return $head;
}

/**
 * Prepare documentation pages header
 *
 * @return array
 */
function generateLareponseDocumentationHeader()
{
	global $langs, $conf;

	$langs->load("lareponse@lareponse");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/lareponse/user_doc.php", 1);
	$head[$h][1] = $langs->trans("UserDocTitle");
	$head[$h][2] = 'userdoc';
	$h++;
	$head[$h][0] = dol_buildpath("/lareponse/changelog.php", 1);
	$head[$h][1] = $langs->trans("NewsTitle");
	$head[$h][2] = 'changelog';
	$h++;

	// H2G2 contactus page
	$langs->load('h2g2@h2g2');
	$head[$h][0] = dol_buildpath('/h2g2/admin/contactus_page.php?module=modLareponse&modulePath=/lareponse/core/modules/modLareponse.class.php', 1);
	$head[$h][1] = $langs->trans("ContactUsPage");
	$head[$h][2] = 'contactus';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'lareponse');
	return $head;
}

/**
 *  Return a link to the user articles (with optionaly the picto)
 *  Reformatted from getNomUrl native function
 *
 * @param  User       $user                     Concerned user
 * @param  int        $withpictoimg             Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small)
 * @param  string     $option                   On what the link point to ('leave', 'nolink', )
 * @param  integer    $infologin                Add complete info tooltip
 * @param  integer    $notooltip                1=Disable tooltip on picto and name
 * @param  int        $maxlen                   Max length of visible user name
 * @param  int        $hidethirdpartylogo       Hide logo of thirdparty if user is external user
 * @param  string     $mode                     ''=Show firstname and lastname, 'firstname'=Show only firstname, 'login'=Show login
 * @param  string     $morecss                  Add more css on link
 * @param  int        $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
 * @return    string                                String with URL
 */
function activeContributorUrl($user, $withpictoimg = 0, $option = '', $infologin = 0, $notooltip = 0, $maxlen = 24, $hidethirdpartylogo = 0, $mode = '', $morecss = '', $save_lastsearch_value = -1)
{
	global $langs, $conf, $db, $hookmanager;
	global $dolibarr_main_authentication, $dolibarr_main_demo;
	global $menumanager;

	if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && $withpictoimg) $withpictoimg = 0;

	$result = '';
	$label = '';
	$link = '';
	$linkstart = '';
	$linkend = '';

	if (!empty($user->photo)) {
		$label .= '<div class="photointooltip">';
		$label .= Form::showphoto('userphoto', $user, 0, 60, 0, 'photowithmargin photologintooltip', 'small', 0, 1);    // Force height to 60 so we total height of tooltip can be calculated and collision can be managed
		$label .= '</div><div style="clear: both;"></div>';
	}

	$label .= '<div class="centpercent">';
	$label .= '<u>' . $langs->trans("User") . '</u><br>';
	$label .= '<b>' . $langs->trans('Name') . ':</b> ' . $user->getFullName($langs, '');
	if (!empty($user->login))
		$label .= '<br><b>' . $langs->trans('Login') . ':</b> ' . $user->login;
	$label .= '<br><b>' . $langs->trans("EMail") . ':</b> ' . $user->email;
	if (!empty($user->admin))
		$label .= '<br><b>' . $langs->trans("Administrator") . '</b>: ' . yn($user->admin);
	if (!empty($user->societe_id)) {    // Add thirdparty for external users
		$thirdpartystatic = new Societe($db);
		$thirdpartystatic->fetch($user->societe_id);
		if (empty($hidethirdpartylogo)) $companylink = ' ' . $thirdpartystatic->getNomUrl(2, (($option == 'nolink') ? 'nolink' : ''));    // picto only of company
		$company = ' (' . $langs->trans("Company") . ': ' . $thirdpartystatic->name . ')';
	}
	if (isset($user->societe_id)) {
		$type = ($user->societe_id ? $langs->trans("External") . $company : $langs->trans("Internal"));
		$label .= '<br><b>' . $langs->trans("Type") . ':</b> ' . $type;
	}
	$label .= '<br><b>' . $langs->trans("Status") . '</b>: ' . $user->getLibStatut(0);
	$label .= '</div>';

	// Info Login
	if ($infologin) {
		$label .= '<br>';
		$label .= '<br><u>' . $langs->trans("Connection") . '</u>';
		$label .= '<br><b>' . $langs->trans("IPAddress") . '</b>: ' . $_SERVER["REMOTE_ADDR"];
		if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY)) $label .= '<br><b>' . $langs->trans("ConnectedOnMultiCompany") . ':</b> ' . $conf->entity . ' (user entity ' . $user->entity . ')';
		$label .= '<br><b>' . $langs->trans("AuthenticationMode") . ':</b> ' . $_SESSION["dol_authmode"] . (empty($dolibarr_main_demo) ? '' : ' (demo)');
		$label .= '<br><b>' . $langs->trans("ConnectedSince") . ':</b> ' . dol_print_date($user->datelastlogin, "dayhour", 'tzuser');
		$label .= '<br><b>' . $langs->trans("PreviousConnexion") . ':</b> ' . dol_print_date($user->datepreviouslogin, "dayhour", 'tzuser');
		$label .= '<br><b>' . $langs->trans("CurrentTheme") . ':</b> ' . $conf->theme;
		$label .= '<br><b>' . $langs->trans("CurrentMenuManager") . ':</b> ' . $menumanager->name;
		$s = picto_from_langcode($langs->getDefaultLang());
		$label .= '<br><b>' . $langs->trans("CurrentUserLanguage") . ':</b> ' . ($s ? $s . ' ' : '') . $langs->getDefaultLang();
		$label .= '<br><b>' . $langs->trans("Browser") . ':</b> ' . $conf->browser->name . ($conf->browser->version ? ' ' . $conf->browser->version : '') . ' (' . $_SERVER['HTTP_USER_AGENT'] . ')';
		$label .= '<br><b>' . $langs->trans("Layout") . ':</b> ' . $conf->browser->layout;
		$label .= '<br><b>' . $langs->trans("Screen") . ':</b> ' . $_SESSION['dol_screenwidth'] . ' x ' . $_SESSION['dol_screenheight'];
		if (!empty($conf->browser->phone)) $label .= '<br><b>' . $langs->trans("Phone") . ':</b> ' . $conf->browser->phone;
		if (!empty($_SESSION["disablemodules"])) $label .= '<br><b>' . $langs->trans("DisabledModules") . ':</b> <br>' . join(', ', explode(',', $_SESSION["disablemodules"]));
	}

	$url = dol_buildpath('/lareponse/article_list.php', 2) . '?search_fk_user_creat=' . $user->id;
	if ($option == 'leave') $url = DOL_URL_ROOT . '/holiday/list.php?id=' . $user->id;

	if ($option != 'nolink') {
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
		if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
	}

	$linkstart = '<a href="' . $url . '"';
	$linkclose = "";
	if (empty($notooltip)) {
		if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
			$langs->load("users");
			$label = $langs->trans("ShowUser");
			$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
		}
		$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
		$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
	}
	if (!is_object($hookmanager)) {
		include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
		$hookmanager = new HookManager($user->db);
	}
	$hookmanager->initHooks(array('userdao'));
	$parameters = array('id' => $user->id);
	$reshook = $hookmanager->executeHooks('getnomurltooltip', $parameters, $user, $action);    // Note that $action and $object may have been modified by some hooks
	if ($reshook > 0) $linkclose = $hookmanager->resPrint;

	$linkstart .= $linkclose . '>';
	$linkend = '</a>';

	//if ($withpictoimg == -1) $result.='<div class="nowrap">';
	$result .= (($option == 'nolink') ? '' : $linkstart);
	if ($withpictoimg) {
		$paddafterimage = '';
		if (abs($withpictoimg) == 1) $paddafterimage = 'style="margin-right: 3px;"';
		// Only picto
		if ($withpictoimg > 0) $picto = '<!-- picto user --><div class="inline-block nopadding userimg' . ($morecss ? ' ' . $morecss : '') . '">' . img_object('', 'user', $paddafterimage . ' ' . ($notooltip ? '' : 'class="classfortooltip"'), 0, 0, $notooltip ? 0 : 1) . '</div>';
		// Picto must be a photo
		else $picto = '<!-- picto photo user --><div class="inline-block nopadding userimg' . ($morecss ? ' ' . $morecss : '') . '"' . ($paddafterimage ? ' ' . $paddafterimage : '') . '>' . Form::showphoto('userphoto', $user, 0, 0, 0, 'userphoto' . ($withpictoimg == -3 ? 'small' : ''), 'mini', 0, 1) . '</div>';
		$result .= $picto;
	}
	if ($withpictoimg > -2 && $withpictoimg != 2) {
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result .= '<div class="inline-block nopadding valignmiddle usertext' . ($morecss ? ' ' . $morecss : '') . '">';
		if ($mode == 'login') $result .= dol_trunc($user->login, $maxlen);
		else $result .= $user->getFullName($langs, '', ($mode == 'firstname' ? 2 : -1), $maxlen);
		if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result .= '</div>';
	}
	$result .= (($option == 'nolink') ? '' : $linkend);
	//if ($withpictoimg == -1) $result.='</div>';
	if (isset($companylink)) {
		$result .= $companylink;
	}
	return $result;
}


/**
 * Get entity id where tag/article are shared with
 *
 * @param  string    $table_element    table_element
 * @return array    getEntity()  ids of entity that elements are shared with
 */
function getEntityLareponse($table_element)
{
	global $conf, $db;

	if (isset($conf->multicompany->enabled) && $conf->multicompany->enabled && !empty($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING)) {
		$res = array();
		$res[] = $conf->entity;

		if ($table_element == 'categorie') {
			$table_element = 'tag';
		}

		if ($table_element == 'lareponse_article') {
			$table_element = 'article';
		}

		$sql = 'SELECT options from ' . MAIN_DB_PREFIX . 'entity where rowid = ' . $conf->entity . ' LIMIT 1';
		$result = $db->query($sql);

		if ($result) {
			while ($obj = $db->fetch_object($result)) {
				//check $table_element is present on the pattern
				if (preg_match(".$table_element.", $obj->options) == 1) {
					$tags = json_decode($obj->options);

					if ($tags->sharings->tag) {
						foreach ($tags->sharings->tag as $tag) {
							$res[] = $tag;
						}
					}
				}
			}
		}
		return implode(',', $res);
	} else {
		return getEntity($table_element);
	}
}

/**
 * Prepare array with list of tabs
 *
 * @param  Object    $object    Object related to tabs
 * @param  string    $type      Type of category
 * @return  array                Array of tabs to show
 */
function tags_prepare_head(Tag $object, $type)
{
	global $langs, $conf, $user;

	// Load translation files required by the page
	$langs->loadLangs(array('categories', 'products'));

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/lareponse/card_tag.php?id=' . $object->id . '&amp;type=' . $type, 1);
	$head[$h][1] = $langs->trans("Category");
	$head[$h][2] = 'card';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'categories_' . $type);

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'categories_' . $type, 'remove');

	return $head;
}

/**
 * Display multiselect button
 *
 * @param  Object    $object             object
 * @param  string    $permissiontoadd    right
 * @return void
 */
function displayMultiSelectButtonModify($object, $permissiontoadd)
{
	global $langs;
	// Define main button @Modify
	$mainBtnModify = array(
		'href' => $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit',
		'label' => $langs->trans("Modify"),
		'picto' => '<span class="fas fa-pencil-alt"></span>',
		'disabled' => !$permissiontoadd,
		'title' => '',
	);

	// Define other entries for the button
	// [#174] Ajout de l'état "Clos" pour un article
	// Si l'état est clos
	if ($object->private == 2) {
		$entriesModify = array(
			array(
				'href' => $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit',
				'label' => $langs->trans("Modify"),
				'picto' => '<i class="fas fa-pencil-alt"></i>',
				'disabled' => !$permissiontoadd,
				'title' => '',
			),
			array(
				'href' => 'javascript:callToAction(\'clone\', \'' . addslashes($langs->trans('ConfirmClonearticle')) . '\')',
				'label' => $langs->trans("ToClone"),
				'picto' => '<i class="fa fa-clone"></i>',
				'disabled' => !$permissiontoadd,
				'title' => '',
			),
			array(
				'href' => 'javascript:callToAction(\'open\', \'' . addslashes($langs->trans('ConfirmOpenArticle')) . '\')',
				'label' => $langs->trans("LaReponseOpenedArticles"),
				'picto' => '<i class="fa fa-unlock"></i>',
				'disabled' => !$permissiontoadd,
				'title' => '',
			),
		);
	} else {
		$entriesModify = array(
			array(
				'href' => $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit',
				'label' => $langs->trans("Modify"),
				'picto' => '<i class="fas fa-pencil-alt"></i>',
				'disabled' => !$permissiontoadd,
				'title' => '',
			),
			array(
				'href' => 'javascript:callToAction(\'clone\', \'' . addslashes($langs->trans('ConfirmClonearticle')) . '\')',
				'label' => $langs->trans("ToClone"),
				'picto' => '<i class="fa fa-clone"></i>',
				'disabled' => !$permissiontoadd,
				'title' => '',
			),
			array(
				'href' => 'javascript:callToAction(\'close\', \'' . addslashes($langs->trans('ConfirmCloseArticle')) . '\')',
				'label' => $langs->trans("LaReponseClosedArticles"),
				'picto' => '<i class="fa fa-lock"></i>',
				'disabled' => !$permissiontoadd,
				'title' => '',
			),
		);
	}

	if (isset($entriesModify)) {
		print buildMultiEntriesButton($mainBtnModify, $entriesModify);
	} else {
		print '';
	}
}

/**
 * Display multiselect button
 *
 * @param  Object    $object                object
 * @param  string    $permissiontoread      right
 * @param  string    $permissiontoexport    right
 * @param  string    $user                  user id
 * @return void
 */
function displayMultiSelectButtonPublish($object, $permissiontoread, $permissiontoexport, $user)
{
	global $langs;
	// Define main button @Publish
	if (!$object->publish_token && ($user->rights->lareponse->article->publish ?? false)) {
		$mainBtn = array(
			'href' => 'javascript:callToAction(\'publish\', \'' . addslashes($langs->trans('ConfirmPubliclyPublish')) . '\')',
			'label' => $langs->trans("PublicPublish"),
			'picto' => '<i class="fa fa-paper-plane-o"></i>',
			'disabled' => ($object->publish_token && $user->rights->lareponse->article->publish),
			'title' => '',
		);
	} elseif ($object->publish_token && ($user->rights->lareponse->article->publish ?? false)) {
		$mainBtn = array(
			'href' => 'javascript:callToAction(\'unpublish\', \''. $langs->trans('ConfirmPubliclyUnPublish') . '\')',
			'label' => $langs->trans("PublicUnPublish"),
			'picto' => '<i class="fa fa-exchange"></i>',
			'disabled' => (!$object->publish_token && $user->rights->lareponse->article->publish),
			'title' => '',
		);
	}

	// Define other entries for the button
	$entries = array(
		array(
			'href' => $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&optioncss=print',
			'label' => $langs->trans("Print"),
			'picto' => '<i class="fa fa-print"></i>',
			'disabled' => !$permissiontoread,
			'title' => '',
		),
		array(
			'href' => 'javascript:callToAction(\'export\', \'' . addslashes($langs->trans('ConfirmExportArticle')) . '\')',
			'label' => $langs->trans("Export"),
			'picto' => '<i class="fa fa-exchange"></i>',
			'disabled' => !$permissiontoexport,
			'title' => '',
		)
	);

	if ($object->publish_token) array_push($entries, array(
		'href' => '#',
		'label' => '<a id="clipboardcopytoken" data-token="' . $object->publish_token . '">' . $langs->trans("LaReponseCopyLink") . '</a>',
		'picto' => '<i class="fa fa-clipboard"></i>',
		'disabled' => 0,
		'title' => '',
	));

	if (isset($entries) && isset($mainBtn)) {
		print buildMultiEntriesButton($mainBtn, $entries);
	} else {
		print '';
	}
}

/**
 * Display all tags
 *
 * @param  int    $tagVal    int
 * @return  object                Object to get
 */
function getObjectNameByType($tagVal)
{

	global $langs;

	switch ($tagVal['type']) {
		case 0:
			$typeObject = $langs->trans('product');
			break;
		case 1:
			$typeObject = $langs->trans('supplier');
			break;
		case 2:
			$typeObject = 'Tiers';
			break;
		case 3:
			$typeObject = $langs->trans('member');
			break;
		case 4:
			$typeObject = $langs->trans('contact');
			break;
		case 5:
			$typeObject = $langs->trans('bank_account');
			break;
		case 6:
			$typeObject = $langs->trans('project');
			break;
		case 7:
			$typeObject = $langs->trans('user');
			break;
		case 12:
			$typeObject = 'Ticket';
			break;
		case 43:
			$typeObject = 'LaReponse';
			break;
		default:
			$typeObject = 'GestionParc';
	}
	return $typeObject;
}

/**
 * Display headers of objects
 *
 * @param  string    $type               int
 * @param  object    $moduleClassName    int
 * @return void
 */
function prepareHead($type, $moduleClassName)
{
	global $langs;

	// Utilisation de réflexion pour appeler dynamiquement la fonction de préparation du type spécifié
	$prepareHead = strtolower($type) . '_prepare_head';
	if (function_exists($prepareHead)) {
		$head = call_user_func($prepareHead, $moduleClassName);
	}

	if ($head) {
		if ($type == 'Societe') {
			print dol_get_fiche_head($head, 'lareponse_article', $langs->trans(ucfirst($type)), 0, 'company');
		} else {
			print dol_get_fiche_head($head, 'lareponse_article', $langs->trans(ucfirst($type)), 0, (isset($moduleClassName->public) ? strtolower($type) . 'pub' : strtolower($type)));
		}
	}
}


/**
 * Display headers of objects
 *
 * @param  object    $moduleClassName    int
 * @param  string    $type               string
 * @return string   $morehtmlref
 */
function gestionParcHeader($moduleClassName, $type)
{
	global $db, $langs;

	$contacts = $moduleClassName->getObjectLinked('contact');
	if ($contacts) {
		$contactCounter = count($contacts);
		if ($contactCounter > 1)
			$conlinked = $langs->trans('MultipleContactLinked');
		else {
			$contact = new ExtraContact($db);
			$contact->fetch($contacts[0]);
			$conlinked = $contact->getNomUrl(1, '', 0, '', -1, 0, -1, 0);
		}
	} else $conlinked = $langs->trans('NoContactLinked');

	$thirdparty = new Societe($db);

	if ($type == "Device") {
		$morehtmlref = '<button type="button" class="btn-copy btn-copy-title" data-clipboard-text="' . $moduleClassName->name . '"><img src="../gestionparc/img/clippy.svg" alt="Copy to clipboard"></button>';

		$thirdparty->fetch($moduleClassName->owner);
		if (!$thirdparty->name) {
			$belongsTo = $langs->trans('NoThirdpartyOwner');
		} else {
			$belongsTo = '<a href="' . dol_buildpath('/gestionparc/index.php?socid=' . $thirdparty->id, 1) . '">' . img_picto($langs->trans('thirdparty'), 'object_company', '', false, 0, 0, '', 'paddingright classfortooltip valigntextbottom') . $thirdparty->name . '</a>';
		}

		$morehtmlref .= '</br><text style="font-size: small">' . $langs->trans('OwnBy') . " " . $belongsTo . " / " . $conlinked;
		'</text>';
		$thirdparty->fetch($moduleClassName->fk_soc_maintenance);
		if (!$thirdparty->name) {
			$maint = $langs->trans('NoThirdparty');
		} else {
			$maint = '<a href="' . dol_buildpath('/gestionparc/index.php?socid=' . $thirdparty->id, 1) . '">' . img_picto($langs->trans('thirdparty'), 'object_company', '', false, 0, 0, '', 'paddingright classfortooltip valigntextbottom') . $thirdparty->name . '</a>';
		}

		$contact = new ExtraContact($db);
		$contact->fetch($moduleClassName->fk_con_maintenance);
		if (!$contact->lastname) {
			$con = $langs->trans('NoContact');
		} else {
			$con = $contact->getNomUrl(1, '', 0, '', -1, 0, -1, 0);
		}

		$morehtmlref .= '</br><text style="font-size: small">' . $langs->trans('MaintainBy') . " " . $maint . " / " . $con;
		'</text>';
	} elseif ($type == "Application") {
		$morehtmlref = '<button type="button" class="btn-copy btn-copy-title" data-clipboard-text="' . $moduleClassName->name . '"><img src="../gestionparc/img/clippy.svg" alt="Copy to clipboard"></button>';

		$objectsLinked = $moduleClassName->getObjectLinked('thirdparty');
		if ($objectsLinked) {
			$maintainCounter = count($objectsLinked);
			if ($maintainCounter > 1)
				$belongsTo = $langs->trans('MultipleThirdpartyLinked');
			else {
				$thirdparty->fetch($objectsLinked[0]);
				$belongsTo = '<a href="' . dol_buildpath('/gestionparc/index.php?socid=' . $thirdparty->id, 1) . '">' . img_picto($langs->trans('thirdparty'), 'object_company', '', false, 0, 0, '', 'paddingright classfortooltip valigntextbottom') . $thirdparty->name . '</a>';
			}
		} else $belongsTo = $langs->trans('NoThirdpartyLinked');

		$morehtmlref .= '</br><text style="font-size: small">' . $langs->trans('OwnBy') . " " . $belongsTo . " / " . $conlinked;
		'</text>';
		$thirdparty->fetch($moduleClassName->fk_soc_maintenance);
		$maintain = '<a href="' . dol_buildpath('/gestionparc/index.php?socid=' . $thirdparty->id, 1) . '">' . img_picto($langs->trans('thirdparty'), 'object_company', '', false, 0, 0, '', 'paddingright classfortooltip valigntextbottom') . $thirdparty->name . '</a>';
		$morehtmlref .= '</br><text style="font-size: small">' . $langs->trans('MaintainBy') . " " . $maintain;
		'</text>';
	} elseif ($type == "Address") {
		$morehtmlref = '<button type="button" class="btn-copy btn-copy-title" data-clipboard-text="' . $moduleClassName->name . '"><img src="../gestionparc/img/clippy.svg" alt="Copy to clipboard"></button>';
		$thirdparty->fetch($moduleClassName->owner);
		if (!$thirdparty->name) {
			$belongsTo = $langs->trans('NoThirdpartyOwner');
		} else {
			$belongsTo = '<a href="' . dol_buildpath('/gestionparc/index.php?socid=' . $thirdparty->id, 1) . '">' . img_picto($langs->trans('thirdparty'), 'object_company', '', false, 0, 0, '', 'paddingright classfortooltip valigntextbottom') . $thirdparty->name . '</a>';
		}
		$morehtmlref .= '</br><text style="font-size: small">' . $langs->trans('OwnBy') . " " . $belongsTo . " / " . $conlinked;
		'</text>';
	}

	return $morehtmlref;
}

/**
 * Build list of links to passed articles
 *
 * @param  array    $articlesToSend    List of articles to get list of links
 * @return string
 */
function buildArticleLinks($articlesToSend)
{
	global $db;

	$updatedArticles = "";
	$debugArticle = "";
	$errorArticle = "";
	$article = new Article($db);

	// Build string of updated articles with url
	if (!empty($articlesToSend) && is_array($articlesToSend)) {
		foreach ($articlesToSend as $articleId => $useless) {
			$debugArticle .= ($debugArticle == "" ? "" : ", ") . $articleId;
			if ($article->fetch($articleId)) {
				$updatedArticles .= "<br><b>" . $article->title . "</b> -> <a href='" . dol_buildpath('/lareponse/article_card.php?id=' . $article->id, 3) . "' target='_blank'>" . dol_buildpath('/lareponse/article_card.php?id=' . $article->id, 3) . "</a>";
			} else $errorArticle .= ($errorArticle == "" ? "" : ", ") . $articleId;
		}
	}

	if (empty($errorArticle)) dol_syslog('lareponse.lib.php : buildArticleLinks(' . $debugArticle . ')', LOG_DEBUG);
	else dol_syslog('lareponse.lib.php : buildArticleLinks(' . $debugArticle . ') - Articles with ids (' . $errorArticle . ')  can not be fetch', LOG_WARNING);

	return $updatedArticles;
}

/**
 * Get the list of available article templates
 *
 * @return array
 */
function getArticleTemplateList()
{
	global $db, $conf;

	$templates = array();

	$sql = "SELECT rowid, label FROM " . MAIN_DB_PREFIX . "c_email_templates";
	$sql .= " WHERE type_template = 'article' AND entity IN (0, " . $conf->entity . ") AND active = 1";

	$resql = $db->query($sql);
	if ($resql) while ($row = $db->fetch_object($resql)) $templates[$row->rowid] = $row->label;

	return $templates;
}

/**
 * Get first article tempalte available
 *
 * @return int|mixed
 */
function getFirstArticleTemplate()
{
	$templates = getArticleTemplateList();
	if (!empty($templates) && is_array($templates)) {
		$template = key($templates);
		if (!empty($template)) return $template;
	}
	return 0;
}

/**
 * Get update article event by date. You can change operator if you want
 *
 * @param  int       $date        Timestamp of date to check
 * @param  string    $operator    Operator of date checking. It can be ">", "<", "=" or other date operator in SQL
 * @return array|int
 * @throws Exception
 */
function getDatedArticleEvents($date, $operator = "=")
{
	global $db, $conf;

	$articleEvents = array();

	// Get ActionComm that corresponds to article update or article comment
	$sql = "SELECT id, fk_user_author, fk_element, code FROM " . MAIN_DB_PREFIX . "actioncomm WHERE datep " . $operator . " '" . $db->idate($date) . "' AND code IN ('ARTICLE_MO', 'COMMENT_CR') AND elementtype = 'article@lareponse' AND entity = " . $conf->entity;
	$resql = $db->query($sql);

	if (!empty($resql)) {
		while ($event = $db->fetch_object($resql)) {
			$articleEvents[$event->id] = $event;
		} // For each event
	} else {
		// $resql
		dol_syslog("Cron Articles (LaReponse) - Error : impossible to execute this query '$sql'", LOG_ERR);
		return -1;
	}

	return $articleEvents;
}

/**
 * Get Email template's rowid, topic, label and content
 *
 * @param  int    $templateId    Id of email template
 * @return false|int|Object Template object or -1
 * @throws Exception
 */
function getLaReponseEmailTemplate($templateId)
{
	global $db, $conf;

	$sql = "SELECT rowid, topic, content, label FROM " . MAIN_DB_PREFIX . "c_email_templates WHERE entity IN (0, " . $conf->entity . ") AND rowid = " . $templateId;

	$resql = $db->query($sql);
	if (!empty($resql)) {
		return $db->fetch_object($resql);
	} else {
		dol_syslog("Cron Articles (LaReponse) - Error : impossible to execute this query '$sql'", LOG_ERR);
		return -1;
	}
}

/**
 * Get LaReponse article notification cron frequency and unit
 *
 * @return array Frequency and unit with this shape array("unit" => $unit, "frequency" => $frequency)
 */
function getArticleNotificationCronFrequency()
{
	global $db;

	$sql = "SELECT frequency, unit_frequency FROM " . MAIN_DB_PREFIX . "cronjob WHERE label = 'LaReponseCronNotificationArticle' AND objectname = 'ArticleCron' AND methodename = 'notifySubscribedUsers'";
	$resql = $db->query($sql);
	// Get frequency and unit
	if (!empty($resql)) {
		$cron = $db->fetch_object($resql);

		$unit = $cron->unit_frequency;
		$frequency = $cron->frequency;
	}
	// If cron not found or request failed, we get default values (unit = 60 for minutes and frequency = 60 for 60 minutes or 1 hour)
	if (empty($unit)) $unit = 60;
	if (empty($frequency)) $frequency = getDolGlobalInt("LAREPONSE_TIME_BEFORE_NOTIFICATION_CHECK");
	if (empty($frequency)) $frequency = 60;

	return array("unit" => $unit, "frequency" => $frequency);
}
