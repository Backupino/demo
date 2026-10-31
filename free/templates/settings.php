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


		<h2><?php esc_html_e( 'Notifications', 'backupino' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Backupino can announce every finished backup, with the permanent download link, in Telegram or Bale. The bot belongs to you: create one with @BotFather, then paste its token and your chat id here.', 'backupino' ); ?>
		</p>
		<div class="backupino-grid">
			<label>
				<?php esc_html_e( 'Messenger', 'backupino' ); ?>
				<select name="notify.service" id="backupino-notify-service">
					<option value="none" <?php selected( 'none', $backupino_values['notify']['service'] ); ?>>
						<?php esc_html_e( 'Disabled', 'backupino' ); ?>
					</option>
					<?php foreach ( \Backupino\Notify\ChannelFactory::services() as $backupino_service => $backupino_service_label ) : ?>
						<option value="<?php echo esc_attr( (string) $backupino_service ); ?>"
							<?php selected( (string) $backupino_service, (string) $backupino_values['notify']['service'] ); ?>>
							<?php echo esc_html( (string) $backupino_service_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<?php esc_html_e( 'Bot token', 'backupino' ); ?>
				<input type="password" name="notify.token" autocomplete="off"
					value="<?php echo esc_attr( (string) $backupino_values['notify']['token'] ); ?>">
			</label>
			<label>
				<?php esc_html_e( 'Chat id', 'backupino' ); ?>
				<input type="text" name="notify.chat_id"
					value="<?php echo esc_attr( (string) $backupino_values['notify']['chat_id'] ); ?>">
			</label>
		</div>
		<button type="button" class="button" id="backupino-notify-test">
			<?php esc_html_e( 'Send test message', 'backupino' ); ?>
		</button>
		<p class="backupino-result" id="backupino-notify-result"></p>
		<?php $backupino_queued = $plugin->notifier()->pending(); ?>
		<?php if ( $backupino_queued > 0 ) : ?>
			<p class="description">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of queued messages. */
						_n( '%d message is waiting to be resent on the next scheduled run.', '%d messages are waiting to be resent on the next scheduled run.', $backupino_queued, 'backupino' ),
						$backupino_queued
					)
				);
				?>
			</p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Schedule', 'backupino' ); ?></h2>
		<label class="backupino-check">
			<input type="checkbox" name="schedule.enabled" <?php checked( (bool) $backupino_values['schedule']['enabled'] ); ?>>
			<?php esc_html_e( 'Run automatic backups', 'backupino' ); ?>
		</label>
		<div class="backupino-grid">
			<label>
				<?php esc_html_e( 'Frequency', 'backupino' ); ?>
				<select name="schedule.interval">
					<?php foreach ( (array) apply_filters( 'backupino_schedule_intervals_labels', array( 'monthly' => __( 'Monthly (first day of the month)', 'backupino' ) ) ) as $backupino_interval => $backupino_interval_label ) : ?>
						<option value="<?php echo esc_attr( $backupino_interval ); ?>" <?php selected( $backupino_interval, $backupino_values['schedule']['interval'] ); ?>>
							<?php echo esc_html( is_string( $backupino_interval_label ) ? $backupino_interval_label : $backupino_interval ); ?>
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
