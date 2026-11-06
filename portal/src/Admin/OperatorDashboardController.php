<?php
/**
 * Internal Operator Dashboard Admin Controller.
 *
 * Implements S3-T09 wp-admin menus, views, and operator actions.
 *
 * @package Backupino\Portal\Admin
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Admin;

use Backupino\Portal\Auth\RoleManager;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates wp-admin menus, template loading, and administrative actions.
 */
final class OperatorDashboardController {

	public const MENU_SLUG_OVERVIEW    = 'backupino-portal';
	public const MENU_SLUG_ACTIVATIONS = 'backupino-activations';
	public const MENU_SLUG_SLA         = 'backupino-sla';
	public const MENU_SLUG_SETTLEMENTS = 'backupino-settlements';
	public const MENU_SLUG_SETTINGS    = 'backupino-settings';
	public const MENU_SLUG_AUDIT_LOGS  = 'backupino-audit-logs';

	/**
	 * Initialize admin hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'register_admin_menus' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_assets' ) );

		// AJAX endpoints
		add_action( 'wp_ajax_backupino_admin_override', array( self::class, 'ajax_perform_override' ) );
		add_action( 'wp_ajax_backupino_admin_save_flags', array( self::class, 'ajax_save_feature_flags' ) );
		add_action( 'wp_ajax_backupino_admin_save_matrix', array( self::class, 'ajax_save_version_matrix' ) );
		add_action( 'wp_ajax_backupino_operator_get_ticket', array( self::class, 'ajax_get_ticket' ) );
		add_action( 'wp_ajax_backupino_operator_reply_ticket', array( self::class, 'ajax_reply_ticket' ) );
		add_action( 'wp_ajax_backupino_operator_update_ticket_status', array( self::class, 'ajax_update_ticket_status' ) );
	}

	/**
	 * Register menus and submenus.
	 */
	public static function register_admin_menus(): void {
		// Main Top-level menu: accessible to Support and Admin
		add_menu_page(
			__( 'Backupino Portal', 'backupino-portal' ),
			__( 'Backupino Portal', 'backupino-portal' ),
			RoleManager::CAP_SUPPORT_VIEW,
			self::MENU_SLUG_OVERVIEW,
			array( self::class, 'render_overview_page' ),
			'dashicons-shield-alt',
			56
		);

		// Submenus
		add_submenu_page(
			self::MENU_SLUG_OVERVIEW,
			__( 'Licenses & Users', 'backupino-portal' ),
			__( 'Licenses & Users', 'backupino-portal' ),
			RoleManager::CAP_SUPPORT_VIEW,
			self::MENU_SLUG_OVERVIEW,
			array( self::class, 'render_overview_page' )
		);

		add_submenu_page(
			self::MENU_SLUG_OVERVIEW,
			__( 'Activation Logs', 'backupino-portal' ),
			__( 'Activation Logs', 'backupino-portal' ),
			RoleManager::CAP_SUPPORT_VIEW,
			self::MENU_SLUG_ACTIVATIONS,
			array( self::class, 'render_activations_page' )
		);

		add_submenu_page(
			self::MENU_SLUG_OVERVIEW,
			__( 'SLA & Support Queue', 'backupino-portal' ),
			__( 'SLA & Support Queue', 'backupino-portal' ),
			RoleManager::CAP_SUPPORT_VIEW,
			self::MENU_SLUG_SLA,
			array( self::class, 'render_sla_page' )
		);

		add_submenu_page(
			self::MENU_SLUG_OVERVIEW,
			__( 'Billing & Settlements', 'backupino-portal' ),
			__( 'Settlements', 'backupino-portal' ),
			RoleManager::CAP_SUPPORT_VIEW,
			self::MENU_SLUG_SETTLEMENTS,
			array( self::class, 'render_settlements_page' )
		);

		add_submenu_page(
			self::MENU_SLUG_OVERVIEW,
			__( 'Feature Flags & Matrix', 'backupino-portal' ),
			__( 'Feature Flags', 'backupino-portal' ),
			RoleManager::CAP_ADMIN_PORTAL, // Admin only
			self::MENU_SLUG_SETTINGS,
			array( self::class, 'render_settings_page' )
		);

		add_submenu_page(
			self::MENU_SLUG_OVERVIEW,
			__( 'Security Audit Logs', 'backupino-portal' ),
			__( 'Audit Logs', 'backupino-portal' ),
			RoleManager::CAP_SUPPORT_VIEW,
			self::MENU_SLUG_AUDIT_LOGS,
			array( self::class, 'render_audit_logs_page' )
		);
	}

	/**
	 * Enqueue admin styles.
	 */
	public static function enqueue_admin_assets( string $hook ): void {
		if ( false === strpos( $hook, 'backupino' ) ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		$css_path = dirname( __DIR__, 2 ) . '/assets/css/admin.css';
		if ( file_exists( $css_path ) ) {
			wp_add_inline_style( 'dashicons', (string) file_get_contents( $css_path ) );
		}
	}

	/**
	 * Render Overview & Licenses page.
	 */
	public static function render_overview_page(): void {
		self::check_permission( RoleManager::CAP_SUPPORT_VIEW );

		$metrics = OperatorDashboardService::get_metrics_summary();
		$page    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$search  = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$status  = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

		$licenses_data = OperatorDashboardService::get_licenses( $page, 20, $search, $status );
		$nonce         = wp_create_nonce( 'backupino_operator_nonce' );

		require dirname( __DIR__, 2 ) . '/templates/admin/overview.php';
	}

	/**
	 * Render Activation Logs page.
	 */
	public static function render_activations_page(): void {
		self::check_permission( RoleManager::CAP_SUPPORT_VIEW );

		$page   = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

		$data  = OperatorDashboardService::get_activations( $page, 25, $search );
		$nonce = wp_create_nonce( 'backupino_operator_nonce' );

		require dirname( __DIR__, 2 ) . '/templates/admin/activations.php';
	}

	/**
	 * Render SLA & Support Queue page.
	 */
	public static function render_sla_page(): void {
		self::check_permission( RoleManager::CAP_SUPPORT_VIEW );

		$page   = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

		$queue        = OperatorDashboardService::get_sla_queue();
		$tickets_data = OperatorDashboardService::get_operator_tickets( $page, 20, $status, $search );
		$nonce        = wp_create_nonce( 'backupino_operator_nonce' );

		require dirname( __DIR__, 2 ) . '/templates/admin/sla-queue.php';
	}

	/**
	 * Render Settlements page.
	 */
	public static function render_settlements_page(): void {
		self::check_permission( RoleManager::CAP_SUPPORT_VIEW );

		$settlements = OperatorDashboardService::get_settlements_summary();
		require dirname( __DIR__, 2 ) . '/templates/admin/settlements.php';
	}

	/**
	 * Render Settings & Feature Flags page.
	 */
	public static function render_settings_page(): void {
		self::check_permission( RoleManager::CAP_ADMIN_PORTAL );

		$flags  = OperatorDashboardService::get_feature_flags();
		$matrix = OperatorDashboardService::get_version_matrix();
		$nonce  = wp_create_nonce( 'backupino_operator_nonce' );

		require dirname( __DIR__, 2 ) . '/templates/admin/settings.php';
	}

	/**
	 * Render Audit Logs page.
	 */
	public static function render_audit_logs_page(): void {
		self::check_permission( RoleManager::CAP_SUPPORT_VIEW );

		$page   = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$action = isset( $_GET['action_filter'] ) ? sanitize_text_field( $_GET['action_filter'] ) : '';

		$logs = OperatorDashboardService::get_audit_logs( $page, 25, $action );
		require dirname( __DIR__, 2 ) . '/templates/admin/audit-logs.php';
	}

	/**
	 * AJAX: Admin Override License Parameters.
	 */
	public static function ajax_perform_override(): void {
		check_ajax_referer( 'backupino_operator_nonce', '_wpnonce' );

		if ( ! current_user_can( 'administrator' ) && ! current_user_can( RoleManager::CAP_ADMIN_PORTAL ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'backupino-portal' ) ), 403 );
		}

		$license_id = isset( $_POST['license_id'] ) ? (int) $_POST['license_id'] : 0;
		$reason     = isset( $_POST['reason'] ) ? sanitize_text_field( (string) $_POST['reason'] ) : '';
		$status     = isset( $_POST['status'] ) ? sanitize_text_field( (string) $_POST['status'] ) : '';
		$domains    = isset( $_POST['max_domains'] ) ? (int) $_POST['max_domains'] : -1;
		$quota_gb   = isset( $_POST['quota_gb'] ) ? (int) $_POST['quota_gb'] : -1;

		$params = array();
		if ( ! empty( $status ) ) {
			$params['status'] = $status;
		}
		if ( $domains > 0 ) {
			$params['max_domains'] = $domains;
		}
		if ( $quota_gb >= 0 ) {
			$params['cloud_quota_bytes'] = $quota_gb * 1073741824;
		}

		$res = OperatorDashboardService::perform_override( $license_id, $params, get_current_user_id(), $reason );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'message' => __( 'License parameters overridden successfully.', 'backupino-portal' ) ) );
	}

	/**
	 * AJAX: Save Feature Flags.
	 */
	public static function ajax_save_feature_flags(): void {
		check_ajax_referer( 'backupino_operator_nonce', '_wpnonce' );

		if ( ! current_user_can( 'administrator' ) && ! current_user_can( RoleManager::CAP_ADMIN_PORTAL ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'backupino-portal' ) ), 403 );
		}

		$flags = array(
			'allow_trial_registration'   => ! empty( $_POST['allow_trial_registration'] ),
			'cloud_storage_enabled'       => ! empty( $_POST['cloud_storage_enabled'] ),
			'strict_cooling_off_enabled' => ! empty( $_POST['strict_cooling_off_enabled'] ),
			'key_rotation_auto'          => ! empty( $_POST['key_rotation_auto'] ),
		);

		OperatorDashboardService::update_feature_flags( $flags, get_current_user_id() );
		wp_send_json_success( array( 'message' => __( 'Feature flags updated successfully.', 'backupino-portal' ) ) );
	}

	/**
	 * AJAX: Save Version Matrix.
	 */
	public static function ajax_save_version_matrix(): void {
		check_ajax_referer( 'backupino_operator_nonce', '_wpnonce' );

		if ( ! current_user_can( 'administrator' ) && ! current_user_can( RoleManager::CAP_ADMIN_PORTAL ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'backupino-portal' ) ), 403 );
		}

		$matrix = array(
			'pro_latest_version'       => sanitize_text_field( (string) ( $_POST['pro_latest_version'] ?? '1.0.0' ) ),
			'pro_min_required_version' => sanitize_text_field( (string) ( $_POST['pro_min_required_version'] ?? '1.0.0' ) ),
			'wp_tested_up_to'          => sanitize_text_field( (string) ( $_POST['wp_tested_up_to'] ?? '6.7' ) ),
			'php_min_version'          => sanitize_text_field( (string) ( $_POST['php_min_version'] ?? '8.1' ) ),
			'released_at'              => sanitize_text_field( (string) ( $_POST['released_at'] ?? current_time( 'Y-m-d' ) ) ),
		);

		OperatorDashboardService::update_version_matrix( $matrix, get_current_user_id() );
		wp_send_json_success( array( 'message' => __( 'Version matrix updated successfully.', 'backupino-portal' ) ) );
	}

	/**
	 * AJAX: Get ticket details for operator drawer/modal.
	 */
	public static function ajax_get_ticket(): void {
		check_ajax_referer( 'backupino_operator_nonce', '_wpnonce' );
		self::check_permission( RoleManager::CAP_SUPPORT_VIEW );

		$ticket_id = isset( $_POST['ticket_id'] ) ? (int) $_POST['ticket_id'] : 0;
		$ticket    = OperatorDashboardService::get_ticket_with_replies( $ticket_id );

		if ( ! $ticket ) {
			wp_send_json_error( array( 'message' => __( 'Ticket not found.', 'backupino-portal' ) ), 404 );
		}

		wp_send_json_success( $ticket );
	}

	/**
	 * AJAX: Operator reply to customer ticket.
	 */
	public static function ajax_reply_ticket(): void {
		check_ajax_referer( 'backupino_operator_nonce', '_wpnonce' );
		self::check_permission( RoleManager::CAP_SUPPORT_VIEW );

		$ticket_id   = isset( $_POST['ticket_id'] ) ? (int) $_POST['ticket_id'] : 0;
		$message     = isset( $_POST['message'] ) ? (string) $_POST['message'] : '';
		$new_status  = isset( $_POST['new_status'] ) ? sanitize_text_field( (string) $_POST['new_status'] ) : 'waiting_customer';
		$operator_id = get_current_user_id();

		$att_url  = null;
		$att_name = null;
		if ( ! empty( $_FILES['attachment'] ) && ! empty( $_FILES['attachment']['name'] ) ) {
			$upload = \Backupino\Portal\Ui\CustomerDashboardService::handle_file_attachment( $_FILES['attachment'] );
			if ( is_wp_error( $upload ) ) {
				wp_send_json_error( array( 'message' => $upload->get_error_message() ), 400 );
			}
			$att_url  = $upload['url'];
			$att_name = $upload['name'];
		}

		$res = OperatorDashboardService::operator_reply_ticket( $operator_id, $ticket_id, $message, $new_status, $att_url, $att_name );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'message' => __( 'Staff reply sent successfully.', 'backupino-portal' ) ) );
	}

	/**
	 * AJAX: Operator update ticket status.
	 */
	public static function ajax_update_ticket_status(): void {
		check_ajax_referer( 'backupino_operator_nonce', '_wpnonce' );
		self::check_permission( RoleManager::CAP_SUPPORT_VIEW );

		$ticket_id   = isset( $_POST['ticket_id'] ) ? (int) $_POST['ticket_id'] : 0;
		$status      = isset( $_POST['status'] ) ? sanitize_text_field( (string) $_POST['status'] ) : 'open';
		$operator_id = get_current_user_id();

		$res = OperatorDashboardService::operator_update_ticket_status( $operator_id, $ticket_id, $status );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'message' => __( 'Ticket status updated successfully.', 'backupino-portal' ) ) );
	}

	/**
	 * Check capability or die.
	 */
	private static function check_permission( string $cap ): void {
		if ( ! current_user_can( $cap ) && ! current_user_can( 'administrator' ) ) {
			wp_die( esc_html__( 'You do not have permission to access the Backupino Operator Portal.', 'backupino-portal' ), 403 );
		}
	}
}
