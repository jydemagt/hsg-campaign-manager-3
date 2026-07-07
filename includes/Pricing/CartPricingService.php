<?php
/**
 * Cart Pricing Service
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Pricing;

defined( 'ABSPATH' ) || exit;

final class CartPricingService {

	/**
	 * Base pricing service.
	 *
	 * @var PricingService
	 */
	private PricingService $pricing;

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
	 * Constructor.
	 *
	 * @param PricingService    $pricing   Pricing service.
	 * @param CampaignLoader    $loader    Campaign loader.
	 * @param CampaignEvaluator $evaluator Campaign evaluator.
	 * @param ConflictResolver  $resolver  Conflict resolver.
	 */
	public function __construct(
		PricingService $pricing,
		CampaignLoader $loader,
		CampaignEvaluator $evaluator,
		ConflictResolver $resolver
	) {

		$this->pricing   = $pricing;
		$this->loader    = $loader;
		$this->evaluator = $evaluator;
		$this->resolver  = $resolver;

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
	 * Apply cart pricing.
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

		$groups = $this->collect_groups( $cart );

		foreach ( $groups as $campaign_id => $group ) {

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
	 * @param \WC_Cart $cart Cart.
	 *
	 * @return array
	 */
	private function collect_groups( \WC_Cart $cart ): array {

		$groups = array();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {

			if ( isset( $cart->cart_contents[ $cart_item_key ] ) ) {
				unset( $cart->cart_contents[ $cart_item_key ]['hsgcm_multi_buy_notice'] );
			}

			$campaign = $this->resolve_multi_buy_campaign( $cart_item );

			$product = $cart_item['data'] ?? null;

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$base_price = $this->get_base_price( $product, $campaign );

			$product->set_price( $base_price );

			if ( ! $campaign ) {
				continue;
			}

			$groups[ $campaign->id ]['campaign'] = $campaign;
			$groups[ $campaign->id ]['items'][] = array(
				'key'       => $cart_item_key,
				'quantity'  => max( 0, (int) ( $cart_item['quantity'] ?? 0 ) ),
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
	 * @param array $cart_item Cart item.
	 *
	 * @return object|null
	 */
	private function resolve_multi_buy_campaign( array $cart_item ): ?object {

		$product = $cart_item['data'] ?? null;

		if ( ! $product instanceof \WC_Product ) {
			return null;
		}

		$applicable = array();

		foreach ( $this->loader->active() as $campaign ) {

			if ( ! $this->evaluator->applies( $campaign, (int) $product->get_id() ) ) {
				continue;
			}

			$applicable[] = $campaign;

		}

		if ( empty( $applicable ) ) {
			return null;
		}

		$resolved = $this->resolver->resolve( $applicable );

		foreach ( $resolved as $campaign ) {
			if ( 'multi_buy' === $campaign->type ) {
				return $campaign;
			}
		}

		return null;

	}

	/**
	 * Get the base price for a cart item.
	 *
	 * @param \WC_Product $product   Product.
	 * @param object|null $campaign  Campaign.
	 *
	 * @return float
	 */
	private function get_base_price(
		\WC_Product $product,
		?object $campaign
	): float {

		$regular_price = $product->get_regular_price( 'edit' );

		if ( '' === $regular_price ) {
			$regular_price = $product->get_price( 'edit' );
		}

		if ( ! is_numeric( $regular_price ) ) {
			return 0.0;
		}

		if ( null === $campaign || ! empty( $campaign->stackable ) ) {
			return $this->pricing->getProductPrice(
				(int) $product->get_id(),
				(float) $regular_price
			);
		}

		return (float) $regular_price;

	}

	/**
	 * Apply a multi-buy campaign to its cart group.
	 *
	 * @param \WC_Cart $cart Cart.
	 * @param object   $campaign Campaign.
	 * @param array    $items Group items.
	 *
	 * @return void
	 */
	private function apply_group(
		\WC_Cart $cart,
		object $campaign,
		array $items
	): void {

		$bundle_size = max( 0, (int) ( $campaign->quantity ?? 0 ) );
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
		$assigned = array();
		$bundle_units = $bundle_count * $bundle_size;

		for ( $index = 0; $index < count( $units ); $index++ ) {

			$unit = $units[ $index ];

			if ( $index < $bundle_units ) {
				$share_index = $index % $bundle_size;
				$amount = $bundle_shares[ $share_index ];
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

			$quantity = max( 1, (int) ( $cart->cart_contents[ $cart_item_key ]['quantity'] ?? 1 ) );
			$product = $cart->cart_contents[ $cart_item_key ]['data'] ?? null;

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$product->set_price(
				$line_total / $quantity
			);

			$this->store_notice(
				$cart,
				$cart_item_key,
				$campaign,
				$bundle_count,
				$bundle_size,
				$bundle_price
			);

		}

	}

	/**
	 * Store notice metadata on a cart item.
	 *
	 * @param \WC_Cart $cart Cart.
	 * @param string   $cart_item_key Cart item key.
	 * @param object   $campaign Campaign.
	 * @param int      $bundle_count Bundle count.
	 * @param int      $bundle_size Bundle size.
	 * @param float    $bundle_price Bundle price.
	 *
	 * @return void
	 */
	private function store_notice(
		\WC_Cart $cart,
		string $cart_item_key,
		object $campaign,
		int $bundle_count,
		int $bundle_size,
		float $bundle_price
	): void {

		if (
			$bundle_count <= 0 ||
			$bundle_size <= 0 ||
			$bundle_price <= 0 ||
			! isset( $cart->cart_contents[ $cart_item_key ] )
		) {
			return;
		}

		$cart->cart_contents[ $cart_item_key ]['hsgcm_multi_buy_notice'] = array(
			'campaign_id'   => (int) ( $campaign->id ?? 0 ),
			'campaign_name' => sanitize_text_field( $campaign->name ?? '' ),
			'bundle_count'  => $bundle_count,
			'bundle_size'   => $bundle_size,
			'bundle_price'  => $bundle_price,
		);

	}

	/**
	 * Append item data for cart and checkout notices.
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

		$notice = $cart_item['hsgcm_multi_buy_notice'] ?? array();

		if ( ! is_array( $notice ) ) {
			return $item_data;
		}

		$text = $this->build_notice_text( $notice );

		if ( '' === $text ) {
			return $item_data;
		}

		$item_data[] = array(
			'name'    => esc_html__( 'Campaign', 'hsg-campaign-manager' ),
			'display' => esc_html( $text ),
		);

		return $item_data;

	}

	/**
	 * Build the notice text.
	 *
	 * @param array $notice Notice metadata.
	 *
	 * @return string
	 */
	private function build_notice_text( array $notice ): string {

		$bundle_count = max( 0, (int) ( $notice['bundle_count'] ?? 0 ) );
		$bundle_size  = max( 0, (int) ( $notice['bundle_size'] ?? 0 ) );
		$bundle_price = (float) ( $notice['bundle_price'] ?? 0 );

		if ( $bundle_count <= 0 || $bundle_size <= 0 || $bundle_price <= 0 ) {
			return '';
		}

		$price_text = wp_strip_all_tags( wc_price( $bundle_price ) );

		if ( $bundle_count > 1 ) {
			return sprintf(
				/* translators: 1: number of bundles, 2: bundle quantity, 3: formatted bundle price. */
				__( '%1$d x %2$d for %3$s applied', 'hsg-campaign-manager' ),
				$bundle_count,
				$bundle_size,
				$price_text
			);
		}

		return sprintf(
			/* translators: 1: bundle quantity, 2: formatted bundle price. */
			__( '%1$d for %2$s applied', 'hsg-campaign-manager' ),
			$bundle_size,
			$price_text
		);

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

		$decimals = wc_get_price_decimals();
		$multiplier = 10 ** $decimals;
		$total_minor = (int) round( $amount * $multiplier );
		$base_minor = intdiv( $total_minor, $pieces );
		$remainder = $total_minor % $pieces;
		$shares = array();

		for ( $i = 0; $i < $pieces; $i++ ) {
			$share_minor = $base_minor + ( $i < $remainder ? 1 : 0 );
			$shares[] = $share_minor / $multiplier;
		}

		return $shares;

	}

}
