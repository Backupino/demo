<?php
/**
 * Multi-Channel Notification Dispatcher.
 *
 * Implements S3-T13: Email (HTML/plain-text), Central Bot (Telegram/Bale, D-009),
 * and SMS hooks across the 5 core customer lifecycle events.
 *
 * @package Backupino\Portal\Notifications
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Notifications;

use Backupino\Portal\Ui\CustomerDashboardService;
use Backupino\Portal\Audit\AuditLogger;
use Backupino\Portal\Models\License;

defined( 'ABSPATH' ) || exit;

/**
 * Handles dispatching transactional alerts to customers.
 */
final class NotificationService {

	/**
	 * Register notification lifecycle event listeners.
	 */
	public static function init(): void {
		// License lifecycle state transitions
		add_action( 'backupino_license_status_changed', array( self::class, 'handle_license_status_change' ), 10, 3 );

		// Payment failure event
		add_action( 'backupino_payment_failed', array( self::class, 'handle_payment_failure' ), 10, 5 );

		// Backup error or quota limit reached
		add_action( 'backupino_backup_error', array( self::class, 'handle_backup_error' ), 10, 4 );

		// Disaster recovery restore completed / shadow ready
		add_action( 'backupino_restore_event', array( self::class, 'handle_restore_event' ), 10, 4 );
	}

	/**
	 * Send an automated notification to a customer across active channels.
	 *
	 * @param string               $event    Event key ('license_expiring_soon', 'license_grace_period', 'payment_failed', 'backup_error_or_quota', 'restore_completed').
	 * @param int                  $user_id  Target WordPress user ID.
	 * @param array<string, mixed> $context  Event data context.
	 * @param string|null          $locale   Optional locale override ('fa' or 'en').
	 * @return array{success: bool, channels: array<string>, error?: string}
	 */
	public static function send( string $event, int $user_id, array $context = array(), ?string $locale = null ): array {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array(
				'success'  => false,
				'channels' => array(),
				'error'    => 'USER_NOT_FOUND',
			);
		}

		$settings = CustomerDashboardService::get_customer_settings( $user_id );

		// Resolve locale
		if ( empty( $locale ) ) {
			$locale = ! empty( $settings['preferred_locale'] ) ? (string) $settings['preferred_locale'] : 'fa';
		}

		// Render bilingual template with D-036 tokens
		$rendered = EmailTemplateRenderer::render( $event, $locale, $context );

		$channels_dispatched = array();

		// 1. Email Channel (Default)
		if ( ! empty( $user->user_email ) ) {
			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . sprintf( '%s <%s>', get_bloginfo( 'name' ), get_option( 'admin_email' ) ),
			);

			$sent = wp_mail( $user->user_email, $rendered['subject'], $rendered['html'], $headers );
			if ( $sent ) {
				$channels_dispatched[] = 'email';
			}
		}

		// 2. Central Bot Channel (Strictly Opt-in, Pro Only, D-009, S3-T12)
		if ( ! empty( $settings['central_bot_enabled'] ) && ! empty( $settings['bot_chat_id'] ) ) {
			$should_dispatch_bot = false;

			switch ( $event ) {
				case 'license_expiring_soon':
				case 'license_grace_period':
					$should_dispatch_bot = ! empty( $settings['notify_on_expire'] );
					break;
				case 'backup_error_or_quota':
					$should_dispatch_bot = ! empty( $settings['notify_on_error'] );
					break;
				case 'restore_completed':
					$should_dispatch_bot = ! empty( $settings['notify_on_backup'] );
					break;
				case 'payment_failed':
					$should_dispatch_bot = true; // Critical transactional alert
					break;
			}

			if ( $should_dispatch_bot ) {
				$messenger = (string) ( $settings['bot_messenger'] ?? 'telegram' );
				$chat_id   = (string) $settings['bot_chat_id'];

				/**
				 * Central bot dispatch hook (consumable by Bot Engine or background worker).
				 *
				 * @param string $messenger 'telegram' or 'bale'
				 * @param string $chat_id   Destination chat ID
				 * @param string $text      Plain-text formatted notification message
				 * @param array  $context   Raw event payload
				 */
				do_action( 'backupino_central_bot_dispatch', $messenger, $chat_id, $rendered['text'], $context );
				$channels_dispatched[] = 'bot';
			}
		}

		// 3. SMS Hook (Extensible for Iranian SMS gateways: Kavenegar, Faraz, etc.)
		$phone = get_user_meta( $user_id, 'billing_phone', true );
		if ( empty( $phone ) ) {
			$phone = get_user_meta( $user_id, 'phone', true );
		}
		if ( ! empty( $phone ) ) {
			do_action( 'backupino_send_sms_notification', (string) $phone, $event, $context, $locale );
			$channels_dispatched[] = 'sms';
		}

		// Audit Log
		AuditLogger::log(
			'notification_dispatched',
			$user_id,
			0,
			null,
			array(
				'event'    => $event,
				'channels' => $channels_dispatched,
				'locale'   => $locale,
			)
		);

		do_action( 'backupino_notification_sent', $event, $user_id, $context, $channels_dispatched );

		return array(
			'success'  => ! empty( $channels_dispatched ),
			'channels' => $channels_dispatched,
		);
	}

	/**
	 * Hook handler for license state transition.
	 *
	 * @param License $license
	 * @param string  $from_status
	 * @param string  $to_status
	 */
	public static function handle_license_status_change( License $license, string $from_status, string $to_status ): void {
		if ( 'expiring_soon' === $to_status ) {
			self::send(
				'license_expiring_soon',
				$license->user_id,
				array(
					'license_key' => $license->license_key,
					'plan_name'   => $license->plan_id,
					'expires_at'  => $license->expires_at,
				)
			);
		} elseif ( 'grace_period' === $to_status ) {
			self::send(
				'license_grace_period',
				$license->user_id,
				array(
					'license_key'      => $license->license_key,
					'plan_name'        => $license->plan_id,
					'grace_expires_at' => $license->grace_expires_at,
				)
			);
		}
	}

	/**
	 * Hook handler for payment failure.
	 *
	 * @param int         $user_id
	 * @param int         $order_id
	 * @param float       $amount
	 * @param string      $currency
	 * @param string|null $reason
	 */
	public static function handle_payment_failure( int $user_id, int $order_id, float $amount, string $currency, ?string $reason = null ): void {
		self::send(
			'payment_failed',
			$user_id,
			array(
				'order_id'      => $order_id,
				'amount'        => $amount,
				'currency'      => $currency,
				'error_message' => $reason,
			)
		);
	}

	/**
	 * Hook handler for backup error or quota exceeded.
	 *
	 * @param int    $user_id
	 * @param string $domain
	 * @param string $issue_type
	 * @param string $details
	 */
	public static function handle_backup_error( int $user_id, string $domain, string $issue_type, string $details ): void {
		self::send(
			'backup_error_or_quota',
			$user_id,
			array(
				'domain'     => $domain,
				'issue_type' => $issue_type,
				'details'    => $details,
			)
		);
	}

	/**
	 * Hook handler for disaster recovery restore events.
	 *
	 * @param int                  $user_id
	 * @param string               $domain
	 * @param string               $stage
	 * @param array<string, mixed> $details
	 */
	public static function handle_restore_event( int $user_id, string $domain, string $stage, array $details = array() ): void {
		$context = array_merge(
			array(
				'domain' => $domain,
				'stage'  => $stage,
			),
			$details
		);

		self::send( 'restore_completed', $user_id, $context );
	}
}
