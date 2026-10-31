<?php
/**
 * Scanner tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Backup\FileFilter;
use Backupino\Backup\Scanner;
use Backupino\Core\Paths;
use Backupino\Jobs\Deadline;
use Backupino\Tests\Support\ExpiredDeadline;
use Backupino\Tests\TestCase;

/**
 * Covers the resumable directory walker.
 */
final class ScannerTest extends TestCase {

	/**
	 * Build a small site tree.
	 *
	 * @return array{0: string, 1: Paths}
	 */
	private function site(): array {
		$root = $this->temp_dir();

		$this->make_file( $root, 'index.php', '<?php' );
		$this->make_file( $root, 'wp-config.php', '<?php' );
		$this->make_file( $root, 'wp-content/themes/x/style.css', 'body{}' );
		$this->make_file( $root, 'wp-content/themes/x/functions.php', '<?php' );
		$this->make_file( $root, 'wp-content/cache/page.html', 'cached' );
		$this->make_file( $root, 'wp-content/uploads/photo.jpg', 'jpg' );
		$this->make_file( $root, 'wp-content/uploads/backupino/old.zip', 'zip' );
		$this->make_file( $root, 'node_modules/pkg/index.js', 'js' );
		$this->make_file( $root, 'notes.log', 'log' );

		return array( $root, new Paths( $root . '/wp-content/uploads/backupino', $root ) );
	}

	/**
	 * Collect the entries produced by a scan.
	 *
	 * @param Scanner $scanner Scanner.
	 * @param array<string, mixed> $state State.
	 * @param Deadline $deadline Deadline.
	 * @return array{0: string[], 1: array<string, mixed>}
	 */
	private function collect( Scanner $scanner, array $state, Deadline $deadline ): array {
		$found = array();

		$state = $scanner->scan(
			$state,
			$deadline,
			static function ( $entry ) use ( &$found ) {
				$found[] = $entry->relative();
			}
		);

		return array( $found, $state );
	}

	/**
	 * Only allowed files end up in the manifest.
	 *
	 * @return void
	 */
	public function test_scan_applies_filters(): void {
		list( $root, $paths ) = $this->site();
		$scanner = new Scanner( $paths, new FileFilter() );

		list( $found, $state ) = $this->collect( $scanner, Scanner::initial_state(), new Deadline( 10 ) );

		sort( $found );
		$expected = array(
			'index.php',
			'wp-content/themes/x/functions.php',
			'wp-content/themes/x/style.css',
			'wp-content/uploads/photo.jpg',
		);

		sort( $expected );

		$this->assertSame( $expected, $found );
		$this->assertTrue( $state['done'] );
		$this->assertSame( 4, $state['files'] );
		$this->assertGreaterThan( 0, $state['bytes'] );
	}

	/**
	 * The plugin storage directory is never archived (no recursion).
	 *
	 * @return void
	 */
	public function test_storage_directory_is_skipped(): void {
		list( $root, $paths ) = $this->site();
		$scanner = new Scanner( $paths, new FileFilter() );

		list( $found ) = $this->collect( $scanner, Scanner::initial_state(), new Deadline( 10 ) );

		$this->assertFalse( in_array( 'wp-content/uploads/backupino/old.zip', $found, true ) );
	}

	/**
	 * An expired budget leaves the state untouched so work can resume later.
	 *
	 * @return void
	 */
	public function test_expired_deadline_pauses_scan(): void {
		list( $root, $paths ) = $this->site();
		$scanner = new Scanner( $paths, new FileFilter() );

		list( $found, $state ) = $this->collect( $scanner, Scanner::initial_state(), new ExpiredDeadline() );

		$this->assertCount( 0, $found );
		$this->assertFalse( $state['done'] );

		list( $found, $state ) = $this->collect( $scanner, $state, new Deadline( 10 ) );

		$this->assertCount( 4, $found );
		$this->assertTrue( $state['done'] );
	}

	/**
	 * A partially walked stack continues where it stopped.
	 *
	 * @return void
	 */
	public function test_state_can_be_resumed_from_a_partial_stack(): void {
		list( $root, $paths ) = $this->site();
		$scanner = new Scanner( $paths, new FileFilter() );

		$state = Scanner::initial_state();
		$state['stack'] = array( 'wp-content/themes' );

		list( $found, $state ) = $this->collect( $scanner, $state, new Deadline( 10 ) );

		sort( $found );

		$this->assertSame(
			array( 'wp-content/themes/x/functions.php', 'wp-content/themes/x/style.css' ),
			$found
		);
		$this->assertTrue( $state['done'] );
	}
}
