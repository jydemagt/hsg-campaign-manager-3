<?php
/**
 * Cart Pricing Service.
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Pricing;

defined( 'ABSPATH' ) || exit;

final class CartPricingService {

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
	 * Original active prices for the current request.
	 *
	 * Prices are stored by cart item key so repeated executions of
	 * woocommerce_before_calculate_totals do not apply campaign pricing
	 * to a price that this service has already changed.
	 *
	 * @var array<string,float>
	 */
	private array $base_prices = array();

	/**
	 * Constructor.
	 *
	 * @param CouponService        $coupon_service Coupon service.
	 * @param PriceCalculator      $calculator     Price calculator.
	 * @param CampaignLabelService $label_service  Campaign label service.
	 */
	public function __construct(
		CouponService $coupon_service,
		PriceCalculator $calculator,
		CampaignLabelService $label_service
	) {
		$this->coupon_service = $coupon_service;
		$this->calculator     = $calculator;
		$this->label_service  = $label_service;

		add_action(
			'woocommerce_before_calculate_totals',
			array( $this, 'apply' ),
			20,
			1
		);

		add_filter(
			'woocommerce_get_item_data',
			array( $this, 'filter_item_data' ),
			20,
			2
		);
	}

	/**
	 * Apply campaign pricing to cart items.
	 *
	 * @param \WC_Cart $cart Cart.
	 *
	 * @return void
	 */
	public function apply( \WC_Cart $cart ): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		if ( empty( $cart->cart_contents ) ) {
			return;
		}

		$coupon_codes = $this->coupon_service->getAppliedCouponCodes();
		$groups       = $this->collect_groups( $cart, $coupon_codes );

		foreach ( $groups as $group ) {
			$this->apply_group(
				$cart,
				$group['campaign'],
				$group['items']
			);
		}
	}

	/**
	 * Collect multi-buy groups from the cart.
	 *
	 * @param \WC_Cart $cart         Cart.
	 * @param array    $coupon_codes Applied coupon codes.
	 *
	 * @return array
	 */
	private function collect_groups(
		\WC_Cart $cart,
		array $coupon_codes
	): array {
		$groups = array();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'] ?? null;

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$resolved_campaigns = $this->coupon_service->resolveCampaignsForProduct(
				(int) $product->get_id(),
				$coupon_codes
			);

			$base_price = $this->get_base_price(
				(string) $cart_item_key,
				$product,
				$resolved_campaigns
			);

			$product->set_price( $base_price );

			$campaign = $this->find_multi_buy_campaign( $resolved_campaigns );

			if ( ! $campaign ) {
				continue;
			}

			$groups[ $campaign->id ]['campaign'] = $campaign;
			$groups[ $campaign->id ]['items'][]  = array(
				'key'        => $cart_item_key,
				'quantity'   => max( 0, (int) ( $cart_item['quantity'] ?? 0 ) ),
				'product_id' => (int) $product->get_id(),
				'base_price' => $base_price,
			);
		}

		return array_filter(
			$groups,
			static function ( array $group ): bool {
				return ! empty( $group['items'] ) && isset( $group['campaign'] );
			}
		);
	}

	/**
	 * Resolve the winning multi-buy campaign for a cart item.
	 *
	 * @param array $campaigns Campaigns.
	 *
	 * @return object|null
	 */
	private function find_multi_buy_campaign( array $campaigns ): ?object {
		foreach ( $campaigns as $campaign ) {
			if ( 'multi_buy' === $campaign->type ) {
				return $campaign;
			}
		}

		return null;
	}

	/**
	 * Get the base price for a cart item.
	 *
	 * The original active WooCommerce price is cached for the current
	 * request. This preserves native sale prices and prevents campaign
	 * pricing from compounding when cart totals are recalculated.
	 *
	 * @param string      $cart_item_key Cart item key.
	 * @param \WC_Product $product       Product.
	 * @param array       $campaigns     Campaigns.
	 *
	 * @return float
	 */
	private function get_base_price(
		string $cart_item_key,
		\WC_Product $product,
		array $campaigns
	): float {
		if ( ! array_key_exists( $cart_item_key, $this->base_prices ) ) {
			$active_price = $product->get_price( 'edit' );

			if ( ! is_numeric( $active_price ) ) {
				return 0.0;
			}

			$this->base_prices[ $cart_item_key ] = (float) $active_price;
		}

		$base_price = $this->base_prices[ $cart_item_key ];

		$pricing_campaigns = array_filter(
			$campaigns,
			static function ( object $campaign ): bool {
				return (
					'multi_buy' !== $campaign->type &&
					empty( $campaign->coupon )
				);
			}
		);

		if ( empty( $pricing_campaigns ) ) {
			return $base_price;
		}

		return $this->calculator->calculate(
			$base_price,
			$pricing_campaigns
		);
	}

	/**
	 * Apply a multi-buy campaign to its cart group.
	 *
	 * @param \WC_Cart $cart     Cart.
	 * @param object   $campaign Campaign.
	 * @param array    $items    Group items.
	 *
	 * @return void
	 */
	private function apply_group(
		\WC_Cart $cart,
		object $campaign,
		array $items
	): void {
		$bundle_size  = max( 0, (int) ( $campaign->quantity ?? 0 ) );
		$bundle_price = (float) ( $campaign->bundle_price ?? 0 );

		if ( $bundle_size < 2 || $bundle_price <= 0 || empty( $items ) ) {
			return;
		}

		$units = array();

		foreach ( $items as $item ) {
			$quantity = max( 0, (int) ( $item['quantity'] ?? 0 ) );

			if ( $quantity <= 0 ) {
				continue;
			}

			for ( $i = 0; $i < $quantity; $i++ ) {
				$units[] = array(
					'key'  => $item['key'],
					'base' => (float) ( $item['base_price'] ?? 0 ),
				);
			}
		}

		$bundle_count = intdiv( count( $units ), $bundle_size );

		if ( $bundle_count <= 0 ) {
			return;
		}

		$bundle_shares = $this->split_amount( $bundle_price, $bundle_size );
		$assigned      = array();
		$bundle_units  = $bundle_count * $bundle_size;

		for ( $index = 0; $index < count( $units ); $index++ ) {
			$unit = $units[ $index ];

			if ( $index < $bundle_units ) {
				$share_index = $index % $bundle_size;
				$amount      = $bundle_shares[ $share_index ];
			} else {
				$amount = $unit['base'];
			}

			if ( ! isset( $assigned[ $unit['key'] ] ) ) {
				$assigned[ $unit['key'] ] = 0.0;
			}

			$assigned[ $unit['key'] ] += $amount;
		}

		foreach ( $assigned as $cart_item_key => $line_total ) {
			if ( ! isset( $cart->cart_contents[ $cart_item_key ] ) ) {
				continue;
			}

			$quantity = max(
				1,
				(int) ( $cart->cart_contents[ $cart_item_key ]['quantity'] ?? 1 )
			);

			$product = $cart->cart_contents[ $cart_item_key ]['data'] ?? null;

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$product->set_price( $line_total / $quantity );
		}
	}

	/**
	 * Append campaign label item data for cart and checkout.
	 *
	 * @param array $item_data Item data.
	 * @param array $cart_item Cart item.
	 *
	 * @return array
	 */
	public function filter_item_data(
		array $item_data,
		array $cart_item
	): array {
		$product = $cart_item['data'] ?? null;

		if ( ! $product instanceof \WC_Product ) {
			return $item_data;
		}

		$campaign_item_data = $this->label_service->item_data_for_product(
			(int) $product->get_id(),
			$this->coupon_service->getAppliedCouponCodes()
		);

		if ( empty( $campaign_item_data ) ) {
			return $item_data;
		}

		foreach ( $campaign_item_data as $campaign_item ) {
			$item_data[] = array(
				'name'    => esc_html( $campaign_item['name'] ),
				'display' => esc_html( $campaign_item['display'] ),
			);
		}

		return $item_data;
	}

	/**
	 * Split a bundle amount into equal-priced pieces with rounding.
	 *
	 * @param float $amount Amount.
	 * @param int   $pieces Pieces.
	 *
	 * @return array
	 */
	private function split_amount(
		float $amount,
		int $pieces
	): array {
		if ( $pieces <= 0 ) {
			return array();
		}

		$decimals   = wc_get_price_decimals();
		$multiplier = 10 ** $decimals;
		$total_minor = (int) round( $amount * $multiplier );
		$base_minor  = intdiv( $total_minor, $pieces );
		$remainder   = $total_minor % $pieces;
		$shares      = array();

		for ( $i = 0; $i < $pieces; $i++ ) {
			$share_minor = $base_minor + ( $i < $remainder ? 1 : 0 );
			$shares[]    = $share_minor / $multiplier;
		}

		return $shares;
	}
}