/**
 * HSG Campaign Manager admin UI.
 *
 * @package HSGCampaignManager
 */

(function ($) {
	'use strict';

	var $form = $('#hsgcm-campaign-form');
	var $products = $('#hsgcm-products');
	var $notice = $('#hsgcm-notice');
	var i18n = (window.hsgcmAdmin && hsgcmAdmin.i18n) || {};

	if (!$form.length || !window.hsgcmAdmin) {
		return;
	}

	function ajaxRequest(data) {
		return $.ajax({
			url: hsgcmAdmin.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			data: $.extend(
				{
					nonce: hsgcmAdmin.nonce
				},
				data
			)
		});
	}

	function responseMessage(response, fallback) {
		if (response && response.data) {
			if (typeof response.data.message === 'string' && response.data.message) {
				return response.data.message;
			}

			if (
				response.data.data &&
				typeof response.data.data.message === 'string' &&
				response.data.data.message
			) {
				return response.data.data.message;
			}
		}

		return fallback || i18n.serverError || 'Unexpected server error.';
	}

	function showNotice(message, type) {
		type = type || 'success';

		$notice
			.removeClass('notice-success notice-error notice-warning notice-info')
			.addClass('notice-' + type)
			.html('<p></p>')
			.find('p')
			.text(message);

		$notice.prop('hidden', false);
	}

	function clearNotice() {
		$notice.prop('hidden', true).removeClass(
			'notice-success notice-error notice-warning notice-info'
		);
	}

	function setLoading($button, loading) {
		if (!$button || !$button.length) {
			return;
		}

		$button.prop('disabled', loading);

		if (loading) {
			if (!$button.data('hsgcm-label')) {
				$button.data('hsgcm-label', $button.text());
			}
			$button.addClass('is-busy');
		} else {
			$button.removeClass('is-busy');

			if ($button.data('hsgcm-label')) {
				$button.text($button.data('hsgcm-label'));
			}
		}
	}

	function normaliseProductResults(response) {
		if (!response) {
			return [];
		}

		var data = response.success === true ? response.data : response;

		if ($.isArray(data)) {
			return data;
		}

		if (data && $.isArray(data.results)) {
			return data.results;
		}

		if (data && data.data && $.isArray(data.data)) {
			return data.data;
		}

		return [];
	}

	function initProductSearch() {
		if (!$products.length || typeof $products.selectWoo !== 'function') {
			return;
		}

		$products.selectWoo({
			width: '100%',
			multiple: true,
			placeholder: i18n.productSearch || 'Search products...',
			minimumInputLength: 1,
			ajax: {
				url: hsgcmAdmin.ajaxUrl,
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return {
						action: 'hsgcm_product_search',
						nonce: hsgcmAdmin.nonce,
						term: params.term || '',
						q: params.term || '',
						page: params.page || 1
					};
				},
				processResults: function (response) {
					return {
						results: normaliseProductResults(response)
					};
				},
				cache: true
			}
		});
	}

	function destroySelectedProducts() {
		$products.empty().trigger('change');
	}

	function appendProductOption(id, text) {
		id = parseInt(id, 10);

		if (!id) {
			return;
		}

		text = text || ('#' + id);

		var $existing = $products.find('option[value="' + id + '"]');

		if ($existing.length) {
			$existing.prop('selected', true);
			return;
		}

		$products.append(new Option(text, id, true, true));
	}

	function populateProducts(campaign) {
		destroySelectedProducts();

		var options = campaign.product_options ||
			campaign.products_data ||
			campaign.product_labels ||
			null;

		if ($.isArray(options)) {
			options.forEach(function (item) {
				if (item && typeof item === 'object') {
					appendProductOption(
						item.id || item.value || item.product_id,
						item.text || item.label || item.name
					);
				} else {
					appendProductOption(item, null);
				}
			});
		} else if (options && typeof options === 'object') {
			Object.keys(options).forEach(function (id) {
				var item = options[id];

				if (item && typeof item === 'object') {
					appendProductOption(
						item.id || id,
						item.text || item.label || item.name
					);
				} else {
					appendProductOption(id, item);
				}
			});
		} else if ($.isArray(campaign.products)) {
			campaign.products.forEach(function (item) {
				if (item && typeof item === 'object') {
					appendProductOption(
						item.id || item.value || item.product_id,
						item.text || item.label || item.name
					);
				} else {
					appendProductOption(item, null);
				}
			});
		}

		$products.trigger('change');
	}

	function pricingHelp(type) {
		switch (type) {
			case 'fixed_price':
				return {
					label: 'Fixed price per product',
					help: 'Example: enter 200 to make each eligible product cost 200.'
				};
			case 'percentage_discount':
				return {
					label: 'Discount percentage',
					help: 'Enter a value from 1 to 100.'
				};
			case 'fixed_discount':
				return {
					label: 'Discount amount per product',
					help: 'Example: enter 50 to subtract 50 from each eligible product.'
				};
			default:
				return {
					label: 'Price / discount value',
					help: ''
				};
		}
	}

	function updatePricingFields() {
		var type = $('#hsgcm-type').val();
		var isMultiBuy = type === 'multi_buy';
		var help = pricingHelp(type);

		$('.hsgcm-standard-pricing-row').prop('hidden', isMultiBuy);
		$('.hsgcm-multi-buy-row').prop('hidden', !isMultiBuy);
		$('.hsgcm-multi-buy-note').prop('hidden', !isMultiBuy);

		$('#hsgcm-value-label').text(help.label);
		$('#hsgcm-value-help').text(help.help);

		$('#hsgcm-value').prop('disabled', isMultiBuy);
		$('#hsgcm-quantity, #hsgcm-bundle-price').prop('disabled', !isMultiBuy);

		/*
		 * Multi-buy + coupon is intentionally unsupported by the service.
		 * Clear and disable the coupon field to make the admin UI match that rule.
		 */
		if (isMultiBuy) {
			$('#hsgcm-coupon').val('').prop('disabled', true);
			$('.hsgcm-coupon-row').addClass('is-disabled');
		} else {
			$('#hsgcm-coupon').prop('disabled', false);
			$('.hsgcm-coupon-row').removeClass('is-disabled');
		}
	}

	function resetForm() {
		clearNotice();

		$('#hsgcm-id').val('0');
		$('#hsgcm-name').val('');
		$('#hsgcm-status').val('draft');
		$('#hsgcm-priority').val('0');
		$('#hsgcm-type').val('fixed_price');
		$('#hsgcm-value').val('');
		$('#hsgcm-quantity').val('2');
		$('#hsgcm-bundle-price').val('');
		$('#hsgcm-coupon').val('');
		$('#hsgcm-start-date').val('');
		$('#hsgcm-end-date').val('');
		$('#hsgcm-stackable').prop('checked', false);

		destroySelectedProducts();
		updatePricingFields();

		$('#hsgcm-name').trigger('focus');
	}

	function formData() {
		var isMultiBuy = $('#hsgcm-type').val() === 'multi_buy';

		return {
			action: 'hsgcm_save_campaign',
			id: $('#hsgcm-id').val() || '0',
			name: $('#hsgcm-name').val() || '',
			status: $('#hsgcm-status').val() || 'draft',
			priority: $('#hsgcm-priority').val() || '0',
			products: $products.val() || [],
			type: $('#hsgcm-type').val() || 'fixed_price',
			value: isMultiBuy ? '' : ($('#hsgcm-value').val() || ''),
			quantity: isMultiBuy ? ($('#hsgcm-quantity').val() || '2') : '2',
			bundle_price: isMultiBuy ? ($('#hsgcm-bundle-price').val() || '') : '',
			coupon: isMultiBuy ? '' : ($('#hsgcm-coupon').val() || ''),
			start_date: $('#hsgcm-start-date').val() || '',
			end_date: $('#hsgcm-end-date').val() || '',
			stackable: $('#hsgcm-stackable').is(':checked') ? '1' : ''
		};
	}

	function validateClientSide(data) {
		if (!$.trim(data.name)) {
			return 'Campaign name is required.';
		}

		if (!data.products.length) {
			return 'Select at least one product.';
		}

		if (data.type === 'multi_buy') {
			if (parseInt(data.quantity, 10) < 2) {
				return 'Quantity must be 2 or greater.';
			}

			if (!data.bundle_price || parseFloat(data.bundle_price) <= 0) {
				return 'Bundle price must be greater than 0.';
			}
		} else if (data.value === '' || isNaN(parseFloat(data.value))) {
			return 'Enter a campaign value.';
		}

		if (
			data.start_date &&
			data.end_date &&
			data.end_date < data.start_date
		) {
			return 'End date cannot be earlier than start date.';
		}

		return '';
	}

	function populateForm(campaign) {
		$('#hsgcm-id').val(campaign.id || 0);
		$('#hsgcm-name').val(campaign.name || '');
		$('#hsgcm-status').val(campaign.status || 'draft');
		$('#hsgcm-priority').val(
			campaign.priority === undefined ? 0 : campaign.priority
		);
		$('#hsgcm-type').val(campaign.type || 'fixed_price');
		$('#hsgcm-value').val(
			campaign.value === undefined ? '' : campaign.value
		);
		$('#hsgcm-quantity').val(
			campaign.quantity === undefined ? 2 : campaign.quantity
		);
		$('#hsgcm-bundle-price').val(
			campaign.bundle_price === undefined ? '' : campaign.bundle_price
		);
		$('#hsgcm-coupon').val(campaign.coupon || '');
		$('#hsgcm-start-date').val(campaign.start_date || '');
		$('#hsgcm-end-date').val(campaign.end_date || '');
		$('#hsgcm-stackable').prop('checked', !!campaign.stackable);

		populateProducts(campaign);
		updatePricingFields();

		window.scrollTo({
			top: $('.hsgcm-editor').offset().top - 40,
			behavior: 'smooth'
		});
	}

	$('#hsgcm-new-campaign, #hsgcm-reset-campaign').on('click', function (event) {
		event.preventDefault();
		resetForm();
	});

	$('#hsgcm-type').on('change', updatePricingFields);

	$form.on('submit', function (event) {
		event.preventDefault();
		clearNotice();

		var data = formData();
		var clientError = validateClientSide(data);
		var $button = $('#hsgcm-save-campaign');

		if (clientError) {
			showNotice(clientError, 'error');
			return;
		}

		setLoading($button, true);

		ajaxRequest(data)
			.done(function (response) {
				if (!response || !response.success) {
					showNotice(
						responseMessage(response, 'Unable to save campaign.'),
						'error'
					);
					return;
				}

				showNotice(responseMessage(response, 'Campaign saved.'), 'success');

				window.setTimeout(function () {
					window.location.reload();
				}, 350);
			})
			.fail(function (xhr) {
				showNotice(
					responseMessage(xhr.responseJSON, 'Unable to save campaign.'),
					'error'
				);
			})
			.always(function () {
				setLoading($button, false);
			});
	});

	$(document).on('click', '.hsgcm-edit', function () {
		var id = parseInt($(this).data('id'), 10);
		var $button = $(this);

		if (!id) {
			return;
		}

		clearNotice();
		setLoading($button, true);

		ajaxRequest({
			action: 'hsgcm_get_campaign',
			id: id
		})
			.done(function (response) {
				if (!response || !response.success || !response.data) {
					showNotice(
						responseMessage(response, 'Unable to load campaign.'),
						'error'
					);
					return;
				}

				populateForm(response.data);
			})
			.fail(function (xhr) {
				showNotice(
					responseMessage(xhr.responseJSON, 'Unable to load campaign.'),
					'error'
				);
			})
			.always(function () {
				setLoading($button, false);
			});
	});

	$(document).on('click', '.hsgcm-delete', function () {
		var id = parseInt($(this).data('id'), 10);
		var $button = $(this);

		if (
			!id ||
			!window.confirm(i18n.deleteConfirm || 'Delete campaign?')
		) {
			return;
		}

		setLoading($button, true);

		ajaxRequest({
			action: 'hsgcm_delete_campaign',
			id: id
		})
			.done(function (response) {
				if (!response || !response.success) {
					showNotice(
						responseMessage(
							response,
							i18n.deleteError || 'Unable to delete campaign.'
						),
						'error'
					);
					return;
				}

				window.location.reload();
			})
			.fail(function (xhr) {
				showNotice(
					responseMessage(
						xhr.responseJSON,
						i18n.deleteError || 'Unable to delete campaign.'
					),
					'error'
				);
			})
			.always(function () {
				setLoading($button, false);
			});
	});

	$(document).on('click', '.hsgcm-status-action', function () {
		var id = parseInt($(this).data('id'), 10);
		var status = String($(this).data('status') || '');
		var $button = $(this);

		if (!id || !status) {
			return;
		}

		setLoading($button, true);

		ajaxRequest({
			action: 'hsgcm_update_campaign_status',
			id: id,
			status: status
		})
			.done(function (response) {
				if (!response || !response.success) {
					showNotice(
						responseMessage(
							response,
							i18n.statusError || 'Unable to update campaign status.'
						),
						'error'
					);
					return;
				}

				window.location.reload();
			})
			.fail(function (xhr) {
				showNotice(
					responseMessage(
						xhr.responseJSON,
						i18n.statusError || 'Unable to update campaign status.'
					),
					'error'
				);
			})
			.always(function () {
				setLoading($button, false);
			});
	});

	initProductSearch();
	updatePricingFields();

})(jQuery);
