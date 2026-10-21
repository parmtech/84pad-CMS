# 84PAD Core

**Status:** Core 0.1 development preview. Laravel foundation and included automated tests pass GitHub CI on PHP 8.3/8.4. **Not production ready or deployed.**

**Resume here:** [Progress checkpoint](docs/PROGRESS.md) · [Development workflow](docs/WORKFLOW.md) · [Core task board](https://github.com/manbir6-ux/84pad-core/issues).

84PAD is a lightweight, plugin-first PHP CMS and application platform maintained by ParmTech.

## What is implemented so far

- Laravel 12 bootstrap, Composer configuration, database migrations and CI checks.
- CI-tested CLI first-owner installation with preflight checks, replay/concurrency protection, hidden password prompt and headless stdin option. A browser-based installer is **not built yet** ([tracked here](https://github.com/manbir6-ux/84pad-core/issues/18)).
- Guarded sign-in/sign-out and initial Control Studio view (still a development UI).
- JSON plugin discovery and activation/deactivation **metadata markers only**. Plugin PHP is deliberately **not executed** until trusted lifecycle/loading contracts are built and tested.
- HookBus contract with priority ordering and action dispatch, with unit tests. It is not yet connected to running plugins.
- `84pad:doctor` read-only host checks, plus basic secrets hygiene and negative-path tests.

**Verified CI:** [PHP 8.3/8.4 #39609985107](https://github.com/manbir6-ux/84pad-core/actions/runs/39609985107), and [MySQL 8 concurrency #39609985317](https://github.com/manbir6-ux/84pad-core/actions/runs/39609985317). These are not a live shared-hosting installation or a full security audit.


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

This README defines the starting contract only; it is not a claim that any listed feature is implemented.
