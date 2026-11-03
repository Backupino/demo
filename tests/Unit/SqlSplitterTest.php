<?php
/**
 * SQL splitter tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Installer\SqlSplitter;
use Backupino\Tests\TestCase;

/**
 * The splitter must never cut inside a string, a comment or a backtick.
 */
final class SqlSplitterTest extends TestCase {

	/**
	 * Write a dump and return the splitter.
	 *
	 * @param string $sql SQL contents.
	 * @return SqlSplitter
	 */
	private function splitter( string $sql ): SqlSplitter {
		$dir = $this->temp_dir();

		return new SqlSplitter( $this->make_file( $dir, 'dump.sql', $sql ) );
	}

	/**
	 * Semicolons inside strings and comments do not split statements.
	 *
	 * @return void
	 */
	public function test_respects_strings_and_comments(): void {
		$sql = "SET NAMES utf8mb4;\n"
			. "-- a comment with ; inside\n"
			. "INSERT INTO `t` (`a`) VALUES ('x;y'),(\"z;w\");\n"
			. "/* block ; comment */\n"
			. "INSERT INTO `t` (`a`) VALUES ('it''s; fine');\n"
			. "INSERT INTO `t` (`a`) VALUES ('backslash \\'; still inside');\n";

		$splitter = $this->splitter( $sql );
		$batch    = $splitter->read( 0, 1048576 );

		$this->assertCount( 4, $batch['statements'] );
		$this->assertStringContains( "SET NAMES utf8mb4", $batch['statements'][0] );
		$this->assertStringContains( "'x;y'", $batch['statements'][1] );
		$this->assertStringContains( "it''s; fine", $batch['statements'][2] );
		$this->assertStringContains( "still inside", $batch['statements'][3] );
		$this->assertTrue( $batch['done'] );
	}

	/**
	 * Reading with a small budget resumes exactly where it stopped.
	 *
	 * @return void
	 */
	public function test_resumes_across_chunks(): void {
		$sql = '';

		for ( $i = 0; $i < 25; $i++ ) {
			$sql .= "INSERT INTO `t` (`a`) VALUES ('row-{$i}');\n";
		}

		$splitter = $this->splitter( $sql );
		$offset   = 0;
		$found    = array();
		$guard    = 0;

		while ( true ) {
			$batch = $splitter->read( $offset, 65536 );
			$guard++;

			foreach ( $batch['statements'] as $statement ) {
				$found[] = $statement;
			}

			if ( $batch['offset'] === $offset && ! $batch['done'] ) {
				$this->fail( 'Splitter made no progress.' );
			}

			$offset = $batch['offset'];

			if ( $batch['done'] ) {
				break;
			}

			if ( $guard > 100 ) {
				$this->fail( 'Too many iterations.' );
			}
		}

		$this->assertCount( 25, $found );
		$this->assertStringContains( 'row-24', $found[24] );
	}

	/**
	 * An empty file yields nothing but reports completion.
	 *
	 * @return void
	 */
	public function test_empty_file(): void {
		$splitter = $this->splitter( '' );
		$batch    = $splitter->read( 0, 1024 );

		$this->assertCount( 0, $batch['statements'] );
		$this->assertTrue( $batch['done'] );
	}

	/**
	 * Backticked identifiers do not confuse the tracker.
	 *
	 * @return void
	 */
	public function test_backticks(): void {
		$splitter = $this->splitter( "SELECT `a;b` FROM `t`;\nSELECT 2;\n" );
		$batch    = $splitter->read( 0, 65536 );

		$this->assertCount( 2, $batch['statements'] );
		$this->assertStringContains( '`a;b`', $batch['statements'][0] );
	}
}
