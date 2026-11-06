<?php
/**
 * WooCommerce Order Synchronization and License Provisioning.
 *
 * Implements S3-T06, D-034, D-037, D-052.
 *
 * @package Backupino\Portal\Commerce
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Commerce;

use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\Repository\ActivationRepository;
use Backupino\Portal\Plans\PlanManager;
use Backupino\Portal\Audit\AuditLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Handles WooCommerce order lifecycle hooks and automatic license provisioning.
 */
final class OrderSyncService {

	public const META_LICENSE_KEY = '_backupino_license_key';
	public const META_LICENSE_ID  = '_backupino_license_id';

	/**
	 * Register WooCommerce order hooks.
	 */
	public static function init(): void {
		add_action( 'woocommerce_order_status_completed', array( self::class, 'handle_order_completed' ), 10, 1 );
		add_action( 'woocommerce_order_status_processing', array( self::class, 'handle_order_completed' ), 10, 1 );
		add_action( 'woocommerce_order_status_refunded', array( self::class, 'handle_order_refunded' ), 10, 1 );
		add_action( 'woocommerce_order_status_cancelled', array( self::class, 'handle_order_cancelled' ), 10, 1 );
	}

	/**
	 * Provision license upon order payment completion.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @return int[] Array of created License IDs.
	 */
	public static function handle_order_completed( int $order_id ): array {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return array();
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array();
		}

		// Prevent duplicate provisioning
		$already_provisioned = (bool) $order->get_meta( '_backupino_provisioned', true );
		if ( $already_provisioned ) {
			return array();
		}

		$user_id = (int) $order->get_user_id();

		// Handle renewal order if linked to existing license (D-037)
		$renewal_license_id = (int) $order->get_meta( '_backupino_renewal_license_id', true );
		if ( $renewal_license_id > 0 ) {
			$renewal_res = \Backupino\Portal\Lifecycle\RenewalService::renew_license( $renewal_license_id, 365, $order_id, $user_id );
			if ( ! is_wp_error( $renewal_res ) ) {
				$order->update_meta_data( '_backupino_provisioned', 1 );
				$order->save();
				return array( $renewal_license_id );
			}
		}

		$created_ids = array();

		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}

			// Identify plan from product meta or slug
			$plan_slug = self::detect_plan_slug( $product );
			if ( empty( $plan_slug ) ) {
				continue;
			}

			$plan = PlanManager::get( $plan_slug );
			if ( ! $plan ) {
				continue;
			}

			// Generate license key: BK-XXXX-XXXX-XXXX-XXXX
			$license_key = self::generate_license_key();
			$expires_at  = gmdate( 'Y-m-d H:i:s', time() + ( $plan->duration_days * 86400 ) );

			$license_id = LicenseRepository::create(
				array(
					'order_id'                      => $order_id,
					'user_id'                       => $user_id,
					'license_key'                   => $license_key,
					'plan_slug'                     => $plan->slug,
					'status'                        => 'active',
					'max_domains'                   => $plan->domains_limit,
					'active_domains_count'          => 0,
					'expires_at'                    => $expires_at,
					'cloud_storage_enabled'         => $plan->dedicated_cloud_quota_bytes > 0 ? 1 : 0,
					'cloud_quota_bytes'             => $plan->dedicated_cloud_quota_bytes,
					'cloud_used_bytes'              => 0,
					'cloud_max_versions_per_domain' => $plan->dedicated_cloud_retention > 0 ? $plan->dedicated_cloud_retention : 5,
				)
			);

			if ( $license_id ) {
				$item->update_meta_data( self::META_LICENSE_KEY, $license_key );
				$item->update_meta_data( self::META_LICENSE_ID, $license_id );
				$item->save();

				$created_ids[] = $license_id;

				AuditLogger::log(
					'license_provisioned_from_order',
					$user_id,
					$license_id,
					null,
					array(
						'order_id'    => $order_id,
						'plan'        => $plan->slug,
						'license_key' => $license_key,
					)
				);
			}
		}

		if ( ! empty( $created_ids ) ) {
			$order->update_meta_data( '_backupino_provisioned', 1 );
			$order->update_meta_data( '_backupino_license_ids', $created_ids );
			$order->add_order_note(
				sprintf(
					/* translators: %s: list of license IDs */
					__( 'Backupino License(s) successfully provisioned: %s', 'backupino-portal' ),
					implode( ', ', $created_ids )
				)
			);
			$order->save();
		}

		return $created_ids;
	}

	/**
	 * Revoke license upon order refund.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 */
	public static function handle_order_refunded( int $order_id ): void {
		self::revoke_order_licenses( $order_id, 'order_refunded' );
	}

	/**
	 * Revoke license upon order cancellation.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 */
	public static function handle_order_cancelled( int $order_id ): void {
		self::revoke_order_licenses( $order_id, 'order_cancelled' );
	}

	/**
	 * Revoke all licenses associated with an order.
	 */
	private static function revoke_order_licenses( int $order_id, string $reason ): void {
		global $wpdb;

		$licenses_table = LicenseRepository::table();
		$licenses       = $wpdb->get_results(
			$wpdb->prepare( "SELECT `id`, `user_id` FROM `{$licenses_table}` WHERE `order_id` = %d", $order_id ),
			ARRAY_A
		);

		if ( ! is_array( $licenses ) ) {
			return;
		}

		foreach ( $licenses as $row ) {
			$license_id = (int) $row['id'];
			$user_id    = (int) $row['user_id'];

			LicenseRepository::update_status( $license_id, 'revoked' );

			// Deactivate all associated domain activations
			$activations = ActivationRepository::find_active_by_license( $license_id );
			foreach ( $activations as $activation ) {
				ActivationRepository::deactivate( $activation->id );
			}

			AuditLogger::log(
				'license_revoked_by_order_status',
				$user_id,
				$license_id,
				null,
				array(
					'order_id' => $order_id,
					'reason'   => $reason,
				)
			);
		}
	}

	/**
	 * Generate standard format license key (BK-XXXX-XXXX-XXXX-XXXX).
	 */
	public static function generate_license_key(): string {
		$groups = array();
		for ( $i = 0; $i < 4; $i++ ) {
			$groups[] = strtoupper( bin2hex( random_bytes( 2 ) ) ); // 4 chars hex
		}
		return 'BK-' . implode( '-', $groups );
	}

	/**
	 * Detect plan slug from product attributes or metadata.
	 *
	 * @param \WC_Product $product
	 * @return string|null
	 */
	public static function detect_plan_slug( \WC_Product $product ): ?string {
		$custom_slug = (string) $product->get_meta( '_backupino_plan_slug', true );
		if ( ! empty( $custom_slug ) ) {
			return $custom_slug;
		}

		$product_slug = $product->get_slug();
		foreach ( array( 'trial', 'personal', 'freelancer', 'agency', 'enterprise' ) as $slug ) {
			if ( false !== strpos( $product_slug, $slug ) ) {
				return 'enterprise' === $slug ? 'agency' : $slug;
			}
		}

		return null;
	}
}
