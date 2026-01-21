<?php

/* Copyright (C) 2022 Fabien FERNANDES ALVES <fabien@code42.fr>
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
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) {  define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIRESOC')) {   define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {    define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) { define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {        define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {  define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {  define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {  define('NOREQUIREAJAX', '1');
}

/**
 * \file    h2g2/js/default_list.js.php
 * \ingroup h2g2
 * \brief   Generic javaScript file to manage dolibarr listing.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) { $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--;
}
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) { $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
}
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/../main.inc.php")) { $res=@include substr($tmp, 0, ($i+1))."/../main.inc.php";
}
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) { $res=@include "../../main.inc.php";
}
if (! $res && file_exists("../../../main.inc.php")) { $res=@include "../../../main.inc.php";
}
if (! $res) { die("Include of main fails");
}

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) { header('Cache-Control: max-age=3600, public, must-revalidate');
} else { header('Cache-Control: no-cache');
}

global $langs, $db;

$langs->load('h2g2@h2g2');

?>

let perPage;
let page = 1;
let sortfield;
let sortorder;
let context;
let filters = {};

/**
 * Get the up icon for filter elem
 *
 * @returns {HTMLSpanElement}
 */
const getUpIcon = () => {
	const upIcon = document.createElement('span');
	upIcon.classList.add('nowrap');
	upIcon.id = 'sortIcon';
	upIcon.innerHTML = '<span class="fas fa-caret-up imgup paddingleft" style="" title="Z-A"></span>';

	return upIcon;
}

/**
 * Get the down icon for filter elem
 *
 * @returns {HTMLSpanElement}
 */
const getDownIcon = () => {
	const downIcon = document.createElement('span');
	downIcon.classList.add('nowrap');
	downIcon.id = 'sortIcon';
	downIcon.innerHTML = '<span class="fas fa-caret-down imgdown paddingleft" style="" title="A-Z"></span>';

	return downIcon;
}

/**
 * Empty the table of each tr with class oddeven
 *
 * @param    table         Table to empty
 */
const emptyTable = (table) => {
	const tbody = table.querySelector('tbody');
	if (tbody) {
		const rows = tbody.querySelectorAll('tr.oddeven');
		rows.forEach(row => tbody.removeChild(row));
	}

	// Hide non visible elements
	hideMassactionElements();
}

/**
 * Display the loader on the page
 */
const displayLoader = () => {
	const loader = document.querySelector('#loaderContainer');
	if (loader) {
		loader.style.visibility = 'visible';
		loader.style.display = '';
	}
}

/**
 * Hide the loader on the page
 */
const hideLoader = () => {
	const loader = document.querySelector('#loaderContainer');
	if (loader) {
		loader.style.visibility = 'hidden';
		loader.style.display = 'none';
	}
}

/**
 * Fill table with all values
 *
 * @param values            Data to fill
 * @param table                Table element
 */
const fillTable = (values, table) => {
	// Get columns to display
	const columns = table.querySelectorAll('tr.liste_titre th');
	let keyList = [];
	columns.forEach(column => {
		if (column.dataset.key !== undefined) { // Object fields
			keyList.push(column.dataset.key);
		} else if (column.dataset.titlekey !== undefined) { // Extrafields
			keyList.push('options_' + column.dataset.titlekey);
		}
	})

	// Display datas on table
	values.forEach((value, idx) => {
		const tr = document.createElement('tr');
		tr.classList.add('oddeven');
		tr.id = 'line_' + value.id;

		keyList.forEach(key => {
			const td = document.createElement('td');
			td.innerHTML = value[key];
			tr.appendChild(td);
		})

		// Add the td of action
		const td = document.createElement('td');
		td.classList.add('center');
		td.classList.add('nowrap');
		if (value.custom_btn_action) {
			td.innerHTML = value.custom_btn_action;
		} else if (typeof initCheckForSelect !== 'undefined') { // We check if the function initCheckForSelect exists
			td.innerHTML = '<input id="cb'+ value.id + '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' + value.id + '">';
		}
		tr.appendChild(td);

		table.querySelector('tbody').appendChild(tr);
	})

	// Add check listener on each action checkbox
	jQuery('.checkforselect').click(function() {
		initCheckForSelect(1, 'massaction', 'checkforselect');
	});

	// Refresh the tooltips @see lib_foot.js.php
	jQuery(".classfortooltip").tooltip({
		show: { collision: "flipfit", effect:'toggle', delay:50 },
		hide: { delay: 250 },
		tooltipClass: "mytooltip",
		content: function () {
			return $(this).prop('title');        /* To force to get title as is */
		}
	});
}

/**
 * Fill all page datas (table, pagination, total, ...)
 *
 * @param     data            Data from ajax request
 * @param     table            Table element
 */
const fillDatas = (data, table) => {
	// Fill total
	const total = document.querySelector('#listTotal');
	if (total) {
		total.innerHTML = '(' + data.total + ')';
	}

	// Fill pages
	const currentpage = document.querySelector('#currentpage');
	const previouspage = document.querySelector('#previouspage');
	const nextpage = document.querySelector('#nextpage');
	const lastpage = document.querySelector('#lastpage');
	currentpage.innerHTML =  data.page;
	lastpage.innerHTML =  data.lastPage;
	previouspage.dataset.prev = data.prevPage;
	nextpage.dataset.next = data.nextPage;

	// Fill table
	fillTable(data.values, table);
}

/**
 * Add a listener to hold to select multiple lines.
 * This method will be called by the fetchDatas method
 *
 * @param table                Table element
 */
const holdSelectMassactions = (table) => {
	$( "tbody" ).selectable({
		filter: "tr",
		delay: 500,
		start: () => {
			table.style.cursor = 'crosshair';
		},
		stop: () => {
			table.style.cursor = 'unset';
			const selectedRows = document.querySelectorAll('tr.oddeven.ui-selected');
			if (selectedRows) {
				selectedRows.forEach(elem => {
					const cb = elem.querySelector('input[name="toselect[]"]');
					if (cb && !cb.checked) {
						cb.click();
						cb.checked = true;
					}
				})
			}
		}
	});
	const rows = table.querySelectorAll('tr.oddeven');

	if (rows) {
		// Select a row when we click on the line
		rows.forEach(row => {
			row.addEventListener('click', (e) => {
				const cb = row.querySelector('input[name="toselect[]"]');
				if (cb && e.target !== cb) {
					cb.click();
				}
			})
		})
	}
}

/**
 * Fetch datas and fill the table
 *
 * @param table                Table element
 */
const fetchDatas = (table) => {
	// Empty the table from value
	emptyTable(table);

	// Display the loader
	displayLoader();

	// Get new datas
	if (table.dataset.ajax) {
		$.ajax({
			url: table.dataset.ajax,
			type: 'GET',
			data: {
				token: '<?php echo newToken();?>',
				perPage,
				page,
				sortfield,
				sortorder,
				filters: encodeURI(JSON.stringify(filters)),
				context
			},
			success: function (data) {
				data = JSON.parse(data);

				// Hide the loader
				hideLoader();

				if (data.success) {
					fillDatas(data, table);
					holdSelectMassactions(table);
				}
			}
		});
	} else {
		console.error('Missing data-ajax parameter on the table');
	}
}

/**
 * Add a listener to the per page change
 *
 * @param     table            Table element
 */
const addSelectPerPageListener = (table) => {
	const select = document.querySelector('#selectPerPage');

	if (select) {
		perPage = select.value;
		select.addEventListener('change', event => {
			perPage = event.target.value;
			fetchDatas(table);
		})
	}
}

/**
 * Add click listener for the pagination button
 *
 * @param     table            Table element
 */
const addPaginationBtnListener = (table) => {
	const previouspage = document.querySelector('#previouspage');
	const nextpage = document.querySelector('#nextpage');
	const totalpage = document.querySelector('#lastpage');

	if (previouspage) {
		previouspage.addEventListener('click', event => {
			event.preventDefault();
			const prev = previouspage.getAttribute('data-prev');
			if (prev > 0 && prev !== page) {
				page = prev;
				fetchDatas(table);
			}
		})
	}

	if (nextpage) {
		nextpage.addEventListener('click', event => {
			event.preventDefault();
			const next = nextpage.getAttribute('data-next');
			if (next > 0 && next < totalpage && next !== page) {
				page = next;
				fetchDatas(table);
			}
		})
	}
}

/**
 * Add listener on filter links
 *
 * @param table                Table element
 */
const addFilterBtnListener = (table) => {
	const container = table.querySelector('#filterFieldsContainer');

	if (container) {
		const links = container.querySelectorAll('th a.reposition'); // Select all links except the last one (actions)

		links.forEach(link => {
			link.addEventListener('click', e => {
				e.preventDefault();

				// Change page
				page = 1;

				const parent = e.target.parentNode;
				const parentDataset = parent.dataset;

				// Remove the sorticon
				const sortIcon = table.querySelector('#sortIcon');
				if (sortIcon) {
					sortIcon.parentNode.classList.remove('liste_titre_sel');
					sortIcon.parentNode.classList.add('liste_titre');
					sortIcon.parentNode.removeChild(sortIcon);
				}

				let newSortfield = '';

				if (parentDataset.key) { // Classic fields
					newSortfield = 't.' + parentDataset.key;
				} else if (parentDataset.titlekey) { // Extrafields
					newSortfield = 'ef.' + parentDataset.titlekey;
				}

				if (sortfield === newSortfield) { // Already selected sortfield so we inverse the order
					if (sortorder === "ASC") {
						sortorder = "DESC";
						parent.appendChild(getDownIcon());
					} else {
						sortorder = "ASC";
						parent.appendChild(getUpIcon());
					}
				} else { // By default, order is ASC
					sortorder = "ASC";
					parent.appendChild(getUpIcon());
				}

				// Set the link as active for sel
				parent.classList.add('liste_titre_sel');
				parent.classList.remove('liste_titre');

				// Set the new sortfield and sortorder
				sortfield = newSortfield;

				fetchDatas(table);
			});
		})
	}
}

/**
 * Fill filters from table input
 *
 * @param     table            Table element
 */
const fillFilters = (table) => {
	const container = table.querySelector('#searchFieldsContainer');
	filters = {};

	if (container) {
		const columns = container.querySelectorAll('td.liste_titre');

		columns.forEach(column => {
			// Get the input
			const inputs = column.querySelectorAll('input');
			if (inputs.length > 0) {
				inputs.forEach(input => {
					if (input) {
						// get multiselect
						if (input.classList.contains('select2-search__field')) {
							let multiselect = column.querySelector('select');
							if ($(multiselect).select2('data').length > 0) {
								let selectedOptions = $(multiselect).select2('data').map((opt) => opt.id);
								filters[multiselect.id] = {'value': selectedOptions, 'operator': 'IN'}
							}
						} else if (input.value !== '') {
							if (input.type === 'date') {
								let operator = (input.dataset['operator'] ? input.dataset['operator'] : '=')
								if (filters[input.name]) {
									filters[input.name].push({'value': input.value, 'operator': operator});
								} else {
									filters[input.name] = [{'value': input.value, 'operator': operator}];
								}
							} else {
								filters[input.name] = {'value': input.value, 'operator': 'LIKE'};
							}
						}
					}
				});
			} else {
				const select = column.querySelector('select');
				if (select) {
					if (!select.classList.contains('select-include-zero') && select.value > 0) {
						filters[select.name] = {'value': select.value, 'operator': '='};
					} else if (select.classList.contains('select-include-zero') && select.value >= 0) {
						filters[select.name] = {'value': select.value, 'operator': '='};
					} else {
						delete filters[select.name];
					}
				}
			}
		});
	}
}

/**
 * Remove all filters
 *
 * @param     table            Table element
 */
const emptyFilters = (table) => {
	filters = {};

	const container = table.querySelector('#searchFieldsContainer');
	if (container) {
		const columns = container.querySelectorAll('td.liste_titre');

		columns.forEach(column => {
			// Get the input
			const inputs = column.querySelectorAll('input');
			if (inputs.length > 0) {
				inputs.forEach(input => {
					input.value = '';
				})
			} else {
				// Get the select if input not found
				const select = column.querySelector('select');
				if (select) {
					select.value = '-1';

					if (select.hasAttribute('data-select2-id')) { // We need to update select 2 input
						$('#' + select.id).trigger('change.select2');
					}
				}
			}
		});
	}
}

/**
 * Add listener for search and remove filter buttons
 *
 * @param     table            Table element
 */
const addSearchBtnListener = (table) => {
	const search = table.querySelector('.button_search[name=button_search_x]');
	if (search) {
		search.addEventListener('click', e => {
			e.preventDefault();
			page = 1;
			fillFilters(table);
			fetchDatas(table);
		})
	}

	const removeFilter = table.querySelector('.button_removefilter[name=button_removefilter_x]');
	if (removeFilter) {
		removeFilter.addEventListener('click', e => {
			e.preventDefault();
			page = 1;
			emptyFilters(table);
			fetchDatas(table);
		})
	}
}

/**
 * Hide massactions related elements
 */
const hideMassactionElements = () => {
	jQuery(".massaction").hide();
	jQuery(".massactionother").hide();
}

/**
 * Execute a massaction in batch mode
 *
 * @param action            Action to execute
 * @param selected            List of selected ids
 * @param table                Table element
 * @param additionalData    Additional data to send to the server
 */
const executeMassaction = (action, selected, table, additionalData) => {
	const perBatch = 5; // Number of items sent per batch
	let counter = 0;
	const batchTotal = Math.ceil(selected.length / perBatch); // Total of batch that will be sent
	let batchInError = [];
	let batchInSuccess = [];

	// Load the requests that will be sent
	const promises = [];

	// Create batch of "perBatch" items
	for (let offset = 0; offset < selected.length; offset += perBatch) {
		let batchSelected = [];
		for (let i = 0; i < perBatch; i++) {
			if (selected[i + offset] !== undefined) {
				batchSelected.push(selected[i + offset]);
			} else {
				break;
			}
		}

		// Save the request to execute
		promises.push(new Promise((resolve, reject) => {
			$.ajax({
				url: table.dataset.ajaxMassaction,
				 type: 'POST',
				data: {
					token: '<?php echo newToken();?>',
					action,
					selected: batchSelected,
					...additionalData
				},
				success: function (data) {
					try {
						data = JSON.parse(data);
						if (data && data.success) {
							counter += perBatch;
							document.querySelector("#swal2-content").innerHTML = '<?php echo dol_escape_js($langs->trans('PleaseWait')) ?>' + ` (${counter}/${selected.length})` +
								`<br/><progress id="massactionProgress" value="${counter}" max="${selected.length}"></progress>`;
							if (data.selected) {
								batchInSuccess.push(data.selected);
							}
							resolve(data);
						} else {
							if (data.selected) {
								batchInError.push(data.selected);
							}
							resolve(data);
						}
					} catch (e) {
						reject(e.message);
					}
				},
				error: function (request, status, error) {
					reject(error);
				}
			});
		}))
	}

	// Display the waiting modal
	Swal.fire({
		title: '<?php echo dol_escape_js($langs->trans('ExecutingMassaction')) ?>',
		html: '<?php echo dol_escape_js($langs->trans('PleaseWait')) ?>' +` (${counter}/${selected.length})`+
		`<br/><progress id="massactionProgress" value="${counter}" max="${selected.length}">(${counter}/${selected.length})</progress>`,
		icon: 'info',
		showConfirmButton: false,
		allowOutsideClick: false,
		willOpen: () => {
			Swal.showLoading();

			// Prevent for user to quit the page while loading
			window.onbeforeunload = function() {
				return true;
			};
		}
	}).then(
		Promise.all(promises).then(res => {
			// Remove the function that prevent user to quit the page
			window.onbeforeunload = function () {};
			const nbBatchError = batchInError.length;
			const nbBatchSuccess = batchInSuccess.length;
			let content = document.createElement('div');
			content.style.display = 'flex';
			content.style.justifyContent = 'center';
			let p = document.createElement('table');
			p.style.width = '60%';
			p.innerHTML = '';
			p.innerHTML+= `<tr><td style="text-align: left"><?php echo dol_escape_js($langs->trans('MassactionTotalSelected')) ?></td><td>${selected.length}</td></tr>`;
			p.innerHTML+= `<tr><td style="text-align: left"><?php echo dol_escape_js($langs->trans('MassactionTotalPerBatch')) ?></td><td>${perBatch}</td></tr>`;
			p.innerHTML+= `<tr><td style="text-align: left"><?php echo dol_escape_js($langs->trans('MassactionTotalBatch')) ?></td><td>${batchTotal}</td></tr>`;
			p.innerHTML+= `<tr><td style="text-align: left"><?php echo dol_escape_js($langs->trans('MassactionTotalBatchSuccess')) ?></td><td><span style="color: green">${nbBatchSuccess}</span></td></tr>`;
			p.innerHTML+= `<tr><td style="text-align: left"><?php echo dol_escape_js($langs->trans('MassactionTotalBatchError')) ?></td><td><span style="color: red">${nbBatchError}</span></td></tr>`;
			content.appendChild(p);

			// Create the history event in dolibarr
			$.ajax({
				url: '<?php echo dol_buildpath('/h2g2/ajax/ajax_create_massaction_event.php', 1)?>',
				type: 'POST',
				data: {
					token: '<?php echo newToken();?>',
					action,
					url: window.location.href,
					nbSelected: selected.length,
					nbBatch: batchTotal,
					perBatch,
					batchInError,
					batchInSuccess,
					nbBatchError,
					nbBatchSuccess
				},
				success: function (data) {
					data = JSON.parse(data);
				}
			});

			Swal.fire({
				title: '<?php echo dol_escape_js($langs->trans('MassactionBatchResume')) ?>',
				icon: 'info',
				html: content,
				allowOutsideClick: false
			}).then(() => {
				window.location.reload();
			});
		}).catch(res => {
			// Remove the function that prevent user to quit the page
			window.onbeforeunload = function () {};
			Swal.fire({
				title: '<?php echo dol_escape_js($langs->trans('MassactionInternalError')) ?>',
				icon: 'error',
				html: res
			})
		})
	)
}

/**
 * Initialize checkbox for massactions
 *
 * @param table                Table element
 */
const initMassactions = (table) => {
	const btn = document.querySelector('[name=confirmmassaction]');

	if (btn) {
		btn.addEventListener('click', e => {
			if (table.dataset.ajaxMassaction) {
				e.preventDefault();
				// Get the action
				const select = document.querySelector('#massaction');
				 if (select && select.value) {
					const action = select.value;

					// Get checked lines
					const selected = [];
					const checkedElems = table.querySelectorAll('.checkforselect[id^=cb]:checked'); // Get all checked checkbox with class checkforselect starting with id cb...
					 if (checkedElems) {
						 checkedElems.forEach(elem => {
							 selected.push(elem.value);
						 })
					 }

					 if (selected.length > 0) {
						 // Get the option to check if we need to confirm the action
						 const withPopup = select.querySelector('option[value="' + action + '"]').dataset.withpopup === '1';
						 if (withPopup) { // Dispatch an event to display the popup
							 const event = new CustomEvent('massactionBeforeExecute', {
								 'detail': {
									 action,
									 selected,
									 table,
									 callback: (action, selected, table, additionalData = {}) => executeMassaction(action, selected, table, additionalData)
								 }
							 });
							 document.dispatchEvent(event);
						 } else { // Execute the massaction
							 executeMassaction(action, selected, table);
						 }
					 } else {
						 console.log('No line selected to execute the action')
					 }
				 } else {
					 console.log('No action for the massaction selected');
				 }
			} else {
				console.error('Missing data-ajax-massaction parameter on the table');
			}
		})
	}

	// Hide non visible elements
	hideMassactionElements();
}

/**
 * Prefill search input with GET parameter values.
 */
const prefillSearchInput = () => {
	// Retrieve all GET parameters
	var queryDict = {}
	location.search.substring(1).split("&").forEach(function(item) {queryDict[item.split("=")[0]] = item.split("=")[1]})

	// Loop through each GET parameters to prefill search input
	Object.keys(queryDict).forEach(key => {
		if (key) {
			const input = document.querySelector('#searchFieldsContainer [name="' + key + '"]')

			if (input) {
				input.value = queryDict[key]
			}
		}
	})

	// Prefill sortfield and sortorder if sortfield is available
	if (queryDict['sortfield'] !== undefined) {
		sortfield = queryDict['sortfield'];
		sortorder = (queryDict['sortorder'] !== undefined ? queryDict['sortorder'] : 'ASC');

		const elem = document.querySelector('#filterFieldsContainer [data-key="' + sortfield + '"]')
		if (elem) {
			elem.classList.add('liste_titre_sel');
			elem.classList.remove('liste_titre');
			if (sortorder === 'ASC') {
				elem.appendChild(getUpIcon());
			} else {
				elem.appendChild(getDownIcon());
			}
		}
	} else {
		// Get the sortfield with data-sortfield attribute
		const elem = document.querySelector('#filterFieldsContainer [data-sortfield="true"]')
		if (elem) {
			sortfield = elem.dataset.key;
			sortorder = elem.dataset.sortorder;
			elem.classList.add('liste_titre_sel');
			elem.classList.remove('liste_titre');
			if (sortorder === 'ASC') {
				elem.appendChild(getUpIcon());
			} else {
				elem.appendChild(getDownIcon());
			}
		}
	}
}

$(document).ready(function() {
	const table = document.querySelector('#defaultlist');

	if (table) {
		const multiselect = document.querySelectorAll('.multiselect');
		multiselect.forEach((select) => { $(select).select2();})

		context = document.querySelector('input[name=contextpage]').value; // Init the context
		prefillSearchInput(); // Prefill search inputs if there is GET parameter
		addSelectPerPageListener(table); // Add action for the per page selector
		addPaginationBtnListener(table); // Add action for page navigator buttons
		addSearchBtnListener(table); // Add action for the search button
		addFilterBtnListener(table); // Add action for the filter links
		initMassactions(table); // Init massaction
		fillFilters(table); // Fill default filters
		fetchDatas(table); // Fetch datas
	}
});
