<?php
/**
 * Signed Command Service for 2FA-Guarded Restore and Remote Actions (D-042).
 *
 * Implements S3-T11, Two-Phase Shadow Restore, and Owner-Only Execution.
 *
 * @package Backupino\Portal\Commands
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Commands;

use Backupino\Portal\Api\StorageController;
use Backupino\Portal\Audit\AuditLogger;
use Backupino\Portal\Auth\AuthorizationGuard;
use Backupino\Portal\Auth\TwoFactorAuth;
use Backupino\Portal\Repository\ActivationRepository;
use Backupino\Portal\Repository\BackupRepository;
use Backupino\Portal\Repository\CommandQueueRepository;
use Backupino\Portal\Repository\LicenseRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates cryptographic signing and queueing of restore commands.
 */
final class SignedCommandService {

	/**
	 * Prepare a Two-Phase Shadow Restore command (Step 1).
	 *
	 * Requires 2FA verification from the license owner.
	 *
	 * @param int    $user_id    Customer account ID.
	 * @param int    $license_id License ID.
	 * @param int    $backup_id  Backup package ID.
	 * @param string $totp_code  6-digit TOTP code.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function prepare_shadow_restore( int $user_id, int $license_id, int $backup_id, string $totp_code ) {
		// 1. Strict Owner Authentication (D-042 Invariant: Owner Only)
		$auth = AuthorizationGuard::can_restore( $user_id, $license_id );
		if ( is_wp_error( $auth ) ) {
			return $auth;
		}

		// 2. Enforce 2FA Activation & Code Validation
		if ( ! TwoFactorAuth::is_2fa_enabled( $user_id ) ) {
			return new \WP_Error(
				'2FA_REQUIRED',
				__( 'فعال‌سازی احراز هویت دو مرحله‌ای (2FA) برای ارسال دستورات بازگردانی الزامی است.', 'backupino-portal' ),
				array( 'status' => 403 )
			);
		}

		if ( ! TwoFactorAuth::verify_totp( $user_id, $totp_code ) ) {
			return new \WP_Error(
				'INVALID_2FA_CODE',
				__( 'کد ۶ رقمی احراز هویت دومرحله‌ای نامعتبر یا منقضی شده است.', 'backupino-portal' ),
				array( 'status' => 403 )
			);
		}

		// 3. Resolve Backup Package
		$backup = BackupRepository::find_by_id( $backup_id );
		if ( ! $backup || (int) $backup->license_id !== $license_id ) {
			return new \WP_Error(
				'BACKUP_NOT_FOUND',
				__( 'بسته بکاپ در سیستم یافت نشد.', 'backupino-portal' ),
				array( 'status' => 404 )
			);
		}

		// 4. Resolve Activation
		$activation = ActivationRepository::find_by_id( (int) $backup->activation_id );
		if ( ! $activation || ! $activation->is_active ) {
			// Fallback: find any active activation for this license & domain_hash
			$activations = ActivationRepository::find_by_license_id( $license_id );
			foreach ( $activations as $act ) {
				if ( $act->is_active && $act->domain_hash === $backup->domain_hash ) {
					$activation = $act;
					break;
				}
			}
		}

		if ( ! $activation ) {
			return new \WP_Error(
				'ACTIVATION_NOT_FOUND',
				__( 'دامنه متصل فعال برای اجرای دستور بازگردانی یافت نشد.', 'backupino-portal' ),
				array( 'status' => 404 )
			);
		}

		// 5. Generate Signed Download URL (2 hours TTL)
		$download_url = StorageController::generate_download_url( $backup->backup_hash, (int) $activation->id, 7200 );

		// 6. Build Signed Payload for Rescue Agent / Pro Plugin
		$command_id = 'cmd_rst_' . bin2hex( random_bytes( 8 ) );
		$payload    = array(
			'command_id'              => $command_id,
			'action'                  => 'PREPARE_SHADOW',
			'instance_id'             => $activation->instance_id,
			'domain_hash'             => $activation->domain_hash,
			'backup_hash'             => $backup->backup_hash,
			'package_name'            => $backup->package_name,
			'source_package_url'      => $download_url,
			'snapshot_expiry_seconds' => 172800, // 48 hours emergency snapshot retention
			'storage_provider'        => $backup->storage_provider,
		);

		$now     = current_time( 'mysql', true );
		$expires = gmdate( 'Y-m-d H:i:s', time() + 600 ); // 10 minutes pull TTL

		$inserted = CommandQueueRepository::create(
			array(
				'command_id'    => $command_id,
				'license_id'    => $license_id,
				'activation_id' => $activation->id,
				'action'        => 'PREPARE_SHADOW',
				'payload'       => $payload,
				'nonce'         => bin2hex( random_bytes( 16 ) ),
				'status'        => 'pending',
				'issued_at'     => $now,
				'expires_at'    => $expires,
				'created_at'    => $now,
			)
		);

		if ( ! $inserted ) {
			return new \WP_Error(
				'QUEUE_ERROR',
				__( 'خطا در ثبت دستور در صف ارتباطی پرتال.', 'backupino-portal' ),
				array( 'status' => 500 )
			);
		}

		AuditLogger::log(
			'restore_shadow_queued',
			$user_id,
			$license_id,
			$activation->domain_hash,
			array(
				'command_id'   => $command_id,
				'backup_id'    => $backup_id,
				'package_name' => $backup->package_name,
			)
		);

		return array(
			'success'      => true,
			'command_id'   => $command_id,
			'status'       => 'pending',
			'action'       => 'PREPARE_SHADOW',
			'package_name' => $backup->package_name,
			'message'      => __( 'دستور آماده‌سازی سایه صادر شد و در انتظار واکشی سایت کلاینت است.', 'backupino-portal' ),
		);
	}

	/**
	 * Commit Two-Phase Restore (Step 2 - Atomic Swap).
	 *
	 * @param int    $user_id    Customer account ID.
	 * @param string $command_id Original PREPARE_SHADOW command ID.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function commit_restore( int $user_id, string $command_id ) {
		$original = CommandQueueRepository::find_by_command_id( $command_id );
		if ( ! $original ) {
			return new \WP_Error( 'COMMAND_NOT_FOUND', __( 'دستور بازگردانی اولیه یافت نشد.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$license_id = (int) $original['license_id'];
		$auth       = AuthorizationGuard::can_restore( $user_id, $license_id );
		if ( is_wp_error( $auth ) ) {
			return $auth;
		}

		$commit_cmd_id = 'cmd_cmt_' . bin2hex( random_bytes( 8 ) );
		$orig_payload  = json_decode( (string) $original['payload'], true );

		$commit_payload = array(
			'command_id'        => $commit_cmd_id,
			'action'            => 'COMMIT_RESTORE',
			'parent_command_id' => $command_id,
			'instance_id'       => $orig_payload['instance_id'] ?? '',
			'domain_hash'       => $orig_payload['domain_hash'] ?? '',
			'backup_hash'       => $orig_payload['backup_hash'] ?? '',
		);

		$now     = current_time( 'mysql', true );
		$expires = gmdate( 'Y-m-d H:i:s', time() + 600 );

		$inserted = CommandQueueRepository::create(
			array(
				'command_id'    => $commit_cmd_id,
				'license_id'    => $license_id,
				'activation_id' => (int) $original['activation_id'],
				'action'        => 'COMMIT_RESTORE',
				'payload'       => $commit_payload,
				'nonce'         => bin2hex( random_bytes( 16 ) ),
				'status'        => 'pending',
				'issued_at'     => $now,
				'expires_at'    => $expires,
				'created_at'    => $now,
			)
		);

		if ( ! $inserted ) {
			return new \WP_Error( 'QUEUE_ERROR', __( 'خطا در ثبت دستور اعمال اتمیک در صف.', 'backupino-portal' ), array( 'status' => 500 ) );
		}

		AuditLogger::log(
			'restore_commit_queued',
			$user_id,
			$license_id,
			$orig_payload['domain_hash'] ?? null,
			array(
				'commit_command_id' => $commit_cmd_id,
				'parent_command_id' => $command_id,
			)
		);

		do_action( 'backupino_restore_event', $user_id, $orig_payload['domain_hash'] ?? 'connected-site', 'committed' );

		return array(
			'success'    => true,
			'command_id' => $commit_cmd_id,
			'status'     => 'pending',
			'action'     => 'COMMIT_RESTORE',
			'message'    => __( 'دستور تایید نهایی و تعویض اتمیک صادر شد.', 'backupino-portal' ),
		);
	}

	/**
	 * Abort Two-Phase Restore (Cancel and clean up shadow).
	 *
	 * @param int    $user_id    Customer account ID.
	 * @param string $command_id Original PREPARE_SHADOW command ID.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function abort_restore( int $user_id, string $command_id ) {
		$original = CommandQueueRepository::find_by_command_id( $command_id );
		if ( ! $original ) {
			return new \WP_Error( 'COMMAND_NOT_FOUND', __( 'دستور بازگردانی اولیه یافت نشد.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$license_id = (int) $original['license_id'];
		$auth       = AuthorizationGuard::can_restore( $user_id, $license_id );
		if ( is_wp_error( $auth ) ) {
			return $auth;
		}

		$abort_cmd_id = 'cmd_abt_' . bin2hex( random_bytes( 8 ) );
		$orig_payload = json_decode( (string) $original['payload'], true );

		$abort_payload = array(
			'command_id'        => $abort_cmd_id,
			'action'            => 'ABORT_RESTORE',
			'parent_command_id' => $command_id,
			'instance_id'       => $orig_payload['instance_id'] ?? '',
			'domain_hash'       => $orig_payload['domain_hash'] ?? '',
			'backup_hash'       => $orig_payload['backup_hash'] ?? '',
		);

		$now     = current_time( 'mysql', true );
		$expires = gmdate( 'Y-m-d H:i:s', time() + 600 );

		$inserted = CommandQueueRepository::create(
			array(
				'command_id'    => $abort_cmd_id,
				'license_id'    => $license_id,
				'activation_id' => (int) $original['activation_id'],
				'action'        => 'ABORT_RESTORE',
				'payload'       => $abort_payload,
				'nonce'         => bin2hex( random_bytes( 16 ) ),
				'status'        => 'pending',
				'issued_at'     => $now,
				'expires_at'    => $expires,
				'created_at'    => $now,
			)
		);

		if ( ! $inserted ) {
			return new \WP_Error( 'QUEUE_ERROR', __( 'خطا در ثبت دستور لغو در صف.', 'backupino-portal' ), array( 'status' => 500 ) );
		}

		// Mark original command aborted
		CommandQueueRepository::mark_status( $command_id, 'aborted' );

		AuditLogger::log(
			'restore_aborted',
			$user_id,
			$license_id,
			$orig_payload['domain_hash'] ?? null,
			array(
				'abort_command_id'  => $abort_cmd_id,
				'parent_command_id' => $command_id,
			)
		);

		do_action( 'backupino_restore_event', $user_id, $orig_payload['domain_hash'] ?? 'connected-site', 'aborted' );

		return array(
			'success'    => true,
			'command_id' => $abort_cmd_id,
			'status'     => 'aborted',
			'action'     => 'ABORT_RESTORE',
			'message'    => __( 'عملیات بازگردانی لغو و دستور پاکسازی فایل‌های سایه صادر شد.', 'backupino-portal' ),
		);
	}

	/**
	 * Get detailed live status and health preview report for a restore command.
	 *
	 * @param int    $user_id    Customer account ID.
	 * @param string $command_id Command ID.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function get_command_status( int $user_id, string $command_id ) {
		$cmd = CommandQueueRepository::find_by_command_id( $command_id );
		if ( ! $cmd ) {
			return new \WP_Error( 'COMMAND_NOT_FOUND', __( 'دستور یافت نشد.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$license_id = (int) $cmd['license_id'];
		$license    = LicenseRepository::find_by_id( $license_id );
		if ( ! $license || (int) $license->user_id !== $user_id ) {
			return new \WP_Error( 'FORBIDDEN', __( 'دسترسی غیرمجاز.', 'backupino-portal' ), array( 'status' => 403 ) );
		}

		$result_data = array();
		if ( ! empty( $cmd['result_payload'] ) ) {
			$decoded = json_decode( (string) $cmd['result_payload'], true );
			if ( is_array( $decoded ) ) {
				$result_data = $decoded;
			}
		}

		$is_shadow_ready = (
			'completed' === $cmd['status'] &&
			'PREPARE_SHADOW' === $cmd['action']
		) || (
			isset( $result_data['shadow_status'] ) &&
			'ready' === $result_data['shadow_status']
		);

		return array(
			'command_id'      => $cmd['command_id'],
			'action'          => $cmd['action'],
			'status'          => $cmd['status'],
			'issued_at'       => $cmd['issued_at'],
			'expires_at'      => $cmd['expires_at'],
			'completed_at'    => $cmd['completed_at'],
			'is_shadow_ready' => $is_shadow_ready,
			'preview_url'     => $result_data['preview_url'] ?? null,
			'preview_token'   => $result_data['preview_token'] ?? null,
			'tables_count'    => $result_data['tables_count'] ?? ( $result_data['tables_verified'] ?? null ),
			'checksum_valid'  => $result_data['checksum_valid'] ?? true,
			'health_checks'   => $result_data['health_checks'] ?? array(),
			'execution_log'   => $result_data['execution_log'] ?? null,
		);
	}
}
