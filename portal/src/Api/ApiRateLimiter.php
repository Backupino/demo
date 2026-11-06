<?php
/**
 * API Rate Limiter (Token Bucket Algorithm).
 *
 * Implements S3-T10, API-CONTRACT-V1.md §2.4.
 *
 * @package Backupino\Portal\Api
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Handles Token Bucket Rate Limiting for Backupino REST API.
 */
final class ApiRateLimiter {

	public const TYPE_PUBLIC   = 'public';   // 60 requests/min by IP
	public const TYPE_POLLING  = 'polling';  // 30 requests/min by license/activation
	public const TYPE_MUTATING = 'mutating'; // 10 requests/min by license/activation

	/**
	 * Limits definition: array( 'limit' => int, 'window' => int (seconds) )
	 */
	private static array $limits = array(
		self::TYPE_PUBLIC   => array( 'limit' => 60, 'window' => 60 ),
		self::TYPE_POLLING  => array( 'limit' => 30, 'window' => 60 ),
		self::TYPE_MUTATING => array( 'limit' => 10, 'window' => 60 ),
	);

	/**
	 * Check rate limit and return headers or null if allowed, or WP_Error if rate limited.
	 *
	 * @param string            $type Endpoint category (public, polling, mutating).
	 * @param \WP_REST_Request  $request Current REST request.
	 * @return array<string, int>|\WP_Error Headers on success, or WP_Error on limit exceeded.
	 */
	public static function check( string $type, \WP_REST_Request $request ) {
		$config = self::$limits[ $type ] ?? self::$limits[ self::TYPE_PUBLIC ];
		$limit  = $config['limit'];
		$window = $config['window'];

		// Determine client identity key
		$identifier = self::get_identifier( $type, $request );
		$cache_key  = 'bk_rl_' . substr( md5( $type . '_' . $identifier ), 0, 24 );

		$now     = time();
		$bucket  = get_transient( $cache_key );

		if ( false === $bucket || ! is_array( $bucket ) ) {
			$bucket = array(
				'tokens'    => $limit - 1,
				'reset_at'  => $now + $window,
			);
			set_transient( $cache_key, $bucket, $window );
		} else {
			if ( $bucket['tokens'] <= 0 ) {
				$retry_after = max( 1, $bucket['reset_at'] - $now );
				return new \WP_Error(
					'RATE_LIMITED',
					__( 'Too many requests. Please slow down and adhere to the Retry-After header.', 'backupino-portal' ),
					array(
						'status'  => 429,
						'headers' => array(
							'X-RateLimit-Limit'     => (string) $limit,
							'X-RateLimit-Remaining' => '0',
							'X-RateLimit-Reset'     => (string) $bucket['reset_at'],
							'Retry-After'           => (string) $retry_after,
						),
						'details' => array(
							'limit'       => $limit,
							'retry_after' => $retry_after,
						),
					)
				);
			}

			$bucket['tokens']--;
			$remaining_ttl = max( 1, $bucket['reset_at'] - $now );
			set_transient( $cache_key, $bucket, $remaining_ttl );
		}

		return array(
			'X-RateLimit-Limit'     => $limit,
			'X-RateLimit-Remaining' => max( 0, (int) $bucket['tokens'] ),
			'X-RateLimit-Reset'     => (int) $bucket['reset_at'],
		);
	}

	/**
	 * Extract identifier based on category: License/Activation ID or Client IP.
	 */
	private static function get_identifier( string $type, \WP_REST_Request $request ): string {
		if ( self::TYPE_PUBLIC !== $type ) {
			$act_id = (string) $request->get_header( 'X-Backupino-Activation-ID' );
			if ( ! empty( $act_id ) ) {
				return 'act_' . $act_id;
			}

			$lic_key = (string) $request->get_header( 'X-Backupino-License-Key' );
			if ( ! empty( $lic_key ) ) {
				return 'lic_' . $lic_key;
			}
		}

		// Fallback to client IP
		return self::get_client_ip();
	}

	/**
	 * Get sanitized client IP.
	 */
	private static function get_client_ip(): string {
		$headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		foreach ( $headers as $h ) {
			if ( ! empty( $_SERVER[ $h ] ) ) {
				$ip_list = explode( ',', (string) $_SERVER[ $h ] );
				$ip      = trim( $ip_list[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}
		return '127.0.0.1';
	}
}
