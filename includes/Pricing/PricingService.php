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
	 * Campaign label service.
	 *
	 * @var CampaignLabelService
	 */
	private CampaignLabelService $label_service;

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
		$this->label_service  = new CampaignLabelService(
			$this->coupon_service
		);

		$this->cart_pricing = new CartPricingService(
			$this->coupon_service,
			$this->calculator,
			$this->label_service
		);

		add_filter(
			'woocommerce_product_get_price',
			array( $this, 'filter_price' ),
			20,
			2
		);

		add_filter(
			'woocommerce_product_variation_get_price',
			array( $this, 'filter_price' ),
			20,
			2
		);

		add_filter(
			'woocommerce_variation_prices_price',
			array( $this, 'filter_price' ),
			20,
			2
		);

		add_action(
			'woocommerce_single_product_summary',
			array( $this, 'render_product_labels' ),
			11
		);

		add_action(
			'woocommerce_after_shop_loop_item_title',
			array( $this, 'render_product_labels' ),
			11
		);
	}

	/**
	 * Return campaign-adjusted product price.
	 *
	 * @param int   $product_id   Product ID.
	 * @param float $regular_price Base product price.
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
			$this->coupon_service->resolveCampaignsForProduct(
				$product_id
			),
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

		return $this->calculator->calculate(
			$regular_price,
			$campaigns
		);
	}

	/**
	 * Filter WooCommerce product price.
	 *
	 * Uses the incoming WooCommerce price as the campaign base price.
	 * This preserves native sale prices and prices changed by other
	 * WooCommerce-compatible extensions.
	 *
	 * @param mixed       $price   Product price.
	 * @param \WC_Product $product Product.
	 *
	 * @return mixed
	 */
	public function filter_price( $price, $product ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $price;
		}

		if ( ! $product instanceof \WC_Product ) {
			return $price;
		}

		if ( ! is_numeric( $price ) ) {
			return $price;
		}

		return wc_format_decimal(
			$this->getProductPrice(
				(int) $product->get_id(),
				(float) $price
			)
		);
	}

	/**
	 * Render campaign labels for the current product.
	 *
	 * @return void
	 */
	public function render_product_labels(): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$labels = $this->label_service->labels_for_product(
			(int) $product->get_id()
		);

		if ( empty( $labels ) ) {
			return;
		}

		echo '<div class="hsgcm-campaign-labels">';

		foreach ( $labels as $label ) {
			echo '<p class="hsgcm-campaign-label">'
				. esc_html( $label )
				. '</p>';
		}

		echo '</div>';
	}
}