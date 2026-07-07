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
	 * Campaign loader.
	 *
	 * @var CampaignLoader
	 */
	private CampaignLoader $loader;

	/**
	 * Campaign evaluator.
	 *
	 * @var CampaignEvaluator
	 */
	private CampaignEvaluator $evaluator;

	/**
	 * Conflict resolver.
	 *
	 * @var ConflictResolver
	 */
	private ConflictResolver $resolver;

	/**
	 * Price calculator.
	 *
	 * @var PriceCalculator
	 */
	private PriceCalculator $calculator;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->loader     = new CampaignLoader();
		$this->evaluator  = new CampaignEvaluator();
		$this->resolver   = new ConflictResolver();
		$this->calculator = new PriceCalculator();

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

		$applicable = array();

		foreach ( $this->loader->active() as $campaign ) {

			if ( $this->evaluator->applies( $campaign, $product_id ) ) {
				$applicable[] = $campaign;
			}

		}

		$campaigns = $this->resolver->resolve( $applicable );

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
