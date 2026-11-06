<?php
/**
 * License Lifecycle State Machine and Daily Cron Evaluator.
 *
 * Implements S3-T07: trial -> active -> T-3d expiring_soon -> grace_period -> downgraded.
 *
 * @package Backupino\Portal\Lifecycle
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Lifecycle;

use Backupino\Portal\Models\License;
use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\Audit\AuditLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Evaluates license expirations, transitions states, and schedules lifecycle checks.
 */
final class LicenseLifecycleManager {

	public const CRON_HOOK           = 'backupino_daily_license_check';
	public const EXPIRING_SOON_DAYS  = 3; // T - 3d warning
	public const DEFAULT_GRACE_DAYS  = 7; // 7 days grace period (D-032)

	/**
	 * Initialize lifecycle hooks and cron.
	 */
	public static function init(): void {
		add_action( self::CRON_HOOK, array( self::class, 'process_daily_lifecycle' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 3600, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Determine the current deterministic state of a license.
	 *
	 * @param License  $license      License instance.
	 * @param int|null $current_time Optional timestamp (default: current time).
	 * @return string Target status ('active', 'expiring_soon', 'grace_period', 'downgraded', 'revoked').
	 */
	public static function evaluate_license_state( License $license, ?int $current_time = null ): string {
		if ( 'revoked' === $license->status ) {
			return 'revoked';
		}

		if ( empty( $license->expires_at ) ) {
			return 'active'; // Perpetual / lifetime license
		}

		$now    = null !== $current_time ? $current_time : time();
		$expiry = strtotime( $license->expires_at );

		// 1. Not yet expired
		if ( $now < $expiry ) {
			$seconds_to_expire = $expiry - $now;
			if ( $seconds_to_expire <= ( self::EXPIRING_SOON_DAYS * 86400 ) ) {
				return 'expiring_soon';
			}
			return 'active';
		}

		// 2. Expiration date passed: check grace period (7 days)
		$grace_expiry = ! empty( $license->grace_expires_at )
			? strtotime( $license->grace_expires_at )
			: ( $expiry + ( self::DEFAULT_GRACE_DAYS * 86400 ) );

		if ( $now < $grace_expiry ) {
			return 'grace_period';
		}

		// 3. Grace period ended: downgraded to read-only (D-015)
		return 'downgraded';
	}

	/**
	 * Run daily lifecycle evaluation on all registered licenses.
	 *
	 * @param int|null $current_time Optional timestamp for testing.
	 * @return array{evaluated: int, transitioned: int, notifications_sent: int}
	 */
	public static function process_daily_lifecycle( ?int $current_time = null ): array {
		global $wpdb;

		$table = LicenseRepository::table();
		$rows  = $wpdb->get_results(
			"SELECT * FROM `{$table}` WHERE `status` != 'revoked'",
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array(
				'evaluated'          => 0,
				'transitioned'       => 0,
				'notifications_sent' => 0,
			);
		}

		$evaluated          = 0;
		$transitioned       = 0;
		$notifications_sent = 0;

		foreach ( $rows as $row ) {
			$license      = new License( $row );
			$current_st   = $license->status;
			$target_st    = self::evaluate_license_state( $license, $current_time );
			$evaluated++;

			if ( $current_st !== $target_st ) {
				LicenseRepository::update_status( $license->id, $target_st );
				$transitioned++;

				AuditLogger::log(
					'license_status_transitioned',
					$license->user_id,
					$license->id,
					null,
					array(
						'from_status' => $current_st,
						'to_status'   => $target_st,
						'expires_at'  => $license->expires_at,
					)
				);

				// Fire action for email / notification dispatcher
				do_action( 'backupino_license_status_changed', $license, $current_st, $target_st );
				$notifications_sent++;
			}
		}

		return array(
			'evaluated'          => $evaluated,
			'transitioned'       => $transitioned,
			'notifications_sent' => $notifications_sent,
		);
	}
}
