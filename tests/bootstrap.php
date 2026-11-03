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

define( 'BACKUPINO_VERSION', '1.0.1-test' );
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

if ( ! function_exists( 'esc_html_e' ) ) {
	/**
	 * Escaped translation echo shim.
	 *
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return void
	 */
	function esc_html_e( $text, $domain = '' ) {
		echo htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	/**
	 * Attribute escaping echo shim.
	 *
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return void
	 */
	function esc_attr_e( $text, $domain = '' ) {
		echo htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	/**
	 * Textarea escaping shim.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_textarea( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * URL escaping shim.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	function esc_url( $url ) {
		return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'checked' ) ) {
	/**
	 * Checked helper shim.
	 *
	 * @param mixed $checked Checked.
	 * @param mixed $current Current.
	 * @param bool  $echo    Echo.
	 * @return string
	 */
	function checked( $checked, $current = true, $echo = true ) {
		$result = ( (string) $checked === (string) $current ) ? ' checked="checked"' : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
}

if ( ! function_exists( 'selected' ) ) {
	/**
	 * Selected helper shim.
	 *
	 * @param mixed $selected Selected.
	 * @param mixed $current  Current.
	 * @param bool  $echo     Echo.
	 * @return string
	 */
	function selected( $selected, $current = true, $echo = true ) {
		$result = ( (string) $selected === (string) $current ) ? ' selected="selected"' : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
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

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Text sanitising shim.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function sanitize_text_field( $text ) {
		return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $text ) ) );
	}
}

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Action shim that records what fired.
	 *
	 * @param string $hook Hook name.
	 * @param mixed  ...$args Arguments.
	 * @return void
	 */
	function do_action( $hook, ...$args ) {
		$GLOBALS['backupino_actions'][] = array( $hook, $args );
	}
}

if ( ! function_exists( 'did_action' ) ) {
	/**
	 * Whether a hook fired.
	 *
	 * @param string $hook Hook name.
	 * @return int
	 */
	function did_action( $hook ) {
		$count = 0;

		foreach ( (array) ( $GLOBALS['backupino_actions'] ?? array() ) as $fired ) {
			if ( $hook === $fired[0] ) {
				++$count;
			}
		}

		return $count;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Listener shim; the tested code paths fire hooks, they do not register them.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @param int      $args     Accepted args.
	 * @return bool
	 */
	function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
		return true;
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


if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return $GLOBALS['backupino_options'][ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		$GLOBALS['backupino_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( $timestamp, $recurrence, $hook, $args = array() ) {
		$GLOBALS['backupino_cron'][ $hook ] = array(
			'timestamp'  => $timestamp,
			'recurrence' => $recurrence,
			'args'       => $args,
		);
		return true;
	}
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
	function wp_schedule_single_event( $timestamp, $hook, $args = array() ) {
		$GLOBALS['backupino_cron'][ $hook ] = array(
			'timestamp'  => $timestamp,
			'recurrence' => false,
			'args'       => $args,
		);
		return true;
	}
}

if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
	function wp_clear_scheduled_hook( $hook, $args = array() ) {
		unset( $GLOBALS['backupino_cron'][ $hook ] );
		return 1;
	}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook, $args = array() ) {
		return isset( $GLOBALS['backupino_cron'][ $hook ] ) ? (int) $GLOBALS['backupino_cron'][ $hook ]['timestamp'] : false;
	}
}


if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Filter listener shim.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @param int      $args     Accepted args.
	 * @return bool
	 */
	function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
		return true;
	}
}


if ( ! function_exists( 'plugin_basename' ) ) {
	/**
	 * Plugin basename shim.
	 *
	 * @param string $file File path.
	 * @return string
	 */
	function plugin_basename( $file ) {
		return basename( dirname( (string) $file ) ) . '/' . basename( (string) $file );
	}
}


if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 'ARRAY_N' );
}

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}


if ( ! function_exists( 'wp_upload_dir' ) ) {
	/**
	 * Upload directory shim.
	 *
	 * @return array<string, string>
	 */
	function wp_upload_dir() {
		return array(
			'basedir' => sys_get_temp_dir() . '/wp-uploads',
			'baseurl' => 'https://example.test/wp-content/uploads',
		);
	}
}


if ( ! function_exists( 'load_plugin_textdomain' ) ) {
	/**
	 * Textdomain loader shim.
	 *
	 * @param string      $domain          Domain.
	 * @param string|bool $deprecated      Deprecated.
	 * @param string|bool $plugin_rel_path Relative path.
	 * @return bool
	 */
	function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) {
		$GLOBALS['backupino_textdomains'][ $domain ] = $plugin_rel_path;
		return true;
	}
}

if ( ! function_exists( 'is_rtl' ) ) {
	/**
	 * RTL check shim.
	 *
	 * @return bool
	 */
	function is_rtl() {
		return ! empty( $GLOBALS['backupino_is_rtl'] );
	}
}

if ( ! function_exists( 'get_locale' ) ) {
	/**
	 * Locale getter shim.
	 *
	 * @return string
	 */
	function get_locale() {
		return $GLOBALS['backupino_locale'] ?? 'en_US';
	}
}


if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * Capability check shim.
	 *
	 * @param string $capability Capability.
	 * @return bool
	 */
	function current_user_can( $capability ) {
		return ! empty( $GLOBALS['backupino_current_user_can'][ $capability ] );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Strip all tags shim.
	 *
	 * @param string $text Content.
	 * @return string
	 */
	function wp_strip_all_tags( $text ) {
		return strip_tags( (string) $text );
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	/**
	 * Admin URL shim.
	 *
	 * @param string $path Path relative to admin.
	 * @return string
	 */
	function admin_url( $path = '' ) {
		return 'https://example.test/wp-admin/' . ltrim( (string) $path, '/' );
	}
}

$GLOBALS['wpdb'] = new \Backupino\Tests\Support\FakeDb();

