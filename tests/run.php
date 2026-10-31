<?php
/**
 * Dependency free test runner.
 *
 * Usage:
 *   php tests/run.php
 *   php tests/run.php --filter=Replacer
 *   php tests/run.php --list
 *
 * @package Backupino
 */

declare( strict_types = 1 );

require_once __DIR__ . '/bootstrap.php';

$backupino_args   = isset( $_SERVER['argv'] ) && is_array( $_SERVER['argv'] ) ? $_SERVER['argv'] : array();
$backupino_filter = '';
$backupino_list   = false;

foreach ( array_slice( $backupino_args, 1 ) as $backupino_arg ) {
	if ( 0 === strpos( (string) $backupino_arg, '--filter=' ) ) {
		$backupino_filter = substr( (string) $backupino_arg, 9 );
	}

	if ( '--list' === $backupino_arg ) {
		$backupino_list = true;
	}
}

$backupino_files = array_merge(
	glob( __DIR__ . '/Unit/*Test.php' ) ?: array(),
	glob( __DIR__ . '/Integration/*Test.php' ) ?: array(),
	glob( __DIR__ . '/Pro/*Test.php' ) ?: array()
);

sort( $backupino_files );

$backupino_classes = array();

foreach ( $backupino_files as $backupino_file ) {
	$backupino_before = get_declared_classes();

	require_once $backupino_file;

	foreach ( array_diff( get_declared_classes(), $backupino_before ) as $backupino_class ) {
		if ( is_subclass_of( $backupino_class, \Backupino\Tests\TestCase::class ) ) {
			$backupino_classes[] = $backupino_class;
		}
	}
}

$backupino_total   = 0;
$backupino_failed  = 0;
$backupino_skipped = 0;
$backupino_errors  = array();

echo "Backupino test suite (PHP " . PHP_VERSION . ")\n";
echo str_repeat( '=', 62 ) . "\n";

foreach ( $backupino_classes as $backupino_class ) {
	$backupino_reflection = new ReflectionClass( $backupino_class );
	$backupino_short      = $backupino_reflection->getShortName();

	if ( '' !== $backupino_filter && false === stripos( $backupino_short, $backupino_filter ) ) {
		continue;
	}

	if ( $backupino_list ) {
		echo $backupino_short . "\n";
		continue;
	}

	echo "\n" . $backupino_short . "\n";

	foreach ( $backupino_reflection->getMethods( ReflectionMethod::IS_PUBLIC ) as $backupino_method ) {
		if ( 0 !== strpos( $backupino_method->getName(), 'test' ) ) {
			continue;
		}

		if ( $backupino_method->getDeclaringClass()->getName() !== $backupino_class ) {
			continue;
		}

		$backupino_total++;
		$backupino_instance = new $backupino_class();

		$backupino_setup = $backupino_reflection->getMethod( 'setUp' );
		$backupino_setup->setAccessible( true );
		$backupino_setup->invoke( $backupino_instance );

		$backupino_started = microtime( true );

		try {
			$backupino_method->invoke( $backupino_instance );
			$backupino_status = 'pass';
		} catch ( \Backupino\Tests\AssertionFailed $backupino_failure ) {
			$backupino_status = 'fail';
			$backupino_errors[] = array( $backupino_short . '::' . $backupino_method->getName(), $backupino_failure->getMessage() );
			$backupino_failed++;
		} catch ( \Throwable $backupino_exception ) {
			$backupino_status = 'error';
			$backupino_errors[] = array(
				$backupino_short . '::' . $backupino_method->getName(),
				get_class( $backupino_exception ) . ': ' . $backupino_exception->getMessage()
				. "\n  at " . $backupino_exception->getFile() . ':' . $backupino_exception->getLine(),
			);
			$backupino_failed++;
		}

		$backupino_teardown = $backupino_reflection->getMethod( 'tearDown' );
		$backupino_teardown->setAccessible( true );
		$backupino_teardown->invoke( $backupino_instance );

		if ( method_exists( $backupino_instance, 'cleanup' ) ) {
			$backupino_instance->cleanup();
		}

		$backupino_duration = (int) round( ( microtime( true ) - $backupino_started ) * 1000 );
		$backupino_marker   = 'pass' === $backupino_status ? 'ok  ' : 'FAIL';

		printf(
			"  [%s] %-52s %4dms\n",
			$backupino_marker,
			$backupino_method->getName(),
			$backupino_duration
		);
	}
}

if ( $backupino_list ) {
	exit( 0 );
}

echo "\n" . str_repeat( '=', 62 ) . "\n";

if ( array() !== $backupino_errors ) {
	echo "\nFailures:\n";

	foreach ( $backupino_errors as $backupino_error ) {
		echo "\n  - " . $backupino_error[0] . "\n    " . str_replace( "\n", "\n    ", $backupino_error[1] ) . "\n";
	}
}

printf(
	"\n%d tests, %d failures, %d skipped\n",
	$backupino_total,
	$backupino_failed,
	$backupino_skipped
);

exit( $backupino_failed > 0 ? 1 : 0 );
