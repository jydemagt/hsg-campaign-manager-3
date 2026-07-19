<?php
/**
 * Campaign Repository
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Campaign;

defined( 'ABSPATH' ) || exit;

final class CampaignRepository {

	/**
	 * Meta key.
	 */
	private const META_KEY = '_hsgcm_campaign';

	/**
	 * Return all campaigns.
	 *
	 * @return array
	 */
	public function all(): array {

		return get_posts(
			array(
				'post_type'      => Campaign::post_type(),
				'post_status'    => array(
					'draft',
					'publish',
				),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

	}

	/**
	 * Return raw data for all editable campaigns.
	 *
	 * @return array
	 */
	public function all_raw(): array {

		$campaigns = array();

		foreach ( $this->all() as $post ) {

			$campaign = $this->find_raw( (int) $post->ID );

			if ( is_array( $campaign ) ) {
				$campaigns[] = $campaign;
			}

		}

		return $campaigns;

	}

	/**
	 * Return published campaigns.
	 *
	 * @return array
	 */
	public function published(): array {

		return get_posts(
			array(
				'post_type'      => Campaign::post_type(),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

	}

	/**
	 * Find campaign.
	 *
	 * @param int $id Campaign ID.
	 *
	 * @return array|null
	 */
	public function find( int $id ): ?array {

		$post = get_post( $id );

		if (
			! $post ||
			$post->post_type !== Campaign::post_type()
		) {
			return null;
		}

		$data = get_post_meta(
			$id,
			self::META_KEY,
			true
		);

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$data = array_merge(
			array(
				'id'         => $id,
				'name'       => $post->post_title,
				'status'     => $post->post_status,
				'start_date' => '',
				'end_date'   => '',
				'priority'   => 0,
				'quantity'   => 2,
				'bundle_price' => '',
				'products'   => array(),
				'type'       => 'fixed_price',
				'value'      => '',
				'coupon'     => '',
				'stackable'  => false,
			),
			$data
		);

		$data['id']       = $id;
		$data['name']     = $post->post_title;
		$data['status']   = $post->post_status;
		$data['products'] = is_array( $data['products'] ) ? $data['products'] : array();

		$product_list = array();

		foreach ( $data['products'] as $product_id ) {

			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			$product_list[] = array(
				'id'   => $product->get_id(),
				'text' => $product->get_formatted_name(),
			);

		}

		$data['products'] = $product_list;

		return $data;

	}

	/**
	 * Find raw campaign data for service use.
	 *
	 * @param int $id Campaign ID.
	 *
	 * @return array|null
	 */
	public function find_raw( int $id ): ?array {

		$post = get_post( $id );

		if (
			! $post ||
			$post->post_type !== Campaign::post_type()
		) {
			return null;
		}

		$data = get_post_meta(
			$id,
			self::META_KEY,
			true
		);

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$data = array_merge(
			array(
				'id'         => $id,
				'name'       => $post->post_title,
				'status'     => $post->post_status,
				'start_date' => '',
				'end_date'   => '',
				'priority'   => 0,
				'quantity'   => 2,
				'bundle_price' => '',
				'products'   => array(),
				'type'       => 'fixed_price',
				'value'      => '',
				'coupon'     => '',
				'stackable'  => false,
			),
			$data
		);

		$data['id']       = $id;
		$data['name']     = $post->post_title;
		$data['status']   = $post->post_status;
		$data['products'] = is_array( $data['products'] ) ? $data['products'] : array();

		return $data;

	}

	/**
	 * Resolve products into admin display rows.
	 *
	 * @param array $product_ids Product IDs.
	 *
	 * @return array
	 */
	public function products_for_ids( array $product_ids ): array {

		$products = array();

		foreach ( array_values( array_unique( array_map( 'absint', $product_ids ) ) ) as $product_id ) {

			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			$products[] = array(
				'id'   => $product->get_id(),
				'text' => $product->get_formatted_name(),
			);

		}

		return $products;

	}

	/**
	 * Create campaign.
	 *
	 * @param array $campaign Campaign.
	 *
	 * @return int|\WP_Error
	 */
	public function create( array $campaign ) {

		$post_id = wp_insert_post(
			array(
				'post_type'   => Campaign::post_type(),
				'post_title'  => $campaign['name'],
				'post_status' => $campaign['status'],
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$campaign['id'] = (int) $post_id;

		update_post_meta(
			$post_id,
			self::META_KEY,
			$campaign
		);

		return (int) $post_id;

	}

	/**
	 * Update campaign.
	 *
	 * @param int   $id Campaign ID.
	 * @param array $campaign Campaign.
	 *
	 * @return bool
	 */
	public function update(
		int $id,
		array $campaign
	): bool {

		$result = wp_update_post(
			array(
				'ID'          => $id,
				'post_title'  => $campaign['name'],
				'post_status' => $campaign['status'],
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return false;
		}

		update_post_meta(
			$id,
			self::META_KEY,
			$campaign
		);

		return true;

	}

	/**
	 * Update campaign status.
	 *
	 * @param int    $id     Campaign ID.
	 * @param string $status Campaign status.
	 *
	 * @return bool
	 */
	public function update_status(
		int $id,
		string $status
	): bool {

		$result = wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => $status,
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return false;
		}

		$data = get_post_meta(
			$id,
			self::META_KEY,
			true
		);

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$data['id']     = $id;
		$data['status'] = $status;

		update_post_meta(
			$id,
			self::META_KEY,
			$data
		);

		return true;

	}

	/**
	 * Delete campaign.
	 *
	 * @param int $id Campaign ID.
	 *
	 * @return bool
	 */
	public function delete( int $id ): bool {

		return false !== wp_delete_post(
			$id,
			true
		);

	}

	/**
	 * Count campaigns.
	 *
	 * @return int
	 */
	public function count(): int {

		$count = wp_count_posts(
			Campaign::post_type()
		);

		return (int) (
			$count->publish +
			$count->draft
		);

	}

}
