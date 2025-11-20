/* Copyright (C) 2024 ATM Consulting <support@atm-consulting.fr>
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
 * Initialize DataTable for supplier proposal list
 * @param {string} tableId - ID of the table to initialize
 * @param {string} languageUrl - URL to the language file for DataTables
 * @param {number} defaultSortColumn - Column index to sort by default
 */
function initSupplierProposalDataTable(tableId, languageUrl, defaultSortColumn) {
	$(document).ready(function () {
		$("#" + tableId).DataTable({
			"language": {
				"url": languageUrl
			},
			'order': [[defaultSortColumn, 'desc']],
			responsive: true,
			columnDefs: [{
				orderable: false,
				"aTargets": [-1] // Disable ordering on the last column (Status)
			}, {
				"bSearchable": false,
				"aTargets": [-2, -1] // Disable search on TotalHT and Status
			}]
		});
	});
}
