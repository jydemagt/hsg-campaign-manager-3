<?php
/**
 * Campaign Manager admin page.
 *
 * @package HSGCampaignManager
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap hsgcm-admin-page">

	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Campaign Manager', 'hsg-campaign-manager' ); ?>
	</h1>

	<a href="#" id="hsgcm-new-campaign" class="page-title-action">
		<?php esc_html_e( 'Add Campaign', 'hsg-campaign-manager' ); ?>
	</a>

	<hr class="wp-header-end">

	<div id="hsgcm-notice" class="notice inline" hidden></div>

	<div class="hsgcm-wrapper">

		<section class="hsgcm-list" aria-labelledby="hsgcm-campaign-list-heading">

			<h2 id="hsgcm-campaign-list-heading">
				<?php esc_html_e( 'Campaigns', 'hsg-campaign-manager' ); ?>
			</h2>

			<table class="widefat striped hsgcm-campaign-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Campaign', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Status', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Type', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Products', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Priority', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Period', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Stackable', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Conflict', 'hsg-campaign-manager' ); ?></th>
						<th class="hsgcm-actions-column"><?php esc_html_e( 'Actions', 'hsg-campaign-manager' ); ?></th>
					</tr>
				</thead>

				<tbody>
					<?php if ( empty( $campaigns ) ) : ?>
						<tr>
							<td colspan="9" class="hsgcm-empty-state">
								<strong><?php esc_html_e( 'No campaigns found.', 'hsg-campaign-manager' ); ?></strong>
								<span><?php esc_html_e( 'Create your first campaign with the editor on the right.', 'hsg-campaign-manager' ); ?></span>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $campaigns as $campaign ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $campaign['name'] ?? '' ); ?></strong>
								</td>
								<td><?php echo esc_html( $campaign['status'] ?? '' ); ?></td>
								<td><?php echo esc_html( $campaign['type'] ?? '' ); ?></td>
								<td><?php echo esc_html( $campaign['products_count'] ?? 0 ); ?></td>
								<td><?php echo esc_html( $campaign['priority'] ?? 0 ); ?></td>
								<td>
									<?php
									$start_date = (string) ( $campaign['start_date'] ?? '' );
									$end_date   = (string) ( $campaign['end_date'] ?? '' );

									if ( '' === $start_date && '' === $end_date ) {
										esc_html_e( 'Always', 'hsg-campaign-manager' );
									} else {
										echo esc_html( $start_date . ' – ' . $end_date );
									}
									?>
								</td>
								<td><?php echo esc_html( $campaign['stackable'] ?? '' ); ?></td>
								<td><?php echo esc_html( $campaign['conflict_status'] ?? '' ); ?></td>
								<td>
									<div class="hsgcm-actions">
										<button
											type="button"
											class="button button-small hsgcm-edit"
											data-id="<?php echo esc_attr( $campaign['id'] ?? 0 ); ?>"
										>
											<?php esc_html_e( 'Edit', 'hsg-campaign-manager' ); ?>
										</button>

										<?php if ( ! empty( $campaign['quick_action'] ) ) : ?>
											<button
												type="button"
												class="button button-small hsgcm-status-action"
												data-id="<?php echo esc_attr( $campaign['id'] ?? 0 ); ?>"
												data-status="<?php echo esc_attr( $campaign['quick_action']['status'] ?? '' ); ?>"
											>
												<?php echo esc_html( $campaign['quick_action']['label'] ?? '' ); ?>
											</button>
										<?php endif; ?>

										<button
											type="button"
											class="button button-small hsgcm-delete"
											data-id="<?php echo esc_attr( $campaign['id'] ?? 0 ); ?>"
										>
											<?php esc_html_e( 'Delete', 'hsg-campaign-manager' ); ?>
										</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

		</section>

		<section class="hsgcm-editor" aria-labelledby="hsgcm-editor-heading">

			<h2 id="hsgcm-editor-heading">
				<?php esc_html_e( 'Campaign', 'hsg-campaign-manager' ); ?>
			</h2>

			<form id="hsgcm-campaign-form">
				<input type="hidden" id="hsgcm-id" value="0">

				<div class="hsgcm-section">
					<h3><?php esc_html_e( 'General', 'hsg-campaign-manager' ); ?></h3>

					<div class="hsgcm-field">
						<label for="hsgcm-name"><?php esc_html_e( 'Name', 'hsg-campaign-manager' ); ?></label>
						<input type="text" id="hsgcm-name" class="regular-text" required>
					</div>

					<div class="hsgcm-field-grid">
						<div class="hsgcm-field">
							<label for="hsgcm-status"><?php esc_html_e( 'Status', 'hsg-campaign-manager' ); ?></label>
							<select id="hsgcm-status">
								<option value="draft"><?php esc_html_e( 'Draft', 'hsg-campaign-manager' ); ?></option>
								<option value="publish"><?php esc_html_e( 'Active', 'hsg-campaign-manager' ); ?></option>
							</select>
						</div>

						<div class="hsgcm-field">
							<label for="hsgcm-priority"><?php esc_html_e( 'Priority', 'hsg-campaign-manager' ); ?></label>
							<input type="number" id="hsgcm-priority" min="0" step="1" value="0">
							<p class="description">
								<?php esc_html_e( 'Higher priority wins when campaigns overlap.', 'hsg-campaign-manager' ); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="hsgcm-section">
					<h3><?php esc_html_e( 'Products', 'hsg-campaign-manager' ); ?></h3>

					<div class="hsgcm-field">
						<label for="hsgcm-products"><?php esc_html_e( 'Products', 'hsg-campaign-manager' ); ?></label>
						<select id="hsgcm-products" multiple="multiple"></select>
						<p class="description">
							<?php esc_html_e( 'Search for one or more WooCommerce products or variations.', 'hsg-campaign-manager' ); ?>
						</p>
					</div>
				</div>

				<div class="hsgcm-section">
					<h3><?php esc_html_e( 'Pricing', 'hsg-campaign-manager' ); ?></h3>

					<div class="hsgcm-field">
						<label for="hsgcm-type"><?php esc_html_e( 'Campaign type', 'hsg-campaign-manager' ); ?></label>
						<select id="hsgcm-type">
							<option value="fixed_price"><?php esc_html_e( 'Fixed price per product', 'hsg-campaign-manager' ); ?></option>
							<option value="percentage_discount"><?php esc_html_e( 'Percentage discount', 'hsg-campaign-manager' ); ?></option>
							<option value="fixed_discount"><?php esc_html_e( 'Fixed discount per product', 'hsg-campaign-manager' ); ?></option>
							<option value="multi_buy"><?php esc_html_e( 'X products for Y price', 'hsg-campaign-manager' ); ?></option>
						</select>
					</div>

					<div class="hsgcm-field hsgcm-standard-pricing-row">
						<label for="hsgcm-value" id="hsgcm-value-label">
							<?php esc_html_e( 'Price / discount value', 'hsg-campaign-manager' ); ?>
						</label>
						<input type="number" id="hsgcm-value" min="0" step="0.01" inputmode="decimal">
						<p class="description" id="hsgcm-value-help"></p>
					</div>

					<div class="hsgcm-field-grid hsgcm-multi-buy-row" hidden>
						<div class="hsgcm-field">
							<label for="hsgcm-quantity"><?php esc_html_e( 'Quantity', 'hsg-campaign-manager' ); ?></label>
							<input type="number" id="hsgcm-quantity" min="2" step="1" value="2">
						</div>

						<div class="hsgcm-field">
							<label for="hsgcm-bundle-price"><?php esc_html_e( 'Bundle price', 'hsg-campaign-manager' ); ?></label>
							<input type="number" id="hsgcm-bundle-price" min="0.01" step="0.01" inputmode="decimal">
						</div>
					</div>

					<div class="hsgcm-field hsgcm-coupon-row">
						<label for="hsgcm-coupon"><?php esc_html_e( 'Coupon code', 'hsg-campaign-manager' ); ?></label>
						<input type="text" id="hsgcm-coupon" class="regular-text" autocomplete="off">
						<p class="description">
							<?php esc_html_e( 'Optional. Leave empty for a campaign that applies automatically.', 'hsg-campaign-manager' ); ?>
						</p>
					</div>

					<div class="hsgcm-info-box hsgcm-multi-buy-note" hidden>
						<?php esc_html_e( 'Multi-buy campaigns cannot use a coupon code. Use a separate fixed-price campaign with a higher priority for a coupon offer.', 'hsg-campaign-manager' ); ?>
					</div>
				</div>

				<div class="hsgcm-section">
					<h3><?php esc_html_e( 'Scheduling', 'hsg-campaign-manager' ); ?></h3>

					<div class="hsgcm-field-grid">
						<div class="hsgcm-field">
							<label for="hsgcm-start-date"><?php esc_html_e( 'Start date', 'hsg-campaign-manager' ); ?></label>
							<input type="date" id="hsgcm-start-date">
						</div>

						<div class="hsgcm-field">
							<label for="hsgcm-end-date"><?php esc_html_e( 'End date', 'hsg-campaign-manager' ); ?></label>
							<input type="date" id="hsgcm-end-date">
						</div>
					</div>

					<p class="description">
						<?php esc_html_e( 'Leave both dates empty to keep the campaign available without a date limit.', 'hsg-campaign-manager' ); ?>
					</p>
				</div>

				<div class="hsgcm-section">
					<h3><?php esc_html_e( 'Behaviour', 'hsg-campaign-manager' ); ?></h3>

					<label class="hsgcm-checkbox" for="hsgcm-stackable">
						<input type="checkbox" id="hsgcm-stackable" value="1">
						<span>
							<strong><?php esc_html_e( 'Allow stacking', 'hsg-campaign-manager' ); ?></strong>
							<small><?php esc_html_e( 'Allow this campaign to be combined with another stackable campaign.', 'hsg-campaign-manager' ); ?></small>
						</span>
					</label>
				</div>

				<div class="hsgcm-example-box">
					<strong><?php esc_html_e( 'Example: 4 for 900 + coupon price 200', 'hsg-campaign-manager' ); ?></strong>
					<p>
						<?php esc_html_e( 'Create a multi-buy campaign with quantity 4, bundle price 900 and no coupon. Then create a separate fixed-price campaign with value 200, your coupon code, and a higher priority.', 'hsg-campaign-manager' ); ?>
					</p>
				</div>

				<div class="hsgcm-form-actions">
					<button type="submit" class="button button-primary" id="hsgcm-save-campaign">
						<?php esc_html_e( 'Save Campaign', 'hsg-campaign-manager' ); ?>
					</button>

					<button type="button" class="button" id="hsgcm-reset-campaign">
						<?php esc_html_e( 'Clear', 'hsg-campaign-manager' ); ?>
					</button>
				</div>

			</form>

		</section>

	</div>

</div>
