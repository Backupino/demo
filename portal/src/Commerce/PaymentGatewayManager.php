<?php
/**
 * Payment Gateway Manager and Test Gateway.
 *
 * Implements S3-T06 payment gateway orchestration (Iranian + International).
 *
 * @package Backupino\Portal\Commerce
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Manages gateway registration, test gateway adapter, and checkout retry URLs.
 */
final class PaymentGatewayManager {

	/**
	 * Initialize gateway hooks.
	 */
	public static function init(): void {
		add_filter( 'woocommerce_payment_gateways', array( self::class, 'register_gateways' ) );
	}

	/**
	 * Register Backupino test gateway in WooCommerce.
	 *
	 * @param string[] $gateways
	 * @return string[]
	 */
	public static function register_gateways( array $gateways ): array {
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once __DIR__ . '/TestPaymentGateway.php';
			$gateways[] = TestPaymentGateway::class;
		}
		return $gateways;
	}

	/**
	 * Generate recovery payment URL for failed/pending orders.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @return string Payment checkout URL.
	 */
	public static function get_retry_payment_url( int $order_id ): string {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return '';
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return '';
		}

		return $order->get_checkout_payment_url();
	}
}
