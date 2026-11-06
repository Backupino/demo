<?php
/**
 * Operator Portal: Overview & Licenses View.
 *
 * Implements S3-T09 and D-042.
 *
 * @var array $metrics
 * @var array $licenses_data
 * @var string $nonce
 * @package Backupino\Portal
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap backupino-admin-wrap" style="max-width: 100%; margin-inline-end: 20px; box-sizing: border-box; margin-top: 20px;">

	<style>
		.backupino-admin-wrap { max-width: 100% !important; box-sizing: border-box; }
		.backupino-admin-wrap .bk-admin-card {
			max-width: 100% !important;
			width: 100% !important;
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			padding: 20px;
			box-sizing: border-box !important;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
			margin-bottom: 24px;
		}
		.backupino-admin-wrap .bk-metrics-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
			gap: 16px;
			margin-bottom: 24px;
		}
		.backupino-admin-wrap .bk-metric-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			padding: 16px 20px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
			box-sizing: border-box;
		}
		.backupino-admin-wrap table.widefat {
			width: 100% !important;
			max-width: 100% !important;
			table-layout: auto !important;
			border: 1px solid #e2e8f0;
			border-radius: 6px;
			box-shadow: none;
		}
		.backupino-admin-wrap table.widefat th,
		.backupino-admin-wrap table.widefat td {
			padding: 12px 14px;
			vertical-align: middle;
		}
		.backupino-admin-wrap table.widefat code {
			white-space: nowrap;
			font-family: Consolas, Monaco, monospace;
			font-size: 13px;
		}
	</style>

	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-shield-alt" style="font-size: 28px; width: 28px; height: 28px; color: #0284c7; vertical-align: middle;"></span>
		<?php esc_html_e( 'Backupino Portal &mdash; Operator Dashboard', 'backupino-portal' ); ?>
	</h1>

	<!-- Strict D-042 Security Invariant Banner -->
	<div class="notice notice-info" style="border-inline-start-color: #0284c7; padding: 12px 16px; margin-block: 16px; max-width: 100%;">
		<p style="margin: 0; font-size: 13px;">
			<strong>🛡️ <?php esc_html_e( 'Security Boundary D-042 Enforced:', 'backupino-portal' ); ?></strong>
			<?php esc_html_e( 'Operators and support specialists are strictly prohibited from triggering remote backups or restores on customer sites. All actions must be triggered exclusively by the account owner.', 'backupino-portal' ); ?>
		</p>
	</div>

	<!-- KPI Summary Metric Cards -->
	<div class="bk-metrics-grid">
		<div class="bk-metric-card">
			<h4 style="margin: 0 0 8px; color: #64748b; font-size: 13px;"><?php esc_html_e( 'Total Issued Licenses', 'backupino-portal' ); ?></h4>
			<span style="font-size: 26px; font-weight: 700; color: #0f172a;"><?php echo (int) $metrics['total_licenses']; ?></span>
			<div style="font-size: 12px; color: #16a34a; margin-top: 4px;"><?php echo (int) $metrics['active_licenses']; ?> <?php esc_html_e( 'Active', 'backupino-portal' ); ?></div>
		</div>

		<div class="bk-metric-card">
			<h4 style="margin: 0 0 8px; color: #64748b; font-size: 13px;"><?php esc_html_e( 'Lifecycle Warnings', 'backupino-portal' ); ?></h4>
			<span style="font-size: 26px; font-weight: 700; color: #d97706;"><?php echo (int) $metrics['expiring_soon']; ?></span>
			<div style="font-size: 12px; color: #dc2626; margin-top: 4px;"><?php echo (int) $metrics['grace_period']; ?> <?php esc_html_e( 'Grace Period', 'backupino-portal' ); ?></div>
		</div>

		<div class="bk-metric-card">
			<h4 style="margin: 0 0 8px; color: #64748b; font-size: 13px;"><?php esc_html_e( 'Active Domain Slots', 'backupino-portal' ); ?></h4>
			<span style="font-size: 26px; font-weight: 700; color: #0284c7;"><?php echo (int) $metrics['total_activations']; ?></span>
			<div style="font-size: 12px; color: #64748b; margin-top: 4px;">+<?php echo (int) $metrics['staging_activations']; ?> <?php esc_html_e( 'Staging (Free)', 'backupino-portal' ); ?></div>
		</div>

		<div class="bk-metric-card">
			<h4 style="margin: 0 0 8px; color: #64748b; font-size: 13px;"><?php esc_html_e( 'Cloud Storage Used', 'backupino-portal' ); ?></h4>
			<span style="font-size: 26px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $metrics['formatted_storage'] ); ?></span>
			<div style="font-size: 12px; color: #64748b; margin-top: 4px;"><?php echo (int) $metrics['total_audit_events']; ?> <?php esc_html_e( 'Audit Events', 'backupino-portal' ); ?></div>
		</div>
	</div>

	<div style="clear: both; margin-bottom: 24px;"></div>

	<!-- Licenses Table -->
	<div class="bk-admin-card" style="width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; clear: both;">
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
			<h2 style="margin: 0; font-size: 16px; font-weight: 700;"><?php esc_html_e( 'Registered Licenses', 'backupino-portal' ); ?> (<?php echo (int) $licenses_data['total']; ?>)</h2>
			<form method="get" style="display: flex; gap: 8px;">
				<input type="hidden" name="page" value="backupino-portal" />
				<input type="search" name="s" value="<?php echo esc_attr( $_GET['s'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Search by key or plan...', 'backupino-portal' ); ?>" class="regular-text" style="width: 220px;" />
				<select name="status">
					<option value=""><?php esc_html_e( 'All Statuses', 'backupino-portal' ); ?></option>
					<option value="active" <?php selected( $_GET['status'] ?? '', 'active' ); ?>><?php esc_html_e( 'Active', 'backupino-portal' ); ?></option>
					<option value="expiring_soon" <?php selected( $_GET['status'] ?? '', 'expiring_soon' ); ?>><?php esc_html_e( 'Expiring Soon', 'backupino-portal' ); ?></option>
					<option value="grace_period" <?php selected( $_GET['status'] ?? '', 'grace_period' ); ?>><?php esc_html_e( 'Grace Period', 'backupino-portal' ); ?></option>
					<option value="downgraded" <?php selected( $_GET['status'] ?? '', 'downgraded' ); ?>><?php esc_html_e( 'Downgraded', 'backupino-portal' ); ?></option>
					<option value="revoked" <?php selected( $_GET['status'] ?? '', 'revoked' ); ?>><?php esc_html_e( 'Revoked', 'backupino-portal' ); ?></option>
				</select>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'backupino-portal' ); ?></button>
			</form>
		</div>

		<div style="width: 100%; overflow-x: auto;">
			<table class="bk-modern-table" style="width: 100% !important; max-width: 100% !important; table-layout: auto !important;">
				<thead>
					<tr>
						<th style="width: 50px; white-space: nowrap;"><?php esc_html_e( 'ID', 'backupino-portal' ); ?></th>
						<th style="min-width: 220px; white-space: nowrap;"><?php esc_html_e( 'License Key', 'backupino-portal' ); ?></th>
						<th style="min-width: 160px;"><?php esc_html_e( 'Customer', 'backupino-portal' ); ?></th>
						<th style="width: 90px; white-space: nowrap;"><?php esc_html_e( 'Plan', 'backupino-portal' ); ?></th>
						<th style="width: 80px; white-space: nowrap;"><?php esc_html_e( 'Slots', 'backupino-portal' ); ?></th>
						<th style="width: 100px; white-space: nowrap;"><?php esc_html_e( 'Status', 'backupino-portal' ); ?></th>
						<th style="width: 160px; white-space: nowrap;"><?php esc_html_e( 'Expires At', 'backupino-portal' ); ?></th>
						<th style="width: 100px; text-align: end; white-space: nowrap;"><?php esc_html_e( 'Actions', 'backupino-portal' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $licenses_data['items'] ) ) : ?>
						<tr>
							<td colspan="8"><?php esc_html_e( 'No licenses found.', 'backupino-portal' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $licenses_data['items'] as $item ) : ?>
							<tr>
								<td style="white-space: nowrap;">#<?php echo (int) $item['id']; ?></td>
								<td style="white-space: nowrap;">
									<code style="font-weight: 600; color: #0284c7; white-space: nowrap;"><?php echo esc_html( $item['license_key'] ); ?></code>
								</td>
								<td>
									<strong><?php echo esc_html( $item['user_display_name'] ); ?></strong><br>
									<small style="color: #64748b;"><?php echo esc_html( $item['user_email'] ); ?></small>
								</td>
								<td style="white-space: nowrap;">
									<span class="badge" style="background: #e0f2fe; color: #0284c7; padding: 3px 8px; border-radius: 4px; font-weight: 700; font-size: 11px; white-space: nowrap;">
										<?php echo esc_html( strtoupper( $item['plan_slug'] ) ); ?>
									</span>
								</td>
								<td style="white-space: nowrap;">
									<strong><?php echo (int) $item['active_domains_count']; ?></strong> / <?php echo (int) $item['max_domains']; ?>
								</td>
								<td style="white-space: nowrap;">
									<?php
									$color_map = array(
										'active'        => '#15803d; background: #dcfce7',
										'expiring_soon' => '#b45309; background: #fef3c7',
										'grace_period'  => '#b91c1c; background: #fee2e2',
										'downgraded'    => '#475569; background: #f1f5f9',
										'revoked'       => '#991b1b; background: #fef2f2',
									);
									$style = $color_map[ $item['status'] ] ?? '#334155; background: #f1f5f9';
									?>
									<span style="padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; color: <?php echo esc_attr( $style ); ?>; white-space: nowrap;">
										<?php echo esc_html( strtoupper( $item['status'] ) ); ?>
									</span>
								</td>
								<td style="color: #475569; white-space: nowrap;">
									<?php echo ! empty( $item['expires_at'] ) ? esc_html( $item['expires_at'] ) : esc_html__( 'Perpetual', 'backupino-portal' ); ?>
								</td>
								<td style="text-align: end; white-space: nowrap;">
									<?php if ( current_user_can( 'administrator' ) || current_user_can( \Backupino\Portal\Auth\RoleManager::CAP_ADMIN_PORTAL ) ) : ?>
										<button type="button" class="button button-small" onclick="openOverrideModal(<?php echo (int) $item['id']; ?>, '<?php echo esc_js( $item['license_key'] ); ?>', '<?php echo esc_js( $item['status'] ); ?>', <?php echo (int) $item['max_domains']; ?>, <?php echo (int) round( $item['cloud_quota_bytes'] / 1073741824 ); ?>)">
											⚙️ <?php esc_html_e( 'Override', 'backupino-portal' ); ?>
										</button>
									<?php else : ?>
										<span style="color: #94a3b8; font-size: 12px;"><?php esc_html_e( 'View Only', 'backupino-portal' ); ?></span>
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

	<!-- Admin Override Modal -->
	<div id="bk-admin-override-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:99999; align-items:center; justify-content:center;">
		<div style="background:#fff; border-radius:8px; max-width:480px; width:100%; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);">
			<h3 style="margin-top:0;">⚙️ <?php esc_html_e( 'Administrator Quota & Status Override', 'backupino-portal' ); ?></h3>
			<p style="font-size:12px; color:#64748b;">
				<?php esc_html_e( 'Overrides require a mandatory documented reason recorded in the security audit log.', 'backupino-portal' ); ?>
			</p>
			
			<div style="margin-bottom:12px;">
				<label><strong><?php esc_html_e( 'License Key:', 'backupino-portal' ); ?></strong></label>
				<div id="override-lic-key" style="font-family:monospace; margin-top:4px;"></div>
			</div>

			<div style="margin-bottom:12px;">
				<label><strong><?php esc_html_e( 'Status:', 'backupino-portal' ); ?></strong></label>
				<select id="override-status" style="width:100%;">
					<option value="active"><?php esc_html_e( 'Active', 'backupino-portal' ); ?></option>
					<option value="expiring_soon"><?php esc_html_e( 'Expiring Soon', 'backupino-portal' ); ?></option>
					<option value="grace_period"><?php esc_html_e( 'Grace Period', 'backupino-portal' ); ?></option>
					<option value="downgraded"><?php esc_html_e( 'Downgraded (Read-Only)', 'backupino-portal' ); ?></option>
					<option value="revoked"><?php esc_html_e( 'Revoked', 'backupino-portal' ); ?></option>
				</select>
			</div>

			<div style="margin-bottom:12px;">
				<label><strong><?php esc_html_e( 'Max Domains (Slots):', 'backupino-portal' ); ?></strong></label>
				<input type="number" id="override-domains" style="width:100%;" min="1" max="500" />
			</div>

			<div style="margin-bottom:12px;">
				<label><strong><?php esc_html_e( 'Cloud Storage Quota (GB):', 'backupino-portal' ); ?></strong></label>
				<input type="number" id="override-quota" style="width:100%;" min="0" max="5000" />
			</div>

			<div style="margin-bottom:16px;">
				<label><strong><?php esc_html_e( 'Audit Reason (Mandatory):', 'backupino-portal' ); ?></strong></label>
				<textarea id="override-reason" rows="2" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. VIP client upgrade exception / billing resolution...', 'backupino-portal' ); ?>"></textarea>
			</div>

			<div style="display:flex; justify-content:flex-end; gap:8px;">
				<button type="button" class="button" onclick="closeOverrideModal()"><?php esc_html_e( 'Cancel', 'backupino-portal' ); ?></button>
				<button type="button" class="button button-primary" onclick="submitOverride()"><?php esc_html_e( 'Save Override', 'backupino-portal' ); ?></button>
			</div>
		</div>
	</div>

	<script>
		const OPERATOR_NONCE = "<?php echo esc_js( $nonce ); ?>";
		let activeLicenseId  = 0;

		function openOverrideModal(id, key, status, domains, quota) {
			activeLicenseId = id;
			document.getElementById('override-lic-key').textContent = key;
			document.getElementById('override-status').value = status;
			document.getElementById('override-domains').value = domains;
			document.getElementById('override-quota').value = quota;
			document.getElementById('override-reason').value = '';
			document.getElementById('bk-admin-override-modal').style.display = 'flex';
		}

		function closeOverrideModal() {
			document.getElementById('bk-admin-override-modal').style.display = 'none';
		}

		function submitOverride() {
			const reason = document.getElementById('override-reason').value.trim();
			if (!reason) {
				alert('<?php echo esc_js( __( 'Please provide a documented audit reason for this override.', 'backupino-portal' ) ); ?>');
				return;
			}

			const body = new URLSearchParams({
				action: 'backupino_admin_override',
				_wpnonce: OPERATOR_NONCE,
				license_id: activeLicenseId,
				status: document.getElementById('override-status').value,
				max_domains: document.getElementById('override-domains').value,
				quota_gb: document.getElementById('override-quota').value,
				reason: reason
			});

			fetch(ajaxurl, { method: 'POST', body })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						alert(res.data.message);
						location.reload();
					} else {
						alert(res.data.message || 'Error executing override');
					}
				});
		}
	</script>
</div>
