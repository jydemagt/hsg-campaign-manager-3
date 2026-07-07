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
