<?php
/**
 * Plugin Name: HSG Campaign Manager
 * Plugin URI: https://hsg-whisky.dk
 * Description: Campaign manager for HSG Whisky.
 * Version: 3.0.0
 * Author: HSG Whisky
 * Requires PHP: 8.1
 * Requires at least: 6.5
 * WC requires at least: 8.0
 * WC tested up to: 10.2
 * Text Domain: hsg-campaign-manager
 */

defined( 'ABSPATH' ) || exit;

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

define( 'HSGCM_VERSION', '3.0.0' );
define( 'HSGCM_PATH', plugin_dir_path( __FILE__ ) );
define( 'HSGCM_URL', plugin_dir_url( __FILE__ ) );

/*
|--------------------------------------------------------------------------
| WooCommerce feature compatibility
|--------------------------------------------------------------------------
*/

add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);

/*
|--------------------------------------------------------------------------
| Autoloader
|--------------------------------------------------------------------------
*/

require_once HSGCM_PATH . 'includes/Loader.php';

/*
|--------------------------------------------------------------------------
| Boot plugin
|--------------------------------------------------------------------------
*/

HSGCM\Loader::register();

HSGCM\Plugin::instance();
