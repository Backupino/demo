<?php
/**
 * Support Ticket Repository.
 *
 * Direct database access layer for `wp_backupino_tickets` and `wp_backupino_ticket_replies`.
 *
 * @package Backupino\Portal\Repository
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Repository;

use Backupino\Portal\Models\Ticket;
use Backupino\Portal\Lifecycle\SlaManager;

defined( 'ABSPATH' ) || exit;

/**
 * Handles database operations for tickets and replies.
 */
final class TicketRepository {

	public const TABLE_TICKETS = 'backupino_tickets';
	public const TABLE_REPLIES = 'backupino_ticket_replies';

	public static function tickets_table(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_TICKETS;
	}

	public static function replies_table(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_REPLIES;
	}

	/**
	 * Find ticket by ID.
	 */
	public static function find_by_id( int $id ): ?Ticket {
		global $wpdb;

		$table = self::tickets_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE `id` = %d LIMIT 1", $id ),
			ARRAY_A
		);

		return $row ? new Ticket( $row ) : null;
	}

	/**
	 * Find tickets by user ID.
	 *
	 * @param int $user_id
	 * @return Ticket[]
	 */
	public static function find_by_user( int $user_id ): array {
		global $wpdb;

		$table = self::tickets_table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE `user_id` = %d ORDER BY `id` DESC", $user_id ),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( fn( $row ) => new Ticket( $row ), $rows );
	}

	/**
	 * Create a new support ticket.
	 *
	 * @param array<string, mixed> $data
	 * @return int|false
	 */
	public static function create( array $data ) {
		global $wpdb;

		$table = self::tickets_table();
		$now   = current_time( 'mysql', true );

		$plan_slug = (string) ( $data['plan_slug'] ?? 'personal' );
		$deadline  = SlaManager::calculate_deadline( $plan_slug, time() );

		$ticket_num = 'TKT-' . gmdate( 'Ymd' ) . '-' . strtoupper( bin2hex( random_bytes( 3 ) ) );

		$priority = 'standard';
		if ( 'agency' === $plan_slug ) {
			$priority = 'critical_dedicated';
		} elseif ( 'freelancer' === $plan_slug ) {
			$priority = 'high';
		}

		$payload = array(
			'ticket_number'   => $ticket_num,
			'user_id'         => (int) ( $data['user_id'] ?? 0 ),
			'license_id'      => (int) ( $data['license_id'] ?? 0 ),
			'plan_slug'       => $plan_slug,
			'subject'         => sanitize_text_field( (string) ( $data['subject'] ?? '' ) ),
			'priority'        => $priority,
			'status'          => 'open',
			'sla_deadline_at' => $deadline ? gmdate( 'Y-m-d H:i:s', $deadline ) : null,
			'sla_breached'    => 0,
			'created_at'      => $now,
			'updated_at'      => $now,
		);

		$inserted = $wpdb->insert(
			$table,
			$payload,
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return false;
		}

		$ticket_id = (int) $wpdb->insert_id;

		// If initial message provided, insert reply
		if ( ! empty( $data['message'] ) ) {
			self::add_reply( $ticket_id, (int) $payload['user_id'], (string) $data['message'], 0 );
		}

		return $ticket_id;
	}

	/**
	 * Add a reply to a ticket.
	 */
	public static function add_reply( int $ticket_id, int $user_id, string $message, int $is_staff = 0, ?string $attachment_url = null, ?string $attachment_name = null ): bool {
		global $wpdb;

		$table = self::replies_table();
		$now   = current_time( 'mysql', true );

		$data = array(
			'ticket_id'  => $ticket_id,
			'user_id'    => $user_id,
			'is_staff'   => $is_staff,
			'message'    => wp_kses_post( $message ),
			'created_at' => $now,
		);
		$formats = array( '%d', '%d', '%d', '%s', '%s' );

		if ( ! empty( $attachment_url ) ) {
			$data['attachment_url']  = esc_url_raw( $attachment_url );
			$data['attachment_name'] = sanitize_file_name( (string) $attachment_name );
			$formats[] = '%s';
			$formats[] = '%s';
		}

		$inserted = $wpdb->insert( $table, $data, $formats );

		if ( false === $inserted ) {
			return false;
		}

		// Touch updated_at on ticket
		$wpdb->update(
			self::tickets_table(),
			array(
				'updated_at' => $now,
				'status'     => $is_staff ? 'waiting_customer' : 'open',
			),
			array( 'id' => $ticket_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return true;
	}

	/**
	 * Close ticket.
	 */
	public static function close_ticket( int $ticket_id, int $user_id, bool $is_operator = false ): bool {
		global $wpdb;

		$table = self::tickets_table();
		$where = array( 'id' => $ticket_id );
		$where_format = array( '%d' );

		if ( ! $is_operator ) {
			$where['user_id'] = $user_id;
			$where_format[]   = '%d';
		}

		$now     = current_time( 'mysql', true );
		$updated = $wpdb->update(
			$table,
			array(
				'status'     => 'closed',
				'updated_at' => $now,
			),
			$where,
			array( '%s', '%s' ),
			$where_format
		);

		return false !== $updated;
	}

	/**
	 * Get replies for a ticket.
	 *
	 * @param int $ticket_id
	 * @return array<array<string, mixed>>
	 */
	public static function get_replies( int $ticket_id ): array {
		global $wpdb;

		$table = self::replies_table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE `ticket_id` = %d ORDER BY `id` ASC", $ticket_id ),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return $rows;
	}
}
