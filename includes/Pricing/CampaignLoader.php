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

		$campaigns = array();
		$today     = current_time( 'Y-m-d' );

		foreach ( $this->repository->published() as $post ) {

			$campaign = $this->repository->find_raw( (int) $post->ID );

			if (
				! $campaign ||
				! $this->is_active( $campaign, $today ) ||
				! $this->has_pricing( $campaign )
			) {
				continue;
			}

			$campaigns[] = $this->normalize( $campaign );

		}

		$this->active = $campaigns;

		return $this->active;

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

		if (
			'' !== $campaign['start_date'] &&
			! $this->is_valid_date( $campaign['start_date'] )
		) {
			return false;
		}

		if (
			'' !== $campaign['end_date'] &&
			! $this->is_valid_date( $campaign['end_date'] )
		) {
			return false;
		}

		if (
			'' !== $campaign['start_date'] &&
			$campaign['start_date'] > $today
		) {
			return false;
		}

		if (
			'' !== $campaign['end_date'] &&
			$campaign['end_date'] < $today
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

		if ( ! in_array( $campaign['type'], array( 'fixed_price', 'percentage_discount', 'fixed_discount' ), true ) ) {
			return false;
		}

		if ( '' === $campaign['value'] || ! is_numeric( $campaign['value'] ) ) {
			return false;
		}

		if (
			'percentage_discount' === $campaign['type'] &&
			(
				(float) $campaign['value'] < 1 ||
				(float) $campaign['value'] > 100
			)
		) {
			return false;
		}

		return (float) $campaign['value'] >= 0;

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
			'id'         => absint( $campaign['id'] ?? 0 ),
			'name'       => sanitize_text_field( $campaign['name'] ?? '' ),
			'priority'   => absint( $campaign['priority'] ?? 0 ),
			'products'   => array_values(
				array_filter(
					array_map( 'absint', (array) ( $campaign['products'] ?? array() ) )
				)
			),
			'type'       => sanitize_key( $campaign['type'] ?? 'fixed_price' ),
			'value'      => (float) wc_format_decimal( $campaign['value'] ?? 0 ),
			'stackable'  => ! empty( $campaign['stackable'] ),
			'start_date' => sanitize_text_field( $campaign['start_date'] ?? '' ),
			'end_date'   => sanitize_text_field( $campaign['end_date'] ?? '' ),
		);

	}

}
