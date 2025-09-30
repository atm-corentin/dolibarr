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

		const pageData = JSON.parse(dataElement.textContent);
		const linesData = pageData.lines;
		console.log(linesData);

		// Attacher les données à chaque ligne <tr>
		for (const lineId in linesData) {
			const row = document.getElementById(`row-${lineId}`);
			if (row) {
				row.dataset.puHt = linesData[lineId].pu_ht;
				row.dataset.costPrice = linesData[lineId].cost_price;
				row.dataset.warningIcon = linesData[lineId].warning_icon;
			}
		}

		// Vérifier chaque ligne
		document.querySelectorAll('tr[id^="row"]').forEach((tr) => {
			const puHt = parseFloat(tr.dataset.puHt);
			const costPrice = parseFloat(tr.dataset.costPrice);
			if (!isNaN(puHt) && !isNaN(costPrice) && puHt < costPrice) {

				// Cibler la cellule contenant le PU HT
				const targetTd = tr.querySelector('.linecoluht');
				if (!targetTd) return;

				// Supprimer un ancien warning s’il existe
				targetTd.querySelector('.negative-margin-warning')?.remove();

				// Créer le warning
				const tempDiv = document.createElement('div');
				tempDiv.innerHTML = tr.dataset.warningIcon;
				const warningElement = tempDiv.firstChild;

				if (warningElement) {
					warningElement.classList.add('negative-margin-warning');
					warningElement.style.marginLeft = '4px';
					warningElement.title = "Attention : le prix de revient est supérieur au prix de vente. Marge négative.";
					targetTd.appendChild(warningElement);
				}
			}
		});

		// ⚡ Mettre à jour le warning quand on modifie une ligne
		const table = document.getElementById('tablelines');
		if (table) {
			table.addEventListener('change', (event) => {
				if (event.target.matches('input[name^="qty"], input[name^="price"], input[name^="remise_percent"]')) {
					const changedRow = event.target.closest('tr');
					if (changedRow) {
						setTimeout(() => {
							const puHt = parseFloat(changedRow.dataset.puHt);
							const costPrice = parseFloat(changedRow.dataset.costPrice);
							const targetTd = changedRow.querySelector('.linecoluht');
							if (targetTd && puHt < costPrice) {
								targetTd.querySelector('.negative-margin-warning')?.remove();
								const tempDiv = document.createElement('div');
								tempDiv.innerHTML = changedRow.dataset.warningIcon;
								const warningElement = tempDiv.firstChild;
								warningElement.title = "Attention : Marge négative.";
								targetTd.appendChild(warningElement);
							}
						}, 100);
					}
				}
			});
		}
	});
})();

