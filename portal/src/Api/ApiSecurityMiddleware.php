<?php
/**
 * API Security Middleware (Anti-Clock Skew, Anti-Replay, Idempotency, Envelope).
 *
 * Implements S3-T10, API-CONTRACT-V1.md §2.
 *
 * @package Backupino\Portal\Api
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Handles security checks, idempotency, and response standard envelopes.
 */
final class ApiSecurityMiddleware {

	/**
	 * Run comprehensive security checks on incoming REST request.
	 *
	 * @param \WP_REST_Request $request
	 * @param string           $rate_limit_type
	 * @return \WP_REST_Response|null Returns error response if check fails, or null if passed.
	 */
	public static function verify( \WP_REST_Request $request, string $rate_limit_type = ApiRateLimiter::TYPE_MUTATING ): ?\WP_REST_Response {
		// 1. Rate Limiting Check
		$rl = ApiRateLimiter::check( $rate_limit_type, $request );
		if ( is_wp_error( $rl ) ) {
			return self::error_response( $rl );
		}

		// 2. Anti-Clock Skew Check (Max 300 seconds deviation)
		$timestamp_header = $request->get_header( 'X-Backupino-Timestamp' );
		if ( ! empty( $timestamp_header ) && is_numeric( $timestamp_header ) ) {
			$client_time = (int) $timestamp_header;
			if ( abs( time() - $client_time ) > 300 ) {
				return self::error_response(
					new \WP_Error(
						'CLOCK_SKEW_DETECTED',
						__( 'Client timestamp differs from server by more than 300 seconds.', 'backupino-portal' ),
						array( 'status' => 400 )
					)
				);
			}
		}

		// 3. Anti-Replay Check (X-Backupino-Nonce uniqueness within 24h)
		$nonce = (string) $request->get_header( 'X-Backupino-Nonce' );
		if ( ! empty( $nonce ) ) {
			$nonce_key = 'bk_nonce_' . substr( md5( $nonce ), 0, 24 );
			if ( get_transient( $nonce_key ) ) {
				return self::error_response(
					new \WP_Error(
						'REPLAY_DETECTED',
						__( 'Request nonce has already been used.', 'backupino-portal' ),
						array( 'status' => 400 )
					)
				);
			}
			set_transient( $nonce_key, 1, 86400 );
		}

		// 4. Idempotency Check (for mutating requests)
		$idempotency_key = (string) $request->get_header( 'Idempotency-Key' );
		if ( ! empty( $idempotency_key ) ) {
			$cached = self::get_idempotent_response( $idempotency_key, $request );
			if ( $cached instanceof \WP_REST_Response ) {
				return $cached;
			}
			if ( is_wp_error( $cached ) ) {
				return self::error_response( $cached );
			}
		}

		return null;
	}

	/**
	 * Check if Idempotency-Key was already processed.
	 *
	 * @param string           $key
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error|null
	 */
	private static function get_idempotent_response( string $key, \WP_REST_Request $request ) {
		$transient_key = 'bk_idemp_' . substr( md5( $key ), 0, 24 );
		$existing      = get_transient( $transient_key );

		if ( ! empty( $existing ) && is_array( $existing ) ) {
			$current_payload_hash = hash( 'sha256', (string) $request->get_body() );
			if ( $existing['payload_hash'] !== $current_payload_hash ) {
				return new \WP_Error(
					'IDEMPOTENCY_CONFLICT',
					__( 'Idempotency key was previously used with a different request body.', 'backupino-portal' ),
					array( 'status' => 409 )
				);
			}

			return new \WP_REST_Response( $existing['response_data'], (int) $existing['response_status'] );
		}

		return null;
	}

	/**
	 * Cache successful response for Idempotency-Key.
	 *
	 * @param string           $key
	 * @param \WP_REST_Request $request
	 * @param mixed            $response_data
	 * @param int              $status
	 */
	public static function set_idempotent_response( string $key, \WP_REST_Request $request, $response_data, int $status ): void {
		if ( empty( $key ) ) {
			return;
		}

		$transient_key = 'bk_idemp_' . substr( md5( $key ), 0, 24 );
		set_transient(
			$transient_key,
			array(
				'payload_hash'    => hash( 'sha256', (string) $request->get_body() ),
				'response_data'   => $response_data,
				'response_status' => $status,
			),
			86400
		);
	}

	/**
	 * Determine if request is running in Sandbox mode.
	 */
	public static function is_sandbox( \WP_REST_Request $request ): bool {
		$hdr = strtolower( (string) $request->get_header( 'X-Backupino-Sandbox' ) );
		return ( 'true' === $hdr || '1' === $hdr || (bool) $request->get_param( 'is_staging' ) );
	}

	/**
	 * Build standardized success envelope.
	 *
	 * @param mixed $data
	 * @param int   $status
	 * @param array $headers
	 * @return \WP_REST_Response
	 */
	public static function success_response( $data, int $status = 200, array $headers = array() ): \WP_REST_Response {
		$response = new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
				'meta'    => array(
					'timestamp'      => time(),
					'request_id'     => 'req_' . bin2hex( random_bytes( 8 ) ),
					'server_version' => BACKUPINO_PORTAL_VERSION,
				),
			),
			$status
		);

		foreach ( $headers as $k => $v ) {
			$response->header( $k, (string) $v );
		}

		return $response;
	}

	/**
	 * Build standardized error envelope.
	 *
	 * @param \WP_Error $error
	 * @return \WP_REST_Response
	 */
	public static function error_response( \WP_Error $error ): \WP_REST_Response {
		$error_data = $error->get_error_data();
		$status     = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : 400;

		$payload = array(
			'success' => false,
			'error'   => array(
				'code'    => $error->get_error_code(),
				'message' => $error->get_error_message(),
			),
			'meta'    => array(
				'timestamp'  => time(),
				'request_id' => 'req_' . bin2hex( random_bytes( 8 ) ),
			),
		);

		if ( is_array( $error_data ) && isset( $error_data['details'] ) ) {
			$payload['error']['details'] = $error_data['details'];
		}

		$response = new \WP_REST_Response( $payload, $status );

		if ( is_array( $error_data ) && isset( $error_data['headers'] ) && is_array( $error_data['headers'] ) ) {
			foreach ( $error_data['headers'] as $k => $v ) {
				$response->header( $k, (string) $v );
			}
		}

		return $response;
	}
}
