/**
 * HSG Campaign Manager
 * Admin
 */

(function ($) {

	'use strict';

	class HSGCampaignManager {

		constructor() {

			this.form = $('#hsgcm-campaign-form');
			this.previewTimer = null;

			if (!this.form.length) {
				return;
			}

			this.initProductSearch();
			this.bindEvents();
			this.togglePricingFields();
			this.previewConflicts();

		}

		/**
		 * Initialise product selector.
		 */
		initProductSearch() {

			$('#hsgcm-products').select2({

				width: '100%',

				placeholder: 'Search products...',

				minimumInputLength: 2,

				ajax: {

					url: hsgcmAdmin.ajaxUrl,

					dataType: 'json',

					delay: 300,

					data: function (params) {

						return {

							action: 'hsgcm_product_search',

							nonce: hsgcmAdmin.nonce,

							term: params.term

						};

					},

					processResults: function (data) {

						return data;

					},

					cache: true

				}

			});

		}

		/**
		 * Register events.
		 */
		bindEvents() {

			$('#hsgcm-new-campaign').on(
				'click',
				this.newCampaign.bind(this)
			);

			this.form.on(
				'submit',
				this.saveCampaign.bind(this)
			);

			$('.hsgcm-edit').on(
				'click',
				this.editCampaign.bind(this)
			);

			$('.hsgcm-delete').on(
				'click',
				this.deleteCampaign.bind(this)
			);

			this.form.on(
				'change',
				'#hsgcm-type',
				this.togglePricingFields.bind(this)
			);

			this.form.on(
				'change input',
				'#hsgcm-id, #hsgcm-name, #hsgcm-status, #hsgcm-priority, #hsgcm-products, #hsgcm-start-date, #hsgcm-end-date, #hsgcm-stackable',
				this.scheduleConflictPreview.bind(this)
			);

		}

		/**
		 * Reset form.
		 */
		newCampaign(e) {

			e.preventDefault();

			this.form.trigger('reset');

			$('#hsgcm-id').val('');

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

			$('#hsgcm-products')
				.empty()
				.val(null)
				.trigger('change');

			this.togglePricingFields();

			this.showMessage('', '');
			this.renderConflictPreview({
				message: hsgcmAdmin.i18n.noConflicts,
				conflicts: []
			});

		}

		/**
		 * Collect current form data for AJAX requests.
		 */
		getCampaignData() {

			return {

				nonce: hsgcmAdmin.nonce,

				id: $('#hsgcm-id').val(),

				name: $('#hsgcm-name').val(),

				status: $('#hsgcm-status').val(),

				priority: $('#hsgcm-priority').val(),

				products: $('#hsgcm-products').val(),

				type: $('#hsgcm-type').val(),

				value: $('#hsgcm-value').val(),

				quantity: $('#hsgcm-quantity').val(),

				bundle_price: $('#hsgcm-bundle-price').val(),

				coupon: $('#hsgcm-coupon').val(),

				start_date: $('#hsgcm-start-date').val(),

				end_date: $('#hsgcm-end-date').val(),

				stackable: $('#hsgcm-stackable').is(':checked') ? 1 : 0

			};

		}

		/**
		 * Save campaign.
		 */
		saveCampaign(e) {

			e.preventDefault();

			const button = $('#hsgcm-save');

			button.prop('disabled', true);

			$.post(

				hsgcmAdmin.ajaxUrl,

				Object.assign(
					this.getCampaignData(),
					{
						action: 'hsgcm_save_campaign'
					}
				)

			)

			.done((response) => {

				button.prop('disabled', false);

				if (!response.success) {

					this.showMessage(
						response.data.message,
						'error'
					);

					return;

				}

				this.showMessage(
					response.data.message,
					'success'
				);

				setTimeout(() => {

					location.reload();

				}, 400);

			})

			.fail(() => {

				button.prop('disabled', false);

				this.showMessage(
					'Unexpected server error.',
					'error'
				);

			});

		}

		/**
		 * Edit campaign.
		 */
		editCampaign(e) {

			e.preventDefault();

			const id = $(e.currentTarget).data('id');

			$.post(

				hsgcmAdmin.ajaxUrl,

				{

					action: 'hsgcm_get_campaign',

					nonce: hsgcmAdmin.nonce,

					id: id

				}

			)

			.done((response) => {

				if (!response.success) {

					this.showMessage(
						response.data.message,
						'error'
					);

					return;

				}

				const c = response.data;

				$('#hsgcm-id').val(c.id);

				$('#hsgcm-name').val(c.name);

				$('#hsgcm-status').val(c.status);

				$('#hsgcm-priority').val(c.priority);

				$('#hsgcm-type').val(c.type);

				$('#hsgcm-value').val(c.value);

				$('#hsgcm-quantity').val(c.quantity);

				$('#hsgcm-bundle-price').val(c.bundle_price);

				$('#hsgcm-coupon').val(c.coupon);

				$('#hsgcm-start-date').val(c.start_date);

				$('#hsgcm-end-date').val(c.end_date);

				$('#hsgcm-stackable').prop('checked', !!c.stackable);

				$('#hsgcm-products').empty();

				if (Array.isArray(c.products)) {

					c.products.forEach((product) => {

						const option = new Option(
							product.text,
							product.id,
							true,
							true
						);

						$('#hsgcm-products').append(option);

					});

				}

				$('#hsgcm-products').trigger('change');
				this.togglePricingFields();
				this.previewConflicts();

			});

		}

		/**
		 * Schedule conflict preview refresh.
		 */
		scheduleConflictPreview() {

			clearTimeout(this.previewTimer);

			this.previewTimer = setTimeout(
				() => {
					this.previewConflicts();
				},
				250
			);

		}

		/**
		 * Load conflict preview.
		 */
		previewConflicts() {

			const preview = $('#hsgcm-conflict-preview');

			if (!preview.length) {
				return;
			}

			preview
				.removeClass('notice-warning notice-error')
				.addClass('notice-info')
				.empty()
				.append(
					$('<p></p>').text(hsgcmAdmin.i18n.previewLoading)
				);

			$.post(

				hsgcmAdmin.ajaxUrl,

				Object.assign(
					this.getCampaignData(),
					{
						action: 'hsgcm_preview_conflicts'
					}
				)

			)

			.done((response) => {

				if (!response.success) {
					this.renderConflictError(response.data.message);
					return;
				}

				this.renderConflictPreview(response.data);

			})

			.fail(() => {

				this.renderConflictError(hsgcmAdmin.i18n.previewError);

			});

		}

		/**
		 * Render conflict preview.
		 */
		renderConflictPreview(data) {

			const preview = $('#hsgcm-conflict-preview');
			const conflicts = Array.isArray(data.conflicts) ? data.conflicts : [];

			preview
				.removeClass('notice-warning notice-error')
				.addClass(conflicts.length ? 'notice-warning' : 'notice-info')
				.empty()
				.append(
					$('<p></p>').text(data.message || hsgcmAdmin.i18n.noConflicts)
				);

			if (!conflicts.length) {
				return;
			}

			const list = $('<ul></ul>').addClass('hsgcm-conflict-list');

			conflicts.forEach((conflict) => {

				const products = Array.isArray(conflict.overlapping_products)
					? conflict.overlapping_products.map((product) => product.text).join(', ')
					: '';

				const item = $('<li></li>').addClass('hsgcm-conflict-item');

				item.append(
					$('<strong></strong>').text(conflict.campaign_name)
				);

				item.append(
					$('<dl></dl>')
						.append($('<dt></dt>').text(hsgcmAdmin.i18n.products))
						.append($('<dd></dd>').text(products))
						.append($('<dt></dt>').text(hsgcmAdmin.i18n.priority))
						.append($('<dd></dd>').text(conflict.priority_comparison))
						.append($('<dt></dt>').text(hsgcmAdmin.i18n.winner))
						.append($('<dd></dd>').text(conflict.winner.name))
				);

				list.append(item);

			});

			preview.append(list);

		}

		/**
		 * Render conflict preview error.
		 */
		renderConflictError(message) {

			$('#hsgcm-conflict-preview')
				.removeClass('notice-info notice-warning')
				.addClass('notice-error')
				.empty()
				.append(
					$('<p></p>').text(message || hsgcmAdmin.i18n.previewError)
				);

		}

		/**
		 * Toggle pricing fields based on selected campaign type.
		 */
		togglePricingFields() {

			const isMultiBuy = 'multi_buy' === $('#hsgcm-type').val();

			$('.hsgcm-standard-pricing-row').toggle(!isMultiBuy);
			$('.hsgcm-multi-buy-row').toggle(isMultiBuy);

			if (isMultiBuy && !$('#hsgcm-quantity').val()) {
				$('#hsgcm-quantity').val('2');
			}

		}

		/**
		 * Delete campaign.
		 */
		deleteCampaign(e) {

			e.preventDefault();

			if (!confirm('Delete campaign?')) {
				return;
			}

			const id = $(e.currentTarget).data('id');

			$.post(

				hsgcmAdmin.ajaxUrl,

				{

					action: 'hsgcm_delete_campaign',

					nonce: hsgcmAdmin.nonce,

					id: id

				}

			)

			.done((response) => {

				if (!response.success) {

					this.showMessage(
						response.data.message,
						'error'
					);

					return;

				}

				location.reload();

			});

		}

		/**
		 * Show message.
		 */
		showMessage(message, type) {

			$('.hsgcm-message').remove();

			if (!message) {
				return;
			}

			$('.hsgcm-editor').prepend(
				$('<div></div>')
					.addClass('notice')
					.addClass('notice-' + type)
					.addClass('hsgcm-message')
					.append(
						$('<p></p>').text(message)
					)
			);

		}

	}

	$(function () {

		new HSGCampaignManager();

	});

})(jQuery);
