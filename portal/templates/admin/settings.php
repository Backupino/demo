<?php
/**
 * Operator Portal: Feature Flags & Version Compatibility Matrix View.
 *
 * Implements S3-T09 Administrator Settings.
 *
 * @var array $flags
 * @var array $matrix
 * @var string $nonce
 * @package Backupino\Portal
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap backupino-admin-wrap" style="max-width: 1000px; margin-top: 20px;">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-admin-settings" style="font-size: 28px; width: 28px; height: 28px; color: #0284c7; vertical-align: middle;"></span>
		<?php esc_html_e( 'System Feature Flags & Compatibility Matrix', 'backupino-portal' ); ?>
	</h1>

	<!-- Strict D-042 Security Invariant Banner -->
	<div class="notice notice-info" style="border-inline-start-color: #0284c7; padding: 12px 16px; margin-block: 16px;">
		<p style="margin: 0; font-size: 13px;">
			<strong>🛡️ <?php esc_html_e( 'Administrator Console:', 'backupino-portal' ); ?></strong>
			<?php esc_html_e( 'Manage global portal switches and the distribution matrix for Pro client updater endpoints (D-049).', 'backupino-portal' ); ?>
		</p>
	</div>

	<!-- 1. Feature Flags -->
	<div class="card" style="margin: 0 0 24px 0; padding: 20px; border-radius: 8px;">
		<h2 style="margin: 0 0 16px; font-size: 16px;"><?php esc_html_e( 'Global System Feature Flags', 'backupino-portal' ); ?></h2>

		<form id="form-feature-flags" onsubmit="saveFeatureFlags(event)">
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Trial Registrations', 'backupino-portal' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="flag-trial" <?php checked( $flags['allow_trial_registration'] ); ?> />
							<?php esc_html_e( 'Allow new users to activate 10-day free trial licenses', 'backupino-portal' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Dedicated Cloud Storage', 'backupino-portal' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="flag-cloud" <?php checked( $flags['cloud_storage_enabled'] ); ?> />
							<?php esc_html_e( 'Enable portal-managed cloud storage and snapshot endpoints', 'backupino-portal' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cooling-Off Period (D-006)', 'backupino-portal' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="flag-cooldown" <?php checked( $flags['strict_cooling_off_enabled'] ); ?> />
							<?php esc_html_e( 'Enforce 72-hour cooling-off delay after domain release', 'backupino-portal' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Ed25519 Auto Key Rotation', 'backupino-portal' ); ?></th>
					<td>
						<label>
							<input type="checkbox" id="flag-rotation" <?php checked( $flags['key_rotation_auto'] ); ?> />
							<?php esc_html_e( 'Automate 90-day asymmetric key rotation with grace window', 'backupino-portal' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Feature Flags', 'backupino-portal' ); ?></button>
			</p>
		</form>
	</div>

	<!-- 2. Version Compatibility Matrix -->
	<div class="card" style="margin: 0; padding: 20px; border-radius: 8px;">
		<h2 style="margin: 0 0 16px; font-size: 16px;"><?php esc_html_e( 'Pro Client Compatibility Matrix (D-049)', 'backupino-portal' ); ?></h2>

		<form id="form-version-matrix" onsubmit="saveVersionMatrix(event)">
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Latest Pro Version', 'backupino-portal' ); ?></th>
					<td>
						<input type="text" id="matrix-latest" value="<?php echo esc_attr( $matrix['pro_latest_version'] ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Minimum Required Pro Version', 'backupino-portal' ); ?></th>
					<td>
						<input type="text" id="matrix-min" value="<?php echo esc_attr( $matrix['pro_min_required_version'] ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'WordPress Tested Up To', 'backupino-portal' ); ?></th>
					<td>
						<input type="text" id="matrix-wp" value="<?php echo esc_attr( $matrix['wp_tested_up_to'] ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Minimum PHP Version', 'backupino-portal' ); ?></th>
					<td>
						<input type="text" id="matrix-php" value="<?php echo esc_attr( $matrix['php_min_version'] ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Release Date', 'backupino-portal' ); ?></th>
					<td>
						<input type="date" id="matrix-date" value="<?php echo esc_attr( $matrix['released_at'] ); ?>" class="regular-text" />
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Compatibility Matrix', 'backupino-portal' ); ?></button>
			</p>
		</form>
	</div>

	<script>
		const OPERATOR_NONCE = "<?php echo esc_js( $nonce ); ?>";

		function saveFeatureFlags(e) {
			e.preventDefault();
			const body = new URLSearchParams({
				action: 'backupino_admin_save_flags',
				_wpnonce: OPERATOR_NONCE,
				allow_trial_registration: document.getElementById('flag-trial').checked ? 1 : 0,
				cloud_storage_enabled: document.getElementById('flag-cloud').checked ? 1 : 0,
				strict_cooling_off_enabled: document.getElementById('flag-cooldown').checked ? 1 : 0,
				key_rotation_auto: document.getElementById('flag-rotation').checked ? 1 : 0,
			});

			fetch(ajaxurl, { method: 'POST', body })
				.then(r => r.json())
				.then(res => {
					alert(res.data.message || 'Updated');
				});
		}

		function saveVersionMatrix(e) {
			e.preventDefault();
			const body = new URLSearchParams({
				action: 'backupino_admin_save_matrix',
				_wpnonce: OPERATOR_NONCE,
				pro_latest_version: document.getElementById('matrix-latest').value,
				pro_min_required_version: document.getElementById('matrix-min').value,
				wp_tested_up_to: document.getElementById('matrix-wp').value,
				php_min_version: document.getElementById('matrix-php').value,
				released_at: document.getElementById('matrix-date').value,
			});

			fetch(ajaxurl, { method: 'POST', body })
				.then(r => r.json())
				.then(res => {
					alert(res.data.message || 'Updated');
				});
		}
	</script>
</div>
