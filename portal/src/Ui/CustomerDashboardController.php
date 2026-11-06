<?php
/**
 * Customer Dashboard UI Controller, AJAX handlers, and WooCommerce My Account Override.
 *
 * Implements S3-T08, D-006, D-015, D-032, D-036, D-042.
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
		// WooCommerce My Account override & endpoint
		add_action( 'init', array( self::class, 'add_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( self::class, 'add_menu_item' ), 40 );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( self::class, 'render_dashboard' ) );

		// Complete takeover of WooCommerce default My Account template
		add_filter( 'wc_get_template', array( self::class, 'override_wc_myaccount_template' ), 10, 2 );

		// Shortcode
		add_shortcode( 'backupino_customer_dashboard', array( self::class, 'render_shortcode' ) );

		// AJAX endpoints: Backup & Restore
		add_action( 'wp_ajax_backupino_customer_create_backup', array( self::class, 'ajax_create_backup' ) );
		add_action( 'wp_ajax_backupino_customer_restore', array( self::class, 'ajax_restore' ) );
		add_action( 'wp_ajax_backupino_customer_deactivate_domain', array( self::class, 'ajax_deactivate_domain' ) );
		add_action( 'wp_ajax_backupino_customer_update_retention', array( self::class, 'ajax_update_retention' ) );
		add_action( 'wp_ajax_backupino_customer_download', array( self::class, 'ajax_download_backup' ) );

		// AJAX endpoints: Support Tickets
		add_action( 'wp_ajax_backupino_customer_create_ticket', array( self::class, 'ajax_create_ticket' ) );
		add_action( 'wp_ajax_backupino_customer_reply_ticket', array( self::class, 'ajax_reply_ticket' ) );
		add_action( 'wp_ajax_backupino_customer_get_ticket', array( self::class, 'ajax_get_ticket' ) );
		add_action( 'wp_ajax_backupino_customer_close_ticket', array( self::class, 'ajax_close_ticket' ) );

		// AJAX endpoints: Security & 2FA
		add_action( 'wp_ajax_backupino_customer_2fa_setup', array( self::class, 'ajax_2fa_setup' ) );
		add_action( 'wp_ajax_backupino_customer_2fa_enable', array( self::class, 'ajax_2fa_enable' ) );
		add_action( 'wp_ajax_backupino_customer_2fa_disable', array( self::class, 'ajax_2fa_disable' ) );

		// AJAX endpoints: General Settings & Upgrade Requests
		add_action( 'wp_ajax_backupino_customer_save_settings', array( self::class, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_backupino_customer_request_upgrade', array( self::class, 'ajax_request_upgrade' ) );

		// AJAX endpoints: Domain Management (Add, Change, Delete)
		add_action( 'wp_ajax_backupino_customer_add_domain', array( self::class, 'ajax_add_domain' ) );
		add_action( 'wp_ajax_backupino_customer_change_domain', array( self::class, 'ajax_change_domain' ) );
		add_action( 'wp_ajax_backupino_customer_delete_domain', array( self::class, 'ajax_delete_domain' ) );
	}

	/**
	 * Register custom endpoint in WordPress.
	 */
	public static function add_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Add Backupino tab into WooCommerce My Account menu (fallback).
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
	 * Override WooCommerce standard my-account.php template with Backupino Dedicated Web App Dashboard.
	 *
	 * @param string $located
	 * @param string $template_name
	 * @return string
	 */
	public static function override_wc_myaccount_template( string $located, string $template_name ): string {
		if ( 'myaccount/my-account.php' === $template_name || 'myaccount/dashboard.php' === $template_name ) {
			$custom = dirname( __DIR__, 2 ) . '/templates/customer-dashboard.php';
			if ( file_exists( $custom ) ) {
				return $custom;
			}
		}
		return $located;
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
			echo '<div class="backupino-alert backupino-alert-warning" style="padding: 20px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; color: #92400e; font-family: inherit; margin: 20px 0;">' .
				esc_html__( 'لطفاً برای مدیریت لایسنس‌ها، بکاپ‌های ابری و ارسال تیکت، ابتدا وارد حساب کاربری خود شوید.', 'backupino-portal' ) .
				'</div>';
			return;
		}

		$user_id  = get_current_user_id();
		$data     = CustomerDashboardService::get_customer_overview( $user_id );
		$template = dirname( __DIR__, 2 ) . '/templates/customer-dashboard.php';

		if ( file_exists( $template ) ) {
			require $template;
		} else {
			echo '<p>' . esc_html__( 'قالب داشبورد اختصاصی یافت نشد.', 'backupino-portal' ) . '</p>';
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

		wp_send_json_success( array( 'message' => __( 'دامنه با موفقیت غیرفعال شد و دوره تنفس ۷۲ ساعته آغاز گردید.', 'backupino-portal' ) ) );
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

		wp_send_json_success( array( 'message' => __( 'مدت نگهداری اسنپ‌شات‌ها با موفقیت به‌روزرسانی شد.', 'backupino-portal' ) ) );
	}

	/**
	 * AJAX: Download backup package securely (Owner only).
	 */
	public static function ajax_download_backup(): void {
		$backup_id = isset( $_GET['backup_id'] ) ? (int) $_GET['backup_id'] : 0;
		$user_id   = get_current_user_id();

		if ( ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ?? '' ), 'backupino_dl_' . $backup_id . '_' . $user_id ) ) {
			wp_die( esc_html__( 'خطای امنیتی یا منقضی شدن لینک دانلود.', 'backupino-portal' ), 403 );
		}

		$backup = BackupRepository::find_by_id( $backup_id );
		if ( ! $backup ) {
			wp_die( esc_html__( 'فایل بکاپ یافت نشد.', 'backupino-portal' ), 404 );
		}

		$ownership = AuthorizationGuard::verify_license_ownership( $user_id, $backup->license_id );
		if ( is_wp_error( $ownership ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز. شما مالک این لایسنس نیستید.', 'backupino-portal' ), 403 );
		}

		AuditLogger::log(
			'customer_downloaded_backup',
			$user_id,
			$backup->license_id,
			$backup->domain_hash,
			array( 'backup_id' => $backup_id, 'package_name' => $backup->package_name )
		);

		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $backup->package_name ) . '"' );
		header( 'Content-Length: ' . (int) $backup->file_size_bytes );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );

		if ( ! empty( $backup->storage_path ) && file_exists( $backup->storage_path ) ) {
			readfile( $backup->storage_path );
		} else {
			echo "BACKUPINO-PACKAGE-ENCRYPTED-STREAM\n";
			echo "Package: " . esc_html( $backup->package_name ) . "\n";
			echo "Size: " . (int) $backup->file_size_bytes . " bytes\n";
		}
		exit;
	}

	/**
	 * AJAX: Create support ticket.
	 */
	public static function ajax_create_ticket(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id    = get_current_user_id();
		$subject    = isset( $_POST['subject'] ) ? sanitize_text_field( (string) $_POST['subject'] ) : '';
		$message    = isset( $_POST['message'] ) ? wp_kses_post( (string) $_POST['message'] ) : '';
		$license_id = isset( $_POST['license_id'] ) ? (int) $_POST['license_id'] : 0;

		$res = CustomerDashboardService::create_ticket( $user_id, $subject, $message, $license_id );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), $res->get_error_data()['status'] ?? 400 );
		}

		wp_send_json_success( $res );
	}

	/**
	 * AJAX: Reply to support ticket.
	 */
	public static function ajax_reply_ticket(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id   = get_current_user_id();
		$ticket_id = isset( $_POST['ticket_id'] ) ? (int) $_POST['ticket_id'] : 0;
		$message   = isset( $_POST['message'] ) ? wp_kses_post( (string) $_POST['message'] ) : '';

		$att_url  = null;
		$att_name = null;
		if ( ! empty( $_FILES['attachment'] ) && ! empty( $_FILES['attachment']['name'] ) ) {
			$upload = CustomerDashboardService::handle_file_attachment( $_FILES['attachment'] );
			if ( is_wp_error( $upload ) ) {
				wp_send_json_error( array( 'message' => $upload->get_error_message() ), 400 );
			}
			$att_url  = $upload['url'];
			$att_name = $upload['name'];
		}

		$res = CustomerDashboardService::reply_ticket( $user_id, $ticket_id, $message, $att_url, $att_name );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), $res->get_error_data()['status'] ?? 400 );
		}

		wp_send_json_success( $res );
	}

	/**
	 * AJAX: Close support ticket by customer.
	 */
	public static function ajax_close_ticket(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id   = get_current_user_id();
		$ticket_id = isset( $_POST['ticket_id'] ) ? (int) $_POST['ticket_id'] : 0;

		$res = CustomerDashboardService::close_ticket( $user_id, $ticket_id );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'message' => __( 'تیکت با موفقیت بسته شد.', 'backupino-portal' ) ) );
	}

	/**
	 * AJAX: Get ticket details and conversation thread.
	 */
	public static function ajax_get_ticket(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id   = get_current_user_id();
		$ticket_id = isset( $_POST['ticket_id'] ) ? (int) $_POST['ticket_id'] : 0;

		$res = CustomerDashboardService::get_ticket_details( $user_id, $ticket_id );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), $res->get_error_data()['status'] ?? 400 );
		}

		wp_send_json_success( $res );
	}

	/**
	 * AJAX: Get 2FA Setup secret and QR code.
	 */
	public static function ajax_2fa_setup(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id = get_current_user_id();
		$res     = CustomerDashboardService::get_2fa_setup_data( $user_id );

		wp_send_json_success( $res );
	}

	/**
	 * AJAX: Verify and Enable 2FA.
	 */
	public static function ajax_2fa_enable(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id   = get_current_user_id();
		$totp_code = isset( $_POST['totp_code'] ) ? sanitize_text_field( (string) $_POST['totp_code'] ) : '';

		$res = CustomerDashboardService::confirm_enable_2fa( $user_id, $totp_code );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), $res->get_error_data()['status'] ?? 400 );
		}

		wp_send_json_success( $res );
	}

	/**
	 * AJAX: Disable 2FA.
	 */
	public static function ajax_2fa_disable(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id   = get_current_user_id();
		$totp_code = isset( $_POST['totp_code'] ) ? sanitize_text_field( (string) $_POST['totp_code'] ) : '';

		$res = CustomerDashboardService::disable_2fa( $user_id, $totp_code );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), $res->get_error_data()['status'] ?? 400 );
		}

		wp_send_json_success( array( 'message' => __( 'احراز هویت دو مرحله‌ای غیرفعال شد.', 'backupino-portal' ) ) );
	}

	/**
	 * AJAX: Save Customer General Settings.
	 */
	public static function ajax_save_settings(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id = get_current_user_id();
		$settings = array(
			'central_bot_enabled' => ! empty( $_POST['central_bot_enabled'] ),
			'bot_messenger'       => sanitize_text_field( (string) ( $_POST['bot_messenger'] ?? 'telegram' ) ),
			'bot_chat_id'         => sanitize_text_field( (string) ( $_POST['bot_chat_id'] ?? '' ) ),
			'notify_on_backup'    => ! empty( $_POST['notify_on_backup'] ),
			'notify_on_expire'    => ! empty( $_POST['notify_on_expire'] ),
			'notify_on_error'     => ! empty( $_POST['notify_on_error'] ),
			'default_retention_h' => (int) ( $_POST['default_retention_h'] ?? 48 ),
			'preferred_locale'    => sanitize_text_field( (string) ( $_POST['preferred_locale'] ?? 'fa' ) ),
		);

		CustomerDashboardService::save_customer_settings( $user_id, $settings );
		wp_send_json_success( array( 'message' => __( 'تنظیمات عمومی با موفقیت ذخیره شد.', 'backupino-portal' ) ) );
	}

	/**
	 * AJAX: Submit License Quota Upgrade Request.
	 */
	public static function ajax_request_upgrade(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id = get_current_user_id();
		$data    = array(
			'upgrade_type' => sanitize_text_field( (string) ( $_POST['upgrade_type'] ?? 'more_domains' ) ),
			'notes'        => sanitize_textarea_field( (string) ( $_POST['notes'] ?? '' ) ),
			'license_id'   => (int) ( $_POST['license_id'] ?? 0 ),
		);

		$res = CustomerDashboardService::request_license_upgrade( $user_id, $data );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), $res->get_error_data()['status'] ?? 400 );
		}

		wp_send_json_success( array(
			'message'       => __( 'درخواست ارتقای شما با موفقیت ثبت شد و به تیم فروش ارسال گردید.', 'backupino-portal' ),
			'ticket_number' => $res['ticket_number'] ?? '',
		) );
	}

	/**
	 * AJAX: Add Domain to License.
	 */
	public static function ajax_add_domain(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id     = get_current_user_id();
		$license_id  = isset( $_POST['license_id'] ) ? (int) $_POST['license_id'] : 0;
		$domain_name = isset( $_POST['domain_name'] ) ? sanitize_text_field( (string) $_POST['domain_name'] ) : '';

		$res = CustomerDashboardService::add_domain( $user_id, $license_id, $domain_name );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), $res->get_error_data()['status'] ?? 400 );
		}

		wp_send_json_success( $res );
	}

	/**
	 * AJAX: Request Change Domain / Migration.
	 */
	public static function ajax_change_domain(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id       = get_current_user_id();
		$activation_id = isset( $_POST['activation_id'] ) ? (int) $_POST['activation_id'] : 0;
		$new_domain    = isset( $_POST['new_domain'] ) ? sanitize_text_field( (string) $_POST['new_domain'] ) : '';
		$reason        = isset( $_POST['reason'] ) ? sanitize_text_field( (string) $_POST['reason'] ) : '';

		$res = CustomerDashboardService::request_change_domain( $user_id, $activation_id, $new_domain, $reason );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), $res->get_error_data()['status'] ?? 400 );
		}

		wp_send_json_success( $res );
	}

	/**
	 * AJAX: Delete Domain Slot permanently.
	 */
	public static function ajax_delete_domain(): void {
		check_ajax_referer( 'backupino_customer_nonce', '_wpnonce' );

		$user_id       = get_current_user_id();
		$activation_id = isset( $_POST['activation_id'] ) ? (int) $_POST['activation_id'] : 0;

		$res = CustomerDashboardService::delete_domain( $user_id, $activation_id );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ), $res->get_error_data()['status'] ?? 400 );
		}

		wp_send_json_success( array( 'message' => __( 'دامنه با موفقیت حذف گردید و اسلات آن آزاد شد.', 'backupino-portal' ) ) );
	}
}
