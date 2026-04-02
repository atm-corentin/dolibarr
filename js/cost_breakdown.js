/**
 * Clichaumeil product cost breakdown UI manager.
 *
 * Responsibilities:
 * - Move cost breakdown rows into the supplier-price tab and initialize the separator toggle.
 * - Hide duplicate native extrafields on the product card.
 *
 * Data sources:
 * - #clichaumeil-cost-breakdown-data
 * - #clichaumeil-cost-breakdown-hide-data
 */
(function () {
	'use strict';

	const $ = window.jQuery || window.$;
	if (!$) {
		console.error('Clichaumeil cost breakdown: jQuery not found, aborting');
		return;
	}

	const parseJsonNode = (id) => {
		const node = document.getElementById(id);
		if (!node) return null;

		try {
			return JSON.parse(node.textContent || '{}');
		} catch (e) {
			console.error(`Clichaumeil cost breakdown: invalid JSON config for ${id}`, e);
			return null;
		}
	};

	const initSupplierCostBreakdown = (config) => {
		const $holder = $('#clichaumeil-cost-breakdown');
		if (!$holder.length) return;

		const $rows = $holder.find('tr');
		const $targetTable = $('.fichecenter .tableforfield tbody').first();
		if ($targetTable.length && $rows.length) {
			$rows.appendTo($targetTable);
		}
		$holder.remove();

		if (!config) return;

		const separatorId = String(config.separatorId || '');
		const collapseClass = String(config.collapseClass || '');
		const cookieName = String(config.cookieName || '');
		const cookiePath = String(config.cookiePath || '/');
		let expanded = Boolean(config.expanded);
		if (!separatorId || !collapseClass || !cookieName) return;

		const $separator = $('#' + separatorId);
		const $groupRows = $('.' + collapseClass);
		if (!$separator.length || !$groupRows.length) return;

		const $icon = $separator.find('td span, th span').first();
		$icon.addClass('cursorpointer');

		const applyState = (isExpanded) => {
			$groupRows.toggle(isExpanded);
			$icon
				.toggleClass('fa-minus-square', isExpanded)
				.toggleClass('fa-plus-square', !isExpanded)
				.removeClass('fa-square opacitymedium');
			document.cookie = `${cookieName}=${isExpanded ? '1' : '0'}; path=${cookiePath}; SameSite=Lax`;
		};

		applyState(expanded);
		$separator.off('click.clichaumeilSeparator').on('click.clichaumeilSeparator', () => {
			expanded = !expanded;
			applyState(expanded);
		});
	};

	const hideNativeProductExtrafields = (config) => {
		const fields = Array.isArray(config && config.fields) ? config.fields : [];
		fields.forEach((field) => {
			// Dolibarr does not reliably let us exclude these extrafields at render time,
			// so we hide the native rows in the DOM. Update these selectors if core markup changes.
			const selectors = [
				'.field_options_' + field,
				'.product_extras_' + field,
				'[id^="extrarow-product_' + field + '_"]',
				'[id^="trextrafieldseparator' + field + '_"]',
				'[class~="trextrafieldseparator' + field + '"]',
			].join(',');

			$(selectors).each(function () {
				const $el = $(this);
				const $row = $el.closest('tr');
				if ($row.length) {
					$row.hide();
				} else {
					$el.hide();
				}
			});
		});
	};

	$(function () {
		initSupplierCostBreakdown(parseJsonNode('clichaumeil-cost-breakdown-data'));
		hideNativeProductExtrafields(parseJsonNode('clichaumeil-cost-breakdown-hide-data'));
	});
})();
