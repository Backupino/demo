<?php
/**
 * Job entity tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Jobs\Job;
use Backupino\Jobs\JobStatus;
use Backupino\Support\SystemClock;
use Backupino\Tests\TestCase;

/**
 * Fixed clock for deterministic assertions.
 */
final class FixedClock implements \Backupino\Support\Clock {

	/**
	 * {@inheritDoc}
	 */
	public function now(): int {
		return 1700000000;
	}

	/**
	 * {@inheritDoc}
	 */
	public function now_mysql(): string {
		return '2023-11-14 22:13:20';
	}
}

/**
 * Covers hydration/serialization of jobs.
 */
final class JobTest extends TestCase {

	/**
	 * A new job is queued and has a token.
	 *
	 * @return void
	 */
	public function test_package_default_name_has_random_suffix(): void {
		$name = \Backupino\Backup\Package::default_name( 'mysite' );
		$this->assertSame( 1, preg_match( '/^mysite_\\d{8}_\\d{6}_[a-f0-9]{12}$/', $name ) );
	}

	public function test_create(): void {
		$job = Job::create( array( 'type' => 'manual' ), new SystemClock() );

		$this->assertSame( JobStatus::QUEUED, $job->status );
		$this->assertSame( 16, strlen( $job->token ) );
		$this->assertSame( 'manual', $job->option( 'type' ) );
		$this->assertNull( $job->option( 'missing' ) );
		$this->assertSame( 'fallback', $job->option( 'missing', 'fallback' ) );
	}

	/**
	 * Jobs survive a database round-trip.
	 *
	 * @return void
	 */
	public function test_row_roundtrip(): void {
		$clock = new FixedClock();
		$job   = Job::create( array( 'type' => 'manual' ), $clock );

		$job->id       = 7;
		$job->status   = JobStatus::RUNNING;
		$job->stage    = 'files';
		$job->progress = 42;
		$job->message  = 'Packing files';
		$job->set_stage_state( 'files', array( 'offset' => 128, 'added' => 64 ) );

		$row = $job->to_row( $clock );

		$this->assertSame( 7, $row['id'] );
		$this->assertSame( 'running', $row['status'] );

		$restored = Job::from_row( $row );

		$this->assertSame( 7, $restored->id );
		$this->assertSame( 'files', $restored->stage );
		$this->assertSame( 42, $restored->progress );
		$this->assertSame( array( 'offset' => 128, 'added' => 64 ), $restored->stage_state( 'files' ) );
		$this->assertSame( array(), $restored->stage_state( 'unknown' ) );
		$this->assertSame( 'manual', $restored->option( 'type' ) );
	}

	/**
	 * Corrupt payloads degrade gracefully.
	 *
	 * @return void
	 */
	public function test_from_row_with_broken_payload(): void {
		$job = Job::from_row(
			array(
				'id'      => 3,
				'token'   => 'abc',
				'status'  => 'queued',
				'payload' => '{not json',
			)
		);

		$this->assertSame( array(), $job->payload['stages'] );
		$this->assertSame( array(), $job->payload['options'] );
	}

	/**
	 * Status helpers.
	 *
	 * @return void
	 */
	public function test_status_helpers(): void {
		$this->assertTrue( JobStatus::is_final( JobStatus::COMPLETED ) );
		$this->assertTrue( JobStatus::is_final( JobStatus::FAILED ) );
		$this->assertFalse( JobStatus::is_final( JobStatus::RUNNING ) );
		$this->assertTrue( JobStatus::is_runnable( JobStatus::QUEUED ) );
		$this->assertFalse( JobStatus::is_runnable( JobStatus::CANCELLED ) );
	}

	/**
	 * The API representation exposes the fields the UI needs.
	 *
	 * @return void
	 */
	public function test_api_shape(): void {
		$job = Job::create( array( 'type' => 'manual' ), new FixedClock() );
		$api = $job->to_api();

		foreach ( array( 'id', 'token', 'status', 'statusText', 'stage', 'progress', 'message', 'createdAt', 'options' ) as $key ) {
			$this->assertArrayHasKey( $key, $api );
		}
	}
}
