<?php
/**
 * Operator Portal: Activation Logs View.
 *
 * Implements S3-T09 and D-051.
 *
 * @var array $data
 * @var string $nonce
 * @package Backupino\Portal
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap backupino-admin-wrap" style="max-width: 100%; margin-inline-end: 20px; box-sizing: border-box; margin-top: 20px;">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-networking" style="font-size: 28px; width: 28px; height: 28px; color: #0284c7; vertical-align: middle;"></span>
		<?php esc_html_e( 'Domain Activations & Telemetry Logs', 'backupino-portal' ); ?>
	</h1>

	<!-- Strict D-042 Security Invariant Banner -->
	<div class="notice notice-info" style="border-inline-start-color: #0284c7; padding: 12px 16px; margin-block: 16px; max-width: 100%;">
		<p style="margin: 0; font-size: 13px;">
			<strong>🛡️ <?php esc_html_e( 'Read-Only Telemetry (D-042):', 'backupino-portal' ); ?></strong>
			<?php esc_html_e( 'This view displays real-time activation logs across production and staging domains. Remote backups and restores cannot be invoked from this console.', 'backupino-portal' ); ?>
		</p>
	</div>

	<div class="bk-admin-card" style="width: 100%; max-width: 100%; box-sizing: border-box; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
			<h2 style="margin: 0; font-size: 16px; font-weight: 700;"><?php esc_html_e( 'Connected Domain Activations', 'backupino-portal' ); ?> (<?php echo (int) $data['total']; ?>)</h2>
			<form method="get" style="display: flex; gap: 8px;">
				<input type="hidden" name="page" value="backupino-activations" />
				<input type="search" name="s" value="<?php echo esc_attr( $_GET['s'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Search domain or hash...', 'backupino-portal' ); ?>" class="regular-text" style="width: 250px;" />
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Search', 'backupino-portal' ); ?></button>
			</form>
		</div>

		<div style="width: 100%; overflow-x: auto;">
			<table class="bk-modern-table" style="width: 100%;">
			<thead>
				<tr>
					<th style="width: 60px;"><?php esc_html_e( 'ID', 'backupino-portal' ); ?></th>
					<th style="min-width: 220px;"><?php esc_html_e( 'Domain Name', 'backupino-portal' ); ?></th>
					<th style="width: 240px;"><?php esc_html_e( 'License Key', 'backupino-portal' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Environment', 'backupino-portal' ); ?></th>
					<th style="width: 110px;"><?php esc_html_e( 'Status', 'backupino-portal' ); ?></th>
					<th style="width: 150px;"><?php esc_html_e( 'Last Heartbeat', 'backupino-portal' ); ?></th>
					<th style="width: 150px;"><?php esc_html_e( 'Activated At', 'backupino-portal' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $data['items'] ) ) : ?>
					<tr>
						<td colspan="7"><?php esc_html_e( 'No activation records found.', 'backupino-portal' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $data['items'] as $act ) : ?>
						<tr>
							<td>#<?php echo (int) $act['id']; ?></td>
							<td>
								<strong><?php echo esc_html( $act['domain_name'] ); ?></strong><br>
								<code style="font-size: 11px; color: #64748b;"><?php echo esc_html( substr( $act['domain_hash'], 0, 16 ) ); ?>...</code>
							</td>
							<td>
								<code style="color: #0284c7;"><?php echo esc_html( $act['license_key'] ?? '&mdash;' ); ?></code>
							</td>
							<td>
								<?php if ( ! empty( $act['is_staging'] ) ) : ?>
									<span style="background: #ecfdf5; color: #059669; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">
										<?php esc_html_e( 'Staging (D-051 Free)', 'backupino-portal' ); ?>
									</span>
								<?php else : ?>
									<span style="background: #e0f2fe; color: #0284c7; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">
										<?php esc_html_e( 'Production Slot', 'backupino-portal' ); ?>
									</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $act['is_active'] ) ) : ?>
									<span style="color: #16a34a; font-weight: 700;">● <?php esc_html_e( 'Active', 'backupino-portal' ); ?></span>
								<?php else : ?>
									<span style="color: #94a3b8; font-weight: 700;">○ <?php esc_html_e( 'Deactivated', 'backupino-portal' ); ?></span>
								<?php endif; ?>
							</td>
							<td style="color: #475569;">
								<?php echo ! empty( $act['last_heartbeat_at'] ) ? esc_html( $act['last_heartbeat_at'] ) : '&mdash;'; ?>
							</td>
							<td style="color: #475569;">
								<?php echo esc_html( $act['activated_at'] ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
			</table>
		</div>
	</div>
</div>
