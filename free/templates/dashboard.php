<?php
/**
 * Dashboard template.
 *
 * @package Backupino
 *
 * @var \Backupino\Plugin          $plugin   Plugin container.
 * @var \Backupino\Core\Settings   $settings Settings.
 * @var \Backupino\Core\Paths      $paths    Paths.
 * @var array<int, \Backupino\Jobs\Job> $jobs Jobs.
 */

defined( 'ABSPATH' ) || exit;

$backupino_issues = \Backupino\Core\Compatibility::warnings();
?>
<div class="wrap backupino-wrap" id="backupino-app">
	<h1 class="backupino-title">
		<?php esc_html_e( 'Backups', 'backupino' ); ?>
		<a class="button button-secondary" href="<?php echo esc_url( Views::url( 'settings' ) ); ?>">
			<?php esc_html_e( 'Settings', 'backupino' ); ?>
		</a>
	</h1>

	<?php if ( array() !== $backupino_issues ) : ?>
		<div class="notice notice-warning inline">
			<ul>
				<?php foreach ( $backupino_issues as $backupino_issue ) : ?>
					<li><?php echo esc_html( $backupino_issue ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<div class="backupino-grid">
		<section class="backupino-card">
			<h2><?php esc_html_e( 'Create a backup', 'backupino' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Everything is packed into one archive plus a standalone installer that rebuilds the site on any empty server.', 'backupino' ); ?>
			</p>

			<noscript>
				<p><strong><?php esc_html_e( 'JavaScript is required to build backups.', 'backupino' ); ?></strong></p>
			</noscript>

			<form id="backupino-create" class="backupino-form">
				<label class="backupino-check">
					<input type="checkbox" name="files" checked>
					<?php esc_html_e( 'Include files', 'backupino' ); ?>
				</label>
				<label class="backupino-check">
					<input type="checkbox" name="database" checked>
					<?php esc_html_e( 'Include database', 'backupino' ); ?>
				</label>
				<label>
					<?php esc_html_e( 'Label (optional)', 'backupino' ); ?>
					<input type="text" name="note" maxlength="120" placeholder="<?php esc_attr_e( 'Before updating plugins', 'backupino' ); ?>">
				</label>
				<button type="submit" class="button button-primary" id="backupino-start">
					<?php esc_html_e( 'Create backup', 'backupino' ); ?>
				</button>
			</form>

			<div id="backupino-progress" class="backupino-progress hidden">
				<div class="backupino-bar"><span></span></div>
				<p class="backupino-progress-text"></p>
			</div>
		</section>

		<section class="backupino-card">
			<h2><?php esc_html_e( 'Destination', 'backupino' ); ?></h2>
			<p class="description">
				<?php
				printf(
					/* translators: %s: storage driver label. */
					esc_html__( 'Packages are stored on: %s', 'backupino' ),
					'<strong>' . esc_html( $plugin->storages()->driver()->label() ) . '</strong>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
				?>
			</p>
			<p class="description">
				<?php
				printf(
					/* translators: %s: directory path. */
					esc_html__( 'Local folder: %s', 'backupino' ),
					'<code>' . esc_html( $paths->packages() ) . '</code>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
				?>
			</p>
			<button type="button" class="button" id="backupino-test-storage">
				<?php esc_html_e( 'Test storage', 'backupino' ); ?>
			</button>
			<p class="backupino-result" id="backupino-storage-result"></p>
		</section>
	</div>

	<section class="backupino-card">
		<h2><?php esc_html_e( 'Recent backups', 'backupino' ); ?></h2>
		<table class="widefat striped backupino-table" id="backupino-jobs">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Created', 'backupino' ); ?></th>
					<th><?php esc_html_e( 'Status', 'backupino' ); ?></th>
					<th><?php esc_html_e( 'Progress', 'backupino' ); ?></th>
					<th><?php esc_html_e( 'Package', 'backupino' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'backupino' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( array() === $jobs ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No backups yet.', 'backupino' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $jobs as $backupino_job ) : ?>
					<tr data-job="<?php echo esc_attr( (string) $backupino_job->id ); ?>">
						<td><?php echo esc_html( get_date_from_gmt( $backupino_job->created_at, 'Y-m-d H:i' ) ); ?></td>
						<td><span class="backupino-status backupino-status--<?php echo esc_attr( $backupino_job->status ); ?>">
							<?php echo esc_html( \Backupino\Jobs\JobStatus::label( $backupino_job->status ) ); ?>
						</span></td>
						<td><?php echo esc_html( (string) $backupino_job->progress ); ?>%</td>
						<td>
							<?php
							if ( isset( $backupino_job->payload['package']['name'] ) ) {
								echo esc_html( (string) $backupino_job->payload['package']['name'] );
							} else {
								echo '&mdash;';
							}
							?>
						</td>
						<td>
							<button type="button" class="button-link backupino-delete" data-job="<?php echo esc_attr( (string) $backupino_job->id ); ?>">
								<?php esc_html_e( 'Delete', 'backupino' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</section>
</div>
