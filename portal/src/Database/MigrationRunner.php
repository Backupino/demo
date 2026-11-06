<?php
/**
 * Portal Database Migration Engine.
 *
 * @package Backupino\Portal\Database
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Executes and tracks database schema migrations.
 */
final class MigrationRunner {

	public const MIGRATIONS_TABLE = 'backupino_schema_migrations';

	/**
	 * Run all pending migrations.
	 */
	public static function run(): void {
		global $wpdb;

		self::ensure_migrations_table();
		$executed = self::get_executed_migrations();

		$migrations = self::get_migration_definitions();

		foreach ( $migrations as $version => $callable ) {
			if ( in_array( $version, $executed, true ) ) {
				continue;
			}

			$success = (bool) call_user_func( $callable, $wpdb );

			if ( $success ) {
				$wpdb->insert(
					$wpdb->prefix . self::MIGRATIONS_TABLE,
					array(
						'version'     => $version,
						'executed_at' => current_time( 'mysql', true ),
					),
					array( '%s', '%s' )
				);

				if ( class_exists( 'Backupino\Portal\Audit\AuditLogger' ) ) {
					\Backupino\Portal\Audit\AuditLogger::log(
						'migration_executed',
						0,
						0,
						null,
						array( 'version' => $version )
					);
				}
			}
		}
	}

	/**
	 * Ensure the migrations tracking table exists.
	 */
	private static function ensure_migrations_table(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . self::MIGRATIONS_TABLE;
		$charset    = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
			`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			`version` VARCHAR(128) NOT NULL UNIQUE,
			`executed_at` DATETIME NOT NULL,
			INDEX `idx_version` (`version`)
		) ENGINE=InnoDB {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get list of executed migration versions.
	 *
	 * @return string[]
	 */
	public static function get_executed_migrations(): array {
		global $wpdb;

		$table_name = $wpdb->prefix . self::MIGRATIONS_TABLE;
		$results    = $wpdb->get_col( "SELECT `version` FROM `{$table_name}` ORDER BY `id` ASC" );

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Define migrations registry.
	 *
	 * @return array<string, callable>
	 */
	private static function get_migration_definitions(): array {
		return array(
			'2026_11_06_000001_create_audit_logs_table' => array( self::class, 'migrate_000001_audit_logs' ),
			'2026_11_06_000002_create_licensing_tables' => array( self::class, 'migrate_000002_licensing_tables' ),
		);
	}

	/**
	 * Migration 000001: Create audit logs table.
	 */
	public static function migrate_000001_audit_logs( \wpdb $wpdb ): bool {
		$table   = $wpdb->prefix . 'backupino_audit_logs';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
			`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			`user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
			`license_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
			`action` VARCHAR(64) NOT NULL,
			`domain_hash` CHAR(64) NULL,
			`context` LONGTEXT NULL,
			`created_at` DATETIME NOT NULL,
			INDEX `idx_user` (`user_id`),
			INDEX `idx_license` (`license_id`),
			INDEX `idx_action_created` (`action`, `created_at`)
		) ENGINE=InnoDB {$charset};";

		$wpdb->query( $sql );

		return true;
	}

	/**
	 * Migration 000002: Create licensing, activations, and reserved cloud backups tables.
	 *
	 * Implements D-007, D-008, D-041, D-044, D-051, D-052.
	 */
	public static function migrate_000002_licensing_tables( \wpdb $wpdb ): bool {
		$charset           = $wpdb->get_charset_collate();
		$licenses_table    = $wpdb->prefix . 'backupino_licenses';
		$activations_table = $wpdb->prefix . 'backupino_activations';
		$backups_table     = $wpdb->prefix . 'backupino_cloud_backups';

		// 1. Licenses Table (D-052 + Phase 7 Reserved Quotas)
		$sql_licenses = "CREATE TABLE IF NOT EXISTS `{$licenses_table}` (
			`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			`order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
			`user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
			`license_key` VARCHAR(64) NOT NULL UNIQUE,
			`plan_slug` VARCHAR(32) NOT NULL DEFAULT 'personal',
			`status` ENUM('active', 'expiring_soon', 'grace_period', 'downgraded', 'revoked') NOT NULL DEFAULT 'active',
			`max_domains` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
			`active_domains_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			`expires_at` DATETIME NULL,
			`grace_expires_at` DATETIME NULL,
			`cloud_storage_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
			`cloud_quota_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
			`cloud_used_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
			`cloud_max_versions_per_domain` SMALLINT UNSIGNED NOT NULL DEFAULT 5,
			`cloud_zero_knowledge_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
			`cloud_retention_days` SMALLINT UNSIGNED NOT NULL DEFAULT 60,
			`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX `idx_user_licenses` (`user_id`, `status`),
			INDEX `idx_license_status` (`status`, `expires_at`),
			INDEX `idx_cloud_enabled` (`cloud_storage_enabled`)
		) ENGINE=InnoDB {$charset};";

		// 2. Activations Table (D-051 Staging Whitelist + Per-domain Cloud Usage)
		$sql_activations = "CREATE TABLE IF NOT EXISTS `{$activations_table}` (
			`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			`license_id` BIGINT UNSIGNED NOT NULL,
			`domain_hash` CHAR(64) NOT NULL,
			`domain_name` VARCHAR(255) NOT NULL,
			`is_staging` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
			`is_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
			`last_heartbeat_at` DATETIME NULL,
			`instance_id` CHAR(32) NOT NULL DEFAULT '',
			`cloud_domain_used_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
			`cloud_domain_versions_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			`cloud_last_backup_at` DATETIME NULL,
			`activated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`deactivated_at` DATETIME NULL,
			UNIQUE KEY `uk_license_domain` (`license_id`, `domain_hash`),
			INDEX `idx_domain_hash` (`domain_hash`),
			INDEX `idx_active_heartbeat` (`is_active`, `last_heartbeat_at`),
			INDEX `idx_license_id` (`license_id`)
		) ENGINE=InnoDB {$charset};";

		// 3. Cloud Backups Reservation Table (Phase 7 Reserved - D-041, D-044)
		$sql_backups = "CREATE TABLE IF NOT EXISTS `{$backups_table}` (
			`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			`license_id` BIGINT UNSIGNED NOT NULL,
			`activation_id` BIGINT UNSIGNED NOT NULL,
			`domain_hash` CHAR(64) NOT NULL,
			`backup_hash` CHAR(64) NOT NULL,
			`package_name` VARCHAR(255) NOT NULL,
			`file_size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
			`storage_provider` VARCHAR(32) NOT NULL DEFAULT 'dedicated-s3',
			`storage_path` VARCHAR(512) NOT NULL DEFAULT '',
			`wrapped_dek` TEXT NULL,
			`shamir_recovery_hint` VARCHAR(255) NULL,
			`retention_status` ENUM('active', 'grace_notice_1', 'grace_notice_2', 'grace_notice_3', 'purged') NOT NULL DEFAULT 'active',
			`retention_purge_scheduled_at` DATETIME NULL,
			`purged_at` DATETIME NULL,
			`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			INDEX `idx_license_id` (`license_id`),
			INDEX `idx_activation_id` (`activation_id`),
			INDEX `idx_domain_created` (`domain_hash`, `created_at`),
			INDEX `idx_retention` (`retention_status`, `retention_purge_scheduled_at`)
		) ENGINE=InnoDB {$charset};";

		$wpdb->query( $sql_licenses );
		$wpdb->query( $sql_activations );
		$wpdb->query( $sql_backups );

		return true;
	}
}
