<?php
/**
 * License Repository.
 *
 * Direct database access layer for `wp_backupino_licenses` table.
 *
 * @package Backupino\Portal\Repository
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Repository;

use Backupino\Portal\Models\License;

defined( 'ABSPATH' ) || exit;

/**
 * Handles database operations for licenses.
 */
final class LicenseRepository {

	public const TABLE_NAME = 'backupino_licenses';

	/**
	 * Get table name with WordPress prefix.
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Insert a new license record.
	 *
	 * @param array<string, mixed> $data
	 * @return int|false Inserted ID or false on failure.
	 */
	public static function create( array $data ) {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$defaults = array(
			'order_id'                      => 0,
			'user_id'                       => 0,
			'license_key'                   => '',
			'plan_slug'                     => 'personal',
			'status'                        => 'active',
			'max_domains'                   => 1,
			'active_domains_count'          => 0,
			'expires_at'                    => null,
			'grace_expires_at'              => null,
			'cloud_storage_enabled'         => 0,
			'cloud_quota_bytes'             => 0,
			'cloud_used_bytes'              => 0,
			'cloud_max_versions_per_domain' => 5,
			'cloud_zero_knowledge_enabled'  => 1,
			'cloud_retention_days'          => 60,
			'created_at'                    => $now,
			'updated_at'                    => $now,
		);

		$payload = array_merge( $defaults, $data );

		$inserted = $wpdb->insert(
			$table,
			$payload,
			array(
				'%d', // order_id
				'%d', // user_id
				'%s', // license_key
				'%s', // plan_slug
				'%s', // status
				'%d', // max_domains
				'%d', // active_domains_count
				'%s', // expires_at
				'%s', // grace_expires_at
				'%d', // cloud_storage_enabled
				'%d', // cloud_quota_bytes
				'%d', // cloud_used_bytes
				'%d', // cloud_max_versions_per_domain
				'%d', // cloud_zero_knowledge_enabled
				'%d', // cloud_retention_days
				'%s', // created_at
				'%s', // updated_at
			)
		);

		if ( false === $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Find license by ID.
	 */
	public static function find_by_id( int $id ): ?License {
		global $wpdb;

		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE `id` = %d LIMIT 1", $id ),
			ARRAY_A
		);

		return $row ? new License( $row ) : null;
	}

	/**
	 * Find license by license key.
	 */
	public static function find_by_key( string $license_key ): ?License {
		global $wpdb;

		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE `license_key` = %s LIMIT 1", trim( $license_key ) ),
			ARRAY_A
		);

		return $row ? new License( $row ) : null;
	}

	/**
	 * Find all licenses belonging to a user.
	 *
	 * @return License[]
	 */
	public static function find_by_user( int $user_id ): array {
		global $wpdb;

		$table = self::table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE `user_id` = %d ORDER BY `id` DESC", $user_id ),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( static fn( $row ) => new License( $row ), $rows );
	}

	/**
	 * Alias for find_by_user.
	 *
	 * @param int $user_id
	 * @return License[]
	 */
	public static function find_by_user_id( int $user_id ): array {
		return self::find_by_user( $user_id );
	}

	/**
	 * Update license status.
	 */
	public static function update_status( int $id, string $status ): bool {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$updated = $wpdb->update(
			$table,
			array(
				'status'     => $status,
				'updated_at' => $now,
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Increment active domains count.
	 */
	public static function increment_domains_count( int $id, int $amount = 1 ): bool {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table}` SET `active_domains_count` = `active_domains_count` + %d, `updated_at` = %s WHERE `id` = %d",
				$amount,
				$now,
				$id
			)
		);

		return false !== $result;
	}

	/**
	 * Decrement active domains count.
	 */
	public static function decrement_domains_count( int $id, int $amount = 1 ): bool {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table}` SET `active_domains_count` = GREATEST(0, CAST(`active_domains_count` AS SIGNED) - %d), `updated_at` = %s WHERE `id` = %d",
				$amount,
				$now,
				$id
			)
		);

		return false !== $result;
	}

	/**
	 * Update cloud storage usage (Phase 7 Reserved).
	 */
	public static function update_cloud_usage( int $id, int $used_bytes ): bool {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$updated = $wpdb->update(
			$table,
			array(
				'cloud_used_bytes' => max( 0, $used_bytes ),
				'updated_at'       => $now,
			),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}
}
