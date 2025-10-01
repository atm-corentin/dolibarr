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

		// Attacher les données initiales aux lignes
		for (const lineId in linesData) {
			const row = document.getElementById(`row-${lineId}`);
			if (row) {
				row.dataset.costPrice = linesData[lineId].cost_price;
				row.dataset.warningIcon = linesData[lineId].warning_icon;
			}
		}

		// Utilitaire : extraire la première valeur numérique d'une chaîne (gère HTML dans la string)
		const extractNumber = (raw) => {
			if (!raw && raw !== 0) return NaN;
			// Si raw est un élément DOM, prendre son texte
			if (raw.nodeType) raw = raw.textContent;
			// si raw contient du HTML, nettoyer
			const tmp = document.createElement('div');
			tmp.innerHTML = String(raw);
			const text = tmp.textContent.trim();
			const m = text.match(/-?\d+(?:[.,]\d+)?/);
			return m ? parseFloat(m[0].replace(',', '.')) : NaN;
		};

		// Créer un élément warning DOM à partir du HTML stocké
		const buildWarningElement = (html) => {
			const tmp = document.createElement('div');
			tmp.innerHTML = (html || '').trim();
			const el = tmp.firstElementChild || tmp.firstChild;
			if (!el) {
				// fallback simple
				const span = document.createElement('span');
				span.className = 'negative-margin-warning pictowarning';
				span.textContent = '⚠️';
				return span;
			}
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

		// Listen for input changes (live typing)
		const table = document.getElementById('tablelines');
		if (table) {
			table.addEventListener('input', (event) => {
				if (event.target.matches('input[name^="price"], input[name^="qty"], input[name^="remise_percent"], input')) {
					const changedRow = event.target.closest('tr');
					if (changedRow) {
						// small delay to let other handlers update things if needed
						setTimeout(() => checkLineMargin(changedRow), 30);
					}
				}
			});

			// Also listen for change event (blur/save)
			table.addEventListener('change', (event) => {
				const changedRow = event.target.closest('tr');
				if (changedRow) setTimeout(() => checkLineMargin(changedRow), 30);
			});

			// MutationObserver: watches for DOM updates from Dolibarr (AJAX re-rendering / inline replace)
			const debounced = (() => {
				let t = null;
				return () => {
					if (t) clearTimeout(t);
					t = setTimeout(() => checkAllRows(), 50);
				};
			})();

			const observer = new MutationObserver((mutations) => {
				// if rows are added/updated, re-check (debounced)
				let relevant = false;
				for (const m of mutations) {
					// small heuristic: if changes are inside tablelines, consider relevant
					if (m.type === 'childList' || m.type === 'characterData' || m.type === 'subtree') {
						relevant = true;
						break;
					}
				}
				if (relevant) debounced();
			});

			observer.observe(table, { childList: true, subtree: true, characterData: true });
		}
	});
})();


