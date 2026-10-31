<?php
/**
 * Bot API transport tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Notify\BotApi;
use Backupino\Notify\ChannelFactory;
use Backupino\Notify\Message;
use Backupino\Tests\TestCase;

/**
 * Covers the Telegram/Bale transport without touching the network.
 */
final class BotApiTest extends TestCase {

	/**
	 * Each service has its own base URL and the endpoint carries the token.
	 *
	 * @return void
	 */
	public function test_endpoint_per_service(): void {
		$telegram = ChannelFactory::make( ChannelFactory::TELEGRAM, 'TOKEN', '42' );
		$bale     = ChannelFactory::make( ChannelFactory::BALE, 'TOKEN', '42' );

		$this->assertNotNull( $telegram );
		$this->assertNotNull( $bale );
		$this->assertSame( 'https://api.telegram.org/botTOKEN/sendMessage', $telegram->endpoint() );
		$this->assertSame( 'https://tapi.bale.ai/botTOKEN/sendMessage', $bale->endpoint() );
	}

	/**
	 * An unknown service yields no channel.
	 *
	 * @return void
	 */
	public function test_unknown_service_is_null(): void {
		$this->assertNull( ChannelFactory::make( 'none', 'TOKEN', '42' ) );
		$this->assertNull( ChannelFactory::make( 'carrier-pigeon', 'TOKEN', '42' ) );
	}

	/**
	 * The payload is what a Bot API expects.
	 *
	 * @return void
	 */
	public function test_payload_shape(): void {
		$api     = new BotApi( 'https://api.telegram.org', 'T', '99' );
		$payload = $api->payload( new Message( 'hello' ) );

		$this->assertSame( '99', $payload['chat_id'] );
		$this->assertSame( 'hello', $payload['text'] );
		$this->assertTrue( $payload['disable_web_page_preview'] );
	}

	/**
	 * A `200 {"ok":true}` answer is a success.
	 *
	 * @return void
	 */
	public function test_successful_delivery(): void {
		$captured = array();

		$api = new BotApi(
			'https://api.telegram.org',
			'T',
			'99',
			static function ( $url, $args ) use ( &$captured ) {
				$captured['url'] = $url;
				$captured['args'] = $args;

				return array(
					'code'  => 200,
					'body'  => '{"ok":true,"result":{"message_id":7}}',
					'error' => '',
				);
			}
		);

		$this->assertTrue( $api->send( new Message( 'hello' ) ) );
		$this->assertSame( '', $api->error() );
		$this->assertSame( 'https://api.telegram.org/botT/sendMessage', $captured['url'] );
		$this->assertStringContains( '"chat_id":"99"', (string) $captured['args']['body'] );
	}

	/**
	 * The messenger rejecting the message surfaces its own description.
	 *
	 * @return void
	 */
	public function test_rejected_message_exposes_description(): void {
		$api = new BotApi(
			'https://tapi.bale.ai',
			'T',
			'99',
			static function () {
				return array(
					'code'  => 200,
					'body'  => '{"ok":false,"description":"chat not found"}',
					'error' => '',
				);
			}
		);

		$this->assertFalse( $api->send( new Message( 'hello' ) ) );
		$this->assertStringContains( 'chat not found', $api->error() );
	}

	/**
	 * A transport failure is reported, not thrown.
	 *
	 * @return void
	 */
	public function test_transport_failure_is_reported(): void {
		$api = new BotApi(
			'https://api.telegram.org',
			'T',
			'99',
			static function () {
				return array(
					'code'  => 0,
					'body'  => '',
					'error' => 'cURL error 28: timed out',
				);
			}
		);

		$this->assertFalse( $api->send( new Message( 'hello' ) ) );
		$this->assertStringContains( 'timed out', $api->error() );
	}

	/**
	 * Missing credentials are refused before any request is made.
	 *
	 * @return void
	 */
	public function test_incomplete_configuration_is_refused(): void {
		$called = false;

		$api = new BotApi(
			'https://api.telegram.org',
			'',
			'',
			static function () use ( &$called ) {
				$called = true;

				return array();
			}
		);

		$this->assertFalse( $api->is_configured() );
		$this->assertFalse( $api->send( new Message( 'hello' ) ) );
		$this->assertFalse( $called );
	}
}
