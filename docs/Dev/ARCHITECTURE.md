# Architecture

## 1. Principles

1. **Pure classes first.** Business logic (`Backup`, `Jobs`, `Replace`, `Installer`) never calls a
   WordPress function unless it has to. WordPress enters through thin adapters (`WpOptions`,
   `WpDbAdapter`, `Admin`, `Cli`).
2. **Everything is resumable.** Any step may be interrupted between two PHP requests; state lives in
   the job payload, not in memory.
3. **Small, named units.** One class, one job. No god objects; `Plugin` only wires things together.
4. **Defensive I/O.** A missing/unreadable/locked file is a logged warning, not an exception.

## 2. Module map

```
Plugin (container)
 ├── Core\Settings       (dot-notation settings, defaults, sanitisation)
 ├── Core\Paths          (storage layout + web guards)
 ├── Core\Db  ← WpDbAdapter ($wpdb)      (used by jobs, dumper; faked in tests)
 ├── Support\Logger      (file log, rotation)
 ├── Support\Clock       (time source; fakeable)
 ├── Jobs\JobRepository  (wp_backupino_jobs)
 ├── Jobs\JobRunner      (one time-boxed slice per call)
 │    └── Jobs\Pipeline → Stages: Scan → Files → Database → Finalize
 ├── Storage\StorageManager → LocalStorage | S3Storage (S3\S3Client + S3\S3Signer)
 ├── Download\PublicDownload  (permanent link: key + login + latest package)
 ├── Notify\Notifier → ChannelFactory → BotApi (telegram | bale) + Queue (retry)
 ├── Admin\Admin         (menu, assets, REST, admin download handler)
 ├── Cli\Commands        (wp backupino …)
 └── Cron\Scheduler + Retention
```

### Data flow of one job

```
POST /jobs ─► Job(queued) ─► step() ─► ScanStage   → manifest.ndjson
                              step() ─► FilesStage  → archive.zip  (append per tick)
                              step() ─► DatabaseStage → database.sql (append per tick)
                              step() ─► FinalizeStage → seal, hash, installer.php, upload
                                        └─► Job(completed) + payload.package
                                        └─► do_action( 'backupino_job_completed' )
                                              └─► Notify\Notifier → Telegram/Bale (+ retry queue)
```

The browser (or cron, or CLI) simply keeps calling `step()` until the job reaches a terminal status.
`JobRunner` guarantees: on exception → `failed` with a message, never a half-written status.

## 3. State machines

### Job status

```
queued ──► running ──► completed
              │
              ├──► failed      (exception, JobException, or unknown stage)
              └──► cancelled   (user action)
```

### Stage cursor

Each stage stores its own cursor under `payload.stages.<id>`:

| Stage | Cursor |
|---|---|
| scan | `stack[]`, `files`, `bytes`, `done` |
| files | `offset` (bytes into manifest), `added`, `failed`, `total`, `done` |
| database | `bytes`, `dumper{ index, phase, cursor, offset, rows, done }` |
| finalize | `step` (`seal` → `hash` → `installer` → `publish` → `done`) |

**Invariant:** a stage may be re-entered at any time and must be idempotent. Write-ahead files
(`database.sql`, `archive.zip`) are truncated to the last committed byte count before continuing.

## 4. The installer

`src/Installer/*` are ordinary classes in `Backupino\Installer`. `InstallerBuilder`:

1. concatenates `functions.php`, `Replace/SerializedParser.php`, `Replace/Replacer.php`, then the
   installer classes in dependency order;
2. strips `<?php`, `declare(...)`, `namespace …;` and `use …;` so everything lands in one namespace;
3. injects the package payload (base64 JSON) into the template;
4. the result must parse (`token_get_all(…, TOKEN_PARSE)` — asserted in tests and CI).

That gives us one implementation that is unit-testable *and* shippable as a single 70 KB file.

## 5. Testing strategy

| Layer | How |
|---|---|
| Pure logic | direct unit tests (`Settings`, `FileFilter`, `Replacer`, `S3Signer`, `SqlSplitter`, …) |
| Databases | `Tests\Support\FakeDb` (scripted SQL) and `FakeConnection` — no MySQL needed |
| Filesystem | real temp trees, real zips (`ZipArchive` is available in php-wasm too) |
| Whole pipeline | `tests/Integration/BackupPipelineTest` builds a real package and inspects archive + installer |
| Hygiene | `SyntaxTest` parses every PHP file and enforces `strict_types` + docblocks |
| CI | PHP 7.4 → 8.4 matrix, PHPCS (WordPress ruleset), installer build artifact |

Run locally: `php tests/run.php`. Without PHP: `npm test` (php-wasm).

## 6. Extension points

| Need | Hook / filter |
|---|---|
| Change who can manage backups | `backupino_manage_capability` |
| Add a storage driver | implement `Storage\Storage` + register in `StorageManager::driver_for()` |
| Add a pipeline stage | implement `Jobs\Stage`, append in `Plugin::pipeline()` |
| Change the schedule | settings `schedule.interval` (`hourly`, `twicedaily`, `daily`, `weekly`) |
| Exclude more files | settings `archive.exclude_rules` (glob syntax) |

## 7. Known limits (0.1.0)

- ZIP only; archives > 4 GB depend on libzip/Zip64 support of the host.
- Single-site only; multisite tables are dumped but URLs of sub-sites are not rewritten.
- No encryption yet; the local storage directory is protected by `.htaccess`/`index.php` only.
- S3 uploads are single-PUT (no multipart resume yet); objects > 5 GB are rejected by S3 itself.
- Restore in place (over a live site) is not implemented; the installer restores *to* a directory.
