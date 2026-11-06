<?php
/**
 * Email and Mobile Verification Service.
 *
 * Implements S3-T03 verification workflows.
 *
 * @package Backupino\Portal\Auth
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Auth;

use Backupino\Portal\Audit\AuditLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Manages email and mobile verification tokens and workflows.
 */
final class VerificationService {

	public const META_EMAIL_VERIFIED  = '_backupino_email_verified';
	public const META_MOBILE_VERIFIED = '_backupino_mobile_verified';
	public const META_MOBILE_NUMBER   = '_backupino_mobile_number';

	private const TRANSIENT_PREFIX_EMAIL  = 'bk_v_email_';
	private const TRANSIENT_PREFIX_MOBILE = 'bk_v_mobile_';
	private const OTP_EXPIRY_SECONDS      = 600; // 10 minutes

	/**
	 * Normalize a mobile phone number to E.164 international format.
	 *
	 * Supports Iranian domestic formats (09xx, +989xx, 00989xx) and international numbers.
	 *
	 * @param string $raw_mobile Raw input.
	 * @return string Normalized mobile number or empty string if invalid.
	 */
	public static function normalize_mobile( string $raw_mobile ): string {
		$clean = preg_replace( '/[^\d+]/', '', trim( $raw_mobile ) );
		if ( empty( $clean ) ) {
			return '';
		}

		// Convert Persian / Arabic digits if any slipped through.
		$persian = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		$arabic  = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
		$latin   = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		$clean   = str_replace( $persian, $latin, $clean );
		$clean   = str_replace( $arabic, $latin, $clean );

		// Iranian mobile format normalization
		if ( preg_match( '/^0098(9\d{9})$/', $clean, $matches ) ) {
			return '+98' . $matches[1];
		}
		if ( preg_match( '/^0(9\d{9})$/', $clean, $matches ) ) {
			return '+98' . $matches[1];
		}
		if ( preg_match( '/^98(9\d{9})$/', $clean, $matches ) ) {
			return '+98' . $matches[1];
		}
		if ( preg_match( '/^\+98(9\d{9})$/', $clean, $matches ) ) {
			return '+98' . $matches[1];
		}

		// Standard international with leading +
		if ( 0 === strpos( $clean, '+' ) && strlen( $clean ) >= 8 && strlen( $clean ) <= 16 ) {
			return $clean;
		}

		// Fallback clean numbers with 00 converted to +
		if ( 0 === strpos( $clean, '00' ) ) {
			return '+' . substr( $clean, 2 );
		}

		return $clean;
	}

	/**
	 * Generate a cryptographically secure 6-digit numeric OTP.
	 */
	public static function generate_otp(): string {
		return (string) random_int( 100000, 999999 );
	}

	/**
	 * Send or generate email verification OTP for a user.
	 *
	 * @param int $user_id User ID.
	 * @return string Generated plain OTP (for sending via mailer or testing).
	 */
	public static function create_email_verification_otp( int $user_id ): string {
		$otp  = self::generate_otp();
		$hash = hash( 'sha256', $otp );

		$transient_key = self::TRANSIENT_PREFIX_EMAIL . $user_id;
		set_transient(
			$transient_key,
			array(
				'hash'     => $hash,
				'attempts' => 0,
			),
			self::OTP_EXPIRY_SECONDS
		);

		AuditLogger::log(
			'email_verification_otp_generated',
			$user_id,
			0,
			null,
			array( 'expires_in' => self::OTP_EXPIRY_SECONDS )
		);

		return $otp;
	}

	/**
	 * Verify an email OTP.
	 *
	 * @param int    $user_id User ID.
	 * @param string $otp     Submitted 6-digit code.
	 * @return bool True if valid and verified.
	 */
	public static function verify_email_otp( int $user_id, string $otp ): bool {
		$transient_key = self::TRANSIENT_PREFIX_EMAIL . $user_id;
		$data          = get_transient( $transient_key );

		if ( ! is_array( $data ) || empty( $data['hash'] ) ) {
			return false;
		}

		// Rate limit attempts (max 5)
		$attempts = isset( $data['attempts'] ) ? (int) $data['attempts'] + 1 : 1;
		if ( $attempts > 5 ) {
			delete_transient( $transient_key );
			return false;
		}

		$expected_hash = $data['hash'];
		$input_hash    = hash( 'sha256', trim( $otp ) );

		if ( hash_equals( $expected_hash, $input_hash ) ) {
			delete_transient( $transient_key );
			update_user_meta( $user_id, self::META_EMAIL_VERIFIED, current_time( 'mysql', true ) );

			AuditLogger::log(
				'email_verified_successfully',
				$user_id,
				0,
				null,
				array()
			);

			return true;
		}

		// Update attempts
		$data['attempts'] = $attempts;
		set_transient( $transient_key, $data, self::OTP_EXPIRY_SECONDS );

		return false;
	}

	/**
	 * Send or generate mobile verification OTP for a user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $mobile  Mobile number.
	 * @return string Generated plain OTP.
	 */
	public static function create_mobile_verification_otp( int $user_id, string $mobile ): string {
		$normalized = self::normalize_mobile( $mobile );
		$otp        = self::generate_otp();
		$hash       = hash( 'sha256', $otp );

		$transient_key = self::TRANSIENT_PREFIX_MOBILE . $user_id;
		set_transient(
			$transient_key,
			array(
				'hash'     => $hash,
				'mobile'   => $normalized,
				'attempts' => 0,
			),
			self::OTP_EXPIRY_SECONDS
		);

		AuditLogger::log(
			'mobile_verification_otp_generated',
			$user_id,
			0,
			null,
			array( 'mobile' => $normalized )
		);

		return $otp;
	}

	/**
	 * Verify a mobile OTP.
	 *
	 * @param int    $user_id User ID.
	 * @param string $otp     Submitted 6-digit code.
	 * @return bool True if valid.
	 */
	public static function verify_mobile_otp( int $user_id, string $otp ): bool {
		$transient_key = self::TRANSIENT_PREFIX_MOBILE . $user_id;
		$data          = get_transient( $transient_key );

		if ( ! is_array( $data ) || empty( $data['hash'] ) ) {
			return false;
		}

		$attempts = isset( $data['attempts'] ) ? (int) $data['attempts'] + 1 : 1;
		if ( $attempts > 5 ) {
			delete_transient( $transient_key );
			return false;
		}

		$expected_hash = $data['hash'];
		$input_hash    = hash( 'sha256', trim( $otp ) );

		if ( hash_equals( $expected_hash, $input_hash ) ) {
			$mobile = $data['mobile'];
			delete_transient( $transient_key );

			update_user_meta( $user_id, self::META_MOBILE_NUMBER, $mobile );
			update_user_meta( $user_id, self::META_MOBILE_VERIFIED, current_time( 'mysql', true ) );

			AuditLogger::log(
				'mobile_verified_successfully',
				$user_id,
				0,
				null,
				array( 'mobile' => $mobile )
			);

			return true;
		}

		$data['attempts'] = $attempts;
		set_transient( $transient_key, $data, self::OTP_EXPIRY_SECONDS );

		return false;
	}

	/**
	 * Check if email is verified for user.
	 */
	public static function is_email_verified( int $user_id ): bool {
		return (bool) get_user_meta( $user_id, self::META_EMAIL_VERIFIED, true );
	}

	/**
	 * Check if mobile is verified for user.
	 */
	public static function is_mobile_verified( int $user_id ): bool {
		return (bool) get_user_meta( $user_id, self::META_MOBILE_VERIFIED, true );
	}
}
