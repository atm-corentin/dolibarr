<?php
/* Copyright (C) 2021 SuperAdmin
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
 * \file    themequarantedeux/class/actions_themequarantedeux.class.php
 * \ingroup themequarantedeux
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsThemeQuarantedeux
 */
class ActionsThemeQuaranteDeux
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
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
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Get the module header for information tab
	 *
	 * @param   array       $parameters         Array of parameter (we only use $parameters['module'] to check module header to display)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function generateInformationTabHeader($parameters)
	{
		dol_include_once('/themequarantedeux/lib/themequarantedeux.lib.php');

		if ($parameters['module'] == 'modThemeQuaranteDeux') {
			$this->results['head'] = themequarantedeuxAdminPrepareHead();
			$this->results['active_tab'] = 'information';
			$this->results['langs'] = 'themequarantedeux@themequarantedeux';
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
		dol_include_once('/themequarantedeux/lib/themequarantedeux.lib.php');

		if ($parameters['module'] == 'modThemeQuaranteDeux') {
			$this->results['head'] = themequarantedeuxAdminPrepareHead();
			$this->results['active_tab'] = 'migration';
			$this->results['langs'] = 'themequarantedeux@themequarantedeux';
			$this->results['header_icon'] = '';
			return 1;
		}
	}

	/**
	 * Hook executed to add more html headers
	 *
	 * @param 	array 		$parameters			Parameters
	 * @return 	void
	 *
	 * @see 	file 	main.inc.php (l.1588)
	 **/
	public function addHtmlHeader($parameters)
	{
		global $langs, $db, $conf;

		if ($_SERVER["PHP_SELF"] == '/admin/limits.php') print '<script src="' . dol_buildpath("/themequarantedeux/js/admin-limits.js", 1) . '" type="text/javascript"></script>'; // #348

		if (strpos($_SERVER["PHP_SELF"], '/export.php') !== false || strpos($_SERVER["PHP_SELF"], '/import.php') !== false) { // #369
			print '<style>
                div.div-table-responsive-no-min, div.div-table-responsive { margin-bottom: 60px !important; }
            </style>';
		}

		if (DOL_VERSION >= '18.0.04') print '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js" type="text/javascript"></script>'; // #266

		$sql = "SELECT url FROM " . MAIN_DB_PREFIX . "scriptsinject  WHERE active = 1 AND type = 'HEADER' AND entity IN ($conf->entity, 0)";
		$resql = $db->query($sql);
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$url = $obj->url;
				if ($url) {
					if (filter_var($url, FILTER_VALIDATE_URL)) {
						print '<script type="text/javascript" src="' . $url . '"></script>';
						// We had js in all context since we are linked to the main context
					} else {
						setEventMessage($langs->trans('ErrorJsUrlInclude', $url), 'errors');
					}
				}
			}
			return 0;
		}
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
		global $langs, $db, $conf, $user;

		$langs->load("themequarantedeux@themequarantedeux");

		if (DOL_VERSION >= '18.0.04') print '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js" type="text/javascript"></script>'; // #266

		if (strpos($parameters['context'], 'userihm') !== false) {
			$listOfMenuPosition = array(
				1 => $langs->trans("TC42MenuPositionClassic"),
				2 => $langs->trans("TC42MenuPositionModern")
			);

			$out = '<tr class="oddeven"><td>' . $langs->trans('TC42MenuManagement') . '</td>';
			$out .= '<td>' . $listOfMenuPosition[$conf->global->TC42_POSITION_MENU] .'</td>';
			$out .= '<td class="nowrap">';
			$out .= '<input id="check_TC42_POSITION_MENU" name="check_TC42_POSITION_MENU" type="checkbox" onclick="clickCheck(this)" ';
			$out .= ' ' . (GETPOST('action') == 'edit' ? '' : ' disabled') . ' ' . (!empty($user->conf->TC42_POSITION_MENU) ? 'checked' : '') . ' >';
			$out .= $langs->trans('UsePersonalValue') . '</td><td>';

			unset($listOfMenuPosition[$conf->global->TC42_POSITION_MENU]); //remove the current choice from admin

			if (GETPOST('action') == 'edit') {
				$form = new Form($db);
				$out .= $form->selectarray('TC42_POSITION_MENU', $listOfMenuPosition, '', 0, 0, 0, '', 0, 0, (!empty($user->conf->TC42_POSITION_MENU) ? 0 : 1), '', 'maxwidth250') . '</td></tr>';
				print "<script>
                    function clickCheck(checkbox) {
                        $('#TC42_POSITION_MENU').prop('disabled', !checkbox.checked);
                    }

                    let menurow = $('table.noborder.centpercent  tr.oddeven:last-child').first();
                    menurow.after(" . json_encode($out) . ");
                </script>";
			} else {
				$out .= (!empty($user->conf->TC42_POSITION_MENU) ? $listOfMenuPosition[$user->conf->TC42_POSITION_MENU] : '') . '</td></tr>';
				print "<script>
                    let menurow = $('table.noborder.centpercent  tr.oddeven:last-child').first();
                    menurow.after(" . json_encode($out) . ");
                </script>";
			}
		}

		if (!empty($conf->postit->enabled)) {
			$menupos = $user->conf->TC42_POSITION_MENU ?? $conf->global->TC42_POSITION_MENU ?? 1;
			print '<script src="' . dol_buildpath("/themequarantedeux/js/post-it.js", 1) . '" data-menu-pos="' . $menupos . '" type="text/javascript"></script>'; // #265
		}

		// #344
		if (strpos($parameters['context'], 'list') !== false) {
			if (!empty($conf->quicklist->enabled)) print '<script src="' . dol_buildpath("/themequarantedeux/js/quicklist.js", 1) . '" type="text/javascript"></script>';
			print '<script src="' . dol_buildpath("/themequarantedeux/js/thead-list.js", 1) . '" type="text/javascript"></script>';
		}


		$sql = "SELECT url FROM " . MAIN_DB_PREFIX . "scriptsinject  WHERE active = 1  AND type = 'FOOTER' AND entity IN ($conf->entity, 0)";
		$resql = $db->query($sql);
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$url = $obj->url;
				if ($url) {
					if (filter_var($url, FILTER_VALIDATE_URL)) {
						print '<script type="text/javascript" src="' . $url . '"></script>';
						// We had js in all context since we are linked to the main context
					} else {
						setEventMessage($langs->trans('ErrorJsUrlInclude', $url), 'errors');
					}
				}
			}
			return 0;
		}
	}


	/**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			$this->resprints = '<option value="0"'.($disabled ? ' disabled="disabled"' : '').'>'.$langs->trans("HideTopMenuMassAction").'</option>';
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;

		if ($object->statut == 0 && $conf->global->THEME_SORTABLE_ACTIVATED > 0) {
			$id = $object->id;
			$fk_element = $object->fk_element;
			$table_element_line = $object->table_element_line;
			$filepath = (empty($filepath) ? '' : $filepath);

			print '<style>
                div.sortable-drag-handle,
                div.sortable-drag-handle-subtotal {
                    flex-grow: 1;
                }
                
                .sortable-drag-handle:not(.linecolmove),
                .sortable-drag-handle-subtotal:not(.linecolmove) {
                    cursor: move;
                    margin: 0px 3px;
                }
                
                .sortable-drag-handle-subtotal:not(.linecolmove) {
                    flex-grow: 1;
                    display: inline-block;
                }
                
                    
            </style>';

			?>
			<script>
				$(document).ready(function () {
					if(!window.location.href.includes('action=editline')) {
						var table = $('#tablelines > tbody:nth-child(2)');
						if (table.length > 0) {
							$(table).find('td').each((index, td) => {
								let cell = $(td);
								let cellContent = cell.html().replace(/&nbsp;/g, '').trim();

								// Add div draggable when td contain text
								if (cell.is('.linecoldescription')) {
									let newCellContent = $('<div></div>').html(cellContent);
									cell.contents().remove();
									cell.append(newCellContent);

									cell.append('<div class="sortable-drag-handle"></div>');
									cell.css('display', 'flex');
								}

								if (cell.is('.linecoluseunit')) cell.addClass('sortable-drag-handle');
								// Add the 'show-drag-handle' class if the <td> is empty or contains non-selectable values
								if (cellContent === '' || cell.is('.linecolnum, .linecoledit, .linecoldelete')) cell.addClass('sortable-drag-handle');
							});

							// subtotal
							$(table).find('tr[data-issubtotal] > td').filter((index, td) => {
								return !$(td).attr('class')?.split(' ').some(cls => cls.startsWith('line')); // remove td with 'line' class
							}).each((index, td) => {
								let cell = $(td);
								let content = cell.html();
								if (!cell.has('img').length) {
									if (!cell.has('span.subtotal_label').length) cell.empty().append(`<div style="display: flex; flex-direction: row-reverse"><span class="sortable-drag-handle-subtotal">&nbsp;</span>${content}</div>`);
									else cell.empty().append(`<div style="display: flex"><span class="sortable-drag-handle-subtotal">&nbsp;</span>${content}</div>`);
								} else {
									cell.empty();
									cell.append(`<div style="display: flex">${content}<span class="sortable-drag-handle-subtotal">&nbsp;</span></div>`);
								}
							});

							var sortable = Sortable.create(table[0], {
								ghostClass: "ghost",
								animation: 200,

								scroll: true, // Enable the plugin. Can be HTMLElement.
								forceAutoScrollFallback: true, // force autoscroll plugin to enable even when native browser autoscroll is available
								scrollSensitivity: 60, // px, how near the mouse must be to an edge to start scrolling.
								scrollSpeed: 20, // px, speed of the scrolling
								bubbleScroll: true, // apply autoscroll to all parent elements, allowing for easier movement

								multiDrag: true, // Enable the plugin
								selectedClass: "selected-sortable-item", // Class name for selected item
								multiDragKey: navigator.appVersion.indexOf("Mac") != -1 ? "alt" : "ctrl",
								avoidImplicitDeselect: false, // true - if you don't want to deselect items on outside click

								handle: ".sortable-drag-handle, .sortable-drag-handle-subtotal",

								onStart: function(evt) {
									let TcurrentChilds = [];
									let currentLine = $(evt.item);

									if (currentLine.attr('data-issubtotal') == 'title') {
										let lock = 0;

										currentLine.nextAll('[id^="row-"]').each(function (index) {
											let dataLevel = $(this).attr('data-level');
											let dataIsSubtotal = $(this).attr('data-issubtotal');

											if (dataIsSubtotal != 'undefined' && dataLevel != 'undefined') {
												if (dataIsSubtotal && dataIsSubtotal != 'subtotal') lock = 1;
												if (!lock) TcurrentChilds.push($(this).attr('id'));
											}
										});

										currentLine.data('childrens', TcurrentChilds);

										TcurrentChilds.forEach(function(rowId) {
											let row = $('#' + rowId);
											row.fadeOut();
										});
									}
								},

								onEnd: function(evt) {
									if ($(evt.item).data('childrens') && $(evt.item).data('childrens').length > 0) {
										let childrens = $(evt.item).data('childrens');
										childrens.reverse().forEach(function(rowId) {
											let row = $('#' + rowId);
											row.insertAfter(evt.item);
											row.fadeIn();
										});
									}

									let rowOrder = Array.from(evt.from.children)
										.map(element => element.getAttribute("data-id")) // get data-id because id can contain letters
										.filter(id => id) // ignore elements without data-id
										.join(",") + ","; // string format

									$.post("<?php echo DOL_URL_ROOT; ?>/core/ajax/row.php", {
										roworder: rowOrder,
										table_element_line: "<?php echo $table_element_line; ?>",
										fk_element: "<?php echo $fk_element; ?>",
										element_id: "<?php echo $id; ?>",
										filepath: "<?php echo urlencode($filepath); ?>",
										token: "<?php echo currentToken(); ?>"
									}).done(function(data) {
										console.log("ThemeQuarantedeux : Row order updated successfully");
									})
									.fail(function(jqXHR, textStatus, errorThrown) {
										console.error("ThemeQuarantedeux : Error updating row order:", textStatus, errorThrown);
									});
								}
							});
						}
					}
				});
			</script>

			<?php
		}

        // Adding reposition for subtotal [#382]
        ?>
        <script>
            $(document).ready(function () {
                if (!window.location.href.includes('action=editline')) {
                    $('#tablelines > tbody > tr[data-issubtotal] > td > .subtotal-line-action-btn').addClass('reposition');
                }
            });
        </script>
        <?php
	}


	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$object		   	Object output on PDF
	 * @param   string	$action     	'add', 'update', 'view'
	 * @return  int 		        	<0 if KO,
	 *                          		=0 if OK but we want to process standard actions too,
	 *  	                            >0 if OK and we want to replace standard actions.
	 */
	public function beforePDFCreation($parameters, &$object, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0; $deltemp = array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}

	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$pdfhandler     PDF builder handler
	 * @param   string	$action         'add', 'update', 'view'
	 * @return  int 		            <0 if KO,
	 *                                  =0 if OK but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 */
	public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		global $langs, $hookmanager;

		$outputlangs = $langs;

		$ret = 0; $deltemp = array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}



	/**
	 * Overloading the loadDataForCustomReports function : returns data to complete the customreport tool
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function loadDataForCustomReports($parameters, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$langs->load("themequarantedeux@themequarantedeux");

		$this->results = array();

		$head = array();
		$h = 0;

		if ($parameters['tabfamily'] == 'hidetopmenu') {
			$head[$h][0] = dol_buildpath('/module/index.php', 1);
			$head[$h][1] = $langs->trans("Home");
			$head[$h][2] = 'home';
			$h++;

			$this->results['title'] = $langs->trans("HideTopMenu");
			$this->results['picto'] = 'hidetopmenu@themequarantedeux';
		}

		$head[$h][0] = 'customreports.php?objecttype='.$parameters['objecttype'].(empty($parameters['tabfamily']) ? '' : '&tabfamily='.$parameters['tabfamily']);
		$head[$h][1] = $langs->trans("CustomReports");
		$head[$h][2] = 'customreports';

		$this->results['head'] = $head;

		return 1;
	}



	/**
	 * Overloading the restrictedArea function : check permission on an object
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int 		      			  	<0 if KO,
	 *                          				=0 if OK but we want to process standard actions too,
	 *  	                            		>0 if OK and we want to replace standard actions.
	 */
	public function restrictedArea($parameters, &$action, $hookmanager)
	{
		global $user;

		if ($parameters['features'] == 'myobject') {
			if ($user->rights->themequarantedeux->myobject->read) {
				$this->results['result'] = 1;
				return 1;
			} else {
				$this->results['result'] = 0;
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Overloading the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printTopRightMenu($parameters)
	{
		global $conf;

		// Hide menu entries that need to be hidden
		if (!empty($conf->global->HIDETOPMENU_ENTRIES)) {
			$menuToHide = explode(';', $conf->global->HIDETOPMENU_ENTRIES);

			$script = '<script>'."\n";
			foreach ($menuToHide as $menu) {
				$script.= 'var entryToHide = document.querySelector(\'#mainmenutd_'.$menu.'\');'."\n";
				$script.= 'if (entryToHide !== null) {'."\n";
				$script.= 'entryToHide.remove()'."\n";
				$script.= '}'."\n";
			}
			$script.= '</script>'."\n";

			print $script;
		}

        // #390
        if ((float) DOL_VERSION >= "20") {
            print "<script>
                $(window).on('load', function () {
                    // Set colaction from right to left
                    const tableSelector = '#id-right > .fiche > form[action*=\"list.php\"] > div.div-table-responsive > table';
                    if ($(tableSelector + ' > thead > tr > *:last-child dl dt a[href=\"#selectedfields\"]').length) {
                        $(tableSelector + ' > thead > tr > td:last-child').attr('align', 'middle'); // Align search icon on the middle
                        $(tableSelector + ' thead > tr, ' + tableSelector + ' tbody > tr').each(function () {
                            $(this).children(':last-child').insertBefore($(this).children(':first-child'));
                        });
                    }

                    // Set burger list anchor from right to left
                    const dropDownUl = $('.dropdown dd ul.selectedfields');
                    if (!dropDownUl.hasClass('selectedfieldsleft')) dropDownUl.addClass('selectedfieldsleft');
                });
            </script>";
        }

		return 0;
	}

    // phpcs:disable
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
		if ($_SERVER["PHP_SELF"] == '/admin/ihm.php') {
			header("Location: ". dol_buildpath('/themequarantedeux/admin/ihm.php', 1));
			exit;
		}

        if(GETPOST('action', 'aZ09') == 'update') {
            require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

            $tabparam = array();
            $menuPos = GETPOST("TC42_POSITION_MENU", 'int');
            if (GETPOST("check_TC42_POSITION_MENU") == "on" && $menuPos > 0) $tabparam["TC42_POSITION_MENU"] = $menuPos;
            else $tabparam["TC42_POSITION_MENU"] = 0;

            dol_set_user_param($db, $conf, $user, $tabparam);

            dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity); // reset cache
        }
	}

    /**
     * Overloading the addSearchEntry function : replacing the parent's function with the one below
     *
     * @param   array()         $parameters     Hook metadatas (context, etc...)
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function addSearchEntry($parameters)
    {
        global $conf;

        if ($conf->browser->layout == 'phone') { //in main.inc.php -> `if ($conf->use_javascript_ajax && !getDolGlobalString('MAIN_USE_OLD_SEARCH_FORM'))` block the usage of select2 in mobile
            $conf->use_javascript_ajax = 1;
            $conf->global->MAIN_USE_OLD_SEARCH_FORM = 0;
        }

        return 0;
    }
    // phpcs:enable
}
