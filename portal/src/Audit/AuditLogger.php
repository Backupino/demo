<?php
/**
 * Audit Logger Service.
 *
 * @package Backupino\Portal\Audit
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Audit;

use Backupino\Portal\Config\Environment;

defined( 'ABSPATH' ) || exit;

/**
 * Records system and security events.
 */
final class AuditLogger {

	/**
	 * Log an action into the audit database.
	 *
	 * @param string               $action      Action identifier (e.g. 'license_activated', 'keypair_rotated').
	 * @param int                  $user_id     Actor user ID.
	 * @param int                  $license_id  Target license ID (if applicable).
	 * @param string|null          $domain_hash SHA-256 hash of domain.
	 * @param array<string, mixed> $context     Detailed context data.
	 * @return bool
	 */
	public static function log(
		string $action,
		int $user_id = 0,
		int $license_id = 0,
		?string $domain_hash = null,
		array $context = array()
	): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'backupino_audit_logs';

		$inserted = $wpdb->insert(
			$table,
			array(
				'user_id'     => $user_id,
				'license_id'  => $license_id,
				'action'      => sanitize_key( $action ),
				'domain_hash' => $domain_hash ? substr( $domain_hash, 0, 64 ) : null,
				'context'     => ! empty( $context ) ? wp_json_encode( $context ) : null,
				'created_at'  => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( Environment::is_debug() ) {
			error_log( sprintf( '[Backupino Portal Audit] action=%s user=%d license=%d', $action, $user_id, $license_id ) );
		}

		return false !== $inserted;
	}
}
