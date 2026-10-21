# 84PAD Core

**Status:** Core 0.1 development preview. Laravel foundation and included automated tests pass GitHub CI on PHP 8.3/8.4. **Not production ready or deployed.**

**Resume here:** [Progress checkpoint](docs/PROGRESS.md) · [Development workflow](docs/WORKFLOW.md) · [Core task board](https://github.com/manbir6-ux/84pad-core/issues).

84PAD is a lightweight, plugin-first PHP CMS and application platform maintained by ParmTech.

## What is implemented so far

- Laravel 12 bootstrap, database migrations, project configuration, PHP 8.3/8.4 and MySQL CI.
- CLI first-owner installation with host preflight and MySQL concurrency protection, and an **experimental** token-gated browser installer. Browser setup currently expects preconfigured `.env`, database and `APP_KEY` ([instructions](docs/WEB-INSTALL.md)).
- Guarded login/logout, session protection, Owner password rotation and trusted server-side CLI recovery.
- Role permissions: Owner controls plugins/security; Administrator can view Control Studio, other roles remain denied by default.
- Fully redesigned responsive futuristic Control Studio with real status and local plugin management.
- JSON plugin manifest discovery, owner-controlled activation/deactivation, HookBus integration and first-party executable demo widget.
- Signed ZIP plugin upload checks publisher public keys, archive size/path/symlink restrictions and leaves new plugins disabled. PHP plugins execute with server privileges **only after Owner activates them** ([security contract](plugins/README.md)).
- Core update **signature-verification interface** using Ed25519 with trusted public keys. There is no automatic upgrader, central release server, or verified rollback yet.

**Latest verified CI:** [PHP 8.3/8.4 #39618022600](https://github.com/manbir6-ux/84pad-core/actions/runs/39618022600) and [MySQL 8 #39618022701](https://github.com/manbir6-ux/84pad-core/actions/runs/39618022701), both succeeded, including signed package quarantine tests. This is not a live shared-hosting deployment or security audit.

## Important gaps before Core release

- Complete browser bootstrap that configures the database and application key without SSH or manual `.env` editing.
- Automatic signed plugin updates, dependency resolution, plugin-owned migrations and automated rollback. **Signed-uploaded plugins can already be moved to private quarantine by confirmed Owner action**, without deleting database data.
- Full unattended Core updater, maintenance mode, rollback/recovery plan, release packaging and security audit.
- Clean cPanel shared-host installation and browser/mobile visual acceptance testing.
- Review/merge the [draft development PR](https://github.com/manbir6-ux/84pad-core/pull/17) only after all acceptance checks pass. **No production release is claimed.**

See [docs/PROGRESS.md](docs/PROGRESS.md) for verified and remaining tasks.

## Core 0.1 scope

The Core is intentionally small and contains only:
- Secure installation, environment and database configuration.
- Owner account creation, authentication, authorization and security controls.
- Control Studio: responsive futuristic dashboard with accessible navigation.
- Plugin discovery, installation, activation, deactivation and safe uninstall lifecycle.
- Versioned hook/event APIs and plugin compatibility metadata.
- Database migration mechanism.
- Core/plugin update integration points and emergency recovery foundation.

## Plugins, not Core

SSL God (certificate detection and safe renewal), domain email management, file management, scheduled backup and rollback, pages, site builder, themes, SEO and AI features will be optional **plugins**. The first four can be offered as recommended first-run selections, without becoming hard-coded Core dependencies.

## Planned technical baseline

- PHP 8.3+ (subject to verification against final Laravel requirements)
- Laravel 12, Composer, MySQL/MariaDB
- Server deployment via web host / cPanel where supported

## Safety rules

- Never commit `.env`, private keys, API tokens, credentials, user data, or backups.
- Installer must be locked once the owner is established, and be resistant to repeated installation.
- Plugin code is untrusted until validated; permission boundaries and integrity checks are required.
- Updates and restore operations require verifiable backups, signature/integrity checks and error recovery.

## Roadmap

1. Scaffold a runnable Laravel 12 project, install dependencies, run baseline checks.
2. Build a one-time setup flow and guarded owner authentication.
3. Build Control Studio and roles/permissions.
4. Implement plugin contracts, manager, hook lifecycle and a working example plugin.
5. Add automated tests and recovery/update interfaces.
6. Build independent SSL, Email, File Manager and Backup plugins.
7. Build a separate signed central release service after Core architecture stabilizes.

This source tree is an in-development Core preview, not a production release. Use staging only until the remaining acceptance tasks pass.
