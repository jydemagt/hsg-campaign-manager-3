<?php
/**
 * Coupon Service
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Pricing;

use HSGCM\Campaign\CampaignRepository;

defined( 'ABSPATH' ) || exit;

final class CouponService {

	/**
	 * Marker amount used to derive the quantity passed through WooCommerce's
	 * fixed-product coupon calculation.
	 *
	 * @var float
	 */
	private const FIXED_PRICE_QUANTITY_MARKER = 1.0;

	/**
	 * Campaign repository.
	 *
	 * @var CampaignRepository
	 */
	private CampaignRepository $repository;

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
	 * Stored validation messages.
	 *
	 * @var array
	 */
	private array $errors = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new CampaignRepository();
		$this->loader     = new CampaignLoader();
		$this->evaluator  = new CampaignEvaluator();
		$this->resolver   = new ConflictResolver();

		add_filter(
			'woocommerce_get_shop_coupon_data',
			array( $this, 'filter_shop_coupon_data' ),
			10,
			3
		);

		add_filter(
			'woocommerce_coupon_is_valid',
			array( $this, 'validate_coupon' ),
			20,
			3
		);

		add_filter(
			'woocommerce_coupon_is_valid_for_product',
			array( $this, 'validate_coupon_for_product' ),
			20,
			4
		);

		add_filter(
			'woocommerce_coupon_error',
			array( $this, 'filter_coupon_error' ),
			20,
			3
		);

		add_filter(
			'woocommerce_coupon_get_discount_amount',
			array( $this, 'filter_fixed_price_discount_amount' ),
			20,
			5
		);
	}

	/**
	 * Return applied coupon codes from the current cart.
	 *
	 * @return array
	 */
	public function getAppliedCouponCodes(): array {

		if ( ! function_exists( 'WC' ) ) {
			return array();
		}

		$woocommerce = WC();

		if (
			! $woocommerce ||
			! isset( $woocommerce->cart ) ||
			! $woocommerce->cart
		) {
			return array();
		}

		return $this->normalizeCouponCodes(
			(array) $woocommerce->cart->get_applied_coupons()
		);

	}

	/**
	 * Resolve active campaigns for a product and optional coupon codes.
	 *
	 * @param int   $product_id   Product ID.
	 * @param array $coupon_codes Coupon codes.
	 *
	 * @return array
	 */
	public function resolveCampaignsForProduct(
		int $product_id,
		array $coupon_codes = array()
	): array {
		if ( $product_id <= 0 ) {
			return array();
		}

		$codes      = $this->normalizeCouponCodes( $coupon_codes );
		$applicable = array();

		foreach ( $this->loader->active() as $campaign ) {
			if ( ! $this->evaluator->applies( $campaign, $product_id ) ) {
				continue;
			}

			if ( ! empty( $campaign->coupon ) ) {
				if ( ! $this->is_supported_coupon_campaign( $campaign ) ) {
					continue;
				}

				$code = $this->normalizeCouponCode(
					$campaign->coupon ?? ''
				);

				if ( '' === $code || ! in_array( $code, $codes, true ) ) {
					continue;
				}
			}

			$applicable[] = $campaign;
		}

		return $this->resolver->resolve( $applicable );
	}

	/**
	 * Filter virtual coupon data.
	 *
	 * @param mixed  $coupon_data Coupon data.
	 * @param string $code Coupon code.
	 * @param mixed  $coupon Coupon object.
	 *
	 * @return mixed
	 */
	public function filter_shop_coupon_data(
		$coupon_data,
		$code,
		$coupon
	) {
		$code = $this->normalizeCouponCode( (string) $code );

		if ( '' === $code ) {
			return $coupon_data;
		}

		/*
		 * A real WooCommerce coupon takes precedence over a virtual campaign
		 * coupon with the same code.
		 */
		if (
			function_exists( 'wc_get_coupon_id_by_code' ) &&
			wc_get_coupon_id_by_code( $code ) > 0
		) {
			return $coupon_data;
		}

		$campaign = $this->find_active_campaign_by_code( $code );

		if ( ! $campaign ) {
			$campaign = $this->find_campaign_by_code( $code );
		}

		if ( ! $campaign ) {
			return $coupon_data;
		}

		return $this->build_coupon_data( $campaign );
	}

	/**
	 * Validate coupon schedule and activation.
	 *
	 * @param mixed      $valid Validation state.
	 * @param \WC_Coupon $coupon Coupon object.
	 * @param mixed      $discounts Discounts object.
	 *
	 * @return mixed
	 */
	public function validate_coupon(
		$valid,
		$coupon,
		$discounts
	) {

		if ( ! $valid || ! $coupon instanceof \WC_Coupon ) {
			return $valid;
		}

		$code = $this->normalizeCouponCode( (string) $coupon->get_code() );

		if ( '' === $code ) {
			return $valid;
		}

		$active_campaign = $this->find_active_campaign_by_code( $code );

		if ( $active_campaign ) {
			unset( $this->errors[ $code ] );
			return $valid;
		}

		$campaign = $this->find_campaign_by_code( $code );

		if ( $campaign ) {
			$this->errors[ $code ] = __( 'This campaign coupon is not active right now.', 'hsg-campaign-manager' );
			return false;
		}

		return $valid;

	}

	/**
	 * Validate coupon for a product.
	 *
	 * @param mixed      $valid Validation state.
	 * @param \WC_Product $product Product.
	 * @param \WC_Coupon $coupon Coupon.
	 * @param array      $values Cart item values.
	 *
	 * @return mixed
	 */
	public function validate_coupon_for_product(
		$valid,
		$product,
		$coupon,
		$values
	) {
		if (
			! $valid ||
			! $coupon instanceof \WC_Coupon ||
			! $product instanceof \WC_Product
		) {
			return $valid;
		}

		$code = $this->normalizeCouponCode(
			(string) $coupon->get_code()
		);

		if ( '' === $code ) {
			return $valid;
		}

		/*
		 * Leave ordinary WooCommerce coupons untouched. An active campaign
		 * with the same code identifies this as a virtual campaign coupon.
		 */
		if ( ! $this->find_active_campaign_by_code( $code ) ) {
			return $valid;
		}

		return null !== $this->find_active_campaign_for_product_by_code(
			(int) $product->get_id(),
			$code
		);
	}

	/**
	 * Filter coupon error messages.
	 *
	 * @param string     $message Error message.
	 * @param int        $error_code Error code.
	 * @param \WC_Coupon $coupon Coupon object.
	 *
	 * @return string
	 */
	public function filter_coupon_error(
		$message,
		$error_code,
		$coupon
	) {

		if (
			(int) $error_code !== \WC_Coupon::E_WC_COUPON_INVALID_FILTERED ||
			! $coupon instanceof \WC_Coupon
		) {
			return $message;
		}

		$code = $this->normalizeCouponCode( (string) $coupon->get_code() );

		if ( '' === $code || ! isset( $this->errors[ $code ] ) ) {
			return $message;
		}

		$custom_message = $this->errors[ $code ];

		unset( $this->errors[ $code ] );

		return $custom_message;

	}

	/**
	 * Calculate the discount required to reach a fixed unit price.
	 *
	 * Fixed-price virtual coupons use an amount of 1 as a quantity
	 * marker. WooCommerce multiplies that marker by the applicable
	 * quantity before this filter runs.
	 *
	 * @param mixed $discount           Existing calculated discount.
	 * @param mixed $discounting_amount Amount available for discount.
	 * @param mixed $cart_item          Product being discounted.
	 * @param bool  $single             Whether one unit is calculated.
	 * @param mixed $coupon             Coupon object.
	 *
	 * @return mixed
	 */
	public function filter_fixed_price_discount_amount(
		$discount,
		$discounting_amount,
		$cart_item,
		$single,
		$coupon
	) {
		if (
			! $coupon instanceof \WC_Coupon ||
			! is_numeric( $discounting_amount )
		) {
			return $discount;
		}

		$code = $this->normalizeCouponCode(
			(string) $coupon->get_code()
		);

		if ( '' === $code ) {
			return $discount;
		}

		$active_campaign = $this->find_active_campaign_by_code( $code );

		if (
			! $active_campaign ||
			'fixed_price' !== $active_campaign->type
		) {
			return $discount;
		}

		$product = $this->get_discount_product( $cart_item );

		if ( ! $product ) {
			return 0.0;
		}

		$campaign = $this->find_active_campaign_for_product_by_code(
			(int) $product->get_id(),
			$code
		);

		if ( ! $campaign || 'fixed_price' !== $campaign->type ) {
			return 0.0;
		}

		$quantity = $single
			? 1.0
			: max(
				0.0,
				(float) $discount / self::FIXED_PRICE_QUANTITY_MARKER
			);

		if ( $quantity <= 0 ) {
			return 0.0;
		}

		$available_total = max(
			0.0,
			(float) $discounting_amount
		);
		$target_total = max(
			0.0,
			(float) ( $campaign->value ?? 0 )
		) * $quantity;

		/*
		 * A fixed-price coupon is a discount only. It must never raise the
		 * price when the current WooCommerce price is already lower.
		 */
		if ( $target_total >= $available_total ) {
			return 0.0;
		}

		return max(
			0.0,
			(float) wc_format_decimal(
				$available_total - $target_total
			)
		);
	}

	/**
	 * Find an active campaign by coupon code.
	 *
	 * @param string $code Coupon code.
	 *
	 * @return object|null
	 */
	private function find_active_campaign_by_code( string $code ): ?object {

		$code = $this->normalizeCouponCode( $code );

		if ( '' === $code ) {
			return null;
		}

		$campaigns = array();

		foreach ( $this->loader->active() as $campaign ) {

			if ( ! $this->is_supported_coupon_campaign( $campaign ) ) {
				continue;
			}

			if ( $this->normalizeCouponCode( $campaign->coupon ?? '' ) !== $code ) {
				continue;
			}

			$campaigns[] = $campaign;

		}

		if ( empty( $campaigns ) ) {
			return null;
		}

		$resolved = $this->resolver->resolve( $campaigns );

		if ( empty( $resolved ) ) {
			return null;
		}

		return reset( $resolved ) ?: null;

	}

	/**
	 * Find the resolved active campaign for a product and coupon code.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $code       Coupon code.
	 *
	 * @return object|null
	 */
	private function find_active_campaign_for_product_by_code(
		int $product_id,
		string $code
	): ?object {
		$code = $this->normalizeCouponCode( $code );

		if ( $product_id <= 0 || '' === $code ) {
			return null;
		}

		foreach (
			$this->resolveCampaignsForProduct(
				$product_id,
				array( $code )
			) as $campaign
		) {
			if (
				! $this->is_supported_coupon_campaign( $campaign ) ||
				$this->normalizeCouponCode(
					$campaign->coupon ?? ''
				) !== $code
			) {
				continue;
			}

			return $campaign;
		}

		return null;
	}

	/**
	 * Extract a product from WooCommerce's coupon filter argument.
	 *
	 * WooCommerce can pass either the product object directly or a cart-item
	 * array containing the product under the data key.
	 *
	 * @param mixed $cart_item Coupon filter cart item.
	 *
	 * @return \WC_Product|null
	 */
	private function get_discount_product( $cart_item ): ?\WC_Product {
		if ( $cart_item instanceof \WC_Product ) {
			return $cart_item;
		}

		if (
			is_array( $cart_item ) &&
			isset( $cart_item['data'] ) &&
			$cart_item['data'] instanceof \WC_Product
		) {
			return $cart_item['data'];
		}

		return null;
	}


	/**
	 * Find a published campaign by coupon code, regardless of schedule.
	 *
	 * @param string $code Coupon code.
	 *
	 * @return object|null
	 */
	private function find_campaign_by_code( string $code ): ?object {

		$code = $this->normalizeCouponCode( $code );

		if ( '' === $code ) {
			return null;
		}

		$campaigns = array();

		foreach ( $this->repository->published() as $post ) {

			$campaign = $this->repository->find_raw( (int) $post->ID );

			if ( ! is_array( $campaign ) ) {
				continue;
			}

			$normalized = $this->normalizeCampaign( $campaign );

			if ( ! $this->is_supported_coupon_campaign( $normalized ) ) {
				continue;
			}

			if ( $this->normalizeCouponCode( $normalized->coupon ?? '' ) !== $code ) {
				continue;
			}

			$campaigns[] = $normalized;

		}

		if ( empty( $campaigns ) ) {
			return null;
		}

		$resolved = $this->resolver->resolve( $campaigns );

		if ( empty( $resolved ) ) {
			return null;
		}

		return reset( $resolved ) ?: null;

	}

	/**
	 * Determine whether a campaign can be used as a coupon.
	 *
	 * @param object $campaign Campaign.
	 *
	 * @return bool
	 */
	private function is_supported_coupon_campaign( object $campaign ): bool {
		return (
			! empty( $campaign->coupon ) &&
			in_array(
				$campaign->type,
				array(
					'fixed_price',
					'percentage_discount',
					'fixed_discount',
				),
				true
			)
		);
	}

	/**
	 * Build coupon data for WooCommerce.
	 *
	 * @param object $campaign Campaign.
	 *
	 * @return array
	 */
	private function build_coupon_data( object $campaign ): array {
		$discount_type = 'percentage_discount' === $campaign->type
			? 'percent'
			: 'fixed_product';

		$amount = 'fixed_price' === $campaign->type
			? self::FIXED_PRICE_QUANTITY_MARKER
			: ( $campaign->value ?? 0 );

		return array(
			'discount_type' => $discount_type,
			'amount' => wc_format_decimal( $amount ),
			'individual_use' => empty( $campaign->stackable ),
			'product_ids' => array_values(
				array_filter(
					array_map(
						'absint',
						(array) ( $campaign->products ?? array() )
					)
				)
			),
			'excluded_product_ids' => array(),
			'usage_limit' => 0,
			'usage_limit_per_user' => 0,
			'limit_usage_to_x_items' => null,
			'free_shipping' => false,
			'product_categories' => array(),
			'excluded_product_categories' => array(),
			'exclude_sale_items' => false,
			'minimum_amount' => '',
			'maximum_amount' => '',
			'email_restrictions' => array(),
			'virtual' => true,
			'description' => sanitize_text_field(
				$campaign->name ?? ''
			),
			'status' => 'publish',
		);
	}

	/**
	 * Normalize a raw campaign array.
	 *
	 * @param array $campaign Campaign data.
	 *
	 * @return object
	 */
	private function normalizeCampaign( array $campaign ): object {

		return (object) array(
			'id'           => absint( $campaign['id'] ?? 0 ),
			'name'         => sanitize_text_field( $campaign['name'] ?? '' ),
			'priority'     => absint( $campaign['priority'] ?? 0 ),
			'quantity'     => absint( $campaign['quantity'] ?? 2 ),
			'bundle_price' => (float) wc_format_decimal( $campaign['bundle_price'] ?? 0 ),
			'products'     => array_values(
				array_filter(
					array_map( 'absint', (array) ( $campaign['products'] ?? array() ) )
				)
			),
			'type'         => sanitize_key( $campaign['type'] ?? 'fixed_price' ),
			'value'        => (float) wc_format_decimal( $campaign['value'] ?? 0 ),
			'coupon'       => sanitize_text_field( $campaign['coupon'] ?? '' ),
			'stackable'    => ! empty( $campaign['stackable'] ),
			'start_date'   => sanitize_text_field( $campaign['start_date'] ?? '' ),
			'end_date'     => sanitize_text_field( $campaign['end_date'] ?? '' ),
		);

	}

	/**
	 * Normalize a coupon code.
	 *
	 * @param string $code Coupon code.
	 *
	 * @return string
	 */
	private function normalizeCouponCode( string $code ): string {

		$code = trim( $code );

		if ( '' === $code ) {
			return '';
		}

		if ( function_exists( 'wc_format_coupon_code' ) ) {
			return wc_format_coupon_code( $code );
		}

		return strtolower( $code );

	}

	/**
	 * Normalize coupon code values.
	 *
	 * @param array $codes Coupon codes.
	 *
	 * @return array
	 */
	private function normalizeCouponCodes( array $codes ): array {

		$normalized = array();

		foreach ( $codes as $code ) {

			if ( ! is_scalar( $code ) ) {
				continue;
			}

			$code = $this->normalizeCouponCode( (string) $code );

			if ( '' === $code ) {
				continue;
			}

			$normalized[ $code ] = $code;

		}

		return array_values( $normalized );

	}

}
