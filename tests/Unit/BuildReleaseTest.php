<?php
/**
 * Release packaging tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Build\ReleaseBuilder;
use Backupino\Tests\TestCase;
use ZipArchive;

require_once dirname( __DIR__, 2 ) . '/bin/build-release.php';

/**
 * Verifies that the release builder generates a clean distribution package.
 */
final class BuildReleaseTest extends TestCase {

	/**
	 * Build release creates a valid zip with correct layout and clean contents.
	 *
	 * @return void
	 */
	public function test_release_package_layout_and_cleanliness(): void {
		$dir = $this->temp_dir();
		$zipPath = $dir . '/test-release.zip';

		$root = dirname( __DIR__, 2 );
		$freeDir = $root . '/free';

		$stats = ReleaseBuilder::build( $freeDir, $zipPath, '1.0.0-test' );

		$this->assertSame( '1.0.0-test', $stats['version'] );
		$this->assertSame( $zipPath, $stats['archive'] );
		$this->assertGreaterThan( 50, $stats['files'] );
		$this->assertGreaterThan( 1000, $stats['size'] );
		$this->assertSame( 64, strlen( $stats['sha256'] ) );
		$this->assertTrue( is_file( $zipPath ), 'Zip package must be created' );

		$zip = new ZipArchive();
		$this->assertTrue( true === $zip->open( $zipPath, ZipArchive::RDONLY ) );

		$entries = array();
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$stat = $zip->statIndex( $i );
			$entries[] = $stat['name'];
		}
		$zip->close();

		$this->assertGreaterThan( 50, count( $entries ) );

		// All entries must start with 'backupino/'
		foreach ( $entries as $entry ) {
			$this->assertTrue(
				0 === strpos( $entry, 'backupino/' ),
				"Entry '{$entry}' does not start with backupino/"
			);

			// Excluded directories and unwanted files
			$this->assertFalse( 0 === strpos( $entry, 'backupino/pro/' ) );
			$this->assertFalse( 0 === strpos( $entry, 'backupino/tests/' ) );
			$this->assertFalse( 0 === strpos( $entry, 'backupino/docs/' ) );
			$this->assertFalse( 0 === strpos( $entry, 'backupino/tools/' ) );
			$this->assertFalse( 0 === strpos( $entry, 'backupino/bin/' ) );
			$this->assertFalse( 0 === strpos( $entry, 'backupino/.github/' ) );
			$this->assertFalse( false !== strpos( $entry, '.gitkeep' ) );
			$this->assertFalse( false !== strpos( $entry, '.DS_Store' ) );
		}

		// Essential files must be present
		$this->assertTrue( in_array( 'backupino/backupino.php', $entries, true ) );
		$this->assertTrue( in_array( 'backupino/uninstall.php', $entries, true ) );
		$this->assertTrue( in_array( 'backupino/readme.txt', $entries, true ) );
		$this->assertTrue( in_array( 'backupino/languages/backupino-fa_IR.mo', $entries, true ) );
		$this->assertTrue( in_array( 'backupino/languages/backupino.pot', $entries, true ) );
	}

	/**
	 * Version detection works from plugin file.
	 *
	 * @return void
	 */
	public function test_detect_version(): void {
		$mainFile = dirname( __DIR__, 2 ) . '/free/backupino.php';
		$version  = ReleaseBuilder::detect_version( $mainFile );

		$this->assertSame( '1.0.0', $version );
	}
}
