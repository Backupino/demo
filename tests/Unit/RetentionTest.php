<?php
/**
 * Retention tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Cron\Retention;
use Backupino\Tests\TestCase;

/**
 * Covers package pruning.
 */
final class RetentionTest extends TestCase {

	/**
	 * The newest packages are kept and their installers removed together with
	 * the pruned archives.
	 *
	 * @return void
	 */
	public function test_prunes_oldest_packages(): void {
		$dir = $this->temp_dir();

		for ( $i = 1; $i <= 5; $i++ ) {
			$zip = $this->make_file( $dir, 'pkg-' . $i . '.zip', 'zip' );
			$this->make_file( $dir, 'pkg-' . $i . '-installer.php', '<?php' );
			touch( $zip, time() - ( 3600 * ( 6 - $i ) ) );
		}

		$removed = Retention::prune( $dir, 2 );

		$this->assertSame( 3, $removed );
		$this->assertTrue( is_file( $dir . '/pkg-5.zip' ) );
		$this->assertTrue( is_file( $dir . '/pkg-4.zip' ) );
		$this->assertFalse( is_file( $dir . '/pkg-1.zip' ) );
		$this->assertFalse( is_file( $dir . '/pkg-1-installer.php' ) );
	}

	/**
	 * Nothing happens when the limit is not exceeded.
	 *
	 * @return void
	 */
	public function test_keeps_everything_below_limit(): void {
		$dir = $this->temp_dir();

		$this->make_file( $dir, 'a.zip', 'x' );
		$this->make_file( $dir, 'b.zip', 'x' );

		$this->assertSame( 0, Retention::prune( $dir, 5 ) );
		$this->assertTrue( is_file( $dir . '/a.zip' ) );
	}

	/**
	 * A missing directory is not an error.
	 *
	 * @return void
	 */
	public function test_missing_directory(): void {
		$this->assertSame( 0, Retention::prune( '/nonexistent-backupino-dir', 3 ) );
	}
}
