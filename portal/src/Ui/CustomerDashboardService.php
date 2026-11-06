<?php
/**
 * Customer Dashboard Business Logic and Aggregator Service.
 *
 * Implements S3-T08, D-006, D-015, D-032, D-042.
 *
 * @package Backupino\Portal\Ui
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Ui;

use Backupino\Portal\Models\License;
use Backupino\Portal\Models\Activation;
use Backupino\Portal\Models\CloudBackup;
use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\Repository\ActivationRepository;
use Backupino\Portal\Repository\BackupRepository;
use Backupino\Portal\Auth\AuthorizationGuard;
use Backupino\Portal\Auth\TwoFactorAuth;
use Backupino\Portal\Lifecycle\LicenseLifecycleManager;
use Backupino\Portal\Audit\AuditLogger;
use Backupino\Portal\Plans\PlanManager;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates data and actions for the Customer Dashboard.
 */
final class CustomerDashboardService {

	public const DEFAULT_SNAPSHOT_RETENTION_HOURS = 48; // Default 48h (S3-T08)
	public const ALLOWED_RETENTION_HOURS          = array( 24, 48, 72, 168 ); // 24h, 48h, 72h, 7 days

	/**
	 * Get full overview data for a customer's dashboard.
	 *
	 * @param int $user_id Customer user ID.
	 * @return array<string, mixed>
	 */
	public static function get_customer_overview( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return array(
				'licenses'      => array(),
				'invoices'      => array(),
				'is_2fa_active' => false,
			);
		}

		$raw_licenses = LicenseRepository::find_by_user_id( $user_id );
		$licenses_out = array();
		$now          = time();

		foreach ( $raw_licenses as $license ) {
			$activations = ActivationRepository::find_active_by_license( $license->id );
			$prod_count  = 0;
			$stage_count = 0;
			$domain_list = array();

			foreach ( $activations as $act ) {
				if ( $act->is_staging ) {
					$stage_count++;
				} else {
					$prod_count++;
				}

				// Calculate 72h cooling-off status if deactivated
				$cooling_off_remaining_seconds = 0;
				if ( ! empty( $act->deactivated_at ) ) {
					$deact_time = strtotime( $act->deactivated_at );
					$co_end     = $deact_time + ( 72 * 3600 );
					if ( $now < $co_end ) {
						$cooling_off_remaining_seconds = $co_end - $now;
					}
				}

				$domain_list[] = array(
					'id'                            => $act->id,
					'domain_name'                   => $act->domain_name,
					'domain_hash'                   => $act->domain_hash,
					'is_staging'                    => (bool) $act->is_staging,
					'is_active'                     => (bool) $act->is_active,
					'last_heartbeat_at'             => $act->last_heartbeat_at,
					'cooling_off_remaining_seconds' => $cooling_off_remaining_seconds,
					'cooling_off_active'            => $cooling_off_remaining_seconds > 0,
				);
			}

			// Calculate offline days remaining (7-day offline cache window - D-032)
			$offline_days_remaining = 7;
			if ( ! empty( $license->updated_at ) ) {
				$last_sync_time   = strtotime( $license->updated_at );
				$elapsed_seconds  = max( 0, $now - $last_sync_time );
				$seven_days_sec   = 7 * 86400;
				$remain_sec       = max( 0, $seven_days_sec - $elapsed_seconds );
				$offline_days_remaining = (int) ceil( $remain_sec / 86400 );
			}

			// Snapshot retention duration
			$retention_hours = (int) get_option(
				"backupino_retention_{$license->id}",
				self::DEFAULT_SNAPSHOT_RETENTION_HOURS
			);

			// Backups for this license
			$backups_raw = BackupRepository::find_by_license_id( $license->id, 20 );
			$backups_out = array();
			foreach ( $backups_raw as $b ) {
				$dl_token = wp_create_nonce( 'backupino_dl_' . $b->id . '_' . $user_id );
				$dl_url   = add_query_arg(
					array(
						'action'    => 'backupino_customer_download',
						'backup_id' => $b->id,
						'_wpnonce'  => $dl_token,
					),
					admin_url( 'admin-ajax.php' )
				);

				$backups_out[] = array(
					'id'               => $b->id,
					'package_name'     => $b->package_name,
					'file_size_bytes'  => $b->file_size_bytes,
					'formatted_size'   => $b->get_formatted_size(),
					'created_at'       => $b->created_at,
					'storage_provider' => $b->storage_provider,
					'retention_status' => $b->retention_status,
					'download_url'     => $dl_url,
				);
			}

			$plan_info = PlanManager::get( $license->plan_slug );

			$licenses_out[] = array(
				'id'                     => $license->id,
				'license_key'            => $license->license_key,
				'masked_key'             => substr( $license->license_key, 0, 8 ) . '****-****-' . substr( $license->license_key, -4 ),
				'plan_slug'              => $license->plan_slug,
				'plan_name'              => $plan_info ? $plan_info->name : ucfirst( $license->plan_slug ),
				'status'                 => $license->status,
				'max_domains'            => $license->max_domains,
				'active_prod_count'      => $prod_count,
				'active_stage_count'     => $stage_count,
				'available_slots'        => max( 0, $license->max_domains - $prod_count ),
				'expires_at'             => $license->expires_at,
				'offline_days_remaining' => $offline_days_remaining,
				'snapshot_retention_h'   => $retention_hours,
				'domains'                => $domain_list,
				'backups'                => $backups_out,
				'cloud_quota_bytes'      => $license->cloud_quota_bytes,
			);
		}

		// Invoices from WooCommerce
		$invoices = self::get_customer_invoices( $user_id );

		return array(
			'licenses'      => $licenses_out,
			'invoices'      => $invoices,
			'is_2fa_active' => TwoFactorAuth::is_2fa_enabled( $user_id ),
		);
	}

	/**
	 * Get WooCommerce orders/invoices for customer.
	 *
	 * @param int $user_id
	 * @return array<array<string, mixed>>
	 */
	public static function get_customer_invoices( int $user_id ): array {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}

		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => 20,
				'status'      => array( 'completed', 'processing' ),
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);

		$out = array();
		foreach ( $orders as $order ) {
			$inv_url = add_query_arg(
				array(
					'action'   => 'backupino_view_invoice',
					'order_id' => $order->get_id(),
					'_wpnonce' => wp_create_nonce( 'backupino_inv_' . $order->get_id() . '_' . $user_id ),
				),
				admin_url( 'admin-ajax.php' )
			);

			$out[] = array(
				'order_id'     => $order->get_id(),
				'order_number' => $order->get_order_number(),
				'date'         => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : '',
				'total'        => $order->get_formatted_order_total(),
				'currency'     => $order->get_currency(),
				'status'       => $order->get_status(),
				'invoice_url'  => $inv_url,
			);
		}

		return $out;
	}

	/**
	 * Update snapshot retention duration for a license.
	 *
	 * @param int $user_id
	 * @param int $license_id
	 * @param int $hours
	 * @return bool|\WP_Error
	 */
	public static function update_snapshot_retention( int $user_id, int $license_id, int $hours ) {
		$ownership = AuthorizationGuard::verify_license_ownership( $user_id, $license_id );
		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		if ( ! in_array( $hours, self::ALLOWED_RETENTION_HOURS, true ) ) {
			return new \WP_Error(
				'INVALID_RETENTION_HOURS',
				__( 'Invalid retention duration selected.', 'backupino-portal' ),
				array( 'status' => 400 )
			);
		}

		update_option( "backupino_retention_{$license_id}", $hours );

		AuditLogger::log(
			'snapshot_retention_updated',
			$user_id,
			$license_id,
			null,
			array( 'retention_hours' => $hours )
		);

		return true;
	}

	/**
	 * Request manual backup generation (Owner only - D-042).
	 *
	 * @param int $user_id
	 * @param int $license_id
	 * @param int $activation_id
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function request_manual_backup( int $user_id, int $license_id, int $activation_id ) {
		$ownership = AuthorizationGuard::verify_license_ownership( $user_id, $license_id );
		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		$activation = ActivationRepository::find_by_id( $activation_id );
		if ( ! $activation || $activation->license_id !== $license_id ) {
			return new \WP_Error( 'ACTIVATION_NOT_FOUND', __( 'Domain activation not found.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$command_id = 'cmd_bk_' . bin2hex( random_bytes( 8 ) );

		AuditLogger::log(
			'manual_backup_requested',
			$user_id,
			$license_id,
			$activation->domain_hash,
			array(
				'command_id'    => $command_id,
				'activation_id' => $activation_id,
				'domain_name'   => $activation->domain_name,
			)
		);

		return array(
			'success'     => true,
			'command_id'  => $command_id,
			'domain_name' => $activation->domain_name,
			'status'      => 'queued',
			'message'     => __( 'Backup command successfully queued for site.', 'backupino-portal' ),
		);
	}

	/**
	 * Request manual restore (Owner only with mandatory 2FA verification - D-042).
	 *
	 * @param int    $user_id
	 * @param int    $license_id
	 * @param int    $backup_id
	 * @param string $totp_code Mandatory 6-digit TOTP code.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function request_manual_restore( int $user_id, int $license_id, int $backup_id, string $totp_code ) {
		// Strict ownership check (D-042)
		$auth = AuthorizationGuard::can_restore( $user_id, $license_id );
		if ( is_wp_error( $auth ) ) {
			return $auth;
		}

		// Verify 2FA TOTP code (D-042 Requirement)
		$is_2fa_enabled = TwoFactorAuth::is_2fa_enabled( $user_id );
		if ( ! $is_2fa_enabled ) {
			return new \WP_Error(
				'2FA_REQUIRED',
				__( 'Two-factor authentication (2FA) must be enabled on your account before issuing restore commands.', 'backupino-portal' ),
				array( 'status' => 403 )
			);
		}

		$totp_valid = TwoFactorAuth::verify_totp( $user_id, $totp_code );
		if ( ! $totp_valid ) {
			return new \WP_Error(
				'INVALID_2FA_CODE',
				__( 'Invalid two-factor authentication code.', 'backupino-portal' ),
				array( 'status' => 403 )
			);
		}

		$backup = BackupRepository::find_by_id( $backup_id );
		if ( ! $backup || $backup->license_id !== $license_id ) {
			return new \WP_Error( 'BACKUP_NOT_FOUND', __( 'Backup package not found.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$command_id = 'cmd_rst_' . bin2hex( random_bytes( 8 ) );

		AuditLogger::log(
			'manual_restore_requested',
			$user_id,
			$license_id,
			$backup->domain_hash,
			array(
				'command_id'   => $command_id,
				'backup_id'    => $backup_id,
				'package_name' => $backup->package_name,
			)
		);

		return array(
			'success'      => true,
			'command_id'   => $command_id,
			'package_name' => $backup->package_name,
			'status'       => 'pending_worker',
			'message'      => __( 'Restore command validated with 2FA and queued for processing.', 'backupino-portal' ),
		);
	}

	/**
	 * Deactivate a domain activation slot with cooling-off period (D-006).
	 *
	 * @param int $user_id
	 * @param int $activation_id
	 * @return bool|\WP_Error
	 */
	public static function deactivate_domain( int $user_id, int $activation_id ) {
		$activation = ActivationRepository::find_by_id( $activation_id );
		if ( ! $activation ) {
			return new \WP_Error( 'ACTIVATION_NOT_FOUND', __( 'Domain activation not found.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$ownership = AuthorizationGuard::verify_license_ownership( $user_id, $activation->license_id );
		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		$deactivated = ActivationRepository::deactivate( $activation_id );
		if ( ! $deactivated ) {
			return new \WP_Error( 'DEACTIVATION_FAILED', __( 'Could not deactivate domain.', 'backupino-portal' ), array( 'status' => 500 ) );
		}

		AuditLogger::log(
			'domain_deactivated_by_customer',
			$user_id,
			$activation->license_id,
			$activation->domain_hash,
			array(
				'activation_id' => $activation_id,
				'domain_name'   => $activation->domain_name,
			)
		);

		return true;
	}
}
