/* global $, window */

/**
 * CliChaumeil subcontractor picker
 */
const CliChaumeilSubcontractor = {
	init() {
		this.bindOpeners();
		this.bindActions();
	},

	bindOpeners() {
		$('.clichaumeil-open-subcontractor').on('click', function (e) {
			e.preventDefault();
			const targetId = $(this).data('modal-target');
			if (!targetId) return;
			const $modal = $('#' + targetId);
			const width = Math.min($(window).width() - 50, 960);
			CliChaumeilSubcontractor.clearInlineMessage($modal);

			// Initialize dialog once
			if (!$modal.data('uiDialog')) {
				$modal.dialog({
					autoOpen: false,
					modal: true,
					width: width,
					dialogClass: 'clichaumeil-subcontractor-dialog',
					title: $(this).text(),
					closeText: '×'
				});
			} else {
				$modal.dialog('option', 'width', width);
				$modal.dialog('option', 'title', $(this).text());
			}

			$modal.dialog('open');
		});
	},

	bindActions() {
		$(document).on('click', '.clichaumeil-select-subcontractor', function (e) {
			e.preventDefault();
			const $button = $(this);
			if ($button.prop('disabled')) {
				return;
			}
			const $container = $(this).closest('.clichaumeil-subcontractor-modal');
			const supplierProposalId = $(this).data('supplier-proposal-id');
			if (!supplierProposalId) {
				return;
			}

				const ajaxUrl = $container.data('ajax-url');
				const defaultErrorMessage = $container.data('error-message') || '';
			const token = $container.data('token');
			const parentType = $container.data('parent-type');
			const parentId = $container.data('parent-id');
			CliChaumeilSubcontractor.clearInlineMessage($container);
			$button.prop('disabled', true);
			$.ajax({
				url: ajaxUrl,
				method: 'POST',
				dataType: 'json',
				data: {
					action: 'choose_subcontractor',
					token: token,
					supplier_proposal_id: supplierProposalId,
					parent_type: parentType,
					parent_id: parentId
				}
			}).done(function (response) {
				if (response && response.success && response.should_reload) {
					CliChaumeilSubcontractor.redirectWithGet(response && response.redirect_url ? response.redirect_url : '');
					return;
				}

				CliChaumeilSubcontractor.showInlineMessage(
					$container,
					response && response.message ? response.message : defaultErrorMessage,
					response && response.status === 'completed_with_warning' ? 'warning' : 'error'
				);
				if (window.console && console.log) {
					console.log('CliChaumeil choose_subcontractor response', response);
				}
			}).fail(function (jqXHR, textStatus, errorThrown) {
				var message = defaultErrorMessage;
				if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.message) {
					message = jqXHR.responseJSON.message;
				}
				CliChaumeilSubcontractor.showInlineMessage($container, message, 'error');
				if (window.console && console.error) {
					console.error('CliChaumeil choose_subcontractor ajax error', textStatus, errorThrown, jqXHR);
				}
			}).always(function () {
				$button.prop('disabled', false);
			});
		});
	},

	showInlineMessage($container, message, level) {
		const $message = $container.find('.clichaumeil-subcontractor-modal__message').first();
		if (!$message.length) {
			return;
		}

		$message
			.removeClass('error warning ok')
			.addClass(level === 'warning' ? 'warning' : 'error')
			.addClass('is-visible')
			.text(message || '');
	},

	clearInlineMessage($container) {
		const $message = $container.find('.clichaumeil-subcontractor-modal__message').first();
		if (!$message.length) {
			return;
		}

		$message
			.removeClass('error warning ok is-visible')
			.empty();
	},

	hideOpenButton($container) {
		const modalId = $container.attr('id');
		if (!modalId) {
			return;
		}

		$('.clichaumeil-open-subcontractor[data-modal-target="' + modalId + '"]').hide();
	},

	closeModal($container) {
		if ($container.data('uiDialog')) {
			$container.dialog('close');
		}
	},

	redirectWithGet(redirectUrl) {
		var targetUrl = redirectUrl;
		if (!targetUrl) {
			targetUrl = window.location.pathname + window.location.search + window.location.hash;
		}
		window.location.replace(targetUrl);
	}
};

$(function () {
	CliChaumeilSubcontractor.init();
});
