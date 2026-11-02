<?php
/**
 * Search and replace tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Replace\Replacer;
use Backupino\Replace\SerializedParser;
use Backupino\Tests\TestCase;

/**
 * The most dangerous part of any migration tool: replacing URLs inside
 * serialized and JSON encoded payloads without corrupting them.
 */
final class ReplacerTest extends TestCase {

	/**
	 * Build a replacer for the two test domains.
	 *
	 * @return Replacer
	 */
	private function replacer(): Replacer {
		return new Replacer( 'old.example.com', 'new.example.org' );
	}

	/**
	 * Plain strings.
	 *
	 * @return void
	 */
	public function test_plain_replacement(): void {
		$this->assertSame(
			'https://new.example.org/path',
			$this->replacer()->text( 'https://old.example.com/path' )
		);

		$this->assertSame( 'nothing here', $this->replacer()->text( 'nothing here' ) );
		$this->assertSame( '', $this->replacer()->text( '' ) );
	}

	/**
	 * Serialized arrays keep their structure and byte lengths.
	 *
	 * @return void
	 */
	public function test_serialized_array(): void {
		$original = serialize(
			array(
				'site'  => 'https://old.example.com',
				'count' => 7,
				'deep'  => array( 'url' => 'http://old.example.com/a' ),
				'flag'  => true,
				'none'  => null,
			)
		);

		$result = $this->replacer()->text( $original );

		$this->assertStringNotContains( 'old.example.com', $result );

		$decoded = unserialize( $result ); // phpcs:ignore

		$this->assertSame( 'https://new.example.org', $decoded['site'] );
		$this->assertSame( 'http://new.example.org/a', $decoded['deep']['url'] );
		$this->assertSame( 7, $decoded['count'] );
		$this->assertTrue( $decoded['flag'] );
		$this->assertNull( $decoded['none'] );
	}

	/**
	 * Objects keep their class name and property order.
	 *
	 * @return void
	 */
	public function test_serialized_object(): void {
		$object = new \stdClass();
		$object->home = 'https://old.example.com';
		$object->items = array( 'https://old.example.com/x' );

		$original = serialize( $object );
		$result   = $this->replacer()->text( $original );

		$this->assertStringContains( 'O:8:"stdClass"', $result );

		$decoded = unserialize( $result ); // phpcs:ignore

		$this->assertSame( 'https://new.example.org', $decoded->home );
		$this->assertSame( array( 'https://new.example.org/x' ), $decoded->items );
	}

	/**
	 * A serialized payload nested inside a serialized payload.
	 *
	 * @return void
	 */
	public function test_nested_serialization(): void {
		$inner = serialize( array( 'url' => 'https://old.example.com' ) );
		$outer = serialize( array( 'data' => $inner ) );

		$result = $this->replacer()->text( $outer );
		$data   = unserialize( $result ); // phpcs:ignore
		$inner  = unserialize( $data['data'] ); // phpcs:ignore

		$this->assertSame( 'https://new.example.org', $inner['url'] );
	}

	/**
	 * JSON documents, including escaped slashes.
	 *
	 * @return void
	 */
	public function test_json(): void {
		$json   = '{"url":"https:\/\/old.example.com\/shop","list":["old.example.com"]}';
		$result = $this->replacer()->text( $json );

		$this->assertStringContains( 'new.example.org', $result );
		$this->assertNotNull( json_decode( $result ) );

		$decoded = json_decode( $result, true );

		$this->assertSame( 'https://new.example.org/shop', $decoded['url'] );
		$this->assertSame( array( 'new.example.org' ), $decoded['list'] );
	}

	/**
	 * JSON stored inside a serialized option (Elementor style).
	 *
	 * @return void
	 */
	public function test_json_inside_serialized(): void {
		$json     = '{"link":"https:\/\/old.example.com"}';
		$original = serialize( array( 'elementor' => $json ) );

		$result  = $this->replacer()->text( $original );
		$decoded = unserialize( $result ); // phpcs:ignore
		$inner   = json_decode( $decoded['elementor'], true );

		$this->assertSame( 'https://new.example.org', $inner['link'] );
	}

	/**
	 * Multibyte content must survive byte-length recalculations.
	 *
	 * @return void
	 */
	public function test_multibyte_content(): void {
		$original = serialize(
			array(
				'title' => 'فروشگاه من — old.example.com',
				'body'  => 'متن فارسی با https://old.example.com داخلش',
			)
		);

		$result  = $this->replacer()->text( $original );
		$decoded = unserialize( $result ); // phpcs:ignore

		$this->assertSame( 'فروشگاه من — new.example.org', $decoded['title'] );
		$this->assertSame( 'متن فارسی با https://new.example.org داخلش', $decoded['body'] );
	}

	/**
	 * Array keys are replaced too.
	 *
	 * @return void
	 */
	public function test_deep_replaces_keys(): void {
		$result = $this->replacer()->deep(
			array( 'old.example.com' => array( 'k' => 'old.example.com/x' ) )
		);

		$this->assertArrayHasKey( 'new.example.org', $result );
		$this->assertSame( 'new.example.org/x', $result['new.example.org']['k'] );
	}

	/**
	 * `from_urls()` covers scheme and www variants.
	 *
	 * @return void
	 */
	public function test_from_urls_variants(): void {
		$replacer = Replacer::from_urls( 'https://www.old.test', 'https://new.test' );

		$this->assertSame(
			'https://new.test/a && http://new.test/a && https://new.test/a',
			$replacer->text( 'https://www.old.test/a && http://www.old.test/a && https://old.test/a' )
		);

		// JSON-escaped slashes
		$this->assertSame(
			'https:\/\/new.test\/path',
			$replacer->text( 'https:\/\/www.old.test\/path' )
		);

		// URL-encoded in query/attribute
		$this->assertSame(
			rawurlencode( 'https://new.test' ) . '%2F%D8%AA%D9%85%D8%A7%D8%B3',
			$replacer->text( rawurlencode( 'https://www.old.test' ) . '%2F%D8%AA%D9%85%D8%A7%D8%B3' )
		);
	}

	/**
	 * Empty search terms are ignored instead of matching everything.
	 *
	 * @return void
	 */
	public function test_empty_needle_is_ignored(): void {
		$replacer = new Replacer( array( '', 'a' ), array( 'X', 'b' ) );

		$this->assertSame( 'b', $replacer->text( 'a' ) );
	}

	/**
	 * The parser round-trips arbitrary structures.
	 *
	 * @return void
	 */
	public function test_parser_roundtrip(): void {
		$payloads = array(
			'b:1;',
			'i:42;',
			'd:1.5;',
			'N;',
			's:5:"hello";',
			serialize( array( 1, 2, array( 'a' => 'b' ) ) ),
			serialize( (object) array( 'x' => 'y' ) ),
		);

		foreach ( $payloads as $payload ) {
			$tree = SerializedParser::parse( $payload );

			$this->assertNotNull( $tree, 'Failed to parse: ' . $payload );
			$this->assertSame( $payload, SerializedParser::render( $tree, static function ( $value ) {
				return $value;
			} ) );
		}
	}

	/**
	 * Broken payloads are rejected instead of producing corrupted output.
	 *
	 * @return void
	 */
	public function test_parser_rejects_broken_payloads(): void {
		$this->assertNull( SerializedParser::parse( 'a:2:{i:0;s:1:"a";}' ) );
		$this->assertNull( SerializedParser::parse( 'garbage' ) );
		$this->assertNull( SerializedParser::parse( 's:99:"short";' ) );
		$this->assertFalse( SerializedParser::looks_serialized( 'plain text' ) );
		$this->assertTrue( SerializedParser::looks_serialized( serialize( array( 1 ) ) ) );
	}
}
