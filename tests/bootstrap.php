<?php
/**
 * Test bootstrap.
 *
 * Loads the plugin autoloader and provides the handful of WordPress functions
 * the plugin touches, so that pure classes can be tested without a database.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

define( 'BACKUPINO_VERSION', '0.1.0-test' );
define( 'BACKUPINO_FILE', dirname( __DIR__ ) . '/free/backupino.php' );
define( 'BACKUPINO_DIR', dirname( __DIR__ ) . '/free/' );
define( 'BACKUPINO_URL', 'https://example.test/wp-content/plugins/backupino/' );
define( 'BACKUPINO_MIN_PHP', '7.4' );
define( 'BACKUPINO_MIN_WP', '6.0' );
define( 'BACKUPINO_TESTS', true );

require_once dirname( __DIR__ ) . '/free/src/autoload.php';
require_once dirname( __DIR__ ) . '/pro/autoload.php';
require_once __DIR__ . '/Harness/AssertionFailed.php';
require_once __DIR__ . '/Harness/TestCase.php';
require_once __DIR__ . '/Support/FakeDb.php';
require_once __DIR__ . '/Support/FakeConnection.php';
require_once __DIR__ . '/Support/ExpiredDeadline.php';
require_once __DIR__ . '/Support/MemoryOptions.php';

/*
 * ---------------------------------------------------------------------------
 * WordPress function shims.
 *
 * Only the functions actually touched by the tested code paths are defined.
 * Anything missing is a signal that the class under test is too coupled to
 * WordPress, which is intentional.
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( '__' ) ) {
	/**
	 * Translation shim.
	 *
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return string
	 */
	function __( $text, $domain = '' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Escaped translation shim.
	 *
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return string
	 */
	function esc_html__( $text, $domain = '' ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Escaping shim.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Attribute escaping shim.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * JSON shim.
	 *
	 * @param mixed $data    Data.
	 * @param int   $options Options.
	 * @param int   $depth   Depth.
	 * @return string|false
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'number_format_i18n' ) ) {
	/**
	 * Number formatting shim.
	 *
	 * @param float $number Number.
	 * @param int   $decimals Decimals.
	 * @return string
	 */
	function number_format_i18n( $number, $decimals = 0 ) {
		return number_format( (float) $number, (int) $decimals );
	}
}

if ( ! function_exists( 'home_url' ) ) {
	/**
	 * Site URL shim.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	function home_url( $path = '' ) {
		return 'https://example.test' . $path;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * URL parser shim.
	 *
	 * @param string $url       URL.
	 * @param int    $component Component.
	 * @return mixed
	 */
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Filter shim.
	 *
	 * @param string $hook  Hook.
	 * @param mixed  $value Value.
	 * @return mixed
	 */
	function apply_filters( $hook, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'esc_sql' ) ) {
	/**
	 * SQL escaping shim.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	function esc_sql( $value ) {
		return str_replace(
			array( '\\', "\0", "\n", "\r", "'", '"', "\x1a" ),
			array( '\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z' ),
			(string) $value
		);
	}
}
