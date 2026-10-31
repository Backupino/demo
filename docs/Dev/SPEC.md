# Backupino — Product & Technical Specification

Version 0.1 · 2026-10-31 · status: living document

---

## 1. Goal

Give WordPress site owners a **fast, honest, self-contained** way to (a) move a site to another host,
(b) keep scheduled backups, and (c) store them on storage they control — including S3 providers that
Duplicator and UpdraftPlus do not support well (ArvanCloud, Liara, ParsPack, MinIO).

Three jobs, one architecture:

| Job | Entry point | Output |
|---|---|---|
| Migrate / clone | Admin button, CLI | `site.zip` + `installer.php` |
| Scheduled backup | WP-Cron | Same package, stored on the configured destination |
| Disaster restore | `installer.php` upload | Working site on an empty server |

## 2. Product tiers

| | Free | Pro | Dedicated cloud |
|---|---|---|---|
| Manual full / DB-only packages | ✅ | ✅ | ✅ |
| Standalone installer + URL rewrite | ✅ | ✅ | ✅ |
| Local storage | ✅ | ✅ | — |
| Cloud storage (S3 / Drive / download hosts) | — *(no cloud code in the free plugin)* | ✅ | ✅ |
| Scheduling + retention | — | ✅ | ✅ |
| Restore in place + rollback | — | ✅ | ✅ |
| Multisite | — | ✅ | ✅ |
| Incremental packages | — | ✅ | ✅ |
| Off-site storage we host | — | — | ✅ |

**Rule we do not break:** restoring a backup you already own is never paywalled.
**Architecture tier rule:** The Pro plugin is a lightweight **Addon** that requires the Free plugin to be active. It hooks into Free's filters/actions to enable commercial capabilities rather than shipping as a duplicate fat standalone plugin.

## 3. Package format

```
<name>.zip
  ├── (WordPress files, paths relative to ABSPATH)
  ├── database.sql                 ← only when the DB is included
  ├── backupino-manifest.ndjson    ← one JSON object per file: {"p":relative,"s":size}
  └── backupino-package.json       ← generator, version, created, site url, table prefix
<name>-installer.php               ← generated, self-contained, ~70 KB
```

Invariants:

1. `wp-config.php` is **never** copied; the installer writes a fresh one (with new salts).
2. Paths inside the archive are always relative and forward-slashed — no absolute paths leak.
3. `installer.php` embeds: package name, SHA-256 of the archive, size, old site URL, table prefix,
   a random 32-char token and an optional password.
4. The installer refuses to run against a different archive (hash pairing).
5. Both files must live in the same directory at the destination.
6. 100% self-contained & offline-ready: zero external CDN or remote runtime dependencies; operates completely even without international internet access.

## 4. Build pipeline (source site)

Stages, each resumable, each time-boxed (`runtime.time_budget`, default 20 s):

| # | Stage | Weight | Resumable by |
|---|---|---|---|
| 1 | `scan` | 8 % | directory stack persisted in the job payload |
| 2 | `files` | 50 % | byte offset into `manifest.ndjson`; zip reopened each tick |
| 3 | `database` | 32 % | per-table `(index, phase, cursor, offset, bytes)` |
| 4 | `finalize` | 10 % | explicit sub-steps: seal → hash → installer → publish |

Everything is stored in `wp_backupino_jobs` (`payload` JSON), so a build survives a closed browser, a
timeout, or a PHP fatal — the next cron run or admin poll continues from the last committed cursor.

### 4.1 Scanning

- Explicit stack of directories (never `RecursiveDirectoryIterator` recursion).
- Skips symlinks, the plugin's own storage directory (no recursion into backups), and any path matching
  a filter rule.
- Rules support `*`, `**`, `?`, directory-only patterns (`cache/`), basename patterns (`*.log`).

### 4.2 Archiving

- `ZipArchive` (libzip) is reopened every tick and appended to — safe, constant memory.
- Compression configurable; `PclZip` fallback is planned for hosts without `zip`.
- Unreadable files are logged and skipped, never fatal.

### 4.3 Database

- `SHOW FULL TABLES` → base tables + views.
- Schema: `DROP … IF EXISTS` + `SHOW CREATE TABLE` (with `AUTO_INCREMENT=…` stripped for diffability).
- Data: keyset pagination on a single-column numeric primary key; `LIMIT/OFFSET` only as a fallback.
- Binary columns are written as `0x…` hex literals; numeric columns unquoted.
- Triggers and routines are dumped with `DEFINER` stripped.
- Header sets `utf8mb4`, `NO_AUTO_VALUE_ON_ZERO`, `time_zone=+00:00`, FK/unique checks off.

## 5. Installer (destination site)

Single PHP file, no WordPress, no Composer. Steps are separate AJAX calls so no step can time out:

1. **Environment** — PHP ≥ 7.4, `json`/`zlib`/`zip`, `mysqli` or `pdo_mysql`, writable dir, free disk
   ≥ 2 × archive, archive SHA-256 matches the embedded hash.
2. **Database** — connect (mysqli, PDO fallback), create the database when the user may, warn about
   existing tables, persist credentials in `backupino-install.json` (`0600`).
3. **Extract** — 200 entries per request, zip-slip protected (`..`, absolute paths, drive letters,
   null bytes rejected).
4. **Import** — statement splitter that understands strings, backticks, `--`/`#`/`/* */` comments;
   resume by byte offset; error messages include a statement preview.
5. **URL rewrite** — for every text column of every table: rows read by keyset, values replaced with a
   serialized/JSON-aware replacer, `UPDATE` issued only when the value actually changed.
6. **Config** — writes `wp-config.php` (with a timestamped backup of any previous file), fresh salts,
   updates `siteurl`/`home`, then offers to delete the installer, archive, dump and state file.

Security model:

- Every AJAX action requires the embedded token (`hash_equals`).
- Optional installer password (stored hashed? **no**: it is compared with `hash_equals` against the
  value injected at build time; the package itself is the secret).
- The state file is `0600`, deleted at the end; the installer self-deletes on request.
- `X-Robots-Tag: noindex`, `Cache-Control: no-store` on every response.

## 6. Search & replace

The single most dangerous operation in a migration. Implementation:

1. Parse the value with a recursive-descent parser for PHP's `serialize()` format
   (arrays, objects, custom serializable, all scalars).
2. Rewrite **every string leaf** (and array key) recursively; lengths are recomputed on render.
3. If the value is not serialized, try JSON (`json_decode` → walk → `json_encode` with
   `JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE`).
4. Otherwise a plain `str_replace`.
5. Broken payloads fall back to a plain replace instead of producing corrupt data.

`Replacer::from_urls()` automatically covers `http`/`https` and `www`/non-`www` variants.

## 7. Security checklist

- [x] Capability `manage_options` (filterable: `backupino_manage_capability`)
- [x] REST nonce + `permission_callback` on every route
- [x] Downloads require `check_admin_referer` and sanitized file names
- [x] Storage directory protected with `.htaccess` + `index.php`
- [x] No raw SQL built from user input; all identifiers quoted, all values escaped
- [x] Zip-slip protection on both sides (plugin reader + installer)
- [x] Installer token pairing + optional password + self-delete
- [ ] Encryption of the package (AES-256) — v0.3
- [ ] Signed installer payload — v0.3

## 8. Compatibility matrix

| | Minimum | Tested in CI |
|---|---|---|
| PHP | 7.4 | 7.4 → 8.4 |
| WordPress | 6.0 | — (needs a WP harness, roadmap) |
| MySQL | 5.7 | fake driver |
| MariaDB | 10.2 | fake driver |
| Browsers | evergreen | — |

Shared hosting constraints handled: no `exec`, no `mysqldump`, low `memory_limit`, `max_execution_time`
≥ 30 s, no `ZipArchive` (fallback planned), no cURL (WP HTTP fallback for S3).

## 9. REST API

Base: `/wp-json/backupino/v1/`

| Method | Route | Purpose |
|---|---|---|
| `GET` | `/jobs` | recent jobs |
| `POST` | `/jobs` | create a job (`files`, `database`, `note`) |
| `GET` | `/jobs/{id}` | job status |
| `POST` | `/jobs/{id}/step` | advance one slice; client polls until terminal |
| `POST` | `/jobs/{id}/cancel` | cancel |
| `DELETE` | `/jobs/{id}` | delete job + package files |
| `GET`/`POST` | `/settings` | read / merge settings |
| `GET` | `/system` | environment, log tail, packages |
| `POST` | `/system/storage-test` | write+delete a probe object |

## 10. Roadmap

| Phase | Scope | State |
|---|---|---|
| 0 | Spec, skeleton, CI, test harness | ✅ done |
| 1 | MVP: scan → zip → dump → installer (single site) | ✅ done |
| 2 | Resume/cron hardening, chunked archive format, CLI parity | 🔜 next |
| 3 | S3 hardening (multipart, list, retention in bucket), encryption | ⏳ |
| 4 | Restore in place + rollback + pre-restore snapshot | ⏳ |
| 5 | Multisite, incremental packages, import-from-URL, Persian UX polish | ⏳ |

## 11. Decisions log

| # | Decision | Why |
|---|---|---|
| 1 | No Composer at runtime | must work on any shared host, plugin is shipped as-is |
| 2 | Own SigV4 signer instead of aws-sdk-php | SDK is ~40 MB; the algorithm is 200 lines and unit-tested |
| 3 | NDJSON manifest instead of one JSON | append-only, resumable by byte offset, constant memory |
| 4 | Database inside the archive (not a second file) | one object to upload, impossible to mismatch |
| 5 | Installer built by flattening real classes | one implementation, still unit-testable |
| 6 | Keyset pagination everywhere | `LIMIT offset` degrades linearly and breaks on gaps |
| 7 | Token pairing instead of archive name matching | prevents “wrong installer for this archive” disasters |
