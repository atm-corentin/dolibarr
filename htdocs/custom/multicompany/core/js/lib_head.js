// Copyright (C) 2014-2021 Regis Houssin	<regis.houssin@inodbox.com>
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// or see http://www.gnu.org/

//
// \file       /multicompany/core/js/lib_head.js
// \brief      File that include javascript functions (included if option use_javascript activated)
//

/*
 *
 */
function setMulticompanyConstant(url, code, input, entity, strict, forcereload, userid, token) {
	var saved_url = url;
	$.post( url, {
		action: "set",
		name: code,
		entity: entity,
		token: token
	},
	function() {
		console.log("url request success forcereload="+forcereload);
		$("#set_" + code).hide();
		$("#del_" + code).show();
		$.each(input, function(type, data) {
			// Enable another element
			if (type == "enabled") {
				$.each(data, function(key, value) {
					var newvalue=(value.search("^#") < 0 ? "#" : "") + value;
					$(newvalue).removeAttr("disabled");
				});
			// Disable another element
			} else if (type == "disabled") {
				$.each(data, function(key, value) {
					var newvalue=(value.search("^#") < 0 ? "#" : "") + value;
					$(newvalue).attr("disabled", true);
				});
			// enable and disable another element
			} else if (type == "disabledenabled") {
				$.each(data, function(key, value) {
					var newvalue=(value.search("^#") < 0 ? "#" : "") + value;
					$(newvalue).removeAttr("disabled");
				});
			// Show another element
			} else if (type == "showhide" || type == "show") {
				$.each(data, function(key, value) {
					var newvalue=(value.search("^#") < 0 ? "#" : "") + value;
					$(newvalue).show();
				});
			} else if (type == "hideshow") {
				$.each(data, function(key, value) {
					var newvalue=(value.search("^#") < 0 ? "#" : "") + value;
					$(newvalue).hide();
				});
			// Set another constant
			} else if (type == "set" || type == "del") {
				$.each(data, function(key, value) {
					if (type == "set") {
						$("#set_" + value).hide();
						$("#del_" + value).show();
						$.post( saved_url, {
							action: type,
							name: value,
							//name: key,
							//value: value,
							entity: entity,
							token: token
						});
					} else if (type == "del") {
						$("#del_" + value).hide();
						$("#set_" + value).show();
						$.post( saved_url, {
							action: type,
							name: value,
							entity: entity,
							token: token
						});
					}
				});
			// reload the current page
			} else if (type == "reload") {
				var url = window.location.pathname;
				location.href=url;
			}
		});
		if (forcereload) {
			location.reload();
		}
	}).fail(function(error) { location.reload(); });	/* When it fails, we always force reload to have setEventErrorMEssage in session visible */
}

/*
 *
 */
function delMulticompanyConstant(url, code, input, entity, strict, forcereload, userid, token) {
	var saved_url = url;
	$.post( url, {
		action: "del",
		name: code,
		entity: entity,
		token: token
	},
	function() {
		console.log("url request success forcereload="+forcereload);
		$("#del_" + code).hide();
		$("#set_" + code).show();
		$.each(input, function(type, data) {
			// Enable another element
			if (type == "enabled" && strict != 1) {
				$.each(data, function(key, value) {
					var newvalue=(value.search("^#") < 0 ? "#" : "") + value;
					$(newvalue).removeAttr("disabled");
				});
			// Disable another element
			} else if (type == "disabled") {
				$.each(data, function(key, value) {
					var newvalue=(value.search("^#") < 0 ? "#" : "") + value;
					$(newvalue).attr("disabled", true);
				});
			// enable and disable another element
			} else if (type == "disabledenabled") {
				$.each(data, function(key, value) {
					var newvalue=(value.search("^#") < 0 ? "#" : "") + value;
					$(newvalue).attr("disabled", true);
				});
			} else if (type == "showhide" || type == "hide") {
				$.each(data, function(key, value) {
					var newvalue=(value.search("^#") < 0 ? "#" : "") + value;
					$(newvalue).hide();
				});
			} else if (type == "hideshow") {
				$.each(data, function(key, value) {
					var newvalue=(value.search("^#") < 0 ? "#" : "") + value;
					$(newvalue).show();
				});
			// Delete another constant
			} else if (type == "set" || type == "del") {
				$.each(data, function(key, value) {
					if (type == "set") {
						$("#set_" + value).hide();
						$("#del_" + value).show();
						$.post( saved_url, {
							action: type,
							name: value,
							//name: key,
							//value: value,
							entity: entity,
							token: token
						});
					} else if (type == "del") {
						$("#del_" + value).hide();
						$("#set_" + value).show();
						$.post( saved_url, {
							action: type,
							name: value,
							entity: entity,
							token: token
						});
					}
				});
			// reload the current page
			} else if (type == "reload") {
				var url = window.location.pathname;
				location.href=url;
			}
		});
		if (forcereload) {
			location.reload();
		}
	}).fail(function(error) { location.reload(); });	/* When it fails, we always force reload to have setEventErrorMEssage in session visible */
}

/*
 *
 */
function confirmMulticompanyConstantAction(action, url, code, input, box, entity, yesButton, noButton, strict, userid, token) {
	var boxConfirm = box;
	$("#confirm_" + code)
			.attr("title", boxConfirm.title)
			.html(boxConfirm.content)
			.dialog({
				resizable: false,
				height: 180,
				width: 500,
				modal: true,
				buttons: [
					{
						id : 'yesButton_' + code,
						text : yesButton,
						click : function() {
							if (action == "set") {
								setMulticompanyConstant(url, code, input, entity, strict, 0, userid, token);
							} else if (action == "del") {
								delMulticompanyConstant(url, code, input, entity, strict, 0, userid, token);
							}
							// Close dialog
							$(this).dialog("close");
							// Execute another method
							if (boxConfirm.method) {
								var fnName = boxConfirm.method;
								if (window.hasOwnProperty(fnName)) {
									window[fnName]();
								}
							}
						}
					},
					{
						id : 'noButton_' + code,
						text : noButton,
						click : function() {
							$(this).dialog("close");
						}
					}
				]
			});
	// For information dialog box only, hide the noButton
	if (boxConfirm.info) {
		$("#noButton_" + code).button().hide();
	}
}
