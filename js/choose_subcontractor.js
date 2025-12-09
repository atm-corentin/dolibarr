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
		$(document).off('click', '.clichaumeil-select-subcontractor').on('click', '.clichaumeil-select-subcontractor', function (e) {
			e.preventDefault();
			const $container = $(this).closest('.clichaumeil-subcontractor-modal');
			const supplierProposalId = $(this).data('supplier-proposal-id');
			if (!supplierProposalId) {
				return;
			}

			const ajaxUrl = $container.data('ajax-url');
			const token = $container.data('token');
			const parentType = $container.data('parent-type');
			const parentId = $container.data('parent-id');
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
				if (response && response.success) {
					window.location.reload();
					return;
				}
				console.log(response.message);

				alert(response && response.message ? response.message : 'Erreur lors de la sélection du sous-traitant');
				if (window.console && console.error) {
					console.error('CliChaumeil choose_subcontractor response', response);
				}
			}).fail(function (jqXHR, textStatus, errorThrown) {
				var details = (jqXHR && jqXHR.responseText) ? '\n' + jqXHR.responseText : '';
				alert('Erreur lors de la sélection du sous-traitant' + details);
				if (window.console && console.error) {
					console.error('CliChaumeil choose_subcontractor ajax error', textStatus, errorThrown, jqXHR);
				}
			});
		});
	}
};

$(function () {
	CliChaumeilSubcontractor.init();
});
