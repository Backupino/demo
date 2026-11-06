<?php
/**
 * Ed25519 Keypair and Signature Manager.
 *
 * @package Backupino\Portal\Security
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Security;

use Backupino\Portal\Config\Environment;

defined( 'ABSPATH' ) || exit;

/**
 * Handles cryptographic keypair storage, generation and signing.
 */
final class KeypairManager {

	private const PRIVATE_KEY_FILE = 'ed25519-private.key';
	private const PUBLIC_KEY_FILE  = 'ed25519-public.key';

	/**
	 * Ensure keys directory exists and is secured (0700).
	 */
	public static function ensure_secure_dir(): string {
		$dir = Environment::keys_dir();
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			@chmod( $dir, 0700 );

			// Write protection .htaccess
			$htaccess = $dir . '/.htaccess';
			if ( ! file_exists( $htaccess ) ) {
				@file_put_contents( $htaccess, "Deny from all\nOptions -Indexes\n" );
			}

			// Write index.php
			$index = $dir . '/index.php';
			if ( ! file_exists( $index ) ) {
				@file_put_contents( $index, "<?php // Silence is golden.\n" );
			}
		}

		return $dir;
	}

	/**
	 * Generate and store new Ed25519 keypair if not already present.
	 *
	 * @return array{public: string, private: string} Base64Url-encoded keys.
	 */
	public static function initialize_keypair(): array {
		$dir        = self::ensure_secure_dir();
		$priv_path  = $dir . '/' . self::PRIVATE_KEY_FILE;
		$pub_path   = $dir . '/' . self::PUBLIC_KEY_FILE;

		if ( file_exists( $priv_path ) && file_exists( $pub_path ) ) {
			return array(
				'public'  => trim( (string) file_get_contents( $pub_path ) ),
				'private' => trim( (string) file_get_contents( $priv_path ) ),
			);
		}

		if ( ! function_exists( 'sodium_crypto_sign_keypair' ) ) {
			throw new \RuntimeException( 'ext-sodium is required for Ed25519 cryptographic key generation.' );
		}

		$keypair = sodium_crypto_sign_keypair();
		$priv_bin = sodium_crypto_sign_secretkey( $keypair );
		$pub_bin  = sodium_crypto_sign_publickey( $keypair );

		$priv_b64 = self::base64url_encode( $priv_bin );
		$pub_b64  = self::base64url_encode( $pub_bin );

		file_put_contents( $priv_path, $priv_b64 );
		@chmod( $priv_path, 0600 );

		file_put_contents( $pub_path, $pub_b64 );
		@chmod( $pub_path, 0644 );

		// Store public key in options as well for quick export
		update_option( 'backupino_portal_public_key', $pub_b64, 'yes' );

		return array(
			'public'  => $pub_b64,
			'private' => $priv_b64,
		);
	}

	/**
	 * Get server public key.
	 */
	public static function get_public_key(): string {
		$cached = (string) get_option( 'backupino_portal_public_key', '' );
		if ( ! empty( $cached ) ) {
			return $cached;
		}

		$keypair = self::initialize_keypair();
		return $keypair['public'];
	}

	/**
	 * Sign data using Ed25519 private key.
	 *
	 * @param string $data Plain message/canonical string to sign.
	 * @return string Base64Url-encoded 64-byte signature.
	 */
	public static function sign_detached( string $data ): string {
		$keypair  = self::initialize_keypair();
		$priv_bin = self::base64url_decode( $keypair['private'] );

		if ( function_exists( 'sodium_crypto_sign_detached' ) ) {
			$signature_bin = sodium_crypto_sign_detached( $data, $priv_bin );
			return self::base64url_encode( $signature_bin );
		}

		throw new \RuntimeException( 'Cannot produce Ed25519 signature: ext-sodium is missing.' );
	}

	/**
	 * Verify an Ed25519 detached signature.
	 *
	 * Checks against the active public key or optionally previous public key (for rotation grace).
	 *
	 * @param string      $data          Original message.
	 * @param string      $signature_b64 Base64Url-encoded signature.
	 * @param string|null $pub_b64       Optional public key (defaults to active key).
	 * @return bool True if valid.
	 */
	public static function verify_detached( string $data, string $signature_b64, ?string $pub_b64 = null ): bool {
		$pub_key = null !== $pub_b64 ? $pub_b64 : self::get_public_key();
		$pub_bin = self::base64url_decode( $pub_key );
		$sig_bin = self::base64url_decode( $signature_b64 );

		if ( 32 !== strlen( $pub_bin ) || 64 !== strlen( $sig_bin ) ) {
			return false;
		}

		if ( function_exists( 'sodium_crypto_sign_verify_detached' ) ) {
			try {
				if ( sodium_crypto_sign_verify_detached( $sig_bin, $data, $pub_bin ) ) {
					return true;
				}

				// If no custom key was supplied, also try verifying against the previous key during rotation grace
				if ( null === $pub_b64 ) {
					$prev_key = self::get_previous_public_key();
					if ( ! empty( $prev_key ) ) {
						$prev_bin = self::base64url_decode( $prev_key );
						if ( 32 === strlen( $prev_bin ) && sodium_crypto_sign_verify_detached( $sig_bin, $data, $prev_bin ) ) {
							return true;
						}
					}
				}
			} catch ( \Throwable $e ) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Get previous public key (if rotation occurred).
	 */
	public static function get_previous_public_key(): ?string {
		$prev = (string) get_option( 'backupino_portal_previous_public_key', '' );
		return ! empty( $prev ) ? $prev : null;
	}

	/**
	 * Perform Ed25519 key rotation.
	 *
	 * Archives current public key and generates new active keypair.
	 *
	 * @return array{public: string, private: string, previous_public: string}
	 */
	public static function rotate_keypair(): array {
		$current_pub = self::get_public_key();
		update_option( 'backupino_portal_previous_public_key', $current_pub, 'no' );

		$dir       = self::ensure_secure_dir();
		$priv_path = $dir . '/' . self::PRIVATE_KEY_FILE;
		$pub_path  = $dir . '/' . self::PUBLIC_KEY_FILE;

		// Archive old keys with timestamp suffix
		$timestamp = time();
		if ( file_exists( $priv_path ) ) {
			@rename( $priv_path, $dir . '/' . self::PRIVATE_KEY_FILE . '.' . $timestamp . '.bak' );
		}
		if ( file_exists( $pub_path ) ) {
			@rename( $pub_path, $dir . '/' . self::PUBLIC_KEY_FILE . '.' . $timestamp . '.bak' );
		}

		delete_option( 'backupino_portal_public_key' );
		$new_keypair = self::initialize_keypair();

		if ( class_exists( 'Backupino\Portal\Audit\AuditLogger' ) ) {
			\Backupino\Portal\Audit\AuditLogger::log(
				'keypair_rotated',
				get_current_user_id(),
				0,
				null,
				array(
					'old_public' => substr( $current_pub, 0, 16 ) . '...',
					'new_public' => substr( $new_keypair['public'], 0, 16 ) . '...',
				)
			);
		}

		return array(
			'public'          => $new_keypair['public'],
			'private'         => $new_keypair['private'],
			'previous_public' => $current_pub,
		);
	}

	/**
	 * Base64Url encoding without padding.
	 */
	public static function base64url_encode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Base64Url decoding.
	 */
	public static function base64url_decode( string $data ): string {
		return (string) base64_decode( strtr( $data, '-_', '+/' ) );
	}
}
