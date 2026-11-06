<?php
/**
 * Plan Definition Model.
 *
 * Implements S3-T05, D-004, D-005, D-006, D-007, D-008, D-014.
 *
 * @package Backupino\Portal\Plans
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Plans;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a single product plan and its capabilities.
 */
final class Plan {

	public string $slug = 'personal';
	public string $name = 'Backupino Personal';
	public string $name_fa = 'بکاپینو شخصی';
	public int $duration_days = 365;
	public int $domains_limit = 1;
	public int $domain_swaps_allowed = 2; // -1 for unlimited, 0 for trial
	public int $annual_activations_cap = 5; // Soft cap on total activations per year
	public int $customer_cloud_retention = 2; // Max versions on customer S3/FTP
	public int $dedicated_cloud_quota_bytes = 0; // 30GB freelancer, 100GB agency
	public int $dedicated_cloud_retention = 0; // Versions on dedicated cloud
	public int $team_users_limit = 1;
	public ?int $sla_hours = 48; // Support SLA hours
	public bool $multisite = false;
	public bool $whitelabel = false;

	/**
	 * @var string[]
	 */
	public array $features = array();

	/**
	 * @param array<string, mixed> $data
	 */
	public function __construct( array $data = array() ) {
		if ( empty( $data ) ) {
			return;
		}

		$this->slug                        = isset( $data['slug'] ) ? (string) $data['slug'] : 'personal';
		$this->name                        = isset( $data['name'] ) ? (string) $data['name'] : 'Backupino Personal';
		$this->name_fa                     = isset( $data['name_fa'] ) ? (string) $data['name_fa'] : 'بکاپینو شخصی';
		$this->duration_days               = isset( $data['duration_days'] ) ? (int) $data['duration_days'] : 365;
		$this->domains_limit               = isset( $data['domains_limit'] ) ? (int) $data['domains_limit'] : 1;
		$this->domain_swaps_allowed        = isset( $data['domain_swaps_allowed'] ) ? (int) $data['domain_swaps_allowed'] : 2;
		$this->annual_activations_cap      = isset( $data['annual_activations_cap'] ) ? (int) $data['annual_activations_cap'] : 5;
		$this->customer_cloud_retention    = isset( $data['customer_cloud_retention'] ) ? (int) $data['customer_cloud_retention'] : 2;
		$this->dedicated_cloud_quota_bytes = isset( $data['dedicated_cloud_quota_bytes'] ) ? (int) $data['dedicated_cloud_quota_bytes'] : 0;
		$this->dedicated_cloud_retention   = isset( $data['dedicated_cloud_retention'] ) ? (int) $data['dedicated_cloud_retention'] : 0;
		$this->team_users_limit            = isset( $data['team_users_limit'] ) ? (int) $data['team_users_limit'] : 1;
		$this->sla_hours                   = isset( $data['sla_hours'] ) && null !== $data['sla_hours'] ? (int) $data['sla_hours'] : null;
		$this->multisite                   = ! empty( $data['multisite'] );
		$this->whitelabel                  = ! empty( $data['whitelabel'] );
		$this->features                    = isset( $data['features'] ) && is_array( $data['features'] ) ? array_values( $data['features'] ) : array();
	}

	/**
	 * Convert to array representation for API serialization.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'slug'                        => $this->slug,
			'name'                        => $this->name,
			'name_fa'                     => $this->name_fa,
			'duration_days'               => $this->duration_days,
			'domains_limit'               => $this->domains_limit,
			'domain_swaps_allowed'        => $this->domain_swaps_allowed,
			'annual_activations_cap'      => $this->annual_activations_cap,
			'customer_cloud_retention'    => $this->customer_cloud_retention,
			'dedicated_cloud_quota_bytes' => $this->dedicated_cloud_quota_bytes,
			'dedicated_cloud_retention'   => $this->dedicated_cloud_retention,
			'team_users_limit'            => $this->team_users_limit,
			'sla_hours'                   => $this->sla_hours,
			'multisite'                   => $this->multisite,
			'whitelabel'                  => $this->whitelabel,
			'features'                    => $this->features,
		);
	}
}
