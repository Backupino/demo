<?php
/**
 * In-memory installer connection.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Support;

use Backupino\Installer\Connection;

/**
 * Scripted {@see Connection} used by the importer and URL rewrite tests.
 */
final class FakeConnection implements Connection {

	/**
	 * Executed statements.
	 *
	 * @var string[]
	 */
	public $log = array();

	/**
	 * Tables: name => [ 'columns' => [...], 'rows' => [...] ].
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public $tables = array();

	/**
	 * When set, the next query fails with this message.
	 *
	 * @var string
	 */
	public $fail_with = '';

	/**
	 * {@inheritDoc}
	 */
	public function query( string $sql ): bool {
		$this->log[] = $sql;

		if ( '' !== $this->fail_with ) {
			return false;
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function rows( string $sql ): array {
		$this->log[] = $sql;
		$sql         = trim( $sql );

		if ( 0 === stripos( $sql, 'SHOW TABLES' ) ) {
			$rows = array();

			foreach ( array_keys( $this->tables ) as $name ) {
				$rows[] = array( 'Tables_in_db' => $name );
			}

			return $rows;
		}

		if ( preg_match( '/^SHOW COLUMNS FROM `([^`]+)`/i', $sql, $match ) ) {
			return $this->tables[ $match[1] ]['columns'] ?? array();
		}

		if ( preg_match( '/^SELECT .* FROM `([^`]+)`/i', $sql, $match ) ) {
			$table  = $match[1];
			$rows   = $this->tables[ $table ]['rows'] ?? array();
			$limit  = preg_match( '/LIMIT (\d+)/i', $sql, $limits ) ? (int) $limits[1] : 1000;
			$offset = preg_match( '/OFFSET (\d+)/i', $sql, $offsets ) ? (int) $offsets[1] : 0;

			if ( preg_match( '/WHERE `([^`]+)` > \'?([^\' ]+)\'?/i', $sql, $where ) ) {
				$column = $where[1];
				$value  = $where[2];
				$rows   = array_values(
					array_filter(
						$rows,
						static function ( $row ) use ( $column, $value ) {
							return isset( $row[ $column ] ) && $row[ $column ] > $value;
						}
					)
				);
			}

			return array_slice( $rows, $offset, $limit );
		}

		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function escape( string $value ): string {
		return str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function error(): string {
		return $this->fail_with;
	}

	/**
	 * {@inheritDoc}
	 */
	public function version(): string {
		return '8.0.35-fake';
	}

	/**
	 * {@inheritDoc}
	 */
	public function select( string $name ): bool {
		return true;
	}
}
