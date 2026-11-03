<?php
/**
 * Paths tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Core\Paths;
use Backupino\Tests\TestCase;

/**
 * Covers the storage layout.
 */
final class PathsTest extends TestCase {

	/**
	 * The layout is derived from the base directory.
	 *
	 * @return void
	 */
	public function test_layout(): void {
		$root = $this->temp_dir();
		$paths = new Paths( $root . '/uploads/backupino', $root );

		$this->assertSame( $root . '/uploads/backupino', $paths->base() );
		$this->assertSame( $root . '/uploads/backupino/logs', $paths->logs() );
		$this->assertSame( $root . '/uploads/backupino/jobs', $paths->jobs() );
		$this->assertSame( $root . '/uploads/backupino/jobs/abc', $paths->job_dir( 'abc' ) );
		$this->assertSame( $root . '/uploads/backupino/packages', $paths->packages() );
		$this->assertSame( $root . '/', $paths->root() );
	}

	/**
	 * `ensure()` creates the tree and drops web guards.
	 *
	 * @return void
	 */
	public function test_ensure_creates_protected_directories(): void {
		$root  = $this->temp_dir();
		$paths = new Paths( $root . '/storage', $root );

		$paths->ensure();

		$this->assertTrue( is_dir( $paths->logs() ) );
		$this->assertTrue( is_dir( $paths->jobs() ) );
		$this->assertTrue( is_dir( $paths->packages() ) );
		$this->assertTrue( is_file( $paths->base() . '/.htaccess' ) );
		$this->assertTrue( is_file( $paths->base() . '/index.php' ) );
	}

	/**
	 * `contains()` recognises every child and rejects siblings.
	 *
	 * @return void
	 */
	public function test_contains(): void {
		$root  = $this->temp_dir();
		$paths = new Paths( $root . '/storage', $root );

		$this->assertTrue( $paths->contains( $root . '/storage' ) );
		$this->assertTrue( $paths->contains( $root . '/storage/jobs/x' ) );
		$this->assertTrue( $paths->contains( $root . '\\storage\\jobs\\x' ) );
		$this->assertFalse( $paths->contains( $root . '/storage-other' ) );
		$this->assertFalse( $paths->contains( $root . '/wp-content' ) );
	}

	/**
	 * Relative paths are computed against the storage root.
	 *
	 * @return void
	 */
	public function test_relative(): void {
		$root  = $this->temp_dir();
		$paths = new Paths( $root . '/storage', $root );

		$this->assertSame( 'packages/a.zip', $paths->relative( $root . '/storage/packages/a.zip' ) );
	}
}
