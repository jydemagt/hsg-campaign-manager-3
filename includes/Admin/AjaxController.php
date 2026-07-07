<?php
/**
 * AJAX Controller
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Admin;

use HSGCM\Campaign\CampaignRepository;
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
	 * Campaign repository.
	 *
	 * @var CampaignRepository
	 */
	private CampaignRepository $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->service    = new CampaignService();
		$this->repository = new CampaignRepository();

		add_action( 'wp_ajax_hsgcm_get_campaign', array( $this, 'get_campaign' ) );
		add_action( 'wp_ajax_hsgcm_save_campaign', array( $this, 'save_campaign' ) );
		add_action( 'wp_ajax_hsgcm_delete_campaign', array( $this, 'delete_campaign' ) );

	}

	/**
	 * Verify request.
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
	 * Load campaign.
	 */
	public function get_campaign(): void {

		$this->verify();

		$id = absint( $_POST['id'] ?? 0 );

		$campaign = $this->service->get( $id );

		if ( null === $campaign ) {

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
	 */
	public function save_campaign(): void {

		$this->verify();

		$result = $this->service->save(
			array(
				'id'         => absint( $_POST['id'] ?? 0 ),
				'name'       => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'status'     => sanitize_text_field( wp_unslash( $_POST['status'] ?? 'draft' ) ),
				'start_date' => sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) ),
				'end_date'   => sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) ),
				'priority'   => absint( $_POST['priority'] ?? 10 ),
				'products'   => array(),
				'type'       => 'fixed_price',
				'value'      => '',
				'coupon'     => '',
				'stackable'  => false,
			)
		);

		if ( ! $result['success'] ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );

	}

	/**
	 * Delete campaign.
	 */
	public function delete_campaign(): void {

		$this->verify();

		$id = absint( $_POST['id'] ?? 0 );

		$result = $this->service->delete( $id );

		if ( ! $result['success'] ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );

	}

}
