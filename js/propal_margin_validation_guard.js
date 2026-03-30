/**
 * Disable proposal validation button when CliChaumeil margin guard is active.
 */
(() => {
	const MARKER_ID = 'clichaumeil-propal-validation-guard';
	const VALIDATE_BUTTON_SELECTOR = 'a.butAction[href*="action=validate"]';
	const DISABLED_CLASS = 'butActionRefused';
	const TOOLTIP_CLASS = 'classfortooltip';

	document.addEventListener('DOMContentLoaded', () => {
		const marker = document.getElementById(MARKER_ID);
		if (!marker) {
			return;
		}

		const message = marker.getAttribute('data-message') || '';
		if (!message) {
			return;
		}

		const button = document.querySelector(VALIDATE_BUTTON_SELECTOR);
		if (!button) {
			return;
		}

		button.classList.remove('butAction');
		button.classList.add(DISABLED_CLASS);
		button.classList.add(TOOLTIP_CLASS);
		button.setAttribute('title', message);
		button.setAttribute('href', '#');
		button.setAttribute('aria-disabled', 'true');

		button.addEventListener('click', (event) => {
			event.preventDefault();
			event.stopPropagation();
		});
	});
})();
