<?php
/**
 * Bilingual Invoice Generator.
 *
 * Implements S3-T06 official invoice generation matching PORTAL-DESIGN-SYSTEM-AND-SEO.md tokens.
 *
 * @package Backupino\Portal\Commerce
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Commerce;

defined( 'ABSPATH' ) || exit;

/**
 * Produces structured and rendered bilingual invoices for orders.
 */
final class InvoiceService {

	/**
	 * Build comprehensive structured invoice data for an order.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function generate_invoice_data( int $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return new \WP_Error( 'WOOCOMMERCE_MISSING', __( 'WooCommerce is not active.', 'backupino-portal' ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new \WP_Error( 'ORDER_NOT_FOUND', __( 'Order not found.', 'backupino-portal' ) );
		}

		$invoice_no = sprintf( 'INV-%s-%04d', gmdate( 'Ymd', strtotime( $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : 'now' ) ), $order_id );

		$items = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$items[] = array(
				'name'        => $item->get_name(),
				'plan_slug'   => $product ? OrderSyncService::detect_plan_slug( $product ) : 'pro',
				'quantity'    => $item->get_quantity(),
				'license_key' => $item->get_meta( OrderSyncService::META_LICENSE_KEY, true ),
				'subtotal'    => (float) $item->get_subtotal(),
				'total'       => (float) $item->get_total(),
			);
		}

		return array(
			'invoice_number'   => $invoice_no,
			'order_id'         => $order_id,
			'status'           => $order->get_status(),
			'date_created'     => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : gmdate( 'Y-m-d H:i:s' ),
			'customer'         => array(
				'id'         => $order->get_user_id(),
				'name'       => trim( $order->get_formatted_billing_full_name() ),
				'email'      => $order->get_billing_email(),
				'phone'      => $order->get_billing_phone(),
			),
			'currency'         => $order->get_currency(),
			'payment_method'   => $order->get_payment_method_title(),
			'transaction_id'   => $order->get_transaction_id(),
			'items'            => $items,
			'subtotal'         => (float) $order->get_subtotal(),
			'discount_total'   => (float) $order->get_discount_total(),
			'tax_total'        => (float) $order->get_total_tax(),
			'total'            => (float) $order->get_total(),
		);
	}

	/**
	 * Render self-contained HTML invoice following the Backupino design system tokens.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @return string HTML content.
	 */
	public static function render_html_invoice( int $order_id ): string {
		$invoice = self::generate_invoice_data( $order_id );
		if ( is_wp_error( $invoice ) ) {
			return '<p>' . esc_html( $invoice->get_error_message() ) . '</p>';
		}

		$is_rtl = is_rtl();
		$dir    = $is_rtl ? 'rtl' : 'ltr';

		ob_start();
		?>
		<!DOCTYPE html>
		<html dir="<?php echo esc_attr( $dir ); ?>">
		<head>
			<meta charset="utf-8">
			<title><?php echo esc_html( $invoice['invoice_number'] ); ?> - Backupino</title>
			<style>
				:root {
					--bk-primary: #0284c7;
					--bk-slate: #0f172a;
					--bk-muted: #64748b;
					--bk-border: #e2e8f0;
					--bk-bg: #f8fafc;
				}
				body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #fff; color: var(--bk-slate); padding: 40px; margin: 0; line-height: 1.5; }
				.invoice-box { max-width: 800px; margin: auto; border: 1px solid var(--bk-border); border-radius: 12px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
				.header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid var(--bk-border); padding-bottom: 24px; margin-bottom: 24px; }
				.logo { font-size: 24px; font-weight: 800; color: var(--bk-primary); letter-spacing: -0.5px; }
				.invoice-meta { text-align: end; }
				.badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
				.badge-completed { background: #dcfce7; color: #166534; }
				.badge-refunded { background: #fee2e2; color: #991b1b; }
				.grid-cols { display: flex; justify-content: space-between; margin-bottom: 32px; }
				table.items { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
				table.items th { background: var(--bk-bg); padding: 12px; text-align: start; font-size: 13px; color: var(--bk-muted); border-bottom: 1px solid var(--bk-border); }
				table.items td { padding: 16px 12px; border-bottom: 1px solid var(--bk-border); font-size: 14px; }
				.license-key-tag { font-family: monospace; background: var(--bk-bg); padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #0369a1; }
				.totals { margin-inline-start: auto; width: 280px; }
				.totals-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
				.totals-row.grand-total { font-size: 18px; font-weight: 800; color: var(--bk-primary); border-top: 2px solid var(--bk-border); margin-top: 8px; padding-top: 12px; }
			</style>
		</head>
		<body>
			<div class="invoice-box">
				<div class="header">
					<div>
						<div class="logo">BACKUPINO</div>
						<div style="color: var(--bk-muted); font-size: 13px; margin-top: 4px;">Official Portal & Licensing Server</div>
					</div>
					<div class="invoice-meta">
						<h2 style="margin: 0; font-size: 20px;"><?php echo esc_html( $invoice['invoice_number'] ); ?></h2>
						<div style="color: var(--bk-muted); font-size: 13px;"><?php echo esc_html( $invoice['date_created'] ); ?></div>
						<div style="margin-top: 8px;">
							<span class="badge badge-<?php echo esc_attr( $invoice['status'] ); ?>">
								<?php echo esc_html( strtoupper( $invoice['status'] ) ); ?>
							</span>
						</div>
					</div>
				</div>

				<div class="grid-cols">
					<div>
						<div style="font-size: 12px; color: var(--bk-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 6px;">Billed To:</div>
						<div style="font-weight: 600;"><?php echo esc_html( $invoice['customer']['name'] ?: 'Valued Customer' ); ?></div>
						<div style="font-size: 13px; color: var(--bk-muted);"><?php echo esc_html( $invoice['customer']['email'] ); ?></div>
					</div>
					<div style="text-align: end;">
						<div style="font-size: 12px; color: var(--bk-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 6px;">Payment Method:</div>
						<div style="font-weight: 600;"><?php echo esc_html( $invoice['payment_method'] ?: 'Online Gateway' ); ?></div>
						<?php if ( ! empty( $invoice['transaction_id'] ) ) : ?>
							<div style="font-size: 12px; color: var(--bk-muted);">Ref: <?php echo esc_html( $invoice['transaction_id'] ); ?></div>
						<?php endif; ?>
					</div>
				</div>

				<table class="items">
					<thead>
						<tr>
							<th>Item & License Details</th>
							<th style="text-align: center;">Qty</th>
							<th style="text-align: end;">Price</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $invoice['items'] as $item ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $item['name'] ); ?></strong>
									<?php if ( ! empty( $item['license_key'] ) ) : ?>
										<div style="margin-top: 4px;">
											<span class="license-key-tag">Key: <?php echo esc_html( $item['license_key'] ); ?></span>
										</div>
									<?php endif; ?>
								</td>
								<td style="text-align: center;"><?php echo esc_html( (string) $item['quantity'] ); ?></td>
								<td style="text-align: end;"><?php echo esc_html( number_format( (float) $item['total'], 2 ) . ' ' . $invoice['currency'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<div class="totals">
					<div class="totals-row">
						<span>Subtotal:</span>
						<span><?php echo esc_html( number_format( $invoice['subtotal'], 2 ) . ' ' . $invoice['currency'] ); ?></span>
					</div>
					<?php if ( $invoice['discount_total'] > 0 ) : ?>
						<div class="totals-row" style="color: #16a34a;">
							<span>Discount / Coupon:</span>
							<span>-<?php echo esc_html( number_format( $invoice['discount_total'], 2 ) . ' ' . $invoice['currency'] ); ?></span>
						</div>
					<?php endif; ?>
					<div class="totals-row grand-total">
						<span>Total Paid:</span>
						<span><?php echo esc_html( number_format( $invoice['total'], 2 ) . ' ' . $invoice['currency'] ); ?></span>
					</div>
				</div>
			</div>
		</body>
		</html>
		<?php
		return (string) ob_get_clean();
	}
}
