<?php
/**
 * URL rewrite tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Installer\UrlRewrite;
use Backupino\Replace\Replacer;
use Backupino\Tests\Support\FakeConnection;
use Backupino\Tests\TestCase;

/**
 * Covers the chunked, serialized-safe table rewrite used by the installer.
 */
final class UrlRewriteTest extends TestCase {

	/**
	 * Connection with one table holding plain and serialized URLs.
	 *
	 * @return FakeConnection
	 */
	private function connection(): FakeConnection {
		$connection = new FakeConnection();

		$connection->tables['wp_options'] = array(
			'columns' => array(
				array( 'Field' => 'option_id', 'Type' => 'bigint(20)', 'Key' => 'PRI' ),
				array( 'Field' => 'option_name', 'Type' => 'varchar(191)', 'Key' => '' ),
				array( 'Field' => 'option_value', 'Type' => 'longtext', 'Key' => '' ),
				array( 'Field' => 'payload', 'Type' => 'longblob', 'Key' => '' ),
			),
			'rows'    => array(
				array(
					'option_id'    => 1,
					'option_name'  => 'siteurl',
					'option_value' => 'https://old.example.com',
					'payload'      => "\x00\x01",
				),
				array(
					'option_id'    => 2,
					'option_name'  => 'widgets',
					'option_value' => serialize( array( 'link' => 'https://old.example.com/shop' ) ),
					'payload'      => '',
				),
				array(
					'option_id'    => 3,
					'option_name'  => 'nothing',
					'option_value' => 'unchanged',
					'payload'      => '',
				),
			),
		);

		return $connection;
	}

	/**
	 * Plain and serialized values are rewritten in place.
	 *
	 * @return void
	 */
	public function test_rewrites_values(): void {
		$connection = $this->connection();
		$rewrite    = new UrlRewrite( $connection, Replacer::from_urls( 'https://old.example.com', 'https://new.example.org' ) );

		$state = $rewrite->step( UrlRewrite::initial_state(), 10, 5.0 );

		$this->assertTrue( $state['done'] );
		$this->assertSame( 3, $state['rows'] );
		$this->assertSame( 2, $state['changes'] );

		$updates = array_values(
			array_filter(
				$connection->log,
				static function ( $statement ) {
					return 0 === stripos( $statement, 'UPDATE' );
				}
			)
		);

		$this->assertCount( 2, $updates );
		$this->assertStringContains( "'https://new.example.org'", $updates[0] );
		$this->assertStringContains( 's:28:"https://new.example.org/shop"', $updates[1] );
		$this->assertStringNotContains( 'old.example.com', implode( "\n", $updates ) );
	}

	/**
	 * Binary columns are never touched.
	 *
	 * @return void
	 */
	public function test_binary_columns_are_skipped(): void {
		$connection = $this->connection();
		$rewrite    = new UrlRewrite( $connection, Replacer::from_urls( 'https://old.example.com', 'https://new.example.org' ) );

		$rewrite->step( UrlRewrite::initial_state(), 10, 5.0 );

		$selects = array_values(
			array_filter(
				$connection->log,
				static function ( $statement ) {
					return 0 === stripos( $statement, 'SELECT' );
				}
			)
		);

		$this->assertStringNotContains( '`payload`', $selects[0] );
	}

	/**
	 * Batch size is honoured and the state can continue from a cursor.
	 *
	 * @return void
	 */
	public function test_keyset_pagination(): void {
		$connection = $this->connection();
		$rewrite    = new UrlRewrite( $connection, Replacer::from_urls( 'https://old.example.com', 'https://new.example.org' ) );

		$state = UrlRewrite::initial_state();
		$state = $rewrite->step( $state, 2, 5.0 );

		$selects = array_values(
			array_filter(
				$connection->log,
				static function ( $statement ) {
					return 0 === stripos( $statement, 'SELECT' );
				}
			)
		);

		$this->assertStringContains( 'ORDER BY `option_id` ASC', $selects[0] );
		$this->assertTrue( $state['done'] );
	}

	/**
	 * Identifiers are quoted safely.
	 *
	 * @return void
	 */
	public function test_identifier_quoting(): void {
		$this->assertSame( '`a`', UrlRewrite::quote( 'a' ) );
		$this->assertSame( '`a``b`', UrlRewrite::quote( 'a`b' ) );
	}
}
