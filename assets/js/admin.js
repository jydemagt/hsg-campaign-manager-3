/**
 * HSG Campaign Manager
 * Admin
 */

(function ($) {

	'use strict';

	class HSGCampaignManager {

		constructor() {

			this.form = $('#hsgcm-campaign-form');

			if (!this.form.length) {
				return;
			}

			this.initProductSearch();
			this.bindEvents();

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

		}

		/**
		 * Reset form.
		 */
		newCampaign(e) {

			e.preventDefault();

			this.form.trigger('reset');

			$('#hsgcm-id').val('');

			$('#hsgcm-status').val('draft');

			$('#hsgcm-priority').val('10');

			$('#hsgcm-type').val('fixed_price');

			$('#hsgcm-value').val('');

			$('#hsgcm-coupon').val('');

			$('#hsgcm-start-date').val('');

			$('#hsgcm-end-date').val('');

			$('#hsgcm-stackable').prop('checked', false);

			$('#hsgcm-products')
				.empty()
				.val(null)
				.trigger('change');

			this.showMessage('', '');

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

				{

					action: 'hsgcm_save_campaign',

					nonce: hsgcmAdmin.nonce,

					id: $('#hsgcm-id').val(),

					name: $('#hsgcm-name').val(),

					status: $('#hsgcm-status').val(),

					priority: $('#hsgcm-priority').val(),

					products: $('#hsgcm-products').val(),

					type: $('#hsgcm-type').val(),

					value: $('#hsgcm-value').val(),

					coupon: $('#hsgcm-coupon').val(),

					start_date: $('#hsgcm-start-date').val(),

					end_date: $('#hsgcm-end-date').val(),

					stackable: $('#hsgcm-stackable').is(':checked') ? 1 : 0

				}

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

			});

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
