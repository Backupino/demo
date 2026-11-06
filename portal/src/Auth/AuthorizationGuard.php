<?php
/**
 * Authorization Guard and Permission Boundaries.
 *
 * Implements strict ownership rules from S3-T03 and D-042.
 *
 * @package Backupino\Portal\Auth
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Auth;

use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\Auth\TwoFactorAuth;
use Backupino\Portal\Audit\AuditLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Enforces account ownership and operation boundaries.
 */
final class AuthorizationGuard {

	/**
	 * Verify if a user is the owner of a specific license.
	 *
	 * @param int $user_id    User ID.
	 * @param int $license_id License ID.
	 * @return bool True if user is the direct owner.
	 */
	public static function is_license_owner( int $user_id, int $license_id ): bool {
		if ( $user_id <= 0 || $license_id <= 0 ) {
			return false;
		}

		$license = LicenseRepository::find_by_id( $license_id );
		if ( ! $license ) {
			return false;
		}

		return $license->user_id === $user_id;
	}

	/**
	 * Check if a user can view or manage a license.
	 *
	 * Owners can manage their license.
	 * Administrators and Support can view for support purposes.
	 *
	 * @param int $user_id    User ID.
	 * @param int $license_id License ID.
	 * @return bool
	 */
	public static function can_manage_license( int $user_id, int $license_id ): bool {
		if ( self::is_license_owner( $user_id, $license_id ) ) {
			return true;
		}

		if ( user_can( $user_id, 'administrator' ) || user_can( $user_id, RoleManager::CAP_SUPPORT_VIEW ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a user is permitted to trigger a manual panel backup (D-042).
	 *
	 * STRICT RULE: Only the account owner who owns the license can trigger a backup!
	 * Administrators and Support specialists are explicitly forbidden from initiating
	 * backups on customer sites without direct customer ownership.
	 *
	 * @param int $user_id    User ID.
	 * @param int $license_id License ID.
	 * @return bool
	 */
	public static function can_trigger_backup( int $user_id, int $license_id ): bool {
		if ( ! self::is_license_owner( $user_id, $license_id ) ) {
			AuditLogger::log(
				'unauthorized_backup_attempt_blocked',
				$user_id,
				$license_id,
				null,
				array( 'reason' => 'non_owner_attempt' )
			);
			return false;
		}

		$license = LicenseRepository::find_by_id( $license_id );
		return $license && $license->is_active();
	}

	/**
	 * Check if a user is permitted to trigger a signed restore command (D-042 / D-043 / S3-T11).
	 *
	 * CRITICAL SECURITY INVARIANT:
	 * 1. Must be the exact account owner of the license.
	 * 2. Support, operators, and administrators CANNOT trigger a restore on behalf of a customer.
	 * 3. Account owner MUST have 2FA enabled and active for restore operations.
	 *
	 * @param int $user_id    User ID.
	 * @param int $license_id License ID.
	 * @return bool
	 */
	public static function can_trigger_restore( int $user_id, int $license_id ): bool {
		// 1. Strict Owner Verification (D-042)
		if ( ! self::is_license_owner( $user_id, $license_id ) ) {
			AuditLogger::log(
				'unauthorized_restore_attempt_blocked',
				$user_id,
				$license_id,
				null,
				array( 'reason' => 'non_owner_attempt' )
			);
			return false;
		}

		// 2. Mandatory 2FA verification requirement for restore operations (S3-T11)
		if ( ! TwoFactorAuth::is_2fa_enabled( $user_id ) ) {
			AuditLogger::log(
				'restore_blocked_2fa_required',
				$user_id,
				$license_id,
				null,
				array( 'reason' => 'owner_2fa_not_active' )
			);
			return false;
		}

		$license = LicenseRepository::find_by_id( $license_id );
		return $license && $license->is_active();
	}

	/**
	 * Check if user can view audit logs.
	 *
	 * @param int      $user_id    User ID.
	 * @param int|null $license_id Specific license ID (optional).
	 * @return bool
	 */
	public static function can_view_audit_logs( int $user_id, ?int $license_id = null ): bool {
		if ( user_can( $user_id, 'administrator' ) || user_can( $user_id, RoleManager::CAP_SUPPORT_VIEW ) ) {
			return true;
		}

		if ( null !== $license_id ) {
			return self::is_license_owner( $user_id, $license_id );
		}

		return false;
	}

	/**
	 * Check if user can override plan limits, quotas, or grant free extensions.
	 *
	 * @param int $user_id User ID.
	 * @return bool Admin only.
	 */
	public static function can_override_plan( int $user_id ): bool {
		return user_can( $user_id, 'administrator' ) || user_can( $user_id, RoleManager::CAP_ADMIN_PORTAL );
	}
}
