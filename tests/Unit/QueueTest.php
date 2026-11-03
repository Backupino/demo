<?php
/**
 * Notification queue tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Notify\Queue;
use Backupino\Tests\Support\MemoryOptions;
use Backupino\Tests\TestCase;

/**
 * Covers retry behaviour of undelivered notifications.
 */
final class QueueTest extends TestCase {

	/**
	 * The queue never grows beyond its limit and drops the oldest entry.
	 *
	 * @return void
	 */
	public function test_queue_is_capped(): void {
		$queue = new Queue( new MemoryOptions() );

		for ( $i = 1; $i <= Queue::LIMIT + 3; $i++ ) {
			$queue->push( 'message ' . $i );
		}

		$items = $queue->all();

		$this->assertCount( Queue::LIMIT, $items );
		$this->assertSame( 'message ' . ( Queue::LIMIT + 3 ), $items[ Queue::LIMIT - 1 ]['text'] );
	}

	/**
	 * A successful retry clears the queue.
	 *
	 * @return void
	 */
	public function test_successful_flush_clears_the_queue(): void {
		$queue = new Queue( new MemoryOptions() );
		$queue->push( 'one' );
		$queue->push( 'two' );

		$delivered = $queue->flush(
			static function ( $text ) {
				return true;
			}
		);

		$this->assertSame( 2, $delivered );
		$this->assertTrue( $queue->is_empty() );
	}

	/**
	 * A message is retried and counted until the attempt limit is reached.
	 *
	 * @return void
	 */
	public function test_message_is_dropped_after_max_attempts(): void {
		$queue = new Queue( new MemoryOptions() );
		$queue->push( 'never delivered' );

		$failing = static function ( $text ) {
			return false;
		};

		$queue->flush( $failing );

		$this->assertCount( 1, $queue->all() );
		$this->assertSame( 1, $queue->all()[0]['attempts'] );

		$queue->flush( $failing );

		$this->assertCount( 1, $queue->all() );
		$this->assertSame( 2, $queue->all()[0]['attempts'] );

		$queue->flush( $failing );

		$this->assertTrue( $queue->is_empty() );
	}

	/**
	 * Empty text is ignored.
	 *
	 * @return void
	 */
	public function test_blank_message_is_ignored(): void {
		$queue = new Queue( new MemoryOptions() );
		$queue->push( '   ' );

		$this->assertTrue( $queue->is_empty() );
	}
}
