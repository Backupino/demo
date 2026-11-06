<?php
/**
 * Dynamic Plans and Quotas Engine.
 *
 * Implements S3-T05: Plans as Signed Data without code release.
 *
 * @package Backupino\Portal\Plans
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Plans;

use Backupino\Portal\Security\KeypairManager;
use Backupino\Portal\Audit\AuditLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Manages dynamic product plans catalog and signed data exports.
 */
final class PlanManager {

	public const OPTION_CATALOG = 'backupino_portal_plans_catalog';

	/**
	 * Get all active plans from database or defaults.
	 *
	 * @return array<string, Plan> Keyed by plan slug.
	 */
	public static function get_all(): array {
		$stored = get_option( self::OPTION_CATALOG, null );
		if ( ! is_array( $stored ) || empty( $stored ) ) {
			$defaults = self::get_canonical_defaults();
			self::save_catalog( $defaults );
			return $defaults;
		}

		$plans = array();
		foreach ( $stored as $slug => $data ) {
			if ( is_array( $data ) ) {
				$plans[ (string) $slug ] = new Plan( $data );
			}
		}

		return $plans;
	}

	/**
	 * Get single plan by slug.
	 *
	 * @param string $slug Plan slug (trial, personal, freelancer, agency).
	 * @return Plan|null
	 */
	public static function get( string $slug ): ?Plan {
		$plans = self::get_all();
		return $plans[ $slug ] ?? null;
	}

	/**
	 * Update plan attributes dynamically without code release (S3-T05).
	 *
	 * @param string               $slug         Plan identifier.
	 * @param array<string, mixed> $override_data New quotas / configuration.
	 * @param int                  $operator_id   Operator user ID.
	 * @return bool True if updated.
	 */
	public static function update_plan( string $slug, array $override_data, int $operator_id = 0 ): bool {
		$plans = self::get_all();
		if ( ! isset( $plans[ $slug ] ) ) {
			return false;
		}

		$current_data = $plans[ $slug ]->to_array();
		$updated_data = array_merge( $current_data, $override_data );
		$updated_data['slug'] = $slug; // Prevent slug corruption

		$plans[ $slug ] = new Plan( $updated_data );
		self::save_catalog( $plans );

		AuditLogger::log(
			'plan_quotas_updated',
			$operator_id,
			0,
			null,
			array(
				'plan'      => $slug,
				'overrides' => $override_data,
			)
		);

		return true;
	}

	/**
	 * Export full plans catalog as Ed25519 signed data.
	 *
	 * Allows client Pro plugin to verify quotas authenticity offline without trusting transport.
	 *
	 * @return array{catalog: array<string, array>, signed_blob: string}
	 */
	public static function export_signed_catalog(): array {
		$plans = self::get_all();
		$raw_catalog = array();

		foreach ( $plans as $slug => $plan ) {
			$raw_catalog[ $slug ] = $plan->to_array();
		}

		// Sort keys recursively for deterministic canonical JSON
		self::recursive_ksort( $raw_catalog );
		$canonical_json = (string) wp_json_encode( $raw_catalog, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		$signature = KeypairManager::sign_detached( $canonical_json );
		$b64_json  = KeypairManager::base64url_encode( $canonical_json );

		return array(
			'catalog'     => $raw_catalog,
			'signed_blob' => $b64_json . '.' . $signature,
		);
	}

	/**
	 * Verify a signed plans catalog blob.
	 *
	 * @param string      $blob      Compact blob.
	 * @param string|null $publicKey Optional public key.
	 * @return array<string, array>|false
	 */
	public static function verify_signed_catalog( string $blob, ?string $publicKey = null ) {
		$parts = explode( '.', trim( $blob ) );
		if ( 2 !== count( $parts ) ) {
			return false;
		}

		list( $b64_json, $signature ) = $parts;
		$canonical_json = KeypairManager::base64url_decode( $b64_json );

		if ( empty( $canonical_json ) ) {
			return false;
		}

		$is_valid = KeypairManager::verify_detached( $canonical_json, $signature, $publicKey );
		if ( ! $is_valid ) {
			return false;
		}

		$data = json_decode( $canonical_json, true );
		return is_array( $data ) ? $data : false;
	}

	/**
	 * Reset catalog to canonical product defaults.
	 */
	public static function reset_to_defaults(): void {
		$defaults = self::get_canonical_defaults();
		self::save_catalog( $defaults );
	}

	/**
	 * Persist plans catalog in options table.
	 *
	 * @param array<string, Plan> $plans
	 */
	private static function save_catalog( array $plans ): void {
		$to_save = array();
		foreach ( $plans as $slug => $plan ) {
			$to_save[ $slug ] = $plan->to_array();
		}
		update_option( self::OPTION_CATALOG, $to_save, 'yes' );
	}

	/**
	 * Canonical product plans specifications per PRODUCT-PLANS.md & DECISIONS.md.
	 *
	 * @return array<string, Plan>
	 */
	public static function get_canonical_defaults(): array {
		$base_features = array( 'restore_in_place', 'cloud_s3', 'cloud_ftp', 'incremental' );

		return array(
			// 1. Trial Plan (10 days, 1 domain, 0 swaps, 2 customer cloud versions)
			'trial' => new Plan(
				array(
					'slug'                        => 'trial',
					'name'                        => 'Backupino Trial',
					'name_fa'                     => 'بکاپینو آزمایشی',
					'duration_days'               => 10,
					'domains_limit'               => 1,
					'domain_swaps_allowed'        => 0, // No swaps allowed (D-005)
					'annual_activations_cap'      => 1,
					'customer_cloud_retention'    => 2,
					'dedicated_cloud_quota_bytes' => 0,
					'dedicated_cloud_retention'   => 0,
					'team_users_limit'            => 1,
					'sla_hours'                   => null,
					'multisite'                   => false,
					'whitelabel'                  => false,
					'features'                    => $base_features,
				)
			),

			// 2. Personal Plan (Annual, 1 domain, 2 swaps/year, 48h SLA)
			'personal' => new Plan(
				array(
					'slug'                        => 'personal',
					'name'                        => 'Backupino Personal',
					'name_fa'                     => 'بکاپینو شخصی',
					'duration_days'               => 365,
					'domains_limit'               => 1,
					'domain_swaps_allowed'        => 2, // 2 swaps per year (D-006)
					'annual_activations_cap'      => 5,
					'customer_cloud_retention'    => 2,
					'dedicated_cloud_quota_bytes' => 0,
					'dedicated_cloud_retention'   => 0,
					'team_users_limit'            => 1,
					'sla_hours'                   => 48, // 48h SLA
					'multisite'                   => false,
					'whitelabel'                  => false,
					'features'                    => array_merge( $base_features, array( 'rescue_agent' ) ),
				)
			),

			// 3. Freelancer Plan (Annual, 10 domains, 30GB Cloud, 5 versions, 3 team users, 24h SLA)
			'freelancer' => new Plan(
				array(
					'slug'                        => 'freelancer',
					'name'                        => 'Backupino Freelancer',
					'name_fa'                     => 'بکاپینو فریلنسر',
					'duration_days'               => 365,
					'domains_limit'               => 10,
					'domain_swaps_allowed'        => -1, // Unlimited swaps (D-006)
					'annual_activations_cap'      => 30, // Soft cap (D-006)
					'customer_cloud_retention'    => 5,
					'dedicated_cloud_quota_bytes' => 32212254720, // 30GB (D-007)
					'dedicated_cloud_retention'   => 5, // 5 versions (D-008)
					'team_users_limit'            => 3,
					'sla_hours'                   => 24, // 24h SLA
					'multisite'                   => true, // Multisite allowed (D-014)
					'whitelabel'                  => true,
					'features'                    => array_merge( $base_features, array( 'multisite', 'whitelabel', 'rescue_agent' ) ),
				)
			),

			// 4. Agency / Enterprise Plan (Annual, 30 domains, 100GB Cloud, 10 versions, 10 team users, 12h SLA)
			'agency' => new Plan(
				array(
					'slug'                        => 'agency',
					'name'                        => 'Backupino Agency',
					'name_fa'                     => 'بکاپینو سازمانی',
					'duration_days'               => 365,
					'domains_limit'               => 30,
					'domain_swaps_allowed'        => -1, // Unlimited swaps (D-006)
					'annual_activations_cap'      => 100, // Soft cap (D-006)
					'customer_cloud_retention'    => 10,
					'dedicated_cloud_quota_bytes' => 107374182400, // 100GB (D-007)
					'dedicated_cloud_retention'   => 10, // 10 versions (D-008)
					'team_users_limit'            => 10,
					'sla_hours'                   => 12, // 12h SLA + dedicated group
					'multisite'                   => true, // Multisite allowed (D-014)
					'whitelabel'                  => true,
					'features'                    => array_merge( $base_features, array( 'multisite', 'whitelabel', 'rescue_agent' ) ),
				)
			),
		);
	}

	/**
	 * Sort array keys alphabetically.
	 *
	 * @param array<mixed> $array
	 */
	private static function recursive_ksort( array &$array ): void {
		foreach ( $array as &$value ) {
			if ( is_array( $value ) ) {
				self::recursive_ksort( $value );
			}
		}
		if ( array_keys( $array ) !== range( 0, count( $array ) - 1 ) ) {
			ksort( $array, SORT_STRING );
		}
	}
}
