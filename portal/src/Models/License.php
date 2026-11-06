<?php
/**
 * License Model.
 *
 * @package Backupino\Portal\Models
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a single license record.
 */
final class License {

	public int $id = 0;
	public int $order_id = 0;
	public int $user_id = 0;
	public string $license_key = '';
	public string $plan_slug = 'personal';
	public string $status = 'active';
	public int $max_domains = 1;
	public int $active_domains_count = 0;
	public ?string $expires_at = null;
	public ?string $grace_expires_at = null;

	// Phase 7 Reserved Fields (D-007, D-041).
	public bool $cloud_storage_enabled = false;
	public int $cloud_quota_bytes = 0;
	public int $cloud_used_bytes = 0;
	public int $cloud_max_versions_per_domain = 5;
	public bool $cloud_zero_knowledge_enabled = true;
	public int $cloud_retention_days = 60;

	public string $created_at = '';
	public string $updated_at = '';

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

		$this->id                            = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$this->order_id                      = isset( $data['order_id'] ) ? (int) $data['order_id'] : 0;
		$this->user_id                       = isset( $data['user_id'] ) ? (int) $data['user_id'] : 0;
		$this->license_key                   = isset( $data['license_key'] ) ? (string) $data['license_key'] : '';
		$this->plan_slug                     = isset( $data['plan_slug'] ) ? (string) $data['plan_slug'] : 'personal';
		$this->status                        = isset( $data['status'] ) ? (string) $data['status'] : 'active';
		$this->max_domains                   = isset( $data['max_domains'] ) ? (int) $data['max_domains'] : 1;
		$this->active_domains_count          = isset( $data['active_domains_count'] ) ? (int) $data['active_domains_count'] : 0;
		$this->expires_at                    = ! empty( $data['expires_at'] ) ? (string) $data['expires_at'] : null;
		$this->grace_expires_at              = ! empty( $data['grace_expires_at'] ) ? (string) $data['grace_expires_at'] : null;
		$this->cloud_storage_enabled         = ! empty( $data['cloud_storage_enabled'] );
		$this->cloud_quota_bytes             = isset( $data['cloud_quota_bytes'] ) ? (int) $data['cloud_quota_bytes'] : 0;
		$this->cloud_used_bytes              = isset( $data['cloud_used_bytes'] ) ? (int) $data['cloud_used_bytes'] : 0;
		$this->cloud_max_versions_per_domain = isset( $data['cloud_max_versions_per_domain'] ) ? (int) $data['cloud_max_versions_per_domain'] : 5;
		$this->cloud_zero_knowledge_enabled  = ! isset( $data['cloud_zero_knowledge_enabled'] ) || ! empty( $data['cloud_zero_knowledge_enabled'] );
		$this->cloud_retention_days          = isset( $data['cloud_retention_days'] ) ? (int) $data['cloud_retention_days'] : 60;
		$this->created_at                    = isset( $data['created_at'] ) ? (string) $data['created_at'] : '';
		$this->updated_at                    = isset( $data['updated_at'] ) ? (string) $data['updated_at'] : '';
	}

	/**
	 * Check if license is active (not revoked, not downgraded).
	 */
	public function is_active(): bool {
		return in_array( $this->status, array( 'active', 'expiring_soon' ), true ) && ! $this->is_expired();
	}

	/**
	 * Check if license expiration date has passed.
	 */
	public function is_expired(): bool {
		if ( null === $this->expires_at ) {
			return false; // Lifetime / perpetual license.
		}

		return strtotime( $this->expires_at ) < time();
	}

	/**
	 * Check if license is within grace period.
	 */
	public function is_in_grace_period(): bool {
		if ( ! $this->is_expired() || null === $this->grace_expires_at ) {
			return false;
		}

		return strtotime( $this->grace_expires_at ) >= time();
	}

	/**
	 * Check if there is capacity for more production domains.
	 */
	public function can_activate_more(): bool {
		return $this->active_domains_count < $this->max_domains;
	}

	/**
	 * Remaining available domain slots.
	 */
	public function remaining_domains(): int {
		$remaining = $this->max_domains - $this->active_domains_count;
		return max( 0, $remaining );
	}

	/**
	 * Convert to array representation.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                            => $this->id,
			'order_id'                      => $this->order_id,
			'user_id'                       => $this->user_id,
			'license_key'                   => $this->license_key,
			'plan_slug'                     => $this->plan_slug,
			'status'                        => $this->status,
			'max_domains'                   => $this->max_domains,
			'active_domains_count'          => $this->active_domains_count,
			'expires_at'                    => $this->expires_at,
			'grace_expires_at'              => $this->grace_expires_at,
			'cloud_storage_enabled'         => $this->cloud_storage_enabled,
			'cloud_quota_bytes'             => $this->cloud_quota_bytes,
			'cloud_used_bytes'              => $this->cloud_used_bytes,
			'cloud_max_versions_per_domain' => $this->cloud_max_versions_per_domain,
			'cloud_zero_knowledge_enabled'  => $this->cloud_zero_knowledge_enabled,
			'cloud_retention_days'          => $this->cloud_retention_days,
			'created_at'                    => $this->created_at,
			'updated_at'                    => $this->updated_at,
		);
	}
}
