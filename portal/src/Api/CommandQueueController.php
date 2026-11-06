<?php
/**
 * REST API Controller for Pull-Only Command Queue and Result Reporting (D-042).
 *
 * Implements API-CONTRACT-V1.md §3.11 (GET /command-queue) and §3.12 (POST /command-result).
 *
 * @package Backupino\Portal\Api
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Api;

use Backupino\Portal\Audit\AuditLogger;
use Backupino\Portal\License\LicenseService;
use Backupino\Portal\Repository\ActivationRepository;
use Backupino\Portal\Repository\CommandQueueRepository;
use Backupino\Portal\Security\KeypairManager;

defined( 'ABSPATH' ) || exit;

/**
 * Handles signed pull-only command queue polling and completion reports.
 */
final class CommandQueueController {

	public const REST_NAMESPACE = 'backupino/v1';

	/**
	 * Register REST routes on rest_api_init.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/command-queue',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'handle_get_queue' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/command-result',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle_command_result' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * GET /wp-json/backupino/v1/command-queue
	 */
	public static function handle_get_queue( \WP_REST_Request $request ): \WP_REST_Response {
		$security_error = ApiSecurityMiddleware::verify( $request, ApiRateLimiter::TYPE_POLLING );
		if ( null !== $security_error ) {
			return $security_error;
		}

		$activation_token = (string) $request->get_header( 'X-Backupino-Activation-ID' );
		if ( empty( $activation_token ) ) {
			$activation_token = (string) $request->get_param( 'activation_id' );
		}

		$act_id = LicenseService::parse_activation_id( $activation_token );
		if ( $act_id <= 0 ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'UNAUTHORIZED', __( 'Valid activation ID is required.', 'backupino-portal' ), array( 'status' => 401 ) )
			);
		}

		$activation = ActivationRepository::find_by_id( $act_id );
		if ( ! $activation || ! $activation->is_active ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'ACTIVATION_NOT_FOUND', __( 'Active activation not found.', 'backupino-portal' ), array( 'status' => 404 ) )
			);
		}

		$pending_rows = CommandQueueRepository::find_pending_by_activation_id( $act_id );
		$commands     = array();

		foreach ( $pending_rows as $row ) {
			$payload_data = json_decode( (string) $row['payload'], true );
			if ( ! is_array( $payload_data ) ) {
				$payload_data = array();
			}

			$issued_ts  = strtotime( (string) $row['issued_at'] );
			$expires_ts = strtotime( (string) $row['expires_at'] );

			// Build canonical string for cryptographic verification: command_id|action|payload_json|nonce|issued_at|expires_at
			$canonical_string = implode(
				'|',
				array(
					$row['command_id'],
					$row['action'],
					(string) $row['payload'],
					$row['nonce'],
					$issued_ts,
					$expires_ts,
				)
			);

			$signature = KeypairManager::sign_detached( $canonical_string );

			$commands[] = array(
				'command_id' => $row['command_id'],
				'action'     => $row['action'],
				'payload'    => $payload_data,
				'nonce'      => $row['nonce'],
				'issued_at'  => $issued_ts,
				'expires_at' => $expires_ts,
				'signature'  => $signature,
			);
		}

		return ApiSecurityMiddleware::success_response( array( 'commands' => $commands ), 200 );
	}

	/**
	 * POST /wp-json/backupino/v1/command-result
	 */
	public static function handle_command_result( \WP_REST_Request $request ): \WP_REST_Response {
		$security_error = ApiSecurityMiddleware::verify( $request, ApiRateLimiter::TYPE_MUTATING );
		if ( null !== $security_error ) {
			return $security_error;
		}

		$activation_token = (string) $request->get_header( 'X-Backupino-Activation-ID' );
		if ( empty( $activation_token ) ) {
			$activation_token = (string) $request->get_param( 'activation_id' );
		}

		$act_id = LicenseService::parse_activation_id( $activation_token );
		if ( $act_id <= 0 ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'UNAUTHORIZED', __( 'Valid activation ID is required.', 'backupino-portal' ), array( 'status' => 401 ) )
			);
		}

		$command_id = sanitize_text_field( (string) $request->get_param( 'command_id' ) );
		$status     = sanitize_key( (string) $request->get_param( 'status' ) );
		if ( ! in_array( $status, array( 'completed', 'failed' ), true ) ) {
			$status = 'completed';
		}

		$existing = CommandQueueRepository::find_by_command_id( $command_id );
		if ( ! $existing || (int) $existing['activation_id'] !== $act_id ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'COMMAND_NOT_FOUND', __( 'Command not found or belongs to another activation.', 'backupino-portal' ), array( 'status' => 404 ) )
			);
		}

		$result_payload = $request->get_param( 'result' );
		if ( ! is_array( $result_payload ) ) {
			$result_payload = array( 'raw' => $result_payload );
		}

		CommandQueueRepository::mark_status( $command_id, $status, $result_payload );

		AuditLogger::log(
			'command_' . $status,
			0,
			(int) $existing['license_id'],
			null,
			array(
				'command_id' => $command_id,
				'action'     => $existing['action'],
			)
		);

		return ApiSecurityMiddleware::success_response( array( 'acknowledged' => true ), 200 );
	}
}
