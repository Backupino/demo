<?php
/**
 * Dumper tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Backup\Sql\MySqlDumper;
use Backupino\Backup\Sql\SqlWriter;
use Backupino\Jobs\Deadline;
use Backupino\Tests\Support\ExpiredDeadline;
use Backupino\Tests\Support\FakeDb;
use Backupino\Tests\TestCase;

/**
 * Covers the resumable SQL dumper against an in-memory database.
 */
final class MySqlDumperTest extends TestCase {

	/**
	 * Fake database with two tables and a view.
	 *
	 * @return FakeDb
	 */
	private function database(): FakeDb {
		$db = new FakeDb();

		$db->tables['wp_options'] = array(
			'type'    => 'BASE TABLE',
			'create'  => "CREATE TABLE `wp_options` (\n  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n"
				. "  `option_name` varchar(191) NOT NULL DEFAULT '',\n  `option_value` longtext NOT NULL,\n"
				. "  PRIMARY KEY (`option_id`)\n) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4",
			'columns' => array(
				array( 'Field' => 'option_id', 'Type' => 'bigint(20) unsigned', 'Key' => 'PRI' ),
				array( 'Field' => 'option_name', 'Type' => 'varchar(191)', 'Key' => '' ),
				array( 'Field' => 'option_value', 'Type' => 'longtext', 'Key' => '' ),
			),
			'rows'    => array(
				array( 'option_id' => 1, 'option_name' => 'siteurl', 'option_value' => 'https://old.example.com' ),
				array( 'option_id' => 2, 'option_name' => 'home', 'option_value' => 'https://old.example.com' ),
				array( 'option_id' => 3, 'option_name' => 'quote', 'option_value' => "it's quoted" ),
				array( 'option_id' => 4, 'option_name' => 'empty', 'option_value' => '' ),
				array( 'option_id' => 5, 'option_name' => 'nullish', 'option_value' => null ),
			),
		);

		$db->tables['wp_media'] = array(
			'type'    => 'BASE TABLE',
			'create'  => 'CREATE TABLE `wp_media` (`id` int NOT NULL, `blob` longblob, PRIMARY KEY (`id`))',
			'columns' => array(
				array( 'Field' => 'id', 'Type' => 'int(11)', 'Key' => 'PRI' ),
				array( 'Field' => 'blob', 'Type' => 'longblob', 'Key' => '' ),
			),
			'rows'    => array(
				array( 'id' => 1, 'blob' => "\x00\x01\xFF" ),
			),
		);

		$db->tables['wp_summary'] = array(
			'type'   => 'VIEW',
			'create' => '',
		);

		return $db;
	}

	/**
	 * Run a dump and return the generated SQL.
	 *
	 * @param FakeDb               $db   Database.
	 * @param array<string, mixed> $seed Initial state.
	 * @return array{0: string, 1: array<string, mixed>}
	 */
	private function dump( FakeDb $db, array $seed = array() ): array {
		$dir    = $this->temp_dir();
		$writer = new SqlWriter( $dir . '/database.sql' );

		$this->assertTrue( $writer->open( false ) );

		$dumper = new MySqlDumper( $db, $writer );
		$state  = $dumper->step( $seed, new Deadline( 30 ), 2 );

		$writer->close();

		return array( (string) file_get_contents( $dir . '/database.sql' ), $state );
	}

	/**
	 * The dump contains the schema, the rows and the trailer.
	 *
	 * @return void
	 */
	public function test_full_dump(): void {
		list( $sql, $state ) = $this->dump( $this->database() );

		$this->assertTrue( $state['done'] );
		$this->assertSame( 6, $state['count'] );

		$this->assertStringContains( 'SET NAMES utf8mb4', $sql );
		$this->assertStringContains( 'SET FOREIGN_KEY_CHECKS = 0', $sql );
		$this->assertStringContains( 'SET FOREIGN_KEY_CHECKS = 1', $sql );

		$this->assertStringContains( 'DROP TABLE IF EXISTS `wp_options`', $sql );
		$this->assertStringContains( 'CREATE TABLE `wp_options`', $sql );
		$this->assertStringContains( 'INSERT INTO `wp_options`', $sql );
		$this->assertStringContains( "'https://old.example.com'", $sql );
		$this->assertStringContains( "'it\\'s quoted'", $sql );
		$this->assertStringContains( 'NULL', $sql );

		// Volatile counters are stripped so that dumps stay diffable.
		$this->assertStringNotContains( 'AUTO_INCREMENT=42', $sql );
	}

	/**
	 * Binary columns are written as hex literals.
	 *
	 * @return void
	 */
	public function test_binary_columns_use_hex(): void {
		list( $sql ) = $this->dump( $this->database() );

		$this->assertStringContains( '0x0001ff', strtolower( $sql ) );
	}

	/**
	 * Views are dumped without their DEFINER clause.
	 *
	 * @return void
	 */
	public function test_views_and_definer(): void {
		list( $sql ) = $this->dump( $this->database() );

		$this->assertStringContains( 'DROP VIEW IF EXISTS `wp_summary`', $sql );
		$this->assertStringContains( 'CREATE ALGORITHM=UNDEFINED', $sql );
		$this->assertStringNotContains( 'DEFINER=', $sql );
	}

	/**
	 * Large tables are read with keyset pagination, not OFFSET.
	 *
	 * @return void
	 */
	public function test_uses_keyset_pagination(): void {
		$db = new FakeDb();

		$rows = array();

		for ( $i = 1; $i <= 25; $i++ ) {
			$rows[] = array( 'option_id' => $i, 'option_name' => 'k' . $i, 'option_value' => 'v' . $i );
		}

		$db->tables['wp_options'] = array(
			'type'    => 'BASE TABLE',
			'create'  => 'CREATE TABLE `wp_options` (`option_id` bigint NOT NULL, `option_name` varchar(191), `option_value` longtext, PRIMARY KEY (`option_id`))',
			'columns' => array(
				array( 'Field' => 'option_id', 'Type' => 'bigint(20)', 'Key' => 'PRI' ),
				array( 'Field' => 'option_name', 'Type' => 'varchar(191)', 'Key' => '' ),
				array( 'Field' => 'option_value', 'Type' => 'longtext', 'Key' => '' ),
			),
			'rows'    => $rows,
		);

		$dir    = $this->temp_dir();
		$writer = new SqlWriter( $dir . '/database.sql' );
		$writer->open( false );

		$dumper = new MySqlDumper( $db, $writer );
		$dumper->step( MySqlDumper::initial_state(), new Deadline( 30 ), 10 );
		$writer->close();

		$selects = array_values(
			array_filter(
				$db->log,
				static function ( $statement ) {
					return 0 === stripos( $statement, 'SELECT `option_id`' );
				}
			)
		);

		$this->assertCount( 3, $selects );
		$this->assertStringContains( 'ORDER BY `option_id` ASC', $selects[0] );
		$this->assertStringNotContains( 'WHERE', $selects[0] );
		$this->assertStringContains( 'WHERE `option_id` > 10', $selects[1] );
		$this->assertStringContains( 'WHERE `option_id` > 20', $selects[2] );
		$this->assertStringNotContains( 'OFFSET', implode( ' ', $selects ) );

		$sql = (string) file_get_contents( $dir . '/database.sql' );

		$this->assertSame( 25, substr_count( $sql, "'k" ) );
	}

	/**
	 * An expired budget keeps the state resumable and writes no duplicate rows.
	 *
	 * @return void
	 */
	public function test_resumes_without_duplicating_rows(): void {
		$db  = $this->database();
		$dir = $this->temp_dir();
		$file = $dir . '/database.sql';

		$writer = new SqlWriter( $file );
		$writer->open( false );

		$dumper = new MySqlDumper( $db, $writer );

		// First tick: only the header and the table discovery happen.
		$state = $dumper->step( MySqlDumper::initial_state(), new ExpiredDeadline(), 2 );
		$writer->close();

		$this->assertFalse( $state['done'] );
		$this->assertCount( 3, $state['tables'] );

		// A fresh tick: new writer handle, same persisted state.
		$writer = new SqlWriter( $file );
		$writer->open( true );
		$dumper = new MySqlDumper( $db, $writer );
		$state  = $dumper->step( $state, new Deadline( 30 ), 2 );
		$writer->close();

		$sql = (string) file_get_contents( $file );

		$this->assertTrue( $state['done'] );
		$this->assertSame( 1, substr_count( $sql, 'SET NAMES utf8mb4' ) );
		$this->assertSame( 1, substr_count( $sql, 'DROP TABLE IF EXISTS `wp_options`' ) );
		$this->assertSame( 6, $state['count'] );
	}

	/**
	 * Type helpers.
	 *
	 * @return void
	 */
	public function test_type_helpers(): void {
		$this->assertTrue( MySqlDumper::is_binary_type( 'longblob' ) );
		$this->assertTrue( MySqlDumper::is_binary_type( 'varbinary(255)' ) );
		$this->assertFalse( MySqlDumper::is_binary_type( 'longtext' ) );
		$this->assertTrue( MySqlDumper::is_numeric_type( 'bigint(20) unsigned' ) );
		$this->assertTrue( MySqlDumper::is_numeric_type( 'decimal(10,2)' ) );
		$this->assertFalse( MySqlDumper::is_numeric_type( 'varchar(191)' ) );
	}
}
