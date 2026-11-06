<?php
/**
 * Date Formatter for Jalali and Gregorian Dates.
 *
 * 100% self-contained pure PHP implementation for Portal notifications,
 * dashboards, and invoices without external library dependency (D-038).
 *
 * @package Backupino\Portal\Support
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Handles Gregorian to Jalali conversion and localized formatting with Latin digits.
 */
final class DateFormatter {

	/**
	 * Jalali month names in Persian.
	 */
	public const JALALI_MONTHS_FA = array(
		1  => 'فروردین',
		2  => 'اردیبهشت',
		3  => 'خرداد',
		4  => 'تیر',
		5  => 'مرداد',
		6  => 'شهریور',
		7  => 'مهر',
		8  => 'آبان',
		9  => 'آذر',
		10 => 'دی',
		11 => 'بهمن',
		12 => 'اسفند',
	);

	/**
	 * Check if a Jalali year is a leap year.
	 *
	 * @param int $jy Jalali year.
	 * @return bool
	 */
	public static function is_jalali_leap( int $jy ): bool {
		list( $gy, $gm, $gd )                   = self::jalali_to_gregorian( $jy, 12, 30 );
		list( $check_jy, $check_jm, $check_jd ) = self::gregorian_to_jalali( $gy, $gm, $gd );
		return ( 30 === $check_jd && 12 === $check_jm );
	}

	/**
	 * Convert Gregorian year/month/day to Jalali year/month/day.
	 *
	 * @param int $gy Gregorian year.
	 * @param int $gm Gregorian month (1-12).
	 * @param int $gd Gregorian day (1-31).
	 * @return array{0: int, 1: int, 2: int} Array of [jy, jm, jd].
	 */
	public static function gregorian_to_jalali( int $gy, int $gm, int $gd ): array {
		$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
		$days  = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 ) + intdiv( $gy2 + 399, 400 ) + $gd + $g_d_m[ $gm - 1 ];
		$jy    = -1595 + ( 33 * intdiv( $days, 12053 ) );
		$days %= 12053;
		$jy   += 4 * intdiv( $days, 1461 );
		$days %= 1461;

		if ( $days > 365 ) {
			$jy   += intdiv( $days - 1, 365 );
			$days  = ( $days - 1 ) % 365;
		}

		if ( $days < 186 ) {
			$jm = 1 + intdiv( $days, 31 );
			$jd = 1 + ( $days % 31 );
		} else {
			$jm = 7 + intdiv( $days - 186, 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}

		return array( (int) $jy, (int) $jm, (int) $jd );
	}

	/**
	 * Convert Jalali year/month/day to Gregorian year/month/day.
	 *
	 * @param int $jy Jalali year.
	 * @param int $jm Jalali month (1-12).
	 * @param int $jd Jalali day (1-31).
	 * @return array{0: int, 1: int, 2: int} Array of [gy, gm, gd].
	 */
	public static function jalali_to_gregorian( int $jy, int $jm, int $jd ): array {
		$jy   += 1595;
		$days  = -355668 + ( 365 * $jy ) + intdiv( $jy, 33 ) * 8 + intdiv( ( $jy % 33 ) + 3, 4 ) + $jd;

		if ( $jm < 7 ) {
			$days += ( $jm - 1 ) * 31;
		} else {
			$days += ( ( $jm - 7 ) * 30 ) + 186;
		}

		$gy    = 400 * intdiv( $days, 146097 );
		$days %= 146097;

		if ( $days > 36524 ) {
			$gy   += 100 * intdiv( --$days, 36524 );
			$days %= 36524;
			if ( $days >= 365 ) {
				$days++;
			}
		}

		$gy   += 4 * intdiv( $days, 1461 );
		$days %= 1461;

		if ( $days > 365 ) {
			$gy   += intdiv( $days - 1, 365 );
			$days  = ( $days - 1 ) % 365;
		}

		$gd = $days + 1;
		$sal_a = array( 0, 31, ( ( ( $gy % 4 === 0 && $gy % 100 !== 0 ) || ( $gy % 400 === 0 ) ) ? 29 : 28 ), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
		for ( $gm = 0; $gm < 13 && $gd > $sal_a[ $gm ]; $gm++ ) {
			$gd -= $sal_a[ $gm ];
		}

		return array( (int) $gy, (int) $gm, (int) $gd );
	}

	/**
	 * Format a timestamp or date string to Jalali with Latin digits (e.g. 1405/08/15 14:30:00).
	 *
	 * @param int|string $time Timestamp or MySQL datetime string.
	 * @param bool       $include_time Whether to append H:i:s.
	 * @return string
	 */
	public static function format_jalali( $time, bool $include_time = true ): string {
		$timestamp = is_numeric( $time ) ? (int) $time : strtotime( (string) $time );
		if ( ! $timestamp || $timestamp <= 0 ) {
			return '-';
		}

		$gy = (int) gmdate( 'Y', $timestamp );
		$gm = (int) gmdate( 'n', $timestamp );
		$gd = (int) gmdate( 'j', $timestamp );

		list( $jy, $jm, $jd ) = self::gregorian_to_jalali( $gy, $gm, $gd );

		$formatted = sprintf( '%04d/%02d/%02d', $jy, $jm, $jd );

		if ( $include_time ) {
			$formatted .= ' ' . gmdate( 'H:i:s', $timestamp );
		}

		return $formatted;
	}

	/**
	 * Format a timestamp or date string to Gregorian (e.g. 2026-11-06 14:30:00).
	 *
	 * @param int|string $time Timestamp or MySQL datetime string.
	 * @param bool       $include_time Whether to append H:i:s.
	 * @return string
	 */
	public static function format_gregorian( $time, bool $include_time = true ): string {
		$timestamp = is_numeric( $time ) ? (int) $time : strtotime( (string) $time );
		if ( ! $timestamp || $timestamp <= 0 ) {
			return '-';
		}

		return gmdate( $include_time ? 'Y-m-d H:i:s' : 'Y-m-d', $timestamp );
	}

	/**
	 * Format date localized according to locale string (fa_IR or en_US).
	 *
	 * @param int|string $time Timestamp or MySQL datetime.
	 * @param string     $locale Locale code (e.g. 'fa', 'fa_IR', 'en', 'en_US').
	 * @param bool       $include_time Whether to append H:i:s.
	 * @return string
	 */
	public static function format_localized( $time, string $locale = 'fa', bool $include_time = true ): string {
		if ( 0 === strpos( $locale, 'fa' ) ) {
			return self::format_jalali( $time, $include_time );
		}
		return self::format_gregorian( $time, $include_time );
	}
}
