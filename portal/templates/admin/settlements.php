<?php
/**
 * Operator Portal: Settlements & Billing Overview.
 *
 * Implements S3-T09 and S3-T06.
 *
 * @var array $settlements
 * @package Backupino\Portal
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap backupino-admin-wrap" style="max-width: 100%; margin-inline-end: 20px; box-sizing: border-box; margin-top: 20px;">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-money-alt" style="font-size: 28px; width: 28px; height: 28px; color: #0284c7; vertical-align: middle;"></span>
		<?php esc_html_e( 'Billing, Orders & Settlements', 'backupino-portal' ); ?>
	</h1>

	<!-- Strict D-042 Security Invariant Banner -->
	<div class="notice notice-info" style="border-inline-start-color: #0284c7; padding: 12px 16px; margin-block: 16px; max-width: 100%;">
		<p style="margin: 0; font-size: 13px;">
			<strong>🛡️ <?php esc_html_e( 'Commerce & Order Boundaries (D-037):', 'backupino-portal' ); ?></strong>
			<?php esc_html_e( 'Licensing and lightweight renewal orders are synchronized via WooCommerce WC_Order without third-party subscription dependencies.', 'backupino-portal' ); ?>
		</p>
	</div>

	<!-- Settlements Metric Cards -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
		<div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
			<h4 style="margin: 0 0 8px; color: #64748b; font-size: 13px;"><?php esc_html_e( 'Total Processed Orders', 'backupino-portal' ); ?></h4>
			<span style="font-size: 26px; font-weight: 700; color: #0f172a;"><?php echo (int) $settlements['total_orders']; ?></span>
		</div>

		<div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
			<h4 style="margin: 0 0 8px; color: #64748b; font-size: 13px;"><?php esc_html_e( 'Total Revenue', 'backupino-portal' ); ?></h4>
			<span style="font-size: 26px; font-weight: 700; color: #16a34a;">
				<?php echo function_exists( 'wc_price' ) ? wc_price( $settlements['total_revenue'] ) : number_format( (float) $settlements['total_revenue'] ); ?>
			</span>
		</div>
	</div>

	<!-- Recent Orders Table -->
	<div class="bk-admin-card" style="width: 100%; max-width: 100%; box-sizing: border-box; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
		<h2 style="margin: 0 0 16px; font-size: 16px; font-weight: 700;"><?php esc_html_e( 'Recent License Transactions & Renewals', 'backupino-portal' ); ?></h2>

		<div style="width: 100%; overflow-x: auto;">
			<table class="bk-modern-table" style="width: 100%;">
			<thead>
				<tr>
					<th style="width: 100px;"><?php esc_html_e( 'Order #', 'backupino-portal' ); ?></th>
					<th style="min-width: 220px;"><?php esc_html_e( 'Customer Email', 'backupino-portal' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Type', 'backupino-portal' ); ?></th>
					<th style="width: 120px;"><?php esc_html_e( 'Status', 'backupino-portal' ); ?></th>
					<th style="width: 130px;"><?php esc_html_e( 'Total', 'backupino-portal' ); ?></th>
					<th style="width: 160px;"><?php esc_html_e( 'Date', 'backupino-portal' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $settlements['recent_orders'] ) ) : ?>
					<tr>
						<td colspan="6"><?php esc_html_e( 'No orders recorded yet.', 'backupino-portal' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $settlements['recent_orders'] as $order ) : ?>
						<tr>
							<td>
								<strong>#<?php echo esc_html( $order['order_number'] ); ?></strong>
							</td>
							<td><?php echo esc_html( $order['customer_email'] ); ?></td>
							<td>
								<?php if ( $order['is_renewal'] ) : ?>
									<span style="background: #e0f2fe; color: #0284c7; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">
										🔄 <?php esc_html_e( 'Renewal', 'backupino-portal' ); ?>
									</span>
								<?php else : ?>
									<span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 4px; font-size: 11px;">
										✨ <?php esc_html_e( 'New License', 'backupino-portal' ); ?>
									</span>
								<?php endif; ?>
							</td>
							<td>
								<span style="font-weight: 700;"><?php echo esc_html( ucfirst( $order['status'] ) ); ?></span>
							</td>
							<td><?php echo wp_kses_post( $order['total'] ); ?></td>
							<td style="color: #475569;"><?php echo esc_html( $order['date'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	</div>
</div>
