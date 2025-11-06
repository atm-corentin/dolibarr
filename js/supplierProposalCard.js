/* Copyright (C) 2024 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Initialize supplier proposal card page
 * @param {object} config Configuration object
 * @param {string} config.ajaxUrl - URL for AJAX requests
 * @param {number} config.propalId - Supplier proposal ID
 * @param {string} config.token - Security token
 * @param {boolean} config.mandatoryFiles - Whether files are mandatory for validation
 * @param {string} config.errorFileUploadMsg - Error message for file upload
 */
function initSupplierProposalCard(config) {
	console.log("=== initSupplierProposalCard called ===");
	console.log("Config:", config);
	console.log("jQuery loaded:", typeof jQuery !== 'undefined');
	console.log("$ loaded:", typeof $ !== 'undefined');

	$(document).ready(function() {
		console.log("=== Document ready fired ===");
		console.log("Supplier Proposal price updater initialized");
		console.log("AJAX URL: " + config.ajaxUrl);
		console.log("Propal ID: " + config.propalId);

		// Initialize price updater
		initPriceUpdater(config);

		// Initialize validation handler if files are mandatory
		if (config.mandatoryFiles) {
			console.log("Initializing validation handler (mandatory files)");
			initValidationHandler(config);
		} else {
			console.log("Validation handler NOT initialized (files not mandatory)");
		}
	});
}

/**
 * Initialize price updater for lines
 * @param {object} config
 */
function initPriceUpdater(config) {
	console.log("Initializing price updater, looking for .line-price-input elements");
	var $inputs = $(".line-price-input");
	console.log("Found " + $inputs.length + " price input elements");

	$inputs.on("blur", function() {
		const $input = $(this);
		const lineId = $input.data("line-id");
		const newPrice = $input.val();

		console.log("Price input blur event - lineId:", lineId, "newPrice:", newPrice);

		// Show loading feedback
		$input.css("background-color", "#fcf8e3"); // warning yellow

		$.ajax({
			type: "POST",
			url: config.ajaxUrl,
			data: {
				action: "update_line_price",
				token: config.token,
				propalId: config.propalId,
				lineId: lineId,
				newPrice: newPrice
			},
			dataType: "json",
			timeout: 10000
		})
		.done(function(response) {
			console.log("AJAX response:", response);
			if (response && response.status === "success") {
				// Update totals on the page
				$("#line-total-" + lineId).text(response.lineTotalHtFormatted);
				$("#object-total-ht").text(response.objectTotalHtFormatted);
				// Show success feedback
				$input.css("background-color", "#dff0d8"); // success green
				setTimeout(function() {
					$input.css("background-color", "");
				}, 1000);
			} else {
				// Show error feedback
				console.error("AJAX error response:", response);
				$input.css("background-color", "#f2dede"); // error red
			}
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
			// Show network/server error feedback
			console.error("AJAX fail callback:", {
				status: jqXHR.status,
				statusText: textStatus,
				error: errorThrown,
				responseText: jqXHR.responseText
			});
			$input.css("background-color", "#f2dede"); // error red
		})
		.always(function() {
			console.log("AJAX always callback - Request completed");
		});
	});
}

/**
 * Initialize validation handler with mandatory file check
 * @param {object} config
 */
function initValidationHandler(config) {
	$("#btn-validate-proposal").on("click", function(e) {
		e.preventDefault();
		console.log("=== Validate button clicked ===");

		var form = $(this).closest("form");
		var fileInput = $("#addedfile");

		// Check if there are files to upload in the file input
		if (fileInput.length > 0 && fileInput[0].files.length > 0) {
			console.log("Files found in input, uploading first...");

			// Create FormData to upload file via AJAX
			var formData = new FormData();
			formData.append("action", "add-comment-file");
			formData.append("id", config.propalId);
			formData.append("token", config.token);

			// Add the file
			if (fileInput[0].files[0]) {
				formData.append("addedfile", fileInput[0].files[0]);
			}

			// Upload file via AJAX
			$.ajax({
				type: "POST",
				url: window.location.href,
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					console.log("File uploaded, now submitting validation");
					// After upload, submit the validation
					form.find("input[name=action]").remove();
					form.append('<input type="hidden" name="action" value="validate_proposal" />');
					form.submit();
				},
				error: function() {
					console.error("Error uploading file");
					alert(config.errorFileUploadMsg);
				}
			});
		} else {
			console.log("No file in input, submitting validation directly");
			// No file to upload, submit validation directly
			// Server-side will handle file check and show proper error message if needed
			form.find("input[name=action]").remove();
			form.append('<input type="hidden" name="action" value="validate_proposal" />');
			form.submit();
		}
	});
}
