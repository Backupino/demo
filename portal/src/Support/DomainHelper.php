<?php
/**
 * Domain normalization and staging detection helper.
 *
 * Implements D-051 and rules defined in STAGING-WHITELIST-RULES.md.
 *
 * @package Backupino\Portal\Support
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Domain and Staging verification helper.
 */
final class DomainHelper {

	/**
	 * Normalize a raw domain or URL to a clean lowercase hostname without port or scheme.
	 *
	 * @param string $raw_url Raw URL or host (e.g. "https://Staging.MySite.com:8080/wp/").
	 * @return string Clean lowercase host (e.g. "staging.mysite.com").
	 */
	public static function normalize( string $raw_url ): string {
		$url = trim( strtolower( $raw_url ) );

		if ( '' === $url ) {
			return '';
		}

		if ( ! preg_match( '#^https?://#i', $url ) ) {
			$url = 'https://' . $url;
		}

		$host = (string) parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			$host = trim( $raw_url );
		}

		// Remove port if present.
		$host = (string) preg_replace( '/:\d+$/', '', $host );

		return strtolower( trim( $host, " \t\n\r\0\x0B./" ) );
	}

	/**
	 * Generate standard SHA-256 hash of normalized domain.
	 *
	 * @param string $domain Raw or normalized domain.
	 * @return string 64-char hex string.
	 */
	public static function hash( string $domain ): string {
		$normalized = self::normalize( $domain );
		return hash( 'sha256', $normalized );
	}

	/**
	 * Determine if a domain is a valid development or staging environment (D-051).
	 *
	 * Staging domains do NOT deduct from the license domain quota.
	 *
	 * @param string $domain Normalized or raw domain.
	 * @return bool True if staging/local whitelist match, false if production.
	 */
	public static function is_staging( string $domain ): bool {
		$domain = self::normalize( $domain );

		if ( '' === $domain ) {
			return false;
		}

		// 1. Loopback and localhost addresses.
		if ( in_array( $domain, array( 'localhost', '127.0.0.1', '::1', '[::1]' ), true ) ) {
			return true;
		}

		// 2. Development TLDs (RFC 2606 / RFC 6761).
		if ( preg_match( '/\.(test|example|invalid|localhost|local|internal|lan)$/i', $domain ) ) {
			return true;
		}

		// 3. Staging and development subdomains (minimum 2 dots to ensure subdomain).
		if ( preg_match( '/^(staging|dev|test|stage|beta|qa|uat)\.[a-z0-9\-]+(\.[a-z0-9\-]+)+$/i', $domain ) ) {
			return true;
		}

		// 4. Cloud hosting staging and temporary tunnels.
		if ( preg_match( '/\.(wpenginepowered\.com|kinsta\.cloud|pantheonsite\.io|flywheelsites\.com|cloudwaysapps\.com|myftpupload\.com|ngrok\.io|ngrok-free\.app)$/i', $domain ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get soft limit for concurrent active staging domains per plan.
	 *
	 * @param string $plan_slug Plan identifier.
	 * @return int Maximum allowed active staging domains.
	 */
	public static function staging_soft_cap( string $plan_slug ): int {
		switch ( $plan_slug ) {
			case 'freelancer':
				return 20;
			case 'agency':
			case 'enterprise':
				return 60;
			case 'trial':
			case 'personal':
			default:
				return 3;
		}
	}
}
