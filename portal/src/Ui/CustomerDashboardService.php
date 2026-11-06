<?php
/**
 * Customer Dashboard Business Logic and Aggregator Service.
 *
 * Implements S3-T08, D-006, D-015, D-032, D-042.
 *
 * @package Backupino\Portal\Ui
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Ui;

use Backupino\Portal\Models\License;
use Backupino\Portal\Models\Activation;
use Backupino\Portal\Models\CloudBackup;
use Backupino\Portal\Models\Ticket;
use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\Repository\ActivationRepository;
use Backupino\Portal\Repository\BackupRepository;
use Backupino\Portal\Repository\TicketRepository;
use Backupino\Portal\Auth\AuthorizationGuard;
use Backupino\Portal\Auth\TwoFactorAuth;
use Backupino\Portal\Lifecycle\LicenseLifecycleManager;
use Backupino\Portal\Lifecycle\SlaManager;
use Backupino\Portal\Audit\AuditLogger;
use Backupino\Portal\Plans\PlanManager;
use Backupino\Portal\Commands\SignedCommandService;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates data and actions for the Customer Dashboard.
 */
final class CustomerDashboardService {

	public const DEFAULT_SNAPSHOT_RETENTION_HOURS = 48; // Default 48h (S3-T08)
	public const ALLOWED_RETENTION_HOURS          = array( 24, 48, 72, 168 ); // 24h, 48h, 72h, 7 days
	public const PENDING_2FA_META                 = '_backupino_pending_2fa_secret';

	/**
	 * Get full overview data for a customer's dashboard.
	 *
	 * @param int $user_id Customer user ID.
	 * @return array<string, mixed>
	 */
	public static function get_customer_overview( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return array(
				'user_profile'  => array(),
				'stats'         => array(),
				'support_sla'   => array(),
				'licenses'      => array(),
				'tickets'       => array(),
				'invoices'      => array(),
				'is_2fa_active' => false,
			);
		}

		$user = get_userdata( $user_id );
		$user_profile = array(
			'id'           => $user_id,
			'display_name' => $user ? ( $user->display_name ?: $user->user_login ) : 'User #' . $user_id,
			'email'        => $user ? $user->user_email : '',
			'registered'   => $user ? substr( (string) $user->user_registered, 0, 10 ) : '',
			'avatar'       => get_avatar_url( $user_id, array( 'size' => 80 ) ),
		);

		$raw_licenses = LicenseRepository::find_by_user_id( $user_id );
		$licenses_out = array();
		$now          = time();

		$total_sites       = 0;
		$total_staging     = 0;
		$total_backups     = 0;
		$total_cloud_bytes = 0;
		$highest_plan      = 'personal';

		foreach ( $raw_licenses as $license ) {
			if ( 'agency' === $license->plan_slug ) {
				$highest_plan = 'agency';
			} elseif ( 'freelancer' === $license->plan_slug && 'agency' !== $highest_plan ) {
				$highest_plan = 'freelancer';
			}

			$activations = ActivationRepository::find_active_by_license( $license->id );
			$prod_count  = 0;
			$stage_count = 0;
			$domain_list = array();

			foreach ( $activations as $act ) {
				if ( $act->is_staging ) {
					$stage_count++;
					$total_staging++;
				} else {
					$prod_count++;
					$total_sites++;
				}

				// Calculate 72h cooling-off status if deactivated
				$cooling_off_remaining_seconds = 0;
				if ( ! empty( $act->deactivated_at ) ) {
					$deact_time = strtotime( $act->deactivated_at );
					$co_end     = $deact_time + ( 72 * 3600 );
					if ( $now < $co_end ) {
						$cooling_off_remaining_seconds = $co_end - $now;
					}
				}

				// Fetch backups specifically for this domain
				$dom_backups_raw = BackupRepository::find_by_domain_hash( $act->domain_hash, 15 );
				$dom_backups_out = array();
				foreach ( $dom_backups_raw as $b ) {
					$dl_token = wp_create_nonce( 'backupino_dl_' . $b->id . '_' . $user_id );
					$dl_url   = add_query_arg(
						array(
							'action'    => 'backupino_customer_download',
							'backup_id' => $b->id,
							'_wpnonce'  => $dl_token,
						),
						admin_url( 'admin-ajax.php' )
					);
					$dom_backups_out[] = array(
						'id'               => $b->id,
						'package_name'     => $b->package_name,
						'file_size_bytes'  => $b->file_size_bytes,
						'formatted_size'   => $b->get_formatted_size(),
						'created_at'       => $b->created_at,
						'storage_provider' => $b->storage_provider,
						'retention_status' => $b->retention_status,
						'download_url'     => $dl_url,
					);
				}

				$domain_list[] = array(
					'id'                            => $act->id,
					'domain_name'                   => $act->domain_name,
					'domain_hash'                   => $act->domain_hash,
					'is_staging'                    => (bool) $act->is_staging,
					'is_active'                     => (bool) $act->is_active,
					'last_heartbeat_at'             => $act->last_heartbeat_at,
					'cooling_off_remaining_seconds' => $cooling_off_remaining_seconds,
					'cooling_off_active'            => $cooling_off_remaining_seconds > 0,
					'backups'                       => $dom_backups_out,
				);
			}

			// Calculate offline days remaining (7-day offline cache window - D-032)
			$offline_days_remaining = 7;
			if ( ! empty( $license->updated_at ) ) {
				$last_sync_time         = strtotime( $license->updated_at );
				$elapsed_seconds        = max( 0, $now - $last_sync_time );
				$seven_days_sec         = 7 * 86400;
				$remain_sec             = max( 0, $seven_days_sec - $elapsed_seconds );
				$offline_days_remaining = (int) ceil( $remain_sec / 86400 );
			}

			// Snapshot retention duration
			$retention_hours = (int) get_option(
				"backupino_retention_{$license->id}",
				self::DEFAULT_SNAPSHOT_RETENTION_HOURS
			);

			// Backups for this license
			$backups_raw = BackupRepository::find_by_license_id( $license->id, 20 );
			$backups_out = array();
			foreach ( $backups_raw as $b ) {
				$total_backups++;
				$total_cloud_bytes += (int) $b->file_size_bytes;

				$dl_token = wp_create_nonce( 'backupino_dl_' . $b->id . '_' . $user_id );
				$dl_url   = add_query_arg(
					array(
						'action'    => 'backupino_customer_download',
						'backup_id' => $b->id,
						'_wpnonce'  => $dl_token,
					),
					admin_url( 'admin-ajax.php' )
				);

				$backups_out[] = array(
					'id'               => $b->id,
					'package_name'     => $b->package_name,
					'file_size_bytes'  => $b->file_size_bytes,
					'formatted_size'   => $b->get_formatted_size(),
					'created_at'       => $b->created_at,
					'storage_provider' => $b->storage_provider,
					'retention_status' => $b->retention_status,
					'download_url'     => $dl_url,
				);
			}

			$plan_info = PlanManager::get( $license->plan_slug );

			$licenses_out[] = array(
				'id'                     => $license->id,
				'license_key'            => $license->license_key,
				'masked_key'             => substr( $license->license_key, 0, 8 ) . '****-****-' . substr( $license->license_key, -4 ),
				'plan_slug'              => $license->plan_slug,
				'plan_name'              => $plan_info ? $plan_info->name : ucfirst( $license->plan_slug ),
				'status'                 => $license->status,
				'max_domains'            => $license->max_domains,
				'active_prod_count'      => $prod_count,
				'active_stage_count'     => $stage_count,
				'available_slots'        => max( 0, $license->max_domains - $prod_count ),
				'expires_at'             => $license->expires_at,
				'offline_days_remaining' => $offline_days_remaining,
				'snapshot_retention_h'   => $retention_hours,
				'domains'                => $domain_list,
				'backups'                => $backups_out,
				'cloud_quota_bytes'      => $license->cloud_quota_bytes,
			);
		}

		// Support SLA guarantee details based on highest user plan
		$support_sla = array(
			'plan_slug' => $highest_plan,
			'sla_hours' => SlaManager::get_sla_hours( $highest_plan ),
			'title'     => 'agency' === $highest_plan ? __( '12-Hour Dedicated VIP Support', 'backupino-portal' ) :
				( 'freelancer' === $highest_plan ? __( '24-Hour High Priority Support', 'backupino-portal' ) :
				__( '48-Hour Standard Support', 'backupino-portal' ) ),
			'badge'     => 'agency' === $highest_plan ? 'VIP 12h' :
				( 'freelancer' === $highest_plan ? 'Priority 24h' : 'Standard 48h' ),
		);

		// Support tickets
		$raw_tickets = TicketRepository::find_by_user( $user_id );
		$tickets_out = array();
		foreach ( $raw_tickets as $t ) {
			$replies_count = count( TicketRepository::get_replies( $t->id ) );
			$tickets_out[] = array(
				'id'              => $t->id,
				'ticket_number'   => $t->ticket_number,
				'license_id'      => $t->license_id,
				'plan_slug'       => $t->plan_slug,
				'subject'         => $t->subject,
				'priority'        => $t->priority,
				'status'          => $t->status,
				'sla_deadline_at' => $t->sla_deadline_at,
				'sla_breached'    => (bool) $t->sla_breached,
				'created_at'      => $t->created_at,
				'updated_at'      => $t->updated_at,
				'replies_count'   => $replies_count,
			);
		}

		// Invoices from WooCommerce
		$invoices = self::get_customer_invoices( $user_id );

		// Aggregated Stats
		$stats = array(
			'total_licenses'        => count( $licenses_out ),
			'active_licenses'       => count( array_filter( $licenses_out, fn( $l ) => 'active' === $l['status'] ) ),
			'total_sites'           => $total_sites,
			'staging_sites'         => $total_staging,
			'total_backups'         => $total_backups,
			'cloud_used_bytes'      => $total_cloud_bytes,
			'cloud_used_formatted'  => function_exists( 'size_format' ) ? size_format( $total_cloud_bytes ) : ( round( $total_cloud_bytes / 1048576, 2 ) . ' MB' ),
			'open_tickets_count'    => count( array_filter( $tickets_out, fn( $t ) => in_array( $t['status'], array( 'open', 'in_progress', 'waiting_customer' ), true ) ) ),
		);

		return array(
			'user_profile'  => $user_profile,
			'stats'         => $stats,
			'support_sla'   => $support_sla,
			'licenses'      => $licenses_out,
			'tickets'       => $tickets_out,
			'invoices'      => $invoices,
			'settings'      => self::get_customer_settings( $user_id ),
			'is_2fa_active' => TwoFactorAuth::is_2fa_enabled( $user_id ),
		);
	}

	public const USER_SETTINGS_META = '_backupino_customer_settings';

	/**
	 * Get General Settings for Customer.
	 *
	 * @param int $user_id
	 * @return array<string, mixed>
	 */
	public static function get_customer_settings( int $user_id ): array {
		$defaults = array(
			'central_bot_enabled' => false,
			'bot_messenger'       => 'telegram', // telegram or bale
			'bot_chat_id'         => '',
			'bot_username'        => '',
			'bot_consent_given'   => false,
			'bot_consent_at'      => null,
			'bot_linked_at'       => null,
			'notify_on_backup'    => true,
			'notify_on_expire'    => true,
			'notify_on_error'     => true,
			'default_retention_h' => 48,
			'preferred_locale'    => str_starts_with( determine_locale(), 'fa' ) ? 'fa' : 'en',
		);
		$saved = get_user_meta( $user_id, self::USER_SETTINGS_META, true );
		$merged = array_merge( $defaults, is_array( $saved ) ? $saved : array() );

		// Check Pro tier eligibility
		$licenses = LicenseRepository::find_by_user_id( $user_id );
		$has_pro  = false;
		foreach ( $licenses as $lic ) {
			if ( 'active' === $lic->status && in_array( $lic->plan_slug, array( 'personal', 'freelancer', 'agency', 'enterprise' ), true ) ) {
				$has_pro = true;
				break;
			}
		}

		$merged['has_pro_license'] = $has_pro;
		$merged['bot_is_linked']   = ! empty( $merged['bot_chat_id'] );

		return $merged;
	}

	/**
	 * Save General Settings for Customer.
	 *
	 * @param int                  $user_id
	 * @param array<string, mixed> $settings
	 * @return bool
	 */
	public static function save_customer_settings( int $user_id, array $settings ): bool {
		$existing = self::get_customer_settings( $user_id );
		$want_bot = ! empty( $settings['central_bot_enabled'] );

		// If user wants to enable bot, verify they have an active Pro license
		if ( $want_bot && empty( $existing['has_pro_license'] ) ) {
			$want_bot = false;
		}

		$consent_given = ! empty( $settings['bot_consent_given'] );
		$consent_at    = $existing['bot_consent_at'];
		if ( $consent_given && empty( $consent_at ) ) {
			$consent_at = current_time( 'mysql', true );
		}

		$clean = array(
			'central_bot_enabled' => $want_bot,
			'bot_messenger'       => in_array( $settings['bot_messenger'] ?? '', array( 'telegram', 'bale' ), true ) ? $settings['bot_messenger'] : ( $existing['bot_messenger'] ?? 'telegram' ),
			'bot_chat_id'         => sanitize_text_field( (string) ( $settings['bot_chat_id'] ?? $existing['bot_chat_id'] ?? '' ) ),
			'bot_username'        => sanitize_text_field( (string) ( $settings['bot_username'] ?? $existing['bot_username'] ?? '' ) ),
			'bot_consent_given'   => $consent_given,
			'bot_consent_at'      => $consent_at,
			'bot_linked_at'       => $existing['bot_linked_at'] ?? null,
			'notify_on_backup'    => ! empty( $settings['notify_on_backup'] ),
			'notify_on_expire'    => ! empty( $settings['notify_on_expire'] ),
			'notify_on_error'     => ! empty( $settings['notify_on_error'] ),
			'default_retention_h' => (int) ( $settings['default_retention_h'] ?? 48 ),
			'preferred_locale'    => ( 'fa' === ( $settings['preferred_locale'] ?? '' ) ) ? 'fa' : 'en',
		);

		update_user_meta( $user_id, self::USER_SETTINGS_META, $clean );

		AuditLogger::log(
			'customer_settings_updated',
			$user_id,
			0,
			null,
			array(
				'bot_enabled' => $clean['central_bot_enabled'],
				'messenger'   => $clean['bot_messenger'],
				'locale'      => $clean['preferred_locale'],
			)
		);

		return true;
	}

	/**
	 * Generate a one-time alphanumeric link code for the central bot (S3-T12, B5-T04).
	 *
	 * @param int $user_id
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function generate_bot_link_code( int $user_id ) {
		$settings = self::get_customer_settings( $user_id );
		if ( empty( $settings['has_pro_license'] ) ) {
			return new \WP_Error(
				'PRO_REQUIRED',
				__( 'قابلیت ربات اطلاع‌رسانی مرکزی منحصراً برای لایسنس‌های فعال Pro فعال است.', 'backupino-portal' ),
				array( 'status' => 403 )
			);
		}

		$code = 'BK-' . strtoupper( bin2hex( random_bytes( 3 ) ) );
		$transient_key = 'bk_bot_link_' . $code;

		// Valid for 15 minutes (900 seconds)
		set_transient( $transient_key, $user_id, 900 );

		return array(
			'code'         => $code,
			'expires_in'   => 900,
			'bot_telegram' => '@BackupinoBot',
			'bot_bale'     => '@backupinobot',
			'message'      => __( 'کد اتصال تولید شد. لطفاً دستور /link را به ربات ارسال نمایید.', 'backupino-portal' ),
		);
	}

	/**
	 * Unlink Central Bot from Customer Account.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public static function unlink_central_bot( int $user_id ): bool {
		$settings = self::get_customer_settings( $user_id );
		$settings['central_bot_enabled'] = false;
		$settings['bot_chat_id']         = '';
		$settings['bot_username']        = '';
		$settings['bot_linked_at']       = null;

		update_user_meta( $user_id, self::USER_SETTINGS_META, $settings );

		AuditLogger::log(
			'bot_unlinked',
			$user_id,
			0,
			null,
			array( 'status' => 'unlinked' )
		);

		return true;
	}

	/**
	 * Submit a License Quota Upgrade or Plan Request.
	 *
	 * @param int                  $user_id
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function request_license_upgrade( int $user_id, array $data ) {
		$type   = sanitize_text_field( (string) ( $data['upgrade_type'] ?? 'more_domains' ) );
		$notes  = sanitize_textarea_field( (string) ( $data['notes'] ?? '' ) );
		$lic_id = (int) ( $data['license_id'] ?? 0 );

		$subject = sprintf( __( 'درخواست ارتقای لایسنس و سهمیه (%s)', 'backupino-portal' ), $type );
		$message = sprintf(
			"درخواست جدید ارتقای خدمات:\nنوع ارتقا: %s\nشناسه لایسنس مرتبط: %d\nتوضیحات و نیازمندی کاربر:\n%s",
			$type,
			$lic_id,
			$notes
		);

		return self::create_ticket( $user_id, $subject, $message, $lic_id );
	}

	/**
	 * Get WooCommerce orders/invoices for customer.
	 *
	 * @param int $user_id
	 * @return array<array<string, mixed>>
	 */
	public static function get_customer_invoices( int $user_id ): array {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}

		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => 20,
				'status'      => array( 'completed', 'processing' ),
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);

		$out = array();
		foreach ( $orders as $order ) {
			$inv_url = add_query_arg(
				array(
					'action'   => 'backupino_view_invoice',
					'order_id' => $order->get_id(),
					'_wpnonce' => wp_create_nonce( 'backupino_inv_' . $order->get_id() . '_' . $user_id ),
				),
				admin_url( 'admin-ajax.php' )
			);

			$out[] = array(
				'order_id'     => $order->get_id(),
				'order_number' => $order->get_order_number(),
				'date'         => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : '',
				'total'        => $order->get_formatted_order_total(),
				'currency'     => $order->get_currency(),
				'status'       => $order->get_status(),
				'invoice_url'  => $inv_url,
			);
		}

		return $out;
	}

	/**
	 * Create a new support ticket.
	 *
	 * @param int    $user_id
	 * @param string $subject
	 * @param string $message
	 * @param int    $license_id
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function create_ticket( int $user_id, string $subject, string $message, int $license_id = 0 ) {
		if ( $user_id <= 0 ) {
			return new \WP_Error( 'UNAUTHORIZED', __( 'User not authorized.', 'backupino-portal' ), array( 'status' => 401 ) );
		}

		$subject = sanitize_text_field( trim( $subject ) );
		$message = trim( $message );

		if ( empty( $subject ) || empty( $message ) ) {
			return new \WP_Error( 'EMPTY_FIELDS', __( 'Subject and message are required.', 'backupino-portal' ), array( 'status' => 400 ) );
		}

		// Determine plan slug for SLA calculation
		$plan_slug = 'personal';
		if ( $license_id > 0 ) {
			$license = LicenseRepository::find_by_id( $license_id );
			if ( $license && $license->user_id === $user_id ) {
				$plan_slug = $license->plan_slug;
			}
		} else {
			// Find highest plan for this user
			$licenses = LicenseRepository::find_by_user_id( $user_id );
			foreach ( $licenses as $l ) {
				if ( 'agency' === $l->plan_slug ) {
					$plan_slug = 'agency';
					break;
				}
				if ( 'freelancer' === $l->plan_slug ) {
					$plan_slug = 'freelancer';
				}
			}
		}

		$ticket_id = TicketRepository::create(
			array(
				'user_id'    => $user_id,
				'license_id' => $license_id,
				'plan_slug'  => $plan_slug,
				'subject'    => $subject,
				'message'    => $message,
			)
		);

		if ( ! $ticket_id ) {
			return new \WP_Error( 'CREATE_FAILED', __( 'Could not create support ticket.', 'backupino-portal' ), array( 'status' => 500 ) );
		}

		$ticket = TicketRepository::find_by_id( (int) $ticket_id );

		AuditLogger::log(
			'support_ticket_created',
			$user_id,
			$license_id,
			null,
			array(
				'ticket_id'     => $ticket_id,
				'ticket_number' => $ticket ? $ticket->ticket_number : '',
				'plan_slug'     => $plan_slug,
			)
		);

		return array(
			'success'       => true,
			'ticket_id'     => $ticket_id,
			'ticket_number' => $ticket ? $ticket->ticket_number : '',
			'message'       => __( 'تیکت پشتیبانی شما با موفقیت ثبت شد.', 'backupino-portal' ),
		);
	}

	/**
	 * Securely process an uploaded ticket attachment file.
	 *
	 * @param array<string, mixed> $file
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function handle_file_attachment( array $file ) {
		if ( ! isset( $file['error'] ) || UPLOAD_ERR_OK !== $file['error'] ) {
			return new \WP_Error( 'UPLOAD_ERROR', __( 'خطا در بارگذاری فایل.', 'backupino-portal' ) );
		}

		if ( $file['size'] > 10 * 1024 * 1024 ) {
			return new \WP_Error( 'FILE_TOO_LARGE', __( 'حجم فایل پیوست نباید بیشتر از ۱۰ مگابایت باشد.', 'backupino-portal' ) );
		}

		$allowed_exts = array( 'jpg', 'jpeg', 'png', 'webp', 'pdf', 'txt', 'log', 'zip' );
		$ext          = strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, $allowed_exts, true ) ) {
			return new \WP_Error( 'INVALID_EXTENSION', __( 'پسوند فایل مجاز نیست. فقط فایل‌های jpg, png, webp, pdf, txt, log, zip مجاز هستند.', 'backupino-portal' ) );
		}

		// Security: MIME validation
		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$mime  = finfo_file( $finfo, (string) $file['tmp_name'] );
			finfo_close( $finfo );

			$allowed_mimes = array(
				'image/jpeg', 'image/png', 'image/webp',
				'application/pdf', 'text/plain', 'text/x-log',
				'application/zip', 'application/x-zip-compressed',
			);
			if ( ! in_array( $mime, $allowed_mimes, true ) ) {
				return new \WP_Error( 'INVALID_MIME', __( 'محتوای فایل ارسالی با پسوند آن همخوانی ندارد.', 'backupino-portal' ) );
			}
		}

		$upload_dir = wp_upload_dir();
		$target_dir = $upload_dir['basedir'] . '/backupino-attachments';
		if ( ! file_exists( $target_dir ) ) {
			wp_mkdir_p( $target_dir );
			file_put_contents( $target_dir . '/.htaccess', "Order Deny,Allow\nDeny from all\n<FilesMatch \"\.(?i:jpg|jpeg|png|webp|pdf|txt|log|zip)$\">\nAllow from all\n</FilesMatch>\n" );
			file_put_contents( $target_dir . '/index.php', '<?php // Silence is golden' );
		}

		$safe_name   = 'att_' . wp_generate_password( 16, false, false ) . '.' . $ext;
		$target_file = $target_dir . '/' . $safe_name;

		if ( ! move_uploaded_file( (string) $file['tmp_name'], $target_file ) ) {
			return new \WP_Error( 'SAVE_FAILED', __( 'خطا در ذخیره فایل در سرور.', 'backupino-portal' ) );
		}

		return array(
			'url'  => $upload_dir['baseurl'] . '/backupino-attachments/' . $safe_name,
			'name' => sanitize_file_name( (string) $file['name'] ),
		);
	}

	/**
	 * Close a support ticket by customer.
	 *
	 * @param int $user_id
	 * @param int $ticket_id
	 * @return bool|\WP_Error
	 */
	public static function close_ticket( int $user_id, int $ticket_id ) {
		$ticket = TicketRepository::find_by_id( $ticket_id );
		if ( ! $ticket || $ticket->user_id !== $user_id ) {
			return new \WP_Error( 'TICKET_NOT_FOUND', __( 'تیکت یافت نشد.', 'backupino-portal' ) );
		}

		$closed = TicketRepository::close_ticket( $ticket_id, $user_id, false );
		if ( ! $closed ) {
			return new \WP_Error( 'CLOSE_FAILED', __( 'خطا در بستن تیکت.', 'backupino-portal' ) );
		}

		AuditLogger::log(
			'support_ticket_closed_by_customer',
			$user_id,
			$ticket->license_id,
			null,
			array( 'ticket_id' => $ticket_id, 'ticket_number' => $ticket->ticket_number )
		);

		return true;
	}

	/**
	 * Add a reply to a user's support ticket.
	 *
	 * @param int         $user_id
	 * @param int         $ticket_id
	 * @param string      $message
	 * @param string|null $attachment_url
	 * @param string|null $attachment_name
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function reply_ticket( int $user_id, int $ticket_id, string $message, ?string $attachment_url = null, ?string $attachment_name = null ) {
		if ( $user_id <= 0 ) {
			return new \WP_Error( 'UNAUTHORIZED', __( 'User not authorized.', 'backupino-portal' ), array( 'status' => 401 ) );
		}

		$ticket = TicketRepository::find_by_id( $ticket_id );
		if ( ! $ticket || $ticket->user_id !== $user_id ) {
			return new \WP_Error( 'TICKET_NOT_FOUND', __( 'Ticket not found or access denied.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$message = trim( $message );
		if ( empty( $message ) && empty( $attachment_url ) ) {
			return new \WP_Error( 'EMPTY_MESSAGE', __( 'پیام یا فایل پیوست نمی‌تواند خالی باشد.', 'backupino-portal' ), array( 'status' => 400 ) );
		}

		$added = TicketRepository::add_reply( $ticket_id, $user_id, $message, 0, $attachment_url, $attachment_name );
		if ( ! $added ) {
			return new \WP_Error( 'REPLY_FAILED', __( 'Could not post reply.', 'backupino-portal' ), array( 'status' => 500 ) );
		}

		AuditLogger::log(
			'support_ticket_replied_by_customer',
			$user_id,
			$ticket->license_id,
			null,
			array( 'ticket_id' => $ticket_id, 'ticket_number' => $ticket->ticket_number )
		);

		return array(
			'success' => true,
			'message' => __( 'پاسخ شما با موفقیت ثبت شد.', 'backupino-portal' ),
		);
	}

	/**
	 * Get details and conversation thread of a ticket.
	 *
	 * @param int $user_id
	 * @param int $ticket_id
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function get_ticket_details( int $user_id, int $ticket_id ) {
		$ticket = TicketRepository::find_by_id( $ticket_id );
		if ( ! $ticket || $ticket->user_id !== $user_id ) {
			return new \WP_Error( 'TICKET_NOT_FOUND', __( 'Ticket not found or access denied.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$replies_raw = TicketRepository::get_replies( $ticket_id );
		$replies_out = array();

		foreach ( $replies_raw as $r ) {
			$sender = get_userdata( (int) $r['user_id'] );
			$replies_out[] = array(
				'id'              => $r['id'],
				'is_staff'        => (bool) $r['is_staff'],
				'sender_name'     => $r['is_staff'] ? __( 'Official Backupino Support', 'backupino-portal' ) : ( $sender ? $sender->display_name : __( 'Customer', 'backupino-portal' ) ),
				'message'         => wp_kses_post( $r['message'] ),
				'attachment_url'  => $r['attachment_url'] ?? null,
				'attachment_name' => $r['attachment_name'] ?? null,
				'created_at'      => $r['created_at'],
			);
		}

		return array(
			'id'              => $ticket->id,
			'ticket_number'   => $ticket->ticket_number,
			'subject'         => $ticket->subject,
			'priority'        => $ticket->priority,
			'status'          => $ticket->status,
			'sla_deadline_at' => $ticket->sla_deadline_at,
			'created_at'      => $ticket->created_at,
			'replies'         => $replies_out,
		);
	}

	/**
	 * Prepare 2FA setup data for a customer.
	 *
	 * @param int $user_id
	 * @return array<string, string>
	 */
	public static function get_2fa_setup_data( int $user_id ): array {
		$secret = TwoFactorAuth::generate_secret( 16 );
		update_user_meta( $user_id, self::PENDING_2FA_META, $secret );

		$user        = get_userdata( $user_id );
		$username    = $user ? $user->user_email : "user_{$user_id}";
		$otpauth_uri = TwoFactorAuth::get_otpauth_uri( $username, $secret, 'Backupino Portal' );
		$qr_api_url  = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode( $otpauth_uri );

		return array(
			'secret'      => $secret,
			'otpauth_uri' => $otpauth_uri,
			'qr_url'      => $qr_api_url,
		);
	}

	/**
	 * Confirm and activate 2FA with verified TOTP code.
	 *
	 * @param int    $user_id
	 * @param string $totp_code
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function confirm_enable_2fa( int $user_id, string $totp_code ) {
		$pending_secret = (string) get_user_meta( $user_id, self::PENDING_2FA_META, true );
		if ( empty( $pending_secret ) ) {
			return new \WP_Error( 'NO_PENDING_2FA', __( 'No pending 2FA setup found. Please generate a new QR code.', 'backupino-portal' ), array( 'status' => 400 ) );
		}

		if ( ! TwoFactorAuth::verify_code( $pending_secret, $totp_code ) ) {
			return new \WP_Error( 'INVALID_2FA_CODE', __( 'کد اعتبارسنجی وارد شده صحیح نیست یا منقضی شده است.', 'backupino-portal' ), array( 'status' => 400 ) );
		}

		$recovery_codes = TwoFactorAuth::generate_recovery_codes( 8 );
		TwoFactorAuth::enable_2fa( $user_id, $pending_secret, $recovery_codes );
		delete_user_meta( $user_id, self::PENDING_2FA_META );

		AuditLogger::log(
			'two_factor_auth_enabled',
			$user_id,
			0,
			null,
			array( 'method' => 'totp_rfc6238' )
		);

		return array(
			'success'        => true,
			'message'        => __( 'احراز هویت دو مرحله‌ای با موفقیت فعال شد.', 'backupino-portal' ),
			'recovery_codes' => $recovery_codes,
		);
	}

	/**
	 * Disable 2FA with valid TOTP code verification.
	 *
	 * @param int    $user_id
	 * @param string $totp_code
	 * @return bool|\WP_Error
	 */
	public static function disable_2fa( int $user_id, string $totp_code ) {
		if ( ! TwoFactorAuth::is_2fa_enabled( $user_id ) ) {
			return true;
		}

		if ( ! TwoFactorAuth::verify_totp( $user_id, $totp_code ) ) {
			return new \WP_Error( 'INVALID_2FA_CODE', __( 'کد دو مرحله‌ای نامعتبر است.', 'backupino-portal' ), array( 'status' => 400 ) );
		}

		TwoFactorAuth::disable_2fa( $user_id );

		AuditLogger::log(
			'two_factor_auth_disabled',
			$user_id,
			0,
			null,
			array( 'status' => 'disabled' )
		);

		return true;
	}

	/**
	 * Update snapshot retention duration for a license.
	 *
	 * @param int $user_id
	 * @param int $license_id
	 * @param int $hours
	 * @return bool|\WP_Error
	 */
	public static function update_snapshot_retention( int $user_id, int $license_id, int $hours ) {
		$ownership = AuthorizationGuard::verify_license_ownership( $user_id, $license_id );
		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		if ( ! in_array( $hours, self::ALLOWED_RETENTION_HOURS, true ) ) {
			return new \WP_Error(
				'INVALID_RETENTION_HOURS',
				__( 'Invalid retention duration selected.', 'backupino-portal' ),
				array( 'status' => 400 )
			);
		}

		update_option( "backupino_retention_{$license_id}", $hours );

		AuditLogger::log(
			'snapshot_retention_updated',
			$user_id,
			$license_id,
			null,
			array( 'retention_hours' => $hours )
		);

		return true;
	}

	/**
	 * Request manual backup generation (Owner only - D-042).
	 *
	 * @param int $user_id
	 * @param int $license_id
	 * @param int $activation_id
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function request_manual_backup( int $user_id, int $license_id, int $activation_id ) {
		$ownership = AuthorizationGuard::verify_license_ownership( $user_id, $license_id );
		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		$activation = ActivationRepository::find_by_id( $activation_id );
		if ( ! $activation || $activation->license_id !== $license_id ) {
			return new \WP_Error( 'ACTIVATION_NOT_FOUND', __( 'Domain activation not found.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$command_id = 'cmd_bk_' . bin2hex( random_bytes( 8 ) );

		AuditLogger::log(
			'manual_backup_requested',
			$user_id,
			$license_id,
			$activation->domain_hash,
			array(
				'command_id'    => $command_id,
				'activation_id' => $activation_id,
				'domain_name'   => $activation->domain_name,
			)
		);

		return array(
			'success'     => true,
			'command_id'  => $command_id,
			'domain_name' => $activation->domain_name,
			'status'      => 'queued',
			'message'     => __( 'دستور تهیه بکاپ ابری برای سایت در صف پردازش قرار گرفت.', 'backupino-portal' ),
		);
	}

	/**
	 * Request manual restore with Two-Factor Authentication (Strict D-042).
	 *
	 * Delegates to SignedCommandService::prepare_shadow_restore.
	 *
	 * @param int    $user_id
	 * @param int    $license_id
	 * @param int    $backup_id
	 * @param string $totp_code Mandatory 6-digit TOTP code.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function request_manual_restore( int $user_id, int $license_id, int $backup_id, string $totp_code ) {
		return SignedCommandService::prepare_shadow_restore( $user_id, $license_id, $backup_id, $totp_code );
	}

	/**
	 * Commit Two-Phase Restore after inspecting shadow preview.
	 *
	 * @param int    $user_id
	 * @param string $command_id
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function commit_restore( int $user_id, string $command_id ) {
		return SignedCommandService::commit_restore( $user_id, $command_id );
	}

	/**
	 * Abort Two-Phase Restore and discard shadow artifacts.
	 *
	 * @param int    $user_id
	 * @param string $command_id
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function abort_restore( int $user_id, string $command_id ) {
		return SignedCommandService::abort_restore( $user_id, $command_id );
	}

	/**
	 * Get live restore execution status and health report.
	 *
	 * @param int    $user_id
	 * @param string $command_id
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function get_restore_status( int $user_id, string $command_id ) {
		return SignedCommandService::get_command_status( $user_id, $command_id );
	}

	/**
	 * Deactivate a domain activation slot with cooling-off period (D-006).
	 *
	 * @param int $user_id
	 * @param int $activation_id
	 * @return bool|\WP_Error
	 */
	public static function deactivate_domain( int $user_id, int $activation_id ) {
		$activation = ActivationRepository::find_by_id( $activation_id );
		if ( ! $activation ) {
			return new \WP_Error( 'ACTIVATION_NOT_FOUND', __( 'Domain activation not found.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$ownership = AuthorizationGuard::verify_license_ownership( $user_id, $activation->license_id );
		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		$deactivated = ActivationRepository::deactivate( $activation_id );
		if ( ! $deactivated ) {
			return new \WP_Error( 'DEACTIVATION_FAILED', __( 'Could not deactivate domain.', 'backupino-portal' ), array( 'status' => 500 ) );
		}

		AuditLogger::log(
			'domain_deactivated_by_customer',
			$user_id,
			$activation->license_id,
			$activation->domain_hash,
			array(
				'activation_id' => $activation_id,
				'domain_name'   => $activation->domain_name,
			)
		);

		return true;
	}

	/**
	 * Add / Register a new domain for a customer license.
	 *
	 * @param int    $user_id
	 * @param int    $license_id
	 * @param string $domain_name
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function add_domain( int $user_id, int $license_id, string $domain_name ) {
		$ownership = AuthorizationGuard::verify_license_ownership( $user_id, $license_id );
		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		$clean_domain = strtolower( trim( $domain_name ) );
		$clean_domain = (string) preg_replace( '#^https?://#i', '', $clean_domain );
		$clean_domain = rtrim( $clean_domain, '/' );

		if ( empty( $clean_domain ) || ! str_contains( $clean_domain, '.' ) ) {
			return new \WP_Error( 'INVALID_DOMAIN', __( 'نام دامنه وارد شده معتبر نیست.', 'backupino-portal' ), array( 'status' => 400 ) );
		}

		$is_staging = \Backupino\Portal\Licensing\DomainHelper::is_staging_domain( $clean_domain );
		$license    = LicenseRepository::find_by_id( $license_id );

		if ( ! $license ) {
			return new \WP_Error( 'LICENSE_NOT_FOUND', __( 'لایسنس یافت نشد.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		if ( ! $is_staging ) {
			$active_prod = ActivationRepository::count_active_production( $license_id );
			if ( $active_prod >= $license->max_domains ) {
				return new \WP_Error(
					'QUOTA_EXCEEDED',
					sprintf( __( 'سقف دامنه‌های فعال این لایسنس (%d دامنه) تکمیل است. لطفاً پلن خود را ارتقا دهید یا یکی از دامنه‌ها را حذف نمایید.', 'backupino-portal' ), $license->max_domains ),
					array( 'status' => 400 )
				);
			}
		}

		$domain_hash = hash( 'sha256', $clean_domain );

		// Check if domain already active on this license
		$existing = ActivationRepository::find_by_license_and_hash( $license_id, $domain_hash );
		if ( $existing && $existing->is_active ) {
			return new \WP_Error( 'ALREADY_ACTIVE', __( 'این دامنه قبلاً روی این لایسنس فعال شده است.', 'backupino-portal' ), array( 'status' => 400 ) );
		}

		if ( $existing ) {
			ActivationRepository::reactivate( $existing->id );
			$act_id = $existing->id;
		} else {
			$act_id = ActivationRepository::create(
				array(
					'license_id'  => $license_id,
					'domain_hash' => $domain_hash,
					'domain_name' => $clean_domain,
					'is_staging'  => $is_staging ? 1 : 0,
					'is_active'   => 1,
				)
			);
		}

		if ( ! $act_id ) {
			return new \WP_Error( 'CREATE_FAILED', __( 'خطا در ثبت دامنه جدید.', 'backupino-portal' ), array( 'status' => 500 ) );
		}

		// Update active count
		$new_active_count = ActivationRepository::count_active_production( $license_id );
		LicenseRepository::update( $license_id, array( 'active_domains_count' => $new_active_count ) );

		AuditLogger::log(
			'domain_added_manually_by_customer',
			$user_id,
			$license_id,
			$domain_hash,
			array( 'domain_name' => $clean_domain, 'is_staging' => $is_staging )
		);

		return array(
			'success'     => true,
			'id'          => $act_id,
			'domain_name' => $clean_domain,
			'is_staging'  => $is_staging,
			'message'     => __( 'دامنه با موفقیت به لایسنس شما افزوده شد.', 'backupino-portal' ),
		);
	}

	/**
	 * Request or perform a Domain Migration / Change.
	 *
	 * @param int    $user_id
	 * @param int    $activation_id
	 * @param string $new_domain_name
	 * @param string $reason
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function request_change_domain( int $user_id, int $activation_id, string $new_domain_name, string $reason = '' ) {
		$activation = ActivationRepository::find_by_id( $activation_id );
		if ( ! $activation ) {
			return new \WP_Error( 'ACTIVATION_NOT_FOUND', __( 'اسلات دامنه یافت نشد.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$ownership = AuthorizationGuard::verify_license_ownership( $user_id, $activation->license_id );
		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		$clean_domain = strtolower( trim( $new_domain_name ) );
		$clean_domain = (string) preg_replace( '#^https?://#i', '', $clean_domain );
		$clean_domain = rtrim( $clean_domain, '/' );

		if ( empty( $clean_domain ) || ! str_contains( $clean_domain, '.' ) ) {
			return new \WP_Error( 'INVALID_DOMAIN', __( 'نام دامنه جدید معتبر نیست.', 'backupino-portal' ), array( 'status' => 400 ) );
		}

		$is_staging = \Backupino\Portal\Licensing\DomainHelper::is_staging_domain( $clean_domain );
		$new_hash   = hash( 'sha256', $clean_domain );

		$updated = ActivationRepository::update_domain( $activation_id, $clean_domain, $new_hash, $is_staging ? 1 : 0 );
		if ( ! $updated ) {
			return new \WP_Error( 'UPDATE_FAILED', __( 'تغییر دامنه با خطا مواجه شد.', 'backupino-portal' ), array( 'status' => 500 ) );
		}

		AuditLogger::log(
			'domain_migrated_by_customer',
			$user_id,
			$activation->license_id,
			$new_hash,
			array(
				'old_domain' => $activation->domain_name,
				'new_domain' => $clean_domain,
				'reason'     => $reason,
			)
		);

		return array(
			'success'    => true,
			'message'    => sprintf( __( 'دامنه با موفقیت از %s به %s تغییر یافت.', 'backupino-portal' ), $activation->domain_name, $clean_domain ),
			'new_domain' => $clean_domain,
		);
	}

	/**
	 * Permanently delete / remove a domain slot to free up capacity.
	 *
	 * @param int $user_id
	 * @param int $activation_id
	 * @return bool|\WP_Error
	 */
	public static function delete_domain( int $user_id, int $activation_id ) {
		$activation = ActivationRepository::find_by_id( $activation_id );
		if ( ! $activation ) {
			return new \WP_Error( 'ACTIVATION_NOT_FOUND', __( 'اسلات دامنه یافت نشد.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		$ownership = AuthorizationGuard::verify_license_ownership( $user_id, $activation->license_id );
		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		$deleted = ActivationRepository::delete( $activation_id );
		if ( ! $deleted ) {
			return new \WP_Error( 'DELETE_FAILED', __( 'حذف دامنه با خطا مواجه شد.', 'backupino-portal' ), array( 'status' => 500 ) );
		}

		// Recalculate production domains
		$new_active_count = ActivationRepository::count_active_production( $activation->license_id );
		LicenseRepository::update( $activation->license_id, array( 'active_domains_count' => $new_active_count ) );

		AuditLogger::log(
			'domain_permanently_deleted_by_customer',
			$user_id,
			$activation->license_id,
			$activation->domain_hash,
			array( 'deleted_domain' => $activation->domain_name )
		);

		return true;
	}
}
