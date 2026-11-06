<?php
/**
 * Customer Dashboard View Template.
 *
 * Implements S3-T08, D-006, D-015, D-032, D-036, D-042.
 *
 * @var array{licenses: array, invoices: array, is_2fa_active: bool} $data
 * @package Backupino\Portal
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$licenses      = $data['licenses'] ?? array();
$invoices      = $data['invoices'] ?? array();
$is_2fa_active = (bool) ( $data['is_2fa_active'] ?? false );
$nonce         = wp_create_nonce( 'backupino_customer_nonce' );
?>

<div class="backupino-customer-dashboard" id="backupino-dashboard" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">

	<!-- Inline CSS based on Unified Design Tokens (D-036) & Subtle Glassmorphism -->
	<style>
		:root {
			--bk-primary: #0284c7;
			--bk-primary-hover: #0369a1;
			--bk-secondary: #0f172a;
			--bk-both: #16a34a;
			--bk-both-hover: #15803d;
			--bk-danger: #dc2626;
			--bk-warning: #d97706;
			--bk-bg: #f8fafc;
			--bk-card-bg: rgba(255, 255, 255, 0.95);
			--bk-card-glass: rgba(255, 255, 255, 0.85);
			--bk-text: #0f172a;
			--bk-muted: #64748b;
			--bk-border: #e2e8f0;
			--bk-radius: 12px;
			--bk-radius-sm: 8px;
			--bk-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -2px rgba(15, 23, 42, 0.03);
			--bk-font: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Arad", "Vazirmatn", sans-serif;
		}

		.backupino-customer-dashboard {
			font-family: var(--bk-font);
			color: var(--bk-text);
			margin-block: 24px;
			max-width: 100%;
		}

		.bk-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 16px;
			margin-bottom: 24px;
			padding-bottom: 16px;
			border-bottom: 1px solid var(--bk-border);
		}

		.bk-title {
			font-size: 24px;
			font-weight: 700;
			margin: 0;
			color: var(--bk-secondary);
		}

		.bk-security-badge {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			font-size: 13px;
			padding: 6px 12px;
			border-radius: 9999px;
			font-weight: 600;
		}

		.bk-security-badge.active {
			background: #ecfdf5;
			color: var(--bk-both);
			border: 1px solid #a7f3d0;
		}

		.bk-security-badge.warning {
			background: #fffbeb;
			color: var(--bk-warning);
			border: 1px solid #fde68a;
		}

		/* Grid & Cards */
		.bk-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
			gap: 20px;
			margin-bottom: 28px;
		}

		.bk-card {
			background: var(--bk-card-bg);
			border: 1px solid var(--bk-border);
			border-radius: var(--bk-radius);
			padding: 24px;
			box-shadow: var(--bk-shadow);
			position: relative;
			transition: transform 0.2s ease, box-shadow 0.2s ease;
		}

		@supports ((-webkit-backdrop-filter: blur(12px)) or (backdrop-filter: blur(12px))) {
			.bk-card {
				background: var(--bk-card-glass);
				-webkit-backdrop-filter: blur(12px);
				backdrop-filter: blur(12px);
			}
		}

		.bk-card:hover {
			box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.08);
		}

		.bk-card-header {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			margin-bottom: 16px;
		}

		.bk-plan-badge {
			background: #e0f2fe;
			color: var(--bk-primary);
			font-size: 12px;
			font-weight: 700;
			padding: 4px 10px;
			border-radius: 9999px;
			text-transform: uppercase;
		}

		.bk-status-badge {
			font-size: 11px;
			font-weight: 600;
			padding: 3px 8px;
			border-radius: 6px;
			display: inline-block;
		}

		.bk-status-active { background: #dcfce7; color: #15803d; }
		.bk-status-expiring_soon { background: #fef3c7; color: #b45309; }
		.bk-status-grace_period { background: #fee2e2; color: #b91c1c; }
		.bk-status-downgraded { background: #f1f5f9; color: #475569; }
		.bk-status-revoked { background: #fef2f2; color: #991b1b; }

		.bk-meta-row {
			display: flex;
			justify-content: space-between;
			padding-block: 8px;
			border-bottom: 1px dashed var(--bk-border);
			font-size: 13px;
		}

		.bk-meta-row:last-child {
			border-bottom: none;
		}

		.bk-meta-label {
			color: var(--bk-muted);
		}

		.bk-meta-val {
			font-weight: 600;
		}

		/* Tables */
		.bk-table-container {
			overflow-x: auto;
			margin-block: 16px;
			border: 1px solid var(--bk-border);
			border-radius: var(--bk-radius-sm);
		}

		.bk-table {
			width: 100%;
			border-collapse: collapse;
			text-align: start;
			font-size: 13px;
		}

		.bk-table th {
			background: #f8fafc;
			padding: 12px 16px;
			font-weight: 600;
			color: var(--bk-muted);
			border-bottom: 1px solid var(--bk-border);
		}

		.bk-table td {
			padding: 12px 16px;
			border-bottom: 1px solid var(--bk-border);
			vertical-align: middle;
		}

		.bk-table tr:last-child td {
			border-bottom: none;
		}

		/* Buttons */
		.bk-btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 6px;
			padding: 8px 14px;
			border-radius: var(--bk-radius-sm);
			font-size: 13px;
			font-weight: 600;
			cursor: pointer;
			border: none;
			transition: all 0.15s ease;
			text-decoration: none !important;
		}

		.bk-btn-primary {
			background: var(--bk-primary);
			color: #ffffff !important;
		}

		.bk-btn-primary:hover {
			background: var(--bk-primary-hover);
		}

		.bk-btn-secondary {
			background: #f1f5f9;
			color: var(--bk-secondary) !important;
			border: 1px solid var(--bk-border);
		}

		.bk-btn-secondary:hover {
			background: #e2e8f0;
		}

		.bk-btn-danger {
			background: #fef2f2;
			color: var(--bk-danger) !important;
			border: 1px solid #fecaca;
		}

		.bk-btn-danger:hover {
			background: #fee2e2;
		}

		.bk-btn-sm {
			padding: 5px 10px;
			font-size: 12px;
		}

		/* Modal */
		.bk-modal-backdrop {
			position: fixed;
			inset: 0;
			background: rgba(15, 23, 42, 0.6);
			display: none;
			align-items: center;
			justify-content: center;
			z-index: 99999;
			padding: 16px;
		}

		.bk-modal {
			background: #ffffff;
			border-radius: var(--bk-radius);
			max-width: 440px;
			width: 100%;
			padding: 24px;
			box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
			border: 1px solid var(--bk-border);
		}

		.bk-input {
			width: 100%;
			padding: 10px 14px;
			border: 1px solid var(--bk-border);
			border-radius: var(--bk-radius-sm);
			font-size: 14px;
			margin-top: 8px;
			box-sizing: border-box;
		}

		.bk-input:focus {
			outline: none;
			border-color: var(--bk-primary);
			box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2);
		}

		.bk-alert {
			padding: 12px 16px;
			border-radius: var(--bk-radius-sm);
			font-size: 13px;
			margin-bottom: 16px;
		}

		.bk-alert-info { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
		.bk-alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
		.bk-alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
	</style>

	<!-- Header with 2FA Indicator -->
	<div class="bk-header">
		<div>
			<h2 class="bk-title"><?php esc_html_e( 'Backupino Client Dashboard', 'backupino-portal' ); ?></h2>
			<span style="color: var(--bk-muted); font-size: 13px;">
				<?php esc_html_e( 'Manage your domains, cloud backups, and licenses.', 'backupino-portal' ); ?>
			</span>
		</div>
		<div>
			<?php if ( $is_2fa_active ) : ?>
				<span class="bk-security-badge active" title="<?php esc_attr_e( '2FA is active (Mandatory for remote restores D-042)', 'backupino-portal' ); ?>">
					🛡️ <?php esc_html_e( '2FA Protection Active', 'backupino-portal' ); ?>
				</span>
			<?php else : ?>
				<span class="bk-security-badge warning" title="<?php esc_attr_e( 'Enable 2FA to unlock remote restore capabilities', 'backupino-portal' ); ?>">
					⚠️ <?php esc_html_e( '2FA Inactive (Restores Locked)', 'backupino-portal' ); ?>
				</span>
			<?php endif; ?>
		</div>
	</div>

	<!-- Alert Messages Box -->
	<div id="bk-ajax-alert" style="display:none;" class="bk-alert"></div>

	<?php if ( empty( $licenses ) ) : ?>
		<div class="bk-card" style="text-align: center; padding: 48px;">
			<p style="color: var(--bk-muted); font-size: 15px; margin-bottom: 20px;">
				<?php esc_html_e( 'You do not have any active Backupino licenses yet.', 'backupino-portal' ); ?>
			</p>
			<a href="<?php echo esc_url( home_url( '/pricing' ) ); ?>" class="bk-btn bk-btn-primary">
				<?php esc_html_e( 'View Plans & Pricing', 'backupino-portal' ); ?>
			</a>
		</div>
	<?php else : ?>

		<!-- Licenses Overview Cards -->
		<?php foreach ( $licenses as $license ) : ?>
			<div class="bk-card" style="margin-bottom: 28px;">
				<div class="bk-card-header">
					<div>
						<span class="bk-plan-badge"><?php echo esc_html( $license['plan_name'] ); ?></span>
						<span class="bk-status-badge bk-status-<?php echo esc_attr( $license['status'] ); ?>">
							<?php echo esc_html( strtoupper( $license['status'] ) ); ?>
						</span>
						<h3 style="margin: 8px 0 0; font-size: 16px; font-family: monospace;">
							<?php echo esc_html( $license['masked_key'] ); ?>
						</h3>
					</div>
					<div style="text-align: end;">
						<span style="font-size: 12px; color: var(--bk-muted); display: block;">
							<?php esc_html_e( 'Offline Cache Remaining:', 'backupino-portal' ); ?>
						</span>
						<strong style="color: var(--bk-primary); font-size: 14px;">
							<?php printf( esc_html__( '%d Days', 'backupino-portal' ), (int) $license['offline_days_remaining'] ); ?>
						</strong>
					</div>
				</div>

				<div class="bk-grid" style="margin-bottom: 16px;">
					<div>
						<div class="bk-meta-row">
							<span class="bk-meta-label"><?php esc_html_e( 'Domain Slots Used:', 'backupino-portal' ); ?></span>
							<span class="bk-meta-val">
								<?php printf( esc_html__( '%1$d of %2$d Domains', 'backupino-portal' ), (int) $license['active_prod_count'], (int) $license['max_domains'] ); ?>
								<?php if ( $license['active_stage_count'] > 0 ) : ?>
									<small style="color: var(--bk-both); font-weight: normal;">
										(+<?php echo (int) $license['active_stage_count']; ?> <?php esc_html_e( 'Staging Free', 'backupino-portal' ); ?>)
									</small>
								<?php endif; ?>
							</span>
						</div>
						<div class="bk-meta-row">
							<span class="bk-meta-label"><?php esc_html_e( 'Expiration Date:', 'backupino-portal' ); ?></span>
							<span class="bk-meta-val">
								<?php echo ! empty( $license['expires_at'] ) ? esc_html( $license['expires_at'] ) : esc_html__( 'Lifetime / Perpetual', 'backupino-portal' ); ?>
							</span>
						</div>
					</div>

					<div>
						<div class="bk-meta-row">
							<span class="bk-meta-label"><?php esc_html_e( 'Snapshot Retention Duration:', 'backupino-portal' ); ?></span>
							<span class="bk-meta-val">
								<select id="retention-select-<?php echo (int) $license['id']; ?>" class="bk-input" style="width: auto; padding: 4px 8px; font-size: 12px; margin: 0;" onchange="updateRetention(<?php echo (int) $license['id']; ?>)">
									<option value="24" <?php selected( $license['snapshot_retention_h'], 24 ); ?>><?php esc_html_e( '24 Hours', 'backupino-portal' ); ?></option>
									<option value="48" <?php selected( $license['snapshot_retention_h'], 48 ); ?>><?php esc_html_e( '48 Hours (Default)', 'backupino-portal' ); ?></option>
									<option value="72" <?php selected( $license['snapshot_retention_h'], 72 ); ?>><?php esc_html_e( '72 Hours', 'backupino-portal' ); ?></option>
									<option value="168" <?php selected( $license['snapshot_retention_h'], 168 ); ?>><?php esc_html_e( '7 Days', 'backupino-portal' ); ?></option>
								</select>
							</span>
						</div>
						<div class="bk-meta-row">
							<span class="bk-meta-label"><?php esc_html_e( 'Available Slots:', 'backupino-portal' ); ?></span>
							<span class="bk-meta-val" style="color: var(--bk-both);">
								<?php echo (int) $license['available_slots']; ?> <?php esc_html_e( 'Remaining', 'backupino-portal' ); ?>
							</span>
						</div>
					</div>
				</div>

				<!-- Connected Sites & Activation Slots -->
				<h4 style="margin: 20px 0 10px; font-size: 14px; font-weight: 700; color: var(--bk-secondary);">
					<?php esc_html_e( 'Connected Sites & Activations', 'backupino-portal' ); ?>
				</h4>

				<?php if ( empty( $license['domains'] ) ) : ?>
					<p style="color: var(--bk-muted); font-size: 13px;">
						<?php esc_html_e( 'No domains activated yet. Enter your license key in your WordPress site to activate.', 'backupino-portal' ); ?>
					</p>
				<?php else : ?>
					<div class="bk-table-container">
						<table class="bk-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Domain Name', 'backupino-portal' ); ?></th>
									<th><?php esc_html_e( 'Type', 'backupino-portal' ); ?></th>
									<th><?php esc_html_e( 'Last Heartbeat', 'backupino-portal' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'backupino-portal' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $license['domains'] as $dom ) : ?>
									<tr>
										<td>
											<strong><?php echo esc_html( $dom['domain_name'] ); ?></strong>
										</td>
										<td>
											<?php if ( $dom['is_staging'] ) : ?>
												<span style="background: #f1f5f9; color: var(--bk-muted); padding: 2px 6px; border-radius: 4px; font-size: 11px;">
													<?php esc_html_e( 'Staging (Unlimited)', 'backupino-portal' ); ?>
												</span>
											<?php else : ?>
												<span style="background: #e0f2fe; color: #0284c7; padding: 2px 6px; border-radius: 4px; font-size: 11px;">
													<?php esc_html_e( 'Production Slot', 'backupino-portal' ); ?>
												</span>
											<?php endif; ?>
										</td>
										<td style="color: var(--bk-muted);">
											<?php echo ! empty( $dom['last_heartbeat_at'] ) ? esc_html( $dom['last_heartbeat_at'] ) : '&mdash;'; ?>
										</td>
										<td>
											<div style="display: flex; gap: 8px;">
												<!-- Manual Create Backup Button (Owner Only D-042) -->
												<button type="button" class="bk-btn bk-btn-primary bk-btn-sm" onclick="triggerBackup(<?php echo (int) $license['id']; ?>, <?php echo (int) $dom['id']; ?>)">
													⚡ <?php esc_html_e( 'Create Backup', 'backupino-portal' ); ?>
												</button>

												<!-- Deactivate Slot (D-006) -->
												<button type="button" class="bk-btn bk-btn-danger bk-btn-sm" onclick="deactivateSlot(<?php echo (int) $dom['id']; ?>)">
													✕ <?php esc_html_e( 'Deactivate', 'backupino-portal' ); ?>
												</button>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

				<!-- Cloud Backups & Download Links -->
				<h4 style="margin: 24px 0 10px; font-size: 14px; font-weight: 700; color: var(--bk-secondary);">
					<?php esc_html_e( 'Latest Backups & Restore', 'backupino-portal' ); ?>
				</h4>

				<?php if ( empty( $license['backups'] ) ) : ?>
					<p style="color: var(--bk-muted); font-size: 13px;">
						<?php esc_html_e( 'No backups stored in cloud for this license.', 'backupino-portal' ); ?>
					</p>
				<?php else : ?>
					<div class="bk-table-container">
						<table class="bk-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Package Name', 'backupino-portal' ); ?></th>
									<th><?php esc_html_e( 'Size', 'backupino-portal' ); ?></th>
									<th><?php esc_html_e( 'Date Created', 'backupino-portal' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'backupino-portal' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $license['backups'] as $bk ) : ?>
									<tr>
										<td>
											<span style="font-family: monospace; font-size: 12px; font-weight: 600;">
												<?php echo esc_html( $bk['package_name'] ); ?>
											</span>
										</td>
										<td><?php echo esc_html( $bk['formatted_size'] ); ?></td>
										<td style="color: var(--bk-muted);"><?php echo esc_html( $bk['created_at'] ); ?></td>
										<td>
											<div style="display: flex; gap: 8px;">
												<!-- Secure Download Link -->
												<a href="<?php echo esc_url( $bk['download_url'] ); ?>" class="bk-btn bk-btn-secondary bk-btn-sm">
													📥 <?php esc_html_e( 'Download', 'backupino-portal' ); ?>
												</a>

												<!-- Manual Restore Button (Strict 2FA D-042) -->
												<button type="button" class="bk-btn bk-btn-primary bk-btn-sm" onclick="openRestoreModal(<?php echo (int) $license['id']; ?>, <?php echo (int) $bk['id']; ?>, '<?php echo esc_js( $bk['package_name'] ); ?>')">
													🔄 <?php esc_html_e( 'Restore', 'backupino-portal' ); ?>
												</button>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

			</div>
		<?php endforeach; ?>

		<!-- Invoices Section -->
		<?php if ( ! empty( $invoices ) ) : ?>
			<div class="bk-card" style="margin-top: 24px;">
				<h3 style="margin: 0 0 16px; font-size: 16px; font-weight: 700; color: var(--bk-secondary);">
					<?php esc_html_e( 'Order Invoices & Billing', 'backupino-portal' ); ?>
				</h3>
				<div class="bk-table-container">
					<table class="bk-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Order #', 'backupino-portal' ); ?></th>
								<th><?php esc_html_e( 'Date', 'backupino-portal' ); ?></th>
								<th><?php esc_html_e( 'Total', 'backupino-portal' ); ?></th>
								<th><?php esc_html_e( 'Status', 'backupino-portal' ); ?></th>
								<th><?php esc_html_e( 'Invoice', 'backupino-portal' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $invoices as $inv ) : ?>
								<tr>
									<td>#<?php echo esc_html( $inv['order_number'] ); ?></td>
									<td><?php echo esc_html( $inv['date'] ); ?></td>
									<td><?php echo wp_kses_post( $inv['total'] ); ?></td>
									<td><?php echo esc_html( ucfirst( $inv['status'] ) ); ?></td>
									<td>
										<a href="<?php echo esc_url( $inv['invoice_url'] ); ?>" target="_blank" class="bk-btn bk-btn-secondary bk-btn-sm">
											📄 <?php esc_html_e( 'View Invoice', 'backupino-portal' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php endif; ?>

	<?php endif; ?>

	<!-- Modal for Manual Restore with Mandatory 2FA (D-042) -->
	<div id="bk-restore-modal" class="bk-modal-backdrop">
		<div class="bk-modal">
			<h3 style="margin: 0 0 12px; font-size: 18px; color: var(--bk-secondary);">
				🛡️ <?php esc_html_e( 'Confirm Remote Restore', 'backupino-portal' ); ?>
			</h3>
			<p style="color: var(--bk-muted); font-size: 13px; margin-bottom: 16px;">
				<?php esc_html_e( 'Per security invariant D-042, restoring a backup package requires mandatory 2FA authentication.', 'backupino-portal' ); ?>
			</p>

			<div style="background: #f8fafc; padding: 12px; border-radius: var(--bk-radius-sm); margin-bottom: 16px; font-size: 13px;">
				<strong><?php esc_html_e( 'Target Package:', 'backupino-portal' ); ?></strong>
				<div id="modal-pkg-name" style="font-family: monospace; word-break: break-all; margin-top: 4px;"></div>
			</div>

			<label for="modal-totp-code" style="font-size: 13px; font-weight: 600; display: block;">
				<?php esc_html_e( 'Enter 6-digit Authenticator Code (TOTP):', 'backupino-portal' ); ?>
			</label>
			<input type="text" id="modal-totp-code" maxlength="6" class="bk-input" placeholder="123456" autocomplete="off" />

			<div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px;">
				<button type="button" class="bk-btn bk-btn-secondary" onclick="closeRestoreModal()">
					<?php esc_html_e( 'Cancel', 'backupino-portal' ); ?>
				</button>
				<button type="button" id="btn-submit-restore" class="bk-btn bk-btn-primary" onclick="submitRestore()">
					<?php esc_html_e( 'Verify & Execute Restore', 'backupino-portal' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Minimal Vanilla JS Controller for Dashboard Actions -->
	<script>
		const BK_AJAX_URL = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
		const BK_NONCE    = "<?php echo esc_js( $nonce ); ?>";

		let activeRestoreLicenseId = 0;
		let activeRestoreBackupId  = 0;

		function showAlert(message, type = 'info') {
			const box = document.getElementById('bk-ajax-alert');
			box.className = 'bk-alert bk-alert-' + type;
			box.textContent = message;
			box.style.display = 'block';
			window.scrollTo({ top: box.offsetTop - 20, behavior: 'smooth' });
		}

		function updateRetention(licenseId) {
			const select = document.getElementById('retention-select-' + licenseId);
			const hours  = select.value;

			const body = new URLSearchParams({
				action: 'backupino_customer_update_retention',
				_wpnonce: BK_NONCE,
				license_id: licenseId,
				retention_hours: hours
			});

			fetch(BK_AJAX_URL, { method: 'POST', body })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						showAlert(res.data.message, 'success');
					} else {
						showAlert(res.data.message || 'Error updating retention', 'error');
					}
				});
		}

		function triggerBackup(licenseId, activationId) {
			if (!confirm('<?php echo esc_js( __( 'Trigger manual backup creation for this domain?', 'backupino-portal' ) ); ?>')) {
				return;
			}

			const body = new URLSearchParams({
				action: 'backupino_customer_create_backup',
				_wpnonce: BK_NONCE,
				license_id: licenseId,
				activation_id: activationId
			});

			fetch(BK_AJAX_URL, { method: 'POST', body })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						showAlert(res.data.message, 'success');
					} else {
						showAlert(res.data.message || 'Error queuing backup', 'error');
					}
				});
		}

		function deactivateSlot(activationId) {
			if (!confirm('<?php echo esc_js( __( 'Are you sure you want to release this domain slot? (72-hour cooling-off applies per D-006)', 'backupino-portal' ) ); ?>')) {
				return;
			}

			const body = new URLSearchParams({
				action: 'backupino_customer_deactivate_domain',
				_wpnonce: BK_NONCE,
				activation_id: activationId
			});

			fetch(BK_AJAX_URL, { method: 'POST', body })
				.then(r => r.json())
				.then(res => {
					if (res.success) {
						showAlert(res.data.message, 'success');
						setTimeout(() => location.reload(), 1500);
					} else {
						showAlert(res.data.message || 'Error deactivating domain slot', 'error');
					}
				});
		}

		function openRestoreModal(licenseId, backupId, pkgName) {
			activeRestoreLicenseId = licenseId;
			activeRestoreBackupId  = backupId;
			document.getElementById('modal-pkg-name').textContent = pkgName;
			document.getElementById('modal-totp-code').value = '';
			document.getElementById('bk-restore-modal').style.display = 'flex';
			document.getElementById('modal-totp-code').focus();
		}

		function closeRestoreModal() {
			document.getElementById('bk-restore-modal').style.display = 'none';
		}

		function submitRestore() {
			const code = document.getElementById('modal-totp-code').value.trim();
			if (!code || code.length !== 6) {
				alert('<?php echo esc_js( __( 'Please enter a valid 6-digit 2FA code.', 'backupino-portal' ) ); ?>');
				return;
			}

			const btn = document.getElementById('btn-submit-restore');
			btn.disabled = true;

			const body = new URLSearchParams({
				action: 'backupino_customer_restore',
				_wpnonce: BK_NONCE,
				license_id: activeRestoreLicenseId,
				backup_id: activeRestoreBackupId,
				totp_code: code
			});

			fetch(BK_AJAX_URL, { method: 'POST', body })
				.then(r => r.json())
				.then(res => {
					btn.disabled = false;
					closeRestoreModal();
					if (res.success) {
						showAlert(res.data.message, 'success');
					} else {
						showAlert(res.data.message || 'Error executing restore', 'error');
					}
				});
		}
	</script>
</div>
