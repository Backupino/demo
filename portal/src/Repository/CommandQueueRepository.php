<?php
/**
 * Command Queue Repository for Pull-Only Customer Actions (D-042).
 *
 * @package Backupino\Portal\Repository
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Handles database operations for command queue.
 */
final class CommandQueueRepository {

	public const TABLE_NAME = 'backupino_command_queue';

	/**
	 * Get table name with prefix.
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Insert a new command into the queue.
	 *
	 * @param array<string, mixed> $data
	 * @return int|false
	 */
	public static function create( array $data ) {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$defaults = array(
			'command_id'    => 'cmd_' . bin2hex( random_bytes( 8 ) ),
			'license_id'    => 0,
			'activation_id' => 0,
			'action'        => 'BACKUP_NOW',
			'payload'       => '{}',
			'nonce'         => bin2hex( random_bytes( 16 ) ),
			'status'        => 'pending',
			'issued_at'     => $now,
			'expires_at'    => gmdate( 'Y-m-d H:i:s', time() + 600 ), // 10 minutes default TTL
			'created_at'    => $now,
		);

		$payload = array_merge( $defaults, $data );

		if ( is_array( $payload['payload'] ) ) {
			$payload['payload'] = wp_json_encode( $payload['payload'] );
		}

		$inserted = $wpdb->insert(
			$table,
			$payload,
			array(
				'%s',
				'%d',
				'%d',
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
	 * Find pending active commands for an activation.
	 *
	 * @param int $activation_id
	 * @return array<int, array<string, mixed>>
	 */
	public static function find_pending_by_activation_id( int $activation_id ): array {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `activation_id` = %d AND `status` = 'pending' AND `expires_at` > %s ORDER BY `issued_at` ASC",
				$activation_id,
				$now
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Find command by string command_id.
	 *
	 * @param string $command_id
	 * @return array<string, mixed>|null
	 */
	public static function find_by_command_id( string $command_id ): ?array {
		global $wpdb;

		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `command_id` = %s LIMIT 1",
				$command_id
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Update status of a command.
	 *
	 * @param string      $command_id
	 * @param string      $status
	 * @param array|null  $result_payload
	 * @return bool
	 */
	public static function mark_status( string $command_id, string $status, ?array $result_payload = null ): bool {
		global $wpdb;

		$table = self::table();
		$now   = current_time( 'mysql', true );

		$update_data = array(
			'status' => $status,
		);
		$update_formats = array( '%s' );

		if ( in_array( $status, array( 'completed', 'failed' ), true ) ) {
			$update_data['completed_at'] = $now;
			$update_formats[]            = '%s';
		}

		if ( null !== $result_payload ) {
			$update_data['result_payload'] = wp_json_encode( $result_payload );
			$update_formats[]              = '%s';
		}

		$updated = $wpdb->update(
			$table,
			$update_data,
			array( 'command_id' => $command_id ),
			$update_formats,
			array( '%s' )
		);

		return false !== $updated;
	}
}
