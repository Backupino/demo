<?php
/**
 * File filter tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Backup\FileFilter;
use Backupino\Tests\TestCase;

/**
 * Covers the exclusion rules.
 */
final class FileFilterTest extends TestCase {

	/**
	 * Built-in rules protect the usual junk.
	 *
	 * @return void
	 */
	public function test_default_rules(): void {
		$filter = new FileFilter();

		$this->assertTrue( $filter->allows_file( 'wp-content/themes/twentytwenty/style.css', 100 ) );
		$this->assertTrue( $filter->allows_file( 'index.php', 10 ) );

		$this->assertFalse( $filter->allows_file( 'wp-content/cache/page.html', 10 ) );
		$this->assertFalse( $filter->allows_file( 'wp-content/themes/x/node_modules/pkg/a.js', 10 ) );
		$this->assertFalse( $filter->allows_file( 'debug.log', 10 ) );
		$this->assertFalse( $filter->allows_file( 'wp-config.php', 10 ) );
		$this->assertFalse( $filter->allows_file( '.git/config', 10 ) );
	}

	/**
	 * Directories are pruned before they are walked.
	 *
	 * @return void
	 */
	public function test_directory_rules(): void {
		$filter = new FileFilter();

		$this->assertFalse( $filter->allows_dir( 'wp-content/cache' ) );
		$this->assertFalse( $filter->allows_dir( 'wp-content/themes/x/node_modules' ) );
		$this->assertTrue( $filter->allows_dir( 'wp-content/themes/x' ) );
		$this->assertTrue( $filter->allows_dir( '' ) );
	}

	/**
	 * User rules are added on top of the defaults.
	 *
	 * @return void
	 */
	public function test_custom_rules(): void {
		$filter = new FileFilter( array( '*.mp4', 'wp-content/uploads/2021/', 'big/**' ) );

		$this->assertFalse( $filter->allows_file( 'wp-content/uploads/clip.mp4', 10 ) );
		$this->assertFalse( $filter->allows_file( 'wp-content/uploads/2021/a.jpg', 10 ) );
		$this->assertFalse( $filter->allows_file( 'big/deep/file.bin', 10 ) );
		$this->assertTrue( $filter->allows_file( 'wp-content/uploads/2022/a.jpg', 10 ) );
	}

	/**
	 * Oversized files can be skipped entirely.
	 *
	 * @return void
	 */
	public function test_max_size(): void {
		$filter = new FileFilter( array(), 1024 );

		$this->assertTrue( $filter->allows_file( 'small.txt', 512 ) );
		$this->assertFalse( $filter->allows_file( 'huge.bin', 2048 ) );
	}

	/**
	 * Defaults can be disabled for packages that must contain everything.
	 *
	 * @return void
	 */
	public function test_defaults_can_be_disabled(): void {
		$filter = new FileFilter( array(), 0, false );

		$this->assertTrue( $filter->allows_file( 'wp-content/cache/page.html', 10 ) );
		$this->assertTrue( $filter->allows_file( 'debug.log', 10 ) );
	}

	/**
	 * Glob conversion handles `*`, `**` and `?`.
	 *
	 * @return void
	 */
	public function test_glob_to_regex(): void {
		$this->assertSame( '[^/]*\.log', FileFilter::glob_to_regex( '*.log' ) );
		$this->assertSame( 'a/.*/c', FileFilter::glob_to_regex( 'a/**/c' ) );
		$this->assertSame( 'file[^/]\.txt', FileFilter::glob_to_regex( 'file?.txt' ) );
	}
}
