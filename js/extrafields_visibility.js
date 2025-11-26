/**
 * Clichaumeil extrafields visibility manager.
 *
 * Responsibilities:
 * - Toggle height/length extrafields on add and edit forms depending on product category.
 * - Show/hide height/length extrafields per line in view mode, without touching other extrafields.
 *
 * Data source:
 * - An inline JSON script with id "clichaumeil-extrafields-data" injected server-side.
 */
(function () {
	'use strict';

	const dataNode = document.getElementById('clichaumeil-extrafields-data');
	if (!dataNode) return;

	let config;
	try {
		config = JSON.parse(dataNode.textContent || '{}');
	} catch (e) {
		console.error('Clichaumeil extrafields: invalid JSON config', e);
		return;
	}

	const $ = window.jQuery || window.$;
	if (!$) {
		console.error('Clichaumeil extrafields: jQuery not found, aborting');
		return;
	}

	const targetProducts = Array.isArray(config.targetProducts) ? config.targetProducts.map((id) => parseInt(id, 10)).filter(Number.isInteger) : [];
	const lines = Array.isArray(config.lines) ? config.lines : [];

	const isTargetProduct = (productId) => {
		const id = parseInt(productId, 10);
		return Number.isInteger(id) && id > 0 && targetProducts.includes(id);
	};

	const toggleExtraFields = ($container, shouldShow) => {
		if (!$container || !$container.length) return;

		const lengthInput = $container.find('#options_clichaumeil_length');
		const heightInput = $container.find('#options_clichaumeil_height');

		const lengthRow = $container.find('.fieldline_options_clichaumeil_length');
		const heightRow = $container.find('.fieldline_options_clichaumeil_height');

		if (shouldShow) {
			lengthRow.show();
			heightRow.show();
			return;
		}

		// Reset values to avoid persisting data on hidden fields
		lengthInput.val('');
		heightInput.val('');
		lengthRow.hide();
		heightRow.hide();
	};

	const findFieldLine = (line, key) => {
		if (!line || !line.selectors) return $();

		const selectors = [
			line.selectors[key],
			`#extrarow-${line.element}_${key}_${line.id}`, // legacy id format
			`.fieldline_options_${key}[data-element="extrafield"][data-targetelement="${line.element}"][data-targetid="${line.id}"]`,
			`.field_options_${key}[data-element="extrafield"][data-targetelement="${line.element}"][data-targetid="${line.id}"]`,
		].filter(Boolean);

		let $found = $(selectors.join(','));
		if ($found.length) return $found;

		// Fallback: look inside the extrafields container for anything matching the key
		const $container = $(`#extrafield_lines_area_${line.id}`);
		if ($container.length) {
			$found = $container.find(`[class*="${key}"]`);
			if ($found.length) return $found;
		}

		return $();
	};

	const resetVisibility = () => {
		$(
			'[id^="extrafield_lines_area_"] .fieldline_options_clichaumeil_length, ' +
			'[id^="extrafield_lines_area_"] .fieldline_options_clichaumeil_height, ' +
			'[id^="extrafield_lines_area_"] .field_options_clichaumeil_length, ' +
			'[id^="extrafield_lines_area_"] .field_options_clichaumeil_height'
		).show();
	};

	const initAddForm = () => {
		const productSelect = $('#idprod');
		if (!productSelect.length) return;

		const addForm = $('#extrafield_lines_area_create');
		const apply = () => toggleExtraFields(addForm, isTargetProduct(productSelect.val()));

		productSelect.on('change', apply);
		apply();
	};

	const initEditForm = () => {
		const editForm = $('#extrafield_lines_area_edit');
		if (!editForm.length) return;

		const editProductSelect = $('#productid');
		const editProductHidden = $('#product_id'); // when product is locked

		const getEditProductId = () => {
			if (editProductSelect.length) return editProductSelect.val();
			return editProductHidden.val();
		};

		const apply = () => toggleExtraFields(editForm, isTargetProduct(getEditProductId()));

		if (editProductSelect.length) {
			editProductSelect.on('change', apply);
		}

		apply();
	};

	const applyLineVisibility = () => {
		lines.forEach((line) => {
			const lengthRow = findFieldLine(line, 'clichaumeil_length');
			const heightRow = findFieldLine(line, 'clichaumeil_height');

			let $targets = lengthRow.add(heightRow);
			if (!$targets.length) {
				$targets = $(`#extrafield_lines_area_${line.id}`).find('[class*="clichaumeil_length"], [class*="clichaumeil_height"]');
			}

			if (line.show) {
				$targets.show();
			} else {
				$targets.hide();
			}
		});
	};

	$(function () {
		resetVisibility();
		initAddForm();
		initEditForm();
		applyLineVisibility();
	});
})();
