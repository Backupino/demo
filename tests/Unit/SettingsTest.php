<?php
/**
 * Settings tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Core\Settings;
use Backupino\Tests\Support\MemoryOptions;
use Backupino\Tests\TestCase;

/**
 * Covers the settings value object.
 */
final class SettingsTest extends TestCase {

	/**
	 * Defaults are complete and dot-notation readable.
	 *
	 * @return void
	 */
	public function test_defaults_are_readable_with_dot_notation(): void {
		$settings = new Settings( new MemoryOptions() );

		$this->assertSame( 'local', $settings->get( 'storage.driver' ) );
		$this->assertSame( 20, $settings->get( 'runtime.time_budget' ) );
		$this->assertSame( 3, $settings->get( 'schedule.keep' ) );
		$this->assertSame( 'monthly', $settings->get( 'schedule.interval' ) );
		$this->assertFalse( $settings->get( 'advanced.opt_in_news' ) );
		$this->assertSame( 'fallback', $settings->get( 'nope.missing', 'fallback' ) );
	}

	/**
	 * Values are merged over the defaults instead of replacing them.
	 *
	 * @return void
	 */
	public function test_update_merges_with_defaults(): void {
		$store    = new MemoryOptions();
		$settings = new Settings( $store );

		$settings->update(
			array(
				'storage'  => array( 'driver' => 'local' ),
				'advanced' => array( 'opt_in_news' => true ),
			)
		);

		$this->assertSame( 'local', $settings->get( 'storage.driver' ) );
				$this->assertTrue( $settings->get( 'advanced.opt_in_news' ) );
		$this->assertSame( 'local', $store->values[ Settings::OPTION ]['storage']['driver'] );
		$this->assertTrue( $store->values[ Settings::OPTION ]['advanced']['opt_in_news'] );
	}

	/**
	 * Out of range values are clamped and unknown drivers rejected.
	 *
	 * @return void
	 */
	public function test_sanitize_clamps_and_validates(): void {
		$clean = Settings::sanitize(
			array(
				'runtime'  => array( 'time_budget' => 9000, 'files_per_tick' => 1 ),
				'storage'  => array( 'driver' => 'ftp' ),
				'schedule' => array( 'keep' => 0, 'interval' => 'yearly' ),
				'archive'  => array( 'exclude_rules' => array( '  a  ', '', 'b' ) ),
				'advanced' => array( 'opt_in_news' => 1 ),
			)
		);

		$this->assertSame( 120, $clean['runtime']['time_budget'] );
		$this->assertSame( 10, $clean['runtime']['files_per_tick'] );
		$this->assertSame( 'local', $clean['storage']['driver'] );
		$this->assertSame( 1, $clean['schedule']['keep'] );
		$this->assertSame( 'monthly', $clean['schedule']['interval'] );
		$this->assertSame( array( 'a', 'b' ), $clean['archive']['exclude_rules'] );
		$this->assertTrue( $clean['advanced']['opt_in_news'] );
	}

	/**
	 * `set()` writes through to the store.
	 *
	 * @return void
	 */
	public function test_set_persists_single_value(): void {
		$store    = new MemoryOptions();
		$settings = new Settings( $store );

		$settings->set( 'archive.files', false );
		$settings->set( 'advanced.opt_in_news', true );

		$this->assertFalse( $settings->get( 'archive.files' ) );
		$this->assertTrue( $settings->get( 'advanced.opt_in_news' ) );
		$this->assertFalse( $store->values[ Settings::OPTION ]['archive']['files'] );
		$this->assertTrue( $store->values[ Settings::OPTION ]['advanced']['opt_in_news'] );
	}
}
