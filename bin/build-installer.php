<?php
/**
 * Builds a standalone `installer.php` for inspection and release testing.
 *
 * Usage:
 *   php bin/build-installer.php
 *   php bin/build-installer.php --out=dist/installer.php
 *
 * @package Backupino
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || define( 'ABSPATH', dirname( __DIR__ ) . '/' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress core constant.
defined( 'BACKUPINO_VERSION' ) || define( 'BACKUPINO_VERSION', '0.1.0' );

require_once dirname( __DIR__ ) . '/free/src/autoload.php';

$backupino_out = dirname( __DIR__ ) . '/releases/installer.php';

foreach ( array_slice( $argv ?? array(), 1 ) as $backupino_arg ) {
	if ( 0 === strpos( $backupino_arg, '--out=' ) ) {
		$backupino_out = substr( $backupino_arg, 6 );
	}
}

$backupino_builder = new \Backupino\Backup\InstallerBuilder();

$backupino_payload = array(
	'version'      => '0.1.0',
	'name'         => 'development_' . gmdate( 'Ymd_His' ),
	'token'        => bin2hex( random_bytes( 16 ) ),
	'archive'      => 'development.zip',
	'archive_hash' => str_repeat( '0', 64 ),
	'archive_size' => 0,
	'site_url'     => 'https://example.test',
	'home_url'     => 'https://example.test',
	'table_prefix' => 'wp_',
	'created'      => gmdate( 'Y-m-d H:i:s' ),
	'password'     => '',
);

$backupino_code = $backupino_builder->build( $backupino_payload );

if ( '' === $backupino_code ) {
	fwrite( STDERR, "The installer could not be generated: are the src/Installer sources present?\n" );
	exit( 1 );
}

try {
	token_get_all( $backupino_code, TOKEN_PARSE );
} catch ( ParseError $backupino_error ) {
	fwrite( STDERR, 'Generated installer is invalid: ' . $backupino_error->getMessage() . "\n" );
	exit( 1 );
}

$backupino_dir = dirname( $backupino_out );

if ( ! is_dir( $backupino_dir ) ) {
	mkdir( $backupino_dir, 0777, true );
}

file_put_contents( $backupino_out, $backupino_code );

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI output, not HTML.
printf( "Installer written to %s (%d bytes)\n", $backupino_out, strlen( $backupino_code ) );
