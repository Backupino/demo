<?php
/**
 * AWS Signature Version 4 signer.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Storage\S3;

/**
 * Minimal, dependency free implementation of AWS SigV4 for S3 requests.
 *
 * Pulling the AWS SDK into a WordPress plugin would add ~40MB and a Composer
 * dependency to shared hosting; the algorithm itself is small and fully
 * specified, so it is implemented here and covered by unit tests.
 */
final class S3Signer {

	/**
	 * Sign a request.
	 *
	 * @param array<string, mixed> $request {
	 *     @type string   $method         HTTP method.
	 *     @type string   $uri            Canonical path, e.g. `/bucket/key`.
	 *     @type array    $query          Query parameters.
	 *     @type array    $headers        Request headers (name => value).
	 *     @type string   $payload_hash   Hex SHA-256 of the body or `UNSIGNED-PAYLOAD`.
	 *     @type string   $region         Region.
	 *     @type string   $service        Service name, `s3`.
	 *     @type string   $access_key     Access key id.
	 *     @type string   $secret_key     Secret key.
	 *     @type string   $amz_date       `Ymd\THis\Z` timestamp.
	 * }
	 * @return array<string, string> Headers that must be sent with the request.
	 */
	public static function sign( array $request ): array {
		$method       = strtoupper( (string) $request['method'] );
		$uri          = (string) $request['uri'];
		$query        = isset( $request['query'] ) && is_array( $request['query'] ) ? $request['query'] : array();
		$headers      = isset( $request['headers'] ) && is_array( $request['headers'] ) ? $request['headers'] : array();
		$payload_hash = (string) $request['payload_hash'];
		$region       = (string) $request['region'];
		$service      = (string) $request['service'];
		$access_key   = (string) $request['access_key'];
		$secret_key   = (string) $request['secret_key'];
		$amz_date     = (string) $request['amz_date'];

		$headers['x-amz-date']           = $amz_date;
		$headers['x-amz-content-sha256'] = $payload_hash;

		$canonical_headers = self::canonical_headers( $headers );
		$signed_headers    = implode( ';', array_keys( $canonical_headers ) );

		$canonical_request = implode(
			"\n",
			array(
				$method,
				self::canonical_uri( $uri ),
				self::canonical_query( $query ),
				self::render_headers( $canonical_headers ),
				$signed_headers,
				$payload_hash,
			)
		);

		$date         = substr( $amz_date, 0, 8 );
		$scope        = $date . '/' . $region . '/' . $service . '/aws4_request';
		$string_to_sign = implode(
			"\n",
			array(
				'AWS4-HMAC-SHA256',
				$amz_date,
				$scope,
				hash( 'sha256', $canonical_request ),
			)
		);

		$signature = self::signature( $string_to_sign, $date, $region, $service, $secret_key );

		$headers['Authorization'] = sprintf(
			'AWS4-HMAC-SHA256 Credential=%s/%s,SignedHeaders=%s,Signature=%s',
			$access_key,
			$scope,
			$signed_headers,
			$signature
		);

		unset( $headers['Host'] );

		return $headers;
	}

	/**
	 * Canonical request for debugging and tests.
	 *
	 * @param array<string, mixed> $request Same as {@see sign()}.
	 * @return string
	 */
	public static function canonical_request( array $request ): string {
		$headers      = isset( $request['headers'] ) && is_array( $request['headers'] ) ? $request['headers'] : array();
		$payload_hash = (string) $request['payload_hash'];

		$headers['x-amz-date']           = (string) $request['amz_date'];
		$headers['x-amz-content-sha256'] = $payload_hash;

		$canonical_headers = self::canonical_headers( $headers );

		return implode(
			"\n",
			array(
				strtoupper( (string) $request['method'] ),
				self::canonical_uri( (string) $request['uri'] ),
				self::canonical_query( isset( $request['query'] ) && is_array( $request['query'] ) ? $request['query'] : array() ),
				self::render_headers( $canonical_headers ),
				implode( ';', array_keys( $canonical_headers ) ),
				$payload_hash,
			)
		);
	}

	/**
	 * Derive the SigV4 signing key.
	 *
	 * @param string $secret_key Secret.
	 * @param string $date       `Ymd`.
	 * @param string $region     Region.
	 * @param string $service    Service.
	 * @return string Binary key.
	 */
	public static function signing_key( string $secret_key, string $date, string $region, string $service ): string {
		$key = hash_hmac( 'sha256', $date, 'AWS4' . $secret_key, true );
		$key = hash_hmac( 'sha256', $region, $key, true );
		$key = hash_hmac( 'sha256', $service, $key, true );

		return hash_hmac( 'sha256', 'aws4_request', $key, true );
	}

	/**
	 * Compute the request signature.
	 *
	 * @param string $string_to_sign String to sign.
	 * @param string $date           `Ymd`.
	 * @param string $region         Region.
	 * @param string $service        Service.
	 * @param string $secret_key     Secret.
	 * @return string Hex signature.
	 */
	public static function signature( string $string_to_sign, string $date, string $region, string $service, string $secret_key ): string {
		return hash_hmac( 'sha256', $string_to_sign, self::signing_key( $secret_key, $date, $region, $service ) );
	}

	/**
	 * Normalize header names/values and sort them.
	 *
	 * @param array<string, string> $headers Headers.
	 * @return array<string, string>
	 */
	private static function canonical_headers( array $headers ): array {
		$normalized = array();

		foreach ( $headers as $name => $value ) {
			$name  = strtolower( trim( (string) $name ) );
			$value = (string) preg_replace( '/\s+/', ' ', trim( (string) $value ) );

			if ( 'authorization' === $name ) {
				continue;
			}

			$normalized[ $name ] = $value;
		}

		ksort( $normalized, SORT_STRING );

		return $normalized;
	}

	/**
	 * Render canonical headers with their trailing newlines.
	 *
	 * @param array<string, string> $headers Sorted canonical headers.
	 * @return string
	 */
	private static function render_headers( array $headers ): string {
		$out = '';

		foreach ( $headers as $name => $value ) {
			$out .= $name . ':' . $value . "\n";
		}

		return $out;
	}

	/**
	 * URI-encode a path, preserving slashes.
	 *
	 * @param string $uri Path.
	 * @return string
	 */
	private static function canonical_uri( string $uri ): string {
		if ( '' === $uri ) {
			return '/';
		}

		$segments = explode( '/', $uri );
		$encoded  = array_map(
			static function ( $segment ) {
				return str_replace( '%7E', '~', rawurlencode( $segment ) );
			},
			$segments
		);

		return implode( '/', $encoded );
	}

	/**
	 * Build the canonical query string.
	 *
	 * @param array<string, mixed> $query Query parameters.
	 * @return string
	 */
	private static function canonical_query( array $query ): string {
		if ( array() === $query ) {
			return '';
		}

		ksort( $query, SORT_STRING );

		$pairs = array();

		foreach ( $query as $key => $value ) {
			$pairs[] = rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value );
		}

		return implode( '&', $pairs );
	}
}
