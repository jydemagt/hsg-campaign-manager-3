/**
 * HSG Campaign Manager
 * Admin
 */

(function ($) {

	'use strict';

	class HSGCampaignManager {

		constructor() {

			console.log('HSGCM: admin.js loaded');

			this.form = $('#hsgcm-campaign-form');

			if (!this.form.length) {
				console.error('HSGCM: Form not found');
				return;
			}

			this.bindEvents();

		}

		/**
		 * Register events.
		 */
		bindEvents() {

			console.log('HSGCM: Binding events');

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

			console.log('HSGCM: New campaign');

			this.form.trigger('reset');

			$('#hsgcm-id').val('');
			$('#hsgcm-status').val('draft');

			this.showMessage('', '');

			$('#hsgcm-name').focus();

		}

		/**
		 * Save campaign.
		 */
		saveCampaign(e) {

			e.preventDefault();

			console.log('HSGCM: Save clicked');

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

			)

			.done((response) => {

				console.log('HSGCM: Save response', response);

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

				}, 800);

			})

			.fail((xhr) => {

				console.error('HSGCM: Save failed', xhr);

				button.prop('disabled', false);

				this.showMessage(
					'Server error.',
					'error'
				);

			});

		}

		/**
		 * Edit campaign.
		 */
		editCampaign(e) {

			e.preventDefault();

			console.log('HSGCM: Edit clicked');

			const id = $(e.currentTarget).data('id');

			console.log('HSGCM: Campaign ID', id);

			$.post(

				hsgcmAdmin.ajaxUrl,

				{
					action: 'hsgcm_get_campaign',
					nonce: hsgcmAdmin.nonce,
					id: id
				}

			)

			.done((response) => {

				console.log('HSGCM: AJAX response', response);

				if (!response.success) {

					this.showMessage(
						response.data.message,
						'error'
					);

					return;

				}

				const c = response.data;

				console.log('HSGCM: Filling form', c);

				$('#hsgcm-id').val(c.id);
				$('#hsgcm-name').val(c.name);
				$('#hsgcm-status').val(c.status);

				console.log('HSGCM: Form updated');

			})

			.fail((xhr) => {

				console.error('HSGCM: AJAX failed', xhr);

			});

		}

		/**
		 * Delete campaign.
		 */
		deleteCampaign(e) {

			e.preventDefault();

			console.log('HSGCM: Delete clicked');

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

				console.log('HSGCM: Delete response', response);

				if (!response.success) {

					this.showMessage(
						response.data.message,
						'error'
					);

					return;

				}

				location.reload();

			})

			.fail((xhr) => {

				console.error('HSGCM: Delete failed', xhr);

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
