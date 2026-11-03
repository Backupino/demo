<?php
/**
 * Notification message tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Notify\Message;
use Backupino\Tests\TestCase;

/**
 * Covers the text that lands in a messenger.
 */
final class MessageTest extends TestCase {

	/**
	 * A finished backup produces a complete, actionable message.
	 *
	 * @return void
	 */
	public function test_backup_message_contains_every_fact(): void {
		$text = Message::backup_completed(
			'example.com',
			'files and database',
			1048576,
			'2026-10-31 14:30',
			'https://example.com/?backupino_download=latest&backupino_key=abc'
		)->text();

		$this->assertStringContains( 'example.com', $text );
		$this->assertStringContains( 'files and database', $text );
		$this->assertStringContains( '1.00 MB', $text );
		$this->assertStringContains( '2026-10-31 14:30', $text );
		$this->assertStringContains( 'backupino_key=abc', $text );
	}

	/**
	 * Every digit in the message is a Latin digit.
	 *
	 * @return void
	 */
	public function test_numbers_stay_latin(): void {
		$text = Message::backup_completed( 'example.com', 'database only', 5242880, '2026-10-31 14:30', 'https://example.com/x' )->text();

		$this->assertSame( 0, preg_match( '/[\x{0660}-\x{0669}\x{06F0}-\x{06F9}]/u', $text ) );
		$this->assertStringContains( '5.00 MB', $text );
	}

	/**
	 * The test message is short but still carries the link.
	 *
	 * @return void
	 */
	public function test_test_message_carries_the_link(): void {
		$message = Message::test( 'example.com', 'https://example.com/?backupino_download=latest' );

		$this->assertFalse( $message->is_empty() );
		$this->assertStringContains( 'example.com', $message->text() );
		$this->assertStringContains( 'backupino_download=latest', $message->text() );
	}

	/**
	 * An empty body is reported as empty.
	 *
	 * @return void
	 */
	public function test_empty_message_is_detected(): void {
		$this->assertTrue( ( new Message( '   ' ) )->is_empty() );
	}
}
