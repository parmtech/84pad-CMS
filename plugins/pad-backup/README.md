# 84PAD Backup 0.1 — staging preview

First-party ParmTech plugin, slug `pad-backup`. Intended for Laravel 12/PHP 8.3+ with `zip`, `sodium`, `pdo_mysql` and MySQL/MariaDB. It has no cPanel token or network dependency.

## Features

- Manual full-installation snapshot: Core directories, plugins, installed vendor runtime, media, `.env` and a **base-table** MySQL dump (not stored routines/triggers/views/events).
- Per-file SHA-256 checked ZIP inside a libsodium secretstream container. Private directory `storage/app/private/pad-backups` is owner-only (not web-accessible). Encryption key uses HKDF with the **current site's APP_KEY** and a random per-archive salt.
- Owner-only dashboard at `/dashboard/extensions/pad-backup`, after activation.
- Automatic hourly/daily/weekly/monthly/off option; schedule state persisted in private JSON; `60 days` retained from snapshot creation; automatic removal via cron ticks. No per-plugin tables/migrations.
- Portable ZIP export (decrypted, includes secrets), ZIP import (validate first, then encrypt at rest), integrity verification, delete.
- Full site restore **NOT IMPLEMENTED**. Import only registers a safe, verified local snapshot and never executes SQL or overwrites files.

## Current Core integration

This branch contains a **proposed unmerged Core extension bridge** for `core.plugin_request` and `core.schedule_tick`. These are **not stable released contracts**. Core 0.1 `main` lacks them. The signed plugin upload path requires a detached ParmTech Ed25519 signature and trusted publisher public key (not provided in this unsigned development source).

1. Install plugin in Core `plugins/pad-backup`, activate through Core.
2. Enable the Core plugin runtime feature switch.
3. Ensure Laravel's console scheduler executes with server cron every minute: `* * * * * cd /absolute/path/to/84pad && /usr/bin/php artisan schedule:run >> /dev/null 2>&1` (adjust executable/install path).
4. Open the Control Studio plugin widget → Backup.
5. Test manual snapshot and backup export on a staging site before trusting automated snapshots.

## Security / recovery boundaries

- Backups may be *larger than your hosting quota*. Available disk usage/free space and ability to write temporary ZIP + SQL files must be assessed before activation. Each archive is verified after creation; no background queue is installed.
- Exported plain ZIP has `.env` and SQL data; download over HTTPS only, do not place in public folders. The encrypted `.84bk` file **cannot be decrypted if APP_KEY is lost or rotated**. Retain an exported portable ZIP in an independent secure location for disaster recovery.
- Scheduled capture requires reliable cron and an actively enabled plugin. Retention is best effort on each cron tick and stops when the plugin is disabled.
- MySQL base-table dump does not capture views/triggers/routines/events and assumes transactional InnoDB tables for consistent reads. Source files can change during snapshot; checksums detect modifications but do not make all application state atomic. Do **not** call this production-complete recovery.
- Import rejects unsafe names, links, modified hashes and oversized archives. No extraction, automatic restore, SQL execution or arbitrary writes to Core are performed.
- The `8 GiB` uncompressed logical limit and `100,000` ZIP entries are development safety bounds, not a site size guarantee. PHP file upload limits may be lower.

## Next acceptance gates

- PHPUnit tests for archive corruption, retention boundaries, authentication/CSRF, missing APP_KEY, MySQL large/binary data, long-running backup and cron overlap.
- Full safe restore staging, maintenance mode, pre-restore snapshot, database consistency, rollback, recovery from broken Core without Laravel app.
- Verify Core extension bridge compatibility and route caching; real cPanel shared-host cron and disk quota testing.
- Signed distribution package, release tags and dedicated plugin repository when repository creation is available.
