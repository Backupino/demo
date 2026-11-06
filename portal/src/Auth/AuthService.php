<?php
/**
 * Customer Authentication and Registration Service.
 *
 * Implements S3-T03 account lifecycle workflows.
 *
 * @package Backupino\Portal\Auth
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Auth;

use Backupino\Portal\Audit\AuditLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Handles registration, authentication, 2FA gating, and password recovery.
 */
final class AuthService {

	/**
	 * Register a new customer.
	 *
	 * @param string      $email    Customer email.
	 * @param string      $password Plain password.
	 * @param string|null $mobile   Optional mobile number.
	 * @param array       $extra    Optional name / profile details.
	 * @return int|\WP_Error Created User ID or WP_Error.
	 */
	public static function register_customer( string $email, string $password, ?string $mobile = null, array $extra = array() ) {
		$email = sanitize_email( trim( $email ) );

		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', __( 'The email address is not valid.', 'backupino-portal' ) );
		}

		if ( email_exists( $email ) ) {
			return new \WP_Error( 'email_exists', __( 'An account with this email already exists.', 'backupino-portal' ) );
		}

		// Enforce strict password strength (min 10 chars, mixed case, numbers, special)
		$strength_error = self::validate_password_strength( $password );
		if ( null !== $strength_error ) {
			return new \WP_Error( 'weak_password', $strength_error );
		}

		$username = sanitize_user( current( explode( '@', $email ) ) );
		if ( username_exists( $username ) ) {
			$username .= '_' . wp_rand( 100, 999 );
		}

		$userdata = array(
			'user_login' => $username,
			'user_email' => $email,
			'user_pass'  => $password,
			'role'       => RoleManager::ROLE_CUSTOMER,
			'first_name' => isset( $extra['first_name'] ) ? sanitize_text_field( $extra['first_name'] ) : '',
			'last_name'  => isset( $extra['last_name'] ) ? sanitize_text_field( $extra['last_name'] ) : '',
		);

		$user_id = wp_insert_user( $userdata );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Save mobile number if provided
		if ( ! empty( $mobile ) ) {
			$normalized_mobile = VerificationService::normalize_mobile( $mobile );
			if ( ! empty( $normalized_mobile ) ) {
				update_user_meta( $user_id, VerificationService::META_MOBILE_NUMBER, $normalized_mobile );
			}
		}

		// Create email verification OTP
		VerificationService::create_email_verification_otp( $user_id );

		AuditLogger::log(
			'customer_registered',
			$user_id,
			0,
			null,
			array( 'email' => $email, 'role' => RoleManager::ROLE_CUSTOMER )
		);

		return $user_id;
	}

	/**
	 * Authenticate user credentials with mandatory 2FA check.
	 *
	 * @param string      $username_or_email Login username or email.
	 * @param string      $password          Plain password.
	 * @param string|null $two_factor_code   6-digit TOTP or backup recovery code.
	 * @return \WP_User|\WP_Error Authenticated user or WP_Error.
	 */
	public static function authenticate( string $username_or_email, string $password, ?string $two_factor_code = null ) {
		$user = wp_authenticate( $username_or_email, $password );

		if ( is_wp_error( $user ) ) {
			AuditLogger::log(
				'login_failed_bad_credentials',
				0,
				0,
				null,
				array( 'identity' => sanitize_text_field( $username_or_email ) )
			);
			return $user;
		}

		$user_id = (int) $user->ID;

		// Check if 2FA is active for this account
		if ( TwoFactorAuth::is_2fa_enabled( $user_id ) ) {
			if ( empty( $two_factor_code ) ) {
				return new \WP_Error(
					'2fa_required',
					__( 'Two-factor authentication code is required.', 'backupino-portal' ),
					array( 'user_id' => $user_id )
				);
			}

			if ( ! TwoFactorAuth::verify_user_2fa( $user_id, $two_factor_code ) ) {
				AuditLogger::log(
					'login_failed_bad_2fa',
					$user_id,
					0,
					null,
					array()
				);
				return new \WP_Error( 'invalid_2fa', __( 'Invalid two-factor code or recovery token.', 'backupino-portal' ) );
			}
		}

		AuditLogger::log(
			'login_success',
			$user_id,
			0,
			null,
			array( '2fa_used' => TwoFactorAuth::is_2fa_enabled( $user_id ) )
		);

		return $user;
	}

	/**
	 * Initiate password reset request.
	 *
	 * @param string $email Customer email address.
	 * @return string|\WP_Error Reset key or WP_Error.
	 */
	public static function request_password_reset( string $email ) {
		$user = get_user_by( 'email', trim( $email ) );
		if ( ! $user ) {
			return new \WP_Error( 'user_not_found', __( 'No account found with this email.', 'backupino-portal' ) );
		}

		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			return $key;
		}

		AuditLogger::log(
			'password_reset_requested',
			(int) $user->ID,
			0,
			null,
			array()
		);

		return $key;
	}

	/**
	 * Reset password using validated reset key.
	 *
	 * @param string $username_or_email User identifier.
	 * @param string $key               Reset key.
	 * @param string $new_password      New password.
	 * @return bool|\WP_Error
	 */
	public static function reset_password_with_token( string $username_or_email, string $key, string $new_password ) {
		$user = check_password_reset_key( $key, $username_or_email );
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$strength_error = self::validate_password_strength( $new_password );
		if ( null !== $strength_error ) {
			return new \WP_Error( 'weak_password', $strength_error );
		}

		reset_password( $user, $new_password );

		AuditLogger::log(
			'password_reset_completed',
			(int) $user->ID,
			0,
			null,
			array()
		);

		return true;
	}

	/**
	 * Enforce enterprise password strength standards.
	 *
	 * @param string $password
	 * @return string|null Error message or null if strong.
	 */
	public static function validate_password_strength( string $password ): ?string {
		if ( strlen( $password ) < 10 ) {
			return __( 'Password must be at least 10 characters long.', 'backupino-portal' );
		}

		if ( ! preg_match( '/[A-Z]/', $password ) ) {
			return __( 'Password must contain at least one uppercase letter.', 'backupino-portal' );
		}

		if ( ! preg_match( '/[a-z]/', $password ) ) {
			return __( 'Password must contain at least one lowercase letter.', 'backupino-portal' );
		}

		if ( ! preg_match( '/[0-9]/', $password ) ) {
			return __( 'Password must contain at least one number.', 'backupino-portal' );
		}

		if ( ! preg_match( '/[^A-Za-z0-9]/', $password ) ) {
			return __( 'Password must contain at least one special character.', 'backupino-portal' );
		}

		return null;
	}
}
