<?php
/**
 * REST API Controller for Licensing Gateway v1.
 *
 * Implements API-CONTRACT-V1.md §3 endpoints.
 *
 * @package Backupino\Portal\Api
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Api;

use Backupino\Portal\License\LicenseService;
use Backupino\Portal\Support\DomainHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Handles licensing REST API endpoints.
 */
final class LicenseController {

	public const REST_NAMESPACE = 'backupino/v1';

	/**
	 * Register REST routes on rest_api_init.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/license/activate',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle_activate' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/license/validate',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle_validate' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/license/deactivate',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle_deactivate' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/license/heartbeat',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle_heartbeat' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * POST /wp-json/backupino/v1/license/activate
	 */
	public static function handle_activate( \WP_REST_Request $request ): \WP_REST_Response {
		$security_error = self::verify_request_security( $request );
		if ( null !== $security_error ) {
			return $security_error;
		}

		$license_key = (string) $request->get_header( 'X-Backupino-License-Key' );
		if ( empty( $license_key ) ) {
			$license_key = (string) $request->get_param( 'license_key' );
		}

		$domain = (string) $request->get_param( 'site_url' );
		if ( empty( $domain ) ) {
			// Fallback to Origin or Referer or remote address if site_url not in payload
			$domain = (string) $request->get_header( 'Origin' );
		}

		$instance_id = (string) $request->get_header( 'X-Backupino-Instance-ID' );
		if ( empty( $instance_id ) ) {
			$instance_id = (string) $request->get_param( 'instance_id' );
		}
		if ( empty( $instance_id ) ) {
			$instance_id = 'inst_' . bin2hex( random_bytes( 8 ) );
		}

		$meta = array(
			'php_version' => (string) $request->get_param( 'php_version' ),
			'wp_version'  => (string) $request->get_param( 'wp_version' ),
			'pro_version' => (string) $request->get_param( 'pro_version' ),
			'locale'      => (string) $request->get_param( 'locale' ),
		);

		$result = LicenseService::activate( $license_key, $domain, $instance_id, $meta );

		if ( is_wp_error( $result ) ) {
			return self::error_response( $result );
		}

		return self::success_response( $result, 200 );
	}

	/**
	 * POST /wp-json/backupino/v1/license/validate
	 */
	public static function handle_validate( \WP_REST_Request $request ): \WP_REST_Response {
		$security_error = self::verify_request_security( $request );
		if ( null !== $security_error ) {
			return $security_error;
		}

		$activation_id = (string) $request->get_header( 'X-Backupino-Activation-ID' );
		if ( empty( $activation_id ) ) {
			$activation_id = (string) $request->get_param( 'activation_id' );
		}

		$cached_sig_hash = (string) $request->get_param( 'cached_license_signature_hash' );

		$result = LicenseService::validate( $activation_id, $cached_sig_hash );

		if ( is_wp_error( $result ) ) {
			return self::error_response( $result );
		}

		return self::success_response( $result, 200 );
	}

	/**
	 * POST /wp-json/backupino/v1/license/deactivate
	 */
	public static function handle_deactivate( \WP_REST_Request $request ): \WP_REST_Response {
		$security_error = self::verify_request_security( $request );
		if ( null !== $security_error ) {
			return $security_error;
		}

		$activation_id = (string) $request->get_header( 'X-Backupino-Activation-ID' );
		if ( empty( $activation_id ) ) {
			$activation_id = (string) $request->get_param( 'activation_id' );
		}

		$reason = (string) $request->get_param( 'reason' );

		$result = LicenseService::deactivate( $activation_id, $reason );

		if ( is_wp_error( $result ) ) {
			return self::error_response( $result );
		}

		return self::success_response( $result, 200 );
	}

	/**
	 * POST /wp-json/backupino/v1/license/heartbeat
	 */
	public static function handle_heartbeat( \WP_REST_Request $request ): \WP_REST_Response {
		$security_error = self::verify_request_security( $request );
		if ( null !== $security_error ) {
			return $security_error;
		}

		$activation_id = (string) $request->get_header( 'X-Backupino-Activation-ID' );
		if ( empty( $activation_id ) ) {
			$activation_id = (string) $request->get_param( 'activation_id' );
		}

		$telemetry = array(
			'last_backup_timestamp' => (int) $request->get_param( 'last_backup_timestamp' ),
			'last_backup_status'    => (string) $request->get_param( 'last_backup_status' ),
			'total_backups_count'   => (int) $request->get_param( 'total_backups_count' ),
			'health_status'         => (string) $request->get_param( 'health_status' ),
		);

		$result = LicenseService::heartbeat( $activation_id, $telemetry );

		if ( is_wp_error( $result ) ) {
			return self::error_response( $result );
		}

		return self::success_response( $result, 200 );
	}

	/**
	 * Verify request headers: anti-clock skew (<300s) and anti-replay (nonce).
	 */
	private static function verify_request_security( \WP_REST_Request $request ): ?\WP_REST_Response {
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

		$nonce = (string) $request->get_header( 'X-Backupino-Nonce' );
		if ( ! empty( $nonce ) ) {
			$nonce_key = 'bk_nonce_' . substr( $nonce, 0, 32 );
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

		return null;
	}

	/**
	 * Build standardized successful response envelope.
	 *
	 * @param mixed $data
	 * @param int   $status
	 * @return \WP_REST_Response
	 */
	public static function success_response( $data, int $status = 200 ): \WP_REST_Response {
		return new \WP_REST_Response(
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
	}

	/**
	 * Build standardized error response envelope.
	 *
	 * @param \WP_Error $error
	 * @return \WP_REST_Response
	 */
	public static function error_response( \WP_Error $error ): \WP_REST_Response {
		$error_data = $error->get_error_data();
		$status     = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : 400;

		$response_payload = array(
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
			$response_payload['error']['details'] = $error_data['details'];
		}

		return new \WP_REST_Response( $response_payload, $status );
	}
}
