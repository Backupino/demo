<?php
/**
 * Canonical License Payload Builder and Signer.
 *
 * Implements LICENSE-CONTRACT-ED25519.md.
 *
 * @package Backupino\Portal\License
 */

declare( strict_types = 1 );

namespace Backupino\Portal\License;

use Backupino\Portal\Security\KeypairManager;

defined( 'ABSPATH' ) || exit;

/**
 * Builds canonical license JSON and produces signed compact blobs.
 */
final class LicenseSigner {

	public const ISSUER = 'https://portal.backupino.com';

	/**
	 * Build canonical payload array for signing.
	 *
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	public static function build_payload( array $params ): array {
		$now = time();

		$payload = array(
			'issuer'            => self::ISSUER,
			'activation_id'     => isset( $params['activation_id'] ) ? (string) $params['activation_id'] : 'act_' . bin2hex( random_bytes( 8 ) ),
			'plan'              => isset( $params['plan'] ) ? (string) $params['plan'] : 'personal',
			'domains'           => isset( $params['domains'] ) && is_array( $params['domains'] ) ? array_values( $params['domains'] ) : array(),
			'quota'             => array(
				'max_domains'         => isset( $params['max_domains'] ) ? (int) $params['max_domains'] : 1,
				'cloud_storage_bytes' => isset( $params['cloud_storage_bytes'] ) ? (int) $params['cloud_storage_bytes'] : 0,
				'retention_versions'  => isset( $params['retention_versions'] ) ? (int) $params['retention_versions'] : 5,
				'features'            => isset( $params['features'] ) && is_array( $params['features'] ) ? array_values( $params['features'] ) : array(),
			),
			'issued_at'         => isset( $params['issued_at'] ) ? (int) $params['issued_at'] : $now,
			'expiry'            => isset( $params['expiry'] ) ? (int) $params['expiry'] : ( $now + ( 365 * 86400 ) ),
			'grace_period_days' => isset( $params['grace_period_days'] ) ? (int) $params['grace_period_days'] : 7,
			'nonce'             => isset( $params['nonce'] ) ? (string) $params['nonce'] : bin2hex( random_bytes( 16 ) ),
		);

		return $payload;
	}

	/**
	 * Canonicalize payload array (recursive ksort) and encode to JSON.
	 *
	 * @param array<string, mixed> $payload
	 * @return string Canonical JSON string without extra whitespace.
	 */
	public static function to_canonical_json( array $payload ): string {
		self::recursive_ksort( $payload );
		return (string) wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Sign a canonical payload and generate a compact license blob.
	 *
	 * Blob format: Base64Url(CanonicalJSON) . '.' . Base64Url(Signature)
	 *
	 * @param array<string, mixed> $params Payload parameters.
	 * @return string Compact signed license blob.
	 */
	public static function sign_license( array $params ): string {
		$payload        = self::build_payload( $params );
		$canonical_json = self::to_canonical_json( $payload );

		$signature = KeypairManager::sign_detached( $canonical_json );
		$b64_json  = KeypairManager::base64url_encode( $canonical_json );

		return $b64_json . '.' . $signature;
	}

	/**
	 * Verify and parse a compact license blob.
	 *
	 * @param string      $blob      Compact license blob.
	 * @param string|null $publicKey Optional public key (default: active key).
	 * @return array<string, mixed>|false Parsed payload array if valid, false otherwise.
	 */
	public static function verify_license_blob( string $blob, ?string $publicKey = null ) {
		$parts = explode( '.', trim( $blob ) );
		if ( 2 !== count( $parts ) ) {
			return false;
		}

		list( $b64_json, $signature ) = $parts;

		$canonical_json = KeypairManager::base64url_decode( $b64_json );
		if ( empty( $canonical_json ) ) {
			return false;
		}

		$is_valid = KeypairManager::verify_detached( $canonical_json, $signature, $publicKey );
		if ( ! $is_valid ) {
			return false;
		}

		$data = json_decode( $canonical_json, true );
		return is_array( $data ) ? $data : false;
	}

	/**
	 * Recursively sort associative array keys alphabetically.
	 *
	 * @param array<mixed> $array
	 */
	private static function recursive_ksort( array &$array ): void {
		foreach ( $array as &$value ) {
			if ( is_array( $value ) ) {
				self::recursive_ksort( $value );
			}
		}
		// If associative, sort keys
		if ( array_keys( $array ) !== range( 0, count( $array ) - 1 ) ) {
			ksort( $array, SORT_STRING );
		}
	}
}
