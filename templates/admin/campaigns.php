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

				<h3><?php esc_html_e( 'General', 'hsg-campaign-manager' ); ?></h3>

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

							<label for="hsgcm-priority">

								<?php esc_html_e( 'Priority', 'hsg-campaign-manager' ); ?>

							</label>

						</th>

						<td>

							<input
								type="number"
								id="hsgcm-priority"
								min="0"
								step="1"
								value="0">

							<p class="description">
								<?php esc_html_e(
									'Higher priority values win when campaigns overlap. Priority starts at 0 and has no upper limit.',
									'hsg-campaign-manager'
								); ?>
							</p>

						</td>

					</tr>

				</table>

				<h3><?php esc_html_e( 'Products', 'hsg-campaign-manager' ); ?></h3>

				<table class="form-table">

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
									'Search for products or variations included in this campaign.',
									'hsg-campaign-manager'
								); ?>

							</p>

						</td>

					</tr>

				</table>

				<h3><?php esc_html_e( 'Pricing', 'hsg-campaign-manager' ); ?></h3>

				<table class="form-table">

					<tr>

						<th>

							<label for="hsgcm-type">

								<?php esc_html_e( 'Campaign type', 'hsg-campaign-manager' ); ?>

							</label>

						</th>

						<td>

							<select id="hsgcm-type">

								<option value="fixed_price">

									<?php esc_html_e( 'Fixed price', 'hsg-campaign-manager' ); ?>

								</option>

								<option value="percentage_discount">

									<?php esc_html_e( 'Percentage discount', 'hsg-campaign-manager' ); ?>

								</option>

								<option value="fixed_discount">

									<?php esc_html_e( 'Fixed discount', 'hsg-campaign-manager' ); ?>

								</option>

							</select>

						</td>

					</tr>

					<tr>

						<th>

							<label for="hsgcm-value">

								<?php esc_html_e( 'Campaign value', 'hsg-campaign-manager' ); ?>

							</label>

						</th>

						<td>

							<input
								type="number"
								id="hsgcm-value"
								min="0"
								step="0.01"
								inputmode="decimal">

						</td>

					</tr>

					<tr>

						<th>

							<label for="hsgcm-coupon">

								<?php esc_html_e( 'Coupon code', 'hsg-campaign-manager' ); ?>

							</label>

						</th>

						<td>

							<input
								type="text"
								id="hsgcm-coupon"
								class="regular-text">

						</td>

					</tr>

				</table>

				<h3><?php esc_html_e( 'Scheduling', 'hsg-campaign-manager' ); ?></h3>

				<table class="form-table">

					<tr>

						<th>

							<label for="hsgcm-start-date">

								<?php esc_html_e( 'Start date', 'hsg-campaign-manager' ); ?>

							</label>

						</th>

						<td>

							<input
								type="date"
								id="hsgcm-start-date">

						</td>

					</tr>

					<tr>

						<th>

							<label for="hsgcm-end-date">

								<?php esc_html_e( 'End date', 'hsg-campaign-manager' ); ?>

							</label>

						</th>

						<td>

							<input
								type="date"
								id="hsgcm-end-date">

						</td>

					</tr>

				</table>

				<h3><?php esc_html_e( 'Behaviour', 'hsg-campaign-manager' ); ?></h3>

				<table class="form-table">

					<tr>

						<th>

							<?php esc_html_e( 'Stacking', 'hsg-campaign-manager' ); ?>

						</th>

						<td>

							<label for="hsgcm-stackable">

								<input
									type="checkbox"
									id="hsgcm-stackable"
									value="1">

								<?php esc_html_e(
									'Allow campaign to be combined with other campaigns.',
									'hsg-campaign-manager'
								); ?>

							</label>

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
