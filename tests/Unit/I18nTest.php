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
 * Tests covering translation template and digit rules.
 */
final class I18nTest extends TestCase {

	/**
	 * Translation template (.pot) exists and has non-empty contents.
	 *
	 * @return void
	 */
	public function test_catalog_files_exist_and_are_valid(): void {
		$pot = BACKUPINO_DIR . 'languages/backupino.pot';

		$this->assertTrue( is_file( $pot ), 'backupino.pot must exist' );

		$content = (string) file_get_contents( $pot );
		$this->assertGreaterThan( 100, strlen( $content ) );
		$this->assertStringContains( 'Project-Id-Version: Backupino', $content );
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
		$this->assertSame( 1, preg_match( '/^[0-9\/\s:]+$/', $formatted ) );

		// Verify byte values are in ASCII range (0x30 - 0x39)
		$chars = preg_split( '//u', $formatted, -1, PREG_SPLIT_NO_EMPTY );
		$this->assertTrue( is_array( $chars ) );
		foreach ( (array) $chars as $ch ) {
			if ( is_numeric( $ch ) ) {
				$ascii_val = ord( $ch );
				$this->assertTrue( $ascii_val >= 0x30 && $ascii_val <= 0x39 );
			}
		}
	}
}
