# 84PAD Core

**Status:** Core 0.1 development preview. Laravel foundation and included automated tests pass GitHub CI on PHP 8.3/8.4. **Not production ready or deployed.**

**Resume here:** [Progress checkpoint](docs/PROGRESS.md) · [Development workflow](docs/WORKFLOW.md) · [Core task board](https://github.com/manbir6-ux/84pad-core/issues).

84PAD is a lightweight, plugin-first PHP CMS and application platform maintained by ParmTech.

## What is implemented so far

- Laravel 12 bootstrap, database migrations, project configuration, PHP 8.3/8.4 and MySQL CI.
- CI-tested first-owner CLI installer plus a **staging no-SSH browser bootstrap**: cPanel user uploads the vendor-bundled staging ZIP, creates a private one-time token, connects a **new empty MySQL database**, then the bootstrap creates `.env`/APP_KEY and opens the Owner wizard ([instructions](docs/WEB-INSTALL.md)). Live cPanel compatibility remains unverified.
- Guarded login/logout, session protection, Owner password rotation and trusted server-side CLI recovery.
- Role permissions: Owner controls plugins/security; Administrator can view Control Studio, other roles remain denied by default.
- Fully redesigned responsive futuristic Control Studio with real status and local plugin management.
- **Core Settings:** central encrypted cPanel API token vault, optional global access toggle, independent feature switches, deny-by-default plugin capabilities and a fixed read-only UAPI gateway contract. No live cPanel connection verification yet.
- **First-login Guided Tour:** arrows/spotlight explain dashboard features and navigate into Settings, with Next/Back/Skip/Finish and per-Owner completion/replay.
- **128 MiB signed plugin ZIP upload:** 512 MiB extraction ceiling, 5000-member maximum and compression-ratio protections; underlying hosting PHP upload limits must allow it.
- JSON plugin manifest discovery, owner-controlled activation/deactivation, HookBus integration and first-party executable demo widget.
- Plugin manifests can declare cPanel gateway capabilities; Owners can grant only the declared set, and grants become invalid when the declaration changes.
- Signed ZIP plugin upload checks publisher public keys, archive size/path/symlink restrictions and leaves new plugins disabled. PHP plugins execute with server privileges **only after Owner activates them** ([security contract](plugins/README.md)).
- Core update Ed25519 inspection plus private code-only snapshots. `84pad:core-snapshot --restore=... --sha256=...` can restore only snapshot-listed source files after confirmation and a pre-restore snapshot. It does not restore databases, installed plugins, uploads, dependencies, hosting settings or `.env`; signed update application and full rollback remain unbuilt.
- Basic page drafting and publish/unpublish is on [`core/C23-basic-pages`](https://github.com/parmtech/84pad-CMS/tree/core/C23-basic-pages); CI passes, but it is not merged or manually reviewed yet.
- Owner-managed site title, canonical URL, locale, and time zone are on [`core/C24-site-identity`](https://github.com/parmtech/84pad-CMS/tree/core/C24-site-identity); CI passes, but it is not merged or manually reviewed yet.
- Selecting a published page as the public homepage is on [`core/C25-homepage`](https://github.com/parmtech/84pad-CMS/tree/core/C25-homepage); PHP 8.3/8.4 CI passes, but it is not merged or manually reviewed yet.
- Manifest-declared cPanel capabilities and owner grants are on [`core/C26-manifest-capabilities`](https://github.com/parmtech/84pad-CMS/tree/core/C26-manifest-capabilities); PHP 8.3/8.4 CI passes, but no staging-package result or maintainer review is recorded.

**Recent verified code CI:** Pages [PHP 8.3/8.4 #40422758337](https://github.com/parmtech/84pad-CMS/actions/runs/40422758337), [MySQL #40422758481](https://github.com/parmtech/84pad-CMS/actions/runs/40422758481), and [staging package #40422758272](https://github.com/parmtech/84pad-CMS/actions/runs/40422758272) succeeded. Site identity [PHP 8.3/8.4 #40423368199](https://github.com/parmtech/84pad-CMS/actions/runs/40423368199) and [staging package #40423368180](https://github.com/parmtech/84pad-CMS/actions/runs/40423368180) succeeded. Homepage revision [90cf068](https://github.com/parmtech/84pad-CMS/commit/90cf068ecd98e9fe80af6f67b93cb65fbeee6453) passed [PHP 8.3/8.4 CI #40424115625](https://github.com/parmtech/84pad-CMS/actions/runs/40424115625). Plugin manifest revision [f6ec3cc](https://github.com/parmtech/84pad-CMS/commit/f6ec3cc7eab55bb32b8d7a0520c8934869816520) passed [PHP 8.3/8.4 CI #40424902320](https://github.com/parmtech/84pad-CMS/actions/runs/40424902320). No staging-package result is recorded for either slice. These checks are not a production security audit or live shared-hosting acceptance.

## Important gaps before Core release

- **Live cPanel acceptance** of the staging no-SSH bootstrap, including PHP extensions, reverse-proxy HTTPS, web document root and permissions. A private token/file-manager step is still required.
- Automatic signed plugin updates, dependency resolution, arbitrary plugin data transformations and automated database rollback. Signed-uploaded plugins can be quarantined and restored disabled; safe explicit plugin-owned **create-only schema** is available from CLI ([contract](docs/PLUGIN-SCHEMA.md)).
- Signed Core update application, integrated database backup and rollback, **reproducible committed composer.lock**, signed release packaging, and security audit.
- Clean cPanel shared-host installation and browser/mobile visual acceptance testing.
- Review/merge the [draft development PR](https://github.com/manbir6-ux/84pad-core/pull/17) only after all acceptance checks pass. **No production release is claimed.**

See [docs/PROGRESS.md](docs/PROGRESS.md) for verified and remaining tasks.

## Core 0.1 scope

The Core is intentionally small and contains only:
- Secure installation, environment and database configuration.
- Owner account creation, authentication, authorization and security controls.
- Control Studio: responsive futuristic dashboard with accessible navigation.
- Plugin discovery, installation, activation, deactivation and safe uninstall lifecycle.
- Basic pages: draft, edit, preview/public render, publish and unpublish.
- Site identity settings: title, canonical URL, language, display time zone, and published homepage selection.
- Plugin manifest capability requests and scope-bound owner grants for the fixed cPanel gateway.
- Versioned hook/event APIs and plugin compatibility metadata.
- Database migration mechanism.
- Core/plugin update integration points and emergency recovery foundation.

## Plugins, not Core

SSL God (certificate detection and safe renewal), domain email management, file management, scheduled backup and rollback, site builder, themes, SEO and AI features will be optional **plugins**. The first four can be offered as recommended first-run selections, without becoming hard-coded Core dependencies.

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

## License

84PAD Core is licensed under the GNU General Public License, version 3 or (at your option) any later version. See [LICENSE](LICENSE).



