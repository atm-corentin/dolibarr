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
 * @param {number} config.maxFileSize - Maximum file size in bytes
 * @param {string} config.maxFileSizeFormatted - Maximum file size formatted (e.g., "8M")
 * @param {string} config.fileTooLargeMsg - Error message for file too large
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

		// Block form submission on Enter key in input fields
		initEnterKeyBlocker();

		// Initialize file size validator
		initFileSizeValidator(config);

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

			// Validate file size BEFORE upload
			var file = fileInput[0].files[0];
			if (!validateFileSize(file, config)) {
				return; // Stop if file is too large
			}

			// Create FormData to upload file via AJAX
			var formData = new FormData();
			formData.append("action", "add-comment-file");
			formData.append("id", config.propalId);
			formData.append("token", config.token);
			formData.append("addedfile", file);

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
				error: function(jqXHR, textStatus, errorThrown) {
					console.error("Error uploading file:", textStatus, errorThrown);

					// Handle specific HTTP errors
					if (jqXHR.status === 413) {
						// Request Entity Too Large - reload page with error message
						sendErrorToServer(config, 'FILE_TOO_LARGE');
					} else {
						// Other upload error - reload page with error message
						sendErrorToServer(config, 'UPLOAD_ERROR');
					}
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

/**
 * Block form submission when Enter key is pressed in input fields
 * This prevents accidental form submission when users are entering data
 */
function initEnterKeyBlocker() {
	console.log("Initializing Enter key blocker for input fields");

	// Block Enter key on all input fields (text, number, etc.) but NOT textareas
	$("form").on("keydown", "input:not([type=submit]):not([type=button])", function(e) {
		if (e.which === 13 || e.keyCode === 13) {
			console.log("Enter key pressed in input field, blocking form submission");
			e.preventDefault();

			// Optionally, trigger blur to save the value (useful for price inputs)
			if ($(this).hasClass("line-price-input")) {
				console.log("Triggering blur on price input to save changes");
				$(this).blur();
			}

			return false;
		}
	});
}

/**
 * Initialize file size validator for file inputs
 * Checks file size before form submission to prevent "Request Entity Too Large" errors
 * @param {object} config Configuration object with maxFileSize and fileTooLargeMsg
 */
function initFileSizeValidator(config) {
	console.log("Initializing file size validator");
	console.log("Max file size:", config.maxFileSize, "bytes");

	// Validate on file input change
	$("input[type=file]").on("change", function() {
		var input = this;
		if (input.files && input.files.length > 0) {
			var file = input.files[0];
			console.log("File selected:", file.name, "Size:", file.size, "bytes");

			if (!validateFileSize(file, config)) {
				// Clear the file input
				$(input).val("");
				// Send error to server to display via setEventMessages
				sendErrorToServer(config, 'FILE_TOO_LARGE');
			}
		}
	});
}

/**
 * Validate file size against maximum allowed size
 * @param {File} file File object to validate
 * @param {object} config Configuration object with maxFileSize and fileTooLargeMsg
 * @return {boolean} True if valid, false if too large
 */
function validateFileSize(file, config) {
	if (!file || !config.maxFileSize) {
		return true; // No validation if file or config missing
	}

	if (file.size > config.maxFileSize) {
		console.error("File too large:", file.size, "bytes > max:", config.maxFileSize, "bytes");
		return false;
	}

	return true;
}

/**
 * Send error to server to display via setEventMessages (page reload)
 * This ensures messages are displayed uniformly using Dolibarr's native system
 * @param {object} config Configuration object with propalId
 * @param {string} errorCode Error code (FILE_TOO_LARGE, UPLOAD_ERROR, etc.)
 */
function sendErrorToServer(config, errorCode) {
	console.log("Sending error to server:", errorCode);

	// Build redirect URL with error parameter
	var url = window.location.pathname + window.location.search;

	// Add or update error parameter
	if (url.indexOf('?') === -1) {
		url += '?';
	} else {
		url += '&';
	}
	url += 'file_error=' + encodeURIComponent(errorCode);

	// Reload page with error parameter (server will display message via setEventMessages)
	window.location.href = url;
}
