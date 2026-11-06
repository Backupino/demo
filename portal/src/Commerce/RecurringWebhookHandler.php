<?php
/**
 * Automated Recurring Payment Webhook Handler.
 *
 * Implements S3-T17: Asynchronous webhook processor for recurring payment gateways
 * (ZarinPal direct debit, Stripe recurring, PayPal subscriptions, and test gateways).
 * Extends subscriptions automatically without requiring customer interactive login (D-037).
 *
 * @package Backupino\Portal\Commerce
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Commerce;

use Backupino\Portal\Models\License;
use Backupino\Portal\Repository\LicenseRepository;
use Backupino\Portal\Lifecycle\RenewalService;
use Backupino\Portal\Audit\AuditLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Handles incoming recurring subscription webhooks.
 */
final class RecurringWebhookHandler {

	public const IDEMPOTENCY_TTL_SECONDS = 604800; // 7 days cache

	/**
	 * Register REST API routes.
	 */
	public static function register_routes(): void {
		register_rest_route(
			'backupino/v1',
			'/webhook/recurring/(?P<gateway>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle_webhook' ),
				'permission_callback' => '__return_true', // Webhook authenticated via gateway signatures
			)
		);
	}

	/**
	 * Process incoming recurring webhook payload.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public static function handle_webhook( \WP_REST_Request $request ): \WP_REST_Response {
		$gateway = sanitize_key( (string) $request->get_param( 'gateway' ) );
		$body    = $request->get_json_params();
		if ( empty( $body ) || ! is_array( $body ) ) {
			$body = $request->get_body_params();
		}

		// 1. Verify Gateway Signature / Secret Token
		$verification = self::verify_signature( $gateway, $request, $body );
		if ( is_wp_error( $verification ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => $verification->get_error_code(),
					'message' => $verification->get_error_message(),
				),
				$verification->get_error_data()['status'] ?? 401
			);
		}

		// 2. Normalize Gateway Payload
		$normalized = self::normalize_payload( $gateway, $body );
		if ( is_wp_error( $normalized ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => $normalized->get_error_code(),
					'message' => $normalized->get_error_message(),
				),
				400
			);
		}

		$event_type = $normalized['event_type'];
		$txn_id     = $normalized['transaction_id'];
		$key        = $normalized['license_key'] ?? '';
		$lic_id     = (int) ( $normalized['license_id'] ?? 0 );

		// 3. Strict Idempotency & Anti-Double Billing
		$idempotency_key = 'bk_rec_txn_' . $gateway . '_' . md5( $txn_id );
		if ( get_transient( $idempotency_key ) ) {
			return new \WP_REST_Response(
				array(
					'success'           => true,
					'already_processed' => true,
					'transaction_id'    => $txn_id,
					'gateway'           => $gateway,
				),
				200
			);
		}

		// 4. Resolve Target License
		$license = null;
		if ( ! empty( $key ) ) {
			$license = LicenseRepository::find_by_key( $key );
		} elseif ( $lic_id > 0 ) {
			$license = LicenseRepository::find_by_id( $lic_id );
		}

		if ( ! $license ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => 'LICENSE_NOT_FOUND',
					'message' => __( 'Could not identify matching license for recurring payment.', 'backupino-portal' ),
				),
				404
			);
		}

		// 5. Route by Event Type
		switch ( $event_type ) {
			case 'payment_succeeded':
				// Lock idempotency
				set_transient( $idempotency_key, time(), self::IDEMPOTENCY_TTL_SECONDS );

				// Programmatically create completed renewal WC_Order (D-037)
				$order_id = self::create_renewal_order(
					$license,
					(float) $normalized['amount'],
					(string) $normalized['currency'],
					$txn_id,
					$gateway
				);

				// Perform atomic extension and re-sign Ed25519 blob
				$renewal_result = RenewalService::renew_license(
					$license->id,
					365,
					$order_id,
					$license->user_id
				);

				if ( is_wp_error( $renewal_result ) ) {
					return new \WP_REST_Response(
						array(
							'success' => false,
							'code'    => $renewal_result->get_error_code(),
							'message' => $renewal_result->get_error_message(),
						),
						500
					);
				}

				AuditLogger::log(
					'recurring_renewal_completed',
					$license->user_id,
					$license->id,
					null,
					array(
						'gateway'        => $gateway,
						'transaction_id' => $txn_id,
						'order_id'       => $order_id,
						'new_expiry'     => $renewal_result['expires_at'],
					)
				);

				return new \WP_REST_Response(
					array(
						'success'        => true,
						'action'         => 'renewed',
						'license_id'     => $license->id,
						'order_id'       => $order_id,
						'transaction_id' => $txn_id,
						'status'         => 'active',
						'expires_at'     => $renewal_result['expires_at'],
					),
					200
				);

			case 'payment_failed':
				$reason = (string) ( $normalized['failure_reason'] ?? 'Recurring charge declined' );

				// Dispatch payment failed action (NotificationService will notify customer)
				do_action(
					'backupino_payment_failed',
					$license->user_id,
					0,
					(float) $normalized['amount'],
					(string) $normalized['currency'],
					$reason
				);

				AuditLogger::log(
					'recurring_payment_failed',
					$license->user_id,
					$license->id,
					null,
					array(
						'gateway'        => $gateway,
						'transaction_id' => $txn_id,
						'reason'         => $reason,
					)
				);

				return new \WP_REST_Response(
					array(
						'success'        => true,
						'action'         => 'failure_recorded',
						'license_id'     => $license->id,
						'transaction_id' => $txn_id,
					),
					200
				);

			case 'subscription_cancelled':
			default:
				AuditLogger::log(
					'recurring_subscription_cancelled',
					$license->user_id,
					$license->id,
					null,
					array(
						'gateway'        => $gateway,
						'transaction_id' => $txn_id,
					)
				);

				return new \WP_REST_Response(
					array(
						'success'    => true,
						'action'     => 'cancelled',
						'license_id' => $license->id,
					),
					200
				);
		}
	}

	/**
	 * Verify webhook signature or authentication token.
	 *
	 * @param string           $gateway
	 * @param \WP_REST_Request $request
	 * @param array            $payload
	 * @return true|\WP_Error
	 */
	public static function verify_signature( string $gateway, \WP_REST_Request $request, array $payload ) {
		// For development or test gateway
		if ( 'test' === $gateway ) {
			$test_token = $request->get_header( 'X-Backupino-Gateway-Token' );
			if ( empty( $test_token ) && ! empty( $payload['token'] ) ) {
				$test_token = (string) $payload['token'];
			}
			if ( empty( $test_token ) || 'test-secret-token' !== $test_token ) {
				return new \WP_Error( 'INVALID_SIGNATURE', __( 'Invalid test gateway token.', 'backupino-portal' ), array( 'status' => 401 ) );
			}
			return true;
		}

		// Generic shared secret verification via filter or option
		$configured_secret = get_option( "backupino_webhook_secret_{$gateway}", '' );
		if ( empty( $configured_secret ) ) {
			// If not yet explicitly configured, verify non-empty token header
			$token = $request->get_header( 'X-Webhook-Token' ) ?? $request->get_header( 'X-Backupino-Gateway-Token' );
			if ( empty( $token ) ) {
				return new \WP_Error( 'MISSING_SIGNATURE', __( 'Webhook signature or secret token missing.', 'backupino-portal' ), array( 'status' => 401 ) );
			}
			return true;
		}

		$signature = (string) ( $request->get_header( 'X-Signature' ) ?? $request->get_header( 'Stripe-Signature' ) ?? '' );
		if ( empty( $signature ) ) {
			return new \WP_Error( 'MISSING_SIGNATURE', __( 'Missing webhook signature header.', 'backupino-portal' ), array( 'status' => 401 ) );
		}

		$raw_body = $request->get_body();
		$expected = hash_hmac( 'sha256', $raw_body, $configured_secret );

		if ( ! hash_equals( $expected, $signature ) ) {
			return new \WP_Error( 'INVALID_SIGNATURE', __( 'Webhook signature verification failed.', 'backupino-portal' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Normalize various gateway webhook shapes into a consistent payload.
	 *
	 * @param string               $gateway
	 * @param array<string, mixed> $body
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function normalize_payload( string $gateway, array $body ) {
		$event_type = 'payment_succeeded';
		$txn_id     = '';
		$lic_key    = '';
		$lic_id     = 0;
		$amount     = 0.0;
		$currency   = 'IRR';
		$reason     = '';

		if ( 'test' === $gateway || 'generic' === $gateway ) {
			$event_type = sanitize_key( (string) ( $body['event'] ?? 'payment_succeeded' ) );
			$txn_id     = sanitize_text_field( (string) ( $body['transaction_id'] ?? '' ) );
			$lic_key    = sanitize_text_field( (string) ( $body['license_key'] ?? '' ) );
			$lic_id     = (int) ( $body['license_id'] ?? 0 );
			$amount     = (float) ( $body['amount'] ?? 0.0 );
			$currency   = sanitize_text_field( (string) ( $body['currency'] ?? 'IRR' ) );
			$reason     = sanitize_text_field( (string) ( $body['reason'] ?? '' ) );
		} elseif ( 'stripe' === $gateway ) {
			$stripe_type = $body['type'] ?? '';
			if ( 'invoice.payment_succeeded' === $stripe_type ) {
				$event_type = 'payment_succeeded';
			} elseif ( 'invoice.payment_failed' === $stripe_type ) {
				$event_type = 'payment_failed';
			} elseif ( 'customer.subscription.deleted' === $stripe_type ) {
				$event_type = 'subscription_cancelled';
			}

			$data_obj = $body['data']['object'] ?? array();
			$txn_id   = (string) ( $data_obj['id'] ?? $body['id'] ?? '' );
			$lic_key  = (string) ( $data_obj['metadata']['license_key'] ?? '' );
			$lic_id   = (int) ( $data_obj['metadata']['license_id'] ?? 0 );
			$amount   = (float) ( ( $data_obj['amount_paid'] ?? $data_obj['amount_due'] ?? 0 ) / 100 );
			$currency = strtoupper( (string) ( $data_obj['currency'] ?? 'USD' ) );
			$reason   = (string) ( $data_obj['last_payment_error']['message'] ?? '' );
		} elseif ( 'zarinpal' === $gateway ) {
			// ZarinPal Recurring / PayPing Direct Debit standard
			$status     = (int) ( $body['Status'] ?? $body['status'] ?? 0 );
			$event_type = ( 100 === $status || 101 === $status || 'success' === $body['status'] ) ? 'payment_succeeded' : 'payment_failed';
			$txn_id     = (string) ( $body['RefID'] ?? $body['ref_id'] ?? $body['authority'] ?? '' );
			$lic_key    = (string) ( $body['license_key'] ?? $body['metadata']['license_key'] ?? '' );
			$lic_id     = (int) ( $body['license_id'] ?? $body['metadata']['license_id'] ?? 0 );
			$amount     = (float) ( $body['Amount'] ?? $body['amount'] ?? 0 );
			$currency   = 'IRR';
			$reason     = (string) ( $body['message'] ?? 'Transaction unconfirmed' );
		}

		if ( empty( $txn_id ) ) {
			return new \WP_Error( 'INVALID_TRANSACTION', __( 'Missing transaction identifier in webhook.', 'backupino-portal' ) );
		}

		return array(
			'event_type'     => $event_type,
			'transaction_id' => $txn_id,
			'license_key'    => $lic_key,
			'license_id'     => $lic_id,
			'amount'         => $amount,
			'currency'       => $currency,
			'failure_reason' => $reason,
		);
	}

	/**
	 * Programmatically create a completed WooCommerce order for automated renewal (D-037).
	 *
	 * @param License $license
	 * @param float   $amount
	 * @param string  $currency
	 * @param string  $txn_id
	 * @param string  $gateway
	 * @return int WooCommerce Order ID.
	 */
	public static function create_renewal_order( License $license, float $amount, string $currency, string $txn_id, string $gateway ): int {
		if ( ! function_exists( 'wc_create_order' ) ) {
			return 0;
		}

		$order = wc_create_order(
			array(
				'customer_id' => $license->user_id,
				'status'      => 'pending',
			)
		);

		if ( is_wp_error( $order ) || ! $order ) {
			return 0;
		}

		$order_id = $order->get_id();

		// Add custom fee/line item representing the renewal
		$item = new \WC_Order_Item_Fee();
		$item->set_name( sprintf( 'Backupino %s Annual Subscription Renewal', ucfirst( $license->plan_slug ) ) );
		$item->set_amount( $amount );
		$item->set_total( $amount );
		$order->add_item( $item );

		// Set metadata
		$order->set_payment_method( $gateway );
		$order->set_payment_method_title( ucfirst( $gateway ) . ' Automated Recurring' );
		$order->set_currency( $currency );
		$order->update_meta_data( '_backupino_renewal_license_id', $license->id );
		$order->update_meta_data( '_backupino_recurring_txn', $txn_id );
		$order->update_meta_data( '_backupino_provisioned', 1 );

		$order->calculate_totals();
		$order->payment_complete( $txn_id );
		$order->add_order_note(
			sprintf(
				/* translators: 1: gateway, 2: transaction id, 3: license key */
				__( 'Automated recurring renewal processed via %1$s webhook (Txn: %2$s). Extended license %3$s.', 'backupino-portal' ),
				ucfirst( $gateway ),
				$txn_id,
				$license->license_key
			)
		);
		$order->save();

		return $order_id;
	}
}
