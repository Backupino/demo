<?php
/**
 * Notifier tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Core\Settings;
use Backupino\Download\DownloadLink;
use Backupino\Jobs\Job;
use Backupino\Notify\Notifier;
use Backupino\Support\SystemClock;
use Backupino\Tests\Support\MemoryOptions;
use Backupino\Tests\TestCase;

/**
 * Covers announcement, failure handling and retry.
 */
final class NotifierTest extends TestCase {

	/**
	 * Build a notifier with the given messenger settings.
	 *
	 * @param array         $notify Settings overrides for the notify group.
	 * @param callable|null $http   Fake transport.
	 * @param MemoryOptions $options Options store.
	 * @return Notifier
	 */
	private function notifier( array $notify, ?callable $http = null, ?MemoryOptions $options = null ): Notifier {
		$options  = $options ? $options : new MemoryOptions();
		$settings = new Settings( $options );
		$settings->update( array( 'notify' => $notify ) );

		return new Notifier( $settings, $options, new DownloadLink( $options ), $http );
	}

	/**
	 * A finished job is announced with its size and permanent link.
	 *
	 * @return void
	 */
	public function test_finished_job_is_announced(): void {
		$sent = array();

		$notifier = $this->notifier(
			array(
				'service' => 'telegram',
				'token'   => 'TOKEN',
				'chat_id' => '42',
			),
			static function ( $url, $args ) use ( &$sent ) {
				$sent[] = json_decode( (string) $args['body'], true );

				return array(
					'code'  => 200,
					'body'  => '{"ok":true}',
					'error' => '',
				);
			}
		);

		$job              = Job::create( array( 'files' => true, 'database' => true ), new SystemClock() );
		$job->payload['package'] = array( 'size' => 2048 );

		$this->assertTrue( $notifier->is_enabled() );
		$this->assertTrue( $notifier->announce( $job, 'example.com' ) );
		$this->assertCount( 1, $sent );
		$this->assertStringContains( 'example.com', (string) $sent[0]['text'] );
		$this->assertStringContains( 'backupino_download=latest', (string) $sent[0]['text'] );
		$this->assertSame( 0, $notifier->pending() );
	}

	/**
	 * Disabled notifications send nothing and queue nothing.
	 *
	 * @return void
	 */
	public function test_disabled_messenger_does_nothing(): void {
		$called = false;

		$notifier = $this->notifier(
			array( 'service' => 'none' ),
			static function () use ( &$called ) {
				$called = true;

				return array();
			}
		);

		$job = Job::create( array( 'files' => true ), new SystemClock() );

		$this->assertFalse( $notifier->is_enabled() );
		$this->assertFalse( $notifier->announce( $job, 'example.com' ) );
		$this->assertFalse( $called );
		$this->assertSame( 0, $notifier->pending() );
	}

	/**
	 * A failed delivery is queued and retried later.
	 *
	 * @return void
	 */
	public function test_failed_delivery_is_queued_and_retried(): void {
		$fail = true;

		$notifier = $this->notifier(
			array(
				'service' => 'bale',
				'token'   => 'TOKEN',
				'chat_id' => '42',
			),
			static function () use ( &$fail ) {
				if ( $fail ) {
					return array(
						'code'  => 500,
						'body'  => '',
						'error' => 'server error',
					);
				}

				return array(
					'code'  => 200,
					'body'  => '{"ok":true}',
					'error' => '',
				);
			}
		);

		$job = Job::create( array( 'database' => true ), new SystemClock() );

		$this->assertFalse( $notifier->announce( $job, 'example.com' ) );
		$this->assertSame( 1, $notifier->pending() );
		$this->assertStringContains( 'server error', $notifier->error() );

		$fail = false;

		$this->assertSame( 1, $notifier->flush() );
		$this->assertSame( 0, $notifier->pending() );
	}

	/**
	 * The test message is delivered too.
	 *
	 * @return void
	 */
	public function test_test_message_is_delivered(): void {
		$notifier = $this->notifier(
			array(
				'service' => 'telegram',
				'token'   => 'TOKEN',
				'chat_id' => '42',
			),
			static function () {
				return array(
					'code'  => 200,
					'body'  => '{"ok":true}',
					'error' => '',
				);
			}
		);

		$this->assertTrue( $notifier->send_test( 'example.com' ) );
	}

	/**
	 * Without credentials nothing is sent and nothing is queued forever.
	 *
	 * @return void
	 */
	public function test_missing_credentials_report_an_error(): void {
		$notifier = $this->notifier( array( 'service' => 'telegram' ) );

		$this->assertFalse( $notifier->send_test( 'example.com' ) );
		$this->assertTrue( '' !== $notifier->error() );
	}

	/**
	 * Simultaneous Telegram and Bale notifications are dispatched to both platforms.
	 *
	 * @return void
	 */
	public function test_simultaneous_telegram_and_bale_broadcast(): void {
		$dispatched_urls = array();

		$notifier = $this->notifier(
			array(
				'telegram_enabled' => true,
				'telegram_token'   => 'TG_TOKEN',
				'telegram_chat_id' => '111',
				'bale_enabled'     => true,
				'bale_token'       => 'BALE_TOKEN',
				'bale_chat_id'     => '222',
			),
			static function ( $url, $args ) use ( &$dispatched_urls ) {
				$dispatched_urls[] = $url;

				return array(
					'code'  => 200,
					'body'  => '{"ok":true}',
					'error' => '',
				);
			}
		);

		$this->assertTrue( $notifier->is_enabled() );
		$this->assertCount( 2, $notifier->channels() );
		$this->assertTrue( $notifier->send_test( 'example.com' ) );
		$this->assertCount( 2, $dispatched_urls );
		$this->assertStringContains( 'api.telegram.org', $dispatched_urls[0] );
		$this->assertStringContains( 'tapi.bale.ai', $dispatched_urls[1] );
	}
}
