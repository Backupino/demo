<?php
/**
 * Latest package tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Download\LatestPackage;
use Backupino\Tests\TestCase;

/**
 * Covers resolution of the newest archive in a package directory.
 */
final class LatestPackageTest extends TestCase {

	/**
	 * The newest archive wins regardless of its name.
	 *
	 * @return void
	 */
	public function test_returns_the_newest_archive(): void {
		$dir = $this->temp_dir();

		$old = $this->make_file( $dir, 'aaa-old.zip', 'old' );
		$new = $this->make_file( $dir, 'zzz-new.zip', 'new' );

		$this->make_file( $dir, 'mmm-installer.php', '<?php' );

		touch( $old, time() - 3600 );
		touch( $new, time() );

		$this->assertSame( $new, ( new LatestPackage() )->find( $dir ) );
	}

	/**
	 * Without archives the lookup returns `null`.
	 *
	 * @return void
	 */
	public function test_empty_directory_returns_null(): void {
		$dir = $this->temp_dir();

		$this->make_file( $dir, 'notes.txt', 'x' );

		$this->assertNull( ( new LatestPackage() )->find( $dir ) );
	}

	/**
	 * A missing directory is not an error.
	 *
	 * @return void
	 */
	public function test_missing_directory_returns_null(): void {
		$this->assertNull( ( new LatestPackage() )->find( $this->temp_dir() . '/nope' ) );
	}
}
