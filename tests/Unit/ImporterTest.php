<?php
/**
 * Importer tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Installer\Importer;
use Backupino\Installer\SqlSplitter;
use Backupino\Tests\Support\FakeConnection;
use Backupino\Tests\TestCase;

/**
 * Covers statement execution and error reporting.
 */
final class ImporterTest extends TestCase {

	/**
	 * Every statement is executed in order.
	 *
	 * @return void
	 */
	public function test_executes_all_statements(): void {
		$dir  = $this->temp_dir();
		$file = $this->make_file(
			$dir,
			'dump.sql',
			"SET NAMES utf8mb4;\nCREATE TABLE `t` (`a` int);\nINSERT INTO `t` VALUES (1);\n"
		);

		$connection = new FakeConnection();
		$importer   = new Importer( $connection, new SqlSplitter( $file ) );
		$offset     = 0;
		$result     = $importer->step( $offset, 1048576, 5.0 );

		$this->assertSame( '', $result['error'] );
		$this->assertTrue( $result['done'] );
		$this->assertSame( 3, $result['executed'] );
		$this->assertCount( 3, $connection->log );
		$this->assertStringContains( 'CREATE TABLE', $connection->log[1] );
	}

	/**
	 * Driver errors are surfaced with a statement preview.
	 *
	 * @return void
	 */
	public function test_reports_errors(): void {
		$dir  = $this->temp_dir();
		$file = $this->make_file( $dir, 'dump.sql', "INSERT INTO `missing` VALUES (1);\n" );

		$connection            = new FakeConnection();
		$connection->fail_with = 'Table does not exist';

		$importer = new Importer( $connection, new SqlSplitter( $file ) );
		$offset   = 0;
		$result   = $importer->step( $offset, 65536, 5.0 );

		$this->assertStringContains( 'Table does not exist', $result['error'] );
		$this->assertStringContains( 'INSERT INTO', $result['error'] );
		$this->assertFalse( $result['done'] );
	}

	/**
	 * The reported size matches the file.
	 *
	 * @return void
	 */
	public function test_reports_size(): void {
		$dir  = $this->temp_dir();
		$sql  = "SELECT 1;\n";
		$file = $this->make_file( $dir, 'dump.sql', $sql );

		$importer = new Importer( new FakeConnection(), new SqlSplitter( $file ) );

		$this->assertSame( strlen( $sql ), $importer->size() );
	}

	/**
	 * Table prefixes are rewritten on the fly during statement execution.
	 *
	 * @return void
	 */
	public function test_prefix_rewriting_during_import(): void {
		$dir  = $this->temp_dir();
		$sql  = "CREATE TABLE `wp_options` (`option_id` bigint(20));\nINSERT INTO `wp_options` VALUES (1);\nDROP TABLE IF EXISTS `wp_users`;\n";
		$file = $this->make_file( $dir, 'dump.sql', $sql );

		$connection = new FakeConnection();
		$importer   = new Importer( $connection, new SqlSplitter( $file ), 'wp_', 'jvd_' );
		$offset     = 0;
		$result     = $importer->step( $offset, 65536, 5.0 );

		$this->assertSame( '', $result['error'] );
		$this->assertTrue( $result['done'] );
		$this->assertCount( 3, $connection->log );
		$this->assertStringContains( '`jvd_options`', $connection->log[0] );
		$this->assertStringContains( '`jvd_options`', $connection->log[1] );
		$this->assertStringContains( '`jvd_users`', $connection->log[2] );
	}
}
