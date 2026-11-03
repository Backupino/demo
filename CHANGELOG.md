<div align="right" dir="rtl">

# Changelog

All notable changes to this project are documented here.
Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
this project adheres to **Backupino's Custom Versioning Specification**:
- **Format:** `Major.Minor.Patch`
- **Patch (0..9):** Capped strictly at 9. Rolling over from 9 resets Patch to 0 and increments Minor (`1.0.0` .. `1.0.9` $\to$ `1.1.0`).
- **Minor (0..∞):** Unbounded integer representing feature iterations and milestone rollouts (`1.9.9` $\to$ `1.10.0` $\to$ ...).
- **Major (1..9):** Capped at 9, reserved for foundational architecture redesigns.

## [1.0.1] — 2026-11-03

### Fixed
- **Fatal Error Prevention in JobRunner**: Replaced nonexistent method call `Fs::mkdir()` with defensive `Fs::ensure_dir()` in `JobRunner::step()` (line 122), ensuring backup jobs initialize flawlessly on fresh WordPress installations.
- **Lock Management & Clean Releasing**: Added comprehensive lock release (`LOCK_UN`) and `.lock` file unlinking in `JobRunner` on pipeline stage lookup failure and job failures, preventing orphaned lock files on disk.
- **WP-Cron Manual Job Detection**: Fixed `Scheduler::run_jobs()` to read `$job->option('type', 'manual')` instead of undefined property `$job->type`, and ensured `RestController::create_job()` sets default `'manual'` type when not explicitly supplied by browser requests.

### Security
- **Nginx Installer Execution Shield**: Standalone installer (`installer-template.php`) now enforces a strict 403 Forbidden guard if executed inside `wp-content/`, completely thwarting unauthenticated installer execution on Nginx servers.
- **Cryptographic Package Suffix**: Package filenames generated via `Package::default_name()` now incorporate a 12-character random hexadecimal suffix (`Str::random(6)`), eliminating any predictable URL enumeration on web servers lacking `.htaccess` support.
- **Ephemeral Installer Storage**: Removed persistent `packages/installer.php` storage in `FinalizeStage`, streaming installer files directly through authenticated download endpoints with clean `installer.php` headers.

## [1.0.0] — 2026-11-03

### Added
- **7-Step Standalone Installer Wizard**: Completely self-contained deployment engine (~72 KB) featuring dynamic multi-step wizardry:
  - Step 1: Pre-flight system requirement validation (PHP version, ZipArchive, write permissions).
  - Step 2: Database connection testing & MySQL credential verification.
  - Step 3: Database options & custom table prefix mapping (e.g. `jvd_` or custom prefixes) with automatic user capabilities & option prefix synchronization to eliminate admin lockouts.
  - Step 4: Chunked archive file extraction & streaming SQL dump importer with memory resilience and live progress metrics.
  - Step 5: Deep serialized & JSON-aware search-and-replace, preserving Elementor dynamic tags (`@elementor-tag-data:...@`) and raw / URL-encoded UTF-8 Persian slugs with automatic string length recalculation, plus clean `wp-config.php` and `.htaccess` generation.
  - Step 6: Administrator account setup & verification with option to update or create admin credentials.
  - Step 7: Final summary, deployment verification, one-click admin login, and automated self-cleanup of installer runtime assets.
- **Server Config Quarantine Extraction**: Intelligent protection during archive extraction isolating `.htaccess`, `.user.ini`, `php.ini`, and `wp-config.php` as `.backupino-orig` to prevent LiteSpeed/cPanel HTTP 500 Internal Server Errors from incompatible source host directives.
- **Dual Installer Artifacts**: Automatic generation of both standardized `installer.php` and uniquely timestamped `[site]_[timestamp]-installer.php` in the backup packages directory.
- **Independent Messenger Controls**: Decoupled toggle switches (`telegram_enabled` and `bale_enabled`) allowing Telegram and Bale bots to be enabled, tested, or disabled independently.
- **Database Import Resilience**: Auto-recovery against MariaDB/InnoDB Error 1813 (`Tablespace exists`) via automatic tablespace discarding, multi-row `INSERT` statement batching for low `max_allowed_packet` hosts, and foreign key constraint safety (`FOREIGN_KEY_CHECKS=0`).
- **Full WordPress Plugin Check (PCP) Compliance**: 100% clean check across all 5 official WordPress.org Plugin Check categories with zero errors and zero warnings.
- **Storage Partitioning**: Partitioned architecture separating core backup engine from cloud providers; `StorageManager` is filter-driven (`backupino_storage_drivers`) with local-only storage in the free core.
- **Permanent Download Link**: Admin login-gated, non-expiring `?backupino_download=latest` URL with secure 32-byte secret token and one-click copyable UI in the admin dashboard.
- **Bot Notifications**: Telegram Bot API and Bale Bot API support for instant backup completion alerts, containing domain, size, timestamp, and download link. Includes "Send test message" action in Settings, automated retry queue, messenger-specific formatting (Markdown links for Bale and entity-safe HTML for Telegram), and post-restore notification dispatch.
- **Live Activity Log & Cancel**: Real-time modal activity log viewer for backup processes and immediate backup job cancellation support.
- **Automatic Storage Housekeeping**: Automatic cleanup of temporary jobs slices and files upon process completion or cancellation to prevent disk waste.
- **Monthly Automated Backup**: Cron-scheduled backup running on the 1st of every month, with native Jalali solar calendar for Persian locale (`fa_IR`) and Gregorian calendar for other locales (`Support\Dates`).
- **Internationalization (i18n) & RTL**: Complete gettext catalog (`backupino.pot`), full Persian translation (`backupino-fa_IR.po` & `backupino-fa_IR.mo`), native RTL styling via CSS logical properties, and strict Latin digit normalization (`Str::latin_digits`).
- **Release Packaging Pipeline**: `bin/build-release.php` and `ReleaseBuilder` creating clean distribution packages (`releases/backupino-{version}.zip`) stripped of dev tools, tests, and non-free code with SHA-256 verification.
- **Security QA & Audit**: Comprehensive test suite (`SecurityQATest`) verifying REST capability checks, safe output escaping, removal of `extract()`, and Zip-Slip traversal defenses.
- **Offline-First Architecture**: 100% offline-first operation with zero external CDN dependencies, remote fonts, or third-party telemetry.
- **WP-CLI Support**: Native commands (`wp backupino create`, `wp backupino jobs`, `wp backupino status`).

### Changed
- Upgraded standalone installer architecture from 5-step prototype to complete 7-step production lifecycle.
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
