<?php
/**
 * Permanent download link tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Download\DownloadLink;
use Backupino\Tests\Support\MemoryOptions;
use Backupino\Tests\TestCase;

/**
 * Covers the secret behind the permanent link and the URL it produces.
 */
final class DownloadLinkTest extends TestCase {

	/**
	 * The key is generated once and reused afterwards.
	 *
	 * @return void
	 */
	public function test_key_is_generated_once(): void {
		$options = new MemoryOptions();
		$link    = new DownloadLink( $options );

		$first  = $link->key();
		$second = ( new DownloadLink( $options ) )->key();

		$this->assertSame( 32, strlen( $first ) );
		$this->assertSame( $first, $second );
		$this->assertSame( 1, preg_match( '/^[0-9a-f]{32}$/', $first ) );
	}

	/**
	 * The URL carries the routing flag and the key, and no expiry.
	 *
	 * @return void
	 */
	public function test_url_contains_flag_and_key(): void {
		$options = new MemoryOptions();
		$link    = new DownloadLink( $options );
		$url     = $link->url();

		$this->assertStringContains( 'backupino_download=latest', $url );
		$this->assertStringContains( 'backupino_key=' . $link->key(), $url );
		$this->assertStringNotContains( 'expires', $url );
	}

	/**
	 * Only the exact key is accepted.
	 *
	 * @return void
	 */
	public function test_matches_requires_the_exact_key(): void {
		$link = new DownloadLink( new MemoryOptions() );
		$key  = $link->key();

		$this->assertTrue( $link->matches( $key ) );
		$this->assertFalse( $link->matches( '' ) );
		$this->assertFalse( $link->matches( substr( $key, 0, 31 ) ) );
		$this->assertFalse( $link->matches( $key . '0' ) );
		$this->assertFalse( $link->matches( strtoupper( $key ) ) );
	}

	/**
	 * A site that already has a key keeps using it.
	 *
	 * @return void
	 */
	public function test_existing_key_is_reused(): void {
		$options                                = new MemoryOptions();
		$options->values[ DownloadLink::OPTION ] = 'abc123';

		$link = new DownloadLink( $options );

		$this->assertSame( 'abc123', $link->key() );
		$this->assertTrue( $link->matches( 'abc123' ) );
	}
}
