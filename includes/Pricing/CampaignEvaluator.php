<?php
/**
 * Campaign Evaluator
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Pricing;

defined( 'ABSPATH' ) || exit;

final class CampaignEvaluator {

	/**
	 * Parent product cache.
	 *
	 * @var array
	 */
	private array $parent_ids = array();

	/**
	 * Determine whether campaign applies to a product.
	 *
	 * @param object $campaign  Campaign.
	 * @param int    $product_id Product ID.
	 *
	 * @return bool
	 */
	public function applies(
		object $campaign,
		int $product_id
	): bool {

		if ( empty( $campaign->products ) ) {
			return false;
		}

		if ( in_array( $product_id, $campaign->products, true ) ) {
			return true;
		}

		$parent_id = $this->get_parent_id( $product_id );

		return (
			$parent_id > 0 &&
			in_array( $parent_id, $campaign->products, true )
		);

	}

	/**
	 * Return parent product ID for variations.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return int
	 */
	private function get_parent_id( int $product_id ): int {

		if ( isset( $this->parent_ids[ $product_id ] ) ) {
			return $this->parent_ids[ $product_id ];
		}

		$product = wc_get_product( $product_id );

		if ( ! $product || ! $product->is_type( 'variation' ) ) {
			$this->parent_ids[ $product_id ] = 0;
			return 0;
		}

		$this->parent_ids[ $product_id ] = (int) $product->get_parent_id();

		return $this->parent_ids[ $product_id ];

	}

}
