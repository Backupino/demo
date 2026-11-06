<?php
/**
 * REST API Controller for Dynamic Feature Flags and Version Compatibility Matrix.
 *
 * Implements API-CONTRACT-V1.md §3.9 (GET /feature-flags) and §3.10 (GET /version-matrix).
 *
 * @package Backupino\Portal\Api
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Handles feature flags and version matrix endpoints.
 */
final class CompatibilityController {

	public const REST_NAMESPACE = 'backupino/v1';

	/**
	 * Register REST routes on rest_api_init.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/feature-flags',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'handle_feature_flags' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/version-matrix',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'handle_version_matrix' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * GET /wp-json/backupino/v1/feature-flags
	 */
	public static function handle_feature_flags( \WP_REST_Request $request ): \WP_REST_Response {
		$security_error = ApiSecurityMiddleware::verify( $request, ApiRateLimiter::TYPE_PUBLIC );
		if ( null !== $security_error ) {
			return $security_error;
		}

		$default_flags = array(
			'enable_ftp_tls'             => true,
			'enable_s3_multipart_v2'     => true,
			'enable_direct_rescue_agent' => true,
			'enable_delta_packs'         => false,
			'enable_argon2id_kdf'        => true,
			'enable_zstd_compression'    => true,
		);

		$flags = apply_filters( 'backupino_portal_feature_flags', $default_flags, $request );

		return ApiSecurityMiddleware::success_response( array( 'flags' => $flags ), 200 );
	}

	/**
	 * GET /wp-json/backupino/v1/version-matrix
	 */
	public static function handle_version_matrix( \WP_REST_Request $request ): \WP_REST_Response {
		$security_error = ApiSecurityMiddleware::verify( $request, ApiRateLimiter::TYPE_PUBLIC );
		if ( null !== $security_error ) {
			return $security_error;
		}

		$min_free = '1.0.1';
		$min_pro  = '1.0.0';
		$min_php  = '7.4';

		$latest_free = '1.0.2';
		$latest_pro  = '1.0.0';

		$status = 'compatible';

		// Parse client plugin version if header passed
		$version_header = (string) $request->get_header( 'X-Backupino-Plugin-Version' );
		if ( ! empty( $version_header ) ) {
			// Expected format: free=1.0.0;pro=1.0.0
			$parts = explode( ';', $version_header );
			foreach ( $parts as $part ) {
				$sub = explode( '=', trim( $part ) );
				if ( 2 === count( $sub ) ) {
					$component = trim( strtolower( $sub[0] ) );
					$version   = trim( $sub[1] );
					if ( 'free' === $component && version_compare( $version, $min_free, '<' ) ) {
						$status = 'upgrade_required';
					}
					if ( 'pro' === $component && version_compare( $version, $min_pro, '<' ) ) {
						$status = 'upgrade_required';
					}
				}
			}
		}

		return ApiSecurityMiddleware::success_response(
			array(
				'minimum_supported' => array(
					'free' => $min_free,
					'pro'  => $min_pro,
					'php'  => $min_php,
				),
				'latest_stable'     => array(
					'free' => $latest_free,
					'pro'  => $latest_pro,
				),
				'status'            => $status,
			),
			200
		);
	}
}
