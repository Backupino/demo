<?php
/**
 * Immediately expired deadline.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Support;

use Backupino\Jobs\Deadline;

/**
 * Deadline that reports itself as expired from the first call.
 */
final class ExpiredDeadline extends Deadline {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( 1 );
	}

	/**
	 * {@inheritDoc}
	 */
	public function expired(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function remaining(): float {
		return 0.0;
	}
}
