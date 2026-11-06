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
