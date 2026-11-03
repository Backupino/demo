<?php
/**
 * Autoloader for the parked Pro sources.
 *
 * The `pro/` tree holds code that belongs to the commercial add-on and must
 * never be shipped inside the free plugin that is published on WordPress.org.
 * It is loaded here only for development and for the Pro test group.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BACKUPINO_PRO_SRC' ) ) {
	define( 'BACKUPINO_PRO_SRC', __DIR__ . '/src' );
}

spl_autoload_register(
	/**
	 * Map `Backupino\Foo\Bar` to `pro/src/Foo/Bar.php`.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return void
	 */
	function ( $class_name ) {
		$prefix = 'Backupino\\';
		$length = strlen( $prefix );

		if ( 0 !== strncmp( $class_name, $prefix, $length ) ) {
			return;
		}

		$relative = substr( $class_name, $length );
		$file     = BACKUPINO_PRO_SRC . '/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);
