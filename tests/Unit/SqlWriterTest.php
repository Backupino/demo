<?php
/**
 * SQL writer tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Backup\Sql\SqlWriter;
use Backupino\Tests\TestCase;

/**
 * Covers the append/truncate contract used by resumable dumps.
 */
final class SqlWriterTest extends TestCase {

	/**
	 * Writes are counted and measurable.
	 *
	 * @return void
	 */
	public function test_write_and_count(): void {
		$dir    = $this->temp_dir();
		$writer = new SqlWriter( $dir . '/dump.sql' );

		$this->assertTrue( $writer->open( false ) );
		$this->assertTrue( $writer->statement( 'SELECT 1' ) );

		$first = $writer->bytes();

		$this->assertSame( strlen( "SELECT 1;\n" ), $first );

		$writer->write( "SELECT 2;\n" );
		$writer->close();

		$this->assertSame( "SELECT 1;\nSELECT 2;\n", file_get_contents( $dir . '/dump.sql' ) );
	}

	/**
	 * Truncating drops a partially written statement after a crash.
	 *
	 * @return void
	 */
	public function test_truncate_restores_last_committed_offset(): void {
		$dir  = $this->temp_dir();
		$file = $dir . '/dump.sql';

		$writer = new SqlWriter( $file );
		$writer->open( false );
		$writer->statement( 'SELECT 1' );
		$committed = $writer->bytes();
		$writer->write( 'SELECT 2' ); // never terminated: simulated crash.
		$writer->close();

		$writer = new SqlWriter( $file );
		$writer->open( true );
		$this->assertTrue( $writer->truncate( $committed ) );
		$writer->statement( 'SELECT 3' );
		$writer->close();

		$this->assertSame( "SELECT 1;\nSELECT 3;\n", file_get_contents( $file ) );
	}

	/**
	 * Writing to a closed writer fails instead of corrupting data.
	 *
	 * @return void
	 */
	public function test_write_after_close_fails(): void {
		$dir    = $this->temp_dir();
		$writer = new SqlWriter( $dir . '/dump.sql' );

		$writer->open( false );
		$writer->close();

		$this->assertFalse( $writer->write( 'SELECT 1;' ) );
	}

	/**
	 * Trimming is applied to already terminated statements.
	 *
	 * @return void
	 */
	public function test_statement_trims_trailing_semicolon(): void {
		$dir    = $this->temp_dir();
		$file   = $dir . '/dump.sql';
		$writer = new SqlWriter( $file );

		$writer->open( false );
		$writer->statement( "SELECT 1;;\n" );
		$writer->close();

		$this->assertSame( "SELECT 1;\n", file_get_contents( $file ) );
	}
}
