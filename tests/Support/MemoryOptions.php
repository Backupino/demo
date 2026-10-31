<?php
/**
 * In-memory option store.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Support;

use Backupino\Support\OptionsStore;

/**
 * Options store that lives in an array.
 */
final class MemoryOptions implements OptionsStore {

	/**
	 * Stored values.
	 *
	 * @var array<string, mixed>
	 */
	public $values = array();

	/**
	 * {@inheritDoc}
	 */
	public function get( string $key, $default = null ) {
		return array_key_exists( $key, $this->values ) ? $this->values[ $key ] : $default;
	}

	/**
	 * {@inheritDoc}
	 */
	public function update( string $key, $value ): bool {
		$this->values[ $key ] = $value;

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete( string $key ): bool {
		unset( $this->values[ $key ] );

		return true;
	}
}
