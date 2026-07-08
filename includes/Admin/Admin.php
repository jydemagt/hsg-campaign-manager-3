<?php
/**
 * Admin
 *
 * @package HSGCampaignManager
 */

namespace HSGCM\Admin;

use HSGCM\Campaign\CampaignService;

defined( 'ABSPATH' ) || exit;

final class Admin {

	/**
	 * Campaign service.
	 *
	 * @var CampaignService
	 */
	private CampaignService $service;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->service = new CampaignService();

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
	 * Load assets.
	 *
	 * @param string $hook Current page hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {

		if ( 'woocommerce_page_hsg-campaign-manager' !== $hook ) {
			return;
		}

		// WooCommerce Select2.
		wp_enqueue_script( 'selectWoo' );
		wp_enqueue_style( 'select2' );

		// WooCommerce product search.
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'woocommerce_admin_styles' );

		wp_enqueue_style(
			'hsgcm-admin',
			HSGCM_URL . 'assets/css/admin.css',
			array( 'woocommerce_admin_styles' ),
			HSGCM_VERSION
		);

		wp_enqueue_script(
			'hsgcm-admin',
			HSGCM_URL . 'assets/js/admin.js',
			array(
				'jquery',
				'selectWoo',
				'wc-enhanced-select',
			),
			HSGCM_VERSION,
			true
		);

		wp_localize_script(
			'hsgcm-admin',
			'hsgcmAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hsgcm_admin' ),
				'i18n'    => array(
					'previewLoading' => __( 'Checking conflicts...', 'hsg-campaign-manager' ),
					'previewError'   => __( 'Unable to load conflict preview.', 'hsg-campaign-manager' ),
					'noConflicts'    => __( 'No conflicts found.', 'hsg-campaign-manager' ),
					'products'       => __( 'Overlapping products', 'hsg-campaign-manager' ),
					'priority'       => __( 'Priority comparison', 'hsg-campaign-manager' ),
					'winner'         => __( 'Would win', 'hsg-campaign-manager' ),
				),
			)
		);

	}

	/**
	 * Render page.
	 *
	 * @return void
	 */
	public function render_page(): void {

		$campaigns = $this->service->list_rows();

		require HSGCM_PATH . 'templates/admin/campaigns.php';

	}

}
