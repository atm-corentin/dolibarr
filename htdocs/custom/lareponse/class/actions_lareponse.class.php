<?php
/*
 * Copyright (C) 2022-2022 Ayoub Bayed      <ayoub@code42.fr>

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
 */

/**
 * \file    lareponse/class/actions_lareponse.class.php
 * \ingroup lareponse
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */


/**
 * Class ActionsLaReponse
 */
class ActionsLaReponse
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;
	/**
	 * @var string Error
	 */
	public $error = '';
	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/* Add here any other hooked methods... */

	/**
	 * addSearchEntry Method Hook Call
	 *
	 * @param  array  $parameters  parameters
	 * @param  Object $object      Object to use hooks on
	 * @param  string $action      Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param  object $hookmanager class instance
	 * @return int
	 */
	public function addSearchEntry($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $user;

		$position = 30;
		$langs->load('lareponse@lareponse');

		if (!empty($conf->lareponse->enabled) && ($user->rights->lareponse->article->read ?? false)) {
			$arrayresult['articlelist'] = array(
				'position' => $position,
				'text' => $langs->trans("LaReponseArticle"),
				'url' => dol_buildpath('/lareponse/article_list.php', 1)
			);
			$this->results = $arrayresult;
		}

		return 0;
	}

	/**
	 * Get the module header for contactus tab
	 *
	 * @param   array       $parameters         Array of parameter (we only use $parameters['module'] to check module header to display)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function generateContactUsTabHeader($parameters)
	{
		dol_include_once("/lareponse/lib/lareponse.lib.php");

		// Check if we need to display contact us information header
		if ($parameters['module'] == 'modLareponse') {
			$this->results['head'] = generateLareponseDocumentationHeader();
			$this->results['active_tab'] = 'contactus';
			$this->results['langs'] = 'lareponse@lareponse';
			$this->results['header_icon'] = '';
			return 1;
		}
	}

	/**
	 * Get the module header for information tab
	 *
	 * @param   array       $parameters         Array of parameter (we only use $parameters['module'] to check module header to display)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function generateInformationTabHeader($parameters)
	{
		dol_include_once('/lareponse/lib/lareponse.lib.php');

		if ($parameters['module'] == 'modLareponse') {
			$this->results['head'] = lareponseAdminPrepareHead();
			$this->results['active_tab'] = 'information';
			$this->results['langs'] = 'lareponse@lareponse';
			$this->results['header_icon'] = '';
			return 1;
		}
	}

	/**
	 * Get the module header for migration tab
	 *
	 * @param   array       $parameters         Array of parameter (we only use $parameters['module'] to check module header to display)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function generateMigrationTabHeader($parameters)
	{
		dol_include_once('/lareponse/lib/lareponse.lib.php');

		// Check if we need to display contrat plus information header
		if ($parameters['module'] == 'modLareponse') {
			$this->results['head'] = lareponseAdminPrepareHead();
			$this->results['active_tab'] = 'migration';
			$this->results['langs'] = 'lareponse@lareponse';
			$this->results['header_icon'] = '';
			return 1;
		}
	}

	/**
	 * Execute action completeTabsHead
	 *
	 * @param   array           $parameters     Array of parameters
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         'add', 'update', 'view'
	 * @param   Hookmanager     $hookmanager    hookmanager
	 * @return  int                             <0 if KO,
	 *                                          =0 if OK but we want to process standard actions too,
	 *                                          >0 if OK and we want to replace standard actions.
	 */
	public function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
	{

		global $langs, $conf, $user, $db;

		require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
		require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		dol_include_once('/lareponse/class/article.class.php');
		dol_include_once('/lareponse/class/tag.class.php');

		if (isset($conf->gestionparc->enabled) && $conf->gestionparc->enabled) {
			dol_include_once('/gestionparc/lib/device.lib.php');
			dol_include_once('/gestionparc/class/device.class.php');
			dol_include_once('/gestionparc/lib/application.lib.php');
			dol_include_once('/gestionparc/class/application.class.php');
			dol_include_once('/gestionparc/lib/address.lib.php');
			dol_include_once('/gestionparc/lib/output.lib.php');
			dol_include_once('/gestionparc/class/categorie.class.php');
			dol_include_once('/gestionparc/class/role.class.php');
			dol_include_once('/gestionparc/class/gestionparccommonobject.class.php');
			dol_include_once('/gestionparc/lib/contact.lib.php');
			dol_include_once('/gestionparc/class/contact.class.php');
			dol_include_once('/gestionparc/class/address.class.php');
		}

		$langs->load('lareponse@lareponse');

		if (!isset($parameters['object']->element)) {
			return 0;
		}
		if ($parameters['mode'] == 'remove') {
			$counter = count($parameters['head']);
			$element = $parameters['object']->element;
			$id = $parameters['object']->id;
			if (in_array($element, ['project', 'product', 'societe', 'ticket'])) {
				$type = ucfirst($element);
				$object = new $type($db);
				$object->fetch($id);
				$listOfArticle = array();
				$article = new Article($db);
				if (dolibarr_get_const($db, "LAREPONSE_TAG_CATEGORIES_ACTIVE", $conf->entity) == '1') {
					if ($element == 'societe') {
						$listOfTagsSupplier = $object->getCategoriesCommon('supplier');
						$listOfTagsCustomer = $object->getCategoriesCommon('customer');

						foreach ($listOfTagsSupplier as $supplierTag) {
							$listOfArticlesBySupplierTag = $article->getArticles($supplierTag);
							foreach ($listOfArticlesBySupplierTag as $articles) {
								$listOfArticle[] = $articles->id;
							}
						}
						foreach ($listOfTagsCustomer as $customerTag) {
							$listOfArticlesByCustomerTag = $article->getArticles($customerTag);
							foreach ($listOfArticlesByCustomerTag as $articles) {
								$listOfArticle[] = $articles->id;
							}
						}
					} else {
						$listOfTags = $object->getCategoriesCommon($element);
						if (dolibarr_get_const($db, "LAREPONSE_TAG_CATEGORIES_ACTIVE", $conf->entity) == '1') {
							foreach ($listOfTags as $tag) {
								$listOfArticlesByTag = $article->getArticles($tag);
								foreach ($listOfArticlesByTag as $articles) {
									$listOfArticle[] = $articles->id;
								}
							}
						}
					}
				}

				if ($element == 'societe') {
					if (isset($conf->gestionparc->enabled) && $conf->gestionparc->enabled) {
						if (dolibarr_get_const($db, "LAREPONSE_TAG_GESTIONPARC_ACTIVE", $conf->entity) == '1') {
							$arrayTagsGestionParc = getAllTag($id);
							foreach ($arrayTagsGestionParc as $tagGestionParc) {
								$listOfArticlesByGestionParcTag = $article->getArticles($tagGestionParc);
								foreach ($listOfArticlesByGestionParcTag as $articles) {
									$listOfArticle[] = $articles->id;
								}
							}
						}
					}
				}

				if ($type == 'Ticket') {
					if (isset($conf->gestionparc->enabled) && $conf->gestionparc->enabled) {
						if (dolibarr_get_const($db, "LAREPONSE_TAG_GESTIONPARC_ACTIVE", $conf->entity) == '1') {
							$device = new Device($db);
							if (isset($object->array_options['options_c42ticket_device_linked'])) {
								$idDevice = $device->fetch($object->array_options['options_c42ticket_device_linked']);
								$listRolesOfDevice = getRolesOfDevice($idDevice);
								foreach ($listRolesOfDevice as $deviceTag) {
									$listOfArticlesByDeviceTag = $article->getArticles($deviceTag);
									foreach ($listOfArticlesByDeviceTag as $articles) {
										$listOfArticle[] = $articles->id;
									}
								}
							}
						}
					}
				}

				$articlesAssociated = array_unique($listOfArticle);
				$count = is_array($articlesAssociated) ? count($articlesAssociated) : 0;
				$parameters['head'][$counter][0] = dol_buildpath('/lareponse/article_list.php', 1) . '?id=' . $id . '&amp;type=' . $element .'&search_category_tag_operator=1';
				if ($count == 0) {
					if (!empty($parameters['head'])) $parameters['head'][$counter][1] = $langs->trans('Articles') .'<span style="color: var(--colortextbackhmenu) !important;" class="badge marginleftonlyshort">'.$count.'</span>';
				} else {
					if (!empty($parameters['head'])) $parameters['head'][$counter][1] = $langs->trans('Articles') .'<span style="background: var(--colorbackhmenu1) ; color: var(--colortextbackhmenu)!important;" class="badge marginleftonlyshort">'.$count.'</span>';
				}
				$parameters['head'][$counter][2] = 'lareponse_article';
				$counter++;
			}
			if ($counter > 0 && (int) DOL_VERSION < 14) {
				$this->results = $parameters['head'];
				return 1;
			} else {
				return 0;
			}
		} elseif ($parameters['mode'] == 'add') {
			$counter = count($parameters['head']);
			$element = $parameters['object']->element;
			$id = $parameters['object']->id;

			if (in_array($element, ['device', 'application', 'address', 'contact'])) {
				$type = ucfirst($element);
				$object = new $type($db);
				$object->fetch($id);
				$listOfArticle = array();
				$listOfTags = array();
				$idTagsList = array();
				$listOfRoles = array();
				$article = new Article($db);
				if (isset($conf->gestionparc->enabled) && $conf->gestionparc->enabled) {
					if (dolibarr_get_const($db, "LAREPONSE_TAG_GESTIONPARC_ACTIVE", $conf->entity) == '1') {
						if ($element == 'application') {
							$newtype = ucfirst(substr($element, 0, 3));
							$getRolesOf = 'getRolesOf' . $newtype;
						} else {
							$getRolesOf = 'getRolesOf' . ucfirst($element);
						}
						if (function_exists($getRolesOf)) {
							$listOfRoles = call_user_func($getRolesOf, $id);
						}
					}
				}
				if (dolibarr_get_const($db, "LAREPONSE_TAG_CATEGORIES_ACTIVE", $conf->entity) == '1') {
					if ($element == 'contact') {
						$listOfTags = $object->getCategoriesCommon($element);
					}
				}
				if ($listOfTags && $listOfRoles) {
					$idTagsList = array_merge($listOfTags, $listOfRoles);
				} elseif ($listOfTags) {
					$idTagsList = $listOfTags;
				} elseif ($listOfRoles) {
					$idTagsList = $listOfRoles;
				}
				if ($idTagsList) {
					foreach ($idTagsList as $tag) {
						$listOfArticlesByTags = $article->getArticles($tag);
						foreach ($listOfArticlesByTags as $articles) {
							$listOfArticle[] = $articles->id;
						}
					}
				}
				$articlesAssociated = array_unique($listOfArticle);
				$count = is_array($articlesAssociated) ? count($articlesAssociated) : 0;
				$parameters['head'][$counter][0] = dol_buildpath('/lareponse/article_list.php', 1) . '?id=' . $id . '&amp;type=' . $element .'&search_category_tag_operator=1';
				if ((float) DOL_VERSION >= 17.0) {
					if (isset($parameters['filterorigmodule']) && $element == 'contact' && $parameters['filterorigmodule'] == 'core') {
						if ($count == 0) {
							if (!empty($parameters['head'])) $parameters['head'][$counter][1] = $langs->trans('Articles') .'<span style"color: var(--colortextbackhmenu) !important;" class="badge marginleftonlyshort">'.$count.'</span>';
						} else {
							if (!empty($parameters['head'])) $parameters['head'][$counter][1] = $langs->trans('Articles') .'<span style="background: var(--colorbackhmenu1); color: var(--colortextbackhmenu) !important;" class="badge marginleftonlyshort">'.$count.'</span>';
						}
					} elseif ($element == 'address' || $element == 'application' || $element == 'device') {
						if ($count == 0) {
							if (!empty($parameters['head'])) $parameters['head'][$counter][1] = $langs->trans('Articles') .'<span style"color: var(--colortextbackhmenu) !important;" class="badge marginleftonlyshort">'.$count.'</span>';
						} else {
							if (!empty($parameters['head'])) $parameters['head'][$counter][1] = $langs->trans('Articles') .'<span style="background: var(--colorbackhmenu1); color: var(--colortextbackhmenu) !important;" class="badge marginleftonlyshort">'.$count.'</span>';
						}
					}
				} else {
					if ($element == 'address' || $element == 'application' || $element == 'device' || $element == 'contact') {
						if ($count == 0) {
							if (!empty($parameters['head'])) $parameters['head'][$counter][1] = $langs->trans('Articles') .'<span style"color: var(--colortextbackhmenu) !important;" class="badge marginleftonlyshort">'.$count.'</span>';
						} else {
							if (!empty($parameters['head'])) $parameters['head'][$counter][1] = $langs->trans('Articles') .'<span style="background: var(--colorbackhmenu1); color: var(--colortextbackhmenu) !important;" class="badge marginleftonlyshort">'.$count.'</span>';
						}
					}
				}

				$parameters['head'][$counter][2] = 'lareponse_article';
				$counter++;
			}
			if ($counter > 0 && (int) DOL_VERSION < 14) {
				$this->results = $parameters['head'];
				return 1;
			} else {
				return 0;
			}
		}
	}

	/**
	 * Print Action in QuickAddBlockMenu (v13)
	 *
	 * @param array $parameters Array of parameter
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printQuickAddBlock($parameters)
	{
		global $langs;

		$this->resprints = '
                <div class="quickaddblock center">
                    <a class="quickadddropdown-icon-link" href="' . dol_buildpath('/lareponse/article_card.php?action=create&mainmenu=lareponse', 1) . '" title="' . $langs->trans("lareponseNewArticle") . '">
                        <img src="' . dol_buildpath('/lareponse/img/object_lareponse.png', 1) . '" alt="LaReponse"/><br>
                        ' . $langs->trans("ArticleCard") . '
                    </a>
                </div>
                ';
		return 0;
	}

	/**
	 * Execute action menuDropdownQuickaddItems (v16+)
	 *
	 * @param  array           $parameters     Array of parameter
	 * @param  CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param  string          $action         'add', 'update', 'view'
	 * @param  Hookmanager     $hookmanager    hookmanager
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function menuDropdownQuickaddItems(&$parameters, &$object, &$action, $hookmanager)
	{
		global $user;

		$hookmanager->results['article'] = array(
			"url" => dol_buildpath('/lareponse/article_card.php?action=create&mainmenu=lareponse', 1),
			"title" => "lareponseNewArticle@lareponse",
			"name" => "ArticleCard@lareponse",
			"picto" => "object_lareponse@lareponse",
			"activation" => ($user->rights->lareponse->article->write ?? false),
			"position" => 505,
		);

		$hookmanager->resArray['article'] = $hookmanager->results['article'];
		return 0;
	}

	/**
	 * Overloading the email type template function : check permission on an object
	 *
	 * @param  array          $parameters     Hook metadatas (context, etc...)
	 * @param  string         $action         Current action (if set). Generally create or edit or null
	 * @param  HookManager    $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                            <0 if KO,
	 *                                        =0 if OK but we want to process standard actions too,
	 *                                        >0 if OK and we want to replace standard actions.
	 */
	public function emailElementlist($parameters, &$action, $hookmanager)
	{

		global $langs;

		$langs->load('lareponse@lareponse');

		$this->results['article'] = '<span class="fa fa-newspaper"> </span> ' . $langs->trans('LaReponseArticle');
		return 0;
	}

    /**
     * Hook executed to add more html footer
     *
     * @param 	array 		$parameters			Parameters
     * @return 	void
     *
     * @see 	file 	main.inc.php (l.1588)
     */
    public function printCommonFooter($parameters)
    {
        global $langs, $form, $conf, $user;

        $langs->load("lareponse@lareponse");

        if (strpos($parameters['context'], 'userihm') !== false) {
            $confTocFoldValue = $conf->global->LAREPONSE_TOC_FOLD_DEFAULT ?? 0;
            $out = '<tr class="oddeven"><td>' . $langs->trans('LareponseIhmFoldTocByDefault') . '</td>';
            $out .= '<td>' . yn($confTocFoldValue) .'</td>';
            $out .= '<td class="nowrap">';
            $out .= '<input id="check_LAREPONSE_TOC_FOLD_DEFAULT" name="check_LAREPONSE_TOC_FOLD_DEFAULT" type="checkbox" onclick="clickCheckLareponseTocFold(this)" ';
            $out .= ' ' . (GETPOST('action') == 'edit' ? '' : ' disabled') . ' ' . (isset($user->conf->LAREPONSE_TOC_FOLD_DEFAULT) ? 'checked' : '') . ' >';
            $out .= $langs->trans('UsePersonalValue') . '</td><td>';

            if (GETPOST('action') == 'edit') {
                $out .= $form->selectyesno('LAREPONSE_TOC_FOLD_DEFAULT', $confTocFoldValue, 1, is_null($confTocFoldValue));
                print "<script>
                    function clickCheckLareponseTocFold(checkbox) {
                        $('#LAREPONSE_TOC_FOLD_DEFAULT').prop('disabled', !checkbox.checked);
                    }
                </script>";
            } else {
                $out .= (isset($user->conf->LAREPONSE_TOC_FOLD_DEFAULT) ? yn($user->conf->LAREPONSE_TOC_FOLD_DEFAULT) : '') . '</td></tr>';
            }
            print "<script>
                let lareponseMenurow = $('table.noborder.centpercent  tr.oddeven:last-child').first();
                lareponseMenurow.after(" . json_encode($out) . ");
            </script>";
        }
    }

    /**
     *	Save personal parameter
     *  This function is a clone of dol_set_user_param, but considers 0 values (while the former skips them)
     *
     *	@param	DoliDB	$db         Handler database
     *	@param	Conf	$conf		Object conf
     *	@param	User	$user      	Object user
     *	@param	array	$tab        Array (key=>value) with all parameters to save
     *	@return int         		<0 if KO, >0 if OK
     */
    private function custom_set_user_param($db, $conf, &$user, $tab)
    {
        // Verification parametres
        if (count($tab) < 1) return -1;

        $db->begin();

        // We remove old parameters for all keys in $tab
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."user_param";
        $sql .= " WHERE fk_user = ".$user->id;
        $sql .= " AND entity = ".$conf->entity;
        $sql .= " AND param in (";
        $i = 0;
        foreach ($tab as $key => $value)
        {
            if ($i > 0) $sql .= ',';
            $sql .= "'".$db->escape($key)."'";
            $i++;
        }
        $sql .= ")";

        $resql = $db->query($sql);
        if (!$resql)
        {
            dol_print_error($db);
            $db->rollback();
            return -1;
        }

        foreach ($tab as $key => $value)
        {
            // Set new parameters
            if (!is_null($value))
            {
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."user_param(fk_user,entity,param,value)";
                $sql .= " VALUES (".$user->id.",".$conf->entity.",";
                $sql .= " '".$db->escape($key)."','".$db->escape($value)."')";

                $result = $db->query($sql);
                if (!$result)
                {
                    dol_print_error($db);
                    $db->rollback();
                    return -1;
                }
                $user->conf->$key = $value;
            } else {
                unset($user->conf->$key);
            }
        }

        $db->commit();
        return 1;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          &$action        Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $db, $conf, $user;

        if (strpos($parameters['context'], 'userihm') !== false) {
            if (GETPOST('action', 'aZ09') == 'update') {
                require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

                $tabparam = array();
                $tocFold = GETPOST("LAREPONSE_TOC_FOLD_DEFAULT", 'int');
                if (GETPOST("check_LAREPONSE_TOC_FOLD_DEFAULT") == "on" && ($tocFold === "0" || $tocFold === "1")) $tabparam["LAREPONSE_TOC_FOLD_DEFAULT"] = $tocFold;
                else $tabparam["LAREPONSE_TOC_FOLD_DEFAULT"] = null;

                $this->custom_set_user_param($db, $conf, $user, $tabparam);

                dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity); // reset cache
            }
        }
    }
}
