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

		add_action(
			'wp_ajax_hsgcm_save_campaign',
			array( $this, 'save_campaign' )
		);

		add_action(
			'wp_ajax_hsgcm_get_campaign',
			array( $this, 'get_campaign' )
		);

		add_action(
			'wp_ajax_hsgcm_delete_campaign',
			array( $this, 'delete_campaign' )
		);

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
	 * Save campaign.
	 *
	 * @return void
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
	 * Get campaign.
	 *
	 * @return void
	 */
	public function get_campaign(): void {

		$this->verify();

		$id = absint( $_POST['id'] ?? 0 );

		$campaign = $this->repository->find( $id );

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

}
