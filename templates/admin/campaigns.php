<?php
/**
 * Campaign Manager
 *
 * @package HSGCampaignManager
 */

// Include WordPress bootstrap to access functions like esc_html_e and esc_attr
require_once dirname( __FILE__ ) . '/../../../../wp-load.php';

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

			<table class="widefat striped hsgcm-campaign-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Campaign name', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Status', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Campaign type', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Products count', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Priority', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Start date', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'End date', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Stackable', 'hsg-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Conflict status', 'hsg-campaign-manager' ); ?></th>
						<th width="160"><?php esc_html_e( 'Actions', 'hsg-campaign-manager' ); ?></th>
					</tr>
				</thead>

				<tbody>
					<?php if ( empty( $campaigns ) ) : ?>
						<tr>
							<td colspan="10">
								<?php esc_html_e(
									'No campaigns found.',
									'hsg-campaign-manager'
								); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $campaigns as $campaign ) : ?>
							<tr>
								<td><?php echo esc_html( $campaign['name'] ); ?></td>
								<td><?php echo esc_html( $campaign['status'] ); ?></td>
								<td><?php echo esc_html( $campaign['type'] ); ?></td>
								<td><?php echo esc_html( $campaign['products_count'] ); ?></td>
								<td><?php echo esc_html( $campaign['priority'] ); ?></td>
								<td><?php echo esc_html( $campaign['start_date'] ); ?></td>
								<td><?php echo esc_html( $campaign['end_date'] ); ?></td>
								<td><?php echo esc_html( $campaign['stackable'] ); ?></td>
								<td><?php echo esc_html( $campaign['conflict_status'] ); ?></td>
								<td>
									<div class="hsgcm-actions">
										<a href="#" class="button button-small hsgcm-edit" data-id="<?php echo esc_attr( $campaign['id'] ); ?>">
											<?php esc_html_e( 'Edit', 'hsg-campaign-manager' ); ?>
										</a>
										<?php if ( ! empty( $campaign['quick_action'] ) ) : ?>
											<a href="#" class="button button-small hsgcm-status-action" data-id="<?php echo esc_attr( $campaign['id'] ); ?>" data-status="<?php echo esc_attr( $campaign['quick_action']['status'] ); ?>">
												<?php echo esc_html( $campaign['quick_action']['label'] ); ?>
											</a>
										<?php endif; ?>
										<a href="#" class="button button-small hsgcm-delete" data-id="<?php echo esc_attr( $campaign['id'] ); ?>">
											<?php esc_html_e( 'Delete', 'hsg-campaign-manager' ); ?>
										</a>
										<button href="#" class="button button-small duplicate" data-id="<?php echo esc_attr( $campaign['id'] ); ?>">
											<?php esc_html_e( 'Duplicate', 'hsg-campaign-manager' ); ?>
										</button>
									</div>
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
				<input type="hidden" id="hsgcm-id">
				<h3><?php esc_html_e( 'General', 'hsg-campaign-manager' ); ?></h3>
				<table class="form-table">
					<tr>
						<th>
							<label for="hsgcm-name"><?php esc_html_e( 'Name', 'hsg-campaign-manager' ); ?></label>
						</th>
						<td>
							<input type="text" id="hsgcm-name" class="regular-text">
						</td>
					</tr>
					<tr>
						<th>
							<label for="hsgcm-status"><?php esc_html_e( 'Status', 'hsg-campaign-manager' ); ?></label>
						</th>
						<td>
							<select id="hsgcm-status">
								<option value="draft"><?php esc_html_e( 'Draft', 'hsg-campaign-manager' ); ?></option>
								<option value="publish"><?php esc_html_e( 'Active', 'hsg-campaign-manager' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th>
							<label for="hsgcm-priority"><?php esc_html_e( 'Priority', 'hsg-campaign-manager' ); ?></label>
						</th>
						<td>
							<input type="number" id="hsgcm-priority" min="0" step="1" value="0">
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
							<label for="hsgcm-products"><?php esc_html_e(
								'Products',
								'hsg-campaign-manager'
							); ?></label>
						</th>
						<td>
							<select id="hsgcm-products" multiple style="width:100%;">
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
							<label for="hsgcm-type"><?php esc_html_e(
								'Campaign type',
								'hsg-campaign-manager'
							); ?></label>
						</th>
						<td>
							<select id="hsgcm-type">
								<option value="fixed_price"><?php esc_html_e(
									'Fixed price',
									'hsg-campaign-manager'
								); ?></option>
								<option value="percentage_discount"><?php esc_html_e(
									'Percentage discount',
									'hsg-campaign-manager'
								); ?></option>
								<option value="fixed_discount"><?php esc_html_e(
									'Fixed discount',
									'hsg-campaign-manager'
								); ?></option>
								<option value="multi_buy"><?php esc_html_e(
									'X products for Y price',
									'hsg-campaign-manager'
								); ?></option>
							</select>
						</td>
					</tr>
					<tr class="hsgcm-standard-pricing-row">
						<th>
							<label for="hsgcm-value"><?php esc_html_e(
								'Campaign value',
								'hsg-campaign-manager'
							); ?></label>
						</th>
						<td>
							<input type="number" id="hsgcm-value" min="0" step="0.01" inputmode="decimal">
						</td>
					</tr>
					<tr class="hsgcm-multi-buy-row" style="display:none;">
						<th>
							<label for="hsgcm-quantity"><?php esc_html_e(
								'Quantity',
								'hsg-campaign-manager'
							); ?></label>
						</th>
						<td>
							<input type="number" id="hsgcm-quantity" min="2" step="1" value="2">
						</td>
					</tr>
					<tr class="hsgcm-multi-buy-row" style="display:none;">
						<th>
							<label for="hsgcm-bundle-price"><?php esc_html_e(
								'Bundle price',
								'hsg-campaign-manager'
							); ?></label>
						</th>
						<td>
							<input type="number" id="hsgcm-bundle-price" min="0.01" step="0.01" inputmode="decimal">
						</td>
					</tr>
					<tr>
						<th>
							<label for="hsgcm-coupon"><?php esc_html_e(
								'Coupon code',
								'hsg-campaign-manager'
							); ?></label>
						</th>
						<td>
							<input type="text" id="hsgcm-coupon" class="regular-text">
						</td>
					</tr>
				</table>

				<h3><?php esc_html_e( 'Scheduling', 'hsg-campaign-manager' ); ?></h3>
				<table class="form-table">
					<tr>
						<th>
							<label for="hsgcm-start-date"><?php esc_html_e(
								'Start date',
								'hsg-campaign-manager'
							); ?></label>
						</th>
						<td>
							<input type="date" id="hsgcm-start-date">
						</td>
					</tr>
					<tr>
						<th>
							<label for="hsgcm-end-date"><?php esc_html_e(
								'End date',
								'hsg-campaign-manager'
							); ?></label>
						</th>
						<td>
							<input type="date" id="hsgcm-end-date">
						</td>
					</tr>
				</table>

				<h3><?php esc_html_e( 'Behaviour', 'hsg-campaign-manager' ); ?></h3>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e(
							'Stacking',
							'hsg-campaign-manager'
						); ?></th>
						<td>
							<label for="hsgcm-stackable">
								<input type="checkbox" id="hsgcm-stackable" value="1">
								<?php esc_html_e(
									'Allow campaign to be combined with other campaigns.',
									'hsg-campaign-manager'
								); ?>
							</label>
						</td>

</final_file_content>

<task_progress>
- [x] Read AGENTS.md, ARCHITECTURE.md, FEATURES.md, TODO.md, and .clinerules
- [x] Analyze the requirements for duplicating a campaign
- [x] Create a comprehensive checklist of all steps needed
- [x] Set up necessary files if required
- [ ] Implement main functionality in CampaignService
- [ ] Handle edge cases
- [ ] Test the implementation
- [ ] Verify results
</task_progress>
</write_to_file>