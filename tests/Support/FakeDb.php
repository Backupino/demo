<?php
/**
 * In-memory database double.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests\Support;

use Backupino\Core\Db;

/**
 * Scripted {@see Db} implementation used by the dumper tests.
 *
 * It understands just enough SQL to answer the queries the dumper issues.
 */
final class FakeDb implements Db {

	/**
	 * Every executed statement.
	 *
	 * @var string[]
	 */
	public $log = array();

	/**
	 * Table definitions.
	 *
	 * Structure:
	 *  [
	 *    'wp_options' => [
	 *      'type'    => 'BASE TABLE',
	 *      'create'  => 'CREATE TABLE ...',
	 *      'columns' => [ [ 'Field' => 'option_id', 'Type' => 'bigint', 'Key' => 'PRI' ], ... ],
	 *      'rows'    => [ [ 'option_id' => 1, ... ] ],
	 *    ],
	 *  ]
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public $tables = array();

	/**
	 * Table prefix.
	 *
	 * @var string
	 */
	public $prefix = 'wp_';

	public $insert_id = 1;

	/**
	 * {@inheritDoc}
	 */
	public function query( string $sql ): bool {
		$this->log[] = $sql;

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function rows( string $sql ): array {
		$this->log[] = $sql;
		$sql         = trim( $sql );

		if ( 0 === stripos( $sql, 'SHOW FULL TABLES' ) || 0 === stripos( $sql, 'SHOW TABLES' ) ) {
			$rows = array();

			foreach ( $this->tables as $name => $table ) {
				$rows[] = array(
					'Tables_in_wordpress' => $name,
					'Table_type'          => isset( $table['type'] ) ? $table['type'] : 'BASE TABLE',
				);
			}

			return $rows;
		}

		if ( preg_match( '/^SHOW COLUMNS FROM `([^`]+)`/i', $sql, $match ) ) {
			return isset( $this->tables[ $match[1] ]['columns'] ) ? $this->tables[ $match[1] ]['columns'] : array();
		}

		if ( preg_match( '/^SHOW CREATE TABLE `([^`]+)`/i', $sql, $match ) ) {
			$table = $this->tables[ $match[1] ] ?? array();

			return array(
				array(
					'Table'        => $match[1],
					'Create Table' => isset( $table['create'] ) ? $table['create'] : 'CREATE TABLE `' . $match[1] . '` (`id` int)',
				),
			);
		}

		if ( preg_match( '/^SHOW CREATE VIEW `([^`]+)`/i', $sql, $match ) ) {
			return array(
				array(
					'View'        => $match[1],
					'Create View' => 'CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `'
						. $match[1] . '` AS select 1 AS `x`',
				),
			);
		}

		if ( 0 === stripos( $sql, 'SHOW TRIGGERS' ) ) {
			return array();
		}

		if ( false !== stripos( $sql, 'information_schema.ROUTINES' ) ) {
			return array();
		}

		if ( preg_match( '/^SELECT .* FROM `([^`]+)`/i', $sql, $match ) ) {
			return $this->select_rows( $match[1], $sql );
		}

		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function row( string $sql ): ?array {
		$rows = $this->rows( $sql );

		return isset( $rows[0] ) ? $rows[0] : null;
	}

	/**
	 * WordPress wpdb compat: get_results.
	 *
	 * @param string $sql    Statement.
	 * @param string $output Output format.
	 * @return array<int, mixed>
	 */
	public function get_results( string $sql, string $output = 'OBJECT' ): array {
		return $this->rows( $sql );
	}

	/**
	 * WordPress wpdb compat: get_row.
	 *
	 * @param string $sql    Statement.
	 * @param string $output Output format.
	 * @return mixed
	 */
	public function get_row( string $sql, string $output = 'OBJECT' ) {
		return $this->row( $sql );
	}

	/**
	 * WordPress wpdb compat: get_var.
	 *
	 * @param string $sql Statement.
	 * @return mixed
	 */
	public function get_var( string $sql ) {
		return $this->var( $sql );
	}

	/**
	 * WordPress wpdb compat: prepare.
	 *
	 * @param string $query Query.
	 * @param mixed  ...$args Arguments.
	 * @return string
	 */
	public function prepare( string $query, ...$args ): string {
		if ( empty( $args ) ) {
			return $query;
		}
		if ( isset( $args[0] ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		$escaped_args = array_map(
			static function ( $arg ) {
				return is_numeric( $arg ) ? $arg : addslashes( (string) $arg );
			},
			$args
		);
		$format = str_replace( array( '%s', '%d', '%f' ), array( "'%s'", '%d', '%f' ), $query );
		return vsprintf( $format, $escaped_args );
	}

	/**
	 * {@inheritDoc}
	 */
	public function var( string $sql, $default = null ) {
		$row = $this->row( $sql );

		return null === $row ? $default : reset( $row );
	}

	/**
	 * {@inheritDoc}
	 */
	public function column( string $sql ): array {
		$values = array();

		foreach ( $this->rows( $sql ) as $row ) {
			$values[] = reset( $row );
		}

		return $values;
	}

	/**
	 * {@inheritDoc}
	 */
	public function escape( string $value ): string {
		return esc_sql( $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function prefix(): string {
		return $this->prefix;
	}

	/**
	 * {@inheritDoc}
	 */
	public function insert( string $table, array $data ): int {
		$this->log[] = 'INSERT ' . $table;

		return 1;
	}

	/**
	 * {@inheritDoc}
	 */
	public function update( string $table, array $data, array $where ): int {
		$this->log[] = 'UPDATE ' . $table;

		return 1;
	}

	/**
	 * {@inheritDoc}
	 */
	public function error(): string {
		return '';
	}

	/**
	 * Crude SELECT implementation covering keyset pagination and LIMIT/OFFSET.
	 *
	 * @param string $table Table name.
	 * @param string $sql   Statement.
	 * @return array<int, array<string, mixed>>
	 */
	private function select_rows( string $table, string $sql ): array {
		$rows = isset( $this->tables[ $table ]['rows'] ) ? $this->tables[ $table ]['rows'] : array();

		$limit  = preg_match( '/LIMIT (\d+)/i', $sql, $match ) ? (int) $match[1] : 1000;
		$offset = preg_match( '/OFFSET (\d+)/i', $sql, $match ) ? (int) $match[1] : 0;

		if ( preg_match( '/WHERE `([^`]+)` > \'?([^\' ]+)\'?/i', $sql, $match ) ) {
			$column = $match[1];
			$value  = $match[2];
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
}
