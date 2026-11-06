<?php
/**
 * Activation Model.
 *
 * @package Backupino\Portal\Models
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a domain activation record.
 */
final class Activation {

	public int $id = 0;
	public int $license_id = 0;
	public string $domain_hash = '';
	public string $domain_name = '';
	public bool $is_staging = false;
	public bool $is_active = true;
	public ?string $last_heartbeat_at = null;
	public string $instance_id = '';

	// Phase 7 Reserved Fields.
	public int $cloud_domain_used_bytes = 0;
	public int $cloud_domain_versions_count = 0;
	public ?string $cloud_last_backup_at = null;

	public string $activated_at = '';
	public ?string $deactivated_at = null;

	/**
	 * Construct from database row array or object.
	 *
	 * @param array<string, mixed>|object $data
	 */
	public function __construct( $data = array() ) {
		if ( is_object( $data ) ) {
			$data = get_object_vars( $data );
		}

		if ( ! is_array( $data ) || empty( $data ) ) {
			return;
		}

		$this->id                          = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$this->license_id                  = isset( $data['license_id'] ) ? (int) $data['license_id'] : 0;
		$this->domain_hash                 = isset( $data['domain_hash'] ) ? (string) $data['domain_hash'] : '';
		$this->domain_name                 = isset( $data['domain_name'] ) ? (string) $data['domain_name'] : '';
		$this->is_staging                  = ! empty( $data['is_staging'] );
		$this->is_active                   = ! isset( $data['is_active'] ) || ! empty( $data['is_active'] );
		$this->last_heartbeat_at           = ! empty( $data['last_heartbeat_at'] ) ? (string) $data['last_heartbeat_at'] : null;
		$this->instance_id                 = isset( $data['instance_id'] ) ? (string) $data['instance_id'] : '';
		$this->cloud_domain_used_bytes     = isset( $data['cloud_domain_used_bytes'] ) ? (int) $data['cloud_domain_used_bytes'] : 0;
		$this->cloud_domain_versions_count = isset( $data['cloud_domain_versions_count'] ) ? (int) $data['cloud_domain_versions_count'] : 0;
		$this->cloud_last_backup_at        = ! empty( $data['cloud_last_backup_at'] ) ? (string) $data['cloud_last_backup_at'] : null;
		$this->activated_at                = isset( $data['activated_at'] ) ? (string) $data['activated_at'] : '';
		$this->deactivated_at              = ! empty( $data['deactivated_at'] ) ? (string) $data['deactivated_at'] : null;
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                          => $this->id,
			'license_id'                  => $this->license_id,
			'domain_hash'                 => $this->domain_hash,
			'domain_name'                 => $this->domain_name,
			'is_staging'                  => $this->is_staging,
			'is_active'                   => $this->is_active,
			'last_heartbeat_at'           => $this->last_heartbeat_at,
			'instance_id'                 => $this->instance_id,
			'cloud_domain_used_bytes'     => $this->cloud_domain_used_bytes,
			'cloud_domain_versions_count' => $this->cloud_domain_versions_count,
			'cloud_last_backup_at'        => $this->cloud_last_backup_at,
			'activated_at'                => $this->activated_at,
			'deactivated_at'              => $this->deactivated_at,
		);
	}
}
