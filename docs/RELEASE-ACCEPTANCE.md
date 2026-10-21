# 84PAD Core 0.1 — Release acceptance gate

**Status: STAGING PREVIEW. Not production ready.** No checkbox is considered complete solely because a file was committed or a draft PR exists. The completed automated checks below apply to the recorded GitHub Actions run only.

## Automated checks (passing)

- [x] Laravel 12 runtime, Core migrations and PHPUnit on PHP 8.3 and 8.4 — [run #39686927563](https://github.com/manbir6-ux/84pad-core/actions/runs/39686927563)
- [x] MySQL 8 compatibility, disposable no-SSH bootstrap and concurrent CLI Owner install — [run #39686927537](https://github.com/manbir6-ux/84pad-core/actions/runs/39686927537)
- [x] Create staging ZIP with runtime `vendor`, `.env.example`, PHP bootstrap and NO `.env` secrets — [run #39686927599](https://github.com/manbir6-ux/84pad-core/actions/runs/39686927599)
- [x] Token/replay guards, Owner login/password rotation, permission tests, signed local plugin activation, quarantine/restore and create-only schema ledger have automated test coverage
- [x] Source-only snapshot creation + checksum verification / corruption rejection, signed Core update ZIP dry-run inspection

## Required before marking Core production complete

- [ ] Commit, verify and audit `composer.lock` to make official builds reproducible. A generated lockfile exists inside the staging ZIP only.
- [ ] Provision a disposable cPanel/Apache/PHP 8.3/8.4 staging domain with correct document root pointing to `public/` and TLS; test fresh File Manager upload, private token and database setup with real cPanel MySQL privileges.
- [ ] Browser acceptance: installer, owner login, password changes, Admin/View roles, plugin installation, activation, quarantine/restore on desktop and mobile; inspect accessibility and reduced-motion rendering.
- [ ] Independent security review: web bootstrap CSRF/session/rate limits, empty DB assumptions, proxy TLS, file/path permissions, trusted-key management, plugin code privilege boundary and concurrent restore/install races.
- [ ] Add secure **full-site** backup covering DB, plugins, uploads, vendor, environment and data; test off-host encrypted recovery. Source-only snapshot is NOT sufficient.
- [ ] Implement and test signed application of an update, maintenance mode, migrations/recovery and atomic rollback or documented irreversible migration gates.
- [ ] Define and test plugin schema migration upgrades/reversion (or explicitly freeze create-only schema contract for the first production release), with MySQL DDL nontransactionality handled.
- [ ] Version and cryptographically sign the actual production distribution package, test clean install and upgrade from previous version, then merge reviewed PR to `main`.
- [ ] Conduct post-deploy smoke tests on production-like cPanel hosting with real browser sessions, preserving existing domains and data.

## Scope held outside Core

SSL God, domain email, file manager, daily backups as a user-facing service, website builder, themes, SEO and store remain separately installable plugins. The future central release server is intentionally deferred. Do not mark these extras as in-Core functionality.

## Handoff

Branch: `core/C01-laravel-foundation`; draft PR #17. Main remains unchanged. Resume with C14 full-site recovery + signed update apply design, then C15 live-host security/visual acceptance. Current evidence is in [PROGRESS.md](PROGRESS.md).