<?php
/**
 * Operator Portal: Comprehensive Security Audit Logs View.
 *
 * Implements S3-T09 and S3-T01.
 *
 * @var array $logs
 * @package Backupino\Portal
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap backupino-admin-wrap" style="max-width: 100%; margin-inline-end: 20px; box-sizing: border-box; margin-top: 20px;">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-clipboard" style="font-size: 28px; width: 28px; height: 28px; color: #0284c7; vertical-align: middle;"></span>
		<?php esc_html_e( 'Comprehensive Security Audit Logs', 'backupino-portal' ); ?>
	</h1>

	<!-- Strict D-042 Security Invariant Banner -->
	<div class="notice notice-info" style="border-inline-start-color: #0284c7; padding: 12px 16px; margin-block: 16px; max-width: 100%;">
		<p style="margin: 0; font-size: 13px;">
			<strong>🛡️ <?php esc_html_e( 'Immutable Audit Trail:', 'backupino-portal' ); ?></strong>
			<?php esc_html_e( 'All authentication, licensing, renewal, quota override, and ownership events are recorded with actor IDs and timestamp signatures.', 'backupino-portal' ); ?>
		</p>
	</div>

	<div class="bk-admin-card" style="width: 100%; max-width: 100%; box-sizing: border-box; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
			<h2 style="margin: 0; font-size: 16px; font-weight: 700;"><?php esc_html_e( 'Audit Events Log', 'backupino-portal' ); ?> (<?php echo (int) $logs['total']; ?>)</h2>
			<form method="get" style="display: flex; gap: 8px;">
				<input type="hidden" name="page" value="backupino-audit-logs" />
				<input type="text" name="action_filter" value="<?php echo esc_attr( $_GET['action_filter'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Filter by action...', 'backupino-portal' ); ?>" class="regular-text" style="width: 220px;" />
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'backupino-portal' ); ?></button>
			</form>
		</div>

		<div style="width: 100%; overflow-x: auto;">
			<table class="bk-modern-table" style="width: 100%;">
			<thead>
				<tr>
					<th style="width: 60px;"><?php esc_html_e( 'ID', 'backupino-portal' ); ?></th>
					<th style="width: 160px;"><?php esc_html_e( 'Timestamp', 'backupino-portal' ); ?></th>
					<th style="width: 200px;"><?php esc_html_e( 'Action', 'backupino-portal' ); ?></th>
					<th style="width: 160px;"><?php esc_html_e( 'Actor User', 'backupino-portal' ); ?></th>
					<th style="width: 110px;"><?php esc_html_e( 'Target License', 'backupino-portal' ); ?></th>
					<th style="min-width: 250px;"><?php esc_html_e( 'Context / Details', 'backupino-portal' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $logs['items'] ) ) : ?>
					<tr>
						<td colspan="6"><?php esc_html_e( 'No audit log entries recorded.', 'backupino-portal' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $logs['items'] as $log ) : ?>
						<tr>
							<td>#<?php echo (int) $log['id']; ?></td>
							<td style="color: #475569;"><?php echo esc_html( $log['created_at'] ); ?></td>
							<td>
								<code style="font-weight: 700; color: #0284c7;"><?php echo esc_html( $log['action'] ); ?></code>
							</td>
							<td>
								<strong><?php echo esc_html( $log['actor_name'] ); ?></strong>
								<?php if ( $log['user_id'] ) : ?>
									<small style="color: #64748b;">(ID: <?php echo (int) $log['user_id']; ?>)</small>
								<?php endif; ?>
							</td>
							<td>
								<?php echo $log['license_id'] ? '#' . (int) $log['license_id'] : '&mdash;'; ?>
							</td>
							<td>
								<?php if ( ! empty( $log['context_parsed'] ) ) : ?>
									<pre style="margin: 0; padding: 6px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 11px; max-height: 80px; overflow-y: auto;"><?php echo esc_html( wp_json_encode( $log['context_parsed'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
								<?php else : ?>
									&mdash;
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	</div>
</div>
