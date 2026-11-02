<div align="right" dir="rtl">

# Changelog

All notable changes to this project are documented here.
Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] — 2026-11-01

### Added
- **Storage Partitioning**: Partitioned architecture separating core backup engine from cloud providers; `StorageManager` is filter-driven (`backupino_storage_drivers`) with local-only storage in the free core.
- **Permanent Download Link**: Admin login-gated, non-expiring `?backupino_download=latest` URL with secure 32-byte secret token and one-click copyable UI in the admin dashboard.
- **Bot Notifications**: Telegram Bot API and Bale Bot API support for instant backup completion alerts, containing domain, size, timestamp, and download link. Includes "Send test message" action in Settings, automated retry queue, messenger-specific formatting (Markdown links for Bale and entity-safe HTML for Telegram), and post-restore notification dispatch.
- **Live Activity Log & Cancel**: Real-time modal activity log viewer for backup processes and immediate backup job cancellation support.
- **Automatic Storage Housekeeping**: Automatic cleanup of temporary jobs slices and files upon process completion or cancellation to prevent disk waste.
- **Monthly Automated Backup**: Cron-scheduled backup running on the 1st of every month, with native Jalali solar calendar for Persian locale (`fa_IR`) and Gregorian calendar for other locales (`Support\Dates`).
- **Internationalization (i18n) & RTL**: Complete gettext catalog (`backupino.pot`), full Persian translation (`backupino-fa_IR.po` & `backupino-fa_IR.mo`), native RTL styling via CSS logical properties, and strict Latin digit normalization (`Str::latin_digits`).
- **Standalone Installer Builder**: Dynamically builds `installer.php` (~72 KB) with chunked file extraction, streaming SQL dump importer, deep JSON/nested serialization search-and-replace, multi-step navigation (Back/Next), preserved source language and custom constants, robust prefix mapping preserving administrator roles, and fresh `wp-config.php` generation with unique salts.
- **Pure SQL Dumper**: Native MySQL string escaping preserving Persian slugs and UTF-8 characters without `%` placeholder corruption.
- **Release Packaging Pipeline**: `bin/build-release.php` and `ReleaseBuilder` creating clean distribution packages (`releases/backupino-{version}.zip`) stripped of dev tools, tests, and non-free code with SHA-256 verification.
- **Security QA & Audit**: Comprehensive test suite (`SecurityQATest`) verifying REST capability checks, safe output escaping, removal of `extract()`, and Zip-Slip traversal defenses.
- **Offline-First Architecture**: 100% offline-first operation with zero external CDN dependencies, remote fonts, or third-party telemetry.
- **Settings & UX**: Retention policy defaulting to 3 packages, and explicit opt-in preference for security notices.
- **WP-CLI Support**: Native commands (`wp backupino create`, `wp backupino jobs`, `wp backupino status`).

### Changed
- Default package retention set to 3 copies.
- Replaced `extract()` in view templates with explicit variable binding for WordPress.org coding standard compliance.
- Removed leftover S3/cloud UI elements and styles from the free core plugin.

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

</div>
