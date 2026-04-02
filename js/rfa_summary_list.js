(function ($) {
	'use strict';

	$(function () {
		var $button = $('#clichaumeil-rfa-summary-rebuild-button');
		var $feedback = $('#clichaumeil-rfa-summary-rebuild-feedback');
		var $tokenField = $('input[name="token"]').first();
		var requestFailedMessage = $button.data('errorLabel') || 'Error';
		var loadingLabel = $button.data('loadingLabel') || 'Loading...';
		var reloadDelay = parseInt($button.data('reloadDelay'), 10) || 700;

		if (!$button.length || !$feedback.length || !$tokenField.length) {
			return;
		}

		var defaultHtml = $button.html();
		var loadingHtml = '<span class="fas fa-spinner fa-spin"></span> ' + loadingLabel;

		$button.on('click', function (event) {
			event.preventDefault();

			if ($button.hasClass('button-disabled')) {
				return;
			}

			$feedback.hide().removeClass('error warning ok').empty();
			$button.addClass('button-disabled').attr('aria-disabled', 'true').html(loadingHtml);

			$.ajax({
				url: $button.data('url'),
				type: 'POST',
				dataType: 'json',
				data: {
					mode: 'ajax',
					confirm: 'yes',
					yearid: $button.data('year'),
					token: $tokenField.val()
				}
			}).done(function (response) {
				if (!response || !response.success) {
					var errorMessage = response && response.message ? response.message : requestFailedMessage;
					$feedback.addClass('error').text(errorMessage).show();
					$button.removeClass('button-disabled').removeAttr('aria-disabled').html(defaultHtml);
					return;
				}

				$feedback.addClass('ok').text(response.message).show();
				window.setTimeout(function () {
					window.location.reload();
				}, reloadDelay);
			}).fail(function (xhr) {
				var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
				var errorMessage = response && response.message ? response.message : requestFailedMessage;
				$feedback.addClass('error').text(errorMessage).show();
				$button.removeClass('button-disabled').removeAttr('aria-disabled').html(defaultHtml);
			});
		});
	});
})(jQuery);
