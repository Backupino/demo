<?php
/**
 * Test Payment Gateway for Development & Testing.
 *
 * @package Backupino\Portal\Commerce
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Commerce;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}

/**
 * Built-in test payment gateway for Backupino development and automated testing.
 */
class TestPaymentGateway extends \WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'backupino_test_gateway';
		$this->icon               = '';
		$this->has_fields         = false;
		$this->method_title       = __( 'Backupino Test Gateway', 'backupino-portal' );
		$this->method_description = __( 'Instant test gateway for development and automated checkout verification.', 'backupino-portal' );

		$this->supports = array(
			'products',
			'refunds',
		);

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title', __( 'Backupino Test Gateway (Dev / Test)', 'backupino-portal' ) );
		$this->description = $this->get_option( 'description', __( 'Instant payment simulation without real charges.', 'backupino-portal' ) );
		$this->enabled     = $this->get_option( 'enabled', 'yes' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	public function init_form_fields(): void {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'backupino-portal' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Test Gateway', 'backupino-portal' ),
				'default' => 'yes',
			),
			'title'   => array(
				'title'       => __( 'Title', 'backupino-portal' ),
				'type'        => 'text',
				'description' => __( 'Payment method title displayed during checkout.', 'backupino-portal' ),
				'default'     => __( 'Backupino Test Gateway', 'backupino-portal' ),
			),
		);
	}

	/**
	 * Process payment.
	 *
	 * @param int $order_id
	 * @return array{result: string, redirect: string}
	 */
	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array(
				'result'   => 'failure',
				'redirect' => '',
			);
		}

		$txn_id = 'test_txn_' . bin2hex( random_bytes( 8 ) );
		WebhookHandler::process( 'test', $txn_id, (int) $order_id );

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Process refund.
	 *
	 * @param int    $order_id
	 * @param float  $amount
	 * @param string $reason
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ): bool {
		OrderSyncService::handle_order_refunded( (int) $order_id );
		return true;
	}
}
