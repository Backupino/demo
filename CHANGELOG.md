# Changelog

All notable changes to this project are documented here.
Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Notification channels: Telegram and Bale (personal bot) with the permanent download link,
  a "send test message" button in Settings, and a retry queue (5 messages, 3 attempts)
  flushed on the next cron run.
- Permanent "latest backup" download link (`?backupino_download=latest`), login-gated
  and never expiring, with a copy-to-clipboard field on the dashboard.
- Monthly automatic backup (first day of the month; Jalali for `fa_IR`).

### Changed
- Coding standards are now enforced and clean: `phpcs.xml.dist` passes with zero
  errors and zero warnings; every deliberate exclusion is documented inline.
- Repository partitioned; free plugin carries no cloud code.

### Planned for 1.0.0
- Persian translation and RTL admin.
- Release build script producing a clean WordPress.org zip.

## [0.1.0] — 2026-10-31 (internal)

### Added
- Resumable job engine: `scan → files → database → finalize`, state persisted per stage.
- NDJSON manifest, append-mode ZIP writer, keyset-paginated SQL dumper, resumable importer.
- Standalone `installer.php` (~72 KB) generated from the real, unit-tested installer classes.
- Serialized/JSON aware search & replace.
- Admin screens, REST API, WP-CLI commands, cron scheduler, retention, structured logging.
- Dependency-free test suite (86 tests) that runs on PHP 7.4 → 8.4 via php-wasm.
- GitHub Actions: PHP matrix, PHPCS (WordPress ruleset), installer build artifact.

### Changed
- Repository partitioned into `free/`, `pro/`, `docs/`, `tests/`, `roadmaps/`, `releases/`.
- Cloud storage moved out of the free plugin: `StorageManager` is now filter driven
  (`backupino_storage_drivers`); the S3 driver lives in `pro/`.
