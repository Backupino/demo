<?php
/**
 * Release packaging tool.
 *
 * Packages the free plugin into a clean, WordPress.org-ready distribution archive.
 *
 * Usage:
 *   php bin/build-release.php
 *   php bin/build-release.php --version=1.0.0 --out=releases/backupino-1.0.0.zip
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Build;

use ParseError;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

defined( 'STDERR' ) || define( 'STDERR', fopen( 'php://stderr', 'wb' ) );

/**
 * Packs the free plugin files into a clean distribution zip.
 */
final class ReleaseBuilder {

	/**
	 * Build the distribution archive.
	 *
	 * @param string $freeDir Source directory of the free plugin.
	 * @param string $outZip  Target zip archive file path.
	 * @param string $version Release version string.
	 * @return array<string, mixed> Release build details.
	 */
	public static function build( string $freeDir, string $outZip, string $version = '1.0.0' ): array {
		if ( ! class_exists( 'ZipArchive' ) ) {
			throw new \RuntimeException( 'ZipArchive extension is required to build releases.' );
		}

		$outDir = dirname( $outZip );
		if ( ! is_dir( $outDir ) ) {
			mkdir( $outDir, 0777, true );
		}

		if ( is_file( $outZip ) ) {
			@unlink( $outZip );
		}

		$zip = new ZipArchive();
		$status = $zip->open( $outZip, ZipArchive::CREATE | ZipArchive::OVERWRITE );

		if ( true !== $status ) {
			throw new \RuntimeException( "Could not create zip archive: {$outZip} (code: {$status})" );
		}

		$filesCount = 0;
		$totalBytes = 0;
		$iterator   = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $freeDir, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		$excludedNames = array(
			'.gitkeep',
			'.DS_Store',
			'Thumbs.db',
		);

		$excludedExtensions = array(
			'bak',
			'tmp',
			'log',
			'swp',
		);

		foreach ( $iterator as $item ) {
			if ( ! $item->isFile() ) {
				continue;
			}

			$filename  = $item->getFilename();
			$extension = strtolower( $item->getExtension() );

			if ( in_array( $filename, $excludedNames, true ) ) {
				continue;
			}

			if ( 0 === strpos( $filename, '.' ) ) {
				continue;
			}

			if ( in_array( $extension, $excludedExtensions, true ) ) {
				continue;
			}

			$fullPath = $item->getPathname();
			$relPath  = substr( $fullPath, strlen( $freeDir ) + 1 );
			$relPath  = str_replace( '\\', '/', $relPath );

			// Validate PHP syntax for every packaged PHP file
			if ( 'php' === $extension ) {
				$code = (string) file_get_contents( $fullPath );
				if ( false === strpos( $code, '__BACKUPINO_BODY__' ) ) {
					try {
						token_get_all( $code, TOKEN_PARSE );
					} catch ( ParseError $e ) {
						$zip->close();
						@unlink( $outZip );
						throw new \RuntimeException( "Syntax error in {$relPath}: " . $e->getMessage() );
					}
				}
			}

			$entryName = 'backupino/' . $relPath;
			$zip->addFile( $fullPath, $entryName );
			$filesCount++;
			$totalBytes += (int) $item->getSize();
		}

		$zip->close();

		clearstatcache( true, $outZip );

		if ( ! is_file( $outZip ) ) {
			throw new \RuntimeException( "Archive was not created at {$outZip}." );
		}

		$zipSize = (int) filesize( $outZip );
		$sha256  = (string) hash_file( 'sha256', $outZip );

		return array(
			'version' => $version,
			'archive' => $outZip,
			'files'   => $filesCount,
			'bytes'   => $totalBytes,
			'size'    => $zipSize,
			'sha256'  => $sha256,
		);
	}

	/**
	 * Detect version from main plugin file.
	 *
	 * @param string $mainFile Path to backupino.php.
	 * @return string Version string.
	 */
	public static function detect_version( string $mainFile ): string {
		if ( ! is_file( $mainFile ) ) {
			return '1.0.0';
		}

		$content = (string) file_get_contents( $mainFile );
		if ( preg_match( "/define\(\s*['\"]BACKUPINO_VERSION['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\);/", $content, $matches ) ) {
			return $matches[1];
		}

		if ( preg_match( "/\*\s*Version:\s*([0-9a-zA-Z.-]+)/", $content, $matches ) ) {
			return $matches[1];
		}

		return '1.0.0';
	}
}

// CLI Execution entry point
if ( ! defined( 'BACKUPINO_TESTS' ) && ( ( isset( $_SERVER['argv'][0] ) && false !== strpos( (string) $_SERVER['argv'][0], 'build-release.php' ) ) || ( isset( $argv[0] ) && false !== strpos( (string) $argv[0], 'build-release.php' ) ) ) ) {
	$root    = dirname( __DIR__ );
	$free    = $root . '/free';
	$version = ReleaseBuilder::detect_version( $free . '/backupino.php' );
	$out     = $root . '/releases/backupino-' . $version . '.zip';

	foreach ( array_slice( $_SERVER['argv'], 1 ) as $arg ) {
		if ( 0 === strpos( (string) $arg, '--version=' ) ) {
			$version = substr( (string) $arg, 10 );
			$out     = $root . '/releases/backupino-' . $version . '.zip';
		}
		if ( 0 === strpos( (string) $arg, '--out=' ) ) {
			$out = substr( (string) $arg, 6 );
		}
	}

	try {
		$result = ReleaseBuilder::build( $free, $out, $version );
		echo "Backupino release package built successfully:\n";
		echo "  Version:  {$result['version']}\n";
		echo "  Archive:  {$result['archive']}\n";
		echo "  Files:    {$result['files']} files (" . number_format( $result['bytes'] ) . " bytes uncompressed)\n";
		echo "  Zip size: " . number_format( $result['size'] ) . " bytes\n";
		echo "  SHA-256:  {$result['sha256']}\n";
	} catch ( \Throwable $e ) {
		fwrite( STDERR, "Error: " . $e->getMessage() . "\n" );
		exit( 1 );
	}
}
