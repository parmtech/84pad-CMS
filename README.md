# 84PAD Core

**Status:** Early development. Tracking set up; Laravel scaffold not yet verified runnable.

**Resume here:** [Progress checkpoint](docs/PROGRESS.md) · [Development workflow](docs/WORKFLOW.md) · [Core task board (15 issues)](https://github.com/manbir6-ux/84pad-core/issues).

84PAD is a lightweight, plugin-first PHP CMS and application platform maintained by ParmTech.

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
