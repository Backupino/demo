<?php
/**
 * Security audit and QA tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Admin\RestController;
use Backupino\Backup\ZipArchiveReader;
use Backupino\Core\Capabilities;
use Backupino\Download\DownloadLink;
use Backupino\Plugin;
use Backupino\Tests\Support\MemoryOptions;
use Backupino\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Automates security and quality assurance checks for WordPress.org compliance.
 */
final class SecurityQATest extends TestCase {

	/**
	 * Every REST endpoint in the plugin must be guarded by a permission_callback.
	 *
	 * @return void
	 */
	public function test_all_rest_routes_have_permission_callbacks(): void {
		$file    = dirname( __DIR__, 2 ) . '/free/src/Admin/RestController.php';
		$content = (string) file_get_contents( $file );

		// Count register_rest_route occurrences
		$routeCount = preg_match_all( '/register_rest_route\s*\(/', $content );
		$this->assertGreaterThan( 5, $routeCount );

		// Count permission_callback occurrences
		$callbackCount = preg_match_all( "/'permission_callback'\s*=>/", $content );
		$this->assertTrue( $callbackCount >= $routeCount, 'Every route must have a permission callback' );

		// Assert that can_manage evaluates properly
		$controller = new RestController();
		$this->assertFalse( $controller->can_manage() );
	}

	/**
	 * No dangerous or forbidden execution functions anywhere in the free distribution.
	 *
	 * @return void
	 */
	public function test_no_dangerous_execution_functions(): void {
		$freeDir  = dirname( __DIR__, 2 ) . '/free';
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $freeDir, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		$forbidden = array(
			'eval'        => '/(?<!->)(?<!::)\beval\s*\(/i',
			'exec'        => '/(?<!->)(?<!::)\bexec\s*\(/i',
			'system'      => '/(?<!function\s)(?<!->)(?<!::)\bsystem\s*\(/i',
			'shell_exec'  => '/(?<!->)(?<!::)\bshell_exec\s*\(/i',
			'passthru'    => '/(?<!->)(?<!::)\bpassthru\s*\(/i',
			'proc_open'   => '/(?<!->)(?<!::)\bproc_open\s*\(/i',
			'popen'       => '/(?<!->)(?<!::)\bpopen\s*\(/i',
			'extract'     => '/(?<!function\s)(?<!->)(?<!::)\bextract\s*\(/i',
		);

		foreach ( $iterator as $file ) {
			if ( 'php' !== strtolower( $file->getExtension() ) ) {
				continue;
			}

			$code = (string) file_get_contents( $file->getPathname() );
			$rel  = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $freeDir ) + 1 ) );

			foreach ( $forbidden as $func => $pattern ) {
				$this->assertFalse(
					(bool) preg_match( $pattern, $code ),
					"Forbidden function '{$func}' found in {$rel}"
				);
			}
		}
	}

	/**
	 * 100% Offline-first: No remote CDNs, third-party scripts, or external font references.
	 *
	 * @return void
	 */
	public function test_offline_first_no_external_cdns(): void {
		$freeDir  = dirname( __DIR__, 2 ) . '/free';
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $freeDir, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		$cdnPatterns = array(
			'/https?:\/\/(?:cdn|cdnjs|unpkg|jsdelivr|googleapis|gstatic|font)\b/i',
		);

		foreach ( $iterator as $file ) {
			$ext = strtolower( $file->getExtension() );
			if ( ! in_array( $ext, array( 'php', 'css', 'js' ), true ) ) {
				continue;
			}

			$content = (string) file_get_contents( $file->getPathname() );
			$rel     = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $freeDir ) + 1 ) );

			foreach ( $cdnPatterns as $pattern ) {
				$this->assertFalse(
					(bool) preg_match( $pattern, $content ),
					"External CDN or remote asset reference detected in {$rel}"
				);
			}
		}
	}

	/**
	 * Zip-slip defense safely blocks traversal attempts.
	 *
	 * @return void
	 */
	public function test_zip_slip_security(): void {
		$malicious = array(
			'../etc/passwd',
			'..\\..\\windows\\system32',
			'/absolute/path',
			'\absolute\path',
			"safe/../../unsafe\0.php",
			'C:/Windows/win.ini',
			'file.php/../../outside',
		);

		foreach ( $malicious as $entry ) {
			$this->assertFalse(
				ZipArchiveReader::is_safe_entry( $entry ),
				"Zip entry '{$entry}' should have been flagged as unsafe"
			);
		}

		$valid = array(
			'index.php',
			'wp-content/uploads/photo.jpg',
			'nested/dir/file.txt',
		);

		foreach ( $valid as $entry ) {
			$this->assertTrue(
				ZipArchiveReader::is_safe_entry( $entry ),
				"Zip entry '{$entry}' should be recognized as safe"
			);
		}
	}

	/**
	 * Download keys have sufficient cryptographic entropy (16 bytes = 32 hex chars) and use hash_equals.
	 *
	 * @return void
	 */
	public function test_download_link_entropy(): void {
		$store = new MemoryOptions();
		$link  = new DownloadLink( $store );
		$key   = $link->key();

		$this->assertSame( 32, strlen( $key ) );
		$this->assertTrue( (bool) preg_match( '/^[a-f0-9]{32}$/', $key ) );
		$this->assertTrue( $link->matches( $key ) );
		$this->assertFalse( $link->matches( 'invalid-key' ) );
	}
}
