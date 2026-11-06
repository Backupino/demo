<?php
/**
 * Cloud Backup Repository.
 *
 * Direct database access layer for `wp_backupino_cloud_backups`.
 *
 * @package Backupino\Portal\Repository
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Repository;

use Backupino\Portal\Models\CloudBackup;

defined( 'ABSPATH' ) || exit;

/**
 * Handles database operations for cloud backups.
 */
final class BackupRepository {

	public const TABLE_NAME = 'backupino_cloud_backups';

	/**
	 * Get table name with prefix.
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Find backup by ID.
	 *
	 * @param int $id
	 * @return CloudBackup|null
	 */
	public static function find_by_id( int $id ): ?CloudBackup {
		global $wpdb;

		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE `id` = %d LIMIT 1", $id ),
			ARRAY_A
		);

		return $row ? new CloudBackup( $row ) : null;
	}

	/**
	 * Find backups by license ID.
	 *
	 * @param int $license_id
	 * @param int $limit
	 * @return CloudBackup[]
	 */
	public static function find_by_license_id( int $license_id, int $limit = 50 ): array {
		global $wpdb;

		$table = self::table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `license_id` = %d AND `retention_status` != 'purged' ORDER BY `created_at` DESC LIMIT %d",
				$license_id,
				$limit
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( fn( $row ) => new CloudBackup( $row ), $rows );
	}

	/**
	 * Find backups by domain hash.
	 *
	 * @param string $domain_hash
	 * @param int    $limit
	 * @return CloudBackup[]
	 */
	public static function find_by_domain_hash( string $domain_hash, int $limit = 20 ): array {
		global $wpdb;

		$table = self::table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `domain_hash` = %s AND `retention_status` != 'purged' ORDER BY `created_at` DESC LIMIT %d",
				$domain_hash,
				$limit
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( fn( $row ) => new CloudBackup( $row ), $rows );
	}

	/**
	 * Insert a new cloud backup record.
	 *
	 * @param array<string, mixed> $data
	 * @return int|false
	 */
	public static function create( array $data ) {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$defaults = array(
			'license_id'                   => 0,
			'activation_id'                => 0,
			'domain_hash'                  => '',
			'backup_hash'                  => '',
			'package_name'                 => '',
			'file_size_bytes'              => 0,
			'storage_provider'             => 'dedicated-s3',
			'storage_path'                 => '',
			'wrapped_dek'                  => null,
			'shamir_recovery_hint'         => null,
			'retention_status'             => 'active',
			'retention_purge_scheduled_at' => null,
			'purged_at'                    => null,
			'created_at'                   => $now,
		);

		$payload = array_merge( $defaults, $data );

		$inserted = $wpdb->insert(
			$table,
			$payload,
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		return false !== $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Count active backups for a license.
	 *
	 * @param int $license_id
	 * @return int
	 */
	public static function count_for_license( int $license_id ): int {
		global $wpdb;

		$table = self::table();
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE `license_id` = %d AND `retention_status` != 'purged'",
				$license_id
			)
		);
	}

	/**
	 * Find a backup record by backup/package hash.
	 *
	 * @param string $backup_hash
	 * @return CloudBackup|null
	 */
	public static function find_by_backup_hash( string $backup_hash ): ?CloudBackup {
		global $wpdb;

		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `backup_hash` = %s AND `retention_status` != 'purged' LIMIT 1",
				$backup_hash
			),
			ARRAY_A
		);

		return $row ? new CloudBackup( $row ) : null;
	}

	/**
	 * Find active backups by activation ID.
	 *
	 * @param int $activation_id
	 * @param int $limit
	 * @return CloudBackup[]
	 */
	public static function find_by_activation_id( int $activation_id, int $limit = 50 ): array {
		global $wpdb;

		$table = self::table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `activation_id` = %d AND `retention_status` != 'purged' ORDER BY `created_at` DESC LIMIT %d",
				$activation_id,
				$limit
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( fn( $row ) => new CloudBackup( $row ), $rows );
	}
}
