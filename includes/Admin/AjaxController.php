<?php
/**
 * AJAX Controller
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Admin;

use HSGCM\Campaign\CampaignService;
use HSGCM\Pricing\SimulationService;

defined( 'ABSPATH' ) || exit;

final class AjaxController {

	/**
	 * Campaign service.
	 *
	 * @var CampaignService
	 */
	private CampaignService $service;

	/**
	 * Simulation service.
	 *
	 * @var SimulationService
	 */
	private SimulationService $simulation_service;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->service            = new CampaignService();
		$this->simulation_service = new SimulationService();

		add_action( 'wp_ajax_hsgcm_get_campaign', array( $this, 'get_campaign' ) );
		add_action( 'wp_ajax_hsgcm_save_campaign', array( $this, 'save_campaign' ) );
		add_action( 'wp_ajax_hsgcm_delete_campaign', array( $this, 'delete_campaign' ) );
		add_action( 'wp_ajax_hsgcm_update_campaign_status', array( $this, 'update_campaign_status' ) );
		add_action( 'wp_ajax_hsgcm_preview_conflicts', array( $this, 'preview_conflicts' ) );
		add_action( 'wp_ajax_hsgcm_simulate_campaign', array( $this, 'simulate_campaign' ) );

	}

	/**
	 * Verify request.
	 *
	 * @return void
	 */
	private function verify(): void {

		check_ajax_referer( 'hsgcm_admin', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {

			wp_send_json_error(
				array(
					'message' => __( 'Permission denied.', 'hsg-campaign-manager' ),
				),
				403
			);

		}

	}

	/**
	 * Get campaign.
	 *
	 * @return void
	 */
	public function get_campaign(): void {

		$this->verify();

		$id = absint( $this->post_scalar( 'id' ) );

		$campaign = $this->service->get( $id );

		if ( ! $campaign ) {

			wp_send_json_error(
				array(
					'message' => __( 'Campaign not found.', 'hsg-campaign-manager' ),
				)
			);

		}

		wp_send_json_success( $campaign );

	}

	/**
	 * Save campaign.
	 *
	 * @return void
	 */
	public function save_campaign(): void {

		$this->verify();

		$result = $this->service->save(
			array(
				'id'         => absint( $this->post_scalar( 'id' ) ),
				'name'       => sanitize_text_field( $this->post_scalar( 'name' ) ),
				'status'     => sanitize_text_field( $this->post_scalar( 'status', 'draft' ) ),
				'start_date' => sanitize_text_field( $this->post_scalar( 'start_date' ) ),
				'end_date'   => sanitize_text_field( $this->post_scalar( 'end_date' ) ),
				'priority'   => sanitize_text_field( $this->post_scalar( 'priority', '0' ) ),
				'quantity'   => sanitize_text_field( $this->post_scalar( 'quantity', '2' ) ),
				'bundle_price' => sanitize_text_field( $this->post_scalar( 'bundle_price' ) ),
				'products'   => $this->post_product_ids( 'products' ),
				'type'       => sanitize_key( $this->post_scalar( 'type', 'fixed_price' ) ),
				'value'      => sanitize_text_field( $this->post_scalar( 'value' ) ),
				'coupon'     => sanitize_text_field( $this->post_scalar( 'coupon' ) ),
				'stackable'  => ! empty( $_POST['stackable'] ),
			)
		);

		if ( ! $result['success'] ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );

	}

	/**
	 * Delete campaign.
	 *
	 * @return void
	 */
	public function delete_campaign(): void {

		$this->verify();

		$result = $this->service->delete(
			absint( $this->post_scalar( 'id' ) )
		);

		if ( ! $result['success'] ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );

	}

	/**
	 * Update campaign status.
	 *
	 * @return void
	 */
	public function update_campaign_status(): void {

		$this->verify();

		$result = $this->service->update_status(
			absint( $this->post_scalar( 'id' ) ),
			sanitize_key( $this->post_scalar( 'status' ) )
		);

		if ( ! $result['success'] ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );

	}

	/**
	 * Simulate campaign evaluation.
	 *
	 * @return void
	 */
	public function simulate_campaign(): void {

		$this->verify();

		$result = $this->simulation_service->simulate(
			array(
				'product_id'    => absint( $this->post_scalar( 'product_id' ) ),
				'quantity'      => absint( $this->post_scalar( 'quantity', '1' ) ),
				'customer_role' => sanitize_key( $this->post_scalar( 'customer_role' ) ),
				'coupon'        => sanitize_text_field( $this->post_scalar( 'coupon' ) ),
				'date'          => sanitize_text_field( $this->post_scalar( 'date', current_time( 'Y-m-d' ) ) ),
			)
		);

		if ( ! $result['success'] ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result['data'] );

	}

	/**
	 * Preview campaign conflicts.
	 *
	 * @return void
	 */
	public function preview_conflicts(): void {

		$this->verify();

		$result = $this->service->preview_conflicts(
			array(
				'id'         => absint( $this->post_scalar( 'id' ) ),
				'name'       => sanitize_text_field( $this->post_scalar( 'name' ) ),
				'status'     => sanitize_text_field( $this->post_scalar( 'status', 'draft' ) ),
				'start_date' => sanitize_text_field( $this->post_scalar( 'start_date' ) ),
				'end_date'   => sanitize_text_field( $this->post_scalar( 'end_date' ) ),
				'priority'   => sanitize_text_field( $this->post_scalar( 'priority', '0' ) ),
				'products'   => $this->post_product_ids( 'products' ),
				'stackable'  => ! empty( $_POST['stackable'] ),
			)
		);

		if ( ! $result['success'] ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );

	}

	/**
	 * Read a scalar POST value.
	 *
	 * @param string $key     POST key.
	 * @param string $default Default value.
	 *
	 * @return string
	 */
	private function post_scalar(
		string $key,
		string $default = ''
	): string {

		$value = $_POST[ $key ] ?? $default;

		if ( ! is_scalar( $value ) ) {
			return $default;
		}

		return (string) wp_unslash( $value );

	}

	/**
	 * Read product IDs from POST.
	 *
	 * @param string $key POST key.
	 *
	 * @return array
	 */
	private function post_product_ids( string $key ): array {

		if ( ! isset( $_POST[ $key ] ) ) {
			return array();
		}

		$ids = array();

		foreach ( (array) wp_unslash( $_POST[ $key ] ) as $product_id ) {

			if ( ! is_scalar( $product_id ) ) {
				continue;
			}

			$product_id = absint( $product_id );

			if ( $product_id > 0 ) {
				$ids[] = $product_id;
			}

		}

		return $ids;

	}

}
