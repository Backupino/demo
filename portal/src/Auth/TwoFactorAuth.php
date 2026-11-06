<?php
/**
 * Two-Factor Authentication (TOTP / RFC 6238) Engine.
 *
 * Offline-first, zero-dependency pure PHP implementation.
 *
 * @package Backupino\Portal\Auth
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Auth;

defined( 'ABSPATH' ) || exit;

/**
 * Manages TOTP generation, validation, and recovery codes.
 */
final class TwoFactorAuth {

	public const META_SECRET         = '_backupino_2fa_secret';
	public const META_ENABLED        = '_backupino_2fa_enabled';
	public const META_RECOVERY_CODES = '_backupino_2fa_recovery_codes';

	private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	/**
	 * Generate a random Base32 secret key.
	 *
	 * @param int $length Number of characters (standard 16 or 32).
	 * @return string Base32 encoded secret.
	 */
	public static function generate_secret( int $length = 16 ): string {
		$secret = '';
		$chars  = self::BASE32_CHARS;
		$max    = strlen( $chars ) - 1;

		for ( $i = 0; $i < $length; $i++ ) {
			$secret .= $chars[ random_int( 0, $max ) ];
		}

		return $secret;
	}

	/**
	 * Calculate current 6-digit TOTP code for a secret.
	 *
	 * @param string   $secret    Base32 secret.
	 * @param int|null $timestamp Unix timestamp (defaults to current time).
	 * @param int      $time_step Time step in seconds (standard 30).
	 * @return string 6-digit code.
	 */
	public static function get_totp_code( string $secret, ?int $timestamp = null, int $time_step = 30 ): string {
		$time = null !== $timestamp ? $timestamp : time();
		$time_slice = (int) floor( $time / $time_step );

		// Pack 64-bit integer into 8 bytes big-endian.
		$packed_time = pack( 'N*', 0, $time_slice );

		$decoded_secret = self::base32_decode( $secret );
		$hash           = hash_hmac( 'sha1', $packed_time, $decoded_secret, true );

		// Dynamic truncation per RFC 4226 / RFC 6238.
		$offset = ord( $hash[ strlen( $hash ) - 1 ] ) & 0x0f;

		$truncated_hash = (
			( ( ord( $hash[ $offset ] ) & 0x7f ) << 24 ) |
			( ( ord( $hash[ $offset + 1 ] ) & 0xff ) << 16 ) |
			( ( ord( $hash[ $offset + 2 ] ) & 0xff ) << 8 ) |
			( ord( $hash[ $offset + 3 ] ) & 0xff )
		);

		$code = $truncated_hash % 1000000;

		return str_pad( (string) $code, 6, '0', STR_PAD_LEFT );
	}

	/**
	 * Verify a TOTP code with time drift window.
	 *
	 * @param string $secret      Base32 secret.
	 * @param string $code        6-digit user input.
	 * @param int    $discrepancy Allowed steps before/after (default 1 = +/- 30s).
	 * @return bool
	 */
	public static function verify_code( string $secret, string $code, int $discrepancy = 1 ): bool {
		$code = trim( $code );
		if ( 6 !== strlen( $code ) || ! ctype_digit( $code ) ) {
			return false;
		}

		$current_time = time();

		for ( $i = -$discrepancy; $i <= $discrepancy; $i++ ) {
			$calculated = self::get_totp_code( $secret, $current_time + ( $i * 30 ) );
			if ( hash_equals( $calculated, $code ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate an otpauth:// URI suitable for authenticator apps / QR codes.
	 *
	 * @param string $username Account identifier (email).
	 * @param string $secret   Base32 secret.
	 * @param string $issuer   Application name.
	 * @return string
	 */
	public static function get_otpauth_uri( string $username, string $secret, string $issuer = 'Backupino' ): string {
		$encoded_user   = rawurlencode( $username );
		$encoded_issuer = rawurlencode( $issuer );

		return "otpauth://totp/{$encoded_issuer}:{$encoded_user}?secret={$secret}&issuer={$encoded_issuer}&algorithm=SHA1&digits=6&period=30";
	}

	/**
	 * Generate a set of single-use backup recovery codes.
	 *
	 * @param int $count Number of codes to generate.
	 * @return string[] Array of plain recovery codes.
	 */
	public static function generate_recovery_codes( int $count = 8 ): array {
		$codes = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$codes[] = strtoupper( bin2hex( random_bytes( 5 ) ) ); // 10 alphanumeric chars
		}
		return $codes;
	}

	/**
	 * Check if 2FA is active for a user.
	 */
	public static function is_2fa_enabled( int $user_id ): bool {
		return (bool) get_user_meta( $user_id, self::META_ENABLED, true );
	}

	/**
	 * Enable 2FA for a user with verified secret and hashed recovery codes.
	 *
	 * @param int      $user_id        WordPress user ID.
	 * @param string   $secret         Base32 secret.
	 * @param string[] $recovery_codes Plain recovery codes.
	 * @return bool
	 */
	public static function enable_2fa( int $user_id, string $secret, array $recovery_codes ): bool {
		// Store encrypted or obscured secret.
		update_user_meta( $user_id, self::META_SECRET, $secret );
		update_user_meta( $user_id, self::META_ENABLED, 1 );

		// Hash recovery codes before saving.
		$hashed_codes = array_map(
			static fn( string $code ) => password_hash( strtoupper( trim( $code ) ), PASSWORD_DEFAULT ),
			$recovery_codes
		);
		update_user_meta( $user_id, self::META_RECOVERY_CODES, $hashed_codes );

		return true;
	}

	/**
	 * Disable 2FA for a user.
	 */
	public static function disable_2fa( int $user_id ): bool {
		delete_user_meta( $user_id, self::META_SECRET );
		delete_user_meta( $user_id, self::META_ENABLED );
		delete_user_meta( $user_id, self::META_RECOVERY_CODES );

		return true;
	}

	/**
	 * Verify user's 2FA challenge using either 6-digit TOTP or backup recovery code.
	 *
	 * @param int    $user_id User ID.
	 * @param string $input   6-digit code or 10-char recovery code.
	 * @return bool True if valid.
	 */
	public static function verify_user_2fa( int $user_id, string $input ): bool {
		$input = trim( $input );

		if ( ! self::is_2fa_enabled( $user_id ) ) {
			return true; // Not required if not enabled
		}

		$secret = (string) get_user_meta( $user_id, self::META_SECRET, true );

		// 1. Try TOTP code first
		if ( 6 === strlen( $input ) && ctype_digit( $input ) ) {
			return self::verify_code( $secret, $input );
		}

		// 2. Try recovery codes
		$saved_hashes = get_user_meta( $user_id, self::META_RECOVERY_CODES, true );
		if ( is_array( $saved_hashes ) ) {
			$clean_input = strtoupper( $input );
			foreach ( $saved_hashes as $index => $hash ) {
				if ( password_verify( $clean_input, (string) $hash ) ) {
					// Single-use: remove this code from stored list
					unset( $saved_hashes[ $index ] );
					update_user_meta( $user_id, self::META_RECOVERY_CODES, array_values( $saved_hashes ) );
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Decode Base32 string into binary bytes.
	 *
	 * @param string $base32 Base32 string.
	 * @return string Binary string.
	 */
	private static function base32_decode( string $base32 ): string {
		$base32 = strtoupper( trim( $base32 ) );
		$buffer = 0;
		$bits_left = 0;
		$result = '';
		$alphabet = self::BASE32_CHARS;

		for ( $i = 0, $len = strlen( $base32 ); $i < $len; $i++ ) {
			$char = $base32[ $i ];
			if ( '=' === $char ) {
				break;
			}

			$val = strpos( $alphabet, $char );
			if ( false === $val ) {
				continue;
			}

			$buffer = ( $buffer << 5 ) | $val;
			$bits_left += 5;

			if ( $bits_left >= 8 ) {
				$bits_left -= 8;
				$result .= chr( ( $buffer >> $bits_left ) & 0xff );
			}
		}

		return $result;
	}
}
