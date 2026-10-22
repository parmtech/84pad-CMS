# 84PAD Backup — Development Progress

Updated: 2026-10-22, Australia/Sydney.
Product: **84PAD Backup** (internal slug `pad-backup`).
Version: **0.1.0 staging source**.
Branch: [`plugin/backup-v0.1`](https://github.com/manbir6-ux/84pad-core/tree/plugin/backup-v0.1), based on `core/C01-laravel-foundation`.
Tracker: [B01 — #23](https://github.com/manbir6-ux/84pad-core/issues/23).
**Live / production deployed: NO. Safe full restore: NO.**

## Checklist

| ID | Task | Status | Evidence and remaining checks |
|---|---|---|---|
| B01.01 | Independent plugin manifest/bootstrap | Implemented, untested | `plugin.json`, `bootstrap.php`; slug conforms to Core catalog |
| B01.02 | MySQL table dump + full file snapshot | Implemented, untested | `src/BackupService.php`, archives Core/files/plugins/media/storage/app + root .env; MySQL base tables only |
| B01.03 | Authenticated encryption and SHA verification | Implemented, untested | libsodium XChaCha20-Poly1305 stream, per-file hashes, APP_KEY-derived key |
| B01.04 | Manual backup + verify, export and validated import | Implemented, untested | Owner-only `src/BackupPanel.php` and `resources/views/dashboard.blade.php`; import never writes live site files |
| B01.05 | Hourly/daily/weekly/monthly schedule + 60-day retention | Implemented, untested | `core.schedule_tick`; hosting cron must run Laravel `schedule:run` every minute; expired artifacts pruned |
| B01.06 | Generic Core bridge | Proposed in feature branch, **awaiting approval** | `PluginExtensionController.php`, `routes/web.php`, `routes/console.php`, `docs/PLUGIN-EXTENSION-BRIDGE.md` |
| B01.07 | Unit/feature tests | Written, **not executed successfully** | `tests/Feature/BackupPluginTest.php`, PHP 8.3/8.4/MySQL workflows updated |
| B01.08 | CI validation | **Blocked** | GitHub Actions jobs show failure before runner assignment (0 steps), see links below. Root cause not yet established |
| B02.01 | Safe full restore | Pending | Maintenance mode, staged file/database restore, pre-restore snapshot, rollback and broken-Core disaster recovery |
| B02.02 | Release and operational QA | Pending | Signed ZIP, dedicated repository, cPanel cron, load tests, UI/keyboard/browser, quotas, missing/extreme files |

## Explicit validation evidence

- [Core CI run 39704805415](https://github.com/manbir6-ux/84pad-core/actions/runs/39704805415) **failed**, PHP 8.3/8.4 jobs show no assigned runner and zero executed steps; not proof of an application test failure or pass.
- [MySQL run 39704805410](https://github.com/manbir6-ux/84pad-core/actions/runs/39704805410) **failed**, no successful MySQL checks from this branch.
- Source written/committed via GitHub repository connector; **no local PHP/Composer test command was executed against the committed sources**.
- Fixes after CI triggers: valid `pad-backup` slug, PHP scheduler namespace separators, consistent storage path, encrypted staging cleanup, Unicode filename-safe archive validation, disk preflight and upload limit alignment. All require fresh validation.

## Known limitations

- Import is **verified staging only**, not recovery/restore.
- SQL covers base tables. MySQL views/triggers/routines/events and nontransactional consistency remain unsupported.
- Local encrypted `.84bk` cannot be decrypted without original APP_KEY; export portable ZIP to independent secure storage over HTTPS.
- ZIP exports contain .env secrets and SQL. Use TLS and keep them off web-accessible folders.
- Backup uses temporary SQL+ZIP+encrypted files and up to 8 GiB logical cap. It needs sufficient disk and runtime for large files; uploads depend on host settings.
- Schedule and retention depend on live cron, plugin enabled and Core scheduler bridge. Monthly schedule is calendar-month based, not a fixed 30-day interval.
- Runtime PHP plugins are trusted code, not sandboxed. Core bridge/API remains **experimental** until versioned release.

## Next-session resume

1. Fetch latest head of `plugin/backup-v0.1` and read this document, issue #23, and Core `docs/PROGRESS.md`.
2. Investigate GitHub Actions job failure before start; check repository Actions billing/permissions/runner availability via GitHub UI, then rerun PHP 8.3/8.4 + disposable MySQL suites.
3. Correct test/runtime failures with fresh verifiable logs; prioritize plugin panel route, safe snapshot encrypt/decrypt, import/export integrity, timestamp expiry.
4. Test staging installation and one complete download+reimport on test cPanel; verify exact DB and plugin files captured.
5. Only after clean validation, design B02 safe restore and publish a signed release. No merge/deploy without approval.
