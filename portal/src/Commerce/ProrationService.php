<?php
/**
 * Proration and Subscription Upgrade/Downgrade Calculator.
 *
 * Implements S3-T06 proration mathematics without heavyweight plugins.
 *
 * @package Backupino\Portal\Commerce
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Commerce;

use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\Plans\PlanManager;
use Backupino\Portal\Audit\AuditLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plan upgrade calculations, unused credit proration, and quota adjustments.
 */
final class ProrationService {

	/**
	 * Calculate fair prorated upgrade costs.
	 *
	 * Formula:
	 *   Unused Credit = (Remaining Days / Total Duration Days) * Current Plan Price
	 *   Prorated Due  = Max(0, New Plan Price - Unused Credit)
	 *
	 * @param float $current_price Current plan paid amount.
	 * @param float $new_price     New plan full price.
	 * @param int   $total_days    Total plan duration (e.g. 365).
	 * @param int   $remaining_days Days left until expiration.
	 * @return array<string, float|int>
	 */
	public static function calculate_upgrade( float $current_price, float $new_price, int $total_days, int $remaining_days ): array {
		$remaining_days = max( 0, min( $remaining_days, $total_days ) );
		$total_days     = max( 1, $total_days );

		$daily_rate     = $current_price / $total_days;
		$unused_credit  = round( $daily_rate * $remaining_days, 2 );
		$amount_to_pay  = max( 0.0, round( $new_price - $unused_credit, 2 ) );

		return array(
			'current_price'   => $current_price,
			'new_price'       => $new_price,
			'total_days'      => $total_days,
			'remaining_days'  => $remaining_days,
			'unused_credit'   => $unused_credit,
			'amount_to_pay'   => $amount_to_pay,
			'discount_rate'   => $new_price > 0 ? round( ( $unused_credit / $new_price ) * 100, 1 ) : 0.0,
		);
	}

	/**
	 * Apply an instant plan upgrade to an existing license.
	 *
	 * @param int    $license_id    License ID to upgrade.
	 * @param string $new_plan_slug Target plan slug.
	 * @param int    $actor_id      User/Operator ID.
	 * @return bool|\WP_Error
	 */
	public static function apply_upgrade( int $license_id, string $new_plan_slug, int $actor_id = 0 ) {
		$license = LicenseRepository::find_by_id( $license_id );
		if ( ! $license ) {
			return new \WP_Error( 'LICENSE_NOT_FOUND', __( 'License not found.', 'backupino-portal' ) );
		}

		$new_plan = PlanManager::get( $new_plan_slug );
		if ( ! $new_plan ) {
			return new \WP_Error( 'INVALID_PLAN', __( 'Target plan does not exist.', 'backupino-portal' ) );
		}

		$old_plan_slug = $license->plan_slug;

		global $wpdb;
		$table = LicenseRepository::table();
		$now   = current_time( 'mysql', true );

		// Update license record with upgraded quotas
		$updated = $wpdb->update(
			$table,
			array(
				'plan_slug'                     => $new_plan->slug,
				'max_domains'                   => $new_plan->domains_limit,
				'cloud_storage_enabled'         => $new_plan->dedicated_cloud_quota_bytes > 0 ? 1 : 0,
				'cloud_quota_bytes'             => $new_plan->dedicated_cloud_quota_bytes,
				'cloud_max_versions_per_domain' => $new_plan->dedicated_cloud_retention > 0 ? $new_plan->dedicated_cloud_retention : 5,
				'status'                        => 'active',
				'updated_at'                    => $now,
			),
			array( 'id' => $license_id ),
			array( '%s', '%d', '%d', '%d', '%d', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new \WP_Error( 'DATABASE_ERROR', __( 'Could not update license plan.', 'backupino-portal' ) );
		}

		AuditLogger::log(
			'license_plan_upgraded',
			$actor_id ?: $license->user_id,
			$license_id,
			null,
			array(
				'from_plan'   => $old_plan_slug,
				'to_plan'     => $new_plan->slug,
				'new_domains' => $new_plan->domains_limit,
			)
		);

		return true;
	}
}
