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

			this.bindEvents();

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

			$(document).on(
				'click',
				'.hsgcm-edit',
				this.editCampaign.bind(this)
			);

			$(document).on(
				'click',
				'.hsgcm-delete',
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

			this.showMessage(
				'',
				''
			);

			$('#hsgcm-name').focus();

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

					start_date: '',

					end_date: '',

					priority: 10

				}

			).done((response) => {

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

				setTimeout(function () {

					location.reload();

				}, 800);

			}).fail(() => {

				button.prop('disabled', false);

				this.showMessage(
					'Server error.',
					'error'
				);

			});

		}

		/**
		 * Load campaign.
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

			).done((response) => {

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

			).done((response) => {

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

			if (message === '') {
				return;
			}

			const notice =
				'<div class="notice notice-' +
				type +
				' hsgcm-message"><p>' +
				message +
				'</p></div>';

			$('.hsgcm-editor').prepend(notice);

		}

	}

	$(function () {

		new HSGCampaignManager();

	});

})(jQuery);
