<?php
/**
 * Campaign Loader
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Pricing;

use HSGCM\Campaign\CampaignRepository;

defined( 'ABSPATH' ) || exit;

final class CampaignLoader {

	/**
	 * Repository.
	 *
	 * @var CampaignRepository
	 */
	private CampaignRepository $repository;

	/**
	 * Active campaigns cache.
	 *
	 * @var array|null
	 */
	private ?array $active = null;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->repository = new CampaignRepository();

	}

	/**
	 * Load active published campaigns.
	 *
	 * @return array
	 */
	public function active(): array {

		if ( null !== $this->active ) {
			return $this->active;
		}

		$this->active = $this->active_for_date( current_time( 'Y-m-d' ) );

		return $this->active;

	}

	/**
	 * Load active published campaigns for a date.
	 *
	 * @param string $date Date in YYYY-MM-DD format.
	 *
	 * @return array
	 */
	public function active_for_date( string $date ): array {

		return $this->campaigns_for_date( $date )['active'];

	}

	/**
	 * Evaluate campaigns for a date.
	 *
	 * @param string $date Date in YYYY-MM-DD format.
	 *
	 * @return array
	 */
	public function campaigns_for_date( string $date ): array {

		$active   = array();
		$rejected = array();

		foreach ( $this->repository->all_raw() as $campaign ) {

			if ( ! is_array( $campaign ) ) {
				continue;
			}

			if ( 'publish' !== ( $campaign['status'] ?? '' ) ) {
				$rejected[] = array(
					'campaign' => $this->normalize( $campaign ),
					'reason'   => __( 'Campaign is not published.', 'hsg-campaign-manager' ),
				);
				continue;
			}

			if ( ! $this->is_active( $campaign, $date ) ) {
				$rejected[] = array(
					'campaign' => $this->normalize( $campaign ),
					'reason'   => __( 'Campaign is outside the selected date window.', 'hsg-campaign-manager' ),
				);
				continue;
			}

			if ( ! $this->has_pricing( $campaign ) ) {
				$rejected[] = array(
					'campaign' => $this->normalize( $campaign ),
					'reason'   => __( 'Campaign does not have usable pricing data.', 'hsg-campaign-manager' ),
				);
				continue;
			}

			$active[] = $this->normalize( $campaign );

		}

		return array(
			'active'   => $active,
			'rejected' => $rejected,
		);

	}

	/**
	 * Check campaign schedule.
	 *
	 * @param array  $campaign Campaign.
	 * @param string $today Current site date.
	 *
	 * @return bool
	 */
	private function is_active(
		array $campaign,
		string $today
	): bool {

		$start_date = sanitize_text_field( $campaign['start_date'] ?? '' );
		$end_date   = sanitize_text_field( $campaign['end_date'] ?? '' );

		if (
			'' !== $start_date &&
			! $this->is_valid_date( $start_date )
		) {
			return false;
		}

		if (
			'' !== $end_date &&
			! $this->is_valid_date( $end_date )
		) {
			return false;
		}

		if (
			'' !== $start_date &&
			$start_date > $today
		) {
			return false;
		}

		if (
			'' !== $end_date &&
			$end_date < $today
		) {
			return false;
		}

		return true;

	}

	/**
	 * Determine whether campaign has usable pricing data.
	 *
	 * @param array $campaign Campaign.
	 *
	 * @return bool
	 */
	private function has_pricing( array $campaign ): bool {

		$type = sanitize_key( $campaign['type'] ?? 'fixed_price' );

		if ( 'multi_buy' === $type ) {
			return $this->has_multi_buy_pricing( $campaign );
		}

		if ( ! in_array( $type, array( 'fixed_price', 'percentage_discount', 'fixed_discount' ), true ) ) {
			return false;
		}

		$value = $campaign['value'] ?? '';

		if ( '' === $value || ! is_numeric( $value ) ) {
			return false;
		}

		if (
			'percentage_discount' === $type &&
			(
				(float) $value < 1 ||
				(float) $value > 100
			)
		) {
			return false;
		}

		return (float) $value >= 0;

	}

	/**
	 * Determine whether campaign has usable multi-buy data.
	 *
	 * @param array $campaign Campaign.
	 *
	 * @return bool
	 */
	private function has_multi_buy_pricing( array $campaign ): bool {

		if ( empty( array_filter( (array) ( $campaign['products'] ?? array() ) ) ) ) {
			return false;
		}

		if ( ! $this->is_valid_multi_buy_quantity( $campaign['quantity'] ?? 0 ) ) {
			return false;
		}

		if ( '' === ( $campaign['bundle_price'] ?? '' ) || ! is_numeric( $campaign['bundle_price'] ) ) {
			return false;
		}

		return (float) $campaign['bundle_price'] > 0;

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

	/**
	 * Normalize campaign for pricing.
	 *
	 * @param array $campaign Campaign.
	 *
	 * @return object
	 */
	private function normalize( array $campaign ): object {

		return (object) array(
			'id'           => absint( $campaign['id'] ?? 0 ),
			'name'         => sanitize_text_field( $campaign['name'] ?? '' ),
			'priority'     => absint( $campaign['priority'] ?? 0 ),
			'quantity'     => absint( $campaign['quantity'] ?? 2 ),
			'bundle_price' => (float) wc_format_decimal( $campaign['bundle_price'] ?? 0 ),
			'products'     => array_values(
				array_filter(
					array_map( 'absint', (array) ( $campaign['products'] ?? array() ) )
				)
			),
			'type'         => sanitize_key( $campaign['type'] ?? 'fixed_price' ),
			'value'        => (float) wc_format_decimal( $campaign['value'] ?? 0 ),
			'coupon'       => sanitize_text_field( $campaign['coupon'] ?? '' ),
			'stackable'    => ! empty( $campaign['stackable'] ),
			'start_date'   => sanitize_text_field( $campaign['start_date'] ?? '' ),
			'end_date'     => sanitize_text_field( $campaign['end_date'] ?? '' ),
		);

	}

	/**
	 * Validate a quantity string for multi-buy campaigns.
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

}
