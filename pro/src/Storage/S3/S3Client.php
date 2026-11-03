<?php
/**
 * S3 HTTP client.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Storage\S3;

/**
 * Tiny S3 client built on cURL (with the WordPress HTTP API as a fallback).
 *
 * Only the operations a backup plugin needs are implemented. Bodies are
 * streamed from, and to, file handles so that a multi-gigabyte archive never
 * has to fit in memory.
 */
final class S3Client {

	/**
	 * Endpoint URL, e.g. `https://s3.ir-thr-at1.arvanstorage.ir`.
	 *
	 * @var string
	 */
	private $endpoint;

	/**
	 * Region.
	 *
	 * @var string
	 */
	private $region;

	/**
	 * Bucket.
	 *
	 * @var string
	 */
	private $bucket;

	/**
	 * Access key.
	 *
	 * @var string
	 */
	private $access_key;

	/**
	 * Secret key.
	 *
	 * @var string
	 */
	private $secret_key;

	/**
	 * Whether to use path style URLs (`endpoint/bucket/key`).
	 *
	 * @var bool
	 */
	private $path_style;

	/**
	 * Last error.
	 *
	 * @var string
	 */
	private $error = '';

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Client configuration.
	 */
	public function __construct( array $config ) {
		$this->endpoint   = rtrim( (string) ( $config['endpoint'] ?? '' ), '/' );
		$this->region     = (string) ( $config['region'] ?? 'us-east-1' );
		$this->bucket     = (string) ( $config['bucket'] ?? '' );
		$this->access_key = (string) ( $config['access_key'] ?? '' );
		$this->secret_key = (string) ( $config['secret_key'] ?? '' );
		$this->path_style = (bool) ( $config['path_style'] ?? true );
	}

	/**
	 * Upload a file.
	 *
	 * @param string $local_path Absolute path.
	 * @param string $key        Object key.
	 * @return bool
	 */
	public function put_object( string $local_path, string $key ): bool {
		if ( ! is_readable( $local_path ) ) {
			$this->error = __( 'The file to upload does not exist.', 'backupino' );

			return false;
		}

		$size = (int) filesize( $local_path );
		$url  = $this->url( $key, $host );

		// `UNSIGNED-PAYLOAD` lets us stream the body without hashing it twice.
		$headers = $this->signed_headers( 'PUT', $this->path( $key ), $host, 'UNSIGNED-PAYLOAD', 'application/octet-stream' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$handle = fopen( $local_path, 'rb' );

		if ( false === $handle ) {
			$this->error = __( 'The file could not be opened.', 'backupino' );

			return false;
		}

		$result = $this->curl(
			$url,
			'PUT',
			$headers,
			array(
				'upload'      => $handle,
				'upload_size' => $size,
			)
		);

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		return $result;
	}

	/**
	 * Download an object into a file.
	 *
	 * @param string $key        Object key.
	 * @param string $local_path Destination.
	 * @return bool
	 */
	public function get_object( string $key, string $local_path ): bool {
		$url     = $this->url( $key, $host );
		$headers = $this->signed_headers( 'GET', $this->path( $key ), $host, self::empty_hash() );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$handle = fopen( $local_path, 'wb' );

		if ( false === $handle ) {
			$this->error = __( 'The destination file could not be created.', 'backupino' );

			return false;
		}

		$result = $this->curl( $url, 'GET', $headers, array( 'download' => $handle ) );

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		return $result;
	}

	/**
	 * Delete an object.
	 *
	 * @param string $key Object key.
	 * @return bool
	 */
	public function delete_object( string $key ): bool {
		$url     = $this->url( $key, $host );
		$headers = $this->signed_headers( 'DELETE', $this->path( $key ), $host, self::empty_hash() );

		return $this->curl( $url, 'DELETE', $headers );
	}

	/**
	 * Whether an object exists.
	 *
	 * @param string $key Object key.
	 * @return bool
	 */
	public function head_object( string $key ): bool {
		$url     = $this->url( $key, $host );
		$headers = $this->signed_headers( 'HEAD', $this->path( $key ), $host, self::empty_hash() );

		return $this->curl( $url, 'HEAD', $headers );
	}

	/**
	 * Last error.
	 *
	 * @return string
	 */
	public function error(): string {
		return $this->error;
	}

	/**
	 * Build the signable path for a key.
	 *
	 * @param string $key Object key.
	 * @return string
	 */
	private function path( string $key ): string {
		$path = '/' . ltrim( $key, '/' );

		return $this->path_style ? '/' . $this->bucket . $path : $path;
	}

	/**
	 * Build the request URL and resolve the Host header.
	 *
	 * @param string      $key  Object key.
	 * @param string|null $host Receives the Host header value.
	 * @return string
	 */
	private function url( string $key, &$host = null ): string {
		$parsed = wp_parse_url( $this->endpoint );
		$host   = isset( $parsed['host'] ) ? (string) $parsed['host'] : '';
		$scheme = isset( $parsed['scheme'] ) ? (string) $parsed['scheme'] : 'https';
		$port   = isset( $parsed['port'] ) ? ':' . (int) $parsed['port'] : '';

		if ( ! $this->path_style ) {
			$host = $this->bucket . '.' . $host;
		}

		return $scheme . '://' . $host . $port . $this->path( $key );
	}

	/**
	 * Sign a request and return the headers to send.
	 *
	 * @param string $method       HTTP method.
	 * @param string $path         Signed path.
	 * @param string $host         Host header.
	 * @param string $payload_hash Payload hash or `UNSIGNED-PAYLOAD`.
	 * @param string $content_type Optional content type.
	 * @return array<string, string>
	 */
	private function signed_headers( string $method, string $path, string $host, string $payload_hash, string $content_type = '' ): array {
		$headers = array( 'Host' => $host );

		if ( '' !== $content_type ) {
			$headers['Content-Type'] = $content_type;
		}

		$signed = S3Signer::sign(
			array(
				'method'       => $method,
				'uri'          => $path,
				'query'        => array(),
				'headers'      => $headers,
				'payload_hash' => $payload_hash,
				'region'       => $this->region,
				'service'      => 's3',
				'access_key'   => $this->access_key,
				'secret_key'   => $this->secret_key,
				'amz_date'     => gmdate( 'Ymd\THis\Z' ),
			)
		);

		unset( $signed['Host'] );

		return $signed;
	}

	/**
	 * Perform the HTTP request.
	 *
	 * @param string                $url     Request URL.
	 * @param string                $method  HTTP method.
	 * @param array<string, string> $headers Signed headers.
	 * @param array<string, mixed>  $options `upload`/`upload_size`/`download`.
	 * @return bool
	 */
	private function curl( string $url, string $method, array $headers, array $options = array() ): bool {
		if ( '' === $this->endpoint || '' === $this->bucket || '' === $this->access_key ) {
			$this->error = __( 'The S3 credentials are incomplete.', 'backupino' );

			return false;
		}

		$lines = array();

		foreach ( $headers as $name => $value ) {
			$lines[] = $name . ': ' . $value;
		}

		if ( function_exists( 'curl_init' ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init
			$handle = curl_init();

			curl_setopt( $handle, CURLOPT_URL, $url );
			curl_setopt( $handle, CURLOPT_CUSTOMREQUEST, $method );
			curl_setopt( $handle, CURLOPT_HTTPHEADER, $lines );
			curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $handle, CURLOPT_TIMEOUT, 600 );
			curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 30 );
			curl_setopt( $handle, CURLOPT_FOLLOWLOCATION, false );

			if ( isset( $options['upload'] ) ) {
				curl_setopt( $handle, CURLOPT_UPLOAD, true );
				curl_setopt( $handle, CURLOPT_INFILE, $options['upload'] );
				curl_setopt( $handle, CURLOPT_INFILESIZE, (int) $options['upload_size'] );
			}

			if ( isset( $options['download'] ) ) {
				curl_setopt( $handle, CURLOPT_FILE, $options['download'] );
			}

			$body = curl_exec( $handle );
			$code = (int) curl_getinfo( $handle, CURLINFO_HTTP_CODE );

			if ( false === $body && '' === $this->error ) {
				$this->error = (string) curl_error( $handle );
			}

			curl_close( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close

			if ( $code >= 200 && $code < 300 ) {
				return true;
			}

			if ( '' === $this->error && is_string( $body ) ) {
				$this->error = sprintf(
					/* translators: 1: HTTP status, 2: response body. */
					__( 'S3 request failed with HTTP %1$d: %2$s', 'backupino' ),
					$code,
					substr( $body, 0, 300 )
				);
			}

			return false;
		}

		// Fallback for hosts without cURL: the whole file goes through memory.
		$request = array(
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 600,
		);

		if ( isset( $options['upload'] ) ) {
			$contents = stream_get_contents( $options['upload'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions

			if ( false === $contents ) {
				$this->error = __( 'The file could not be read.', 'backupino' );

				return false;
			}

			$request['body'] = $contents;
		}

		$response = wp_remote_request( $url, $request );

		if ( is_wp_error( $response ) ) {
			$this->error = $response->get_error_message();

			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code >= 200 && $code < 300 ) {
			if ( isset( $options['download'] ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
				fwrite( $options['download'], (string) wp_remote_retrieve_body( $response ) );
			}

			return true;
		}

		$this->error = sprintf(
			/* translators: 1: HTTP status, 2: response body. */
			__( 'S3 request failed with HTTP %1$d: %2$s', 'backupino' ),
			$code,
			substr( (string) wp_remote_retrieve_body( $response ), 0, 300 )
		);

		return false;
	}

	/**
	 * SHA-256 of an empty body.
	 *
	 * @return string
	 */
	public static function empty_hash(): string {
		return 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';
	}
}
