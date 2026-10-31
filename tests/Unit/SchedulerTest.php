<?php
/**
 * Cron scheduler tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Cron\Scheduler;
use Backupino\Plugin;
use Backupino\Support\Dates;
use Backupino\Tests\Support\FakeDb;
use Backupino\Tests\TestCase;

/**
 * Covers monthly scheduling and cron synchronization.
 */
final class SchedulerTest extends TestCase {

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		$GLOBALS['wpdb'] = new FakeDb();
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$GLOBALS['backupino_cron']    = array();
		$GLOBALS['backupino_options'] = array();
	}

	/**
	 * Monthly schedule interval is registered.
	 *
	 * @return void
	 */
	public function test_add_schedules_registers_monthly(): void {
		$scheduler = new Scheduler( Plugin::instance() );
		$schedules = $scheduler->add_schedules( array() );

		$this->assertArrayHasKey( 'backupino_minute', $schedules );
		$this->assertArrayHasKey( 'weekly', $schedules );
		$this->assertArrayHasKey( 'backupino_monthly', $schedules );
		$this->assertSame( 2592000, $schedules['backupino_monthly']['interval'] );
	}

	/**
	 * When schedule is disabled, any active cron hook is cleared.
	 *
	 * @return void
	 */
	public function test_sync_schedule_clears_when_disabled(): void {
		$plugin    = Plugin::instance();
		$scheduler = new Scheduler( $plugin );

		$plugin->settings()->set( 'schedule.enabled', false );
		$GLOBALS['backupino_cron']['backupino_scheduled_backup'] = array(
			'timestamp'  => time() + 1000,
			'recurrence' => false,
			'args'       => array(),
		);

		$scheduler->sync_schedule();

		$this->assertFalse( wp_next_scheduled( 'backupino_scheduled_backup' ) );
	}

	/**
	 * When monthly schedule is enabled, event is scheduled for the first day of the next month.
	 *
	 * @return void
	 */
	public function test_sync_schedule_schedules_for_next_month_start(): void {
		$plugin    = Plugin::instance();
		$scheduler = new Scheduler( $plugin );

		$plugin->settings()->set( 'schedule.enabled', true );
		$plugin->settings()->set( 'schedule.interval', 'monthly' );

		$scheduler->sync_schedule();

		$scheduled_time = wp_next_scheduled( 'backupino_scheduled_backup' );
		$this->assertTrue( is_int( $scheduled_time ) );

		$expected = Dates::next_month_start( time() );
		$this->assertSame( $expected, $scheduled_time );
	}

	/**
	 * When scheduled backup creates a job, the next monthly run is scheduled.
	 *
	 * @return void
	 */
	public function test_create_scheduled_job_schedules_next_month(): void {
		$plugin    = Plugin::instance();
		$scheduler = new Scheduler( $plugin );

		$plugin->settings()->set( 'schedule.enabled', true );
		$plugin->settings()->set( 'schedule.interval', 'monthly' );

		$scheduler->create_scheduled_job();

		$scheduled_time = wp_next_scheduled( 'backupino_scheduled_backup' );
		$this->assertTrue( is_int( $scheduled_time ) );
		$this->assertGreaterThan( time(), $scheduled_time );
	}
}
