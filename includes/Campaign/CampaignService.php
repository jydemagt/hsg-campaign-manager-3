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
	 * Get campaigns prepared for the admin list table.
	 *
	 * @return array
	 */
	public function list_rows(): array {

		$rows = array();

		foreach ( $this->repository->all_raw() as $campaign ) {

			$campaign = $this->normalize_preview_campaign( $campaign );

			$rows[] = array(
				'id'              => $campaign['id'],
				'name'            => $campaign['name'],
				'status'          => $this->get_status_label( $campaign['status'] ),
				'type'            => $this->get_type_label( $campaign['type'] ),
				'products_count'  => count( $campaign['products'] ),
				'priority'        => $campaign['priority'],
				'start_date'      => $this->get_date_label( $campaign['start_date'] ),
				'end_date'        => $this->get_date_label( $campaign['end_date'] ),
				'stackable'       => $campaign['stackable']
					? __( 'Yes', 'hsg-campaign-manager' )
					: __( 'No', 'hsg-campaign-manager' ),
				'conflict_status' => $this->get_list_conflict_status( $campaign ),
			);

		}

		return $rows;

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
	 * Preview campaign conflicts without saving.
	 *
	 * @param array $campaign Campaign data.
	 *
	 * @return array
	 */
	public function preview_conflicts( array $campaign ): array {

		$campaign = $this->normalize_preview_campaign(
			$this->sanitize( $campaign )
		);

		if (
			! in_array( $campaign['status'], array( 'draft', 'publish' ), true ) ||
			empty( $campaign['products'] )
		) {
			return array(
				'success'   => true,
				'message'   => __( 'No conflicts found.', 'hsg-campaign-manager' ),
				'conflicts' => array(),
			);
		}

		$conflicts = array();

		foreach ( $this->repository->all_raw() as $other ) {

			$other = $this->normalize_preview_campaign( $other );

			if (
				$other['id'] === $campaign['id'] ||
				! in_array( $other['status'], array( 'draft', 'publish' ), true ) ||
				empty( $other['products'] )
			) {
				continue;
			}

			$overlapping_products = array_values(
				array_intersect(
					$campaign['products'],
					$other['products']
				)
			);

			if (
				empty( $overlapping_products ) ||
				! $this->date_ranges_overlap( $campaign, $other ) ||
				$this->campaigns_can_stack( $campaign, $other )
			) {
				continue;
			}

			$conflicts[] = array(
				'campaign_id'          => $other['id'],
				'campaign_name'        => $other['name'],
				'overlapping_products' => $this->repository->products_for_ids( $overlapping_products ),
				'current_priority'     => $campaign['priority'],
				'other_priority'       => $other['priority'],
				'priority_comparison'  => $this->format_priority_comparison( $campaign, $other ),
				'winner'               => $this->determine_conflict_winner( $campaign, $other ),
			);

		}

		return array(
			'success'   => true,
			'message'   => empty( $conflicts )
				? __( 'No conflicts found.', 'hsg-campaign-manager' )
				: __( 'Potential campaign conflicts found.', 'hsg-campaign-manager' ),
			'conflicts' => $conflicts,
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
		$quantity = $campaign['quantity'] ?? '2';

		if ( ! is_scalar( $priority ) ) {
			$priority = '';
		}

		if ( ! is_scalar( $quantity ) ) {
			$quantity = '';
		}

		return array(
			'id'         => absint( $campaign['id'] ?? 0 ),
			'name'       => sanitize_text_field( $campaign['name'] ?? '' ),
			'status'     => sanitize_key( $campaign['status'] ?? 'draft' ),
			'start_date' => sanitize_text_field( $campaign['start_date'] ?? '' ),
			'end_date'   => sanitize_text_field( $campaign['end_date'] ?? '' ),
			'priority'   => sanitize_text_field( (string) $priority ),
			'quantity'   => sanitize_text_field( (string) $quantity ),
			'bundle_price' => sanitize_text_field( (string) ( $campaign['bundle_price'] ?? '' ) ),
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
		$campaign['quantity'] = (int) $campaign['quantity'];
		$campaign['value']    = '' === $campaign['value']
			? ''
			: wc_format_decimal( $campaign['value'] );
		$campaign['bundle_price'] = '' === $campaign['bundle_price']
			? ''
			: wc_format_decimal( $campaign['bundle_price'] );
		$campaign['products'] = array_values(
			array_filter(
				array_map( 'absint', $campaign['products'] )
			)
		);

		return $campaign;

	}

	/**
	 * Normalize campaign values used by conflict preview.
	 *
	 * @param array $campaign Campaign.
	 *
	 * @return array
	 */
	private function normalize_preview_campaign( array $campaign ): array {

		return array(
			'id'         => absint( $campaign['id'] ?? 0 ),
			'name'       => sanitize_text_field( $campaign['name'] ?? '' ),
			'status'     => sanitize_key( $campaign['status'] ?? 'draft' ),
			'start_date' => sanitize_text_field( $campaign['start_date'] ?? '' ),
			'end_date'   => sanitize_text_field( $campaign['end_date'] ?? '' ),
			'priority'   => $this->is_valid_priority( $campaign['priority'] ?? '0' )
				? (int) $campaign['priority']
				: 0,
			'type'       => sanitize_key( $campaign['type'] ?? 'fixed_price' ),
			'products'   => array_values(
				array_filter(
					array_unique(
						array_map( 'absint', (array) ( $campaign['products'] ?? array() ) )
					)
				)
			),
			'stackable'  => ! empty( $campaign['stackable'] ),
		);

	}

	/**
	 * Get the display label for a campaign status.
	 *
	 * @param string $status Campaign status.
	 *
	 * @return string
	 */
	private function get_status_label( string $status ): string {

		if ( 'publish' === $status ) {
			return __( 'Active', 'hsg-campaign-manager' );
		}

		if ( 'draft' === $status ) {
			return __( 'Draft', 'hsg-campaign-manager' );
		}

		return __( 'Unknown', 'hsg-campaign-manager' );

	}

	/**
	 * Get the display label for a campaign type.
	 *
	 * @param string $type Campaign type.
	 *
	 * @return string
	 */
	private function get_type_label( string $type ): string {

		$labels = array(
			'fixed_price'         => __( 'Fixed price', 'hsg-campaign-manager' ),
			'percentage_discount' => __( 'Percentage discount', 'hsg-campaign-manager' ),
			'fixed_discount'      => __( 'Fixed discount', 'hsg-campaign-manager' ),
			'multi_buy'           => __( 'X products for Y price', 'hsg-campaign-manager' ),
		);

		return $labels[ $type ] ?? __( 'Unknown', 'hsg-campaign-manager' );

	}

	/**
	 * Get the display label for a campaign date.
	 *
	 * @param string $date Campaign date.
	 *
	 * @return string
	 */
	private function get_date_label( string $date ): string {

		if ( '' === $date ) {
			return __( 'Not set', 'hsg-campaign-manager' );
		}

		return $date;

	}

	/**
	 * Get the conflict status label for the admin list.
	 *
	 * @param array $campaign Campaign.
	 *
	 * @return string
	 */
	private function get_list_conflict_status( array $campaign ): string {

		if (
			! in_array( $campaign['status'], array( 'draft', 'publish' ), true ) ||
			empty( $campaign['products'] )
		) {
			return __( 'Not checked', 'hsg-campaign-manager' );
		}

		$preview = $this->preview_conflicts( $campaign );

		if ( empty( $preview['success'] ) ) {
			return __( 'Not checked', 'hsg-campaign-manager' );
		}

		return empty( $preview['conflicts'] )
			? __( 'OK', 'hsg-campaign-manager' )
			: __( 'Conflict', 'hsg-campaign-manager' );

	}

	/**
	 * Determine whether two campaign schedules overlap.
	 *
	 * @param array $campaign Campaign.
	 * @param array $other    Other campaign.
	 *
	 * @return bool
	 */
	private function date_ranges_overlap(
		array $campaign,
		array $other
	): bool {

		$campaign_start = $this->normalize_preview_date( $campaign['start_date'] );
		$campaign_end   = $this->normalize_preview_date( $campaign['end_date'] );
		$other_start    = $this->normalize_preview_date( $other['start_date'] );
		$other_end      = $this->normalize_preview_date( $other['end_date'] );

		if (
			null !== $campaign_start &&
			null !== $campaign_end &&
			$campaign_end < $campaign_start
		) {
			return false;
		}

		if (
			null !== $other_start &&
			null !== $other_end &&
			$other_end < $other_start
		) {
			return false;
		}

		return (
			( null === $campaign_start || null === $other_end || $campaign_start <= $other_end ) &&
			( null === $other_start || null === $campaign_end || $other_start <= $campaign_end )
		);

	}

	/**
	 * Normalize a preview date.
	 *
	 * @param string $date Date.
	 *
	 * @return string|null
	 */
	private function normalize_preview_date( string $date ): ?string {

		if ( '' === $date || ! $this->is_valid_date( $date ) ) {
			return null;
		}

		return $date;

	}

	/**
	 * Determine whether two campaigns can stack without blocking each other.
	 *
	 * @param array $campaign Campaign.
	 * @param array $other    Other campaign.
	 *
	 * @return bool
	 */
	private function campaigns_can_stack(
		array $campaign,
		array $other
	): bool {

		return $campaign['stackable'] && $other['stackable'];

	}

	/**
	 * Determine the winner for a campaign conflict.
	 *
	 * @param array $campaign Campaign.
	 * @param array $other    Other campaign.
	 *
	 * @return array
	 */
	private function determine_conflict_winner(
		array $campaign,
		array $other
	): array {

		$current_wins = $campaign['priority'] > $other['priority'];

		if ( $campaign['priority'] === $other['priority'] ) {
			$current_wins = $campaign['id'] > $other['id'];
		}

		if ( $current_wins ) {
			return array(
				'id'   => $campaign['id'],
				'name' => '' !== $campaign['name']
					? $campaign['name']
					: __( 'Current campaign', 'hsg-campaign-manager' ),
				'type' => 'current',
			);
		}

		return array(
			'id'   => $other['id'],
			'name' => $other['name'],
			'type' => 'other',
		);

	}

	/**
	 * Format priority comparison text.
	 *
	 * @param array $campaign Campaign.
	 * @param array $other    Other campaign.
	 *
	 * @return string
	 */
	private function format_priority_comparison(
		array $campaign,
		array $other
	): string {

		if ( $campaign['priority'] > $other['priority'] ) {
			return sprintf(
				/* translators: 1: current campaign priority, 2: conflicting campaign priority. */
				__( 'Current priority %1$d is higher than conflicting priority %2$d.', 'hsg-campaign-manager' ),
				$campaign['priority'],
				$other['priority']
			);
		}

		if ( $campaign['priority'] < $other['priority'] ) {
			return sprintf(
				/* translators: 1: current campaign priority, 2: conflicting campaign priority. */
				__( 'Current priority %1$d is lower than conflicting priority %2$d.', 'hsg-campaign-manager' ),
				$campaign['priority'],
				$other['priority']
			);
		}

		return sprintf(
			/* translators: 1: shared priority value. */
			__( 'Both campaigns have priority %1$d; the higher campaign ID wins.', 'hsg-campaign-manager' ),
			$campaign['priority']
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

		if ( ! in_array( $campaign['status'], array( 'draft', 'publish' ), true ) ) {

			return array(
				'success' => false,
				'message' => __( 'Campaign status is invalid.', 'hsg-campaign-manager' ),
			);

		}

		if ( ! in_array( $campaign['type'], array( 'fixed_price', 'percentage_discount', 'fixed_discount', 'multi_buy' ), true ) ) {

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

		if ( 'multi_buy' === $campaign['type'] ) {

			if ( empty( array_filter( array_map( 'absint', (array) $campaign['products'] ) ) ) ) {

				return array(
					'success' => false,
					'message' => __( 'Multi-buy campaigns require at least one product.', 'hsg-campaign-manager' ),
				);

			}

			if ( ! $this->is_valid_multi_buy_quantity( $campaign['quantity'] ) ) {

				return array(
					'success' => false,
					'message' => __( 'Quantity must be an integer of 2 or greater.', 'hsg-campaign-manager' ),
				);

			}

			if ( '' === $campaign['bundle_price'] || ! is_numeric( $campaign['bundle_price'] ) || (float) $campaign['bundle_price'] <= 0 ) {

				return array(
					'success' => false,
					'message' => __( 'Bundle price must be greater than 0.', 'hsg-campaign-manager' ),
				);

			}

		} else {

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
	 * Check whether a quantity value is an integer of 2 or greater.
	 *
	 * @param mixed $quantity Quantity value.
	 *
	 * @return bool
	 */
	private function is_valid_multi_buy_quantity( $quantity ): bool {

		if ( ! is_scalar( $quantity ) ) {
			return false;
		}

		$quantity = trim( (string) $quantity );

		return '' !== $quantity && ctype_digit( $quantity ) && (int) $quantity >= 2;

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
