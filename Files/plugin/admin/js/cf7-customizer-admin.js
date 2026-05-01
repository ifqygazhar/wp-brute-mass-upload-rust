(function( $ ) {
	'use strict';
	var editor;

	window.refreshTimer = false;

	$(document.body).on('click', '#cf7cstmzr_to_fw', function() {
		$(this).hide();
		var currentUrl = $('#cf7cstmzr-current-url').val();
		window.history.replaceState(null, null, currentUrl + '&fw=1');
		$('#cf7cstmzr-main-container').addClass('fw');
		$('#cf7cstmzr_exit_fw').show();
		$('body').addClass('my-body-noscroll-class');
		recalculateContainersSizes();
	});

	$(document.body).on('click', '#cf7cstmzr_exit_fw', function() {
		var currentUrl = $('#cf7cstmzr-current-url').val();
		window.history.replaceState(null, null, currentUrl);
		$(this).hide();
		$('#cf7cstmzr-main-container').removeClass('fw');
		$('#cf7cstmzr_to_fw').show();
		$('body').removeClass('my-body-noscroll-class');
		recalculateContainersSizes();
	});

	$(document.body).on('click', '.cf7cstmzr-preview-control-icons .dashicons', function() {
		var control = $(this);
		var container = $('#form-preview-container_inner');

		var styles = {
			width : '100%',
			height: '100%'
		};

		if (control.hasClass('dashicons-tablet')) {
			styles = {
				width : 768,
				height: 1024,
				"max-height" : '100%'
			};
		} else if (control.hasClass('dashicons-smartphone')) {
			styles = {
				width : 375,
				height: 667,
				"max-height" : '100%'
			};
		}

		container.css(styles);
	});

	$(document.body).on('click', '.cf7cstmzr-settings-item-header', function() {
		var header = $(this);
		var item = header.parents('.cf7cstmzr-settings-item');
		var body = item.find('.cf7cstmzr-settings-item-body');

		var items = $('.cf7cstmzr-settings-item');

		if (item.hasClass('active')) {
			item.removeClass('active');

			if (items.length > 0) {
				items.each(function () {
					$(this).removeClass('disabled');
				});
			}

			header.addClass('last-active');
		} else {
			if (items.length > 0) {
				items.each(function () {
					$(this).removeClass('active').addClass('disabled');
					$(this).find('.cf7cstmzr-settings-item-header').removeClass('last-active');
				});
			}

			item.removeClass('disabled').addClass('active');
		}

	});

	$(document.body).on('click', '.cf7cstmzr-settings-save', function() {
		var control = $('.cf7cstmzr-settings-save');
		var formData = $("#cf7cstmzr-settings-form").serializeArray();
		var titleInput = $('#cf7cstmzr-current-title');
		var slugInput = $('#cf7cstmzr-current-slug');
		var enableButton = $('#cf7cstmzr-enable-globally');
		var enableForm = $('#cf7cstmzr-enable-for-form');

		var data = {
			action: 'cf7cstmzr_save_form_customizer_settings',
			formData: formData
		};

		if (titleInput.length) {
			data.styleSchemeTitle = titleInput.val();
		}

		if (slugInput.length) {
			data.styleSchemeSlug = slugInput.val();
		}

		$.ajax({
			type: 'post',
			url: ajaxurl,
			data: data,
			success: function (response) {
				var decoded;

				try {
					decoded = $.parseJSON(response);
				} catch(err) {
					console.log(err);
					decoded = false;
				}

				if (decoded) {
					if (decoded.success) {
						control.attr('disabled', true).removeClass('button-success');
						enableButton.attr('disabled', false).addClass('button-success');
						enableForm.attr('disabled', false).addClass('button-success');
						// alert(decoded.message);
						alert_sticky(decoded.message, 'cf7cstmzr-success');
						refreshPreview();

						if (decoded.url) {
							setTimeout(function() {
								window.location.replace(decoded.url);
							}, 2000);
						}
					} else {
						// var fragments = response.message.fragments;
						// updateFragments(fragments);

						alert_sticky(decoded.message, 'cf7cstmzr-error');
						// alert(decoded.message);
					}
				} else {

					alert_sticky('Something went wrong', 'cf7cstmzr-error');
					// alert('Something went wrong');
				}
			}
		});
	});

	function alert_sticky(message, type) {
		var messageContainer = $('#cf7cstmzr-sticky-message');
		messageContainer.text(message).removeClass('cf7cstmzr-success').removeClass('cf7cstmzr-success').addClass(type).addClass('active');

		setTimeout(function () {
			messageContainer.removeClass('active');
		}, 3000);
	}

	$(document.body).on('click', '.cf7cstmzr-settings-reset', function() {
		resetStyleSettings();
	});

	$(document.body).on('click', '.cf7cstmzr-settings-default', function() {
		var control = $(this);
		var defaultSettings = control.data('default-settings');

		resetStyleSettings();

		console.log(defaultSettings);

		// Form Default Styling
		$('#cf7cstmzr_form_padding').next('.dashicons').click();
		$('#cf7cstmzr_form_padding-top').val(defaultSettings.form.padding.top);
		$('#cf7cstmzr_form_padding-right').val(defaultSettings.form.padding.right);
		$('#cf7cstmzr_form_padding-bottom').val(defaultSettings.form.padding.bottom);
		$('#cf7cstmzr_form_padding-left').val(defaultSettings.form.padding.left);

		$('#cf7cstmzr_form_margin').next('.dashicons').click();
		$('#cf7cstmzr_form_margin-top').val(defaultSettings.form.margin.top);
		$('#cf7cstmzr_form_margin-right').val(defaultSettings.form.margin.right);
		$('#cf7cstmzr_form_margin-bottom').val(defaultSettings.form.margin.bottom);
		$('#cf7cstmzr_form_margin-left').val(defaultSettings.form.margin.left);

		$('#cf7cstmzr_form_border_color').iris('color', defaultSettings.form.border.color);
		$('#cf7cstmzr_form_border_radius').val(defaultSettings.form.border.radius);

		$('#cf7cstmzr_form_border_width').next('.dashicons').click();
		$('#cf7cstmzr_form_border_width-top').val(defaultSettings.form.border.width.top);
		$('#cf7cstmzr_form_border_width-right').val(defaultSettings.form.border.width.right);
		$('#cf7cstmzr_form_border_width-bottom').val(defaultSettings.form.border.width.bottom);
		$('#cf7cstmzr_form_border_width-left').val(defaultSettings.form.border.width.left);

		// Input
		$('#cf7cstmzr_input_bg_color').iris('color', defaultSettings.input.bg.color);

		$('#cf7cstmzr_input_border_color').iris('color', defaultSettings.input.border.color);
		$('#cf7cstmzr_input_border_radius').val(defaultSettings.input.border.radius);

		$('#cf7cstmzr_input_border_width').next('.dashicons').click();
		$('#cf7cstmzr_input_border_width-top').val(defaultSettings.input.border.width.top);
		$('#cf7cstmzr_input_border_width-right').val(defaultSettings.input.border.width.right);
		$('#cf7cstmzr_input_border_width-bottom').val(defaultSettings.input.border.width.bottom);
		$('#cf7cstmzr_input_border_width-left').val(defaultSettings.input.border.width.left);

		$('#cf7cstmzr_input_padding').next('.dashicons').click();
		$('#cf7cstmzr_input_padding-top').val(defaultSettings.input.padding.top);
		$('#cf7cstmzr_input_padding-right').val(defaultSettings.input.padding.right);
		$('#cf7cstmzr_input_padding-bottom').val(defaultSettings.input.padding.bottom);
		$('#cf7cstmzr_input_padding-left').val(defaultSettings.input.padding.left);

		$('#cf7cstmzr_input_text_line-height').val(defaultSettings.input.text["line-height"]);

		if ('yes' === defaultSettings.input["full-width"]) {
			$('#cf7cstmzr_input_width_yes').prop('checked', true);
		} else {
			$('#cf7cstmzr_input_width_no').prop('checked', true);
		}

		// Button Default Styling
		$('#cf7cstmzr_button_bg_color').iris('color', defaultSettings.button.bg.color);
		$('#cf7cstmzr_button_bg_color-hover').iris('color', defaultSettings.button.bg["color-hover"]);

		$('#cf7cstmzr_button_border_color').iris('color', defaultSettings.button.border.color);
		$('#cf7cstmzr_button_border_color-hover').iris('color', defaultSettings.button.border["color-hover"]);
		$('#cf7cstmzr_button_border_radius').val(defaultSettings.button.border.radius);
		$('#cf7cstmzr_button_border_width').val(defaultSettings.button.border.width);

		$('#cf7cstmzr_button_padding').val(defaultSettings.button.padding);

		$('#cf7cstmzr_button_shadow_vertical-length').val(defaultSettings.button.shadow["vertical-length"]);
		$('#cf7cstmzr_button_shadow_blur-radius').val(defaultSettings.button.shadow["blur-radius"]);
		$('#cf7cstmzr_button_shadow_spread-radius').val(defaultSettings.button.shadow["spread-radius"]);
		$('#cf7cstmzr_button_shadow_opacity').val(defaultSettings.button.shadow["opacity"]);
		$('#cf7cstmzr_button_shadow_position').val(defaultSettings.button.shadow["position"]);
		$('#cf7cstmzr_button_shadow_color').iris('color', defaultSettings.button.shadow.color);

		$('#cf7cstmzr_button_text_color').iris('color', defaultSettings.button.text.color);
		$('#cf7cstmzr_button_text_color-hover').iris('color', defaultSettings.button.text["color-hover"]);
		$('#cf7cstmzr_button_text_line-height').val(defaultSettings.button.text["line-height"]);

		refreshPreview();
	});

	$(document.body).on('change', '#cf7cstmzr-preview-unstyle', function() {
		refreshPreview();
	});

	$(document.body).on('change', '#cf7cstmzr-preview-mode', function() {
		var selected = $(this).val();

		if ('split-mode' === selected) {
			$('#split-mode-settings').css('display', 'inline-block');
		} else {
			$('#split-mode-settings').css('display', 'none');
		}

		refreshPreview();
	});

	$(document.body).on('change', '#cf7cstmzr-load-body-tag', function() {
		var control = $(this);
		var loadBody = false;

		if (control.is(':checked')) {
			loadBody = true;
		}

		var data = {
			action: 'cf7cstmzr_load_body_tag',
			loadBody: loadBody,
		}

		ajaxRequest(data);
	});

	$(document.body).on('click', '#cf7cstmzr-enable-globally', function() {
		var control = $(this);
		var scheme = control.data('scheme');

		$.ajax({
			type: 'post',
			url: ajaxurl,
			data: {
				action: 'cf7cstmzr_enable_globally',
				scheme: scheme
			},
			success: function (response) {
				var decoded;

				try {
					decoded = $.parseJSON(response);
				} catch(err) {
					console.log(err);
					decoded = false;
				}

				if (decoded) {
					if (decoded.success) {
						// var fragments = response.message.fragments;
						// updateFragments(fragments);

						alert_sticky(decoded.message, 'cf7cstmzr-success');
						// alert(decoded.message);
						setTimeout(function() {
							window.location.reload();
						}, 2000);
					} else {
						alert_sticky(decoded.message, 'cf7cstmzr-error');
						// alert(decoded.message);
					}
				} else {
					alert_sticky('Something went wrong', 'cf7cstmzr-error');
					//alert('Something went wrong');
				}
			}
		});
	});

	$(document.body).on('click', '#cf7cstmzr-disable-globally', function() {
		var control = $(this);

		$.ajax({
			type: 'post',
			url: ajaxurl,
			data: {
				action: 'cf7cstmzr_disable_globally'
			},
			success: function (response) {
				var decoded;

				try {
					decoded = $.parseJSON(response);
				} catch(err) {
					console.log(err);
					decoded = false;
				}

				if (decoded) {
					if (decoded.success) {
						// var fragments = response.message.fragments;
						// updateFragments(fragments);

						alert_sticky(decoded.message, 'cf7cstmzr-success');
						// alert(decoded.message);
						setTimeout(function() {
							window.location.reload();
						}, 2000);
					} else {
						alert_sticky(decoded.message, 'cf7cstmzr-error');
						//alert(decoded.message);
					}
				} else {
					alert_sticky('Something went wrong', 'cf7cstmzr-error');
					// alert('Something went wrong');
				}
			}
		});
	});

	$(document.body).on('click', '#cf7cstmzr-enable-for-form', function() {
		var control = $(this);
		var scheme = control.data('scheme');
		var form = $('#cf7cstmzr_select_form').val();
		var mode = $('#cf7cstmzr_select_form').data('mode');

		$.ajax({
			type: 'post',
			url: ajaxurl,
			data: {
				action: 'cf7cstmzr_enable_for_form',
				scheme: scheme,
				form: form,
			},
			success: function (response) {
				var decoded;

				try {
					decoded = $.parseJSON(response);
				} catch(err) {
					console.log(err);
					decoded = false;
				}

				if (decoded) {
					if (decoded.success) {
						if ('free' === mode) {
							$('#cf7cstmzr_select_form option').each(function() {
								$(this).data('scheme', '');
							});
						}
						$('#cf7cstmzr_select_form option:selected').data('scheme', scheme);

						var single_form_description = $('#cf7cstmzr-select-single-form-description');

						if (single_form_description.length) {
							// console.log($('#cf7cstmzr_select_form option:selected').text());
							// console.log($('#cf7cstmzr_selected_style_scheme').text());
							single_form_description.data('form', form);
							single_form_description.data('form-title', $.trim($('#cf7cstmzr_select_form option:selected').text()));
							single_form_description.data('scheme', scheme);
							single_form_description.data('scheme-title', $.trim($('#cf7cstmzr_selected_style_scheme').text()));
						}

						show_select_form_message_button();
						alert_sticky(decoded.message, 'cf7cstmzr-success');
						// alert(decoded.message);
					} else {
						alert_sticky(decoded.message, 'cf7cstmzr-error');
						// alert(decoded.message);
					}
				} else {
					alert_sticky('Something went wrong', 'cf7cstmzr-error');
					// alert('Something went wrong');
				}
			}
		});
	});

	$(document.body).on('click', '#cf7cstmzr-disable-for-form', function() {
		var form = $('#cf7cstmzr_select_form').val();

		$.ajax({
			type: 'post',
			url: ajaxurl,
			data: {
				action: 'cf7cstmzr_disable_for_form',
				form: form,
			},
			success: function (response) {
				var decoded;

				try {
					decoded = $.parseJSON(response);
				} catch(err) {
					console.log(err);
					decoded = false;
				}

				if (decoded) {
					if (decoded.success) {
						$('#cf7cstmzr_select_form option:selected').data('scheme', '');

						var single_form_description = $('#cf7cstmzr-select-single-form-description');

						if (single_form_description.length) {
							single_form_description.data('form', '');
							single_form_description.data('form-title', '');
							single_form_description.data('scheme', '');
							single_form_description.data('scheme-title', '');
						}

						show_select_form_message_button();

						alert_sticky(decoded.message, 'cf7cstmzr-success');
						// alert(decoded.message);
					} else {
						alert_sticky(decoded.message, 'cf7cstmzr-error');
						// alert(decoded.message);
					}
				} else {
					alert_sticky('Something went wrong', 'cf7cstmzr-error');
					// alert('Something went wrong');
				}
			}
		});
	});

	$(document.body).on('click', '#cf7cstmzr-settings-save-as', function() {
		var control = $(this);
		var container = $('#cf7cstmzr-settings-create-new');
		var saveBtn = $('#cf7cstmzr-settings-save');
		var defaultBtn = $('#cf7cstmzr-settings-default');
		var deleteBtn = $('#cf7cstmzr-settings-delete');
		var resetBtn = $('#cf7cstmzr-settings-reset');

		control.hide();
		saveBtn.hide();
		defaultBtn.hide();
		deleteBtn.hide();
		resetBtn.hide();
		container.show();

		recalculateContainersSizes();
	});

	$(document.body).on('click', '#cf7cstmzr-settings-create', function() {
		var control = $('.cf7cstmzr-settings-save');
		var formData = $("#cf7cstmzr-settings-form").serializeArray();
		var title = $('#cf7cstmzr_settings_title_new').val();
		var copySettingsControl = $('#cf7cstmzr_settings_copy_new');
		var copySettings = false;
		var isFw = $('#cf7cstmzr-main-container').hasClass('fw');

		if (copySettingsControl.is(':checked')) {
			copySettings = true;
		}

		$.ajax({
			type: 'post',
			url: ajaxurl,
			data: {
				title: title,
				action: 'cf7cstmzr_new_form_customizer_settings',
				formData: formData,
				copySettings: copySettings,
				isFw: isFw
			},
			success: function (response) {
				var decoded;

				try {
					decoded = $.parseJSON(response);
				} catch(err) {
					console.log(err);
					decoded = false;
				}

				if (decoded) {
					if (decoded.success) {
						// var fragments = response.message.fragments;
						// updateFragments(fragments);
						alert_sticky(decoded.message, 'cf7cstmzr-success');
						// alert(decoded.message);

						setTimeout(function() {
							window.location.replace(decoded.url);
						}, 2000);
					} else {
						alert_sticky(decoded.message, 'cf7cstmzr-error');
						// alert(decoded.message);
					}
				} else {
					alert_sticky('Something went wrong', 'cf7cstmzr-error');
					// alert('Something went wrong');
				}
			}
		});
	});

	$(document.body).on('click', '#cf7cstmzr-settings-delete', function() {
		var scheme = $('#cf7cstmzr_select_style_scheme').val();
		var isFw = $('#cf7cstmzr-main-container').hasClass('fw');
		var data = {
			action: 'cf7cstmzr_delete_form_customizer_settings',
			scheme: scheme,
			isFw: isFw
		};

		ajaxRequest(data);
	});

	$(document.body).on('click', '.cf7cstmzr-close-welcome', function() {
		var data = {
			action: 'cf7cstmzr_close_welcome'
		};

		ajaxRequest(data);
	});

	$(document.body).on('click', '#cf7cstmzr-settings-save-as-cancel', function() {
		var control = $(this);
		var saveAsBtn = $('#cf7cstmzr-settings-save-as');
		var container = $('#cf7cstmzr-settings-create-new');

		var title = container.find('#cf7cstmzr_settings_title_new');

		title.val('');
		container.hide();
		saveAsBtn.show();

		var saveBtn = $('#cf7cstmzr-settings-save');
		var defaultBtn = $('#cf7cstmzr-settings-default');
		var deleteBtn = $('#cf7cstmzr-settings-delete');
		var resetBtn = $('#cf7cstmzr-settings-reset');

		saveBtn.show();
		defaultBtn.show();
		deleteBtn.show();
		resetBtn.show();

		recalculateContainersSizes();
	});

	$(document.body).on('click', '.cf7cstmzr-refresh', function() {
		var formData = $("#cf7cstmzr-settings-form").serializeArray();
		var previewButton = $('.cf7cstmzr-refresh');

		previewButton.each(function() {
			$(this).attr('disabled', true);
		});


		$.ajax({
			type: 'post',
			url: ajaxurl,
			data: {
				action: 'cf7cstmzr_preview_form_customizer_settings',
				formData: formData
			},
			success: function (response) {
				var decoded;

				try {
					decoded = $.parseJSON(response);
				} catch(err) {
					console.log(err);
					decoded = false;
				}

				if (decoded) {
					if (decoded.success) {
						var frame_top = $('#formPreviewFrame').contents().scrollTop();
						var formId = $('#cf7cstmzr_select_form').val();
						var url = $('#cf7cstmzr-url').val();

						var iframe = $( '<iframe />', {
							title: 'Inline Frame Example',
							id: 'formPreviewFrame'
						} );

						iframe.attr( 'src', url + formId );
						iframe.appendTo('#form-preview-container_inner');

						iframe.on("load", function() {
							$('#form-preview-container_inner iframe.previous-iframe' ).remove();
							$('#formPreviewFrame').addClass('previous-iframe');
							$('#formPreviewFrame').contents().scrollTop(frame_top);
						});
					} else {
						previewButton.each(function() {
							$(this).attr('disabled', false);
						});

						alert_sticky(decoded.message, 'cf7cstmzr-error');
						// alert(decoded.message);
					}
				} else {
					alert_sticky('Something went wrong', 'cf7cstmzr-error');
					// alert('Something went wrong');
				}
			}
		});
	});

	$(document.body).on('change', '#cf7cstmzr_select_form', function() {
		var form = $('#cf7cstmzr_select_form').val();

		$.ajax({
			type: 'post',
			url: ajaxurl,
			data: {
				action: 'cf7cstmzr_cache_form',
				form: form
			},
			success: function (response) {
				var decoded;

				try {
					decoded = $.parseJSON(response);
				} catch(err) {
					console.log(err);
					decoded = false;
				}

				if (decoded) {
					if (decoded.success) {
						// refreshPreview();
						show_select_form_message_button();
					} else {
						alert_sticky(decoded.message, 'cf7cstmzr-error');
						// alert(decoded.message);
					}
				} else {
					alert_sticky('Something went wrong', 'cf7cstmzr-error');
					// alert('Something went wrong');
				}
			}
		});
	});

	$(document.body).on('click', '.go-to-upgrade', function(e) {
		event.preventDefault();
		var saveButton = $('.cf7cstmzr-settings-save');
		var enableButton = $('#cf7cstmzr-enable-globally');
		var enableForm = $('#cf7cstmzr-enable-for-form');
		var url = $(this).attr('href');
		var confirmMessage = $(this).data('confirm');

		if (saveButton.hasClass('button-success')) {
			if (confirm(confirmMessage)) {
				var formData = $("#cf7cstmzr-settings-form").serializeArray();

				var titleInput = $('#cf7cstmzr-current-title');
				var slugInput = $('#cf7cstmzr-current-slug');

				var data = {
					action: 'cf7cstmzr_save_form_customizer_settings',
					formData: formData
				};

				if (titleInput.length) {
					data.styleSchemeTitle = titleInput.val();
				}

				if (slugInput.length) {
					data.styleSchemeSlug = slugInput.val();
				}

				$.ajax({
					type: 'post',
					url: ajaxurl,
					data: data,
					success: function (response) {
						var decoded;

						try {
							decoded = $.parseJSON(response);
						} catch(err) {
							console.log(err);
							decoded = false;
						}

						if (decoded) {
							if (decoded.success) {
								saveButton.attr('disabled', true).removeClass('button-success');
								enableButton.attr('disabled', false).addClass('button-success');
								enableForm.attr('disabled', false).addClass('button-success');
								window.open(url, '_blank');
							} else {
								// var fragments = response.message.fragments;
								// updateFragments(fragments);

								alert_sticky(decoded.message, 'cf7cstmzr-error');
								// alert(decoded.message);
							}
						} else {

							alert_sticky('Something went wrong', 'cf7cstmzr-error');
							// alert('Something went wrong');
						}
					}
				});
			} else {
				window.open(url, '_blank');
			}
		} else {
			window.open(url, '_blank');
		}
	});

	function show_select_form_message_button() {
		var enabled_message = $('#cf7cstmzr-enabled-message');
		var enabled_another_message = $('#cf7cstmzr-enabled-another-message');
		var disabled_message = $('#cf7cstmzr-disabled-message');
		var disabled_globally_message = $('#cf7cstmzr-disabled-globally-message');
		var enabled_button = $('#cf7cstmzr-enable-for-form');
		var disabled_button = $('#cf7cstmzr-disable-for-form');

		var selectedForm = $('#cf7cstmzr_select_form').val();
		var selectedFormScheme = $('#cf7cstmzr_select_form option:selected').data('scheme');
		var selectedFormSchemeTitle = $('#cf7cstmzr_select_form option:selected').data('scheme-title');
		var globalFormScheme = $('#cf7cstmzr_select_form option:selected').data('scheme-global');
		var globalFormSchemeTitle = $('#cf7cstmzr_select_form option:selected').data('scheme-global-title');
		var selectedScheme = $('#cf7cstmzr_select_style_scheme').val();

		if (!selectedScheme) {
			selectedScheme = $('#cf7cstmzr_selected_style_scheme').data('slug');
		}

		if ('' === selectedFormScheme) {
		    if (globalFormScheme === selectedFormScheme) {
                enabled_message.css('display', 'none');
                enabled_another_message.css('display', 'none');
                disabled_message.css('display', 'none');
                disabled_globally_message.css('display', 'inline-block');
                enabled_button.css('display', 'inline-block');
                disabled_button.css('display', 'none');
            } else {
                enabled_message.css('display', 'none');
                enabled_another_message.css('display', 'none');
                disabled_message.css('display', 'inline-block');
                disabled_globally_message.css('display', 'none');
                enabled_button.css('display', 'inline-block');
                disabled_button.css('display', 'none');
            }
        } else {
            if (selectedScheme === selectedFormScheme) {
                enabled_message.css('display', 'inline-block');
                enabled_another_message.css('display', 'none');
                disabled_message.css('display', 'none');
                disabled_globally_message.css('display', 'none');
                enabled_button.css('display', 'none');
                disabled_button.css('display', 'inline-block');
            } else {
                $('#cf7cstmzr-enabled-another-message').find('strong').text(selectedFormSchemeTitle);

                enabled_message.css('display', 'none');
                enabled_another_message.css('display', 'inline-block');
                disabled_message.css('display', 'none');
                disabled_globally_message.css('display', 'none');
                enabled_button.css('display', 'inline-block');
                disabled_button.css('display', 'none');
            }
        }

		var single_form_description = $('#cf7cstmzr-select-single-form-description');

		if (single_form_description.length) {
			var enabled_form = single_form_description.data('form');
			var enabled_form_title = single_form_description.data('form-title');
			var enabled_scheme = single_form_description.data('scheme');
			var enabled_scheme_title = single_form_description.data('scheme-title');
			var enabled_single_message = $('#cf7cstmzr-enabled-single-form');
			var disabled_single_message = $('#cf7cstmzr-disabled-single-form');

			$('#cf7cstmzr-styled_form_title').text(enabled_form_title);
			$('#cf7cstmzr-styled_form_style_title').text(enabled_scheme_title);

			if (enabled_scheme === '') {
				enabled_single_message.css('display', 'none');
				disabled_single_message.css('display', 'inline-block');
			} else if (selectedForm == enabled_form) {
				enabled_single_message.css('display', 'none');
				disabled_single_message.css('display', 'none');
			} else {
				enabled_single_message.css('display', 'inline-block');
				disabled_single_message.css('display', 'none');
			}
		}

		refreshPreview();
	}

	$(document.body).on('change', '#cf7cstmzr_select_style_scheme', function() {
		var isFw = $('#cf7cstmzr-main-container').hasClass('fw');

		var fw = '';

		if (isFw) {
			fw = '&fw=1';
		}
		var styleScheme = $(this).val();
		var saveButton = $('.cf7cstmzr-settings-save');
		var enableButton = $('#cf7cstmzr-enable-globally');
		var enableForm = $('#cf7cstmzr-enable-for-form');
		var confirmMessage = $(this).data('confirm');
		var url = $('#cf7cstmzr-admin-url').val();

		if (saveButton.hasClass('button-success')) {
			if (confirm(confirmMessage)) {
				var formData = $("#cf7cstmzr-settings-form").serializeArray();

				var titleInput = $('#cf7cstmzr-current-title');
				var slugInput = $('#cf7cstmzr-current-slug');

				var data = {
					action: 'cf7cstmzr_save_form_customizer_settings',
					formData: formData
				};

				if (titleInput.length) {
					data.styleSchemeTitle = titleInput.val();
				}

				if (slugInput.length) {
					data.styleSchemeSlug = slugInput.val();
				}

				$.ajax({
					type: 'post',
					url: ajaxurl,
					data: data,
					success: function (response) {
						var decoded;

						try {
							decoded = $.parseJSON(response);
						} catch(err) {
							console.log(err);
							decoded = false;
						}

						if (decoded) {
							if (decoded.success) {
								saveButton.attr('disabled', true).removeClass('button-success');
								enableButton.attr('disabled', false).addClass('button-success');
								enableForm.attr('disabled', false).addClass('button-success');
								window.location.replace(url + '&style_scheme=' + styleScheme + fw);
							} else {
								// var fragments = response.message.fragments;
								// updateFragments(fragments);

								alert_sticky(decoded.message, 'cf7cstmzr-error');
								// alert(decoded.message);
							}
						} else {

							alert_sticky('Something went wrong', 'cf7cstmzr-error');
							// alert('Something went wrong');
						}
					}
				});
			} else {
				window.location.replace(url + '&style_scheme=' + styleScheme + fw);
			}
		} else {
			window.location.replace(url + '&style_scheme=' + styleScheme + fw);
		}
	});

	$(document.body).on('click', '#cf7cstmzr_upload_image_btn', function() {
		var button = $(this),
			custom_uploader = wp.media({
				title: 'Insert image',
				library : {
					// uncomment the next line if you want to attach image to the current post
					// uploadedTo : wp.media.view.settings.post.id,
					type : 'image'
				},
				button: {
					text: 'Use this image' // button label text
				},
				multiple: false // for multiple image selection set to true
			}).on('select', function() { // it also has "open" and "close" events
				var attachment = custom_uploader.state().get('selection').first().toJSON();
				var imgHolder = $('#cf7cstmzr_uploaded_image_holder');
				var noSave = imgHolder.data('save');
				imgHolder.html('<img class="true_pre_image" src="' + attachment.url + '" />').next().val(attachment.id);
				$('#cf7cstmzr_upload_image_btn').hide();
				$('#cf7cstmzr_delete_image_btn').show();
				// $('#cf7cstmzr_form_bg_img-opacity-holder').show();
				// $('#cf7cstmzr_form_bg_img-size-holder').show();
				// $('#cf7cstmzr_form_bg_img-position-holder').show();
				$('#cf7cstmzr_form_bg_img-opacity').attr('disabled', false).val('0.5');
				$('#cf7cstmzr_form_bg_img-size').attr('disabled', false).val('no-repeat');
				$('#cf7cstmzr_form_bg_img-position_center').attr('checked', true);
                var positionChecboxes = $('.cf7cstmzr_position');

                if (positionChecboxes.length) {
                    positionChecboxes.each(function() {
                        $(this).attr('disabled', false);
                    });
                }

				var isSave = true;

				if ('not-save' === $.trim(noSave)) {
					isSave = false;
				}

				$( document.body ).trigger('cf7cstmzr_settings_changed', isSave);

				recalculateContainersSizes();
			})
				.open();
	});

	$(document.body).on('click', '#cf7cstmzr_delete_image_btn', function() {
		var imgHolder = $('#cf7cstmzr_uploaded_image_holder');
		var noSave = imgHolder.data('save');
		imgHolder.empty().next().val('');
		$('#cf7cstmzr_upload_image_btn').show();
		$('#cf7cstmzr_delete_image_btn').hide();
		// $('#cf7cstmzr_form_bg_img-opacity-holder').hide();
		// $('#cf7cstmzr_form_bg_img-size-holder').hide();
		// $('#cf7cstmzr_form_bg_img-position-holder').hide();
		$('#cf7cstmzr_form_bg_img-opacity').val('').attr('disabled', true);
		$('#cf7cstmzr_form_bg_img-size').val('').attr('disabled', true);
		var positionChecboxes = $('.cf7cstmzr_position');

		if (positionChecboxes.length) {
			positionChecboxes.each(function() {
				$(this).attr('checked', false).attr('disabled', true);
			});
		}

		var isSave = true;

		if ('not-save' === $.trim(noSave)) {
			isSave = false;
		}

		$( document.body ).trigger('cf7cstmzr_settings_changed', isSave);

        recalculateContainersSizes();
	});

	$(document.body).on('cf7cstmzr_settings_changed', function(event, isSave) {
		var saveButton = $('#cf7cstmzr-settings-save');
		var previewButton = $('.cf7cstmzr-refresh');
		var enableButton = $('#cf7cstmzr-enable-globally');
		var enableForm = $('#cf7cstmzr-enable-for-form');

		if (isSave) {
			saveButton.attr('disabled', false).addClass('button-success');
			enableButton.attr('disabled', true).removeClass('button-success');
			enableForm.attr('disabled', true).removeClass('button-success');
		}

		previewButton.each(function() {
			$(this).attr('disabled', false);
		});

		setTimerForRefreshPreview();
	});

	$(document.body).on('click', '.cf7cstmzr-description-togler', function() {
		var control = $(this);
		var target = control.data('target');
		var targetContainer = $('#' + target);

		if (targetContainer.length) {
			targetContainer.slideToggle(0, function() {
				recalculateContainersSizes();
			});
		}
	});

	$(document.body).on('change', '.cf7cstmzr-parent-input', function() {
		var input = $(this);
		var inputVal = input.val();
		var parent = input.parents('.cf7cstmzr-settings-item-body-setting');
		var children = parent.find('.cf7cstmzr-settings-item-body-setting-variations input');

		if (children.length) {
			children.each(function() {
				$(this).val(inputVal);

				$( document.body ).trigger('cf7cstmzr_settings_changed', true);
			});
		}
	});

	$(document.body).on('click', '.cf7cstmzr-settings-item-body-setting-variations-main .dashicons', function() {
		var control = $(this).parents('.cf7cstmzr-settings-item-body-setting-variations-main');
		var parent = control.parents('.cf7cstmzr-settings-item-body-setting');
		var variations = parent.find('.cf7cstmzr-settings-item-body-setting-variations');

		control.hide();
		variations.show();

		recalculateContainersSizes();
	});

	$(document.body).on('change', '#cf7cstmzr_form_padding, ' +
		'#cf7cstmzr_form_text_size, ' +
		'#cf7cstmzr_form_text_label-size, ' +
		'#cf7cstmzr_form_text_label-weight, ' +
		'#cf7cstmzr_form_text_label-style, ' +
		'#cf7cstmzr_form_text_font-family, ' +
		'#cf7cstmzr_form_bg_img-opacity, ' +
		'#cf7cstmzr_form_bg_img-size, ' +
		'.cf7cstmzr_position, ' +
		'#cf7cstmzr_form_padding-top, ' +
		'#cf7cstmzr_form_padding-right, ' +
		'#cf7cstmzr_form_padding-bottom, ' +
		'#cf7cstmzr_form_padding-left, ' +
		'#cf7cstmzr_form_margin-top, ' +
		'#cf7cstmzr_form_margin-right, ' +
		'#cf7cstmzr_form_margin-bottom, ' +
		'#cf7cstmzr_form_margin-left, ' +
		'#cf7cstmzr_form_border_width-top, ' +
		'#cf7cstmzr_form_border_width-right, ' +
		'#cf7cstmzr_form_border_width-bottom, ' +
		'#cf7cstmzr_form_border_width-left, ' +
		'#cf7cstmzr_form_border_type, ' +
		'#cf7cstmzr_form_border_radius, ' +
		'#cf7cstmzr_form_shadow_horizontal-length, ' +
		'#cf7cstmzr_form_shadow_vertical-length, ' +
		'#cf7cstmzr_form_shadow_blur-radius, ' +
		'#cf7cstmzr_form_shadow_spread-radius, ' +
		'#cf7cstmzr_form_shadow_opacity, ' +
		'#cf7cstmzr_form_shadow_position, ' +
		'#cf7cstmzr_input_width_yes, ' +
		'#cf7cstmzr_input_width_no, ' +
		'#cf7cstmzr_input_padding-top, ' +
		'#cf7cstmzr_input_padding-right, ' +
		'#cf7cstmzr_input_padding-bottom, ' +
		'#cf7cstmzr_input_padding-left, ' +
		'#cf7cstmzr_input_margin-top, ' +
		'#cf7cstmzr_input_margin-right, ' +
		'#cf7cstmzr_input_margin-bottom, ' +
		'#cf7cstmzr_input_margin-left, ' +
		'#cf7cstmzr_input_text_size, ' +
		'#cf7cstmzr_input_bg_color-opacity, ' +
		'#cf7cstmzr_input_text_line-height, ' +
		'#cf7cstmzr_input_border_width-top, ' +
		'#cf7cstmzr_input_border_width-right, ' +
		'#cf7cstmzr_input_border_width-bottom, ' +
		'#cf7cstmzr_input_border_width-left, ' +
		'#cf7cstmzr_input_border_radius, ' +
		'#cf7cstmzr_input_shadow_horizontal-length, ' +
		'#cf7cstmzr_input_shadow_vertical-length, ' +
		'#cf7cstmzr_input_shadow_blur-radius, ' +
		'#cf7cstmzr_input_shadow_spread-radius, ' +
		'#cf7cstmzr_input_shadow_opacity, ' +
		'#cf7cstmzr_input_shadow_position, ' +
		'#cf7cstmzr_button_shadow_horizontal-length, ' +
		'#cf7cstmzr_button_shadow_vertical-length, ' +
		'#cf7cstmzr_button_shadow_blur-radius, ' +
		'#cf7cstmzr_button_shadow_spread-radius, ' +
		'#cf7cstmzr_button_shadow_opacity, ' +
		'#cf7cstmzr_button_shadow_position, ' +
		'#cf7cstmzr_input_border_color, ' +
		'#cf7cstmzr_checkbox_width_yes, ' +
		'#cf7cstmzr_checkbox_width_no, ' +
		'#cf7cstmzr_radiobutton_width_yes, ' +
		'#cf7cstmzr_radiobutton_width_no, ' +
		'#cf7cstmzr_checkbox_text_label-size, ' +
		'#cf7cstmzr_button_width_yes, ' +
		'#cf7cstmzr_button_width_no, ' +
		'#cf7cstmzr_button_text_size, ' +
		'#cf7cstmzr_button_text_line-height, ' +
		'#cf7cstmzr_button_padding, ' +
		'#cf7cstmzr_button_border_width, ' +
		'#cf7cstmzr_button_border_radius',
		function() {
		var control = $(this);
		var isSave = true;

		if (control.hasClass('not-save')) {
			isSave = false;
		}

		$( document.body ).trigger('cf7cstmzr_settings_changed', isSave);
	});

	$(document.body).on('click', '.cf7cstmzr-install-plugin', function () {
		var btn = $(this);
		var plugin = btn.data('plugin');

		var processingHolder = $('.processing-holder .processing-spinner-holder');
		processingHolder.addClass('processing');

		var data = {
			action: 'cf7cstmzr_install_plugin',
			plugin: plugin
		};

		$.ajax({
			type: 'post',
			url: ajaxurl,
			data: data,

			success: function(response) {
				response = response.split('&&&&&');
				console.log(response);
				var decoded;

				try {
					decoded = $.parseJSON(response[1]);
				} catch(err) {
					console.log(err);
					decoded = false;
				}

				if (decoded) {
					if (decoded.success) {
						// alert(decoded.message);
						alert_sticky(decoded.message, 'cf7cstmzr-success');

						setTimeout(function() {
							window.location.reload(false);
						}, 2000);
					} else {
						alert_sticky(decoded.message, 'cf7cstmzr-error');
						processingHolder.removeClass('processing');
					}
				} else {
					alert_sticky('Something went wrong', 'cf7cstmzr-error');
					processingHolder.removeClass('processing');
				}
			}
		});
	});

	$(document).ready(function () {
		localStorage.removeItem('split-sizes');
		var cstmzrForm = $('#cf7cstmzr-settings-form');

		if (!cstmzrForm.length) {
			return true;
		}

		responsiveClasses();
		recalculateContainersSizes();
		show_select_form_message_button();

		$('.cf7cstmzr-color-picker').wpColorPicker(
			{
				change: function(event, ui){
					$( document.body ).trigger('cf7cstmzr_settings_changed', true);
				},
				clear: function() {
					$( document.body ).trigger('cf7cstmzr_settings_changed', true);
				}
			}
		);

		if( $('#cf7cstmzr_custom-css').length ) {
			// var editor = wp.codeEditor.initialize( $('#cf7cstmzr_custom-css'), editorSettings );
			var editorSettings = wp.codeEditor.defaultSettings ? _.clone( wp.codeEditor.defaultSettings ) : {};

			editorSettings.codemirror = _.extend(
				{},
				editorSettings.codemirror,
				{
					indentUnit: 2,
					tabSize: 2,
					mode: 'css',
				}
			);

			editor = wp.codeEditor.initialize($('#cf7cstmzr_custom-css'), editorSettings);
			// editor.codemirror.on( 'blur', onCodeEditorChange );
			editor.codemirror.on( 'change', onCodeEditorChange );
			window.codeEditor = editor;
		}
	});

	$(window).on( 'resize', function() {
		responsiveClasses();
		recalculateContainersSizes();
	} );

	function recalculateContainersSizes() {
		var mainContainer = $("#cf7cstmzr-main-container");

		if (!mainContainer.length) {
			return true;
		}

		var windowHeight = $(window).height();
		var mainOffset = $("#cf7cstmzr-main-container").offset().top;
		mainOffset = 40;
		var mainPosition = $("#cf7cstmzr-main-container").position().top;

		if ($("#cf7cstmzr-main-container").hasClass('fw')) {
            var heightOffset = windowHeight;
            var heightPosition = windowHeight;
		} else {
            var heightOffset = windowHeight - mainOffset - 15;
            var heightPosition = windowHeight - mainPosition - 15;
        }

		var settingsContainerHeader = $('#cf7cstmzr-settings-container-header');
		var settingsContainerBody = $('#cf7cstmzr-settings-container-body');

		var previewContainerHeader = $('#cf7cstmzr-preview-container-header');
		var previewContainerBody = $('#cf7cstmzr-preview-container-body');

		var settingsContainerHeaderHeight = settingsContainerHeader.outerHeight(true);
		var previewContainerHeaderHeight = previewContainerHeader.outerHeight(true);

		var settingsContainerBodyHeight = heightOffset - settingsContainerHeaderHeight - 10;
		var previewContainerBodyHeight = heightOffset - previewContainerHeaderHeight - 5;

		var settingItems = $('.cf7cstmzr-settings-item');

		if ($('body').hasClass('device-xl') || $('body').hasClass('device-lg')) {
			$("#cf7cstmzr-main-container").css('height', heightOffset);

			if (settingItems.length) {
				settingItems.each(function() {
					var settingItem = $(this);
					var settingItemHeader = settingItem.find('.cf7cstmzr-settings-item-header');
					var settingItemHeaderHeight = settingItemHeader.outerHeight(true);
					var settingItemBody = settingItem.find('.cf7cstmzr-settings-item-body');
					var settingItemBodyHeight = settingsContainerBodyHeight - settingItemHeaderHeight;

					settingItemBody.css('height', settingItemBodyHeight).css('overflow', 'auto');
				});
			}

			settingsContainerBody.css('height', settingsContainerBodyHeight).css('overflow', 'auto');
			previewContainerBody.css('height', previewContainerBodyHeight);
		} else {
			if (settingItems.length) {
				settingItems.each(function() {
					var settingItem = $(this);
					var settingItemHeader = settingItem.find('.cf7cstmzr-settings-item-header');
					var settingItemHeaderHeight = settingItemHeader.outerHeight(true);
					var settingItemBody = settingItem.find('.cf7cstmzr-settings-item-body');
					var settingItemBodyHeight = settingsContainerBodyHeight - settingItemHeaderHeight;

					settingItemBody.css('height', 'auto');
				});
			}

			$("#cf7cstmzr-main-container").css('height', 'auto');
			settingsContainerBody.css('height', 'auto');
			previewContainerBody.css('height', heightOffset);
		}
	}

	$(document.body).on('click', '#cf7cstmzr_custom_css_save', function() {
		$(this).removeClass('button-primary').attr('disabled', true);

		var value = window.codeEditor.codemirror.getValue();

		if (!$.trim(value)) {
			$('#cf7cstmzr_custom_css_clear').removeClass('button-primary').attr('disabled', true);
		} else {
			$('#cf7cstmzr_custom_css_clear').addClass('button-primary').attr('disabled', false);
		}

		$( document.body ).trigger('cf7cstmzr_settings_changed', true);
	});

	$(document.body).on('change', "input[name='cf7cstmzr-split-mode']", function() {
		refreshPreview();
	});

	$(document.body).on('change', '#cf7cstmzr-split-fixed', function() {
		var frame = $('#formPreviewFrame').contents();

		if ($(this).is(':checked')) {
			frame.find('#split-container').removeClass('float-split').addClass('scroll-split');
		} else {
			frame.find('#split-container').removeClass('scroll-split').addClass('float-split');
		}
	});

	$(document.body).on('change', '#cf7cstmzr-preview-mode-check', function() {
		if ($(this).is(':checked')) {
			$('#cf7cstmzr-preview-mode').val('split-mode');
		} else {
			$('#cf7cstmzr-preview-mode').val('current-style');
		}

		$( '#cf7cstmzr-preview-mode' ).trigger( 'change' );
	});

	$(document.body).on('click', '#cf7cstmzr_custom_css_clear', function() {
		window.codeEditor.codemirror.setValue('');
		$('#cf7cstmzr_custom-css').text('');

		$(this).removeClass('button-primary').attr('disabled', true);
		$('#cf7cstmzr_custom_css_save').removeClass('button-primary').attr('disabled', true);

		$( document.body ).trigger('cf7cstmzr_settings_changed', true);
	});

	function onCodeEditorChange(instance) {
		var value = instance.getValue();

		$('#cf7cstmzr_custom-css').text( value );
		$('#cf7cstmzr_custom_css_save').addClass('button-primary').attr('disabled', false);

		if (!$.trim(value)) {
			$('#cf7cstmzr_custom_css_clear').removeClass('button-primary').attr('disabled', true);
		} else {
			$('#cf7cstmzr_custom_css_clear').addClass('button-primary').attr('disabled', false);
		}
	}

	function responsiveClasses(){

		if( typeof jRespond === 'undefined' ) {
			console.log('responsiveClasses: jRespond not Defined.');
			return true;
		}

		var jRes = jRespond([
			{
				label: 'smallest',
				enter: 0,
				exit: 575
			},{
				label: 'handheld',
				enter: 576,
				exit: 767
			},{
				label: 'tablet',
				enter: 768,
				exit: 960
			},{
				label: 'laptop',
				enter: 961,
				exit: 1199
			},{
				label: 'desktop',
				enter: 1200,
				exit: 10000
			}
		]);
		jRes.addFunc([
			{
				breakpoint: 'desktop',
				enter: function() { $('body').addClass('device-xl'); },
				exit: function() { $('body').removeClass('device-xl'); }
			},{
				breakpoint: 'laptop',
				enter: function() { $('body').addClass('device-lg'); },
				exit: function() { $('body').removeClass('device-lg'); }
			},{
				breakpoint: 'tablet',
				enter: function() { $('body').addClass('device-md'); },
				exit: function() { $('body').removeClass('device-md'); }
			},{
				breakpoint: 'handheld',
				enter: function() { $('body').addClass('device-sm'); },
				exit: function() { $('body').removeClass('device-sm'); }
			},{
				breakpoint: 'smallest',
				enter: function() { $('body').addClass('device-xs'); },
				exit: function() { $('body').removeClass('device-xs'); }
			}
		]);
	}

	function resetStyleSettings() {
		var parentTextFields = $('.cf7cstmzr-parent-text-field');
		var textFields = $('.cf7cstmzr-text-field');
		var dropdownFields = $('.cf7cstmzr-dropdown-field');
		var radioFields = $('.cf7cstmzr-radio-field');

		radioFields.each(function() {
			$(this).prop( "checked", false );
		});

		parentTextFields.each(function() {
			$(this).val('');
		});

		dropdownFields.each(function() {
			$(this).val('');
		});

		// var variationsMain = $('.cf7cstmzr-settings-item-body-setting-variations-main');
		// var variationsSetting = parent.find('.cf7cstmzr-settings-item-body-setting-variations');

		textFields.each(function() {
			$(this).val('');
		});

		var variationsMain = $('.cf7cstmzr-settings-item-body-setting-variations-main');
		var variationsSetting = $('.cf7cstmzr-settings-item-body-setting-variations');

		variationsMain.each(function() {
			$(this).show();
		});

		variationsSetting.each(function() {
			$(this).hide();
		});

		var clearColorBtn = $('.cf7cstmzr-color-picker').parents('.wp-picker-input-wrap').find('.wp-picker-clear');

		clearColorBtn.each(function() {
			$(this).click();
		});

		window.codeEditor.codemirror.setValue('');
		$('#cf7cstmzr_custom-css').text('');

		$('#cf7cstmzr_delete_image_btn').click();

		recalculateContainersSizes();
	}

	function refreshPreview() {
		console.log('Preview rtefresh');
		clearTimeout( window.refreshTimer );

		var formData = $("#cf7cstmzr-settings-form").serializeArray();
		var previewButton = $('.cf7cstmzr-refresh');

		previewButton.each(function() {
			$(this).attr('disabled', true);
		});

		var unstyleControl = $('#cf7cstmzr-preview-unstyle');
		var unstyle = false;
		var splitModeControl = $('#cf7cstmzr-split-mode');
		var splitMode = false;

		if (unstyleControl.is(':checked')) {
			unstyle = true;
		}

		if (splitModeControl.is(':checked')) {
			splitMode = true;
		}

		var splitModeValue = $("input[name='cf7cstmzr-split-mode']:checked").val();

		var previewMode = $('#cf7cstmzr-preview-mode').val();


		$.ajax({
			type: 'post',
			url: ajaxurl,
			data: {
				action: 'cf7cstmzr_preview_form_customizer_settings',
				formData: formData,
				unstyle: unstyle,
				previewMode: previewMode,
				splitMode: splitMode,
				splitModeValue:splitModeValue
			},
			success: function (response) {
				var decoded;

				try {
					decoded = $.parseJSON(response);
				} catch(err) {
					console.log(err);
					decoded = false;
				}

				if (decoded) {
					if (decoded.success) {
						$('#cf7cstmzr-split-fixed').attr('checked', false);
						var frame_top = $('#formPreviewFrame').contents().scrollTop();
						var formId = $('#cf7cstmzr_select_form').val();
						var url = $('#cf7cstmzr-url').val();

						var iframe = $( '<iframe />', {
							title: 'Inline Frame Example',
							id: 'formPreviewFrame'
						} );

						iframe.attr( 'src', url + formId );
						iframe.appendTo('#form-preview-container_inner');

						iframe.on("load", function() {
							$('#form-preview-container_inner iframe.previous-iframe' ).remove();
							$('#formPreviewFrame').addClass('previous-iframe');
							$('#formPreviewFrame').contents().scrollTop(frame_top);
						});
					} else {
						previewButton.each(function() {
							$(this).attr('disabled', false);
						});
						alert_sticky(decoded.message, 'cf7cstmzr-error');
						// alert(decoded.message);
					}
				} else {
					alert_sticky('Something went wrong', 'cf7cstmzr-error');
				}
			}
		});
	}

	function setTimerForRefreshPreview() {
		resetTimer();
		window.refreshTimer = setTimeout( maybeRefreshPreview, '500' );
	}

	function resetTimer() {
		clearTimeout( window.refreshTimer );
	}

	function maybeRefreshPreview() {
		refreshPreview();
	}

	function ajaxRequest(data, cb, cbError) {
		$.ajax({
			type: 'post',
			url: ajaxurl,
			data: data,
			success: function (response) {
				var decoded;

				try {
					decoded = $.parseJSON(response);
				} catch(err) {
					console.log(err);
					decoded = false;
				}

				if (decoded) {
					if (decoded.success) {
						if (decoded.message) {
							alert_sticky(decoded.message, 'cf7cstmzr-success');
							// alert(decoded.message);
						}

						if (decoded.url) {
							if (decoded.message) {
								setTimeout(function() {
									window.location.replace(decoded.url);
								}, 2000);
							} else {
								window.location.replace(decoded.url);
							}
						}

						if (typeof cbError === 'function') {
							cbError();
						}
					} else {
						if (decoded.message) {
							alert_sticky(decoded.message, 'cf7cstmzr-error');
							// alert(decoded.message);
						}

						if (typeof cbError === 'function') {
							cbError();
						}
					}
				} else {
					alert_sticky('Something went wrong', 'cf7cstmzr-error');
					// alert('Something went wrong');
				}
			}
		});
	}

	/**
	 *
	 * @param e
	 * @param fragments
	 */
	function updateFragments( fragments ) {
		if ( fragments ) {
			$.each( fragments, function( key ) {
				$( key )
					.addClass( 'updating' )
					.fadeTo( '400', '0.6' )
					.block({
						message: null,
						overlayCSS: {
							opacity: 0.6
						}
					});
			});

			$.each( fragments, function( key, value ) {
				$( key ).replaceWith( value );
				$( key ).stop( true ).css( 'opacity', '1' ).unblock();
			});
		}
	}
})( jQuery );
