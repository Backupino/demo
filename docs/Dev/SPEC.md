<div align="right" dir="rtl">

# Backupino — Product & Technical Specification

Version 1.0.2 · 2026-11-04 · status: living document «roadmap revised; Phase 1 complete»

## 0. Versioning Policy

Backupino adheres to a custom SemVer rule for all source code, tags, and documentation:
- Format: Major.Minor.Patch
- **Patch:** Caps strictly at 9 (1.0.0 .. 1.0.9). After 9, rolls over to 0 and bumps Minor (1.0.9 -> 1.1.0).
- **Minor:** Unbounded non-negative integer (1.0.x, 1.1.x, 1.2.x, ..., 1.10.x, ...).
- **Major:** Caps at 9 (1.x.x through 9.x.x).

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
| Scheduling + retention | ✅ «ماهانه + retention، پیشفرض ۳» | ✅ «دلخواه» | ✅ |
| Restore in place + rollback | — | ✅ | ✅ |
| Multisite | — | ✅ | ✅ |
| Incremental packages | — | ✅ | ✅ |
| Off-site storage we host | — | ⏳ «فاز ۷» | ✅ |
| Central bot | — «فقط ربات شخصی» | ✅ «پیشفرض خاموش» | ✅ |
| Panel-driven manual backup / restore | — | ✅ «فقط مالک» | ✅ |
| Recoverable client-side encryption | — | ✅ «فاز ۷» | ✅ |

**Rule we do not break:** restoring a backup you already own is never paywalled.
**Architecture tier rule:** The Pro plugin is a lightweight **Addon** that requires the Free plugin to be active. It hooks into Free's filters/actions to enable commercial capabilities rather than shipping as a duplicate fat standalone plugin.

**Correction «2026-11-04»:** an earlier revision of this table incorrectly listed *Scheduling + retention* as Pro-only. It is implemented in Free «monthly + Jalali/Gregorian + retention» and only *arbitrary intervals* are Pro. The central bot never enters Free. Dedicated cloud is deferred to Phase 7. See [`DECISIONS.md`](DECISIONS.md) `D-013`, `D-033`, `D-041`.

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
2. **Database** — connect (mysqli, PDO fallback), create database when permissions allow, warn about
   existing tables, auto-drop tables on empty DB request, persist credentials in `backupino-install.json` (`0600`).
3. **Extract** — 300 entries per slice, zip-slip protected. Automatically quarantines server config files
   (`.htaccess`, `.user.ini`, `php.ini`, `wp-config.php`) as `.backupino-orig` to prevent web server 500 crashes on LiteSpeed/cPanel.
4. **Import** — streaming SQL statement splitter; time-budgeted execution; packet-size auto-splitting for giant multi-row INSERTs;
   automatic recovery from MariaDB/InnoDB orphaned tablespaces and NTFS file lock collisions.
5. **URL rewrite** — deep keyset-paginated replacement across serialized arrays/objects and JSON structures;
   preserves Elementor dynamic tag syntax (`settings=\"%7B%7D\"`); preserves and auto-heals UTF-8 Persian slugs and URLs.
6. **Config** — writes fresh `wp-config.php` preserving custom constants from original config, generates secure salts,
   configures valid WordPress `.htaccess` with target `RewriteBase`, and detects custom login URLs.
7. **Cleanup** — securely removes installer script, archive, database dump, state file, and temporary `.backupino-orig` files.

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
| 1 | Free v1.0.x «v1.0.2: 12/12 done» | ✅ done |
| 2 | Contract gate: Ed25519 license, API v1, privacy, restore/rescue contract «15/15 done» | ✅ done |
| 3 | Official WordPress site: Elementor Pro, WooCommerce, panel, custom renewal | ⏳ todo |
| 4 | Pro addon, rescue agent, two-phase panel restore, customer cloud drivers | ⏳ todo |
| 5 | Independent central bot «Telegram → Bale, bilingual» | ⏳ todo |
| 6 | Integration, release gate, live-WordPress E2E | ⏳ todo |
| 7 | Dedicated Backupino cloud + recoverable encryption «post-launch» | ⏳ todo |

**Canonical roadmap:** [`roadmaps/README.md`](../../roadmaps/README.md). Phase 2 must close before Phases 3/4; Phases 3 and 4 may run in parallel; Phase 7 starts only after 3+4+5+6.
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

---

## 12. مسیر فازهای ۲ تا ۷ «خلاصهٔ مرجع»

```text
فاز ۲ Gate «قرارداد» ──► فاز ۳ «سایت» ┐
                         ├─► فاز ۵ «ربات» ──► فاز ۶ «انتشار/E2E» ──► فاز ۷ «ابر اختصاصی»
                         └─► فاز ۴ «Pro»  ┘
```

| موضوع | تصمیم قفلشده |
|---|---|
| لایسنس | `Ed25519` + مدل `B` + کش ۷روزه + heartbeat اختیاری «`D-031`/`D-032`» |
| هستهٔ رایگان | هرگز پنل را صدا نمیزند؛ ربات مرکزی فقط Pro «`D-033`» |
| سایت | `en_US` دیفالت + `fa_IR` ۱۰۰٪ + ۲ فونت محلی + توکن واحد «`D-034`…`D-036`» |
| ریستور پنلی | دستی، فقط مالک، `Shadow → تأیید → Commit → تأیید → Rollback`، اسنپشات ۴۸h «`D-042`/`D-043`» |
| ریکاوری | `wrapped_DEK` + Shamir ۲از۳ برای همه «`D-044`» |
| قانون فاز ۱ | هر تغییر `free/` تسک فاز ۱ میگیرد؛ `P1-T12` ثبت شد «`D-045`» |

تست مرجع: **۱۵۲** «`D-047`». این سند در شاخهٔ طراحی نقشهٔ راه فقط **مستنداتی** بهروز شد؛ هیچ کد `free/` تغییر نکرده است.

</div>
