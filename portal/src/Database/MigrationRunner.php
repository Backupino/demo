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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return true;
	}
}
