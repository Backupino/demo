<?php
/**
 * Idempotent Payment Webhook Handler.
 *
 * Implements S3-T06 idempotent payment gateway webhooks.
 *
 * @package Backupino\Portal\Commerce
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Commerce;

use Backupino\Portal\Audit\AuditLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Processes incoming payment webhooks safely without double-processing.
 */
final class WebhookHandler {

	public const IDEMPOTENCY_TTL_SECONDS = 604800; // 7 days

	/**
	 * Process webhook payload with strict idempotency protection.
	 *
	 * @param string               $gateway        Gateway slug ('zarinpal', 'stripe', 'paypal', 'test').
	 * @param string               $transaction_id Unique transaction reference.
	 * @param int                  $order_id       WooCommerce Order ID.
	 * @param array<string, mixed> $payload        Raw webhook data.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function process( string $gateway, string $transaction_id, int $order_id, array $payload = array() ) {
		if ( empty( $transaction_id ) || $order_id <= 0 ) {
			return new \WP_Error( 'INVALID_PAYLOAD', __( 'Transaction ID and Order ID are required.', 'backupino-portal' ) );
		}

		$idempotency_key = 'bk_txn_' . sanitize_key( $gateway ) . '_' . md5( $transaction_id );

		// 1. Check Idempotency: has this transaction been processed before?
		if ( get_transient( $idempotency_key ) ) {
			return array(
				'success'           => true,
				'already_processed' => true,
				'gateway'           => $gateway,
				'transaction_id'    => $transaction_id,
				'order_id'          => $order_id,
			);
		}

		if ( ! function_exists( 'wc_get_order' ) ) {
			return new \WP_Error( 'WOOCOMMERCE_MISSING', __( 'WooCommerce is not active.', 'backupino-portal' ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new \WP_Error( 'ORDER_NOT_FOUND', __( 'Order not found.', 'backupino-portal' ), array( 'status' => 404 ) );
		}

		// 2. Mark idempotency key immediately to prevent race conditions
		set_transient( $idempotency_key, time(), self::IDEMPOTENCY_TTL_SECONDS );

		// 3. Complete order and record transaction ID
		$order->payment_complete( $transaction_id );
		$order->add_order_note(
			sprintf(
				/* translators: 1: gateway name, 2: transaction ID */
				__( 'Payment verified via %1$s webhook. Transaction ID: %2$s', 'backupino-portal' ),
				ucfirst( $gateway ),
				$transaction_id
			)
		);
		$order->save();

		// 4. Ensure license provisioning
		$license_ids = OrderSyncService::handle_order_completed( $order_id );

		AuditLogger::log(
			'payment_webhook_processed',
			(int) $order->get_user_id(),
			0,
			null,
			array(
				'gateway'        => $gateway,
				'transaction_id' => $transaction_id,
				'order_id'       => $order_id,
				'licenses'       => $license_ids,
			)
		);

		return array(
			'success'        => true,
			'gateway'        => $gateway,
			'transaction_id' => $transaction_id,
			'order_id'       => $order_id,
			'license_ids'    => $license_ids,
		);
	}
}
