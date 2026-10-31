<?php
/**
 * PDO connection fallback.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Installer;

/**
 * {@see Connection} implemented on top of PDO, used when mysqli is disabled.
 */
final class PdoConnection implements Connection {

	/**
	 * Handle.
	 *
	 * @var \PDO
	 */
	private $pdo;

	/**
	 * Last error.
	 *
	 * @var string
	 */
	private $error = '';

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Connection configuration.
	 * @return void
	 *
	 * @throws \RuntimeException When the connection fails.
	 */
	public function __construct( array $config ) {
		if ( ! extension_loaded( 'pdo_mysql' ) ) {
			throw new \RuntimeException( 'pdo_mysql is not available' );
		}

		$host   = (string) ( $config['host'] ?? 'localhost' );
		$port   = isset( $config['port'] ) ? (int) $config['port'] : 0;
		$db     = (string) ( $config['name'] ?? '' );
		$socket = (string) ( $config['socket'] ?? '' );

		$dsn = 'mysql:charset=utf8mb4';

		if ( '' !== $socket ) {
			$dsn .= ';unix_socket=' . $socket;
		} else {
			$dsn .= ';host=' . $host . ( $port > 0 ? ';port=' . $port : '' );
		}

		if ( '' !== $db ) {
			$dsn .= ';dbname=' . $db;
		}

		try {
			$this->pdo = new \PDO(
				$dsn,
				(string) ( $config['user'] ?? '' ),
				(string) ( $config['password'] ?? '' ),
				array(
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_SILENT,
				)
			);
		} catch ( \PDOException $e ) {
			$this->error = $e->getMessage();

			throw new \RuntimeException( $this->error );
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function query( string $sql ): bool {
		$result = $this->pdo->query( $sql );

		if ( false === $result ) {
			$this->error = (string) $this->pdo->errorInfo()[2];

			return false;
		}

		$result->closeCursor();

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function rows( string $sql ): array {
		$result = $this->pdo->query( $sql );

		if ( false === $result ) {
			$this->error = (string) $this->pdo->errorInfo()[2];

			return array();
		}

		$rows = $result->fetchAll( \PDO::FETCH_ASSOC );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function escape( string $value ): string {
		return trim( (string) $this->pdo->quote( $value ), "'" );
	}

	/**
	 * {@inheritDoc}
	 */
	public function error(): string {
		return $this->error;
	}

	/**
	 * {@inheritDoc}
	 */
	public function version(): string {
		$version = $this->pdo->getAttribute( \PDO::ATTR_SERVER_VERSION );

		return is_string( $version ) ? $version : '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function select( string $name ): bool {
		return $this->query( 'USE `' . str_replace( '`', '``', $name ) . '`' );
	}
}
