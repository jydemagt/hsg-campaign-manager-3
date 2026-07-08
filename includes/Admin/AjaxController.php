<?php
/**
 * AJAX Controller
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Admin;

use HSGCM\Campaign\CampaignService;

defined( 'ABSPATH' ) || exit;

final class AjaxController {

	/**
	 * Campaign service.
	 *
	 * @var CampaignService
	 */
	private CampaignService $service;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->service = new CampaignService();

		add_action( 'wp_ajax_hsgcm_get_campaign', array( $this, 'get_campaign' ) );
		add_action( 'wp_ajax_hsgcm_save_campaign', array( $this, 'save_campaign' ) );
		add_action( 'wp_ajax_hsgcm_delete_campaign', array( $this, 'delete_campaign' ) );
		add_action( 'wp_ajax_hsgcm_preview_conflicts', array( $this, 'preview_conflicts' ) );

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

		$id = absint( $_POST['id'] ?? 0 );

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

		$products = array();

		if ( isset( $_POST['products'] ) ) {

			$products = array_map(
				'absint',
				(array) wp_unslash( $_POST['products'] )
			);

		}

		$priority = $_POST['priority'] ?? '0';

		if ( ! is_scalar( $priority ) ) {
			$priority = '';
		}

		$result = $this->service->save(
			array(
				'id'         => absint( $_POST['id'] ?? 0 ),
				'name'       => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'status'     => sanitize_text_field( wp_unslash( $_POST['status'] ?? 'draft' ) ),
				'start_date' => sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) ),
				'end_date'   => sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) ),
				'priority'   => sanitize_text_field( wp_unslash( (string) $priority ) ),
				'quantity'   => sanitize_text_field( wp_unslash( $_POST['quantity'] ?? '2' ) ),
				'bundle_price' => sanitize_text_field( wp_unslash( $_POST['bundle_price'] ?? '' ) ),
				'products'   => $products,
				'type'       => sanitize_key( wp_unslash( $_POST['type'] ?? 'fixed_price' ) ),
				'value'      => sanitize_text_field( wp_unslash( $_POST['value'] ?? '' ) ),
				'coupon'     => sanitize_text_field( wp_unslash( $_POST['coupon'] ?? '' ) ),
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
			absint( $_POST['id'] ?? 0 )
		);

		if ( ! $result['success'] ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );

	}

	/**
	 * Preview campaign conflicts.
	 *
	 * @return void
	 */
	public function preview_conflicts(): void {

		$this->verify();

		$products = array();

		if ( isset( $_POST['products'] ) ) {

			$products = array_map(
				'absint',
				(array) wp_unslash( $_POST['products'] )
			);

		}

		$priority = $_POST['priority'] ?? '0';

		if ( ! is_scalar( $priority ) ) {
			$priority = '';
		}

		$result = $this->service->preview_conflicts(
			array(
				'id'         => absint( $_POST['id'] ?? 0 ),
				'name'       => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'status'     => sanitize_text_field( wp_unslash( $_POST['status'] ?? 'draft' ) ),
				'start_date' => sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) ),
				'end_date'   => sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) ),
				'priority'   => sanitize_text_field( wp_unslash( (string) $priority ) ),
				'products'   => $products,
				'stackable'  => ! empty( $_POST['stackable'] ),
			)
		);

		if ( ! $result['success'] ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );

	}

}
