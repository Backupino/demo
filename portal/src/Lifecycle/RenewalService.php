<?php
/**
 * License Renewal and Administrator Override Service.
 *
 * Implements S3-T07 and D-037 custom renewal logic on WC_Order.
 *
 * @package Backupino\Portal\Lifecycle
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Lifecycle;

use Backupino\Portal\Models\License;
use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\License\LicenseSigner;
use Backupino\Portal\Plans\PlanManager;
use Backupino\Portal\Audit\AuditLogger;
use Backupino\Portal\Auth\RoleManager;

defined( 'ABSPATH' ) || exit;

/**
 * Handles renewals, re-signing license blobs, and administrative quota overrides.
 */
final class RenewalService {

	/**
	 * Renew a license for a given number of days.
	 *
	 * Extends from current expiration date if still active, or from current time if expired.
	 *
	 * @param int $license_id    License ID to renew.
	 * @param int $duration_days Number of days to extend (default: 365).
	 * @param int $order_id      Optional WooCommerce Order ID associated with renewal payment.
	 * @param int $actor_id      Optional user or admin ID performing the renewal.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function renew_license( int $license_id, int $duration_days = 365, int $order_id = 0, int $actor_id = 0 ) {
		$license = LicenseRepository::find_by_id( $license_id );
		if ( ! $license ) {
			return new \WP_Error( 'LICENSE_NOT_FOUND', __( 'License not found.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$now = time();
		$current_expiry = ! empty( $license->expires_at ) ? strtotime( $license->expires_at ) : 0;

		// If current expiry is in the future, add to it; otherwise start from now
		$base_time  = $current_expiry > $now ? $current_expiry : $now;
		$new_expiry = $base_time + ( $duration_days * 86400 );
		$new_grace  = $new_expiry + ( LicenseLifecycleManager::DEFAULT_GRACE_DAYS * 86400 );

		$new_expiry_str = gmdate( 'Y-m-d H:i:s', $new_expiry );
		$new_grace_str  = gmdate( 'Y-m-d H:i:s', $new_grace );

		global $wpdb;
		$table = LicenseRepository::table();

		$update_payload = array(
			'expires_at'       => $new_expiry_str,
			'grace_expires_at' => $new_grace_str,
			'status'           => 'active',
			'updated_at'       => current_time( 'mysql', true ),
		);
		$update_format  = array( '%s', '%s', '%s', '%s' );

		if ( $order_id > 0 ) {
			$update_payload['order_id'] = $order_id;
			$update_format[]            = '%d';
		}

		$updated = $wpdb->update(
			$table,
			$update_payload,
			array( 'id' => $license_id ),
			$update_format,
			array( '%d' )
		);

		if ( false === $updated ) {
			return new \WP_Error( 'DATABASE_ERROR', __( 'Could not update license renewal.', 'backupino-portal' ), array( 'status' => 500 ) );
		}

		$fresh_license = LicenseRepository::find_by_id( $license_id );

		// Re-issue signed Ed25519 license blob
		$plan     = PlanManager::get( $fresh_license->plan_slug );
		$features = $plan ? $plan->features : array( 'restore_in_place', 'cloud_s3', 'incremental' );

		$signed_blob = LicenseSigner::sign_license(
			array(
				'activation_id'       => 'act_' . bin2hex( random_bytes( 8 ) ),
				'plan'                => $fresh_license->plan_slug,
				'domains'             => array(),
				'max_domains'         => $fresh_license->max_domains,
				'cloud_storage_bytes' => $fresh_license->cloud_quota_bytes,
				'retention_versions'  => $fresh_license->cloud_max_versions_per_domain,
				'features'            => $features,
				'issued_at'           => $now,
				'expiry'              => $new_expiry,
				'grace_period_days'   => LicenseLifecycleManager::DEFAULT_GRACE_DAYS,
			)
		);

		AuditLogger::log(
			'license_renewed',
			$actor_id ?: $license->user_id,
			$license_id,
			null,
			array(
				'previous_expiry' => $license->expires_at,
				'new_expiry'      => $new_expiry_str,
				'extended_days'   => $duration_days,
				'order_id'        => $order_id,
			)
		);

		return array(
			'license_id'          => $license_id,
			'status'              => 'active',
			'expires_at'          => $new_expiry_str,
			'grace_expires_at'    => $new_grace_str,
			'signed_license_blob' => $signed_blob,
		);
	}

	/**
	 * Administrative override of license parameters (Admin / Operator only).
	 *
	 * @param int                  $license_id      License ID.
	 * @param array<string, mixed> $override_params Quota/status overrides.
	 * @param int                  $admin_user_id   Admin user ID.
	 * @param string               $reason          Required administrative reason.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function admin_override( int $license_id, array $override_params, int $admin_user_id, string $reason ) {
		if ( empty( $reason ) ) {
			return new \WP_Error( 'REASON_REQUIRED', __( 'Administrative override requires a documented reason.', 'backupino-portal' ), array( 'status' => 400 ) );
		}

		if ( $admin_user_id > 0 && ! user_can( $admin_user_id, 'administrator' ) && ! user_can( $admin_user_id, RoleManager::CAP_ADMIN_PORTAL ) ) {
			return new \WP_Error( 'FORBIDDEN', __( 'User is not authorized to override license parameters.', 'backupino-portal' ), array( 'status' => 403 ) );
		}

		$license = LicenseRepository::find_by_id( $license_id );
		if ( ! $license ) {
			return new \WP_Error( 'LICENSE_NOT_FOUND', __( 'License not found.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$allowed_fields = array(
			'status',
			'expires_at',
			'grace_expires_at',
			'max_domains',
			'cloud_quota_bytes',
			'cloud_storage_enabled',
			'plan_slug',
		);

		$payload = array(
			'updated_at' => current_time( 'mysql', true ),
		);
		$formats = array( '%s' );

		foreach ( $allowed_fields as $field ) {
			if ( array_key_exists( $field, $override_params ) ) {
				$val = $override_params[ $field ];
				$payload[ $field ] = $val;
				$formats[] = is_int( $val ) ? '%d' : '%s';
			}
		}

		global $wpdb;
		$table   = LicenseRepository::table();
		$updated = $wpdb->update(
			$table,
			$payload,
			array( 'id' => $license_id ),
			$formats,
			array( '%d' )
		);

		if ( false === $updated ) {
			return new \WP_Error( 'DATABASE_ERROR', __( 'Could not apply admin override.', 'backupino-portal' ), array( 'status' => 500 ) );
		}

		AuditLogger::log(
			'license_admin_override',
			$admin_user_id,
			$license_id,
			null,
			array(
				'reason'    => sanitize_text_field( $reason ),
				'overrides' => $override_params,
			)
		);

		$fresh = LicenseRepository::find_by_id( $license_id );

		return array(
			'success' => true,
			'license' => $fresh ? $fresh->to_array() : array(),
		);
	}

	/**
	 * Create a lightweight WooCommerce renewal order for a license.
	 *
	 * Implements D-037 renewal on WC_Order without WooCommerce Subscriptions.
	 *
	 * @param int $license_id License ID.
	 * @return int|\WP_Error WooCommerce Order ID.
	 */
	public static function create_renewal_order( int $license_id ) {
		$license = LicenseRepository::find_by_id( $license_id );
		if ( ! $license ) {
			return new \WP_Error( 'LICENSE_NOT_FOUND', __( 'License not found.', 'backupino-portal' ) );
		}

		if ( ! function_exists( 'wc_create_order' ) ) {
			return new \WP_Error( 'WOOCOMMERCE_MISSING', __( 'WooCommerce is not active.', 'backupino-portal' ) );
		}

		$order = wc_create_order(
			array(
				'customer_id' => $license->user_id,
				'status'      => 'pending',
			)
		);

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$order_id = $order->get_id();
		$order->update_meta_data( '_backupino_is_renewal', 1 );
		$order->update_meta_data( '_backupino_renewal_license_id', $license_id );
		$order->add_order_note(
			sprintf(
				/* translators: 1: license ID, 2: plan slug */
				__( 'Renewal order created for Backupino License #%1$d (%2$s).', 'backupino-portal' ),
				$license_id,
				$license->plan_slug
			)
		);
		$order->save();

		return $order_id;
	}
}
