#!/usr/bin/env node
/**
 * Runs a PHP script inside php-wasm.
 *
 * The sandbox (and any machine without a PHP binary) can therefore execute the
 * real test suite against a real PHP 8.3 build with zip/mysqli/openssl enabled.
 *
 * Usage:
 *   node tools/run-php.mjs tests/run.php
 *   node tools/run-php.mjs tests/run.php --filter=Replacer
 */

import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime } from '@php-wasm/node';

const here = dirname(fileURLToPath(import.meta.url));
const repo = resolve(here, '..');
const script = process.argv[2] || 'tests/run.php';
const scriptArgs = process.argv.slice(3);

const version = process.env.BACKUPINO_PHP || '8.3';

const php = new PHP(await loadNodeRuntime(version, { emscriptenOptions: { processId: 1 } }));

await php.mount('/plugin', (phpInstance, FS, mountPoint) => {
	FS.mkdir(mountPoint);
	FS.mount(FS.filesystems.NODEFS, { root: repo }, mountPoint);
	return () => FS.unmount(mountPoint);
});

const boot = `<?php
error_reporting( E_ALL );
ini_set( 'display_errors', '1' );
chdir( '/plugin' );
$GLOBALS['__argv'] = json_decode( '__ARGS__', true );
$_SERVER['argv'] = $GLOBALS['__argv'];
$_SERVER['argc'] = count( $GLOBALS['__argv'] );
$argv = $_SERVER['argv'];
$argc = $_SERVER['argc'];
require '/plugin/${script}';
`.replace('__ARGS__', JSON.stringify([script].concat(scriptArgs)).replace(/'/g, "\\'"));

const result = await php.runStream({ code: boot });

const out = await result.stdoutText;
const err = await result.stderrText;

process.stdout.write(out || '');
process.stderr.write(err || '');

const code = await (typeof result.exitCode?.then === 'function' ? result.exitCode : Promise.resolve(result.exitCode));
process.exit(code ?? 0);
