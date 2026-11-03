<?php
/**
 * S3 storage driver.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Storage;

use Backupino\Storage\S3\S3Client;

/**
 * Stores packages on any S3 compatible object storage.
 *
 * Works with AWS, ArvanCloud, Liara, ParsPack, Wasabi, Backblaze B2 (S3 API),
 * MinIO and anything else that speaks SigV4.
 */
final class S3Storage implements Storage {

	/**
	 * Client.
	 *
	 * @var S3Client
	 */
	private $client;

	/**
	 * Key prefix.
	 *
	 * @var string
	 */
	private $prefix;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Driver configuration.
	 */
	public function __construct( array $config ) {
		$this->client = new S3Client( $config );
		$this->prefix = trim( (string) ( $config['path_prefix'] ?? '' ), '/' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 's3';
	}

	/**
	 * {@inheritDoc}
	 */
	public function label(): string {
		return __( 'S3 compatible storage', 'backupino' );
	}

	/**
	 * Build the full object key.
	 *
	 * @param string $remote_key Relative key.
	 * @return string
	 */
	private function key( string $remote_key ): string {
		$remote_key = ltrim( $remote_key, '/' );

		return '' === $this->prefix ? $remote_key : $this->prefix . '/' . $remote_key;
	}

	/**
	 * {@inheritDoc}
	 */
	public function put( string $local_path, string $remote_key ): bool {
		return $this->client->put_object( $local_path, $this->key( $remote_key ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get( string $remote_key, string $local_path ): bool {
		return $this->client->get_object( $this->key( $remote_key ), $local_path );
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete( string $remote_key ): bool {
		return $this->client->delete_object( $this->key( $remote_key ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function exists( string $remote_key ): bool {
		return $this->client->head_object( $this->key( $remote_key ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function error(): string {
		return $this->client->error();
	}
}
