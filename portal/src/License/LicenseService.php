<?php
/**
 * License Lifecycle and Activation Management Service.
 *
 * Implements S3-T04, D-004, D-005, D-006, D-031, D-032, D-051.
 *
 * @package Backupino\Portal\License
 */

declare( strict_types = 1 );

namespace Backupino\Portal\License;

use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\Repository\ActivationRepository;
use Backupino\Portal\Support\DomainHelper;
use Backupino\Portal\Audit\AuditLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Handles activation, validation, deactivation, and heartbeat.
 */
final class LicenseService {

	public const COOLING_OFF_SECONDS = 259200; // 72 hours (D-006)
	public const OFFLINE_TTL_SECONDS = 604800; // 7 days (D-032)

	/**
	 * Activate a license for a given domain and instance.
	 *
	 * @param string               $license_key License key.
	 * @param string               $domain      Site domain or URL.
	 * @param string               $instance_id Client unique instance UUID.
	 * @param array<string, mixed> $client_meta Telemetry (php_version, wp_version, etc.).
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function activate( string $license_key, string $domain, string $instance_id, array $client_meta = array() ) {
		$license = LicenseRepository::find_by_key( $license_key );
		if ( ! $license ) {
			AuditLogger::log( 'activation_failed_not_found', 0, 0, null, array( 'key' => substr( $license_key, 0, 8 ) . '...' ) );
			return new \WP_Error( 'UNAUTHORIZED', __( 'Invalid license key.', 'backupino-portal' ), array( 'status' => 401 ) );
		}

		if ( 'revoked' === $license->status || 'downgraded' === $license->status ) {
			AuditLogger::log( 'activation_failed_status', $license->user_id, $license->id, null, array( 'status' => $license->status ) );
			return new \WP_Error( 'LICENSE_SUSPENDED', __( 'License has been revoked or suspended.', 'backupino-portal' ), array( 'status' => 403 ) );
		}

		if ( $license->is_expired() && ! $license->is_in_grace_period() ) {
			AuditLogger::log( 'activation_failed_expired', $license->user_id, $license->id, null, array( 'expires_at' => $license->expires_at ) );
			return new \WP_Error( 'LICENSE_EXPIRED', __( 'License has expired.', 'backupino-portal' ), array( 'status' => 403 ) );
		}

		$normalized_domain = DomainHelper::normalize( $domain );
		if ( empty( $normalized_domain ) ) {
			return new \WP_Error( 'INVALID_DOMAIN', __( 'Invalid domain name.', 'backupino-portal' ), array( 'status' => 400 ) );
		}

		$domain_hash = DomainHelper::hash( $normalized_domain );
		$is_staging  = DomainHelper::is_staging( $normalized_domain );

		// Check if already active for this license
		$existing = ActivationRepository::find_by_license_and_hash( $license->id, $domain_hash );
		if ( $existing && $existing->is_active ) {
			// Already active: touch heartbeat and re-issue blob
			ActivationRepository::touch_heartbeat( $existing->id );
			return self::prepare_activation_response( $license, $existing, $domain_hash );
		}

		// Staging checks vs Production checks
		if ( $is_staging ) {
			$active_staging_count = ActivationRepository::count_active_staging( $license->id );
			$soft_cap             = DomainHelper::staging_soft_cap( $license->plan_slug );

			if ( $active_staging_count >= $soft_cap ) {
				return new \WP_Error(
					'STAGING_LIMIT_REACHED',
					sprintf(
						/* translators: 1: plan name, 2: max staging count */
						__( 'Staging activation limit (%2$d) reached for plan %1$s.', 'backupino-portal' ),
						$license->plan_slug,
						$soft_cap
					),
					array( 'status' => 409 )
				);
			}
		} else {
			// Production domain: check capacity
			if ( ! $license->can_activate_more() ) {
				AuditLogger::log(
					'activation_blocked_limit',
					$license->user_id,
					$license->id,
					$domain_hash,
					array(
						'active_count' => $license->active_domains_count,
						'max_domains'  => $license->max_domains,
					)
				);

				return new \WP_Error(
					'DOMAIN_LIMIT_REACHED',
					__( 'Domain activation limit reached for this license.', 'backupino-portal' ),
					array(
						'status'  => 409,
						'details' => array(
							'active_count' => $license->active_domains_count,
							'max_domains'  => $license->max_domains,
						),
					)
				);
			}

			// Cooling-off check (72 hours per D-006)
			$cooling_key = 'bk_cooling_' . $license->id;
			$last_change = (int) get_transient( $cooling_key );
			if ( $last_change > 0 && ( time() - $last_change ) < self::COOLING_OFF_SECONDS ) {
				$remaining = self::COOLING_OFF_SECONDS - ( time() - $last_change );
				return new \WP_Error(
					'COOLING_OFF_ACTIVE',
					sprintf(
						/* translators: %d: hours remaining */
						__( 'Production domain switch is in cooling-off period. Please wait %d hours.', 'backupino-portal' ),
						ceil( $remaining / 3600 )
					),
					array(
						'status'  => 429,
						'details' => array( 'remaining_seconds' => $remaining ),
					)
				);
			}
		}

		// Insert or Reactivate activation record
		if ( $existing ) {
			ActivationRepository::reactivate( $existing->id, $instance_id );
			$activation_id = $existing->id;
		} else {
			$activation_id = ActivationRepository::create(
				array(
					'license_id'  => $license->id,
					'domain_hash' => $domain_hash,
					'domain_name' => $normalized_domain,
					'is_staging'  => $is_staging ? 1 : 0,
					'instance_id' => $instance_id,
				)
			);
		}

		if ( ! $activation_id ) {
			return new \WP_Error( 'DATABASE_ERROR', __( 'Could not register activation.', 'backupino-portal' ), array( 'status' => 500 ) );
		}

		// Increment production domain count if not staging (D-051)
		if ( ! $is_staging ) {
			LicenseRepository::increment_domains_count( $license->id );
			set_transient( 'bk_cooling_' . $license->id, time(), self::COOLING_OFF_SECONDS );
		}

		$fresh_license    = LicenseRepository::find_by_id( $license->id );
		$fresh_activation = ActivationRepository::find_by_license_and_hash( $license->id, $domain_hash );

		AuditLogger::log(
			'license_activated',
			$license->user_id,
			$license->id,
			$domain_hash,
			array(
				'is_staging'  => $is_staging,
				'domain_name' => $normalized_domain,
				'instance_id' => $instance_id,
			)
		);

		return self::prepare_activation_response( $fresh_license ?: $license, $fresh_activation, $domain_hash );
	}

	/**
	 * Validate existing license activation (periodic verification / D-032).
	 *
	 * @param string      $activation_token Encoded activation identifier (act_...).
	 * @param string|null $cached_sig_hash  Optional client cached signature hash.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function validate( string $activation_token, ?string $cached_sig_hash = null ) {
		$activation_id = self::parse_activation_id( $activation_token );
		if ( $activation_id <= 0 ) {
			return new \WP_Error( 'INVALID_ACTIVATION', __( 'Invalid activation identifier.', 'backupino-portal' ), array( 'status' => 400 ) );
		}

		global $wpdb;
		$table = ActivationRepository::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `id` = %d LIMIT 1", $activation_id ), ARRAY_A );

		if ( ! $row ) {
			return new \WP_Error( 'ACTIVATION_NOT_FOUND', __( 'Activation not found.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$activation = new \Backupino\Portal\Models\Activation( $row );
		if ( ! $activation->is_active ) {
			return new \WP_Error( 'ACTIVATION_DEACTIVATED', __( 'Activation has been deactivated.', 'backupino-portal' ), array( 'status' => 403 ) );
		}

		$license = LicenseRepository::find_by_id( $activation->license_id );
		if ( ! $license || 'revoked' === $license->status ) {
			return new \WP_Error( 'LICENSE_REVOKED', __( 'Associated license is not active.', 'backupino-portal' ), array( 'status' => 403 ) );
		}

		ActivationRepository::touch_heartbeat( $activation->id );

		$features = self::get_plan_features( $license->plan_slug );
		$now      = time();
		$expiry   = $license->expires_at ? strtotime( $license->expires_at ) : ( $now + ( 365 * 86400 ) );

		$signed_blob = LicenseSigner::sign_license(
			array(
				'activation_id'       => self::format_activation_token( $activation->id ),
				'plan'                => $license->plan_slug,
				'domains'             => array( $activation->domain_hash ),
				'max_domains'         => $license->max_domains,
				'cloud_storage_bytes' => $license->cloud_quota_bytes,
				'retention_versions'  => $license->cloud_max_versions_per_domain,
				'features'            => $features,
				'issued_at'           => $now,
				'expiry'              => $expiry,
				'grace_period_days'   => 7,
			)
		);

		AuditLogger::log(
			'license_validated',
			$license->user_id,
			$license->id,
			$activation->domain_hash,
			array( 'is_staging' => $activation->is_staging )
		);

		return array(
			'status'              => $license->status,
			'plan'                => $license->plan_slug,
			'expires_at'          => $expiry,
			'offline_ttl_seconds' => self::OFFLINE_TTL_SECONDS,
			'signed_license_blob' => $signed_blob,
		);
	}

	/**
	 * Deactivate a domain activation.
	 *
	 * @param string $activation_token Encoded activation identifier.
	 * @param string $reason           Optional client-supplied reason.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function deactivate( string $activation_token, string $reason = '' ) {
		$activation_id = self::parse_activation_id( $activation_token );
		if ( $activation_id <= 0 ) {
			return new \WP_Error( 'INVALID_ACTIVATION', __( 'Invalid activation identifier.', 'backupino-portal' ), array( 'status' => 400 ) );
		}

		global $wpdb;
		$table = ActivationRepository::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `id` = %d LIMIT 1", $activation_id ), ARRAY_A );

		if ( ! $row ) {
			return new \WP_Error( 'ACTIVATION_NOT_FOUND', __( 'Activation not found.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$activation = new \Backupino\Portal\Models\Activation( $row );
		if ( ! $activation->is_active ) {
			return array(
				'deactivated'                => true,
				'already_inactive'           => true,
				'cooling_off_period_seconds' => 0,
			);
		}

		ActivationRepository::deactivate( $activation->id );

		$license = LicenseRepository::find_by_id( $activation->license_id );
		if ( $license && ! $activation->is_staging ) {
			LicenseRepository::decrement_domains_count( $license->id );
		}

		AuditLogger::log(
			'license_deactivated',
			$license ? $license->user_id : 0,
			$activation->license_id,
			$activation->domain_hash,
			array( 'reason' => sanitize_text_field( $reason ) )
		);

		$fresh_license = $license ? LicenseRepository::find_by_id( $license->id ) : null;

		return array(
			'deactivated'                => true,
			'domains_used'               => $fresh_license ? $fresh_license->active_domains_count : 0,
			'domains_limit'              => $fresh_license ? $fresh_license->max_domains : 1,
			'cooling_off_period_seconds' => self::COOLING_OFF_SECONDS,
		);
	}

	/**
	 * Process heartbeat telemetry (D-032).
	 *
	 * @param string               $activation_token Encoded activation ID.
	 * @param array<string, mixed> $telemetry        Optional telemetry data.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function heartbeat( string $activation_token, array $telemetry = array() ) {
		$activation_id = self::parse_activation_id( $activation_token );
		if ( $activation_id <= 0 ) {
			return new \WP_Error( 'INVALID_ACTIVATION', __( 'Invalid activation identifier.', 'backupino-portal' ), array( 'status' => 400 ) );
		}

		ActivationRepository::touch_heartbeat( $activation_id );

		return array(
			'acknowledged'                 => true,
			'pending_commands_count'       => 0, // S3-T11 signed command queue
			'next_heartbeat_delay_seconds' => 86400,
			'notice'                       => null,
		);
	}

	/**
	 * Prepare unified activation response.
	 */
	private static function prepare_activation_response( $license, $activation, string $domain_hash ): array {
		$token_str = self::format_activation_token( $activation->id );
		$features  = self::get_plan_features( $license->plan_slug );
		$now       = time();
		$expiry    = $license->expires_at ? strtotime( $license->expires_at ) : ( $now + ( 365 * 86400 ) );

		$signed_blob = LicenseSigner::sign_license(
			array(
				'activation_id'       => $token_str,
				'plan'                => $license->plan_slug,
				'domains'             => array( $domain_hash ),
				'max_domains'         => $license->max_domains,
				'cloud_storage_bytes' => $license->cloud_quota_bytes,
				'retention_versions'  => $license->cloud_max_versions_per_domain,
				'features'            => $features,
				'issued_at'           => $now,
				'expiry'              => $expiry,
				'grace_period_days'   => 7,
			)
		);

		return array(
			'activation_id'       => $token_str,
			'plan'                => $license->plan_slug,
			'domains_used'        => $license->active_domains_count,
			'domains_limit'       => $license->max_domains,
			'is_staging'          => (bool) $activation->is_staging,
			'issued_at'           => $now,
			'expires_at'          => $expiry,
			'signed_license_blob' => $signed_blob,
			'cloud_quota_bytes'   => $license->cloud_quota_bytes,
			'features'            => $features,
		);
	}

	/**
	 * Format internal numeric ID into API string token (act_...).
	 */
	public static function format_activation_token( int $id ): string {
		return sprintf( 'act_%08x', $id );
	}

	/**
	 * Parse string token (act_...) back into numeric ID.
	 */
	public static function parse_activation_id( string $token ): int {
		$token = trim( $token );
		if ( 0 === strpos( $token, 'act_' ) ) {
			return (int) hexdec( substr( $token, 4 ) );
		}
		return is_numeric( $token ) ? (int) $token : 0;
	}

	/**
	 * Get plan features list.
	 *
	 * @return string[]
	 */
	public static function get_plan_features( string $plan_slug ): array {
		$base = array( 'restore_in_place', 'cloud_s3', 'cloud_ftp', 'incremental' );

		switch ( $plan_slug ) {
			case 'agency':
			case 'enterprise':
				return array_merge( $base, array( 'multisite', 'whitelabel', 'rescue_agent' ) );
			case 'freelancer':
				return array_merge( $base, array( 'multisite', 'rescue_agent' ) );
			case 'trial':
			case 'personal':
			default:
				return $base;
		}
	}
}
