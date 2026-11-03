<?php
/**
 * Manifest tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Backup\FileEntry;
use Backupino\Backup\Manifest;
use Backupino\Tests\TestCase;

/**
 * Covers the NDJSON manifest.
 */
final class ManifestTest extends TestCase {

	/**
	 * Entries are appended and can be re-read from a byte offset.
	 *
	 * @return void
	 */
	public function test_append_and_paged_read(): void {
		$dir      = $this->temp_dir();
		$manifest = new Manifest( $dir . '/manifest.ndjson', $dir );

		$manifest->reset();

		foreach ( array( 'a.php', 'b/c.css', 'd/e/f.js' ) as $index => $path ) {
			$manifest->append( new FileEntry( $dir . '/' . $path, $path, $index + 1 ) );
		}

		$this->assertSame( 3, $manifest->count() );

		$first = $manifest->read_at( 0, 2 );

		$this->assertCount( 2, $first['entries'] );
		$this->assertSame( 'a.php', $first['entries'][0]->relative() );
		$this->assertSame( 'b/c.css', $first['entries'][1]->relative() );
		$this->assertSame( $dir . '/b/c.css', $first['entries'][1]->path() );

		$second = $manifest->read_at( $first['offset'], 2 );

		$this->assertCount( 1, $second['entries'] );
		$this->assertSame( 'd/e/f.js', $second['entries'][0]->relative() );
		$this->assertSame( 3, $second['entries'][0]->size() );

		$empty = $manifest->read_at( $second['offset'], 5 );

		$this->assertCount( 0, $empty['entries'] );
	}

	/**
	 * Reset truncates the file.
	 *
	 * @return void
	 */
	public function test_reset_clears_manifest(): void {
		$dir      = $this->temp_dir();
		$manifest = new Manifest( $dir . '/manifest.ndjson', $dir );

		$manifest->append( new FileEntry( $dir . '/x', 'x', 1 ) );
		$manifest->reset();

		$this->assertSame( 0, $manifest->count() );
	}

	/**
	 * The hash is stable for identical content.
	 *
	 * @return void
	 */
	public function test_hash_is_stable(): void {
		$dir = $this->temp_dir();

		$first  = new Manifest( $dir . '/a.ndjson', $dir );
		$second = new Manifest( $dir . '/b.ndjson', $dir );

		$first->append( new FileEntry( $dir . '/x', 'x', 1 ) );
		$second->append( new FileEntry( $dir . '/x', 'x', 1 ) );

		$this->assertSame( $first->hash(), $second->hash() );
		$this->assertSame( 64, strlen( $first->hash() ) );
	}
}
