<?php
/**
 * Updater tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Core\Updater;
use Backupino\Tests\TestCase;

/**
 * Tests GitHub release updater.
 */
final class UpdaterTest extends TestCase {

	/**
	 * Endpoints and repository slug are correct.
	 *
	 * @return void
	 */
	public function test_endpoints(): void {
		$this->assertSame( 'Backupino/plugin', Updater::GITHUB_REPO );
		$this->assertStringContains( 'api.github.com', Updater::API_URL );
		$this->assertStringContains( 'Backupino/plugin', Updater::API_URL );
	}

	/**
	 * Non-object transient passes through untouched.
	 *
	 * @return void
	 */
	public function test_non_object_transient(): void {
		$updater = new Updater();
		$this->assertFalse( $updater->check_update( false ) );
	}

	/**
	 * Plugin info handles empty or invalid requests.
	 *
	 * @return void
	 */
	public function test_plugin_info_skips_other_plugins(): void {
		$updater = new Updater();
		$args    = (object) array( 'slug' => 'other-plugin' );
		$this->assertSame( 'default', $updater->plugin_info( 'default', 'plugin_information', $args ) );
	}
}
