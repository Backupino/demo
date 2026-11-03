<?php
/**
 * Jalali and Gregorian date handling tests.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Unit;

use Backupino\Support\Dates;
use Backupino\Tests\TestCase;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Tests for Dates class.
 */
final class DatesTest extends TestCase {

	/**
	 * Test two-way conversion for known dates.
	 *
	 * @return void
	 */
	public function test_two_way_conversion(): void {
		// 2026-03-21 is 1405-01-01 (Nowruz).
		list( $jy, $jm, $jd ) = Dates::gregorian_to_jalali( 2026, 3, 21 );
		$this->assertSame( 1405, $jy );
		$this->assertSame( 1, $jm );
		$this->assertSame( 1, $jd );

		list( $gy, $gm, $gd ) = Dates::jalali_to_gregorian( 1405, 1, 1 );
		$this->assertSame( 2026, $gy );
		$this->assertSame( 3, $gm );
		$this->assertSame( 21, $gd );

		// 2026-10-31 is 1405-08-09.
		list( $jy2, $jm2, $jd2 ) = Dates::gregorian_to_jalali( 2026, 10, 31 );
		$this->assertSame( 1405, $jy2 );
		$this->assertSame( 8, $jm2 );
		$this->assertSame( 9, $jd2 );

		list( $gy2, $gm2, $gd2 ) = Dates::jalali_to_gregorian( 1405, 8, 9 );
		$this->assertSame( 2026, $gy2 );
		$this->assertSame( 10, $gm2 );
		$this->assertSame( 31, $gd2 );
	}

	/**
	 * Test Jalali leap year detection.
	 *
	 * @return void
	 */
	public function test_leap_years(): void {
		$this->assertTrue( Dates::is_jalali_leap( 1399 ) );
		$this->assertTrue( Dates::is_jalali_leap( 1403 ) );
		$this->assertFalse( Dates::is_jalali_leap( 1404 ) );
		$this->assertFalse( Dates::is_jalali_leap( 1405 ) );
		$this->assertTrue( Dates::is_jalali_leap( 1408 ) );
	}

	/**
	 * Test Jalali month days.
	 *
	 * @return void
	 */
	public function test_days_in_month(): void {
		$this->assertSame( 31, Dates::jalali_days_in_month( 1404, 1 ) );
		$this->assertSame( 31, Dates::jalali_days_in_month( 1404, 6 ) );
		$this->assertSame( 30, Dates::jalali_days_in_month( 1404, 7 ) );
		$this->assertSame( 30, Dates::jalali_days_in_month( 1404, 11 ) );
		$this->assertSame( 29, Dates::jalali_days_in_month( 1404, 12 ) );
		$this->assertSame( 30, Dates::jalali_days_in_month( 1403, 12 ) ); // Leap year
	}

	/**
	 * Test format in Persian locale produces Jalali with Latin digits.
	 *
	 * @return void
	 */
	public function test_format_persian(): void {
		$tz = new DateTimeZone( 'UTC' );
		$dt = new DateTimeImmutable( '2026-10-31 14:30:00', $tz );
		$ts = $dt->getTimestamp();

		$formatted = Dates::format( $ts, 'fa_IR', $tz );

		$this->assertSame( '1405/08/09 14:30', $formatted );
		// Assert Latin digits only (rule 8 in development rules).
		$this->assertSame( 1, preg_match( '/^[0-9\s\/:.,-]+$/', $formatted ) );
	}

	/**
	 * Test format in English locale produces Gregorian with Latin digits.
	 *
	 * @return void
	 */
	public function test_format_english(): void {
		$tz = new DateTimeZone( 'UTC' );
		$dt = new DateTimeImmutable( '2026-10-31 14:30:00', $tz );
		$ts = $dt->getTimestamp();

		$formatted = Dates::format( $ts, 'en_US', $tz );

		$this->assertSame( '2026-10-31 14:30', $formatted );
		$this->assertSame( 1, preg_match( '/^[0-9\s\/:.,-]+$/', $formatted ) );
	}

	/**
	 * Test calculating the start of the next month in Persian calendar.
	 *
	 * @return void
	 */
	public function test_next_month_start_persian(): void {
		$tz = new DateTimeZone( 'UTC' );

		// 2026-10-31 is 1405-08-10. Next month is 1405-09-01 (1st of Azar).
		// 1405-09-01 in Gregorian is 2026-11-22.
		$dt = new DateTimeImmutable( '2026-10-31 12:00:00', $tz );
		$next_start = Dates::next_month_start( $dt->getTimestamp(), 'fa_IR', $tz );

		$next_dt = ( new DateTimeImmutable( '@' . $next_start ) )->setTimezone( $tz );
		$this->assertSame( '2026-11-22 00:00', $next_dt->format( 'Y-m-d H:i' ) );

		$formatted = Dates::format( $next_start, 'fa_IR', $tz );
		$this->assertSame( '1405/09/01 00:00', $formatted );

		// Year transition: 1405-12-15 -> 1406-01-01 (2027-03-21)
		list( $gy, $gm, $gd ) = Dates::jalali_to_gregorian( 1405, 12, 15 );
		$esfand_dt = new DateTimeImmutable( sprintf( '%04d-%02d-%02d 10:00:00', $gy, $gm, $gd ), $tz );
		$next_year_start = Dates::next_month_start( $esfand_dt->getTimestamp(), 'fa_IR', $tz );

		$next_year_formatted = Dates::format( $next_year_start, 'fa_IR', $tz );
		$this->assertSame( '1406/01/01 00:00', $next_year_formatted );
	}

	/**
	 * Test calculating the start of the next month in Gregorian calendar.
	 *
	 * @return void
	 */
	public function test_next_month_start_gregorian(): void {
		$tz = new DateTimeZone( 'UTC' );

		$dt = new DateTimeImmutable( '2026-10-15 12:00:00', $tz );
		$next_start = Dates::next_month_start( $dt->getTimestamp(), 'en_US', $tz );

		$next_dt = ( new DateTimeImmutable( '@' . $next_start ) )->setTimezone( $tz );
		$this->assertSame( '2026-11-01 00:00', $next_dt->format( 'Y-m-d H:i' ) );

		// December -> January next year
		$dec_dt = new DateTimeImmutable( '2026-12-25 10:00:00', $tz );
		$next_year = Dates::next_month_start( $dec_dt->getTimestamp(), 'en_US', $tz );

		$next_year_dt = ( new DateTimeImmutable( '@' . $next_year ) )->setTimezone( $tz );
		$this->assertSame( '2027-01-01 00:00', $next_year_dt->format( 'Y-m-d H:i' ) );
	}
}
