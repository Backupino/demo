<div align="right" dir="rtl">

# Backupino — Monorepo

> **Status:** 🟢 Phase 1 — free `v1.0.0` completed (100%) · **Tests:** 148 green (PHP 7.4 / 8.3 / 8.4)
> **Entry point for contributors/agents:** [`docs/Dev/DEVELOPMENT-RULES.md`](docs/Dev/DEVELOPMENT-RULES.md) → [`roadmaps/README.md`](roadmaps/README.md)

Backupino backs up a WordPress site — **files + database** — into one resumable package with a
standalone installer that rebuilds the site on an empty server.

## Repository layout

| Path | What lives here |
|---|---|
| [`free/`](free/) | The free plugin — **this is what ships to WordPress.org** |
| [`pro/`](pro/) | Parked commercial code (cloud drivers, licensing) — never shipped in the free zip |
| [`docs/Dev/`](docs/Dev/) | Rules, architecture, decisions, product plans |
| [`docs/Public/`](docs/Public/) | End-user documentation (Persian) |
| [`roadmaps/`](roadmaps/) | Phases, tasks and progress status |
| [`tests/`](tests/) | Dependency-free test suite (unit, integration, pro) |
| [`releases/`](releases/) | Build output (gitignored) |
| [`tools/`](tools/), [`bin/`](bin/) | Dev tooling and build scripts |

## Quick start (development)

```bash
git clone https://github.com/Backupino/dev.git && cd dev
npm install

npm test                 # full suite inside php-wasm — no PHP required
npm run test:74          # same suite on PHP 7.4
npm run build:installer  # build a standalone installer.php
```

With a local PHP binary the same commands work directly:

```bash
php tests/run.php
php tests/run.php --filter=Replacer
php bin/build-installer.php --out=releases/installer.php
```

## Using the plugin

Symlink or copy `free/` into `wp-content/plugins/backupino`, then:

```bash
wp plugin activate backupino
wp backupino create          # full site
wp backupino create --db-only
wp backupino jobs
```

## Documentation

- **Product & technical spec:** [`docs/Dev/SPEC.md`](docs/Dev/SPEC.md)
- **Architecture, modules, extension points:** [`docs/Dev/ARCHITECTURE.md`](docs/Dev/ARCHITECTURE.md)
- **Decision log (plans, limits, licensing):** [`docs/Dev/DECISIONS.md`](docs/Dev/DECISIONS.md)
- **Repository rules & dev cycle:** [`docs/Dev/REPOSITORY-STRUCTURE.md`](docs/Dev/REPOSITORY-STRUCTURE.md)
- **Free vs Pro line, quotas:** [`docs/Dev/PRODUCT-PLANS.md`](docs/Dev/PRODUCT-PLANS.md)
- **For end users:** [`docs/Public/README.md`](docs/Public/README.md)

## Contributing

Read the rules first — they are short and enforced:

1. No commit, push, PR or merge without explicit approval.
2. Work only on the session branch.
3. Docs are updated in the same change as the code.
4. No premium code in `free/`, ever.

## License

GPL-2.0-or-later. See [`LICENSE`](LICENSE).

</div>
