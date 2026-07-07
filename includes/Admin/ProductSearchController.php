<?php
/**
 * Product Search Controller
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Admin;

defined( 'ABSPATH' ) || exit;

final class ProductSearchController {

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_action(
			'wp_ajax_hsgcm_product_search',
			array( $this, 'search' )
		);

	}

	/**
	 * Search WooCommerce products.
	 *
	 * @return void
	 */
	public function search(): void {

		check_ajax_referer(
			'hsgcm_admin',
			'nonce'
		);

		if ( ! current_user_can( 'manage_woocommerce' ) ) {

			wp_send_json_error();

		}

		$term = trim(
			sanitize_text_field(
				wp_unslash( $_GET['term'] ?? '' )
			)
		);

		if ( '' === $term ) {
			wp_send_json(
				array(
					'results' => array(),
				)
			);
		}

		$results  = array();
		$seen_ids = array();

		if ( ctype_digit( $term ) ) {
			$this->add_product_result(
				(int) $term,
				$results,
				$seen_ids
			);
		}

		$this->append_query_results(
			array(
				's' => $term,
			),
			$results,
			$seen_ids,
			20 - count( $results )
		);

		if ( count( $results ) < 20 ) {
			$this->append_query_results(
				array(
					'meta_query' => array(
						array(
							'key'     => '_sku',
							'value'   => $term,
							'compare' => 'LIKE',
						),
					),
				),
				$results,
				$seen_ids,
				20 - count( $results )
			);
		}

		wp_send_json(
			array(
				'results' => array_slice( $results, 0, 20 ),
			)
		);

	}

	/**
	 * Append products from a query.
	 *
	 * @param array $query_args Query args.
	 * @param array $results Results.
	 * @param array $seen_ids Seen product IDs.
	 * @param int   $limit Remaining result slots.
	 *
	 * @return void
	 */
	private function append_query_results(
		array $query_args,
		array &$results,
		array &$seen_ids,
		int $limit
	): void {

		if ( $limit <= 0 ) {
			return;
		}

		$query = new \WP_Query(
			array_merge(
				array(
					'post_type'           => array(
						'product',
						'product_variation',
					),
					'post_status'         => array(
						'publish',
						'private',
					),
					'posts_per_page'      => $limit,
					'fields'              => 'ids',
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
					'orderby'             => 'title',
					'order'               => 'ASC',
				),
				$query_args
			)
		);

		foreach ( $query->posts as $product_id ) {

			$this->add_product_result(
				(int) $product_id,
				$results,
				$seen_ids
			);

			if ( count( $results ) >= 20 ) {
				break;
			}

		}

	}

	/**
	 * Append a single product to the result set.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $results Results.
	 * @param array $seen_ids Seen product IDs.
	 *
	 * @return void
	 */
	private function add_product_result(
		int $product_id,
		array &$results,
		array &$seen_ids
	): void {

		if ( $product_id <= 0 || isset( $seen_ids[ $product_id ] ) ) {
			return;
		}

		if (
			! in_array(
				get_post_status( $product_id ),
				array(
					'publish',
					'private',
				),
				true
			)
		) {
			return;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return;
		}

		$seen_ids[ $product_id ] = true;

		$results[] = array(
			'id'   => $product->get_id(),
			'text' => $product->get_formatted_name(),
		);

	}

}
