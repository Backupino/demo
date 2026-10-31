<?php
/**
 * Settings template.
 *
 * @package Backupino
 *
 * @var \Backupino\Plugin        $plugin   Plugin container.
 * @var \Backupino\Core\Settings $settings Settings.
 * @var \Backupino\Core\Paths    $paths    Paths.
 */

defined( 'ABSPATH' ) || exit;

$backupino_values = $settings->all();
?>
<div class="wrap backupino-wrap">
	<h1 class="backupino-title">
		<?php esc_html_e( 'Backupino settings', 'backupino' ); ?>
		<a class="button button-secondary" href="<?php echo esc_url( Views::url() ); ?>">
			<?php esc_html_e( 'Back to backups', 'backupino' ); ?>
		</a>
	</h1>

	<form id="backupino-settings" class="backupino-card">
		<h2><?php esc_html_e( 'Archive', 'backupino' ); ?></h2>
		<label>
			<?php esc_html_e( 'Excluded paths and patterns (one per line)', 'backupino' ); ?>
			<textarea name="archive.exclude_rules" rows="5" class="large-text code">
			<?php
				echo esc_textarea( implode( "\n", (array) $backupino_values['archive']['exclude_rules'] ) );
			?>
			</textarea>
		</label>
		<p class="description">
			<?php esc_html_e( 'Examples: wp-content/uploads/2022/, *.mp4, node_modules/. Cache and backup folders are always skipped.', 'backupino' ); ?>
		</p>

		<div class="backupino-grid">
			<label>
				<?php esc_html_e( 'Skip files larger than (MB, 0 = no limit)', 'backupino' ); ?>
				<input type="number" min="0" step="1" name="archive.ignore_larger_than_mb"
					value="<?php echo esc_attr( (string) ( (int) $backupino_values['archive']['ignore_larger_than'] / 1048576 ) ); ?>">
			</label>
			<label>
				<?php esc_html_e( 'Files packed per request', 'backupino' ); ?>
				<input type="number" min="10" max="5000" name="runtime.files_per_tick"
					value="<?php echo esc_attr( (string) $backupino_values['runtime']['files_per_tick'] ); ?>">
			</label>
		</div>

		<h2><?php esc_html_e( 'Storage', 'backupino' ); ?></h2>
		<label>
			<?php esc_html_e( 'Destination', 'backupino' ); ?>
			<select name="storage.driver" id="backupino-driver">
				<?php foreach ( $plugin->storages()->available() as $backupino_driver => $backupino_label ) : ?>
					<option value="<?php echo esc_attr( (string) $backupino_driver ); ?>"
						<?php selected( (string) $backupino_driver, (string) $backupino_values['storage']['driver'] ); ?>>
						<?php echo esc_html( (string) $backupino_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>


		<h2><?php esc_html_e( 'Schedule', 'backupino' ); ?></h2>
		<label class="backupino-check">
			<input type="checkbox" name="schedule.enabled" <?php checked( (bool) $backupino_values['schedule']['enabled'] ); ?>>
			<?php esc_html_e( 'Run automatic backups', 'backupino' ); ?>
		</label>
		<div class="backupino-grid">
			<label>
				<?php esc_html_e( 'Frequency', 'backupino' ); ?>
				<select name="schedule.interval">
					<?php foreach ( array( 'hourly', 'twicedaily', 'daily', 'weekly' ) as $backupino_interval ) : ?>
						<option value="<?php echo esc_attr( $backupino_interval ); ?>" <?php selected( $backupino_interval, $backupino_values['schedule']['interval'] ); ?>>
							<?php echo esc_html( $backupino_interval ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<?php esc_html_e( 'Keep at most', 'backupino' ); ?>
				<input type="number" min="1" max="100" name="schedule.keep" value="<?php echo esc_attr( (string) $backupino_values['schedule']['keep'] ); ?>">
			</label>
		</div>

		<h2><?php esc_html_e( 'Installer', 'backupino' ); ?></h2>
		<div class="backupino-grid">
			<label>
				<?php esc_html_e( 'Installer password (optional)', 'backupino' ); ?>
				<input type="text" name="installer.password" value="<?php echo esc_attr( (string) $backupino_values['installer']['password'] ); ?>">
			</label>
			<label>
				<?php esc_html_e( 'Default new URL (optional)', 'backupino' ); ?>
				<input type="url" name="installer.new_url" value="<?php echo esc_attr( (string) $backupino_values['installer']['new_url'] ); ?>">
			</label>
		</div>

		<p class="backupino-save">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'backupino' ); ?></button>
			<span class="backupino-result" id="backupino-settings-result"></span>
		</p>
	</form>

	<section class="backupino-card">
		<h2><?php esc_html_e( 'System status', 'backupino' ); ?></h2>
		<pre class="backupino-system" id="backupino-system"><?php esc_html_e( 'Loading…', 'backupino' ); ?></pre>
	</section>
</div>
