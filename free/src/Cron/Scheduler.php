<?php
/**
 * Cron scheduling.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Cron;

use Backupino\Jobs\Job;
use Backupino\Jobs\JobStatus;
use Backupino\Plugin;

/**
 * Runs queued jobs in the background and creates scheduled backups.
 */
final class Scheduler {

	/**
	 * Plugin container.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Plugin container.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );
		add_action( 'backupino_run_jobs', array( $this, 'run_jobs' ) );
		add_action( 'backupino_scheduled_backup', array( $this, 'create_scheduled_job' ) );
		add_action( 'init', array( $this, 'sync_schedule' ) );
	}

	/**
	 * Add the custom cron intervals.
	 *
	 * @param array<string, array<string, mixed>> $schedules Existing schedules.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_schedules( array $schedules ): array {
		if ( ! isset( $schedules['backupino_minute'] ) ) {
			$schedules['backupino_minute'] = array(
				'interval' => 60,
				'display'  => __( 'Backupino: every minute', 'backupino' ),
			);
		}

		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => 604800,
				'display'  => __( 'Once weekly', 'backupino' ),
			);
		}

		return $schedules;
	}

	/**
	 * Keep the scheduled backup event in sync with the settings.
	 *
	 * @return void
	 */
	public function sync_schedule(): void {
		$enabled  = (bool) $this->plugin->settings()->get( 'schedule.enabled', false );
		$interval = (string) $this->plugin->settings()->get( 'schedule.interval', 'daily' );
		$stored   = get_option( 'backupino_schedule_state', array() );
		$current  = is_array( $stored ) ? (string) ( $stored['interval'] ?? '' ) : '';

		if ( ! $enabled ) {
			if ( false !== wp_next_scheduled( 'backupino_scheduled_backup' ) ) {
				wp_clear_scheduled_hook( 'backupino_scheduled_backup' );
				update_option( 'backupino_schedule_state', array( 'interval' => '' ), false );
			}

			return;
		}

		if ( $current === $interval && false !== wp_next_scheduled( 'backupino_scheduled_backup' ) ) {
			return;
		}

		wp_clear_scheduled_hook( 'backupino_scheduled_backup' );
		wp_schedule_event( time() + 300, $interval, 'backupino_scheduled_backup' );
		update_option( 'backupino_schedule_state', array( 'interval' => $interval ), false );
	}

	/**
	 * Create a job for the scheduled backup.
	 *
	 * @return void
	 */
	public function create_scheduled_job(): void {
		$job = Job::create(
			array(
				'type'     => 'scheduled',
				'note'     => __( 'Scheduled backup', 'backupino' ),
				'files'    => (bool) $this->plugin->settings()->get( 'archive.files', true ),
				'database' => (bool) $this->plugin->settings()->get( 'archive.database', true ),
			),
			$this->plugin->clock()
		);

		$this->plugin->jobs()->create( $job );

		$this->plugin->logger()->info( 'Scheduled job queued.', array( 'job' => $job->id ) );
	}

	/**
	 * Advance pending jobs in the background.
	 *
	 * @return void
	 */
	public function run_jobs(): void {
		$deadline = time() + 25;

		foreach ( $this->plugin->jobs()->pending() as $job ) {
			if ( time() > $deadline || ! $job->is_runnable() ) {
				break;
			}

			$job = $this->plugin->runner()->step( $job );

			if ( JobStatus::COMPLETED === $job->status ) {
				Retention::enforce( $this->plugin );
			}
		}
	}
}
