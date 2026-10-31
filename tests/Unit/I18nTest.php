<?php
/**
 * i18n and localization tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Plugin;
use Backupino\Support\Dates;
use Backupino\Support\Str;
use Backupino\Tests\TestCase;

/**
 * Tests covering translation loading, language files, and digit rules.
 */
final class I18nTest extends TestCase {

	/**
	 * Plugin textdomain is registered with the correct relative path.
	 *
	 * @return void
	 */
	public function test_textdomain_is_loaded(): void {
		$plugin = Plugin::instance();
		$plugin->load_textdomain();

		$expected = dirname( plugin_basename( BACKUPINO_FILE ) ) . '/languages';

		$this->assertSame(
			$expected,
			$GLOBALS['backupino_textdomains']['backupino'] ?? null
		);
	}

	/**
	 * Translation catalogs exist and .mo file has the standard GNU gettext magic number.
	 *
	 * @return void
	 */
	public function test_catalog_files_exist_and_are_valid(): void {
		$pot = BACKUPINO_DIR . 'languages/backupino.pot';
		$po  = BACKUPINO_DIR . 'languages/backupino-fa_IR.po';
		$mo  = BACKUPINO_DIR . 'languages/backupino-fa_IR.mo';

		$this->assertTrue( is_file( $pot ), 'backupino.pot must exist' );
		$this->assertTrue( is_file( $po ), 'backupino-fa_IR.po must exist' );
		$this->assertTrue( is_file( $mo ), 'backupino-fa_IR.mo must exist' );

		$content = (string) file_get_contents( $mo );
		$this->assertGreaterThan( 100, strlen( $content ) );

		// Check GNU MO magic: 0x950412de (little endian: \xde\x12\x04\x95)
		$header = unpack( 'Vmagic', substr( $content, 0, 4 ) );
		$this->assertSame( 0x950412de, $header['magic'] ?? 0 );
	}

	/**
	 * All dates and numbers formatted in Persian locale use ASCII Latin digits.
	 *
	 * @return void
	 */
	public function test_persian_formatted_outputs_use_only_latin_digits(): void {
		$ts = 1793444400; // 2026-10-31 11:00 UTC -> 1405/08/09
		$formatted = Dates::format( $ts, 'fa_IR' );

		// Match that digits are strictly 0-9 and no Persian/Arabic digits appear
		$this->assertTrue( (bool) preg_match( '/^[0-9\/ :]+$/', $formatted ) );
		$this->assertFalse( (bool) preg_match( '/[۰-۹٠-٩]/u', $formatted ) );

		$number = Str::number( '۹۸۷۶۵۴۳۲۱۰' );
		$this->assertSame( '9876543210', $number );
	}
}
