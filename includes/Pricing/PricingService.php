<?php
/**
 * Pricing Service
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Pricing;

defined( 'ABSPATH' ) || exit;

final class PricingService {

	/**
	 * Coupon service.
	 *
	 * @var CouponService
	 */
	private CouponService $coupon_service;

	/**
	 * Price calculator.
	 *
	 * @var PriceCalculator
	 */
	private PriceCalculator $calculator;

	/**
	 * Cart pricing service.
	 *
	 * @var CartPricingService
	 */
	private CartPricingService $cart_pricing;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->coupon_service = new CouponService();
		$this->calculator     = new PriceCalculator();
		$this->cart_pricing   = new CartPricingService(
			$this->coupon_service,
			$this->calculator
		);

		add_filter( 'woocommerce_product_get_price', array( $this, 'filter_price' ), 20, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'filter_price' ), 20, 2 );
		add_filter( 'woocommerce_variation_prices_price', array( $this, 'filter_price' ), 20, 2 );

	}

	/**
	 * Return campaign-adjusted product price.
	 *
	 * @param int   $product_id    Product ID.
	 * @param float $regular_price Regular price.
	 *
	 * @return float
	 */
	public function getProductPrice(
		int $product_id,
		float $regular_price
	): float {

		if ( $product_id <= 0 || $regular_price < 0 ) {
			return $regular_price;
		}

		$campaigns = array_filter(
			$this->coupon_service->resolveCampaignsForProduct( $product_id ),
			static function ( object $campaign ): bool {
				return (
					'multi_buy' !== $campaign->type &&
					empty( $campaign->coupon )
				);
			}
		);

		if ( empty( $campaigns ) ) {
			return $regular_price;
		}

		return $this->calculator->calculate( $regular_price, $campaigns );

	}

	/**
	 * Filter WooCommerce product price.
	 *
	 * @param mixed       $price   Product price.
	 * @param \WC_Product $product Product.
	 *
	 * @return mixed
	 */
	public function filter_price(
		$price,
		$product
	) {

		if ( is_admin() && ! wp_doing_ajax() ) {
			return $price;
		}

		if ( ! $product instanceof \WC_Product ) {
			return $price;
		}

		$regular_price = $product->get_regular_price( 'edit' );

		if ( '' === $regular_price ) {
			$regular_price = $price;
		}

		if ( ! is_numeric( $regular_price ) ) {
			return $price;
		}

		return wc_format_decimal(
			$this->getProductPrice(
				(int) $product->get_id(),
				(float) $regular_price
			)
		);

	}

}
