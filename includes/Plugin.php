<?php
/**
 * Plugin Bootstrap
 *
 * @package HSGCampaignManager
 */

namespace HSGCM;

use HSGCM\Admin\Admin;
use HSGCM\Admin\AjaxController;
use HSGCM\Admin\ProductSearchController;
use HSGCM\Campaign\Campaign;
use HSGCM\Pricing\PricingService;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/**
	 * Instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Return instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Constructor.
	 */
	private function __construct() {

		add_action(
			'plugins_loaded',
			array( $this, 'init' )
		);

	}

	/**
	 * Initialise plugin.
	 *
	 * @return void
	 */
	public function init(): void {

		// WooCommerce required.
		if ( ! class_exists( 'WooCommerce' ) ) {

			add_action(
				'admin_notices',
				array( $this, 'woocommerce_notice' )
			);

			return;

		}

		// Register campaign post type.
		new Campaign();

		// Register AJAX controllers.
		new AjaxController();
		new ProductSearchController();

		// Register pricing engine.
		new PricingService();

		// Load admin.
		if ( is_admin() ) {
			new Admin();
		}

	}

	/**
	 * WooCommerce missing notice.
	 *
	 * @return void
	 */
	public function woocommerce_notice(): void {

		?>

		<div class="notice notice-error">

			<p>

				<strong>HSG Campaign Manager</strong>

				<?php esc_html_e(
					' requires WooCommerce to be installed and activated.',
					'hsg-campaign-manager'
				); ?>

			</p>

		</div>

		<?php

	}

}
