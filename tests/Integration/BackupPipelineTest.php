<?php
/**
 * End-to-end pipeline test.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Integration;

use Backupino\Backup\Stages\DatabaseStage;
use Backupino\Backup\Stages\FilesStage;
use Backupino\Backup\Stages\FinalizeStage;
use Backupino\Backup\Stages\ScanStage;
use Backupino\Backup\ZipArchiveReader;
use Backupino\Core\Paths;
use Backupino\Core\Settings;
use Backupino\Jobs\Job;
use Backupino\Jobs\JobRepository;
use Backupino\Jobs\JobRunner;
use Backupino\Jobs\JobStatus;
use Backupino\Jobs\Pipeline;
use Backupino\Support\Logger;
use Backupino\Support\SystemClock;
use Backupino\Tests\Support\FakeDb;
use Backupino\Tests\Support\MemoryOptions;
use Backupino\Tests\TestCase;

/**
 * Runs a real backup from scanning to a published package, against a fake site
 * and a fake database, then inspects the produced artefact.
 */
final class BackupPipelineTest extends TestCase {

	/**
	 * Build a WordPress-ish tree in a temp directory.
	 *
	 * @return string Root path.
	 */
	private function site(): string {
		$root = $this->temp_dir();

		$this->make_file( $root, 'index.php', "<?php // front controller\n" );
		$this->make_file( $root, 'wp-config.php', "<?php define( 'DB_NAME', 'x' );\n" );
		$this->make_file( $root, 'wp-content/themes/x/style.css', "body { color: #000; }\n" );
		$this->make_file( $root, 'wp-content/themes/x/functions.php', "<?php\n" );
		$this->make_file( $root, 'wp-content/uploads/2024/photo.jpg', str_repeat( 'J', 4096 ) );
		$this->make_file( $root, 'wp-content/cache/page.html', 'cached' );
		$this->make_file( $root, 'wp-content/ai1wm-backups/old.wpress', 'backup' );
		$this->make_file( $root, 'debug.log', 'log' );

		return $root;
	}

	/**
	 * Fake database with two tables.
	 *
	 * @return FakeDb
	 */
	private function database(): FakeDb {
		$db = new FakeDb();

		$db->tables['wp_options'] = array(
			'type'    => 'BASE TABLE',
			'create'  => 'CREATE TABLE `wp_options` (`option_id` bigint NOT NULL, `option_name` varchar(191), PRIMARY KEY (`option_id`))',
			'columns' => array(
				array( 'Field' => 'option_id', 'Type' => 'bigint(20)', 'Key' => 'PRI' ),
				array( 'Field' => 'option_name', 'Type' => 'varchar(191)', 'Key' => '' ),
			),
			'rows'    => array(
				array( 'option_id' => 1, 'option_name' => 'siteurl' ),
				array( 'option_id' => 2, 'option_name' => 'home' ),
			),
		);

		$db->tables['wp_posts'] = array(
			'type'    => 'BASE TABLE',
			'create'  => 'CREATE TABLE `wp_posts` (`ID` bigint NOT NULL, `post_title` text, PRIMARY KEY (`ID`))',
			'columns' => array(
				array( 'Field' => 'ID', 'Type' => 'bigint(20)', 'Key' => 'PRI' ),
				array( 'Field' => 'post_title', 'Type' => 'text', 'Key' => '' ),
			),
			'rows'    => array(
				array( 'ID' => 1, 'post_title' => 'Hello' ),
			),
		);

		return $db;
	}

	/**
	 * A full run produces an archive, an installer and a package descriptor.
	 *
	 * @return void
	 */
	public function test_full_run_produces_a_restorable_package(): void {
		$root  = $this->site();
		$paths = new Paths( $root . '/wp-content/uploads/backupino', $root );
		$paths->ensure();

		$db       = $this->database();
		$clock    = new SystemClock();
		$settings = new Settings( new MemoryOptions() );
		$logger   = new Logger( $paths->logs() . '/backupino.log' );

		$jobs = new JobRepository( $db, $clock );

		$runner = new JobRunner(
			$jobs,
			new Pipeline(
				array(
					new ScanStage(),
					new FilesStage(),
					new DatabaseStage(),
					new FinalizeStage(),
				)
			),
			$settings,
			$paths,
			$logger,
			$db,
			$clock
		);

		$job = Job::create( array( 'type' => 'test' ), $clock );
		$job = $runner->run( $job, 120 );

		$this->assertSame( JobStatus::COMPLETED, $job->status, 'Job failed: ' . $job->message );

		// --- package metadata ---------------------------------------------
		$this->assertArrayHasKey( 'package', $job->payload );

		$package = $job->payload['package'];

		$this->assertSame( 64, strlen( (string) $package['hash'] ) );
		$this->assertGreaterThan( 0, (int) $package['size'] );

		$archive = $paths->packages() . '/' . $package['archive'];
		$installer = $paths->packages() . '/' . $package['installer'];

		$this->assertTrue( is_file( $archive ), 'Archive was not published.' );
		$this->assertTrue( is_file( $installer ), 'Installer was not published.' );

		// --- archive content ----------------------------------------------
		$reader = new ZipArchiveReader();

		$this->assertTrue( $reader->open( $archive ) );

		$entries = $reader->entries();

		$this->assertTrue( in_array( 'index.php', $entries, true ) );
		$this->assertTrue( in_array( 'wp-content/themes/x/style.css', $entries, true ) );
		$this->assertTrue( in_array( 'database.sql', $entries, true ) );
		$this->assertTrue( in_array( 'backupino-package.json', $entries, true ) );
		$this->assertTrue( in_array( 'backupino-manifest.ndjson', $entries, true ) );

		// Excluded content must not leak into the package.
		$this->assertFalse( in_array( 'wp-config.php', $entries, true ), 'wp-config.php must be regenerated, not copied.' );
		$this->assertFalse( in_array( 'wp-content/cache/page.html', $entries, true ) );
		$this->assertFalse( in_array( 'debug.log', $entries, true ) );
		$this->assertFalse( in_array( 'wp-content/ai1wm-backups/old.wpress', $entries, true ) );

		$sql = (string) $reader->read( 'database.sql' );

		$this->assertStringContains( 'CREATE TABLE `wp_options`', $sql );
		$this->assertStringContains( "INSERT INTO `wp_posts`", $sql );

		$reader->close();

		// --- installer ----------------------------------------------------
		$code = (string) file_get_contents( $installer );

		token_get_all( $code, TOKEN_PARSE );

		$this->assertStringContains( 'class Extractor', $code );
		$this->assertStringContains( 'class ConfigWriter', $code );

		$this->assertSame( 1, preg_match( "/base64_decode\\(\\s*'([A-Za-z0-9+\\/=]+)'\\s*\\)/", $code, $match ) );

		$payload = json_decode( (string) base64_decode( $match[1] ), true );

		$this->assertSame( (string) $package['hash'], $payload['archive_hash'] );
		$this->assertSame( basename( (string) $package['archive'] ), $payload['archive'] );
		$this->assertSame( 'https://example.test', $payload['site_url'] );

		// --- progress ------------------------------------------------------
		$this->assertSame( 100, $job->progress );
	}

	/**
	 * A cancelled job stops being stepped.
	 *
	 * @return void
	 */
	public function test_cancelled_job_is_not_run(): void {
		$root  = $this->temp_dir();
		$paths = new Paths( $root . '/storage', $root );
		$paths->ensure();

		$db     = new FakeDb();
		$clock  = new SystemClock();
		$runner = new JobRunner(
			new JobRepository( $db, $clock ),
			new Pipeline( array( new ScanStage() ) ),
			new Settings( new MemoryOptions() ),
			$paths,
			new Logger( $paths->logs() . '/log.txt' ),
			$db,
			$clock
		);

		$job = Job::create( array(), $clock );
		$job = $runner->cancel( $job );

		$this->assertSame( JobStatus::CANCELLED, $job->status );

		$job = $runner->step( $job );

		$this->assertSame( JobStatus::CANCELLED, $job->status );
	}
}
