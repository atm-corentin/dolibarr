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

		var quickPriceCells = row.querySelectorAll(
			'td.linecoluht, td.linecolqty, td.linecoldiscount, td.linecolmargin1, td.linecolmargin2, td.linecolmark1, td.linecoluht_currency, td.linecolcycleref'
		);
		quickPriceCells.forEach(function (cell) {
			var anchors = cell.querySelectorAll('a[col][lineid="' + lineId + '"]');
			anchors.forEach(function (anchor) {
				while (anchor.firstChild) {
					cell.insertBefore(anchor.firstChild, anchor);
				}
				anchor.remove();
			});

			var inputs = cell.querySelectorAll('input.qcp');
			inputs.forEach(function (input) {
				input.remove();
			});
		});

		var extraEditors = row.querySelectorAll('.quick-edit-extras');
		extraEditors.forEach(function (editor) {
			editor.style.display = 'none';
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		var payload = getPayload();
		if (!payload || !Array.isArray(payload.lineIds)) {
			return;
		}

		payload.lineIds.forEach(function (lineId) {
			hideEditForLine(lineId);
		});
	});
}());
