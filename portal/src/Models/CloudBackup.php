<?php
/**
 * Cloud Backup Model.
 *
 * Represents a single cloud backup record in `wp_backupino_cloud_backups`.
 *
 * @package Backupino\Portal\Models
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Cloud backup entity.
 */
final class CloudBackup {

	public int $id;
	public int $license_id;
	public int $activation_id;
	public string $domain_hash;
	public string $backup_hash;
	public string $package_name;
	public int $file_size_bytes;
	public string $storage_provider;
	public string $storage_path;
	public ?string $wrapped_dek;
	public ?string $shamir_recovery_hint;
	public string $retention_status;
	public ?string $retention_purge_scheduled_at;
	public ?string $purged_at;
	public string $created_at;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data
	 */
	public function __construct( array $data = array() ) {
		$this->id                           = (int) ( $data['id'] ?? 0 );
		$this->license_id                   = (int) ( $data['license_id'] ?? 0 );
		$this->activation_id                = (int) ( $data['activation_id'] ?? 0 );
		$this->domain_hash                  = (string) ( $data['domain_hash'] ?? '' );
		$this->backup_hash                  = (string) ( $data['backup_hash'] ?? '' );
		$this->package_name                 = (string) ( $data['package_name'] ?? '' );
		$this->file_size_bytes              = (int) ( $data['file_size_bytes'] ?? 0 );
		$this->storage_provider             = (string) ( $data['storage_provider'] ?? 'dedicated-s3' );
		$this->storage_path                 = (string) ( $data['storage_path'] ?? '' );
		$this->wrapped_dek                  = isset( $data['wrapped_dek'] ) ? (string) $data['wrapped_dek'] : null;
		$this->shamir_recovery_hint         = isset( $data['shamir_recovery_hint'] ) ? (string) $data['shamir_recovery_hint'] : null;
		$this->retention_status             = (string) ( $data['retention_status'] ?? 'active' );
		$this->retention_purge_scheduled_at = isset( $data['retention_purge_scheduled_at'] ) ? (string) $data['retention_purge_scheduled_at'] : null;
		$this->purged_at                    = isset( $data['purged_at'] ) ? (string) $data['purged_at'] : null;
		$this->created_at                   = (string) ( $data['created_at'] ?? current_time( 'mysql', true ) );
	}

	/**
	 * Format file size into human-readable string (MB/GB).
	 */
	public function get_formatted_size(): string {
		if ( $this->file_size_bytes >= 1073741824 ) {
			return sprintf( '%.2f GB', $this->file_size_bytes / 1073741824 );
		}
		if ( $this->file_size_bytes >= 1048576 ) {
			return sprintf( '%.1f MB', $this->file_size_bytes / 1048576 );
		}
		if ( $this->file_size_bytes >= 1024 ) {
			return sprintf( '%d KB', round( $this->file_size_bytes / 1024 ) );
		}
		return sprintf( '%d B', $this->file_size_bytes );
	}

	/**
	 * Convert entity to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                           => $this->id,
			'license_id'                   => $this->license_id,
			'activation_id'                => $this->activation_id,
			'domain_hash'                  => $this->domain_hash,
			'backup_hash'                  => $this->backup_hash,
			'package_name'                 => $this->package_name,
			'file_size_bytes'              => $this->file_size_bytes,
			'formatted_size'               => $this->get_formatted_size(),
			'storage_provider'             => $this->storage_provider,
			'storage_path'                 => $this->storage_path,
			'retention_status'             => $this->retention_status,
			'retention_purge_scheduled_at' => $this->retention_purge_scheduled_at,
			'purged_at'                    => $this->purged_at,
			'created_at'                   => $this->created_at,
		);
	}
}
