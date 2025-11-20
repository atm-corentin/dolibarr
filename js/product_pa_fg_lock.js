/**
 * Keep the overhead amount extrafield locked on product card to avoid manual edits while
 * the backend recalculates it. Early-exits when the field is absent to avoid interfering
 * with other pages or scripts.
 */
(function () {
	document.addEventListener('DOMContentLoaded', function () {
		const fgField = document.querySelector('input[name="options_pa_fg"]');
		if (!fgField) return;

		fgField.setAttribute('readonly', 'readonly');

		if (fgField.classList && !fgField.classList.contains('readonly')) {
			fgField.classList.add('readonly');
		}

		const row = fgField.closest ? fgField.closest('tr') : null;
		if (!row) {
			const parent = fgField.parentNode;
			while (parent && parent.tagName !== 'TR') {
				parent = parent.parentNode;
			}
			row = parent;
		}

		if (row && row.classList && !row.classList.contains('clichaumeil-pa-fg')) {
			row.classList.add('clichaumeil-pa-fg');
		}
	});
})();
