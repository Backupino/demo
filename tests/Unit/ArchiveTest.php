<?php
/**
 * Archive tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Backup\ZipArchiveReader;
use Backupino\Backup\ZipArchiveWriter;
use Backupino\Tests\TestCase;

/**
 * Covers the zip backend and the path traversal guard.
 */
final class ArchiveTest extends TestCase {

	/**
	 * Files survive a write/read round-trip.
	 *
	 * @return void
	 */
	public function test_roundtrip(): void {
		$dir   = $this->temp_dir();
		$path  = $this->make_file( $dir, 'source/hello.txt', 'hello world' );
		$zip   = $dir . '/archive.zip';
		$write = new ZipArchiveWriter();

		$this->assertTrue( $write->open( $zip ) );
		$this->assertTrue( $write->add_file( $path, 'hello.txt' ) );
		$this->assertTrue( $write->add_from_string( 'nested/data.json', '{"a":1}' ) );
		$this->assertTrue( $write->close() );

		$this->assertTrue( is_file( $zip ) );

		$read = new ZipArchiveReader();

		$this->assertTrue( $read->open( $zip ) );
		$this->assertCount( 2, $read->entries() );
		$this->assertSame( 'hello world', $read->read( 'hello.txt' ) );
		$this->assertSame( '{"a":1}', $read->read( 'nested/data.json' ) );
		$this->assertNull( $read->read( 'missing.txt' ) );
		$read->close();
	}

	/**
	 * Extraction honours the offset/limit contract used by the installer.
	 *
	 * @return void
	 */
	public function test_chunked_extraction(): void {
		$dir   = $this->temp_dir();
		$zip   = $dir . '/archive.zip';
		$write = new ZipArchiveWriter();

		$write->open( $zip );
		$write->add_from_string( 'a.txt', 'AAA' );
		$write->add_from_string( 'sub/b.txt', 'BBB' );
		$write->add_from_string( 'sub/deep/c.txt', 'CCC' );
		$write->close();

		$read   = new ZipArchiveReader();
		$target = $dir . '/out';

		$this->assertTrue( $read->open( $zip ) );

		$total     = 0;
		$offset    = 0;
		$extracted = $read->extract( $target, $offset, 2, $total );

		$this->assertSame( 3, $total );
		$this->assertSame( 2, $extracted );
		$this->assertTrue( is_file( $target . '/a.txt' ) );

		$extracted = $read->extract( $target, 2, 2, $total );

		$this->assertSame( 1, $extracted );
		$this->assertSame( 'CCC', file_get_contents( $target . '/sub/deep/c.txt' ) );
		$read->close();
	}

	/**
	 * Zip-slip style entries are rejected.
	 *
	 * @return void
	 */
	public function test_traversal_entries_are_rejected(): void {
		$this->assertFalse( ZipArchiveReader::is_safe_entry( '../evil.php' ) );
		$this->assertFalse( ZipArchiveReader::is_safe_entry( '/etc/passwd' ) );
		$this->assertFalse( ZipArchiveReader::is_safe_entry( 'a/../../b' ) );
		$this->assertFalse( ZipArchiveReader::is_safe_entry( "a\0b" ) );
		$this->assertFalse( ZipArchiveReader::is_safe_entry( 'C:/windows/system32' ) );
		$this->assertTrue( ZipArchiveReader::is_safe_entry( 'wp-content/themes/x/style.css' ) );
		$this->assertTrue( ZipArchiveReader::is_safe_entry( 'index.php' ) );
	}

	/**
	 * Reopening an archive appends to it, which is what a resumable build needs.
	 *
	 * @return void
	 */
	public function test_reopen_appends(): void {
		$dir = $this->temp_dir();
		$zip = $dir . '/archive.zip';

		$write = new ZipArchiveWriter();
		$write->open( $zip );
		$write->add_from_string( 'a.txt', 'A' );
		$write->close();

		$write = new ZipArchiveWriter();
		$write->open( $zip );
		$write->add_from_string( 'b.txt', 'B' );
		$write->close();

		$read = new ZipArchiveReader();
		$read->open( $zip );

		$this->assertCount( 2, $read->entries() );
		$read->close();
	}
}
