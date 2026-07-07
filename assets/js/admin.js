/**
 * HSG Campaign Manager
 * Admin
 */

(function ($) {

	'use strict';

	class HSGCampaignManager {

		constructor() {

			console.log('HSGCM loaded');

			this.form = $('#hsgcm-campaign-form');

			if (!this.form.length) {
				return;
			}

			this.initProductSelector();

			this.bindEvents();

		}

		/**
		 * Initialise WooCommerce product selector.
		 */
		initProductSelector() {

			$('#hsgcm-products').selectWoo({

				width: '100%',

				placeholder: 'Search products...',

				allowClear: true,

				ajax: {

					url: hsgcmAdmin.ajaxUrl,

					dataType: 'json',

					delay: 250,

					data: function (params) {

						return {

							action: 'woocommerce_json_search_products_and_variations',

							security: wc_enhanced_select_params.search_products_nonce,

							term: params.term || '',

							limit: 20

						};

					},

					processResults: function (data) {

						const results = [];

						$.each(data, function (id, text) {

							results.push({

								id: id,

								text: text

							});

						});

						return {

							results: results

						};

					}

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

			$('#hsgcm-products').val(null).trigger('change');

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

					products: $('#hsgcm-products').val(),

					start_date: '',

					end_date: '',

					priority: 10

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

				}, 500);

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
				'<div class="notice notice-' +
				type +
				' hsgcm-message"><p>' +
				message +
				'</p></div>'
			);

		}

	}

	$(function () {

		new HSGCampaignManager();

	});

})(jQuery);
