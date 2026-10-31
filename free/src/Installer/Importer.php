<?php
/**
 * SQL importer.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Installer;

/**
 * Executes the dump statement by statement, in time boxed slices.
 */
final class Importer {

	/**
	 * Connection.
	 *
	 * @var Connection
	 */
	private $connection;

	/**
	 * Splitter.
	 *
	 * @var SqlSplitter
	 */
	private $splitter;

	/**
	 * Total file size.
	 *
	 * @var int
	 */
	private $size;

	/**
	 * Constructor.
	 *
	 * @param Connection  $connection Database connection.
	 * @param SqlSplitter $splitter   Statement splitter.
	 */
	public function __construct( Connection $connection, SqlSplitter $splitter ) {
		$this->connection = $connection;
		$this->splitter   = $splitter;
		$this->size       = $splitter->size();
	}

	/**
	 * Total dump size in bytes.
	 *
	 * @return int
	 */
	public function size(): int {
		return $this->size;
	}

	/**
	 * Execute a slice of the dump.
	 *
	 * @param int   $offset     Byte offset (by reference).
	 * @param int   $max_bytes  Maximum bytes to consume.
	 * @param float $seconds    Time budget.
	 * @return array{executed: int, done: bool, error: string}
	 */
	public function step( int &$offset, int $max_bytes = 262144, float $seconds = 8.0 ): array {
		$started  = microtime( true );
		$executed = 0;
		$done     = false;

		while ( ( microtime( true ) - $started ) < $seconds ) {
			$batch = $this->splitter->read( $offset, $max_bytes );

			if ( array() === $batch['statements'] ) {
				$done = $batch['done'];

				if ( $batch['offset'] === $offset ) {
					break;
				}

				$offset = $batch['offset'];
				continue;
			}

			foreach ( $batch['statements'] as $statement ) {
				if ( ! $this->connection->query( $statement ) ) {
					return array(
						'executed' => $executed,
						'done'     => false,
						'error'    => self::describe( $statement, $this->connection->error() ),
					);
				}

				$executed++;
			}

			$offset = $batch['offset'];

			if ( $batch['done'] ) {
				$done = true;
				break;
			}
		}

		return array(
			'executed' => $executed,
			'done'     => $done,
			'error'    => '',
		);
	}

	/**
	 * Build a helpful error message.
	 *
	 * @param string $statement Failing statement.
	 * @param string $error     Driver error.
	 * @return string
	 */
	private static function describe( string $statement, string $error ): string {
		$preview = (string) preg_replace( '/\s+/', ' ', $statement );

		return $error . ' :: ' . substr( $preview, 0, 200 );
	}
}
