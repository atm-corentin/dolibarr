<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2021 Hugo Allegaert <hugo@code42.fr>
 * Copyright (C) 2023 Ravi Trébuchet <ravi@code42.fr>
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
 * \file    class/actions_customisecard.class.php
 * \ingroup customisecard
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

dol_include_once("/customisecard/class/customcard.class.php");

/**
 * Class Actionscustomisecard
 */
class Actionscustomisecard
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Get the module header for information tab
	 *
	 * @param array $parameters Array of parameter (we only use $parameters['module'] to check module header to display)
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function generateMigrationTabHeader($parameters)
	{
		dol_include_once('/customisecard/lib/customisecard.lib.php');

		// Check if we need to display contrat plus information header
		if ($parameters['module'] == 'modCustomiseCard') {
			$this->results['head'] = customisecardAdminPrepareHead();
			$this->results['active_tab'] = 'migration';
			$this->results['langs'] = 'customisecard@customisecard';
			$this->results['header_icon'] = '';
			return 1;
		}
	}

	/**
	 * Hook formObjectOptions loading at card.php of adherents module
	 *
	 * @param   array         	$parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $user, $db;

		$langs->load("customisecard@customisecard");

		// [#4] Hide description if user wants to
		$hide = false;
		if (in_array('propalcard', explode(':', $parameters['context']))) {
			$hide = ($conf->global->CUSTOMISECARD_HIDE_DESCRIPTION_PROPAL ?? false);
		} else if (in_array('ordercard', explode(':', $parameters['context']))) {
			$hide = ($conf->global->CUSTOMISECARD_HIDE_DESCRIPTION_ORDER ?? false);
		} else if (in_array('invoicecard', explode(':', $parameters['context']))) {
			$hide = ($conf->global->CUSTOMISECARD_HIDE_DESCRIPTION_INVOICE ?? false);
		} else if (in_array('ordersuppliercard', explode(':', $parameters['context']))) {
			$hide = ($conf->global->CUSTOMISECARD_HIDE_DESCRIPTION_SUPPLIER_ORDER ?? false);
		} else if (in_array('invoicesuppliercard', explode(':', $parameters['context']))) {
			$hide = ($conf->global->CUSTOMISECARD_HIDE_DESCRIPTION_SUPPLIER_INVOICE ?? false);
		}

		if ($hide) {
			print "<style>
					#cke_dp_desc, #dp_desc {
						display: none;
					}
					</style>";
		}

		// [#12] Hide limited duration
		if ($conf->global->CUSTOMISECARD_HIDE_LIMITED_DURATION ?? false) {
			print "<style>
					#trlinefordates {
						display: none !important;
					}
					</style>";
		}

		// Advanced search customize
		if ($conf->advancedproductsearch->enabled ?? false) {
			$advancedSearchOptions = array("ref", "label", "stock-reel", "stock-theorique", "buy-price", "subprice", "discount", "finalsubprice", "qty", "unit", "finalprice");
			// For each option, if associated constant is enabled, we hide advanced search column
			foreach ($advancedSearchOptions as $option) {
				$constName = "CUSTOMIZE_CARD_DISABLE_AS_" . strtoupper($option);
				if ($conf->global->$constName ?? false) {
					print "<style>
						.advanced-product-search-col.--" . $option . " {
							display: none;
						}
					</style>";
				}
			}
			print "<style>
						th.advanced-product-search-col.--finalprice {
							text-align: right;
						}
					</style>";
		}

		// [#17] Since v17, table titles are th and not td
		$titleColumnType = (version_compare(version_dolibarr(), "17.0", ">=") ? 'th' : 'td');

		// [#9] Allows user to collapse / uncollapse commerical document information
		if ((in_array(array('propalcard'), explode(':', $parameters['context']))
				|| in_array('ordercard', explode(':', $parameters['context']))
				|| in_array('invoicecard', explode(':', $parameters['context'])))
			&& $action != "create") {
			$card = $_SERVER['PHP_SELF'];
			?>
			<script type="text/javascript">
				$(document).ready(function () {

					// Create table names (to allow collapse/uncollapse)
					let titles = {
						"ComInfo" : "<?php echo $langs->trans('CustomiseCardCommercialsInformation');?>",
						"SellPrice" : "<?php echo $langs->trans('CustomiseCardSellingPrice');?>",
						"Margin" : "<?php echo $langs->trans('CustomiseCardMargin');?>",
						"Elements" : "<?php echo $langs->trans('CustomiseCardDocumentElements');?>",
						"Total" : "<?php echo $langs->trans('CustomiseCardTotalPrice');?>"
					};

					// Change class of tables which are already into a div (object lines AND linked object, events and joint files)
					$('div.div-table-responsive-no-min').each(function (it, div) {
						$div = $(div);
						$div.addClass('customise-card-div-can-be-hide');
						if ($div.parent().find('>table.table-fiche-title').length < 1) {
							$div.parent().prepend("<div id='customisecard-table-without-title-" + it + "'></div>");
							$div.parent().find("div#customisecard-table-without-title-" + it).append($div);
						}
					});

					// Put all document information into a div (Commercials information and price)
					$('div.fiche table.border').not('table table,.ui-dialog,.confirmquestions').each(function (it, table) {
						$table = $(table);
						$table.parent().prepend("<div class='customise-card-div-can-be-hide'></div>");
						$table.parent().find("div.customise-card-div-can-be-hide").append($table);
					});

					// Create title tabs if it doesn't exist and add + / - icon to hide / unhide tables
					$('div.customise-card-div-can-be-hide').each(function (it, div) {
						$div = $(div);

						let titleTab = $div.parent().find('>table.table-fiche-title');

						// Create title tab if it doesn't exist
						if (titleTab.length < 1) {
							// Define table title
							let tableType = "ComInfo";
							let moreClass = "";
							if ($div.find(">table#tablelines").length > 0) {
								tableType = "Elements";
							} else if ($div.find('>table.margintable').length > 0) {
								tableType = "Margin";
								moreClass = "titre";
							} else if ($div.find('>table.paymenttable').length > 0) {
								tableType = "Total";
								moreClass = "titre";
							} else if ($div.find(">table>tbody>tr>td.titlefieldmiddle").length > 0) {
								tableType = "SellPrice";
							}
							// Add title
							$div.parent().prepend(
								'<table class="centpercent notopnoleftnoright table-fiche-title customisecard-table-title" customisecard-table-title="' + it + '">' +
								'<tbody>' +
								'<tr class="' + moreClass + '">' +
								'<td class="nobordernopadding valignmiddle col-title">' +
								'<div class="titre inline-block">' +
								titles[tableType] +
								'</div>' +
								'</td>' +
								'</tr>' +
								'</tbody>' +
								'</table>');
						} else if (titleTab.length === 1) { // If title tab exists, we add class
							titleTab.attr('customisecard-table-title', it);
							titleTab.addClass('customisecard-table-title');
						}
						$div.attr("customisecard-hide-table", it);

						// We update title (with + / - buttons) and we connect it to collapse / save function
						let title = $div.parent().find('table[customisecard-table-title=' + it + ']>tbody>tr>td.col-title>div.titre');
						if (title.length > 0) {
							let content = title.text();
							title.html('');
							title.append('<a href="javascript:tableManagement.hideTable(' + it + ')" class="customisecard-no-link"><span id="customisecard-icon-id-' + it + '" class="fa fa-chevron-up"></span> ' + content + '</a>');
						}
					});

					// Apply user page personalization
					// Get cookies that corresponds to user / different tables
					let collapsArray = [
						<?php
						$first = true;
						for ($i = 0; $i < 8; $i++) {
							if (!empty($_COOKIE['CUSTOMISECARD_COLLAPSE_IDTABLE_' . $i . "-" . $user->id])) {
								if (!$first) {
									echo ',';
								}
								echo '"' . $i . '"';
								$first = false;
							}
						}
						?>
					];

					// For each table to hide, change icon and hide table
					collapsArray.forEach(function(idTable){
						$("#customisecard-icon-id-" + idTable).removeClass('fa-chevron-up');
						$("#customisecard-icon-id-" + idTable).addClass('fa-chevron-down');
						$('div[customisecard-hide-table=' + idTable + ']').addClass("PSHidden");
					});

				});

				const tableManagement = {
					// Hide table with table id
					hideTable: function (idTable) {
						let elem = $('div[customisecard-hide-table=' + idTable + ']');
						// if already hide -> unhide else hide and update cookie thanks to user id and table id
						if (elem.hasClass('PSHidden')) {
							elem.removeClass('PSHidden');
							$("#customisecard-icon-id-" + idTable).removeClass("fa-chevron-down");
							$("#customisecard-icon-id-" + idTable).addClass("fa-chevron-up");
							document.cookie = "CUSTOMISECARD_COLLAPSE_IDTABLE_" + idTable + "-" + <?php echo $user->id;?> + "=0; path=<?php echo $card;?>;";
						} else {
							elem.addClass('PSHidden');
							$("#customisecard-icon-id-" + idTable).removeClass("fa-chevron-up");
							$("#customisecard-icon-id-" + idTable).addClass("fa-chevron-down");
							document.cookie = "CUSTOMISECARD_COLLAPSE_IDTABLE_" + idTable + "-" + <?php echo $user->id;?> + "=1; path=<?php echo $card;?>;";
						}
					},
				}
			</script>
			<?php
		}

		// If globalcard we had the javascript used to custom the view
		if (in_array('globalcard', explode(':', $parameters['context']))) {
			// get current card
			$card = $_SERVER['PHP_SELF'];

			// add exception if we are on societe card who are customer because tab have one more line
			if ($object->element == 'societe' && $action == 'view' && $card == '/societe/card.php') {
				if ($object->client != 0)
					$card .= '&client';
			}

			$langs->load('customisecard@customisecard');

			// Get the card modification saved in db for this card
			$customcard = new Customcard($db);

			// Some actions are customizable (CRUD), so we get 'view' action card if we are not in one of them
			$customCardAction = $action;
			if (!in_array($action, array("edit", "update", "create", "add"))) $customCardAction = 'view';

			$customcard->getCustomView($card, $customCardAction);

			// Get Description number (to edit title / sub title width), depends on MAIN_VIW_LINE_NUMBER
			$descriptionNumber = 0;
			if ($conf->global->MAIN_VIEW_LINE_NUMBER ?? false) {
				$descriptionNumber = 1;
			}

			?>
			<script type="text/javascript">
				// Hide card fiche while processing change
				$('div.fiche').css('visibility', 'hidden');
				$(document).ready(function () {
					<?php
					// Display custom view buttons only if user has permission
					if (!empty($user->rights->customisecard->customcard->create) || !empty($user->admin)) {
						echo "$('#topmenu-login-dropdown').append(\"<div id='personalviewbuttons' class='inline-block'><a title='".$langs->trans('EditView')."' rel='edit' href='javascript:personalview.edit();'><i class='fas fa-low-vision'></i></a><a title='".$langs->trans('Cancel')."' rel='running' href='javascript:personalview.cancel();'><i class='fas fa-power-off'></i></a></div>\");";
						echo "$('#personalviewbuttons [rel=running]').hide();";
					}
					?>

					// If we have modification in the database for this card apply them to the different table
					<?php if (!empty($customcard->cfield || !empty($customcard->cbutton))) { ?>
					// Find all card tab and add css and attribute to identify them
					$('div.fiche:first table.border').not('table table,.ui-dialog,.confirmquestions').each(function (it, table) {
						$table = $(table);
						$table.attr('pview-table', it);
						$table.addClass('PSTable');

						$table.find('>tbody>tr').each(function (i, item) {
							$item = $(item);
							$item.attr('pview-row', i);
						});
					});

					// Find all actions tab and add css and attribute to identify them
					$('div.tabsAction').each(function (it, table) {
						$table = $(table);
						$table.attr('pview-table', it);
						$table.addClass('PSTableActions');

						$table.children().each(function (i, item) {
							$item = $(item);
							$item.attr('pview-row', i);
						});
					});

					// Find all document line and add css and attribute to identify them
					$('#tablelines').each(function (it, table) {
						$table = $(table);
						$table.attr('pview-table', it);
						$table.addClass('PSTableLines');

						$table.find('>thead>tr><?php echo $titleColumnType;?>').each(function (i, item) {
							$item = $(item);
							$item.attr('pview-column', i);
						});

						$table.find('>tbody>tr').each(function (i, item) {
							$item = $(item);
							$item.find('>td').each(function (i, item) {
								$(item).attr('pview-column', i);
							});
						});
					});

						<?php
						// Apply saved modification
						if (!empty($customcard->cfield)) {
							foreach ($customcard->cfield as &$row) {
								$iTable = $row['iTable'];
								$iRow = $row['iRow'];
								$iColumn = $row['iColumn'];

								if ($iRow != "") {
									if (!empty($row['bold'])) echo '$("table[pview-table=' . $iTable . '] tr[pview-row=' . $iRow . ']").addClass("PSBolder");';
									if (!empty($row['hide'])) echo '$("table[pview-table=' . $iTable . '] tr[pview-row=' . $iRow . ']").addClass("PSHidden");';
									if (!empty($row['tooltip'])) echo '$("table[pview-table=' . $iTable . '] tr[pview-row=' . $iRow . ']").addClass("PSTooltip").attr("ps-tooltip","' . dol_escape_js($row['tooltip']) . '");';
									if (!empty($row['title'])) {
										echo '$("table[pview-table=' . $iTable . '] tr[pview-row=' . $iRow . ']").children()[0].textContent = "' . dol_escape_js($row['title']) . '";';
										echo '$("table[pview-table=' . $iTable . '] tr[pview-row=' . $iRow . ']").addClass("PSTitle");';
									}
								}
								if ($iColumn != "") {
									if (!empty($row['bold'])) {
										echo '$("td[pview-column=' . $iColumn . ']").addClass("PSBolder");';
										echo '$("th[pview-column=' . $iColumn . ']").addClass("PSBolder");';
									}
									if (!empty($row['hide'])) {
										echo '$("td[pview-column=' . $iColumn . ']").not("td.linecoledit, td.linecolmove, td.linecoldelete").addClass("PSHidden");';
										echo '$("th[pview-column=' . $iColumn . ']").not("th.linecoledit, th.linecolmove, th.linecoldelete").addClass("PSHidden");';
										echo '$("tr[data-product_type=9] td[pview-column=' . $iColumn . ']").removeClass("PSHidden");';
									}
								}
							}
							// print tooltip
							echo 'personalview.drawTooltip();';
						}

						// Hide button
						if (!empty($customcard->cbutton)) {
							foreach ($customcard->cbutton as &$row) {
								$iButton = $row['iButton'];
								$iRow = $row['iRow'];

								if (!empty($row['hide'])) echo '$("div[pview-table=' . $iButton . '] [pview-row=' . $iRow . ']").addClass("PSHidden");';
							}
						}
					}
					?>
					let descriptionNumber = 0<?php echo $descriptionNumber;?>;
					// Change title / sub title line width
					let visibleColumns = $("#tablelines>thead>tr.liste_titre <?php echo $titleColumnType;?>").not("<?php echo $titleColumnType;?>.PSHidden").length - descriptionNumber;
					// Title lines use total ht column, so if it's hidden, title column has to be smaller
					if ($("#tablelines>thead>tr.liste_titre <?php echo $titleColumnType;?>.linecolht").length > 0) visibleColumns--;
					if ($("#tablelines>thead>tr.liste_titre <?php echo $titleColumnType;?>.linecoltotalht_currency").length > 0) visibleColumns--;
					// If all columns are disabled, we have to enlarge last column
					if (visibleColumns - 3 <= 0) {
						$("td[pview-column=" + descriptionNumber + "]").attr("colspan", 2);
						$("th[pview-column=" + descriptionNumber + "]").attr("colspan", 2);
					}
					$("tr[data-product_type=9] td[pview-column=" + descriptionNumber + "]").attr("colspan", (visibleColumns - 3));
					// Display card fiche
					$('div.fiche').css('visibility', 'visible');
				});

				// List of function to edit and save the change on tab
				const personalview = {

					// Hide/unhide <tr> by iTable = id of table and iRow = id of row
					hide: function (iTable, iRow, iColumn = null) {
						let elem;
						if (iRow != null) {
							elem = $('table[pview-table=' + iTable + '] tr[pview-row=' + iRow + ']');
						} else if (iColumn != null) {
							elem = $('table[pview-table=' + iTable + '] thead tr <?php echo $titleColumnType;?>[pview-column=' + iColumn + ']');
						}
						if (elem !== null) {
							// if already hide -> unhide else hide
							if (elem.hasClass('PSNotReallyHide')) {
								elem.removeClass('PSNotReallyHide');
							} else {
								elem.addClass('PSNotReallyHide');
							}
						}
					},

					// Hide/unhide action button by iTable = id of table and iRow = id of row
					hideButton: function (iTable, iRow) {
						let tr = $('div[pview-table=' + iTable + '] [pview-row=' + iRow + ']');

						// if already hide -> unhide else hide
						if (tr.hasClass('PSNotReallyHide')) {
							tr.removeClass('PSNotReallyHide');
						} else {
							tr.addClass('PSNotReallyHide');
						}
					},

					// Bold/unbold <tr> by iTable = id of table and iRow = id of row
					highLight: function (iTable, iRow, iColumn = null) {
						let elem;
						if (iRow != null) {
							elem = $('table[pview-table=' + iTable + '] tr[pview-row=' + iRow + ']');
						} else if (iColumn != null) {
							elem = $('table[pview-table=' + iTable + '] thead tr <?php echo $titleColumnType;?>[pview-column=' + iColumn + ']');
						}
						if (elem !== null) {
							// if already bold -> unbold else bold
							if (elem.hasClass('PSBolder')) {
								elem.removeClass('PSBolder');
							} else {
								elem.addClass('PSBolder');
							}
						}
					},

					// Draw custom tooltip on tab
					drawTooltip: function () {
						$('tr.PSTooltip').each(function (i, item) {
							let $item = $(item);
							let $td = $(item).children();
							let $tooltip = $('<div class="tooltip"><i class="far fa-question-circle"></i><span class="tooltiptext">'+$item.attr('ps-tooltip')+'</span></div>');
							$td.first().append($tooltip);
						});
					},

					// Create/update tooltip window
					tooltip: function (iTable, iRow) {
						let tr = $('table[pview-table=' + iTable + '] tr[pview-row=' + iRow + ']');
						let texte = tr.attr('ps-tooltip') ? tr.attr('ps-tooltip') : '';

						Swal.fire({
							title: '<?php echo $langs->trans('AddTooltip'); ?>',
							input: 'text',
							inputValue: texte,
							confirmButtonText: '<?php echo $langs->trans('Save'); ?>',
							showDenyButton: true,
							denyButtonText: '<?php echo $langs->trans('DeleteTooltip'); ?>',
							showCancelButton: true,
							cancelButtonText: '<?php echo $langs->trans('Cancel'); ?>',
							showCloseButton: true
						}).then((result) => {
							if (result.isConfirmed === true) {
								// Add class and icon
								tr.attr('ps-tooltip', result.value).addClass('PSTooltip');
								$(tr).children().first().append('<div class="tooltip"><i class="far fa-question-circle"></i></div>');
							} else if (result.isDenied === true) {
								// Delete tooltip class and icon
								tr.removeClass('PSTooltip');
								$(tr).children().first().children().remove()
							}
						});
					},

					// Edit title
					title: function (iTable, iRow) {
						// Get line and title
						let tr = $(`table[pview-table=${iTable}] tr[pview-row=${iRow}]`);
						let td = $(`table[pview-table=${iTable}] tr[pview-row=${iRow}]`).children()[0];

						Swal.fire({
							title: '<?php echo $langs->trans('UpdateTitle'); ?>',
							input: 'text',
							inputValue: td.textContent,
							confirmButtonText: '<?php echo $langs->trans('Save'); ?>',
							showDenyButton: true,
							denyButtonText: '<?php echo $langs->trans('DeleteCustom'); ?>',
							showCancelButton: true,
							cancelButtonText: '<?php echo $langs->trans('Cancel'); ?>',
							showCloseButton: true
						}).then((result) => {
							if (result.isConfirmed === true) {
								td.textContent = result.value;
								tr.addClass('PSTitle');
								tr.addClass('PSTitleEdited');
							} else if (result.isDenied === true) {
								tr.removeClass('PSTitle');
								tr.removeClass('PSTitleEdited');
								this.save();
							}
						});
					},

					// Delete custom class
					remove: function () {
						$("tr.PSNotReallyHide").removeClass("PSNotReallyHide");
						$("tr.PSBolder").removeClass("PSBolder");
						$("tr.PSTitle").removeClass("PSTitle");
						$("tr.PSTooltip").removeClass("PSTooltip");

						this.save('delete');
					},

					// Cancel editing mod
					cancel: function () {
						location.reload();
					},

					// Save configuration
					save: function (caction = '') {
						// Default action = save
						if (!caction) caction = 'save';

						$('table[pview-table] tr').unbind('mouseenter').unbind('mouseleave');
						$('#personalviewbuttons [rel=running]').hide();
						$('#personalviewbuttons [rel=edit]').show();
						$('.PSCanEdit').remove();

						// Re hide what we unhide during edit mode
						$('.PSNotReallyHide').addClass('PSHidden').removeClass('PSNotReallyHide');
						$('.PSTitle').removeClass('PSTitleEdited');

						TRes = [];
						TField = {iTable: [], iButton: []};

						// Store each modification on table into a tab
						$('table[pview-table]').each(function (it, table) {

							$(table).find('tr[pview-row]').each(function (i, item) {
								$item = $(item);

								let row = {iTable: it, iRow: i, iColumn: null, hide: 0, bold: 0, tooltip: '', title: ''};

								if ($item.hasClass('PSBolder')) row.bold = 1;
								if ($item.hasClass('PSHidden')) row.hide = 1;
								if ($item.hasClass('PSTooltip')) row.tooltip = $item.attr('ps-tooltip');
								if ($item.hasClass('PSTitle')) row.title = $item.children()[0].textContent;

								TField.iTable.push(row);
							});

							// Save column changes
							$(table).find('<?php echo $titleColumnType;?>[pview-column]').each(function (i, item) {
								$item = $(item);

								let column = {iTable: it, iRow: null, iColumn: i, hide: 0, bold: 0, tooltip: '', title: ''};

								if ($item.hasClass('PSBolder')) column.bold = 1;
								if ($item.hasClass('PSHidden')) column.hide = 1;

								TField.iTable.push(column);
							});

						});

						// Store each modification on button into a tab
						$('div[pview-table]').each(function (it, table) {

							$(table).find('[pview-row]').each(function (i, item) {
								$item = $(item);

								let row = {iButton: it, iRow: i, hide: 0};

								if ($item.hasClass('PSHidden')) row.hide = 1;

								TField.iButton.push(row);
							});

						});

						TRes.push(TField);

						// Call ajax to save, update or delete the current configuration in database
						$.ajax({
							url: "<?php echo dol_buildpath("/customisecard/ajax/ajax_add_edit_delete_customcard.php", 1) ?>",
							data: {
								action: caction,
								celement: "<?php echo $card ?>",
								caction: "<?php echo $customCardAction;?>",
								cfield: TRes,
								token: '<?php echo newToken();?>'
							},
							method: "POST"
						}).done(function () {
							location.reload();
						});
					},

					// Edit mode
					edit: function () {
						// Unhide previous hidden fields
						$('.PSHidden').addClass('PSNotReallyHide').removeClass('PSHidden');
						$('.PSTitle').addClass('PSTitleEdited');

						// Show top tooltip editing mode
						$('#personalviewbuttons [rel=edit]').hide();
						$('#personalviewbuttons [rel=running]').show();

						<?php
							// alert different customisation needed for societe depending on whether he is a customer or not
						if ($object->element == 'societe' && $action == 'view' && $_SERVER['PHP_SELF'] == '/societe/card.php') {
							echo 'alert("'.dol_string_nohtmltag($langs->trans('AlertCustomSocCard')).'");';
						}
						?>
						// Get all table actions for each of them :
						$('div.tabsAction').each(function (it, table) {
							let $table = $(table);

							// Print upper tab save/delete button
							$table.before('<div class="PSCanEdit"><?php echo '<div class="buttons"><a href="javascript:personalview.save();">' . img_picto($langs->trans('SaveView'), 'save@customisecard') . '</a><a href="javascript:personalview.remove();">' . img_picto($langs->trans('RemoveView'), 'delete@customisecard') . '</a></div>' . $langs->trans('YouCanEditThisActions');
							?></div>');

							if ($table.hasClass('nobordernopadding')) {
								$('table').css({
									'border': '1px #ccc'
								});
							}

							$table.attr('pview-table', it);
							$table.addClass('PSTableActions');

							// For each row buttons of the div :
							$table.children().each(function (i, item) {
								let $item;
								if ($(item).children().length > 0)
									$item = $(item).children();
								else
									$item = $(item);

								$item.attr('pview-row', i);

								let $actions = $('<div class="PSActionsButton" rel="personnal-view-data"></div>');

								// Add edit icon menu
								$actions.append('<a class="PSSmallSpace" rel="hide" href="javascript:personalview.hideButton('+it+','+i+')"><?php echo img_picto($langs->trans('HideOrNot'), 'hide@customisecard'); ?></a>');

								$actions.find('a').attr('pview-row', i);

								// print button while mouse over
								$item.mouseenter(function () {
									$item.append($actions);
								});
								$item.mouseleave(function () {
									$item.find('div.PSActionsButton').remove();
								});
							});
						});

						// Get all table for each of them :
						$('div.fiche:first table.border').not('table table').each(function (it, table) {
							let $table = $(table);

							// Print upper tab save/delete button
							$table.before('<div class="PSCanEdit"><?php echo '<div class="buttons"><a href="javascript:personalview.save();">' . img_picto($langs->trans('SaveView'), 'save@customisecard') . '</a><a href="javascript:personalview.remove();">' . img_picto($langs->trans('RemoveView'), 'delete@customisecard') . '</a></div>' . $langs->trans('YouCanEditThisTable');
							?></div>');

							if ($table.hasClass('nobordernopadding')) {
								$('table').css({
									'border': '1px #ccc'
								});
							}

							$table.attr('pview-table', it);
							$table.addClass('PSTable');

							// For each row <tr> of the table :
							$table.find('>tbody>tr').each(function (i, item) {
								let $item = $(item);
								$item.attr('pview-row', i);

								let $actions = $('<div class="PSActions" rel="personnal-view-data"></div>');

								// Add edit icon menu
								$actions.append('<a class="PSSmallSpace" rel="hide" href="javascript:personalview.hide('+it+','+i+')"><?php echo img_picto($langs->trans('HideOrNot'), 'hide@customisecard'); ?></a>');
								$actions.append('<a class="PSSmallSpace" href="javascript:personalview.highLight('+it+','+i+')"><?php echo img_picto($langs->trans('HighLight'), 'bold@customisecard'); ?></a>');
								$actions.append('<a class="PSSmallSpace" href="javascript:personalview.tooltip('+it+','+i+')"><?php echo img_picto($langs->trans('Tooltip'), 'tooltip@customisecard'); ?></a>');
								$actions.append('<a class="PSBigSpace" href="javascript:personalview.title('+it+','+i+')"><?php echo img_picto($langs->trans('EditTitle'), 'edit@customisecard'); ?></a>');

								$actions.find('a').attr('pview-row', i);

								// print button while mouse over
								$item.mouseenter(function () {
									$item.find('td').first().append($actions);
								});
								$item.mouseleave(function () {
									$item.find('div.PSActions').remove();
								});
							});

						});

						// Document lines edit
						$('#tablelines').each(function (it, table) {
							let $table = $(table);

							// Print upper tab save/delete button
							$table.before('<div class="PSCanEdit"><?php echo '<div class="buttons"><a href="javascript:personalview.save();">' . img_picto($langs->trans('SaveView'), 'save@customisecard') . '</a><a href="javascript:personalview.remove();">' . img_picto($langs->trans('RemoveView'), 'delete@customisecard') . '</a></div>' . $langs->trans('YouCanEditThisTable');
							?></div>');

							if ($table.hasClass('nobordernopadding')) {
								$('table').css({
									'border': '1px #ccc'
								});
							}

							$table.attr('pview-table', it);
							$table.addClass('PSTableLines');

							// For each column thead > tr > td of the table :
							$table.find('>thead>tr><?php echo $titleColumnType;?>').each(function (i, item) {
								let $item = $(item);
								$item.attr('pview-column', i);

								let $actions = $('<div class="PSActionsLines" rel="personnal-view-data"></div>');

								// Add edit icon menu
								$actions.append('<a class="PSSmallSpace" rel="hide" href="javascript:personalview.hide(' + it + ', null, ' + i + ')"><?php echo img_picto($langs->trans('HideOrNot'), 'hide@customisecard'); ?></a>');
								$actions.append('<a class="PSSmallSpace" href="javascript:personalview.highLight(' + it + ', null, ' + i + ')"><?php echo img_picto($langs->trans('HighLight'), 'bold@customisecard'); ?></a>');

								$actions.find('a').attr('pview-column', i);

								// print button while mouse over
								$item.mouseenter(function () {
									$item.append($actions);
								});
								$item.mouseleave(function () {
									$item.find('div.PSActionsLines').remove();
								});
							});

						});

						// Ajust columns to be responsive on edit
						let descriptionNumber = 0<?php echo $descriptionNumber;?>;
						let totalColumns = $("#tablelines>thead>tr.liste_titre td").length - descriptionNumber;
						$("td[pview-column=" + (totalColumns - 1) + "]").attr("colspan", 1);
						$("tr[data-product_type=9] td[pview-column=" + descriptionNumber + "]").attr("colspan", (totalColumns - 4));
					}
				}
			</script>
			<?php
		}
	}
}
