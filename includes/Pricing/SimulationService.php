<?php
/**
 * Campaign Simulation Service
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Pricing;

defined( 'ABSPATH' ) || exit;

final class SimulationService {

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

	}

	/**
	 * Simulate campaign evaluation.
	 *
	 * @param array $input Simulation input.
	 *
	 * @return array
	 */
	public function simulate( array $input ): array {

		$input = $this->sanitize( $input );

		$validation = $this->validate( $input );

		if ( ! $validation['success'] ) {
			return $validation;
		}

		$product = wc_get_product( $input['product_id'] );

		if ( ! $product ) {
			return array(
				'success' => false,
				'message' => __( 'Product not found.', 'hsg-campaign-manager' ),
			);
		}

		$regular_price = $this->get_regular_price( $product );

		if ( null === $regular_price ) {
			return array(
				'success' => false,
				'message' => __( 'Product does not have a numeric regular price.', 'hsg-campaign-manager' ),
			);
		}

		$campaigns_for_date = $this->loader->campaigns_for_date( $input['date'] );
		$coupon_codes       = $this->normalize_coupon_codes(
			'' === $input['coupon'] ? array() : array( $input['coupon'] )
		);
		$applicable         = array();
		$rejected           = array();

		foreach ( $campaigns_for_date['rejected'] as $rejection ) {
			$rejected[] = $this->format_rejection(
				$rejection['campaign'],
				$rejection['reason']
			);
		}

		foreach ( $campaigns_for_date['active'] as $campaign ) {

			if ( ! $this->evaluator->applies( $campaign, $input['product_id'] ) ) {
				$rejected[] = $this->format_rejection(
					$campaign,
					__( 'Campaign does not apply to the selected product.', 'hsg-campaign-manager' )
				);
				continue;
			}

			if ( $this->is_supported_coupon_campaign( $campaign ) ) {

				$campaign_coupon = $this->normalize_coupon_code( (string) ( $campaign->coupon ?? '' ) );

				if ( '' === $campaign_coupon || ! in_array( $campaign_coupon, $coupon_codes, true ) ) {
					$rejected[] = $this->format_rejection(
						$campaign,
						__( 'Campaign requires its coupon code to be applied.', 'hsg-campaign-manager' )
					);
					continue;
				}

			}

			$applicable[] = $campaign;

		}

		$resolved        = $this->resolver->resolve( $applicable );
		$winning        = empty( $resolved ) ? null : reset( $resolved );
		$regular_total  = $regular_price * $input['quantity'];
		$standard       = $this->standard_pricing_campaigns( $resolved );
		$unit_price     = empty( $standard )
			? $regular_price
			: $this->calculator->calculate( $regular_price, $standard );
		$final_total    = $unit_price * $input['quantity'];
		$multi_buy      = $this->find_multi_buy_campaign( $resolved );

		if ( $multi_buy ) {
			$final_total = $this->calculator->calculate_multi_buy_total(
				$unit_price,
				$input['quantity'],
				$multi_buy
			);
		}

		$discount_amount = max( 0.0, $regular_total - $final_total );

		return array(
			'success' => true,
			'data'    => array(
				'product'              => $product->get_formatted_name(),
				'quantity'             => $input['quantity'],
				'customer_role'        => $input['customer_role'],
				'coupon'               => $input['coupon'],
				'date'                 => $input['date'],
				'regular_price'        => $this->format_price( $regular_total ),
				'applicable_campaigns' => array_map( array( $this, 'format_campaign' ), $applicable ),
				'rejected_campaigns'   => $rejected,
				'winning_campaign'     => $winning ? $this->format_campaign( $winning ) : null,
				'final_price'          => $this->format_price( $final_total ),
				'discount_amount'      => $this->format_price( $discount_amount ),
				'explanation'          => $this->build_explanation( $input, $applicable, $resolved, $multi_buy ),
			),
		);

	}

	/**
	 * Sanitize simulation input.
	 *
	 * @param array $input Input.
	 *
	 * @return array
	 */
	private function sanitize( array $input ): array {

		return array(
			'product_id'    => absint( $this->scalar_value( $input, 'product_id' ) ),
			'quantity'      => max( 1, absint( $this->scalar_value( $input, 'quantity', '1' ) ) ),
			'customer_role' => sanitize_key( $this->scalar_value( $input, 'customer_role' ) ),
			'coupon'        => sanitize_text_field( $this->scalar_value( $input, 'coupon' ) ),
			'date'          => sanitize_text_field( $this->scalar_value( $input, 'date', current_time( 'Y-m-d' ) ) ),
		);

	}

	/**
	 * Validate simulation input.
	 *
	 * @param array $input Input.
	 *
	 * @return array
	 */
	private function validate( array $input ): array {

		if ( $input['product_id'] <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'Product is required.', 'hsg-campaign-manager' ),
			);
		}

		if ( $input['quantity'] <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'Quantity must be greater than 0.', 'hsg-campaign-manager' ),
			);
		}

		if ( ! $this->is_valid_date( $input['date'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Simulation date is invalid.', 'hsg-campaign-manager' ),
			);
		}

		return array(
			'success' => true,
		);

	}

	/**
	 * Return product regular price.
	 *
	 * @param \WC_Product $product Product.
	 *
	 * @return float|null
	 */
	private function get_regular_price( \WC_Product $product ): ?float {

		$regular_price = $product->get_regular_price( 'edit' );

		if ( '' === $regular_price ) {
			$regular_price = $product->get_price( 'edit' );
		}

		if ( ! is_numeric( $regular_price ) ) {
			return null;
		}

		return (float) $regular_price;

	}

	/**
	 * Return standard pricing campaigns.
	 *
	 * @param array $campaigns Campaigns.
	 *
	 * @return array
	 */
	private function standard_pricing_campaigns( array $campaigns ): array {

		return array_values(
			array_filter(
				$campaigns,
				static function ( object $campaign ): bool {
					return (
						'multi_buy' !== $campaign->type &&
						(
							empty( $campaign->coupon ) ||
							(
								! empty( $campaign->coupon ) &&
								in_array(
									$campaign->type,
									array( 'percentage_discount', 'fixed_discount' ),
									true
								)
							)
						)
					);
				}
			)
		);

	}

	/**
	 * Find a multi-buy campaign.
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
	 * Format a campaign.
	 *
	 * @param object $campaign Campaign.
	 *
	 * @return array
	 */
	private function format_campaign( object $campaign ): array {

		return array(
			'id'        => (int) $campaign->id,
			'name'      => (string) $campaign->name,
			'type'      => $this->format_campaign_type( (string) $campaign->type ),
			'priority'  => (int) $campaign->priority,
			'stackable' => ! empty( $campaign->stackable )
				? __( 'Yes', 'hsg-campaign-manager' )
				: __( 'No', 'hsg-campaign-manager' ),
		);

	}

	/**
	 * Format a rejected campaign.
	 *
	 * @param object $campaign Campaign.
	 * @param string $reason Reason.
	 *
	 * @return array
	 */
	private function format_rejection(
		object $campaign,
		string $reason
	): array {

		$formatted = $this->format_campaign( $campaign );
		$formatted['reason'] = $reason;

		return $formatted;

	}

	/**
	 * Build simulation explanation.
	 *
	 * @param array       $input Input.
	 * @param array       $applicable Applicable campaigns.
	 * @param array       $resolved Resolved campaigns.
	 * @param object|null $multi_buy Multi-buy campaign.
	 *
	 * @return string
	 */
	private function build_explanation(
		array $input,
		array $applicable,
		array $resolved,
		?object $multi_buy
	): string {

		if ( empty( $applicable ) ) {
			return __( 'No active campaign applied to the selected product, coupon, and date.', 'hsg-campaign-manager' );
		}

		if ( empty( $resolved ) ) {
			return __( 'Campaigns matched the product, but conflict resolution returned no winner.', 'hsg-campaign-manager' );
		}

		$explanation = __( 'Applicable campaigns were resolved by priority, campaign ID, and stackability using the runtime conflict resolver.', 'hsg-campaign-manager' );

		if ( $multi_buy ) {
			$explanation .= ' ' . sprintf(
				/* translators: %d: simulated quantity. */
				__( 'Multi-buy pricing was simulated for a quantity of %d without creating a cart.', 'hsg-campaign-manager' ),
				$input['quantity']
			);
		}

		if ( '' !== $input['customer_role'] ) {
			$explanation .= ' ' . __( 'Customer role was captured for the simulation; no role-based campaign rules exist in the current pricing engine.', 'hsg-campaign-manager' );
		}

		return $explanation;

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
				array( 'percentage_discount', 'fixed_discount' ),
				true
			)
		);

	}

	/**
	 * Normalize coupon code values.
	 *
	 * @param array $codes Coupon codes.
	 *
	 * @return array
	 */
	private function normalize_coupon_codes( array $codes ): array {

		$normalized = array();

		foreach ( $codes as $code ) {

			if ( ! is_scalar( $code ) ) {
				continue;
			}

			$code = $this->normalize_coupon_code( (string) $code );

			if ( '' === $code ) {
				continue;
			}

			$normalized[ $code ] = $code;

		}

		return array_values( $normalized );

	}

	/**
	 * Normalize a coupon code.
	 *
	 * @param string $code Coupon code.
	 *
	 * @return string
	 */
	private function normalize_coupon_code( string $code ): string {

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
	 * Return a scalar input value.
	 *
	 * @param array  $input   Input.
	 * @param string $key     Input key.
	 * @param string $default Default value.
	 *
	 * @return string
	 */
	private function scalar_value(
		array $input,
		string $key,
		string $default = ''
	): string {

		$value = $input[ $key ] ?? $default;

		if ( ! is_scalar( $value ) ) {
			return $default;
		}

		return (string) $value;

	}

	/**
	 * Format campaign type.
	 *
	 * @param string $type Campaign type.
	 *
	 * @return string
	 */
	private function format_campaign_type( string $type ): string {

		$labels = array(
			'fixed_price'         => __( 'Fixed price', 'hsg-campaign-manager' ),
			'percentage_discount' => __( 'Percentage discount', 'hsg-campaign-manager' ),
			'fixed_discount'      => __( 'Fixed discount', 'hsg-campaign-manager' ),
			'multi_buy'           => __( 'X products for Y price', 'hsg-campaign-manager' ),
		);

		return $labels[ $type ] ?? __( 'Unknown', 'hsg-campaign-manager' );

	}

	/**
	 * Format a price.
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
	 * Validate a date string in YYYY-MM-DD format.
	 *
	 * @param string $date Date.
	 *
	 * @return bool
	 */
	private function is_valid_date( string $date ): bool {

		$parsed = date_parse_from_format( 'Y-m-d', $date );

		return (
			0 === $parsed['warning_count'] &&
			0 === $parsed['error_count'] &&
			checkdate(
				(int) $parsed['month'],
				(int) $parsed['day'],
				(int) $parsed['year']
			)
		);

	}

}
