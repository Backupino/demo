<?php
/**
 * Installer tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Backup\InstallerBuilder;
use Backupino\Backup\ZipArchiveWriter;
use Backupino\Installer\ConfigWriter;
use Backupino\Installer\Env;
use Backupino\Installer\Extractor;
use Backupino\Tests\TestCase;

/**
 * Covers the standalone installer pipeline: generation, config writing and
 * chunked extraction.
 */
final class InstallerTest extends TestCase {

	/**
	 * The generated installer is valid PHP and carries the payload.
	 *
	 * @return void
	 */
	public function test_builder_produces_valid_installer(): void {
		$builder = new InstallerBuilder();
		$payload = array(
			'version'      => '0.1.0',
			'name'         => 'site_20260101',
			'token'        => 'abc123',
			'archive'      => 'site_20260101.zip',
			'archive_hash' => str_repeat( 'a', 64 ),
			'site_url'     => 'https://old.example.com',
			'table_prefix' => 'wp_',
		);

		$code = $builder->build( $payload );

		$this->assertGreaterThan( 1000, strlen( $code ) );
		$this->assertStringNotContains( '__BACKUPINO_PAYLOAD__', $code );
		$this->assertStringNotContains( '__BACKUPINO_BODY__', $code );
		$this->assertStringContains( 'class Env', $code );
		$this->assertStringContains( 'class Extractor', $code );
		$this->assertStringContains( 'class Replacer', $code );

		// Exactly one strict_types declaration after flattening.
		$this->assertSame( 1, substr_count( $code, 'declare( strict_types = 1 );' ) );

		// The payload is embedded base64 encoded.
		$this->assertSame( 1, preg_match( "/base64_decode\(\\s*'([A-Za-z0-9+\\/=]+)'\\s*\)/", $code, $match ) );
		$decoded = json_decode( (string) base64_decode( $match[1] ), true );
		$this->assertSame( 'site_20260101', $decoded['name'] );
		$this->assertSame( 'abc123', $decoded['token'] );

		// Exactly one namespace declaration survives the flattening step.
		$this->assertSame( 1, preg_match_all( '/^namespace\s+/m', $code ) );
		$this->assertSame( 1, substr_count( $code, 'namespace Backupino\\Installer;' ) );

		// The file must be syntactically valid.
		token_get_all( $code, TOKEN_PARSE );
		$this->assertTrue( true );
	}

	/**
	 * wp-config.php rendering escapes values and parses as PHP.
	 *
	 * @return void
	 */
	public function test_config_writer(): void {
		$config = array(
			'name'     => 'backupino_db',
			'user'     => 'dbuser',
			'password' => "pa'ss\\word",
			'host'     => 'localhost',
			'port'     => 3307,
			'prefix'   => 'wp_',
			'debug'    => false,
		);

		$code = ConfigWriter::render( $config );

		$this->assertStringContains( "define( 'DB_NAME', 'backupino_db' );", $code );
		$this->assertStringContains( "define( 'DB_HOST', 'localhost:3307' );", $code );
		$this->assertStringContains( "define( 'DB_PASSWORD', 'pa\\'ss\\\\word' );", $code );
		$this->assertStringContains( '$table_prefix = \'wp_\';', $code );
		$this->assertStringContains( 'AUTH_KEY', $code );
		$this->assertStringContains( 'wp-settings.php', $code );

		token_get_all( $code, TOKEN_PARSE );
	}

	/**
	 * The config file is written and old copies are preserved as a backup.
	 *
	 * @return void
	 */
	public function test_config_writer_writes_file_and_keeps_backup(): void {
		$dir  = $this->temp_dir();
		$file = $dir . '/wp-config.php';

		file_put_contents( $file, '<?php // old' );

		$this->assertTrue( ConfigWriter::write( $file, array( 'name' => 'db', 'user' => 'u', 'password' => 'p' ) ) );

		$backups = glob( $file . '.backupino-*' );

		$this->assertCount( 0, $backups );
		$this->assertStringContains( 'wp-settings.php', (string) file_get_contents( $file ) );
	}

	/**
	 * Original wp-config.php settings and custom constants are preserved during transformation.
	 *
	 * @return void
	 */
	public function test_config_writer_preserves_custom_settings(): void {
		$original = "<?php\n"
			. "define( 'DB_NAME', 'old_db' );\n"
			. "define( 'DB_USER', 'old_user' );\n"
			. "define( 'DB_PASSWORD', 'old_pass' );\n"
			. "define( 'DB_HOST', '127.0.0.1' );\n"
			. "\$table_prefix = 'wp_';\n"
			. "// Custom configuration for Redis\n"
			. "define( 'WP_REDIS_HOST', '127.0.0.1' );\n"
			. "define( 'WP_REDIS_PORT', 6379 );\n"
			. "define( 'WP_MEMORY_LIMIT', '512M' );\n"
			. "require_once ABSPATH . 'wp-settings.php';\n";

		$config = array(
			'name'     => 'new_database',
			'user'     => 'new_user',
			'password' => 'new_pass',
			'host'     => 'localhost',
			'prefix'   => 'custom_',
		);

		$updated = ConfigWriter::update_wp_config( $original, $config );

		$this->assertStringContains( "define( 'DB_NAME', 'new_database' );", $updated );
		$this->assertStringContains( "define( 'DB_USER', 'new_user' );", $updated );
		$this->assertStringContains( "define( 'DB_PASSWORD', 'new_pass' );", $updated );
		$this->assertStringContains( "define( 'DB_HOST', 'localhost' );", $updated );
		$this->assertStringContains( "\$table_prefix = 'custom_';", $updated );
		$this->assertStringContains( "// Custom configuration for Redis", $updated );
		$this->assertStringContains( "define( 'WP_REDIS_HOST', '127.0.0.1' );", $updated );
		$this->assertStringContains( "define( 'WP_REDIS_PORT', 6379 );", $updated );
		$this->assertStringContains( "define( 'WP_MEMORY_LIMIT', '512M' );", $updated );
	}

	/**
	 * Secret keys are long and unique.
	 *
	 * @return void
	 */
	public function test_salts(): void {
		$salts = ConfigWriter::salts();

		$this->assertCount( 8, $salts );
		$this->assertSame( 64, strlen( $salts['AUTH_KEY'] ) );
		$this->assertTrue( $salts['AUTH_KEY'] !== $salts['AUTH_SALT'] );
	}

	/**
	 * Environment checks report what is missing.
	 *
	 * @return void
	 */
	public function test_env_checks(): void {
		$dir     = $this->temp_dir();
		$archive = $this->make_file( $dir, 'package.zip', 'not a real zip' );

		$checks = Env::check(
			array( 'archive_hash' => hash_file( 'sha256', $archive ) ),
			$dir,
			$archive
		);

		$labels = array();

		foreach ( $checks as $check ) {
			$labels[ $check['label'] ] = $check['ok'];
		}

		$this->assertTrue( $labels['PHP version'] );
		$this->assertTrue( $labels['Archive checksum'] );
		$this->assertTrue( Env::passes( $checks ) );

		$broken = Env::check( array( 'archive_hash' => 'deadbeef' ), $dir, $archive );
		$this->assertFalse( Env::passes( $broken ) );
	}

	/**
	 * Extraction is chunked and reports the total.
	 *
	 * @return void
	 */
	public function test_chunked_extraction(): void {
		$dir  = $this->temp_dir();
		$zip  = $dir . '/package.zip';
		$w    = new ZipArchiveWriter();
		$w->open( $zip );
		$w->add_from_string( 'index.php', '<?php' );
		$w->add_from_string( 'wp-content/a.txt', 'A' );
		$w->add_from_string( 'wp-content/b.txt', 'B' );
		$w->close();

		$target    = $dir . '/site';
		$extractor = new Extractor( $zip, $target );

		$this->assertTrue( $extractor->open() );
		$this->assertSame( 3, $extractor->total() );

		$offset = 0;
		$first  = $extractor->step( $offset, 2 );

		$this->assertSame( 2, $first );
		$this->assertSame( 2, $offset );

		$second = $extractor->step( $offset, 5 );

		$this->assertSame( 1, $second );
		$this->assertSame( 3, $offset );
		$this->assertTrue( is_file( $target . '/wp-content/b.txt' ) );

		$extractor->close();
	}

	/**
	 * Unsafe archive entries are rejected.
	 *
	 * @return void
	 */
	public function test_unsafe_entries(): void {
		$this->assertFalse( Extractor::is_safe( '../evil.php' ) );
		$this->assertFalse( Extractor::is_safe( 'a/../../b' ) );
		$this->assertFalse( Extractor::is_safe( '/abs' ) );
		$this->assertTrue( Extractor::is_safe( 'wp-content/themes/x/style.css' ) );
	}
	/**
	 * Potentially dangerous web server configs (.htaccess, .user.ini, php.ini, wp-config.php)
	 * are quarantined as .backupino-orig so that extraction never crashes the web server.
	 *
	 * @return void
	 */
	public function test_server_configs_are_quarantined_during_extraction(): void {
		$dir = $this->temp_dir();
		$zip = $dir . '/test-configs.zip';
		$w   = new ZipArchiveWriter();
		$w->open( $zip );
		$w->add_from_string( '.htaccess', 'Options +FollowSymLinks' );
		$w->add_from_string( '.user.ini', 'auto_prepend_file=missing.php' );
		$w->add_from_string( 'php.ini', 'memory_limit=512M' );
		$w->add_from_string( 'wp-config.php', '<?php define("DB_NAME", "orig");' );
		$w->close();

		$target    = $dir . '/extracted';
		$extractor = new Extractor( $zip, $target );
		$extractor->open();

		$offset = 0;
		$extractor->step( $offset, 10 );
		$extractor->close();

		$this->assertFalse( is_file( $target . '/.htaccess' ) );
		$this->assertTrue( is_file( $target . '/.htaccess.backupino-orig' ) );
		$this->assertSame( 'Options +FollowSymLinks', (string) file_get_contents( $target . '/.htaccess.backupino-orig' ) );

		$this->assertFalse( is_file( $target . '/.user.ini' ) );
		$this->assertTrue( is_file( $target . '/.user.ini.backupino-orig' ) );

		$this->assertFalse( is_file( $target . '/php.ini' ) );
		$this->assertTrue( is_file( $target . '/php.ini.backupino-orig' ) );

		$this->assertFalse( is_file( $target . '/wp-config.php' ) );
		$this->assertTrue( is_file( $target . '/wp-config.backupino-orig.php' ) );
	}
}
