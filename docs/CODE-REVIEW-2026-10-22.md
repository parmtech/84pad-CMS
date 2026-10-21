# 84PAD Core — Code Review (22 October 2026)

Scope: manually reviewed web first-run installation, login/permissions, plugin manifest/version checks and executable ZIP lifecycle, database migration recovery boundaries, source snapshots, update inspection, and Control Studio responsive view. This is a **targeted source review plus automated regressions**, not a penetration test or exhaustive guarantee.

## Bugs fixed in this review

1. **High — concurrent browser setup requests could overlap database migrations.** Introduced a nonblocking exclusive file lock around the full migration/Owner transaction and rechecked installation state inside the lock (`app/Http/Controllers/Auth/FirstRunController.php`). A parallel attempt returns a safe error and creates no Owner. New regression test verifies lock contention.
2. **High — first-run web installer ignored the integer exit code from Laravel migrations.** A `migrate` command can return nonzero without throwing. Owner creation now requires a zero exit code. A new regression test mocks failing migration and verifies Owner/marker remain absent.
3. **Medium — plugin Core-version compatibility was hard-coded to `0.1.0`.** Future Core upgrades would still reject otherwise-compatible plugins. `PluginCatalog::compatible()` now reads `config('84pad.core_version')`; tests cover multiple Core versions.
4. **UX/mobile — the Security navigation could be hidden in horizontal overflow.** Mobile Control Studio navigation wraps onto a new row so all menu links remain visible. Visual Chromium checks at 390px, 768px and 1440px found no document-wide horizontal overflow.

## Automated verification

- Laravel 12 bootstrap, Composer install, PHPUnit/feature tests, migrations and PHP syntax lint are run on PHP 8.3 and 8.4 by `.github/workflows/core-ci.yml`.
- MySQL 8 migrations, feature tests and concurrent first-owner verification run via `.github/workflows/mysql-compat.yml`.
- Staging packaging excludes runtime secrets and includes dependencies in `.github/workflows/stage-distribution.yml`.
- Consult the **latest commit's** GitHub Actions status before merging. Do not conflate a successful earlier commit with a newer pending or failing one.

## Significant outstanding risks (NOT fixed in this review)

- Activated PHP plugins run with the hosting account's privileges; signed package provenance does not sandbox malicious or vulnerable plugin code.
- MySQL DDL auto-commits; full database/site backup and automated rollback/recovery remain unimplemented.
- Production release reproducibility is incomplete because `composer.lock` was not committed in the repository at review start. The staging ZIP has a generated lockfile; it is not a substitute for a reviewed committed lockfile.
- No completed browser/security acceptance on an actual Namecheap/cPanel staging deployment; permissions, HTTPS/reverse-proxy behavior and first-run configuration still need real-host testing.
- No complete automatic signed updater, emergency recovery workflow or signed production distribution.

## UI preview

Rendered local **sample-data previews** were captured with Chromium using the Control Studio Blade template's actual styles and layout. These are **not** screenshots of a deployed/authenticated live 84PAD instance. Screenshot evidence is provided in this chat as PNGs.

Development branch: `core/C01-laravel-foundation`, draft PR #17. Keep Core marked **staging preview**, not production-ready.