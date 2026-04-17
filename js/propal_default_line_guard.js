(function () {
	'use strict';

	function getPayload() {
		var node = document.getElementById('clichaumeil-default-propal-line-guard');
		if (!node) {
			return null;
		}

		try {
			return JSON.parse(node.textContent || '{}');
		} catch (error) {
			return null;
		}
	}

	function normalizeLineIds(lineIds) {
		var normalized = [];
		var seen = Object.create(null);

		if (!Array.isArray(lineIds)) {
			return normalized;
		}

		lineIds.forEach(function (lineId) {
			var normalizedId = parseInt(lineId, 10);
			if (isNaN(normalizedId) || normalizedId <= 0 || seen[normalizedId]) {
				return;
			}

			seen[normalizedId] = true;
			normalized.push(normalizedId);
		});

		return normalized;
	}

	function parseSelectedLines(value) {
		if (!value) {
			return [];
		}

		return normalizeLineIds(String(value).split(','));
	}

	function writeSelectedLines(field, lineIds) {
		if (!field) {
			return;
		}

		field.value = normalizeLineIds(lineIds).join(',');
	}

	function stripQuickPriceControlsForLine(row, lineId) {
		var quickPriceCells;
		var extraEditors;
		var quickPriceAnchors;
		var quickPriceInputs;

		if (!row) {
			return;
		}

		quickPriceCells = row.querySelectorAll(
			'td.linecoluht, td.linecolqty, td.linecoldiscount, td.linecolmargin1, td.linecolmargin2, td.linecolmark1, td.linecoluht_currency, td.linecolcycleref'
		);
		quickPriceCells.forEach(function (cell) {
			quickPriceAnchors = cell.querySelectorAll('a[col][lineid="' + lineId + '"], a.blue[lineid="' + lineId + '"]');
			quickPriceAnchors.forEach(function (anchor) {
				var textNode = document.createTextNode(anchor.textContent || '');
				anchor.replaceWith(textNode);
			});

			quickPriceInputs = cell.querySelectorAll('input.qcp');
			quickPriceInputs.forEach(function (input) {
				input.remove();
			});

			cell.style.cursor = 'default';
		});

		extraEditors = row.querySelectorAll('.quick-edit-extras');
		extraEditors.forEach(function (editor) {
			editor.style.display = 'none';
		});
	}

	function observeQuickPriceReinjection(row, lineId) {
		if (!row || row.dataset.clichaumeilQuickObserver === '1' || typeof MutationObserver === 'undefined') {
			return;
		}

		row.dataset.clichaumeilQuickObserver = '1';

		new MutationObserver(function () {
			stripQuickPriceControlsForLine(row, lineId);
		}).observe(row, {
			childList: true,
			subtree: true
		});
	}

	function hideEditForLine(lineId) {
		var row = document.getElementById('row-' + lineId);
		if (!row) {
			return;
		}

		var editLinks = row.querySelectorAll('.linecoledit a, a[href*="action=editline"][href*="lineid=' + lineId + '"]');
		editLinks.forEach(function (link) {
			link.style.display = 'none';
		});

		if (window.jQuery) {
			window.jQuery(row).find(
				'td.linecoluht, td.linecolqty, td.linecoldiscount, td.linecolmargin1, td.linecolmargin2, td.linecolmark1, td.linecoluht_currency, td.linecolcycleref'
			).off('click');
		}

		stripQuickPriceControlsForLine(row, lineId);
		observeQuickPriceReinjection(row, lineId);
	}

	function disableMassActionForLine(lineId, massActionConfig) {
		var row = document.getElementById('row-' + lineId);
		var checkbox;
		var checkboxCell;

		if (!row || !massActionConfig || !massActionConfig.checkboxSelector) {
			return;
		}

		checkbox = row.querySelector(massActionConfig.checkboxSelector);
		if (!checkbox) {
			return;
		}

		checkbox.checked = false;
		checkbox.disabled = true;
		checkbox.setAttribute('aria-disabled', 'true');
		checkboxCell = checkbox.closest('td');
		if (checkboxCell) {
			checkbox.style.visibility = 'hidden';
			checkbox.style.display = '';
			checkboxCell.style.visibility = 'visible';
			checkboxCell.style.display = '';
		}
		row.classList.remove('highlight');
	}

	function sanitizeProtectedMassActionState(massActionConfig) {
		if (!massActionConfig || !Array.isArray(massActionConfig.protectedLineIds)) {
			return;
		}

		massActionConfig.protectedLineIds.forEach(function (lineId) {
			var row = document.getElementById('row-' + lineId);
			var checkbox;

			if (!row) {
				return;
			}

			checkbox = row.querySelector(massActionConfig.checkboxSelector);
			if (checkbox) {
				checkbox.checked = false;
				checkbox.disabled = true;
			}

			row.classList.remove('highlight');
		});

		filterProtectedLinesFromSelectedField(massActionConfig);
	}

	function filterProtectedLinesFromSelectedField(massActionConfig) {
		var selectedField;
		var currentSelectedLineIds;
		var filteredLineIds;

		if (!massActionConfig || !massActionConfig.selectedLinesSelector) {
			return;
		}

		selectedField = document.querySelector(massActionConfig.selectedLinesSelector);
		if (!selectedField) {
			return;
		}

		currentSelectedLineIds = parseSelectedLines(selectedField.value);
		filteredLineIds = currentSelectedLineIds.filter(function (lineId) {
			return massActionConfig.protectedLineIds.indexOf(lineId) === -1;
		});

		writeSelectedLines(selectedField, filteredLineIds);
	}

	function guardMassActionSelection(massActionConfig) {
		document.addEventListener('change', function (event) {
			var target = event.target;
			var normalizedLineId;

			if (!target || !target.matches(massActionConfig.checkboxSelector)) {
				return;
			}

			normalizedLineId = parseInt(target.value, 10);
			if (isNaN(normalizedLineId) || massActionConfig.protectedLineIds.indexOf(normalizedLineId) === -1) {
				return;
			}

			target.checked = false;
			target.disabled = true;
			filterProtectedLinesFromSelectedField(massActionConfig);
			if (window.jQuery && typeof window.jQuery.jnotify === 'function') {
				window.jQuery.jnotify(massActionConfig.forbiddenMessage, 'warning', {timeout: 4, type: 'warning', css: 'warning'});
			}
		});

		document.addEventListener('click', function (event) {
			var target = event.target;
			var mustSanitize;

			if (!target) {
				return;
			}

			mustSanitize = target.matches('#massaction-checkall, #massaction-checkall-products, #massaction-checkall-services');
			if (!mustSanitize) {
				return;
			}

			window.setTimeout(function () {
				sanitizeProtectedMassActionState(massActionConfig);
			}, 0);
		});

		document.addEventListener('change', function (event) {
			var target = event.target;
			var mustSanitize;

			if (!target) {
				return;
			}

			mustSanitize = target.matches('#massaction-checkall, #massaction-checkall-products, #massaction-checkall-services');
			if (!mustSanitize) {
				return;
			}

			window.setTimeout(function () {
				sanitizeProtectedMassActionState(massActionConfig);
			}, 0);
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		var payload = getPayload();
		var lineIds;
		var massActionConfig;

		if (!payload || !Array.isArray(payload.lineIds)) {
			return;
		}

		lineIds = normalizeLineIds(payload.lineIds);
		lineIds.forEach(function (lineId) {
			hideEditForLine(lineId);
		});

		massActionConfig = payload.massAction || null;
		if (!massActionConfig || !Array.isArray(massActionConfig.protectedLineIds)) {
			return;
		}

		massActionConfig.protectedLineIds = normalizeLineIds(massActionConfig.protectedLineIds);
		massActionConfig.checkboxSelector = massActionConfig.checkboxSelector || '.checkforselect';
		massActionConfig.selectedLinesSelector = massActionConfig.selectedLinesSelector || '#selectedLines';
		massActionConfig.forbiddenMessage = massActionConfig.forbiddenMessage || '';

		massActionConfig.protectedLineIds.forEach(function (lineId) {
			disableMassActionForLine(lineId, massActionConfig);
		});
		filterProtectedLinesFromSelectedField(massActionConfig);
		sanitizeProtectedMassActionState(massActionConfig);
		guardMassActionSelection(massActionConfig);

		window.setTimeout(function () {
			sanitizeProtectedMassActionState(massActionConfig);
			lineIds.forEach(function (lineId) {
				hideEditForLine(lineId);
			});
			massActionConfig.protectedLineIds.forEach(function (lineId) {
				disableMassActionForLine(lineId, massActionConfig);
			});
			filterProtectedLinesFromSelectedField(massActionConfig);
		}, 200);

		window.setTimeout(function () {
			sanitizeProtectedMassActionState(massActionConfig);
			lineIds.forEach(function (lineId) {
				hideEditForLine(lineId);
			});
		}, 800);
	});
}());
