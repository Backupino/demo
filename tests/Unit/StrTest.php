<?php
/**
 * String helpers tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Support\Str;
use Backupino\Tests\TestCase;

/**
 * Tests for string and digit normalisation utilities.
 */
final class StrTest extends TestCase {

	/**
	 * Persian and Arabic digits are converted to ASCII Latin digits.
	 *
	 * @return void
	 */
	public function test_latin_digits_converts_persian_and_arabic(): void {
		$persian = '۰۱۲۳۴۵۶۷۸۹';
		$arabic  = '٠١٢٣٤٥٦٧٨٩';
		$mixed   = 'کد: ۱۴۰۴/۰۸/۰۹ - ساعت: ۱۲:۳۰ و رقم عربی: ٥٠٠';

		$this->assertSame( '0123456789', Str::latin_digits( $persian ) );
		$this->assertSame( '0123456789', Str::latin_digits( $arabic ) );
		$this->assertSame( 'کد: 1404/08/09 - ساعت: 12:30 و رقم عربی: 500', Str::latin_digits( $mixed ) );
		$this->assertSame( '123', Str::latin_digits( 123 ) );
	}

	/**
	 * Numbers are formatted with Latin digits and specified decimals.
	 *
	 * @return void
	 */
	public function test_number_formatting(): void {
		$this->assertSame( '1,234', Str::number( 1234 ) );
		$this->assertSame( '1,234.56', Str::number( 1234.56, 2 ) );
		$this->assertSame( '0', Str::number( 0 ) );
		$this->assertSame( '1200', Str::number( '۱۲۰۰' ) );
	}

	/**
	 * String utilities.
	 *
	 * @return void
	 */
	public function test_string_helpers(): void {
		$this->assertTrue( Str::starts_with( 'backupino_daily', 'backupino_' ) );
		$this->assertFalse( Str::starts_with( 'daily', 'backupino_' ) );
		$this->assertTrue( Str::ends_with( 'archive.zip', '.zip' ) );
		$this->assertFalse( Str::ends_with( 'archive.tar', '.zip' ) );
		$this->assertSame( '/path/to/dir/', Str::trailingslash( '/path/to/dir' ) );
		$this->assertSame( '/path/to/dir', Str::untrailingslash( '/path/to/dir/' ) );
		$this->assertSame( 'test', Str::limit( 'testing', 4 ) );
	}
}
