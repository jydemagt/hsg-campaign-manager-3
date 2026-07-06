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

			$('#hsgcm-name').trigger('focus');

		}

		/**
		 * Save campaign.
		 *
		 * Sprint 1:
		 * Vi sender ikke AJAX endnu.
		 */
		saveCampaign(e) {

			e.preventDefault();

			alert(
				'Sprint 1 completed.\n\nSave functionality will be implemented in Sprint 2.'
			);

		}

		/**
		 * Edit campaign.
		 */
		editCampaign(e) {

			e.preventDefault();

			alert(
				'Edit will be implemented in Sprint 2.'
			);

		}

		/**
		 * Delete campaign.
		 */
		deleteCampaign(e) {

			e.preventDefault();

			if (!confirm('Delete campaign?')) {
				return;
			}

			alert(
				'Delete will be implemented in Sprint 2.'
			);

		}

	}

	$(function () {

		new HSGCampaignManager();

	});

})(jQuery);
