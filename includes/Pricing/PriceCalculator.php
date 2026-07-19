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

		return max( 0.0, (float) wc_format_decimal( $price ) );

	}

	/**
	 * Calculate a single-product multi-buy total.
	 *
	 * @param float  $base_price Base unit price.
	 * @param int    $quantity Quantity.
	 * @param object $campaign Campaign.
	 *
	 * @return float
	 */
	public function calculate_multi_buy_total(
		float $base_price,
		int $quantity,
		object $campaign
	): float {

		$quantity     = max( 0, $quantity );
		$bundle_size  = max( 0, (int) ( $campaign->quantity ?? 0 ) );
		$bundle_price = (float) ( $campaign->bundle_price ?? 0 );

		if ( $quantity <= 0 || $bundle_size < 2 || $bundle_price <= 0 ) {
			return 0.0;
		}

		$bundle_count      = intdiv( $quantity, $bundle_size );
		$remaining         = $quantity % $bundle_size;
		$multi_buy_total   = $bundle_count * $bundle_price;
		$remaining_total   = $remaining * max( 0.0, $base_price );

		return max(
			0.0,
			(float) wc_format_decimal( $multi_buy_total + $remaining_total )
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
				return max( 0.0, (float) $campaign->value );

			case 'percentage_discount':
				return max(
					0.0,
					$price - ( $price * ( (float) $campaign->value / 100 ) )
				);

			case 'fixed_discount':
				return max( 0.0, $price - (float) $campaign->value );
		}

		return $price;

	}

}
