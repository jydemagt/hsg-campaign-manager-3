<?php
/**
 * Autoloader
 *
 * @package HSGCampaignManager
 */

namespace HSGCM;

defined( 'ABSPATH' ) || exit;

final class Loader {

	/**
	 * Register autoloader.
	 *
	 * @return void
	 */
	public static function register(): void {

		spl_autoload_register(
			array(
				self::class,
				'autoload',
			)
		);

	}

	/**
	 * Autoload classes.
	 *
	 * @param string $class Class name.
	 *
	 * @return void
	 */
	private static function autoload( string $class ): void {

		$prefix = __NAMESPACE__ . '\\';

		if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, strlen( $prefix ) );

		$file = HSGCM_PATH .
			'includes/' .
			str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) .
			'.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}

	}

}
