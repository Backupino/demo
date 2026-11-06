<?php
/**
 * SLA Timer and Support Escalation Manager.
 *
 * Implements S3-T07 SLA timer tracking per plan:
 * - trial: None
 * - personal: 48 hours
 * - freelancer: 24 hours
 * - agency: 12 hours (with dedicated high-priority support)
 *
 * @package Backupino\Portal\Lifecycle
 */

declare( strict_types = 1 );

namespace Backupino\Portal\Lifecycle;

defined( 'ABSPATH' ) || exit;

/**
 * Manages service level agreement calculations, deadlines, and breach detection.
 */
final class SlaManager {

	/**
	 * Support SLA hours per subscription tier.
	 */
	public const SLA_HOURS = array(
		'trial'      => null, // No SLA guaranteed
		'personal'   => 48,   // 48 hours response time
		'freelancer' => 24,   // 24 hours response time
		'agency'     => 12,   // 12 hours response time + dedicated support
	);

	/**
	 * Warning threshold percentage before breach (e.g., 80% of allowed time spent).
	 */
	public const BREACH_WARNING_THRESHOLD_PERCENT = 80;

	/**
	 * Get the SLA limit in hours for a given plan slug.
	 *
	 * @param string $plan_slug Plan identifier.
	 * @return int|null Hours or null if no SLA applies.
	 */
	public static function get_sla_hours( string $plan_slug ): ?int {
		return self::SLA_HOURS[ $plan_slug ] ?? null;
	}

	/**
	 * Calculate ticket/request deadline timestamp based on plan.
	 *
	 * @param string   $plan_slug  Plan identifier.
	 * @param int|null $start_time Ticket opening timestamp (default: current time).
	 * @return int|null Target epoch timestamp or null if plan has no SLA.
	 */
	public static function calculate_deadline( string $plan_slug, ?int $start_time = null ): ?int {
		$hours = self::get_sla_hours( $plan_slug );
		if ( null === $hours || $hours <= 0 ) {
			return null;
		}

		$start = null !== $start_time ? $start_time : time();
		return $start + ( $hours * 3600 );
	}

	/**
	 * Check if an SLA has been breached.
	 *
	 * @param string   $plan_slug     Plan identifier.
	 * @param int      $start_time    Ticket opening timestamp.
	 * @param int|null $resolved_time Resolution timestamp (or null if still open, evaluated against now).
	 * @return bool True if breached, false if within SLA or no SLA.
	 */
	public static function is_breached( string $plan_slug, int $start_time, ?int $resolved_time = null ): bool {
		$deadline = self::calculate_deadline( $plan_slug, $start_time );
		if ( null === $deadline ) {
			return false; // No SLA to breach
		}

		$check_time = null !== $resolved_time ? $resolved_time : time();
		return $check_time > $deadline;
	}

	/**
	 * Get remaining seconds before SLA breach.
	 *
	 * @param string   $plan_slug    Plan identifier.
	 * @param int      $start_time   Ticket opening timestamp.
	 * @param int|null $current_time Optional reference time (default: now).
	 * @return int|null Remaining seconds (negative if breached), or null if no SLA.
	 */
	public static function get_time_remaining_seconds( string $plan_slug, int $start_time, ?int $current_time = null ): ?int {
		$deadline = self::calculate_deadline( $plan_slug, $start_time );
		if ( null === $deadline ) {
			return null;
		}

		$now = null !== $current_time ? $current_time : time();
		return $deadline - $now;
	}

	/**
	 * Determine full SLA status and metrics for support queues.
	 *
	 * @param string   $plan_slug     Plan identifier.
	 * @param int      $start_time    Ticket opening timestamp.
	 * @param int|null $resolved_time Resolution timestamp (if resolved).
	 * @param int|null $current_time  Reference timestamp (default: now).
	 * @return array{
	 *     plan_slug: string,
	 *     has_sla: bool,
	 *     sla_hours: int|null,
	 *     deadline: int|null,
	 *     remaining_seconds: int|null,
	 *     is_breached: bool,
	 *     is_approaching_breach: bool,
	 *     priority_tier: string
	 * }
	 */
	public static function get_sla_metrics(
		string $plan_slug,
		int $start_time,
		?int $resolved_time = null,
		?int $current_time = null
	): array {
		$sla_hours = self::get_sla_hours( $plan_slug );
		if ( null === $sla_hours ) {
			return array(
				'plan_slug'             => $plan_slug,
				'has_sla'               => false,
				'sla_hours'             => null,
				'deadline'              => null,
				'remaining_seconds'     => null,
				'is_breached'           => false,
				'is_approaching_breach' => false,
				'priority_tier'         => 'standard',
			);
		}

		$deadline   = $start_time + ( $sla_hours * 3600 );
		$now        = null !== $current_time ? $current_time : time();
		$check_time = null !== $resolved_time ? $resolved_time : $now;

		$total_seconds     = $sla_hours * 3600;
		$elapsed_seconds   = $check_time - $start_time;
		$remaining_seconds = $deadline - $check_time;

		$is_breached           = $elapsed_seconds > $total_seconds;
		$is_approaching_breach = ! $is_breached && null === $resolved_time && ( $elapsed_seconds >= ( $total_seconds * ( self::BREACH_WARNING_THRESHOLD_PERCENT / 100 ) ) );

		$priority_tier = 'standard';
		if ( 'agency' === $plan_slug ) {
			$priority_tier = 'critical_dedicated';
		} elseif ( 'freelancer' === $plan_slug ) {
			$priority_tier = 'high';
		}

		return array(
			'plan_slug'             => $plan_slug,
			'has_sla'               => true,
			'sla_hours'             => $sla_hours,
			'deadline'              => $deadline,
			'remaining_seconds'     => $remaining_seconds,
			'is_breached'           => $is_breached,
			'is_approaching_breach' => $is_approaching_breach,
			'priority_tier'         => $priority_tier,
		);
	}
}
