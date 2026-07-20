<?php
/**
 * Price Calculator
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Pricing;

defined( 'ABSPATH' ) || exit;

final class PriceCalculator {

	/**
	 * Apply campaigns to a price.
	 *
	 * @param float $regular_price Regular price.
	 * @param array $campaigns     Campaigns.
	 *
	 * @return float
	 */
	public function calculate(
		float $regular_price,
		array $campaigns
	): float {
		$price = $regular_price;

		foreach ( $campaigns as $campaign ) {
			$price = $this->apply( $price, $campaign );
		}

		return max(
			0.0,
			(float) wc_format_decimal( $price )
		);
	}

	/**
	 * Calculate a single-product multi-buy total.
	 *
	 * Invalid multi-buy configuration fails safely by returning the
	 * product's normal total instead of a zero-priced total.
	 *
	 * @param float  $base_price Base unit price.
	 * @param int    $quantity   Quantity.
	 * @param object $campaign   Campaign.
	 *
	 * @return float
	 */
	public function calculate_multi_buy_total(
		float $base_price,
		int $quantity,
		object $campaign
	): float {
		$quantity   = max( 0, $quantity );
		$base_price = max( 0.0, $base_price );

		if ( $quantity <= 0 ) {
			return 0.0;
		}

		$regular_total = $quantity * $base_price;
		$bundle_size   = max(
			0,
			(int) ( $campaign->quantity ?? 0 )
		);
		$bundle_price = (float) (
			$campaign->bundle_price ?? 0
		);

		if ( $bundle_size < 2 || $bundle_price <= 0 ) {
			return max(
				0.0,
				(float) wc_format_decimal( $regular_total )
			);
		}

		$bundle_count = intdiv( $quantity, $bundle_size );
		$remaining    = $quantity % $bundle_size;

		$multi_buy_total = $bundle_count * $bundle_price;
		$remaining_total = $remaining * $base_price;

		$campaign_total = $multi_buy_total + $remaining_total;

		return max(
			0.0,
			(float) wc_format_decimal(
				min( $regular_total, $campaign_total )
			)
		);
	}

	/**
	 * Apply a single campaign.
	 *
	 * @param float  $price    Current price.
	 * @param object $campaign Campaign.
	 *
	 * @return float
	 */
	private function apply(
		float $price,
		object $campaign
	): float {
		switch ( $campaign->type ) {
			case 'fixed_price':
				return max(
					0.0,
					(float) $campaign->value
				);

			case 'percentage_discount':
				return max(
					0.0,
					$price - (
						$price *
						( (float) $campaign->value / 100 )
					)
				);

			case 'fixed_discount':
				return max(
					0.0,
					$price - (float) $campaign->value
				);
		}

		return $price;
	}
}