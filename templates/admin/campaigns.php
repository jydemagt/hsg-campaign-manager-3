<?php
/**
 * Campaign Manager
 *
 * @package HSGCampaignManager
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="wrap">

	<h1 class="wp-heading-inline">

		<?php esc_html_e( 'Campaign Manager', 'hsg-campaign-manager' ); ?>

	</h1>

	<a
		href="#"
		class="page-title-action"
		id="hsgcm-new-campaign">

		<?php esc_html_e( 'Add Campaign', 'hsg-campaign-manager' ); ?>

	</a>

	<hr class="wp-header-end">

	<div class="hsgcm-wrapper">

		<div class="hsgcm-list">

			<h2>

				<?php esc_html_e( 'Campaigns', 'hsg-campaign-manager' ); ?>

			</h2>

			<?php if ( empty( $campaigns ) ) : ?>

				<div class="notice notice-info inline">

					<p>

						<?php esc_html_e(
							'No campaigns have been created yet.',
							'hsg-campaign-manager'
						); ?>

					</p>

				</div>

			<?php else : ?>

				<table class="widefat striped">

					<thead>

						<tr>

							<th width="40">

								ID

							</th>

							<th>

								<?php esc_html_e(
									'Name',
									'hsg-campaign-manager'
								); ?>

							</th>

							<th width="120">

								<?php esc_html_e(
									'Status',
									'hsg-campaign-manager'
								); ?>

							</th>

							<th width="160">

								<?php esc_html_e(
									'Actions',
									'hsg-campaign-manager'
								); ?>

							</th>

						</tr>

					</thead>

					<tbody>

					<?php foreach ( $campaigns as $campaign ) : ?>

						<tr>

							<td>

								<?php echo esc_html( $campaign->ID ); ?>

							</td>

							<td>

								<strong>

									<?php echo esc_html(
										$campaign->post_title
									); ?>

								</strong>

							</td>

							<td>

								<?php echo esc_html(
									ucfirst( $campaign->post_status )
								); ?>

							</td>

							<td>

								<a
									href="#"
									class="button button-small hsgcm-edit"
									data-id="<?php echo esc_attr( $campaign->ID ); ?>">

									<?php esc_html_e(
										'Edit',
										'hsg-campaign-manager'
									); ?>

								</a>

								<a
									href="#"
									class="button button-small hsgcm-delete"
									data-id="<?php echo esc_attr( $campaign->ID ); ?>">

									<?php esc_html_e(
										'Delete',
										'hsg-campaign-manager'
									); ?>

								</a>

							</td>

						</tr>

					<?php endforeach; ?>

					</tbody>

				</table>

			<?php endif; ?>

		</div>

		<div class="hsgcm-editor">

			<h2>

				<?php esc_html_e(
					'Campaign',
					'hsg-campaign-manager'
				); ?>

			</h2>

			<form
				id="hsgcm-campaign-form">

				<input
					type="hidden"
					id="hsgcm-id"
					value="">

				<table class="form-table">

					<tr>

						<th>

							<label for="hsgcm-name">

								<?php esc_html_e(
									'Name',
									'hsg-campaign-manager'
								); ?>

							</label>

						</th>

						<td>

							<input
								type="text"
								id="hsgcm-name"
								class="regular-text">

						</td>

					</tr>

					<tr>

						<th>

							<label for="hsgcm-status">

								<?php esc_html_e(
									'Status',
									'hsg-campaign-manager'
								); ?>

							</label>

						</th>

						<td>

							<select id="hsgcm-status">

								<option value="draft">

									<?php esc_html_e(
										'Draft',
										'hsg-campaign-manager'
									); ?>

								</option>

								<option value="publish">

									<?php esc_html_e(
										'Active',
										'hsg-campaign-manager'
									); ?>

								</option>

							</select>

						</td>

					</tr>

				</table>

				<p>

					<button
						type="submit"
						class="button button-primary"
						id="hsgcm-save">

						<?php esc_html_e(
							'Save Campaign',
							'hsg-campaign-manager'
						); ?>

					</button>

				</p>

			</form>

		</div>

	</div>

</div>
