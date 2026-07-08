<?php
/**
 * Campaign Label Service
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Pricing;

defined( 'ABSPATH' ) || exit;

final class CampaignLabelService {

	/**
	 * Coupon service.
	 *
	 * @var CouponService
	 */
	private CouponService $coupon_service;

	/**
	 * Constructor.
	 *
	 * @param CouponService $coupon_service Coupon service.
	 */
	public function __construct( CouponService $coupon_service ) {

		$this->coupon_service = $coupon_service;

	}

	/**
	 * Return prepared campaign labels for a product.
	 *
	 * @param int   $product_id   Product ID.
	 * @param array $coupon_codes Coupon codes.
	 *
	 * @return array
	 */
	public function labels_for_product(
		int $product_id,
		array $coupon_codes = array()
	): array {

		if ( $product_id <= 0 ) {
			return array();
		}

		$labels = array();

		foreach ( $this->coupon_service->resolveCampaignsForProduct( $product_id, $coupon_codes ) as $campaign ) {

			$text = $this->build_label_text( $campaign );

			if ( '' === $text ) {
				continue;
			}

			$label = sprintf(
				/* translators: %s: campaign label text. */
				__( 'Campaign: %s', 'hsg-campaign-manager' ),
				$text
			);

			$labels[ $label ] = $label;

		}

		return array_values( $labels );

	}

	/**
	 * Return prepared cart item data rows for a product.
	 *
	 * @param int   $product_id   Product ID.
	 * @param array $coupon_codes Coupon codes.
	 *
	 * @return array
	 */
	public function item_data_for_product(
		int $product_id,
		array $coupon_codes = array()
	): array {

		if ( $product_id <= 0 ) {
			return array();
		}

		$items = array();

		foreach ( $this->coupon_service->resolveCampaignsForProduct( $product_id, $coupon_codes ) as $campaign ) {

			$text = $this->build_label_text( $campaign );

			if ( '' === $text ) {
				continue;
			}

			$items[ $text ] = array(
				'name'    => __( 'Campaign', 'hsg-campaign-manager' ),
				'display' => $text,
			);

		}

		return array_values( $items );

	}

	/**
	 * Build a customer-facing label for a campaign.
	 *
	 * @param object $campaign Campaign.
	 *
	 * @return string
	 */
	private function build_label_text( object $campaign ): string {

		switch ( $campaign->type ?? '' ) {
			case 'fixed_price':
				return sprintf(
					/* translators: %s: formatted campaign price. */
					__( 'Fixed price %s', 'hsg-campaign-manager' ),
					$this->format_price( (float) ( $campaign->value ?? 0 ) )
				);

			case 'percentage_discount':
				return sprintf(
					/* translators: %s: percentage discount value. */
					__( '%s%% discount', 'hsg-campaign-manager' ),
					$this->format_number( (float) ( $campaign->value ?? 0 ) )
				);

			case 'fixed_discount':
				return sprintf(
					/* translators: %s: formatted discount amount. */
					__( '%s discount', 'hsg-campaign-manager' ),
					$this->format_price( (float) ( $campaign->value ?? 0 ) )
				);

			case 'multi_buy':
				return sprintf(
					/* translators: 1: campaign quantity, 2: formatted bundle price. */
					__( '%1$d for %2$s', 'hsg-campaign-manager' ),
					max( 0, (int) ( $campaign->quantity ?? 0 ) ),
					$this->format_price( (float) ( $campaign->bundle_price ?? 0 ) )
				);
		}

		return '';

	}

	/**
	 * Format a price for plain-text display.
	 *
	 * @param float $price Price.
	 *
	 * @return string
	 */
	private function format_price( float $price ): string {

		return wp_strip_all_tags(
			wc_price( $price )
		);

	}

	/**
	 * Format a number for plain-text display.
	 *
	 * @param float $number Number.
	 *
	 * @return string
	 */
	private function format_number( float $number ): string {

		return rtrim(
			rtrim(
				wc_format_decimal( $number ),
				'0'
			),
			'.'
		);

	}

}
