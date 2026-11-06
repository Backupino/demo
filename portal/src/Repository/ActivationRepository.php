<?php
/**
 * Activation Repository.
 *
 * Direct database access layer for `wp_backupino_activations` table.
 *
 * @package Backupino\Portal\Repository
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Repository;

use Backupino\Portal\Models\Activation;

defined( 'ABSPATH' ) || exit;

/**
 * Handles database operations for domain activations.
 */
final class ActivationRepository {

	public const TABLE_NAME = 'backupino_activations';

	/**
	 * Get table name with WordPress prefix.
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Insert a new domain activation.
	 *
	 * @param array<string, mixed> $data
	 * @return int|false Inserted ID or false on failure.
	 */
	public static function create( array $data ) {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$defaults = array(
			'license_id'                  => 0,
			'domain_hash'                 => '',
			'domain_name'                 => '',
			'is_staging'                  => 0,
			'is_active'                   => 1,
			'last_heartbeat_at'           => $now,
			'instance_id'                 => '',
			'cloud_domain_used_bytes'     => 0,
			'cloud_domain_versions_count' => 0,
			'cloud_last_backup_at'        => null,
			'activated_at'                => $now,
			'deactivated_at'              => null,
		);

		$payload = array_merge( $defaults, $data );

		$inserted = $wpdb->insert(
			$table,
			$payload,
			array(
				'%d', // license_id
				'%s', // domain_hash
				'%s', // domain_name
				'%d', // is_staging
				'%d', // is_active
				'%s', // last_heartbeat_at
				'%s', // instance_id
				'%d', // cloud_domain_used_bytes
				'%d', // cloud_domain_versions_count
				'%s', // cloud_last_backup_at
				'%s', // activated_at
				'%s', // deactivated_at
			)
		);

		if ( false === $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Find activation by ID.
	 *
	 * @param int $id Activation ID.
	 * @return Activation|null
	 */
	public static function find_by_id( int $id ): ?Activation {
		global $wpdb;

		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE `id` = %d LIMIT 1", $id ),
			ARRAY_A
		);

		return $row ? new Activation( $row ) : null;
	}

	/**
	 * Find activation by license ID and domain hash.
	 */
	public static function find_by_license_and_hash( int $license_id, string $domain_hash ): ?Activation {
		global $wpdb;

		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `license_id` = %d AND `domain_hash` = %s LIMIT 1",
				$license_id,
				$domain_hash
			),
			ARRAY_A
		);

		return $row ? new Activation( $row ) : null;
	}

	/**
	 * Find active activation by domain hash across any license.
	 *
	 * @return Activation[]
	 */
	public static function find_by_domain_hash( string $domain_hash ): array {
		global $wpdb;

		$table = self::table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `domain_hash` = %s AND `is_active` = 1",
				$domain_hash
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( static fn( $row ) => new Activation( $row ), $rows );
	}

	/**
	 * Find all active activations for a license.
	 *
	 * @return Activation[]
	 */
	public static function find_active_by_license( int $license_id ): array {
		global $wpdb;

		$table = self::table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `license_id` = %d AND `is_active` = 1 ORDER BY `id` ASC",
				$license_id
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( static fn( $row ) => new Activation( $row ), $rows );
	}

	/**
	 * Count active production domains for a license (excludes staging).
	 */
	public static function count_active_production( int $license_id ): int {
		global $wpdb;

		$table = self::table();
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE `license_id` = %d AND `is_active` = 1 AND `is_staging` = 0",
				$license_id
			)
		);

		return (int) $count;
	}

	/**
	 * Count active staging domains for a license (D-051).
	 */
	public static function count_active_staging( int $license_id ): int {
		global $wpdb;

		$table = self::table();
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE `license_id` = %d AND `is_active` = 1 AND `is_staging` = 1",
				$license_id
			)
		);

		return (int) $count;
	}

	/**
	 * Update heartbeat timestamp.
	 */
	public static function touch_heartbeat( int $id ): bool {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$updated = $wpdb->update(
			$table,
			array( 'last_heartbeat_at' => $now ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Mark an activation as deactivated.
	 */
	public static function deactivate( int $id ): bool {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$updated = $wpdb->update(
			$table,
			array(
				'is_active'      => 0,
				'deactivated_at' => $now,
			),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Reactivate an existing activation record.
	 */
	public static function reactivate( int $id, string $instance_id = '' ): bool {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$data = array(
			'is_active'         => 1,
			'activated_at'      => $now,
			'last_heartbeat_at' => $now,
			'deactivated_at'    => null,
		);

		$format = array( '%d', '%s', '%s', '%s' );

		if ( '' !== $instance_id ) {
			$data['instance_id'] = $instance_id;
			$format[]            = '%s';
		}

		$updated = $wpdb->update(
			$table,
			$data,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Permanently delete an activation record from database.
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function delete( int $id ): bool {
		global $wpdb;

		$table   = self::table();
		$deleted = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		return false !== $deleted;
	}

	/**
	 * Update domain name and hash for an activation slot (domain migration).
	 *
	 * @param int    $id
	 * @param string $new_domain
	 * @param string $new_hash
	 * @param int    $is_staging
	 * @return bool
	 */
	public static function update_domain( int $id, string $new_domain, string $new_hash, int $is_staging ): bool {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$updated = $wpdb->update(
			$table,
			array(
				'domain_name'       => $new_domain,
				'domain_hash'       => $new_hash,
				'is_staging'        => $is_staging,
				'activated_at'      => $now,
				'last_heartbeat_at' => $now,
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}
}
