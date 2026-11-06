<?php
/**
 * Internal Operator Dashboard Service.
 *
 * Implements S3-T09 and D-042 operator/support management backend.
 *
 * @package Backupino\Portal\Admin
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Admin;

use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\Repository\ActivationRepository;
use Backupino\Portal\Lifecycle\RenewalService;
use Backupino\Portal\Lifecycle\SlaManager;
use Backupino\Portal\Audit\AuditLogger;
use Backupino\Portal\Auth\RoleManager;
use Backupino\Portal\Auth\AuthorizationGuard;

defined( 'ABSPATH' ) || exit;

/**
 * Handles operator queries, metric summaries, audit log retrieval, and feature flags.
 */
final class OperatorDashboardService {

	public const OPTION_FEATURE_FLAGS   = 'backupino_feature_flags';
	public const OPTION_VERSION_MATRIX  = 'backupino_version_matrix';

	/**
	 * Default feature flags.
	 */
	public const DEFAULT_FEATURE_FLAGS = array(
		'allow_trial_registration'   => true,
		'cloud_storage_enabled'       => true,
		'strict_cooling_off_enabled' => true, // 72-hour cooling off (D-006)
		'key_rotation_auto'          => false,
	);

	/**
	 * Default Pro client compatibility matrix.
	 */
	public const DEFAULT_VERSION_MATRIX = array(
		'pro_latest_version'        => '1.0.0',
		'pro_min_required_version'  => '1.0.0',
		'wp_tested_up_to'           => '6.7',
		'php_min_version'           => '8.1',
		'released_at'               => '2026-11-06',
	);

	/**
	 * Get high-level KPI and system metrics for operator dashboard.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_metrics_summary(): array {
		global $wpdb;

		$lic_table = LicenseRepository::table();
		$act_table = ActivationRepository::table();
		$aud_table = $wpdb->prefix . 'backupino_audit_logs';

		$total_licenses = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$lic_table}`" );
		$active_licenses = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$lic_table}` WHERE `status` = 'active'" );
		$expiring_soon  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$lic_table}` WHERE `status` = 'expiring_soon'" );
		$grace_period   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$lic_table}` WHERE `status` = 'grace_period'" );
		$downgraded     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$lic_table}` WHERE `status` = 'downgraded'" );

		$total_activations = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$act_table}` WHERE `is_active` = 1" );
		$staging_activations = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$act_table}` WHERE `is_active` = 1 AND `is_staging` = 1" );

		$cloud_used_bytes = (int) $wpdb->get_var( "SELECT SUM(`cloud_used_bytes`) FROM `{$lic_table}`" );

		$total_audit_events = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$aud_table}`" );

		return array(
			'total_licenses'      => $total_licenses,
			'active_licenses'     => $active_licenses,
			'expiring_soon'       => $expiring_soon,
			'grace_period'        => $grace_period,
			'downgraded'          => $downgraded,
			'total_activations'   => $total_activations,
			'staging_activations' => $staging_activations,
			'cloud_used_bytes'    => $cloud_used_bytes,
			'formatted_storage'   => self::format_bytes( $cloud_used_bytes ),
			'total_audit_events'  => $total_audit_events,
		);
	}

	/**
	 * Get paginated list of licenses.
	 *
	 * @param int    $page
	 * @param int    $per_page
	 * @param string $search
	 * @param string $status
	 * @return array{items: array, total: int, total_pages: int}
	 */
	public static function get_licenses( int $page = 1, int $per_page = 20, string $search = '', string $status = '' ): array {
		global $wpdb;

		$table  = LicenseRepository::table();
		$offset = max( 0, ( $page - 1 ) * $per_page );
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $search ) ) {
			$where[]  = '( `license_key` LIKE %s OR `plan_slug` LIKE %s )';
			$like     = '%' . $wpdb->esc_like( trim( $search ) ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $status ) ) {
			$where[]  = '`status` = %s';
			$params[] = trim( $status );
		}

		$where_sql = implode( ' AND ', $where );

		$count_query = "SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}";
		$total       = ! empty( $params )
			? (int) $wpdb->get_var( $wpdb->prepare( $count_query, ...$params ) )
			: (int) $wpdb->get_var( $count_query );

		$data_query = "SELECT * FROM `{$table}` WHERE {$where_sql} ORDER BY `id` DESC LIMIT %d, %d";
		$query_params = array_merge( $params, array( $offset, $per_page ) );
		$rows = $wpdb->get_results( $wpdb->prepare( $data_query, ...$query_params ), ARRAY_A );

		$items = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$user = get_userdata( (int) $row['user_id'] );
				$row['user_email']        = $user ? $user->user_email : '&mdash;';
				$row['user_display_name'] = $user ? $user->display_name : '&mdash;';
				$items[]                  = $row;
			}
		}

		return array(
			'items'       => $items,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Get paginated list of domain activations.
	 *
	 * @param int    $page
	 * @param int    $per_page
	 * @param string $search
	 * @return array{items: array, total: int, total_pages: int}
	 */
	public static function get_activations( int $page = 1, int $per_page = 20, string $search = '' ): array {
		global $wpdb;

		$table  = ActivationRepository::table();
		$lic_t  = LicenseRepository::table();
		$offset = max( 0, ( $page - 1 ) * $per_page );
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $search ) ) {
			$where[]  = '( `domain_name` LIKE %s OR `domain_hash` LIKE %s )';
			$like     = '%' . $wpdb->esc_like( trim( $search ) ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );

		$count_query = "SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}";
		$total       = ! empty( $params )
			? (int) $wpdb->get_var( $wpdb->prepare( $count_query, ...$params ) )
			: (int) $wpdb->get_var( $count_query );

		$data_query = "SELECT a.*, l.license_key, l.plan_slug 
			FROM `{$table}` a
			LEFT JOIN `{$lic_t}` l ON a.license_id = l.id
			WHERE {$where_sql} 
			ORDER BY a.id DESC LIMIT %d, %d";

		$query_params = array_merge( $params, array( $offset, $per_page ) );
		$rows         = $wpdb->get_results( $wpdb->prepare( $data_query, ...$query_params ), ARRAY_A );

		return array(
			'items'       => is_array( $rows ) ? $rows : array(),
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Get paginated audit logs from `wp_backupino_audit_logs`.
	 *
	 * @param int    $page
	 * @param int    $per_page
	 * @param string $action_filter
	 * @return array{items: array, total: int, total_pages: int}
	 */
	public static function get_audit_logs( int $page = 1, int $per_page = 25, string $action_filter = '' ): array {
		global $wpdb;

		$table  = $wpdb->prefix . 'backupino_audit_logs';
		$offset = max( 0, ( $page - 1 ) * $per_page );
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $action_filter ) ) {
			$where[]  = '`action` = %s';
			$params[] = trim( $action_filter );
		}

		$where_sql = implode( ' AND ', $where );

		$count_query = "SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}";
		$total       = ! empty( $params )
			? (int) $wpdb->get_var( $wpdb->prepare( $count_query, ...$params ) )
			: (int) $wpdb->get_var( $count_query );

		$data_query = "SELECT * FROM `{$table}` WHERE {$where_sql} ORDER BY `id` DESC LIMIT %d, %d";
		$query_params = array_merge( $params, array( $offset, $per_page ) );
		$rows = $wpdb->get_results( $wpdb->prepare( $data_query, ...$query_params ), ARRAY_A );

		$items = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$user = $row['user_id'] ? get_userdata( (int) $row['user_id'] ) : null;
				$row['actor_name'] = $user ? $user->user_login : 'System/API';
				$row['context_parsed'] = ! empty( $row['context'] ) ? json_decode( $row['context'], true ) : array();
				$items[] = $row;
			}
		}

		return array(
			'items'       => $items,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Get SLA Support Queue and Priority Tiers.
	 *
	 * @return array<array<string, mixed>>
	 */
	public static function get_sla_queue(): array {
		global $wpdb;

		$lic_table = LicenseRepository::table();
		// Fetch active licenses to evaluate SLA monitoring
		$rows = $wpdb->get_results(
			"SELECT id, user_id, license_key, plan_slug, created_at, updated_at, status 
			 FROM `{$lic_table}` 
			 WHERE `status` IN ('active', 'expiring_soon') 
			 ORDER BY `id` DESC LIMIT 50",
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$queue = array();
		$now   = time();

		foreach ( $rows as $row ) {
			$start_time = strtotime( $row['created_at'] );
			$metrics    = SlaManager::get_sla_metrics( $row['plan_slug'], $start_time, null, $now );
			$user       = get_userdata( (int) $row['user_id'] );

			$queue[] = array(
				'license_id'            => (int) $row['id'],
				'license_key'           => $row['license_key'],
				'user_email'            => $user ? $user->user_email : 'Unknown',
				'plan_slug'             => $row['plan_slug'],
				'has_sla'               => $metrics['has_sla'],
				'sla_hours'             => $metrics['sla_hours'],
				'deadline'              => $metrics['deadline'] ? gmdate( 'Y-m-d H:i:s', $metrics['deadline'] ) : null,
				'remaining_seconds'     => $metrics['remaining_seconds'],
				'is_breached'           => $metrics['is_breached'],
				'is_approaching_breach' => $metrics['is_approaching_breach'],
				'priority_tier'         => $metrics['priority_tier'],
			);
		}

		// Sort by priority: critical_dedicated first, then high, then standard
		usort( $queue, function ( $a, $b ) {
			$priority_weight = array(
				'critical_dedicated' => 3,
				'high'               => 2,
				'standard'           => 1,
			);
			$w_a = $priority_weight[ $a['priority_tier'] ] ?? 0;
			$w_b = $priority_weight[ $b['priority_tier'] ] ?? 0;

			if ( $w_a === $w_b ) {
				return ( $a['remaining_seconds'] ?? 0 ) <=> ( $b['remaining_seconds'] ?? 0 );
			}
			return $w_b <=> $w_a;
		} );

		return $queue;
	}

	/**
	 * Get WooCommerce billing & settlement summary for operator.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settlements_summary(): array {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array(
				'total_orders'    => 0,
				'total_revenue'   => 0,
				'refunded_count'  => 0,
				'recent_orders'   => array(),
			);
		}

		$orders = wc_get_orders(
			array(
				'limit'   => 15,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		$recent = array();
		$total_rev = 0.0;

		foreach ( $orders as $order ) {
			$total_rev += (float) $order->get_total();
			$recent[] = array(
				'order_id'       => $order->get_id(),
				'order_number'   => $order->get_order_number(),
				'customer_email' => $order->get_billing_email(),
				'status'         => $order->get_status(),
				'total'          => $order->get_formatted_order_total(),
				'date'           => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i' ) : '',
				'is_renewal'     => (bool) $order->get_meta( '_backupino_is_renewal' ),
			);
		}

		return array(
			'total_orders'   => count( $orders ),
			'total_revenue'  => $total_rev,
			'recent_orders'  => $recent,
		);
	}

	/**
	 * Get Feature Flags.
	 *
	 * @return array<string, bool>
	 */
	public static function get_feature_flags(): array {
		$saved = get_option( self::OPTION_FEATURE_FLAGS, array() );
		return array_merge( self::DEFAULT_FEATURE_FLAGS, is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Update Feature Flags.
	 *
	 * @param array<string, bool> $flags
	 * @param int                 $actor_id
	 * @return bool
	 */
	public static function update_feature_flags( array $flags, int $actor_id ): bool {
		$merged = array_merge( self::get_feature_flags(), $flags );
		update_option( self::OPTION_FEATURE_FLAGS, $merged );

		AuditLogger::log(
			'feature_flags_updated',
			$actor_id,
			0,
			null,
			array( 'flags' => $merged )
		);

		return true;
	}

	/**
	 * Get Version Compatibility Matrix.
	 *
	 * @return array<string, string>
	 */
	public static function get_version_matrix(): array {
		$saved = get_option( self::OPTION_VERSION_MATRIX, array() );
		return array_merge( self::DEFAULT_VERSION_MATRIX, is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Update Version Compatibility Matrix.
	 *
	 * @param array<string, string> $matrix
	 * @param int                   $actor_id
	 * @return bool
	 */
	public static function update_version_matrix( array $matrix, int $actor_id ): bool {
		$merged = array_merge( self::get_version_matrix(), $matrix );
		update_option( self::OPTION_VERSION_MATRIX, $merged );

		AuditLogger::log(
			'version_matrix_updated',
			$actor_id,
			0,
			null,
			array( 'matrix' => $merged )
		);

		return true;
	}

	/**
	 * Perform quota or status override on license with mandatory audit reason.
	 *
	 * @param int                  $license_id
	 * @param array<string, mixed> $params
	 * @param int                  $admin_user_id
	 * @param string               $reason
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function perform_override( int $license_id, array $params, int $admin_user_id, string $reason ) {
		return RenewalService::admin_override( $license_id, $params, $admin_user_id, $reason );
	}

	/**
	 * Get paginated customer support tickets for operator inbox.
	 *
	 * @param int    $page
	 * @param int    $per_page
	 * @param string $status
	 * @param string $search
	 * @return array{items: array, total: int, total_pages: int, stats: array}
	 */
	public static function get_operator_tickets( int $page = 1, int $per_page = 20, string $status = '', string $search = '' ): array {
		global $wpdb;

		$table_tkt = $wpdb->prefix . 'backupino_tickets';
		$table_rep = $wpdb->prefix . 'backupino_ticket_replies';

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $status ) && 'all' !== $status ) {
			$where[]  = 't.`status` = %s';
			$params[] = sanitize_text_field( $status );
		}

		if ( ! empty( $search ) ) {
			$where[]  = '(t.`ticket_number` LIKE %s OR t.`subject` LIKE %s)';
			$wildcard = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $wildcard;
			$params[] = $wildcard;
		}

		$where_sql = implode( ' AND ', $where );
		$offset    = max( 0, ( $page - 1 ) * $per_page );

		// Stats
		$open_count     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_tkt}` WHERE `status` = 'open'" );
		$prog_count     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_tkt}` WHERE `status` = 'in_progress'" );
		$waiting_count  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_tkt}` WHERE `status` = 'waiting_customer'" );
		$resolved_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_tkt}` WHERE `status` IN ('resolved', 'closed')" );

		$count_query = "SELECT COUNT(*) FROM `{$table_tkt}` t WHERE {$where_sql}";
		$total       = ! empty( $params )
			? (int) $wpdb->get_var( $wpdb->prepare( $count_query, ...$params ) )
			: (int) $wpdb->get_var( $count_query );

		$data_query = "SELECT t.*, 
			(SELECT COUNT(*) FROM `{$table_rep}` r WHERE r.`ticket_id` = t.`id`) as replies_count 
			FROM `{$table_tkt}` t 
			WHERE {$where_sql} 
			ORDER BY CASE WHEN t.`status` IN ('open', 'in_progress') THEN 0 ELSE 1 END, t.`id` DESC 
			LIMIT %d, %d";

		$query_params = array_merge( $params, array( $offset, $per_page ) );
		$rows = $wpdb->get_results( $wpdb->prepare( $data_query, ...$query_params ), ARRAY_A );

		$items = array();
		$now   = time();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$user = get_userdata( (int) $row['user_id'] );
				$row['customer_name']  = $user ? $user->display_name : 'Customer #' . $row['user_id'];
				$row['customer_email'] = $user ? $user->user_email : 'Unknown';

				// SLA Remaining
				$row['is_breached'] = false;
				$row['remaining_formatted'] = '';

				if ( ! empty( $row['sla_deadline_at'] ) ) {
					$deadline_ts = strtotime( $row['sla_deadline_at'] );
					if ( $now > $deadline_ts && ! in_array( $row['status'], array( 'resolved', 'closed' ), true ) ) {
						$row['is_breached'] = true;
						$row['remaining_formatted'] = __( 'SLA Breached', 'backupino-portal' );
					} else {
						$rem_sec = max( 0, $deadline_ts - $now );
						$rem_hours = floor( $rem_sec / 3600 );
						$rem_mins  = floor( ( $rem_sec % 3600 ) / 60 );
						$row['remaining_formatted'] = sprintf( __( '%d hours %d mins', 'backupino-portal' ), $rem_hours, $rem_mins );
					}
				}

				$items[] = $row;
			}
		}

		return array(
			'items'       => $items,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'stats'       => array(
				'open'             => $open_count,
				'in_progress'      => $prog_count,
				'waiting_customer' => $waiting_count,
				'resolved'         => $resolved_count,
			),
		);
	}

	/**
	 * Get full ticket conversation for operator review.
	 *
	 * @param int $ticket_id
	 * @return array<string, mixed>|null
	 */
	public static function get_ticket_with_replies( int $ticket_id ): ?array {
		global $wpdb;

		$table_tkt = $wpdb->prefix . 'backupino_tickets';
		$table_rep = $wpdb->prefix . 'backupino_ticket_replies';

		$ticket = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table_tkt}` WHERE `id` = %d LIMIT 1", $ticket_id ),
			ARRAY_A
		);

		if ( ! $ticket ) {
			return null;
		}

		$user = get_userdata( (int) $ticket['user_id'] );
		$ticket['customer_name']  = $user ? $user->display_name : 'Customer';
		$ticket['customer_email'] = $user ? $user->user_email : '';

		$replies_raw = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM `{$table_rep}` WHERE `ticket_id` = %d ORDER BY `id` ASC", $ticket_id ),
			ARRAY_A
		);

		$replies = array();
		if ( is_array( $replies_raw ) ) {
			foreach ( $replies_raw as $r ) {
				$sender = get_userdata( (int) $r['user_id'] );
				$replies[] = array(
					'id'              => (int) $r['id'],
					'is_staff'        => (bool) $r['is_staff'],
					'sender_name'     => (bool) $r['is_staff'] ? ( $sender ? $sender->display_name : __( 'Support Staff', 'backupino-portal' ) ) : ( $user ? $user->display_name : __( 'Customer', 'backupino-portal' ) ),
					'message'         => wp_kses_post( $r['message'] ),
					'attachment_url'  => $r['attachment_url'] ?? null,
					'attachment_name' => $r['attachment_name'] ?? null,
					'created_at'      => $r['created_at'],
				);
			}
		}

		$ticket['replies'] = $replies;

		return $ticket;
	}

	/**
	 * Post official operator reply to a ticket.
	 *
	 * @param int         $operator_user_id
	 * @param int         $ticket_id
	 * @param string      $message
	 * @param string      $new_status
	 * @param string|null $attachment_url
	 * @param string|null $attachment_name
	 * @return bool|\WP_Error
	 */
	public static function operator_reply_ticket( int $operator_user_id, int $ticket_id, string $message, string $new_status = 'waiting_customer', ?string $attachment_url = null, ?string $attachment_name = null ) {
		global $wpdb;

		$table_tkt = $wpdb->prefix . 'backupino_tickets';
		$table_rep = $wpdb->prefix . 'backupino_ticket_replies';

		$ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table_tkt}` WHERE `id` = %d", $ticket_id ), ARRAY_A );
		if ( ! $ticket ) {
			return new \WP_Error( 'TICKET_NOT_FOUND', __( 'Ticket not found.', 'backupino-portal' ) );
		}

		$message = trim( $message );
		if ( empty( $message ) && empty( $attachment_url ) ) {
			return new \WP_Error( 'EMPTY_MESSAGE', __( 'Message is required.', 'backupino-portal' ) );
		}

		$now = current_time( 'mysql', true );

		$reply_data = array(
			'ticket_id'  => $ticket_id,
			'user_id'    => $operator_user_id,
			'is_staff'   => 1,
			'message'    => wp_kses_post( $message ),
			'created_at' => $now,
		);
		$formats = array( '%d', '%d', '%d', '%s', '%s' );

		if ( ! empty( $attachment_url ) ) {
			$reply_data['attachment_url']  = esc_url_raw( $attachment_url );
			$reply_data['attachment_name'] = sanitize_file_name( (string) $attachment_name );
			$formats[] = '%s';
			$formats[] = '%s';
		}

		$inserted = $wpdb->insert( $table_rep, $reply_data, $formats );

		if ( false === $inserted ) {
			return new \WP_Error( 'INSERT_FAILED', __( 'Failed to save reply.', 'backupino-portal' ) );
		}

		// Update ticket status
		$allowed_statuses = array( 'open', 'in_progress', 'waiting_customer', 'resolved', 'closed' );
		if ( ! in_array( $new_status, $allowed_statuses, true ) ) {
			$new_status = 'waiting_customer';
		}

		$wpdb->update(
			$table_tkt,
			array(
				'status'     => $new_status,
				'updated_at' => $now,
			),
			array( 'id' => $ticket_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		AuditLogger::log(
			'ticket_replied_by_operator',
			$operator_user_id,
			(int) $ticket['license_id'],
			null,
			array(
				'ticket_id'     => $ticket_id,
				'ticket_number' => $ticket['ticket_number'],
				'new_status'    => $new_status,
			)
		);

		return true;
	}

	/**
	 * Update ticket status by operator.
	 *
	 * @param int    $operator_user_id
	 * @param int    $ticket_id
	 * @param string $status
	 * @return bool|\WP_Error
	 */
	public static function operator_update_ticket_status( int $operator_user_id, int $ticket_id, string $status ) {
		global $wpdb;

		$table_tkt = $wpdb->prefix . 'backupino_tickets';

		$allowed = array( 'open', 'in_progress', 'waiting_customer', 'resolved', 'closed' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return new \WP_Error( 'INVALID_STATUS', __( 'Invalid ticket status.', 'backupino-portal' ) );
		}

		$now     = current_time( 'mysql', true );
		$updated = $wpdb->update(
			$table_tkt,
			array(
				'status'     => $status,
				'updated_at' => $now,
			),
			array( 'id' => $ticket_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new \WP_Error( 'UPDATE_FAILED', __( 'Could not update ticket status.', 'backupino-portal' ) );
		}

		AuditLogger::log(
			'ticket_status_changed_by_operator',
			$operator_user_id,
			0,
			null,
			array(
				'ticket_id'  => $ticket_id,
				'new_status' => $status,
			)
		);

		return true;
	}

	/**
	 * Verify that operator/support can NEVER trigger backup or restore on customer sites (D-042).
	 *
	 * @param int $operator_user_id
	 * @param int $customer_license_id
	 * @return bool Always returns FALSE strictly per D-042!
	 */
	public static function can_operator_restore( int $operator_user_id, int $customer_license_id ): bool {
		return AuthorizationGuard::can_trigger_restore( $operator_user_id, $customer_license_id );
	}

	/**
	 * Helper to format bytes into readable unit.
	 */
	private static function format_bytes( int $bytes ): string {
		if ( $bytes >= 1073741824 ) {
			return sprintf( '%.2f GB', $bytes / 1073741824 );
		}
		if ( $bytes >= 1048576 ) {
			return sprintf( '%.1f MB', $bytes / 1048576 );
		}
		if ( $bytes >= 1024 ) {
			return sprintf( '%d KB', round( $bytes / 1024 ) );
		}
		return sprintf( '%d B', $bytes );
	}
}
