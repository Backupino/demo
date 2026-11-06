<?php
/**
 * Plugin Name: Backupino Portal Engine
 * Plugin URI: https://backupino.com
 * Description: Core licensing, API v1 gateway, order synchronization and customer management for Backupino.
 * Version: 1.0.0
 * Author: Backupino Team
 * Author URI: https://backupino.com
 * License: Proprietary
 * Text Domain: backupino-portal
 * Domain Path: /languages
 *
 * @package Backupino\Portal
 */

declare( strict_types = 1 );

namespace Backupino\Portal;

defined( 'ABSPATH' ) || exit;

define( 'BACKUPINO_PORTAL_VERSION', '1.0.0' );
define( 'BACKUPINO_PORTAL_FILE', __FILE__ );
define( 'BACKUPINO_PORTAL_DIR', __DIR__ );
define( 'BACKUPINO_PORTAL_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader for Backupino Portal classes.
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'Backupino\\Portal\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( $prefix ) );
		$file           = BACKUPINO_PORTAL_DIR . '/src/' . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * Initialize Portal Engine on plugins_loaded.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		PortalEngine::instance()->boot();
	},
	5
);

/**
 * Run migrations on activation.
 */
register_activation_hook(
	__FILE__,
	static function (): void {
		PortalEngine::instance()->activate();
	}
);
