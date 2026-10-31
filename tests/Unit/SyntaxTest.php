<?php
/**
 * Syntax test.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Tests\TestCase;

/**
 * Parses every PHP file in the repository.
 *
 * Files that are not covered by a unit test (templates, admin screens, the CLI
 * command) are still guaranteed to be syntactically valid, which catches the
 * most common "works on my machine" mistake before a release.
 */
final class SyntaxTest extends TestCase {

	/**
	 * Every `.php` file parses.
	 *
	 * @return void
	 */
	public function test_every_php_file_parses(): void {
		$root = dirname( __DIR__, 2 );
		$list = array();
		$dirs = array( $root . '/free/src', $root . '/free/templates', $root . '/pro', $root . '/tests', $root . '/bin' );

		foreach ( $dirs as $dir ) {
			if ( ! is_dir( $dir ) ) {
				continue;
			}

			$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) );

			foreach ( $iterator as $file ) {
				if ( 'php' === strtolower( $file->getExtension() ) ) {
					$list[] = $file->getPathname();
				}
			}
		}

		$list[] = $root . '/free/backupino.php';
		$list[] = $root . '/free/uninstall.php';
		$list[] = $root . '/pro/autoload.php';

		$this->assertGreaterThan( 20, count( $list ) );

		foreach ( $list as $file ) {
			$code = (string) file_get_contents( $file );

			if ( false !== strpos( $code, '__BACKUPINO_BODY__' ) ) {
				// The installer template is only valid after the builder has
				// substituted the placeholder; it is verified in InstallerTest.
				continue;
			}

			try {
				token_get_all( $code, TOKEN_PARSE );
			} catch ( \ParseError $e ) {
				$this->fail( sprintf( '%s does not parse: %s (line %d)', $file, $e->getMessage(), $e->getLine() ) );
			}

			$this->assertTrue( true );
		}
	}

	/**
	 * Every source file declares strict types and a docblock.
	 *
	 * @return void
	 */
	public function test_source_files_follow_the_house_style(): void {
		$repo   = dirname( __DIR__, 2 );
		$roots  = array( $repo . '/free/src', $repo . '/pro/src' );
		$seen   = 0;

		foreach ( $roots as $root ) {
			if ( ! is_dir( $root ) ) {
				continue;
			}

			$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) );

			foreach ( $iterator as $file ) {
				if ( 'php' !== strtolower( $file->getExtension() ) ) {
					continue;
				}

				$code = (string) file_get_contents( $file->getPathname() );

				$this->assertStringContains( 'declare( strict_types = 1 );', $code, basename( $file->getPathname() ) );
				$this->assertStringContains( '@package Backupino', $code, basename( $file->getPathname() ) );

				$seen++;
			}
		}

		$this->assertGreaterThan( 20, $seen );
	}
}
