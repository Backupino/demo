<?php
/**
 * REST API Controller for Plans Catalog and Usage Telemetry.
 *
 * Implements API-CONTRACT-V1.md §3.5 (GET /plans) and §3.6 (GET /usage).
 *
 * @package Backupino\Portal\Api
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Api;

use Backupino\Portal\Plans\PlanManager;
use Backupino\Portal\License\LicenseService;
use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\Repository\ActivationRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plans and usage REST endpoints.
 */
final class PlansController {

	public const REST_NAMESPACE = 'backupino/v1';

	/**
	 * Register routes on rest_api_init.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/plans',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'handle_get_plans' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/plans/(?P<slug>[a-zA-Z0-9_\-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'handle_get_single_plan' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/usage',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'handle_get_usage' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * GET /wp-json/backupino/v1/plans
	 */
	public static function handle_get_plans( \WP_REST_Request $request ): \WP_REST_Response {
		$signed_export = PlanManager::export_signed_catalog();

		$data = array(
			'plans'               => array_values( $signed_export['catalog'] ),
			'signed_catalog_blob' => $signed_export['signed_blob'],
		);

		return LicenseController::success_response( $data, 200 );
	}

	/**
	 * GET /wp-json/backupino/v1/plans/{slug}
	 */
	public static function handle_get_single_plan( \WP_REST_Request $request ): \WP_REST_Response {
		$slug = sanitize_key( (string) $request->get_param( 'slug' ) );
		$plan = PlanManager::get( $slug );

		if ( ! $plan ) {
			return LicenseController::error_response(
				new \WP_Error( 'PLAN_NOT_FOUND', __( 'Plan does not exist.', 'backupino-portal' ), array( 'status' => 404 ) )
			);
		}

		return LicenseController::success_response( $plan->to_array(), 200 );
	}

	/**
	 * GET /wp-json/backupino/v1/usage
	 *
	 * Returns usage statistics and quota for the authenticated activation.
	 */
	public static function handle_get_usage( \WP_REST_Request $request ): \WP_REST_Response {
		$activation_token = (string) $request->get_header( 'X-Backupino-Activation-ID' );
		if ( empty( $activation_token ) ) {
			$activation_token = (string) $request->get_param( 'activation_id' );
		}

		$activation_id = LicenseService::parse_activation_id( $activation_token );
		if ( $activation_id <= 0 ) {
			return LicenseController::error_response(
				new \WP_Error( 'UNAUTHORIZED', __( 'Valid activation ID is required.', 'backupino-portal' ), array( 'status' => 401 ) )
			);
		}

		global $wpdb;
		$table = ActivationRepository::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `id` = %d LIMIT 1", $activation_id ), ARRAY_A );

		if ( ! $row ) {
			return LicenseController::error_response(
				new \WP_Error( 'ACTIVATION_NOT_FOUND', __( 'Activation not found.', 'backupino-portal' ), array( 'status' => 404 ) )
			);
		}

		$activation = new \Backupino\Portal\Models\Activation( $row );
		$license    = LicenseRepository::find_by_id( $activation->license_id );

		if ( ! $license ) {
			return LicenseController::error_response(
				new \WP_Error( 'LICENSE_NOT_FOUND', __( 'License not found.', 'backupino-portal' ), array( 'status' => 404 ) )
			);
		}

		$usage_percent = $license->cloud_quota_bytes > 0
			? round( ( $license->cloud_used_bytes / $license->cloud_quota_bytes ) * 100, 1 )
			: 0.0;

		$data = array(
			'plan'                     => $license->plan_slug,
			'domains'                  => array(
				'used'  => $license->active_domains_count,
				'limit' => $license->max_domains,
			),
			'dedicated_cloud'          => array(
				'enabled'       => (bool) $license->cloud_storage_enabled,
				'used_bytes'    => $license->cloud_used_bytes,
				'limit_bytes'   => $license->cloud_quota_bytes,
				'usage_percent' => $usage_percent,
			),
			'backup_versions_on_cloud' => $activation->cloud_domain_versions_count,
			'max_versions_per_domain'  => $license->cloud_max_versions_per_domain,
		);

		return LicenseController::success_response( $data, 200 );
	}
}
