<?php
/**
 * Campaign Service
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Campaign;

defined( 'ABSPATH' ) || exit;

final class CampaignService {

	/**
	 * Repository.
	 *
	 * @var CampaignRepository
	 */
	private CampaignRepository $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->repository = new CampaignRepository();

	}

	/**
	 * Save campaign.
	 *
	 * @param array $campaign Campaign data.
	 *
	 * @return array
	 */
	public function save( array $campaign ): array {

		$campaign = $this->sanitize( $campaign );

		$validation = $this->validate( $campaign );

		if ( ! $validation['success'] ) {
			return $validation;
		}

		if ( ! empty( $campaign['id'] ) ) {

			$result = $this->repository->update(
				(int) $campaign['id'],
				$campaign
			);

			if ( ! $result ) {

				return array(
					'success' => false,
					'message' => __( 'Unable to update campaign.', 'hsg-campaign-manager' ),
				);

			}

			return array(
				'success' => true,
				'message' => __( 'Campaign updated.', 'hsg-campaign-manager' ),
			);

		}

		$result = $this->repository->create( $campaign );

		if ( is_wp_error( $result ) ) {

			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);

		}

		return array(
			'success' => true,
			'message' => __( 'Campaign created.', 'hsg-campaign-manager' ),
			'id'      => (int) $result,
		);

	}

	/**
	 * Delete campaign.
	 *
	 * @param int $id Campaign ID.
	 *
	 * @return array
	 */
	public function delete( int $id ): array {

		if ( ! $this->repository->delete( $id ) ) {

			return array(
				'success' => false,
				'message' => __( 'Unable to delete campaign.', 'hsg-campaign-manager' ),
			);

		}

		return array(
			'success' => true,
			'message' => __( 'Campaign deleted.', 'hsg-campaign-manager' ),
		);

	}

	/**
	 * Sanitize campaign.
	 *
	 * @param array $campaign Raw campaign.
	 *
	 * @return array
	 */
	private function sanitize( array $campaign ): array {

		return array(

			'id' => absint( $campaign['id'] ?? 0 ),

			'name' => sanitize_text_field(
				$campaign['name'] ?? ''
			),

			'status' => in_array(
				$campaign['status'] ?? 'draft',
				array( 'draft', 'publish' ),
				true
			)
				? $campaign['status']
				: 'draft',

			'start_date' => sanitize_text_field(
				$campaign['start_date'] ?? ''
			),

			'end_date' => sanitize_text_field(
				$campaign['end_date'] ?? ''
			),

			'priority' => absint(
				$campaign['priority'] ?? 10
			),

			'products' => array_map(
				'absint',
				(array) ( $campaign['products'] ?? array() )
			),

			'type' => sanitize_key(
				$campaign['type'] ?? 'fixed_price'
			),

			'value' => wc_format_decimal(
				$campaign['value'] ?? ''
			),

			'coupon' => sanitize_text_field(
				$campaign['coupon'] ?? ''
			),

			'stackable' => ! empty(
				$campaign['stackable']
			),

		);

	}

	/**
	 * Validate campaign.
	 *
	 * @param array $campaign Campaign.
	 *
	 * @return array
	 */
	private function validate( array $campaign ): array {

		if ( '' === $campaign['name'] ) {

			return array(
				'success' => false,
				'message' => __( 'Campaign name is required.', 'hsg-campaign-manager' ),
			);

		}

		if (
			'' !== $campaign['start_date'] &&
			'' !== $campaign['end_date'] &&
			strtotime( $campaign['start_date'] ) >
			strtotime( $campaign['end_date'] )
		) {

			return array(
				'success' => false,
				'message' => __( 'Start date must be before end date.', 'hsg-campaign-manager' ),
			);

		}

		if (
			! in_array(
				$campaign['type'],
				array(
					'fixed_price',
					'x_for_y',
					'coupon',
				),
				true
			)
		) {

			return array(
				'success' => false,
				'message' => __( 'Invalid campaign type.', 'hsg-campaign-manager' ),
			);

		}

		return array(
			'success' => true,
		);

	}

}
