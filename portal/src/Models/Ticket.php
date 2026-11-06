<?php
/**
 * Support Ticket Model.
 *
 * @package Backupino\Portal\Models
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Support ticket entity.
 */
final class Ticket {

	public int $id;
	public string $ticket_number;
	public int $user_id;
	public int $license_id;
	public string $plan_slug;
	public string $subject;
	public string $priority;
	public string $status;
	public ?string $sla_deadline_at;
	public bool $sla_breached;
	public string $created_at;
	public string $updated_at;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data
	 */
	public function __construct( array $data = array() ) {
		$this->id              = (int) ( $data['id'] ?? 0 );
		$this->ticket_number   = (string) ( $data['ticket_number'] ?? '' );
		$this->user_id         = (int) ( $data['user_id'] ?? 0 );
		$this->license_id      = (int) ( $data['license_id'] ?? 0 );
		$this->plan_slug       = (string) ( $data['plan_slug'] ?? 'personal' );
		$this->subject         = (string) ( $data['subject'] ?? '' );
		$this->priority        = (string) ( $data['priority'] ?? 'standard' );
		$this->status          = (string) ( $data['status'] ?? 'open' );
		$this->sla_deadline_at = isset( $data['sla_deadline_at'] ) ? (string) $data['sla_deadline_at'] : null;
		$this->sla_breached    = ! empty( $data['sla_breached'] );
		$this->created_at      = (string) ( $data['created_at'] ?? current_time( 'mysql', true ) );
		$this->updated_at      = (string) ( $data['updated_at'] ?? current_time( 'mysql', true ) );
	}

	/**
	 * Convert entity to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'              => $this->id,
			'ticket_number'   => $this->ticket_number,
			'user_id'         => $this->user_id,
			'license_id'      => $this->license_id,
			'plan_slug'       => $this->plan_slug,
			'subject'         => $this->subject,
			'priority'        => $this->priority,
			'status'          => $this->status,
			'sla_deadline_at' => $this->sla_deadline_at,
			'sla_breached'    => $this->sla_breached,
			'created_at'      => $this->created_at,
			'updated_at'      => $this->updated_at,
		);
	}
}
