<?php
/**
 * Portal Environment Manager.
 *
 * @package Backupino\Portal\Config
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Manages environment configurations (dev, staging, prod).
 */
final class Environment {

	public const ENV_DEV     = 'dev';
	public const ENV_STAGING = 'staging';
	public const ENV_PROD    = 'prod';

	/**
	 * Get active environment name.
	 *
	 * @return string 'dev' | 'staging' | 'prod'
	 */
	public static function current(): string {
		if ( defined( 'BACKUPINO_PORTAL_ENV' ) ) {
			$env = strtolower( trim( (string) constant( 'BACKUPINO_PORTAL_ENV' ) ) );
			if ( in_array( $env, array( self::ENV_DEV, self::ENV_STAGING, self::ENV_PROD ), true ) ) {
				return $env;
			}
		}

		// Auto-detect based on host
		$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : 'localhost';
		if ( 'localhost' === $host || '127.0.0.1' === $host || false !== strpos( $host, '.test' ) || false !== strpos( $host, '.local' ) ) {
			return self::ENV_DEV;
		}

		if ( 0 === strpos( $host, 'staging.' ) || 0 === strpos( $host, 'dev.' ) ) {
			return self::ENV_STAGING;
		}

		return self::ENV_PROD;
	}

	/**
	 * Check if running in development mode.
	 */
	public static function is_dev(): bool {
		return self::ENV_DEV === self::current();
	}

	/**
	 * Check if running in production mode.
	 */
	public static function is_prod(): bool {
		return self::ENV_PROD === self::current();
	}

	/**
	 * Check if running in staging mode.
	 */
	public static function is_staging(): bool {
		return self::ENV_STAGING === self::current();
	}

	/**
	 * Get directory path for storing private cryptographic keys.
	 */
	public static function keys_dir(): string {
		if ( defined( 'BACKUPINO_KEYS_DIR' ) ) {
			return rtrim( (string) constant( 'BACKUPINO_KEYS_DIR' ), '/\\' );
		}

		$upload_dir = wp_upload_dir();
		$secure_dir = dirname( $upload_dir['basedir'] ) . '/backupino-keys';

		return $secure_dir;
	}

	/**
	 * Should enable debug logging.
	 */
	public static function is_debug(): bool {
		return self::is_dev() || ( defined( 'WP_DEBUG' ) && WP_DEBUG );
	}
}
