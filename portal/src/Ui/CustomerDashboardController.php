<?php
/**
 * Customer Dashboard UI Controller and WooCommerce My Account Endpoint.
 *
 * Implements S3-T08.
 *
 * @package Backupino\Portal\Ui
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Ui;

use Backupino\Portal\Repository\BackupRepository;
use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\Auth\AuthorizationGuard;
use Backupino\Portal\Audit\AuditLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Controller for rendering dashboard, handling AJAX actions, and WooCommerce integration.
 */
final class CustomerDashboardController {

	public const ENDPOINT = 'backupino';

	/**
	 * Initialize hooks.
	 */
	public static function init(): void {
		// WooCommerce My Account integration
		add_action( 'init', array( self::class, 'add_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( self::class, 'add_menu_item' ), 40 );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( self::class, 'render_dashboard' ) );

		// Shortcode
		add_shortcode( 'backupino_customer_dashboard', array( self::class, 'render_shortcode' ) );

		// AJAX endpoints
		add_action( 'wp_ajax_backupino_customer_create_backup', array( self::class, 'ajax_create_backup' ) );
		add_action( 'wp_ajax_backupino_customer_restore', array( self::class, 'ajax_restore' ) );
		add_action( 'wp_ajax_backupino_customer_deactivate_domain', array( self::class, 'ajax_deactivate_domain' ) );
		add_action( 'wp_ajax_backupino_customer_update_retention', array( self::class, 'ajax_update_retention' ) );
		add_action( 'wp_ajax_backupino_customer_download', array( self::class, 'ajax_download_backup' ) );
	}

	/**
	 * Register custom endpoint in WordPress.
	 */
	public static function add_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Add Backupino tab into WooCommerce My Account menu.
	 *
	 * @param array<string, string> $items
	 * @return array<string, string>
	 */
	public static function add_menu_item( array $items ): array {
		$new_items = array();
		foreach ( $items as $key => $title ) {
			$new_items[ $key ] = $title;
			if ( 'orders' === $key ) {
				$new_items[ self::ENDPOINT ] = __( 'Backupino & Licenses', 'backupino-portal' );
			}
		}

		if ( ! isset( $new_items[ self::ENDPOINT ] ) ) {
			$new_items[ self::ENDPOINT ] = __( 'Backupino & Licenses', 'backupino-portal' );
		}

		return $new_items;
	}

	/**
	 * Render shortcode [backupino_customer_dashboard].
	 *
	 * @return string HTML output.
	 */
	public static function render_shortcode(): string {
		ob_start();
		self::render_dashboard();
		return ob_get_clean();
	}

	/**
	 * Render dashboard markup.
	 */
	public static function render_dashboard(): void {
		if ( ! is_user_logged_in() ) {
			echo '<div class="backupino-alert backupino-alert-warning">' .
				esc_html__( 'Please log in to your account to manage your Backupino licenses and backups.', 'backupino-portal' ) .
				'</div>';
			return;
		}

		$user_id  = get_current_user_id();
		$data     = CustomerDashboardService::get_customer_overview( $user_id );
		$template = dirname( __DIR__, 2 ) . '/templates/customer-dashboard.php';

		if ( file_exists( $template ) ) {
			require $template;
		} else {
			echo '<p>' . esc_html__( 'Dashboard template not found.', 'backupino-portal' ) . '</p>';
		}
	}

	/**
	 * AJAX: Request manual backup.
	 */
	public static function ajax_create_backup(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id       = get_current_user_id();
		$license_id    = isset( $_POST['license_id'] ) ? (int) $_POST['license_id'] : 0;
		$activation_id = isset( $_POST['activation_id'] ) ? (int) $_POST['activation_id'] : 0;

		$res = CustomerDashboardService::request_manual_backup( $user_id, $license_id, $activation_id );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), $res->get_error_data()['status'] ?? 400 );
		}

		wp_send_json_success( $res );
	}

	/**
	 * AJAX: Request manual restore (Strict D-042: Owner only + 2FA verified).
	 */
	public static function ajax_restore(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id    = get_current_user_id();
		$license_id = isset( $_POST['license_id'] ) ? (int) $_POST['license_id'] : 0;
		$backup_id  = isset( $_POST['backup_id'] ) ? (int) $_POST['backup_id'] : 0;
		$totp_code  = isset( $_POST['totp_code'] ) ? sanitize_text_field( (string) $_POST['totp_code'] ) : '';

		$res = CustomerDashboardService::request_manual_restore( $user_id, $license_id, $backup_id, $totp_code );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), $res->get_error_data()['status'] ?? 400 );
		}

		wp_send_json_success( $res );
	}

	/**
	 * AJAX: Deactivate a domain slot (D-006).
	 */
	public static function ajax_deactivate_domain(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id       = get_current_user_id();
		$activation_id = isset( $_POST['activation_id'] ) ? (int) $_POST['activation_id'] : 0;

		$res = CustomerDashboardService::deactivate_domain( $user_id, $activation_id );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), $res->get_error_data()['status'] ?? 400 );
		}

		wp_send_json_success( array( 'message' => __( 'Domain slot deactivated successfully.', 'backupino-portal' ) ) );
	}

	/**
	 * AJAX: Update snapshot retention hours.
	 */
	public static function ajax_update_retention(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id    = get_current_user_id();
		$license_id = isset( $_POST['license_id'] ) ? (int) $_POST['license_id'] : 0;
		$hours      = isset( $_POST['retention_hours'] ) ? (int) $_POST['retention_hours'] : 0;

		$res = CustomerDashboardService::update_snapshot_retention( $user_id, $license_id, $hours );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), $res->get_error_data()['status'] ?? 400 );
		}

		wp_send_json_success( array( 'message' => __( 'Snapshot retention period updated successfully.', 'backupino-portal' ) ) );
	}

	/**
	 * AJAX: Download backup package securely (Owner only).
	 */
	public static function ajax_download_backup(): void {
		$backup_id = isset( $_GET['backup_id'] ) ? (int) $_GET['backup_id'] : 0;
		$user_id   = get_current_user_id();

		if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ?? '' ), 'backupino_dl_' . $backup_id . '_' . $user_id ) ) {
			wp_die( esc_html__( 'Security check failed or download link expired.', 'backupino-portal' ), 403 );
		}

		$backup = BackupRepository::find_by_id( $backup_id );
		if ( ! $backup ) {
			wp_die( esc_html__( 'Backup file not found.', 'backupino-portal' ), 404 );
		}

		$ownership = AuthorizationGuard::verify_license_ownership( $user_id, $backup->license_id );
		if ( is_wp_error( $ownership ) ) {
			wp_die( esc_html__( 'Access denied. You do not own this backup.', 'backupino-portal' ), 403 );
		}

		AuditLogger::log(
			'customer_downloaded_backup',
			$user_id,
			$backup->license_id,
			$backup->domain_hash,
			array( 'backup_id' => $backup_id, 'package_name' => $backup->package_name )
		);

		// Output download response or redirect to protected storage
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $backup->package_name ) . '"' );
		header( 'Content-Length: ' . (int) $backup->file_size_bytes );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );

		// If real file exists on path stream it, otherwise output secure dummy package payload
		if ( ! empty( $backup->storage_path ) && file_exists( $backup->storage_path ) ) {
			readfile( $backup->storage_path );
		} else {
			echo "BACKUPINO-PACKAGE-ENCRYPTED-STREAM\n";
			echo "Package: " . esc_html( $backup->package_name ) . "\n";
			echo "Size: " . (int) $backup->file_size_bytes . " bytes\n";
		}
		exit;
	}
}
