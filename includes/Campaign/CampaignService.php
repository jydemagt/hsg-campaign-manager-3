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
	 * Get campaign.
	 *
	 * @param int $id Campaign ID.
	 *
	 * @return array|null
	 */
	public function get( int $id ): ?array {

		return $this->repository->find( $id );

	}

	/**
	 * Get all campaigns.
	 *
	 * @return array
	 */
	public function all(): array {

		return $this->repository->all();

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

		$campaign = $this->normalize( $campaign );

		if ( $campaign['id'] > 0 ) {

			if ( ! $this->repository->update( $campaign['id'], $campaign ) ) {

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

		$new_id = $this->repository->create( $campaign );

		if ( is_wp_error( $new_id ) ) {

			return array(
				'success' => false,
				'message' => $new_id->get_error_message(),
			);

		}

		return array(
			'success' => true,
			'id'      => (int) $new_id,
			'message' => __( 'Campaign created.', 'hsg-campaign-manager' ),
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

		$priority = $campaign['priority'] ?? '0';

		if ( ! is_scalar( $priority ) ) {
			$priority = '';
		}

		return array(
			'id'         => absint( $campaign['id'] ?? 0 ),
			'name'       => sanitize_text_field( $campaign['name'] ?? '' ),
			'status'     => sanitize_key( $campaign['status'] ?? 'draft' ),
			'start_date' => sanitize_text_field( $campaign['start_date'] ?? '' ),
			'end_date'   => sanitize_text_field( $campaign['end_date'] ?? '' ),
			'priority'   => sanitize_text_field( (string) $priority ),
			'products'   => array_map( 'absint', (array) ( $campaign['products'] ?? array() ) ),
			'type'       => sanitize_key( $campaign['type'] ?? 'fixed_price' ),
			'value'      => sanitize_text_field( (string) ( $campaign['value'] ?? '' ) ),
			'coupon'     => sanitize_text_field( $campaign['coupon'] ?? '' ),
			'stackable'  => ! empty( $campaign['stackable'] ),
		);

	}

	/**
	 * Normalize validated campaign values for storage.
	 *
	 * @param array $campaign Sanitized campaign.
	 *
	 * @return array
	 */
	private function normalize( array $campaign ): array {

		$campaign['priority'] = (int) $campaign['priority'];
		$campaign['value']    = wc_format_decimal( $campaign['value'] );
		$campaign['products'] = array_values(
			array_filter(
				array_map( 'absint', $campaign['products'] )
			)
		);

		return $campaign;

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

		if ( ! in_array( $campaign['status'], array( 'draft', 'publish' ), true ) ) {

			return array(
				'success' => false,
				'message' => __( 'Campaign status is invalid.', 'hsg-campaign-manager' ),
			);

		}

		if ( ! in_array( $campaign['type'], array( 'fixed_price', 'percentage_discount', 'fixed_discount' ), true ) ) {

			return array(
				'success' => false,
				'message' => __( 'Campaign type is invalid.', 'hsg-campaign-manager' ),
			);

		}

		if (
			'' !== $campaign['start_date'] &&
			! $this->is_valid_date( $campaign['start_date'] )
		) {

			return array(
				'success' => false,
				'message' => __( 'Start date is invalid.', 'hsg-campaign-manager' ),
			);

		}

		if (
			'' !== $campaign['end_date'] &&
			! $this->is_valid_date( $campaign['end_date'] )
		) {

			return array(
				'success' => false,
				'message' => __( 'End date is invalid.', 'hsg-campaign-manager' ),
			);

		}

		if (
			'' !== $campaign['start_date'] &&
			'' !== $campaign['end_date'] &&
			strtotime( $campaign['end_date'] ) < strtotime( $campaign['start_date'] )
		) {

			return array(
				'success' => false,
				'message' => __( 'End date must be greater than or equal to start date.', 'hsg-campaign-manager' ),
			);

		}

		if ( ! $this->is_valid_priority( $campaign['priority'] ) ) {

			return array(
				'success' => false,
				'message' => __( 'Priority must be a non-negative integer.', 'hsg-campaign-manager' ),
			);

		}

		if ( '' === $campaign['value'] ) {

			return array(
				'success' => false,
				'message' => __( 'Campaign value is required.', 'hsg-campaign-manager' ),
			);

		}

		if ( ! is_numeric( $campaign['value'] ) ) {

			return array(
				'success' => false,
				'message' => __( 'Campaign value must be numeric.', 'hsg-campaign-manager' ),
			);

		}

		if (
			in_array( $campaign['type'], array( 'fixed_price', 'fixed_discount' ), true ) &&
			(float) $campaign['value'] < 0
		) {

			return array(
				'success' => false,
				'message' => __( 'Campaign value cannot be negative.', 'hsg-campaign-manager' ),
			);

		}

		if (
			'percentage_discount' === $campaign['type'] &&
			(
				(float) $campaign['value'] < 1 ||
				(float) $campaign['value'] > 100
			)
		) {

			return array(
				'success' => false,
				'message' => __( 'Percentage discount must be between 1 and 100.', 'hsg-campaign-manager' ),
			);

		}

		return array(
			'success' => true,
		);

	}

	/**
	 * Check whether a priority value is a non-negative integer.
	 *
	 * @param mixed $priority Priority value.
	 *
	 * @return bool
	 */
	private function is_valid_priority( $priority ): bool {

		if ( ! is_scalar( $priority ) ) {
			return false;
		}

		$priority = trim( (string) $priority );

		return '' !== $priority && ctype_digit( $priority );

	}

	/**
	 * Validate a date string in YYYY-MM-DD format.
	 *
	 * @param string $date Date.
	 *
	 * @return bool
	 */
	private function is_valid_date( string $date ): bool {

		$parsed = date_parse_from_format( 'Y-m-d', $date );

		return (
			0 === $parsed['warning_count'] &&
			0 === $parsed['error_count'] &&
			checkdate(
				(int) $parsed['month'],
				(int) $parsed['day'],
				(int) $parsed['year']
			)
		);

	}

}
