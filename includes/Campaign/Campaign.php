<?php
/**
 * Campaign Post Type
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Campaign;

defined( 'ABSPATH' ) || exit;

final class Campaign {

	/**
	 * Post type.
	 */
	private const POST_TYPE = 'hsg_campaign';

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_action(
			'init',
			array( $this, 'register_post_type' )
		);

	}

	/**
	 * Register campaign post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {

		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'          => __( 'Campaigns', 'hsg-campaign-manager' ),
					'singular_name' => __( 'Campaign', 'hsg-campaign-manager' ),
				),

				'public'             => false,
				'show_ui'            => false,
				'show_in_menu'       => false,
				'show_in_admin_bar'  => false,
				'show_in_nav_menus'  => false,
				'show_in_rest'       => false,
				'publicly_queryable' => false,
				'exclude_from_search'=> true,
				'has_archive'        => false,
				'hierarchical'       => false,

				'supports' => array(
					'title',
				),

				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);

	}

	/**
	 * Return post type.
	 *
	 * @return string
	 */
	public static function post_type(): string {

		return self::POST_TYPE;

	}

}
