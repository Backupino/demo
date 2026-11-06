<?php
/**
 * REST API Controller for Cloud Storage and Backup Metadata Indexing.
 *
 * Implements API-CONTRACT-V1.md §3.7 (POST /backup-index) and §3.8 (POST /storage/download-url).
 *
 * @package Backupino\Portal\Api
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Api;

use Backupino\Portal\License\LicenseService;
use Backupino\Portal\Repository\ActivationRepository;
use Backupino\Portal\Repository\BackupRepository;
use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\Security\KeypairManager;

defined( 'ABSPATH' ) || exit;

/**
 * Handles storage index and download URL requests.
 */
final class StorageController {

	public const REST_NAMESPACE = 'backupino/v1';

	/**
	 * Register REST routes on rest_api_init.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/backup-index',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle_backup_index' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/storage/download-url',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle_download_url' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * POST /wp-json/backupino/v1/backup-index
	 */
	public static function handle_backup_index( \WP_REST_Request $request ): \WP_REST_Response {
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

		$activation = ActivationRepository::find_by_id( $act_id );
		if ( ! $activation || ! $activation->is_active ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'ACTIVATION_NOT_FOUND', __( 'Active activation record not found.', 'backupino-portal' ), array( 'status' => 404 ) )
			);
		}

		$license = LicenseRepository::find_by_id( $activation->license_id );
		if ( ! $license ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'LICENSE_NOT_FOUND', __( 'Associated license not found.', 'backupino-portal' ), array( 'status' => 404 ) )
			);
		}

		$package_hash = sanitize_text_field( (string) $request->get_param( 'package_hash' ) );
		if ( empty( $package_hash ) || strlen( $package_hash ) < 32 ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'INVALID_PAYLOAD', __( 'A valid sha256 package_hash is required.', 'backupino-portal' ), array( 'status' => 400 ) )
			);
		}

		$package_size   = (int) $request->get_param( 'package_size_bytes' );
		$storage_driver = sanitize_key( (string) $request->get_param( 'storage_driver' ) );
		if ( empty( $storage_driver ) ) {
			$storage_driver = 'dedicated-s3';
		}

		$package_name = sanitize_file_name( (string) $request->get_param( 'package_name' ) );
		if ( empty( $package_name ) ) {
			$package_name = 'backupino-pkg-' . substr( $package_hash, 0, 12 ) . '.bpkg';
		}

		$is_sandbox = ApiSecurityMiddleware::is_sandbox( $request );

		// Check cloud storage quota if cloud storage is used and not sandbox
		if ( ! $is_sandbox && $license->cloud_storage_enabled && $license->cloud_quota_bytes > 0 ) {
			if ( ( $license->cloud_used_bytes + $package_size ) > $license->cloud_quota_bytes ) {
				return ApiSecurityMiddleware::error_response(
					new \WP_Error(
						'STORAGE_QUOTA_EXCEEDED',
						__( 'Dedicated cloud storage quota exceeded for this license.', 'backupino-portal' ),
						array(
							'status'  => 403,
							'details' => array(
								'used_bytes'  => $license->cloud_used_bytes,
								'quota_bytes' => $license->cloud_quota_bytes,
							),
						)
					)
				);
			}
		}

		// Calculate retention candidates
		$max_versions     = $license->cloud_max_versions_per_domain > 0 ? $license->cloud_max_versions_per_domain : 5;
		$existing_backups = BackupRepository::find_by_activation_id( $act_id, 100 );
		$prune_candidates = array();
		$retention_action = 'none';

		if ( count( $existing_backups ) >= $max_versions ) {
			$retention_action = 'prune_required';
			// Backups are ordered DESC by created_at; oldest are at the end
			$excess_count = count( $existing_backups ) - $max_versions + 1;
			$oldest_slice = array_slice( array_reverse( $existing_backups ), 0, $excess_count );
			foreach ( $oldest_slice as $old_backup ) {
				$prune_candidates[] = array(
					'backup_hash'  => $old_backup->backup_hash,
					'package_name' => $old_backup->package_name,
					'size_bytes'   => $old_backup->file_size_bytes,
				);
			}
		}

		// Record backup metadata
		$backup_id = BackupRepository::create(
			array(
				'license_id'       => $license->id,
				'activation_id'    => $activation->id,
				'domain_hash'      => $activation->domain_hash,
				'backup_hash'      => $package_hash,
				'package_name'     => $package_name,
				'file_size_bytes'  => $package_size,
				'storage_provider' => $storage_driver,
				'storage_path'     => 'vault/' . $activation->domain_hash . '/' . $package_name,
				'retention_status' => 'active',
			)
		);

		if ( ! $backup_id ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'INTERNAL_ERROR', __( 'Could not index cloud backup metadata.', 'backupino-portal' ), array( 'status' => 500 ) )
			);
		}

		// Update activation & license counters
		global $wpdb;
		$act_table = ActivationRepository::table();
		$lic_table = LicenseRepository::table();

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$act_table}` SET 
					`cloud_domain_used_bytes` = `cloud_domain_used_bytes` + %d,
					`cloud_domain_versions_count` = `cloud_domain_versions_count` + 1,
					`cloud_last_backup_at` = %s
				WHERE `id` = %d",
				$package_size,
				current_time( 'mysql', true ),
				$activation->id
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$lic_table}` SET `cloud_used_bytes` = `cloud_used_bytes` + %d WHERE `id` = %d",
				$package_size,
				$license->id
			)
		);

		$response_data = array(
			'indexed'          => true,
			'backup_id'        => $backup_id,
			'retention_action' => $retention_action,
			'prune_candidates' => $prune_candidates,
		);

		// Cache for idempotency if key was provided
		$idemp_key = (string) $request->get_header( 'Idempotency-Key' );
		if ( ! empty( $idemp_key ) ) {
			ApiSecurityMiddleware::set_idempotent_response( $idemp_key, $request, $response_data, 200 );
		}

		return ApiSecurityMiddleware::success_response( $response_data, 200 );
	}

	/**
	 * POST /wp-json/backupino/v1/storage/download-url
	 */
	public static function handle_download_url( \WP_REST_Request $request ): \WP_REST_Response {
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

		$package_hash = sanitize_text_field( (string) $request->get_param( 'package_hash' ) );
		if ( empty( $package_hash ) ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'INVALID_PAYLOAD', __( 'package_hash is required.', 'backupino-portal' ), array( 'status' => 400 ) )
			);
		}

		$backup = BackupRepository::find_by_backup_hash( $package_hash );
		if ( ! $backup ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'BACKUP_NOT_FOUND', __( 'Cloud backup package not found in catalog.', 'backupino-portal' ), array( 'status' => 404 ) )
			);
		}

		$ttl        = min( 86400, max( 300, (int) $request->get_param( 'requested_expiry_seconds' ) ?: 3600 ) );
		$expires_at = time() + $ttl;

		$download_url = self::generate_download_url( $package_hash, $act_id, $ttl );

		return ApiSecurityMiddleware::success_response(
			array(
				'download_url' => $download_url,
				'expires_at'   => $expires_at,
				'sha256'       => $package_hash,
			),
			200
		);
	}

	/**
	 * Generate signed download URL for package (internal & REST helper).
	 *
	 * @param string $package_hash
	 * @param int    $act_id
	 * @param int    $ttl
	 * @return string
	 */
	public static function generate_download_url( string $package_hash, int $act_id, int $ttl = 3600 ): string {
		$expires_at = time() + $ttl;
		$token_data = $package_hash . '|' . $expires_at . '|' . $act_id;
		$signature  = KeypairManager::sign_detached( $token_data );

		return add_query_arg(
			array(
				'pkg' => $package_hash,
				'exp' => $expires_at,
				'sig' => $signature,
				'act' => $act_id,
			),
			rest_url( self::REST_NAMESPACE . '/storage/stream' )
		);
	}
}
