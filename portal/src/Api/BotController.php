<?php
/**
 * REST API Controller for Central Notification Bot (S3-T12, D-009, D-033, D-040).
 *
 * Exclusively serves Pro licenses. Zero presence in Free core.
 *
 * @package Backupino\Portal\Api
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Api;

use Backupino\Portal\Audit\AuditLogger;
use Backupino\Portal\License\LicenseService;
use Backupino\Portal\Repository\ActivationRepository;
use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\Ui\CustomerDashboardService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles bot linking, status queries, and event dispatching.
 */
final class BotController {

	public const REST_NAMESPACE = 'backupino/v1';

	/**
	 * Register REST routes on rest_api_init.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/bot/link',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle_link' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/bot/status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'handle_status' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * POST /wp-json/backupino/v1/bot/link
	 *
	 * Links Telegram/Bale chat to a customer account via one-time /link code.
	 */
	public static function handle_link( \WP_REST_Request $request ): \WP_REST_Response {
		$security_error = ApiSecurityMiddleware::verify( $request, ApiRateLimiter::TYPE_MUTATING );
		if ( null !== $security_error ) {
			return $security_error;
		}

		$raw_code = sanitize_text_field( (string) $request->get_param( 'link_code' ) );
		$code     = strtoupper( trim( str_replace( array( '/link', ' ' ), '', $raw_code ) ) );

		if ( empty( $code ) ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'INVALID_CODE', __( 'One-time link code is required.', 'backupino-portal' ), array( 'status' => 400 ) )
			);
		}

		$transient_key = 'bk_bot_link_' . $code;
		$user_id       = (int) get_transient( $transient_key );

		if ( $user_id <= 0 ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'CODE_EXPIRED', __( 'Link code has expired or is invalid. Please generate a new code in your dashboard.', 'backupino-portal' ), array( 'status' => 404 ) )
			);
		}

		// Verify user has an active Pro license
		$licenses = LicenseRepository::find_by_user_id( $user_id );
		$has_pro  = false;
		foreach ( $licenses as $lic ) {
			if ( 'active' === $lic->status && in_array( $lic->plan_slug, array( 'personal', 'freelancer', 'agency', 'enterprise' ), true ) ) {
				$has_pro = true;
				break;
			}
		}

		if ( ! $has_pro ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'PRO_REQUIRED', __( 'Central bot is available exclusively for active Pro license holders.', 'backupino-portal' ), array( 'status' => 403 ) )
			);
		}

		$messenger = sanitize_key( (string) $request->get_param( 'messenger' ) );
		if ( ! in_array( $messenger, array( 'telegram', 'bale' ), true ) ) {
			$messenger = 'telegram';
		}

		$chat_id  = sanitize_text_field( (string) $request->get_param( 'chat_id' ) );
		$username = sanitize_text_field( (string) $request->get_param( 'chat_username' ) );

		// Update user settings
		$settings                        = CustomerDashboardService::get_customer_settings( $user_id );
		$settings['central_bot_enabled'] = true;
		$settings['bot_messenger']       = $messenger;
		$settings['bot_chat_id']         = $chat_id;
		$settings['bot_username']        = $username;
		$settings['bot_linked_at']       = current_time( 'mysql', true );

		CustomerDashboardService::save_customer_settings( $user_id, $settings );

		// Invalidate single-use code
		delete_transient( $transient_key );

		AuditLogger::log(
			'bot_linked',
			$user_id,
			0,
			null,
			array(
				'messenger' => $messenger,
				'chat_id'   => substr( $chat_id, 0, 4 ) . '****',
			)
		);

		return ApiSecurityMiddleware::success_response(
			array(
				'linked'           => true,
				'user_id'          => $user_id,
				'messenger'        => $messenger,
				'preferred_locale' => $settings['preferred_locale'] ?? 'fa',
			),
			200
		);
	}

	/**
	 * GET /wp-json/backupino/v1/bot/status
	 */
	public static function handle_status( \WP_REST_Request $request ): \WP_REST_Response {
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
		if ( ! $activation ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'ACTIVATION_NOT_FOUND', __( 'Activation not found.', 'backupino-portal' ), array( 'status' => 404 ) )
			);
		}

		$license = LicenseRepository::find_by_id( $activation->license_id );
		if ( ! $license ) {
			return ApiSecurityMiddleware::error_response(
				new \WP_Error( 'LICENSE_NOT_FOUND', __( 'License not found.', 'backupino-portal' ), array( 'status' => 404 ) )
			);
		}

		$settings = CustomerDashboardService::get_customer_settings( (int) $license->user_id );
		$is_pro   = in_array( $license->plan_slug, array( 'personal', 'freelancer', 'agency', 'enterprise' ), true );
		$enabled  = $is_pro && ! empty( $settings['central_bot_enabled'] ) && ! empty( $settings['bot_chat_id'] );

		return ApiSecurityMiddleware::success_response(
			array(
				'central_bot_enabled' => $enabled,
				'messenger'           => $settings['bot_messenger'] ?? 'telegram',
				'preferred_locale'    => $settings['preferred_locale'] ?? 'fa',
				'notify_on_backup'    => ! empty( $settings['notify_on_backup'] ),
				'notify_on_expire'    => ! empty( $settings['notify_on_expire'] ),
				'notify_on_error'     => ! empty( $settings['notify_on_error'] ),
			),
			200
		);
	}
}
