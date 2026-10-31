<?php
/**
 * String helpers.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Support;

/**
 * Small string utilities shared by the plugin and the installer.
 */
final class Str {

	/**
	 * Random lowercase hexadecimal string.
	 *
	 * @param int $bytes Number of random bytes (output is twice as long).
	 * @return string
	 */
	public static function random( int $bytes = 8 ): string {
		try {
			return bin2hex( random_bytes( $bytes ) );
		} catch ( \Exception $e ) {
			return substr( md5( uniqid( (string) mt_rand(), true ) ), 0, $bytes * 2 );
		}
	}

	/**
	 * Ensure a trailing slash.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public static function trailingslash( string $path ): string {
		return rtrim( $path, '/\\' ) . '/';
	}

	/**
	 * Ensure no trailing slash.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public static function untrailingslash( string $path ): string {
		return rtrim( $path, '/\\' );
	}

	/**
	 * Relative path of `$path` inside `$root`, using forward slashes.
	 *
	 * @param string $root Absolute base path.
	 * @param string $path Absolute path.
	 * @return string
	 */
	public static function relative( string $root, string $path ): string {
		$root = self::trailingslash( Fs::normalize( $root ) );
		$path = Fs::normalize( $path );

		if ( 0 === strpos( $path, $root ) ) {
			return substr( $path, strlen( $root ) );
		}

		return ltrim( $path, '/' );
	}

	/**
	 * Whether a string starts with a needle (PHP 7.4 compatible).
	 *
	 * @param string $haystack Subject.
	 * @param string $needle   Prefix.
	 * @return bool
	 */
	public static function starts_with( string $haystack, string $needle ): bool {
		if ( '' === $needle ) {
			return true;
		}

		return 0 === strncmp( $haystack, $needle, strlen( $needle ) );
	}

	/**
	 * Whether a string ends with a needle (PHP 7.4 compatible).
	 *
	 * @param string $haystack Subject.
	 * @param string $needle   Suffix.
	 * @return bool
	 */
	public static function ends_with( string $haystack, string $needle ): bool {
		if ( '' === $needle ) {
			return true;
		}

		$length = strlen( $needle );

		return $length <= strlen( $haystack ) && substr( $haystack, -$length ) === $needle;
	}

	/**
	 * Truncate a string to a maximum byte length.
	 *
	 * @param string $value Subject.
	 * @param int    $max   Maximum length.
	 * @return string
	 */
	public static function limit( string $value, int $max ): string {
		if ( strlen( $value ) <= $max ) {
			return $value;
		}

		return substr( $value, 0, $max );
	}
}
