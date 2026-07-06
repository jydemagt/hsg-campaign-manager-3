<?php
/**
 * Admin
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Admin;

use HSGCM\Campaign\CampaignRepository;

defined( 'ABSPATH' ) || exit;

final class Admin {

	/**
	 * Repository.
	 *
	 * @var CampaignRepository
	 */
	private CampaignRepository $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->repository = new CampaignRepository();

		add_action(
			'admin_menu',
			array( $this, 'register_menu' )
		);

		add_action(
			'admin_enqueue_scripts',
			array( $this, 'enqueue_assets' )
		);

	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function register_menu(): void {

		add_submenu_page(
			'woocommerce',
			__( 'Campaign Manager', 'hsg-campaign-manager' ),
			__( 'Campaign Manager', 'hsg-campaign-manager' ),
			'manage_woocommerce',
			'hsg-campaign-manager',
			array( $this, 'render_page' )
		);

	}

	/**
	 * Load CSS & JavaScript.
	 *
	 * @param string $hook Current admin page.
	 *
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {

		if ( 'woocommerce_page_hsg-campaign-manager' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'hsgcm-admin',
			HSGCM_URL . 'assets/css/admin.css',
			array(),
			HSGCM_VERSION
		);

		wp_enqueue_script(
			'hsgcm-admin',
			HSGCM_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			HSGCM_VERSION,
			true
		);

		wp_localize_script(
			'hsgcm-admin',
			'hsgcmAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hsgcm_admin' ),
			)
		);

	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_page(): void {

		$campaigns = $this->repository->all();

		require HSGCM_PATH . 'templates/admin/campaigns.php';

	}

}
