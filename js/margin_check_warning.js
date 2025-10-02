/**
* Copyright (C) 2025 Grégory MAZA

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
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

(() => {
	document.addEventListener('DOMContentLoaded', () => {
		const dataElement = document.getElementById('margins-pagedata');
		if (!dataElement) return;

		const pageData = JSON.parse(dataElement.textContent || '{}');
		const linesData = pageData.lines || {};

		// Attach initial data (cost price and warning icon) to each row
		for (const lineId in linesData) {
			const row = document.getElementById(`row-${lineId}`);
			if (row) {
				row.dataset.costPrice = linesData[lineId].cost_price;
				row.dataset.warningIcon = linesData[lineId].warning_icon;
			}
		}

		// Helper: extract the first number from a string (even if it contains HTML)
		const extractNumber = (raw) => {
			if (!raw && raw !== 0) return NaN;
			// If raw is a DOM element, use its text content
			if (raw.nodeType) raw = raw.textContent;
			// If raw contains HTML, remove it
			const tmp = document.createElement('div');
			tmp.innerHTML = String(raw);
			const text = tmp.textContent.trim();
			// Find the first number in the text (supports commas or dots)
			const m = text.match(/-?\d+(?:[.,]\d+)?/);
			return m ? parseFloat(m[0].replace(',', '.')) : NaN;
		};

		// Create a warning DOM element from the stored HTML
		const buildWarningElement = (html) => {
			const tmp = document.createElement('div');
			tmp.innerHTML = (html || '').trim();

			// Use the first HTML element inside the string
			const el = tmp.firstElementChild || tmp.firstChild;
			if (!el) {
				// Fallback: simple warning icon if nothing is provided
				const span = document.createElement('span');
				span.className = 'negative-margin-warning pictowarning';
				span.textContent = '⚠️';
				return span;
			}
			// Add a class for consistent styling
			el.classList.add('negative-margin-warning');
			return el;
		};

		/**
		 * Check a single <tr> and add/remove warning.
		 * Ensures the warning is inserted AFTER the editable anchor (so Dolibarr doesn't include it in the input value).
		 */
		const checkLineMargin = (tr) => {
			if (!tr) return;

			const targetTd = tr.querySelector('.linecoluht');
			if (!targetTd) return;

			// remove previous warnings in this cell (wherever they are)
			targetTd.querySelectorAll('.negative-margin-warning').forEach(n => n.remove());

			// 1) Try to find an input (inline editor). If present, read its value (robustly).
			const puInput = tr.querySelector('input[name^="price"], input[name*="subprice"], input[name^="pvp"]');
			let puHt = NaN;
			if (puInput) {
				// input.value might contain HTML string if previous code injected badly. Clean it and extract number.
				puHt = extractNumber(puInput.value || puInput.getAttribute('value') || puInput.textContent);
			} else {
				// fallback : take text / HTML content of the cell but strip HTML tags
				// prefer the editable anchor's text if present
				const editableAnchor = targetTd.querySelector('a, .edit, .line-edit');
				if (editableAnchor) {
					puHt = extractNumber(editableAnchor.innerHTML);
				} else {
					puHt = extractNumber(targetTd.innerHTML);
				}
			}

			// 2) cost price is stored in dataset by PHP; but could contain HTML - be robust
			const costPrice = extractNumber(tr.dataset.costPrice);

			// 3) if negative margin, create warning and insert AFTER the editable anchor (if any), else append to cell
			if (!isNaN(puHt) && !isNaN(costPrice) && puHt < costPrice) {
				const warningEl = buildWarningElement(tr.dataset.warningIcon);
				warningEl.title = "⚠️ Attention : le prix de revient est supérieur au prix de vente. Marge négative.";

				const editableAnchor = targetTd.querySelector('a, .edit, .line-edit');
				if (editableAnchor && editableAnchor.parentNode === targetTd) {
					// insert after the anchor to avoid being included in anchor.innerHTML
					editableAnchor.insertAdjacentElement('afterend', warningEl);
				} else {
					// append at the end of TD (still outside inputs/anchors when possible)
					targetTd.appendChild(warningEl);
				}
			}
		};

		// Check all rows once
		const checkAllRows = () => {
			document.querySelectorAll('tr[id^="row"]').forEach(checkLineMargin);
		};

		// Initial check
		checkAllRows();

		// 📌 Grab the main table containing all quote lines
		const table = document.getElementById('tablelines');
		if (table) {

			/**
			 * - Listen for `input` events:
			 * This event fires every time the user types something into an input field
			 * (like quantity, unit price, discount, etc.).
			 *
			 * 👉 Purpose: check the margin **live** as the user edits the fields.
			 */

			table.addEventListener('input', (event) => {
				// Only react to relevant fields: unit price, quantity, discount...
				if (event.target.matches('input[name^="price"], input[name^="qty"], input[name^="remise_percent"], input')) {
					// Find the <tr> row where the change occurred
					const changedRow = event.target.closest('tr');
					if (changedRow) {
						/**
						 * Small delay (30 ms):
						 * - Gives Dolibarr time to update other fields in the row (like recalculating unit price or totals)
						 * - Then runs the margin check on this specific line
						 */
						setTimeout(() => checkLineMargin(changedRow), 30);
					}
				}
			});

			/**
			 * - Listen for `change` events:
			 * This event fires when the user leaves a field (on blur) or confirms a change.
			 *
			 * 👉 Purpose: ensure we still check the margin even if the user pastes a value,
			 * navigates with the keyboard, or changes the value without typing.
			 */
			table.addEventListener('change', (event) => {
				const changedRow = event.target.closest('tr');
				if (changedRow) setTimeout(() => checkLineMargin(changedRow), 30);
			});

			/**
			 * - MutationObserver:
			 * A MutationObserver "watches" the DOM and triggers a callback when it detects changes:
			 * - Rows being added or removed
			 * - Cells being updated by AJAX
			 * - Any DOM changes not triggered by user input
			 *
			 * 👉 Purpose: automatically re-check all rows when Dolibarr dynamically re-renders
			 * the table (for example, after editing a line or adding a new one).
			 */

				// "Debounce" function: prevents `checkAllRows()` from running too often
			const debounced = (() => {
				let t = null;
				return () => {
					if (t) clearTimeout(t);   // Reset the timer if already scheduled
					t = setTimeout(() => checkAllRows(), 50); // Call `checkAllRows()` after 50ms of inactivity
				};
			})();

			// Create the observer to watch for DOM changes inside the table
			const observer = new MutationObserver((mutations) => {
				// if rows are added/updated, re-check (debounced)
				let relevant = false;

				// Create the observer to watch for DOM changes inside the table
				for (const m of mutations) {

					/**
					 * - childList: rows added or removed
					 * - characterData: text content updated (e.g., recalculated PU)
					 * - subtree: deeper changes in the table structure
					 */

					if (m.type === 'childList' || m.type === 'characterData' || m.type === 'subtree') {
						relevant = true;
						break;  // One relevant change is enough — we don’t need to keep checking
					}
				}
				// If we detected meaningful changes, trigger a debounced full check
				if (relevant) debounced();
			});
			// Start watching the table for DOM changes
			observer.observe(table, { childList: true, subtree: true, characterData: true });
		}
	});
})();


