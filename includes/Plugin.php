<?php
/**
 * Plugin Bootstrap
 *
 * @package HSGCampaignManager
 */

namespace HSGCM;

use HSGCM\Admin\Admin;
use HSGCM\Campaign\Campaign;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Get instance.
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
	 * Initialize plugin.
	 *
	 * @return void
	 */
	public function init(): void {

		// WooCommerce is required.
		if ( ! class_exists( 'WooCommerce' ) ) {

			add_action(
				'admin_notices',
				array( $this, 'woocommerce_notice' )
			);

			return;

		}

		// Register campaign post type.
		new Campaign();

		// Start admin.
		if ( is_admin() ) {
			new Admin();
		}

	}

	/**
	 * Show WooCommerce required notice.
	 *
	 * @return void
	 */
	public function woocommerce_notice(): void {

		?>

		<div class="notice notice-error">

			<p>

				<strong>HSG Campaign Manager</strong>

				<?php esc_html_e( ' requires WooCommerce to be installed and activated.', 'hsg-campaign-manager' ); ?>

			</p>

		</div>

		<?php

	}

}
