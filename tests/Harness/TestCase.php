<?php
/**
 * Minimal test case base class.
 *
 * The suite is dependency free on purpose: it has to run on a bare PHP binary
 * (and inside php-wasm) without Composer or PHPUnit being installed.
 *
 * @package Backupino
 */

declare( strict_types = 1 );

namespace Backupino\Tests;

/**
 * Base class with the assertions the suite needs.
 */
abstract class TestCase {

	/**
	 * Temp directories created by the test, removed in tearDown.
	 *
	 * @var string[]
	 */
	private $temp_dirs = array();

	/**
	 * Runs before each test method.
	 *
	 * @return void
	 */
	protected function setUp(): void {
	}

	/**
	 * Runs after each test method.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
	}

	/**
	 * Create a unique temporary directory.
	 *
	 * @return string Absolute path, without a trailing slash.
	 */
	protected function temp_dir(): string {
		$dir = sys_get_temp_dir() . '/backupino-test-' . bin2hex( random_bytes( 6 ) );

		if ( ! mkdir( $dir, 0777, true ) && ! is_dir( $dir ) ) {
			throw new \RuntimeException( 'Cannot create temp dir: ' . $dir );
		}

		$this->temp_dirs[] = $dir;

		return $dir;
	}

	/**
	 * Create a file with the given content inside a directory.
	 *
	 * @param string $dir     Directory.
	 * @param string $path    Relative path.
	 * @param string $content Content.
	 * @return string Absolute path.
	 */
	protected function make_file( string $dir, string $path, string $content = '' ): string {
		$target = $dir . '/' . $path;
		$parent = dirname( $target );

		if ( ! is_dir( $parent ) ) {
			mkdir( $parent, 0777, true );
		}

		file_put_contents( $target, $content );

		return $target;
	}

	/**
	 * Remove temp directories.
	 *
	 * @return void
	 */
	public function cleanup(): void {
		foreach ( $this->temp_dirs as $dir ) {
			self::remove_dir( $dir );
		}

		$this->temp_dirs = array();
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $dir Directory.
	 * @return void
	 */
	private static function remove_dir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		foreach ( (array) scandir( $dir ) as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			$path = $dir . '/' . $item;

			if ( is_dir( $path ) ) {
				self::remove_dir( $path );
			} else {
				@unlink( $path );
			}
		}

		@rmdir( $dir );
	}

	/**
	 * Assert two values are identical.
	 *
	 * @param mixed  $expected Expected.
	 * @param mixed  $actual   Actual.
	 * @param string $message  Message.
	 * @return void
	 */
	protected function assertSame( $expected, $actual, string $message = '' ): void {
		if ( $expected !== $actual ) {
			$this->fail(
				sprintf(
					"%s\nExpected: %s\nActual:   %s",
					'' === $message ? 'Values are not identical.' : $message,
					self::describe( $expected ),
					self::describe( $actual )
				)
			);
		}
	}

	/**
	 * Assert two values are equal (loose).
	 *
	 * @param mixed  $expected Expected.
	 * @param mixed  $actual   Actual.
	 * @param string $message  Message.
	 * @return void
	 */
	protected function assertEquals( $expected, $actual, string $message = '' ): void {
		if ( $expected != $actual ) { // phpcs:ignore Universal.Operators.StrictComparisons
			$this->fail(
				sprintf(
					"%s\nExpected: %s\nActual:   %s",
					'' === $message ? 'Values are not equal.' : $message,
					self::describe( $expected ),
					self::describe( $actual )
				)
			);
		}
	}

	/**
	 * Assert a condition is true.
	 *
	 * @param bool   $condition Condition.
	 * @param string $message   Message.
	 * @return void
	 */
	protected function assertTrue( $condition, string $message = '' ): void {
		if ( true !== $condition ) {
			$this->fail( '' === $message ? 'Condition is not true.' : $message );
		}
	}

	/**
	 * Assert a condition is false.
	 *
	 * @param bool   $condition Condition.
	 * @param string $message   Message.
	 * @return void
	 */
	protected function assertFalse( $condition, string $message = '' ): void {
		if ( false !== $condition ) {
			$this->fail( '' === $message ? 'Condition is not false.' : $message );
		}
	}

	/**
	 * Assert a value is null.
	 *
	 * @param mixed  $value   Value.
	 * @param string $message Message.
	 * @return void
	 */
	protected function assertNull( $value, string $message = '' ): void {
		if ( null !== $value ) {
			$this->fail( ( '' === $message ? 'Value is not null.' : $message ) . ' ' . self::describe( $value ) );
		}
	}

	/**
	 * Assert a value is not null.
	 *
	 * @param mixed  $value   Value.
	 * @param string $message Message.
	 * @return void
	 */
	protected function assertNotNull( $value, string $message = '' ): void {
		if ( null === $value ) {
			$this->fail( '' === $message ? 'Value is null.' : $message );
		}
	}

	/**
	 * Assert a substring is present.
	 *
	 * @param string $needle   Needle.
	 * @param string $haystack Haystack.
	 * @param string $message  Message.
	 * @return void
	 */
	protected function assertStringContains( string $needle, string $haystack, string $message = '' ): void {
		if ( false === strpos( $haystack, $needle ) ) {
			$this->fail(
				sprintf(
					"%s\nMissing: %s\nIn:      %s",
					'' === $message ? 'Substring not found.' : $message,
					$needle,
					self::describe( $haystack )
				)
			);
		}
	}

	/**
	 * Assert a substring is absent.
	 *
	 * @param string $needle   Needle.
	 * @param string $haystack Haystack.
	 * @param string $message  Message.
	 * @return void
	 */
	protected function assertStringNotContains( string $needle, string $haystack, string $message = '' ): void {
		if ( false !== strpos( $haystack, $needle ) ) {
			$this->fail(
				sprintf(
					"%s\nUnexpected: %s",
					'' === $message ? 'Substring found.' : $message,
					$needle
				)
			);
		}
	}

	/**
	 * Assert a count.
	 *
	 * @param int    $expected Expected count.
	 * @param mixed  $value    Countable value.
	 * @param string $message  Message.
	 * @return void
	 */
	protected function assertCount( int $expected, $value, string $message = '' ): void {
		$actual = is_array( $value ) ? count( $value ) : ( is_countable( $value ) ? count( $value ) : -1 );

		if ( $expected !== $actual ) {
			$this->fail( sprintf( '%s Expected %d, got %d.', '' === $message ? 'Wrong count.' : $message, $expected, $actual ) );
		}
	}

	/**
	 * Assert an array key exists.
	 *
	 * @param string              $key     Key.
	 * @param array<string,mixed> $array   Array.
	 * @param string              $message Message.
	 * @return void
	 */
	protected function assertArrayHasKey( string $key, array $array, string $message = '' ): void {
		if ( ! array_key_exists( $key, $array ) ) {
			$this->fail( sprintf( '%s Missing key "%s".', '' === $message ? 'Key not found.' : $message, $key ) );
		}
	}

	/**
	 * Assert an instance type.
	 *
	 * @param string $class   Expected class.
	 * @param mixed  $value   Value.
	 * @param string $message Message.
	 * @return void
	 */
	protected function assertInstanceOf( string $class, $value, string $message = '' ): void {
		if ( ! $value instanceof $class ) {
			$this->fail(
				sprintf(
					'%s Expected %s, got %s.',
					'' === $message ? 'Wrong type.' : $message,
					$class,
					is_object( $value ) ? get_class( $value ) : gettype( $value )
				)
			);
		}
	}

	/**
	 * Assert a value is greater than another.
	 *
	 * @param float  $expected Threshold.
	 * @param float  $value    Value.
	 * @param string $message  Message.
	 * @return void
	 */
	protected function assertGreaterThan( $expected, $value, string $message = '' ): void {
		if ( ! ( $value > $expected ) ) {
			$this->fail( sprintf( '%s Expected > %s, got %s.', '' === $message ? 'Not greater.' : $message, self::describe( $expected ), self::describe( $value ) ) );
		}
	}

	/**
	 * Assert that some code throws.
	 *
	 * @param string   $class   Expected exception class.
	 * @param callable $closure Code under test.
	 * @param string   $message Message.
	 * @return void
	 */
	protected function assertThrows( string $class, callable $closure, string $message = '' ): void {
		try {
			$closure();
		} catch ( \Throwable $e ) {
			if ( ! $e instanceof $class ) {
				$this->fail( sprintf( '%s Expected %s, got %s (%s).', '' === $message ? 'Wrong exception.' : $message, $class, get_class( $e ), $e->getMessage() ) );
			}

			return;
		}

		$this->fail( sprintf( '%s Nothing was thrown, expected %s.', '' === $message ? 'No exception.' : $message, $class ) );
	}

	/**
	 * Fail the current test.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	protected function fail( string $message ): void {
		throw new AssertionFailed( $message );
	}

	/**
	 * Human readable representation of a value.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function describe( $value ): string {
		if ( is_string( $value ) ) {
			return '"' . ( strlen( $value ) > 400 ? substr( $value, 0, 400 ) . '…' : $value ) . '"';
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( null === $value ) {
			return 'null';
		}

		if ( is_array( $value ) ) {
			return 'array(' . count( $value ) . ') ' . json_encode( $value );
		}

		if ( is_object( $value ) ) {
			return get_class( $value );
		}

		return (string) $value;
	}
}
