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

	<a href="#" id="hsgcm-new-campaign" class="page-title-action">
		<?php esc_html_e( 'Add Campaign', 'hsg-campaign-manager' ); ?>
	</a>

	<hr class="wp-header-end">

	<div class="hsgcm-wrapper">

		<div class="hsgcm-list">

			<h2><?php esc_html_e( 'Campaigns', 'hsg-campaign-manager' ); ?></h2>

			<table class="widefat striped">

				<thead>

				<tr>
					<th width="60">ID</th>
					<th><?php esc_html_e( 'Campaign', 'hsg-campaign-manager' ); ?></th>
					<th width="120"><?php esc_html_e( 'Status', 'hsg-campaign-manager' ); ?></th>
					<th width="160"><?php esc_html_e( 'Actions', 'hsg-campaign-manager' ); ?></th>
				</tr>

				</thead>

				<tbody>

				<?php if ( empty( $campaigns ) ) : ?>

					<tr>

						<td colspan="4">

							<?php esc_html_e(
								'No campaigns found.',
								'hsg-campaign-manager'
							); ?>

						</td>

					</tr>

				<?php else : ?>

					<?php foreach ( $campaigns as $campaign ) : ?>

						<tr>

							<td><?php echo esc_html( $campaign->ID ); ?></td>

							<td><?php echo esc_html( $campaign->post_title ); ?></td>

							<td><?php echo esc_html( ucfirst( $campaign->post_status ) ); ?></td>

							<td>

								<a
									href="#"
									class="button button-small hsgcm-edit"
									data-id="<?php echo esc_attr( $campaign->ID ); ?>">

									<?php esc_html_e( 'Edit', 'hsg-campaign-manager' ); ?>

								</a>

								<a
									href="#"
									class="button button-small hsgcm-delete"
									data-id="<?php echo esc_attr( $campaign->ID ); ?>">

									<?php esc_html_e( 'Delete', 'hsg-campaign-manager' ); ?>

								</a>

							</td>

						</tr>

					<?php endforeach; ?>

				<?php endif; ?>

				</tbody>

			</table>

		</div>

		<div class="hsgcm-editor">

			<h2><?php esc_html_e( 'Campaign', 'hsg-campaign-manager' ); ?></h2>

			<form id="hsgcm-campaign-form">

				<input
					type="hidden"
					id="hsgcm-id">

				<table class="form-table">

					<tr>

						<th>

							<label for="hsgcm-name">

								<?php esc_html_e( 'Name', 'hsg-campaign-manager' ); ?>

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

								<?php esc_html_e( 'Status', 'hsg-campaign-manager' ); ?>

							</label>

						</th>

						<td>

							<select id="hsgcm-status">

								<option value="draft">

									<?php esc_html_e( 'Draft', 'hsg-campaign-manager' ); ?>

								</option>

								<option value="publish">

									<?php esc_html_e( 'Active', 'hsg-campaign-manager' ); ?>

								</option>

							</select>

						</td>

					</tr>

					<tr>

						<th>

							<label for="hsgcm-products">

								<?php esc_html_e(
									'Products',
									'hsg-campaign-manager'
								); ?>

							</label>

						</th>

						<td>

							<select
								id="hsgcm-products"
								multiple
								style="width:100%;">

							</select>

							<p class="description">

								<?php esc_html_e(
									'Products will be selectable in the next step.',
									'hsg-campaign-manager'
								); ?>

							</p>

						</td>

					</tr>

				</table>

				<p class="description">
					<?php esc_html_e(
						'Higher priority values win when campaigns overlap. Priority starts at 0 and has no upper limit.',
						'hsg-campaign-manager'
					); ?>
				</p>

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
