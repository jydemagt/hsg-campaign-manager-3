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

		$term = sanitize_text_field(
			wp_unslash( $_GET['term'] ?? '' )
		);

		$query = new \WP_Query(
			array(
				'post_type'      => array(
					'product',
					'product_variation',
				),
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				's'              => $term,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$results = array();

		foreach ( $query->posts as $post ) {

			$product = wc_get_product( $post->ID );

			if ( ! $product ) {
				continue;
			}

			$results[] = array(
				'id'   => $product->get_id(),
				'text' => $product->get_formatted_name(),
			);

		}

		wp_send_json(
			array(
				'results' => $results,
			)
		);

	}

}
