<?php
/* Copyright (C) 2018 SuperAdmin
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
 *
 * Library javascript to enable Browser notifications
 */
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];$tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/../main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");


// Define js type
header('Content-Type: application/javascript');
header('Cache-Control: no-cache'); //no cache so we reset css each times

require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
dol_include_once('/supersearch/lib/supersearch.lib.php');

global $langs, $conf, $db, $dolibarr_main_instance_unique_id, $dolibarr_main_cookie_cryptkey, $mc, $user;
$langs->load("supersearch@supersearch");

$dolUID = $dolibarr_main_instance_unique_id ? : $dolibarr_main_cookie_cryptkey;
$mainFeaturesLevel = dolibarr_get_const($db, 'MAIN_FEATURES_LEVEL', 0);
$supersearchURL = dolibarr_get_const($db, 'SUPERSEARCH_URL_MEILISEARCH');
$supersearchAdminKey = dolibarr_get_const($db, 'SUPERSEARCH_ADMINKEY');
$supersearchAPIKey = dolibarr_get_const($db, 'SUPERSEARCH_APIKEY');

$content = '';
$arrayofentities = array();
$interventionplusEnabled = false;

if (!empty($supersearchAdminKey) && !empty($supersearchURL)) {
	$content = '<div id="loading-indicator" style="display: none"><div class="supersearch-loader"></div></div>
				<div style="display: none" class="supersearch-container">
					<div id="supersearch-results-container"></div>
				</div>';
} else {
	$content = '<p style="color: darkred; font-weight: bold">' . $langs->trans('SuperSearchMasterKeyNeedToBeSet') . '</p>';
	$content .= '<a href="' . dol_buildpath('/supersearch/admin/setup.php', 1) . '">' . $langs->trans('SuperSearchSetupPage') . '</a>';
}

$active = false;
if ($user->rights->supersearch->access == 1) {
	$active = true;
}

$multicompanyEnabled = false;
if (!empty($conf->multicompany->enabled) && $conf->multicompany->enabled) {
	$multicompanyEnabled = true;
	$arrayofentities = $mc->getEntitiesList();

	if ($conf->interventionplus->enabled) { // #42
		$interventionplusEnabled = true;
		dol_include_once('supercotrolia/class/entityparam.class.php');
		$entities = implode(',', EntityParam::getEntitiesControlledByThePowerEntity());
		$filter = 'entity IN [' . $entities . ']';
	} else {
		$filter = 'entity = ' . $conf->entity;
	}
} else $filter = 'entity = 1';


$tooltipText = $langs->trans('SuperSearchTooltipPopup');

$hitsPerPage = dolibarr_get_const($db, 'SUPERSEARCH_HITS_PER_PAGE');
$charsBeforeSearch = dolibarr_get_const($db, 'SUPERSEARCH_CHARS_BEFORE_SEARCH');

if (empty($hitsPerPage)) $hitsPerPage = 6;
if (empty($charsBeforeSearch)) $charsBeforeSearch = 3;

$picto = dol_buildpath('/custom/supersearch/img/object_supersearch.png', 1);
$indexCustomization = getIndexCustomization();
$indexCustomization = json_encode($indexCustomization);

dol_include_once('/supersearch/core/modules/modSuperSearch.class.php');
$modSuperSearch = new modSuperSearch($db);
$version = 'v.' . $modSuperSearch->getVersion();
?>
var active = false;
const baseUrl = '<?php echo DOL_MAIN_URL_ROOT; ?>';
const dolUID = '<?php echo $dolUID; ?>_';
const hitsPerPage = <?php echo $hitsPerPage; ?>;
const charsBeforeSearch = <?php echo $charsBeforeSearch; ?>;

var objectTemplate = <?php echo $indexCustomization; ?>;
var mySearch; // Global variable to store the current search instance.
var indexes; // Global variable to store the list of indexes retrieved from MeiliSearch.

const labelFields = ['ref', 'title', 'name', 'nom', 'lastname'];
const excludedFields = ['rowid', 'content'];

var arrayofEntities = <?php echo json_encode($arrayofentities); ?>;
var interventionplusEnabled = <?php echo json_encode($interventionplusEnabled); ?>;

if (`<?php echo $multicompanyEnabled; ?>` == false) {
	excludedFields.push('entity');
}
if (`<?php echo $active; ?>` == true) {
	active = true;
}

/**
 * Filters fields from a 'hit' object, excluding certain fields,
 * and returns an HTML string of the remaining values.
 *
 * @param {Object} hit - The object containing the data to filter.
 * @returns {String} - An HTML string with the filtered fields.
 */
function contentFilter(hit, indexName, label) {
	let arrayLength = Object.keys(arrayofEntities).length;
	return Object.keys(hit)
		.filter(key => filterTreatment(key, indexName, hit, label))
		.map((key) => {
			if (key == 'entity' && arrayLength > 0) return `<span class="multicompany-entity-card-container" title="entity"><span class="fa fa-globe"></span> ${arrayofEntities[hit['entity']]}</span>`;
			else return `<span title="${key}">${hit[key]}</span>`;
		})
		.join(' - ');
}


/**
 * Filters treatment fields based on the given index name and criteria.
 * Determines whether a field should be included or excluded based on predefined conditions.
 *
 * @param {string} key - The key representing the field to be evaluated.
 * @param {string} indexName - The name of the index, used for applying specific filtering rules.
 * @param {object} hit - The object containing the fields and their corresponding values.
 * @return {boolean} Returns true if the field meets the criteria for inclusion, otherwise false.
 */
function filterTreatment(key, indexName, hit, label) {
	if (indexName == "Article") return !key.startsWith('_') && !labelFields.includes(key) && !excludedFields.includes(key) && hit[key]
	else return !key.startsWith('_') && !excludedFields.includes(key) && hit[key] && (hit[key] !== label)
}

/**
 * Generates an HTML template for displaying a search result item.
 *
 * @param {Object} hit - The search result object containing data to be displayed.
 *                       Each key-value pair in this object represents a field and its value.
 * @param {string} indexName - The name of the index from which the search result originates.
 *                             This is used to generate the appropriate URL and label.
 * @param {string} status - The actual 'status' of the research
 *
 * @return {string} - Returns a string containing an HTML snippet that includes the content
 *                    from the search result and a link to the full item.
 */
function generateTemplate(hit, indexName, status) {
	indexName = indexName.replace(dolUID, '');
	// Generate the URL template based on the index name
	let template = objectTemplate[indexName];

	// Construct the full URL by combining the base URL, the template, and the row ID from the hit object
	let url = `${baseUrl + template.url}${hit.rowid}`;

	let label = hit[labelFields.find(key => hit[key])];

	// Filter out any keys that start with an underscore and join the remaining key values into a single string
	let content = ((indexName == 'Fichinter' && interventionplusEnabled) ? '' : contentFilter(hit, indexName, label));

	let item = `<a class="hit-item" href='${url}' data-href='${url}' data-color="${template.color}">
					<span class="${template.picto} hit-icon" style="color: ${template.color} !important;"></span>
					<div>
						<span title="${labelFields.find(key => hit[key])}" class="hit-label one-line">${label}</span>
						<div class="hit-content one-line rowid-${hit['rowid']} ${indexName}">${content}`;

	if (indexName == 'Fichinter' && interventionplusEnabled) {
		if (status == 'idle') { // if the search isn't finish -> don't render the item
			$.ajax({
				url: '<?php echo dol_buildpath('/supersearch/ajax/get_intervention_plus.php', 1) ?>',
				type: 'GET',
				data: { 'id' : hit.rowid},
				success: function(response) {
					response = JSON.parse(response);
					var content = []; // to have a clean 'join()'
					content.push(`<span class="multicompany-entity-card-container" title="entity"><span class="fa fa-globe"></span> ${arrayofEntities[hit['entity']]}</span>`);
					if (response.pole) content.push(`<span title="pole">${response.pole}</span>`);
					if (response.brand) content.push(`<span title="brand">${response.brand}</span>`);
					let status = `<span title="status" style="float: right">${response.status}</span>`;

					$(`div.hit-content.rowid-${hit['rowid']}.${indexName}`).html(`${content.join(' - ')} ${status}`);
				}
			});
		}
	}
	item += `</div></div></a>`;
	return item;
}


/**
 * Initializes the search functionality based on the specified index name.
 *
 * @param {string} indexName - The name of the index to search. This can be a specific index name
 *                             or 'Multi-search' to perform a search across multiple indexes.
 *                             If no indexName is provided, 'Multi-search' is used by default.
 *
 * @return {void}
 */
function initializeSearch(indexName = 'Multi-search') {

	// Create a search client using the instantMeiliSearch method with the search URL and API key.
	const searchClient = instantMeiliSearch('<?php echo $supersearchURL;?>', '<?php echo $supersearchAPIKey;?>').searchClient;

	// Dispose of any existing search instance if it exists.
	if (mySearch) mySearch.dispose();

	$('#supersearch-results-container').empty();
		// Loop through the fetched indexes and create a result container for each index.
	indexes.forEach(({uid}) => {
		$('#supersearch-results-container').append(`<div>
														<div id='${uid}-hits'></div>
													</div>`);
	});

	setupMultiSearch(searchClient);

	// Now listen for the `render` event on the `mySearch` instance, not on the searchClient.
	mySearch.on('render', () => {
		// retrieve the href from the child <div> of the <li> and apply it to the <li> on click
		$('.supersearch-results-item').each(function () {
			if ($(this).children().data()) {
				$(this).css('--hover-color', $(this).children().data().color);
				$(this).on('click', function () {
					window.location.href = $(this).children().data().href
				});
			}
		});

		const loader = $('#loading-indicator');
		const container = $('.supersearch-container');

		//Loader
		if (mySearch.status === 'loading' || mySearch.status === 'stalled') {
			$(loader).css('display', 'flex');
			$(loader).css('justify-content', 'center');
			$(container).css('display', 'none');
			$('.swal2-content, #swal2-content').css('min-height', '100px'); //#60 -> 100 because the loader is 75px height and spin
		} else {
			$(loader).css('display', 'none');
			$(container).css('display', 'flex');
		}

	});

	// Start the search interface.
	mySearch.start();

	$('input.supersearch-searchbox-input').focus(); // focus input 'searchbox'
}


/**
 * Configures the multi-index search functionality.
 *
 * @param {object} searchClient - The MeiliSearch client used to perform the search.
 *                                This client interacts with the MeiliSearch server to fetch results.
 * @return {void}
 */
function setupMultiSearch(searchClient) {
	// Initialize the InstantSearch instance with an empty indexName as it will be set later.
	mySearch = instantsearch({ indexName: '', searchClient, searchFunction(helper) {
			if (helper.state.query && helper.state.query.trim().length > 0) { // we put custom "search" -> we send 'search' only if we have a query
				helper.search();
			} else {
				$(`div[id*='-hits']`).empty();
			}
		} });
	console.log('Initialize Multisearch');
	// Loop through each indexes
	indexes.forEach(({ uid }) => {
		if (objectTemplate[uid.replace(dolUID, '')]) // #46
		{
			// Add widgets to the search instance for each index.
			mySearch.addWidgets([
				// Add the index widget for the current index.
				instantsearch.widgets.index({ indexName: uid }).addWidgets([
					// Add the 'hits' widget to display the search results.
					instantsearch.widgets.hits({
						container: `div[id*='${uid}-hits']`, // Specify the container element for the hits.
						templates: {
							item: hit => generateTemplate(hit, uid, mySearch.status) // Template for rendering each hit item.
						},
						cssClasses: {
							root: 'supersearch-root',
							emptyRoot: 'supersearch-empty-root',
							list: 'supersearch-results-list',
							item: 'supersearch-results-item',
						}
					})
				]),
				// Add a custom searchBox widget for inputting search queries.
				customSearchBox(),
				// Configure the number of hits per page to be hitsPerPage.
				instantsearch.widgets.configure({
					hitsPerPage: hitsPerPage,
					filters: `<?php echo $filter; ?>`
				}),
			]);
		}
	});
}

/**
 * Opens a search box (using SweetAlert) that contains the search bar and related UI components.
 *
 * @return {void}
 */
function openSearchBox() {

	// #53 include css on header
	if (!$('link[href*="popup.css"]').length) $('head').append('<link rel="stylesheet" type="text/css" href="<?php echo dol_buildpath('/supersearch/css/popup.css', 1); ?>?v=' + Date.now() + '">'); // force to load correctly the css and call one time

	//#56 if no indexes -> no meilisearch so we display an error
	let title = (indexes ? `<div id="searchbox"></div><span class="fa fa-question-circle" title="<?php echo dol_escape_htmltag($tooltipText); ?>"></span>` : `<?php echo $langs->trans("SuperSearchServerNotReachable") ?>`)

	// Use SweetAlert2 to create a custom modal with the search box and other UI elements.
	if (active === true) {
		Swal.fire({
			title: title,
			html: `<?php echo $content; ?>`,
			footer: `<?php echo $version; ?>`,
			showConfirmButton: false,  // Removes the confirm button, allowing for a cleaner interface.
			focusConfirm: false,       // Disables automatic focus on the confirm button.
			customClass: {
				popup: 'swal-supersearch',
				content: 'swal-supersearch-content',
				title: 'swal-supersearch-title',
				footer: 'swal-supersearch-footer'
			}, // Custom Class for this Swal
			didOpen: function () {      // Function executed when the modal is opened.
				console.log('Open SearchBox');
				initializeSearch();
			}
		});
	} else {
		// put swal fire with default message
			// go create the swal
		Swal.fire({
			title: `<?php echo $langs->trans('SuperSearchNoAccessRight');?>`
		});
	}
}
/**
 * Fetches the list of indexes from MeiliSearch.
 *
 * @return {Promise<Array>} - A promise that resolves to an array of indexes retrieved from MeiliSearch.
 *                            Each index in the array contains metadata such as its UID.
 */
function fetchIndexes() {
	return fetch("<?php echo $supersearchURL; ?>/indexes", {
		headers: { "Authorization": `Bearer <?php echo $supersearchAPIKey; ?>` }
	}).then(res => res.json()).then(data => {
		const filteredResults = data.results.filter(item => {
			const uid = item.uid.replace(dolUID, '');
			return objectTemplate.hasOwnProperty(uid) && objectTemplate[uid]["disabled"] === "0"; // check if index is present in objectTemplate
		});
		return filteredResults.sort((a, b) => {
			const posA = objectTemplate[a.uid.replace(dolUID, '')]?.position ?? 0;
			const posB = objectTemplate[b.uid.replace(dolUID, '')]?.position ?? 99999;
			return posA - posB;
		});
	});
}

function openSearchBoxWithIndexes() {
	// Fetch the indexes from MeiliSearch and store them in a global variable `indexes`.
	fetchIndexes().then(data => {
		indexes = data;
		openSearchBox(); // Open the search box modal.
	}).catch(err => {
		Swal.fire({
			title: `<?php echo $langs->trans('SuperSearchErrorFetchIndexes');?>`,
			icon: "error"
		});
		console.error("Erreur de récupération des indexes :", err);
	});
}

// jQuery function that runs when the document is fully loaded.
$(document).ready(function () {
	// Add a global event listener for keyboard events.
	$(document).on("keydown", function(e) {
		// Check if the 'Alt' key and the 'S' key (keyCode 83) are pressed simultaneously.
		if (e.altKey && e.keyCode === 83) {
			$("#searchselectcombo").select2('close');
			e.preventDefault(); // Prevent the default action associated with the 'Alt+B' key combination.
			openSearchBoxWithIndexes();
		}
	});
});

function debounce(func, wait) {
	let timeout;
	return function (...args) {
		clearTimeout(timeout);
		timeout = setTimeout(() => func.apply(this, args), wait);
	};
}

const customSearchBox = instantsearch.connectors.connectSearchBox(
	function renderSearchBox({ refine }, isFirstRendering) {
		if (isFirstRendering) {
			const container = document.querySelector('#searchbox');
			container.innerHTML = '';

			const input = document.createElement('input');
			input.type = 'search';
			input.placeholder = `<?php echo $langs->trans('SuperSearchPlaceholderSearchBox', $charsBeforeSearch); ?>`;
			input.className = 'supersearch-searchbox-input';

			container.appendChild(input);

			input.addEventListener(
				'input',
				debounce((event) => {
					const value = event.target.value;
					if (value.length >= charsBeforeSearch) {
						refine(value);
					}
				}, 500)
			);
		}
	}
);

// Add button next to searchbar native to dolibarr
$(window).on('load', function () {
	$('#blockvmenusearch').append('<img style="cursor: pointer; width: 32px" class="valignmiddle pictotitle" src="<?php echo $picto; ?>" alt="" onclick="openSearchBoxWithIndexes()">');
});
