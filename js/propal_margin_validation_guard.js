/**
 * Keep the proposal validation button synchronized with current line margins.
 */
(() => {
	const MARKER_ID = 'clichaumeil-propal-validation-guard';
	const VALIDATE_BUTTON_SELECTOR = 'a[href*="action=validate"], a.butActionRefused[data-clichaumeil-guard="1"]';
	const DISABLED_CLASS = 'butActionRefused';
	const ENABLED_CLASS = 'butAction';
	const TOOLTIP_CLASS = 'classfortooltip';
	const ROW_SELECTOR = 'tr[id^="row-"]';
	const TABLE_SELECTOR = '#tablelines';

	const extractNumber = (raw) => {
		if (!raw && raw !== 0) return NaN;
		if (raw.nodeType) raw = raw.textContent;

		const tmp = document.createElement('div');
		tmp.innerHTML = String(raw);
		const text = tmp.textContent.trim();
		const match = text.match(/-?\d+(?:[.,]\d+)?/);

		return match ? parseFloat(match[0].replace(',', '.')) : NaN;
	};

	const findValidateButton = () => document.querySelector(VALIDATE_BUTTON_SELECTOR);

	const getLineNetUnitPrice = (row) => {
		const priceCell = row.querySelector('td.linecoluht');
		if (!priceCell) return NaN;

		const priceAnchor = priceCell.querySelector('a');
		const rawUnitPrice = priceAnchor ? (priceAnchor.getAttribute('value') || priceAnchor.textContent) : priceCell.textContent;
		const unitPrice = extractNumber(rawUnitPrice);
		if (isNaN(unitPrice)) return NaN;

		const discountCell = row.querySelector('td.linecoldiscount');
		const discountAnchor = discountCell ? discountCell.querySelector('a') : null;
		const discountPercent = extractNumber(discountAnchor ? (discountAnchor.getAttribute('value') || discountAnchor.textContent) : (discountCell ? discountCell.textContent : '0'));
		if (isNaN(discountPercent)) {
			return unitPrice;
		}

		return unitPrice * (1 - (discountPercent / 100));
	};

	const getLineCostPrice = (row) => {
		const costCell = row.querySelector('td.linecolmargin1');
		if (!costCell) return NaN;

		const costAnchor = costCell.querySelector('a');
		return extractNumber(costAnchor ? (costAnchor.getAttribute('value') || costAnchor.textContent) : costCell.textContent);
	};

	const isBlockingRow = (row) => {
		const netUnitPrice = getLineNetUnitPrice(row);
		const costPrice = getLineCostPrice(row);

		return !isNaN(netUnitPrice) && !isNaN(costPrice) && netUnitPrice < costPrice;
	};

	const disableButton = (button, message) => {
		if (!button.dataset.clichaumeilOriginalHref) {
			button.dataset.clichaumeilOriginalHref = button.getAttribute('href') || '';
		}
		if (!button.dataset.clichaumeilOriginalTitle) {
			button.dataset.clichaumeilOriginalTitle = button.getAttribute('title') || '';
		}

		button.dataset.clichaumeilGuard = '1';
		button.classList.remove(ENABLED_CLASS);
		button.classList.add(DISABLED_CLASS);
		button.classList.add(TOOLTIP_CLASS);
		button.setAttribute('title', message);
		button.setAttribute('href', '#');
		button.setAttribute('aria-disabled', 'true');
	};

	const enableButton = (button) => {
		if (button.dataset.clichaumeilGuard !== '1') {
			return;
		}

		button.classList.remove(DISABLED_CLASS);
		button.classList.remove(TOOLTIP_CLASS);
		button.classList.add(ENABLED_CLASS);
		button.setAttribute('href', button.dataset.clichaumeilOriginalHref || '#');
		button.setAttribute('aria-disabled', 'false');

		if (button.dataset.clichaumeilOriginalTitle) {
			button.setAttribute('title', button.dataset.clichaumeilOriginalTitle);
		} else {
			button.removeAttribute('title');
		}
	};

	const refreshValidationState = (message) => {
		const button = findValidateButton();
		if (!button) {
			return;
		}

		const hasBlockingLine = Array.from(document.querySelectorAll(ROW_SELECTOR)).some(isBlockingRow);
		if (hasBlockingLine) {
			disableButton(button, message);
			return;
		}

		enableButton(button);
	};

	document.addEventListener('DOMContentLoaded', () => {
		const marker = document.getElementById(MARKER_ID);
		if (!marker) {
			return;
		}

		const message = marker.getAttribute('data-message') || '';
		if (!message) {
			return;
		}

		const refresh = () => refreshValidationState(message);
		refresh();

		document.addEventListener('click', (event) => {
			const button = event.target.closest(VALIDATE_BUTTON_SELECTOR);
			if (!button || button.getAttribute('aria-disabled') !== 'true') {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
		}, true);

		if (Array.isArray(window.priceCallbacks)) {
			window.priceCallbacks.push(() => {
				window.setTimeout(refresh, 0);
			});
		}

		const table = document.querySelector(TABLE_SELECTOR);
		if (!table) {
			return;
		}

		table.addEventListener('input', () => {
			window.setTimeout(refresh, 30);
		});

		table.addEventListener('change', () => {
			window.setTimeout(refresh, 30);
		});

		let debounceTimer = null;
		const observer = new MutationObserver(() => {
			if (debounceTimer) {
				window.clearTimeout(debounceTimer);
			}

			debounceTimer = window.setTimeout(refresh, 50);
		});
		observer.observe(table, { childList: true, subtree: true, characterData: true });
	});
})();
