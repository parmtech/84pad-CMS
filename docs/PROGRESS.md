# 84PAD Core — Resume Checkpoint

> **Source of truth:** GitHub issue states + this file. Do not infer completion from chat messages.
> Last updated: 2026-10-24 (Australia/Sydney); this branch tracks the C20 wizard review.
> **Current phase:** C20 English-only installer UI redesign committed on review branch; CI/live acceptance still pending. Core 0.1 staging build — new Owner Settings, encrypted cPanel API credential, deny-by-default per-plugin grants, independent feature switches, guided first-login training, and 128 MiB signed plugin upload added. Live compatibility/release gates remain.
> **Next issue:** [#13 C13 — Plugin migration recovery](https://github.com/manbir6-ux/84pad-core/issues/13): finish migration-failure safeguards and rollback approach; then [#14 C14 — Core updater/recovery](https://github.com/manbir6-ux/84pad-core/issues/14). Browser installer/cPanel QA remain release gates.
> **Runnable:** New staging distribution built with Composer vendor bundled, and the latest PHP 8.3/8.4 and MySQL 8 suites pass. Not deployed to live cPanel or approved for production.
> **Environment test status:** [Core CI #39692517418](https://github.com/manbir6-ux/84pad-core/actions/runs/39692517418) **SUCCESS** PHP 8.3/8.4 with first-login tutorial, encryption/permissions, 128 MiB ZIP and JavaScript syntax tests; [MySQL CI #39692517395](https://github.com/manbir6-ux/84pad-core/actions/runs/39692517395) **SUCCESS**; [Staging ZIP #39692517544](https://github.com/manbir6-ux/84pad-core/actions/runs/39692517544) **SUCCESS**. Live cPanel API, browser visual/accessibility and production security/recovery tests remain pending.

## Latest C20 installer review (2026-10-24)

- Development branch: [`core/C20-preflight-hardening`](https://github.com/manbir6-ux/84pad-core/tree/core/C20-preflight-hardening), forked from existing `core/C20-host-setup-wizard` (do not merge to production yet).
- Existing wizard already supported **Manual MySQL** and **one-time cPanel UAPI database/user creation**, with a dedicated second Owner setup screen. These were reviewed, not rebuilt.
- New changes: mandatory PHP/extension, Composer runtime, writable private/boot cache checks **before any provisioning or `.env` write**; visible readiness checklist, official cPanel token and manual MySQL guidance, explicit no-token fallback, step accessibility improvements, updated CI JavaScript check and tests.
- Security boundaries: HTTPS and private token required, API token not persisted, preexisting `.env` and populated DB forbidden, no WHM/root token required. Independent Core Settings cPanel connection remains optional.
- **Status: implemented on feature branch, NOT release accepted.** Current GitHub Actions runs on C20 base and review branch report **failure**; the inspected failed jobs provided no usable steps/logs in the connector, so the failure cause is unverified. Do not report these commits as tested, use any ZIP as production, or merge without passing CI.
- **Remaining:** inspect failed Actions logs/runner availability, run PHP 8.3/8.4 Core tests and MySQL disposable integration, test automatic cPanel provisioning against a throwaway account, browser/mobile accessibility and HTTPS reverse proxy scenarios; verify zipped distribution and revoke setup token.
- **Next:** obtain a green CI run and host acceptance evidence, then address any regressions, update checklist/issue, and request approval for merge.

## 2026-10-24 — English-only professional installer UI (C20 / PR #25)

- Owner feedback: all in-product installation text must remain **English**. Conversation language is independent of product UI locale.
- Rebuilt `public/84pad-bootstrap.php` presentation with a premium shared 84PAD Control Studio visual system in `public/css/84pad-installer.css`: responsive full-width gradients and glass panels, accessible 3-step progress, selection cards, readiness diagnostics, high-contrast forms, right-side help, clear mobile stacking, and reduced-motion support. The backend credential/DB handlers were preserved. Switched the bootstrap CSP from inline styles to same-origin CSS.
- Rebuilt `resources/views/auth/first-run.blade.php` for the final Owner screen with the **same** design and English labels. Hosting/bootstrap and Owner screens no longer appear as unrelated experiences.
- Improved `public/js/84pad-bootstrap-wizard.js` with completed-step states, accessible current step, required-host guard and duplicate-submit feedback; no credential storage/network polling.
- Expanded `BootstrapWizardTest.php` and `FirstRunWebInstallerTest.php`; added `InstallerDesignTest.php` verifying English-only copy, shared local assets and accessibility CSS. Tests are committed but **not reported passed** until CI reports success.
- Branch: [`core/C20-preflight-hardening`](https://github.com/manbir6-ux/84pad-core/tree/core/C20-preflight-hardening); [draft PR #25](https://github.com/manbir6-ux/84pad-core/pull/25). No merge or live deployment.
- Synced latest upstream C20 safety change (prevalidating DB host/port and serializing cPanel provisioning) into review branch. Reconciled upstream commits using feature-branch merge `8f4cfda793b1d60ed8e697ddb57345395f9943e9` (not merged to production/main). Draft PR #25 was reported mergeable afterwards; acceptance CI still unstable.
- Staging workflow now explicitly verifies `public/css/84pad-installer.css` is packaged and describes private-token bootstrap rather than incorrectly instructing all users to precreate `.env`. Reference commit: `7038c55b6df6d27f05bff7123a9b479753e729b5`.

- CI status at checkpoint: recently triggered PHP/MySQL/Stage workflows for this branch are failing or queued. The connector did not expose actionable job log steps; no verified green runs for this redesign. **Release BLOCKED** on successful CI and real cPanel + browser/mobile QA.
- Next: investigate Actions failures in GitHub UI, run static and feature tests against PHP 8.3/8.4 + disposable MySQL, visually QA at mobile/desktop widths and keyboard/screen-reader modes, then test on a throwaway cPanel account before owner-approved merge.

## Work checklist

| ID | Task | State | Proof |
|---|---|---|---|
| [C01](https://github.com/manbir6-ux/84pad-core/issues/1) | Laravel 12 scaffold | **CI verified (PHP 8.3 & 8.4)** | [Run 39605060743](https://github.com/manbir6-ux/84pad-core/actions/runs/39605060743); still unmerged and not production approved |
| [C02](https://github.com/manbir6-ux/84pad-core/issues/2) | Config and secret hygiene | **CI verified; issue closed** | [Run 39605702869](https://github.com/manbir6-ux/84pad-core/actions/runs/39605702869); nested secret ignores + filename guard |
| [C03](https://github.com/manbir6-ux/84pad-core/issues/3) | Installer preflight checks | **CI verified; issue closed** | Installer now executes `84pad:doctor` before migrations; [CI run 39607991669](https://github.com/manbir6-ux/84pad-core/actions/runs/39607991669) |
| [C04](https://github.com/manbir6-ux/84pad-core/issues/4) | One-time owner CLI installer | **CI verified; issue closed** | [MySQL concurrent first-owner CI 39609985317](https://github.com/manbir6-ux/84pad-core/actions/runs/39609985317); production web installer is separate #18 |
| [C05](https://github.com/manbir6-ux/84pad-core/issues/5) | Authentication | **Login/password rotation/CLI recovery CI tested; review pending** | [PHP CI #39615871600](https://github.com/manbir6-ux/84pad-core/actions/runs/39615871600); production HTTPS/cookie audit outstanding |
| [C06](https://github.com/manbir6-ux/84pad-core/issues/6) | Authorization | **Owner/Admin Gates and route checks CI tested; role management pending** | Dashboard Admin; owner-only plugin/security; viewer default-deny; [PHP CI](https://github.com/manbir6-ux/84pad-core/actions/runs/39615871600) |
| [C07](https://github.com/manbir6-ux/84pad-core/issues/7) | Control Studio dashboard | **Futuristic responsive UI committed; browser QA pending** | Full-width gaming-inspired UI, plugin controls, reduced-motion CSS; [PHP CI](https://github.com/manbir6-ux/84pad-core/actions/runs/39615871600) |
| [C08](https://github.com/manbir6-ux/84pad-core/issues/8) | Plugin manifest | **Validated manifests and signed ZIP installed; dependencies pending** | Uploads require pinned Ed25519 publisher public keys, ZIP inspection; [PHP CI](https://github.com/manbir6-ux/84pad-core/actions/runs/39615871600) |
| [C09](https://github.com/manbir6-ux/84pad-core/issues/9) | Plugin discovery | **Discovery and registry operational; API/install lifecycle not final** | Source registry, dashboard list, signed ZIP installed disabled |
| [C10](https://github.com/manbir6-ux/84pad-core/issues/10) | Plugin activation/deactivation | **Executable trusted local PHP on dashboard; uninstall/migrations pending** | Activation, hook, deactivation and signed install tests [PHP CI](https://github.com/manbir6-ux/84pad-core/actions/runs/39615871600) |
| [C11](https://github.com/manbir6-ux/84pad-core/issues/11) | Hooks API | **HookBus connected to demo plugin; versioning/error boundaries incomplete** | Priority/order, dispatch, active widget and fault fallback [PHP CI](https://github.com/manbir6-ux/84pad-core/actions/runs/39615871600) |
| [C12](https://github.com/manbir6-ux/84pad-core/issues/12) | Demo plugin | **CI verified; issue closed** | First-party PHP widget appears only after activation and disappears after deactivation [PHP CI](https://github.com/manbir6-ux/84pad-core/actions/runs/39615871600) |
| [C13](https://github.com/manbir6-ux/84pad-core/issues/13) | Plugin-owned schema migration and quarantine | **Additive schema/history + quarantine restore CI verified; automatic DDL rollback pending** | [PHP CI #39626800977](https://github.com/manbir6-ux/84pad-core/actions/runs/39626800977) and [MySQL #39626800968](https://github.com/manbir6-ux/84pad-core/actions/runs/39626800968) passed |
| [C14](https://github.com/manbir6-ux/84pad-core/issues/14) | Update and emergency recovery | **Signed update inspection + private source snapshot verified; full rollback not built** | `84pad:core-snapshot` integrity-tested [Core CI #39686927563](https://github.com/manbir6-ux/84pad-core/actions/runs/39686927563); signed release inspection previously verified |
| [C15](https://github.com/manbir6-ux/84pad-core/issues/15) | QA and handoff | **Staging ZIP reproducibility CI passed; live acceptance incomplete** | [Stage Distribution #39686927599](https://github.com/manbir6-ux/84pad-core/actions/runs/39686927599) generated vendor-bundled, secret-excluded artifact; composer.lock and cPanel acceptance still pending |
| [C16](https://github.com/manbir6-ux/84pad-core/issues/18) | Secure browser installer for ordinary hosting | **No-SSH token-gated PHP bootstrap + Laravel Owner wizard CI tested; real cPanel acceptance pending** | Disposable MySQL [CI #39686927537](https://github.com/manbir6-ux/84pad-core/actions/runs/39686927537) verified `.env`/APP_KEY creation without overwrite; [docs/WEB-INSTALL.md](WEB-INSTALL.md) |
| [C17](https://github.com/manbir6-ux/84pad-core/issues/19) | Central cPanel credentials, Core Settings, plugin API grants | **Core/UI + read-only UAPI gateway CI tested; live API/writes pending** | [PHP #39692517418](https://github.com/manbir6-ux/84pad-core/actions/runs/39692517418) / [MySQL #39692517395](https://github.com/manbir6-ux/84pad-core/actions/runs/39692517395); token encrypted and optional |
| [C18](https://github.com/manbir6-ux/84pad-core/issues/20) | First-login Guided Tour | **Arrow tutorial and completion/replay tested; browser accessibility QA pending** | [Core CI #39692517418](https://github.com/manbir6-ux/84pad-core/actions/runs/39692517418): GuidedTourTest and JS syntax |
| C20 | Secure hosting/database setup wizard | **Implemented on review branch; CI acceptance blocked** | [Review branch](https://github.com/manbir6-ux/84pad-core/tree/core/C20-preflight-hardening); PHP/MySQL/real cPanel QA pending |
| [C19](https://github.com/manbir6-ux/84pad-core/issues/21) | Increase signed plugin ZIP upload to 128 MiB | **CI verified; issue closed** | 128 MiB compressed, 512 MiB extracted, <=5000 entries, ratio checks; [Core CI](https://github.com/manbir6-ux/84pad-core/actions/runs/39692517418) / [MySQL](https://github.com/manbir6-ux/84pad-core/actions/runs/39692517395) |

## Work log

### 2026-10-22 — Owner cPanel Settings, 128 MiB plugins, first-login Guided Tour
- Added `core_settings`, `core_cpanel_grants` and per-user `core_user_preferences` migrations. Owner-only settings let users store an APP_KEY-encrypted cPanel API token, control independent Core switches, and grant/revoke specific plugin capabilities. Integration defaults OFF, so login and unrelated plugins work without any token.
- Added `CpanelPermissions` checks and preliminary fixed read-only `CpanelGateway` operations; privileged PHP plugin code remains full-trust, not sandboxed. No real cPanel API account tested and write commands remain deferred.
- Added guided arrow/spotlight tour across Dashboard and Settings, with Back/Next/Skip/Finish, initial Owner auto-start, persisted completion and replay option. `GuidedTourTest` and JavaScript syntax checks passed; real browser accessibility checks still pending.
- Increased signed ZIP upload to **128 MiB** compressed, **512 MiB** expanded, **5000** entries with ratio checks and tests. Host PHP upload/post limits must support file size; C19 issue closed.
- Security test verifies token encryption, no plaintext token in UI/session flash, Owner authorization, global/individual API access, graceful operation without cPanel, and reject arbitrary API calls/file paths.
- [PHP CI #39692517418](https://github.com/manbir6-ux/84pad-core/actions/runs/39692517418) **SUCCESS** PHP 8.3/8.4, [MySQL CI #39692517395](https://github.com/manbir6-ux/84pad-core/actions/runs/39692517395) **SUCCESS**, and [Stage Distribution #39692517544](https://github.com/manbir6-ux/84pad-core/actions/runs/39692517544) **SUCCESS**.
- Docs: [Core Settings, cPanel and Guided Tour](CORE-SETTINGS-AND-TOUR.md). C17 and C18 remain open for live-host and browser/accessibility verification; Core itself is **still staging preview**.


### 2026-10-22 — cPanel staging distribution, bootstrap and source recovery
- Added `public/84pad-bootstrap.php`: only on direct HTTPS with a 32+ char owner-generated private token, empty MySQL database, CSRF/rate limits, exclusive `.env` creation, generated APP_KEY and fail-closed existing configuration. Added `tests/Fixtures/BootSetupGet.php`, `BootSetupPost.php`, `BootstrapGateTest.php`, `BootstrapMySqlTest.php`.
- [MySQL CI #39686927537](https://github.com/manbir6-ux/84pad-core/actions/runs/39686927537) **SUCCESS**: disposable MySQL database host-bootstrap POST wrote `.env`, generated APP_KEY and blocked repeat overwrite. Browser Core Owner setup remains separate but previously tested; no live cPanel acceptance yet.
- Added `.github/workflows/stage-distribution.yml`, bundles runtime Composer vendor in secret-excluded staging ZIP. [Stage Distribution #39686927599](https://github.com/manbir6-ux/84pad-core/actions/runs/39686927599) **SUCCESS**, artifact available on GitHub Actions for 7 days. The ZIP is not a signed production release and Composer lockfile is not committed.
- Added `CoreSnapshot` class and `84pad:core-snapshot` CLI for **source-code-only** ZIP and SHA-256 integrity verification. Snapshot excludes runtime .env/secrets, vendor, installed plugins, uploads and database; tested corruption detection. Full site backup/recovery and automatic updates/rollback are NOT implemented.
- [Core CI #39686927563](https://github.com/manbir6-ux/84pad-core/actions/runs/39686927563) completed **SUCCESS** PHP 8.3/8.4. No deployment made to production hosting.
- **Next release blockers:** commit verified composer.lock, live cPanel 8.3/8.4 install, signed release delivery/updater with backup and rollback, plugin schema recovery, browser/mobile visual and security audit. Keep issues #13/#14/#15/#18 open.


### 2026-10-22 — C14 signed update inspection checkpoint
- Added `UpdateInspector` with signature verification before parsing ZIP, version/PHP compatibility, path/symlink/duplicate and uncompressed-size checks, and non-installing `84pad:update-inspect` CLI.
- Tests cover trusted signed ZIP, missing publisher key, path traversal, and downgrade refusal. New manager only verifies; **it never installs or modifies Core files**.
- [PHP CI #39626800977](https://github.com/manbir6-ux/84pad-core/actions/runs/39626800977) and [MySQL CI #39626800968](https://github.com/manbir6-ux/84pad-core/actions/runs/39626800968) completed **SUCCESS** including C13 new schema/restore and C14 inspector tests.
- [docs/CORE-UPDATES.md](CORE-UPDATES.md) records signature scheme and nonimplemented updater/rollback. C14 stays open; no release readiness claim.


### 2026-10-22 — C13 schema and restore checkpoint
- Extended `PluginQuarantine` with snapshot listing and exact-snapshot restoration. Owner-only restore route/UI requires typed plugin ID; restores as disabled, protects occupied destination and rejects invalid snapshots.
- Added `core_plugin_schema_history` ledger and explicit `84pad:plugin-schema` CLI that reads a restrictive additive `schema.json`, validates the entire plan, allows only plugin-namespaced new tables, records per-table revision SHA-256 checksum, skips matching repeats and blocks changed definitions or collisions. No automatic destructive schema operations.
- Added feature tests for plugin restore (wrong ID, repeat restore, Owner authorization), safe plugin schema creation, idempotency, drift, collision and core-table namespace protection.
- [PHP Core CI #39625847746](https://github.com/manbir6-ux/84pad-core/actions/runs/39625847746) PASSED PHP 8.3/8.4. [MySQL CI #39625847866](https://github.com/manbir6-ux/84pad-core/actions/runs/39625847866) job steps were green when last inspected; run-level confirmation still required.
- [docs/PLUGIN-SCHEMA.md](PLUGIN-SCHEMA.md) explains that MySQL DDL auto-commits and the Core **does not** provide automatic schema rollback, production backup, or arbitrary plugin data migrations yet. C13 remains open.


### 2026-10-22 — signed plugin quarantine tested
- Added signed-upload provenance marker, Owner-confirmed plugin uninstall route and quarantine manager that disables then moves uploaded plugin code to private storage without deleting plugin database records.
- Bundled plugin uninstall blocked; signed upload quarantine tested. One functional test initially failed due test rate-limit counter accumulation, isolated throttling in package lifecycle tests while retaining production route throttles; rerun passed.
- [PHP CI #39618022600](https://github.com/manbir6-ux/84pad-core/actions/runs/39618022600) completed **SUCCESS** for PHP 8.3/8.4; [MySQL CI #39618022701](https://github.com/manbir6-ux/84pad-core/actions/runs/39618022701) completed **SUCCESS**.
- Remaining C13: plugin migrations, auto restore/rollback; C14: safe core update and rollback; C16: no-SSH setup; C15: complete security/visual/cPanel QA. Core is **still not complete**.


### 2026-10-22 — major Core implementation checkpoint
- Added Owner password-change screen, server-only emergency recovery, Owner/Admin role Gates and route guards; feature tests include bad current password and denied non-owner actions.
- Built an animated responsive futuristic Control Studio shell; browser visual QA still required.
- Added plugin runtime with actual HookBus-executed first-party demo widget; active/deactive and hook fault isolation covered in PHPUnit; C12 issue closed.
- Added owner-only signed plugin ZIP upload with pinned Ed25519 publisher public keys, size/path/symlink/duplicate entry protection and disabled-by-default installs; PHP plugins remain full-trust, not sandboxed. Signed package feature tests cover valid installs, missing keys and path traversal rejection.
- Added token-gated rate-limited browser setup form/controller, fail-closed installer marker and PHP tests. **Requires host-configured DB/.env/APP_KEY**, so no-click shared-host setup remains unbuilt.
- Added signed Core update artifact verifier, but no upgrade installer, automatic rollback or off-host backup yet.
- [Core CI #39615871600](https://github.com/manbir6-ux/84pad-core/actions/runs/39615871600) succeeded on PHP 8.3 and 8.4; [MySQL CI #39615871551](https://github.com/manbir6-ux/84pad-core/actions/runs/39615871551) completed successfully. No live cPanel deployment.
- **Do not merge PR #17 or call this a production release yet**. Primary remaining tasks: C13 plugin migrations/uninstall, C14 rollback/upgrade implementation, full C16 web setup, C07 browser QA and C15 security/release acceptance.


### 2026-10-22 — MySQL concurrent first-owner acceptance
- Added `--password-stdin` to the CLI installer for secure headless input (never a password argument). The normal hidden password prompt remains the default.
- Created `.github/workflows/mysql-compat.yml` and validated migrations/tests against MySQL 8.
- [MySQL CI run 39609985317](https://github.com/manbir6-ux/84pad-core/actions/runs/39609985317) **passed** with two simultaneous installer processes and exactly one owner/installation marker, plus the full test suite.
- C04 (CLI one-time Owner install) closed after verified acceptance; created [issue #18](https://github.com/manbir6-ux/84pad-core/issues/18) for the **still-unbuilt browser-based web installer**.
- Production shared-hosting deployment/security audit remains pending, draft PR #17 has not been merged.


### 2026-10-22 — plugin contract verification
- Added `tests/Unit/HookBusContractTest.php` covering hook priority/registration order, dispatch, unknown-hook behavior, and invalid names.
- Added plugin state tests for activation then deactivation and rejection of unknown plugins; feature remains intentionally metadata-only, no third-party PHP execution.
- [CI run 39608854765](https://github.com/manbir6-ux/84pad-core/actions/runs/39608854765) **passed** on PHP 8.3 and PHP 8.4. README updated to accurately describe scope.
- Next: C04 concurrent MySQL installer validation, C05 final auth recovery/security decisions, then executable plugin lifecycle after security design. C10/C11 remain open.


### 2026-10-22 — preflight enforcement and login hardening
- Installer now invokes `84pad:doctor` before migrations; insecure production configuration exits without creating owner or installation marker. [CI run 39607425378](https://github.com/manbir6-ux/84pad-core/actions/runs/39607425378) passed on PHP 8.3/8.4.
- `tests/Feature/AuthenticationFlowTest.php` now covers invalid password and login throttling after 5 attempts; login returns generic `503` on database failure, without exposing raw diagnostic details.
- [CI run 39607991669](https://github.com/manbir6-ux/84pad-core/actions/runs/39607991669) passed PHP 8.3 and 8.4 for the latest auth hardening. C03 issue closed, C04/C05 remain open for further security and operational verification.


### 2026-10-22 — owner installer acceptance tests
- Added `tests/Feature/OwnerInstallerTest.php`: first CLI owner install succeeds, retry fails without a second user/installation marker, and invalid short password leaves no installation marker.
- [GitHub Actions run 39606560227](https://github.com/manbir6-ux/84pad-core/actions/runs/39606560227) **PASSED** on both PHP 8.3 and 8.4.
- Still need concurrent-install testing on MySQL/cPanel and a production security review before closing C04.


### 2026-10-22 — preflight test verification
- Added `tests/Feature/HostPreflightTest.php` covering valid CI host and negative APP_KEY/database configurations.
- Added production-only HTTPS URL and debug-disabled preflight guards to `app/Console/Commands/DoctorCore.php`, plus a negative feature test.
- [CI run 39606241862](https://github.com/manbir6-ux/84pad-core/actions/runs/39606241862) completed successfully on both PHP 8.3 and PHP 8.4, validating current source for the included tests.
- C03 remains open pending remaining installer/replay/no-secret-error acceptance; Core still not production-ready.


### Latest development checkpoint
- Feature branch: [core/C01-laravel-foundation](https://github.com/manbir6-ux/84pad-core/tree/core/C01-laravel-foundation) (DO NOT merge as production ready).
- Committed Laravel config and standard migration structure: [c2f52fc](https://github.com/manbir6-ux/84pad-core/commit/c2f52fc626e1dacecc6f860259449c004b5c4d8e).
- Added CLI installer, owner login and Control Studio development previews: [b660356](https://github.com/manbir6-ux/84pad-core/commit/b660356a15c6212fd16cbbfd4edeaed4158fc4a8).
- Fixed atomic installation sentinel (nullable foreign key) and wired auth routes: [6b99982](https://github.com/manbir6-ux/84pad-core/commit/6b9998246fe8bff20b116925ec7ff1e5eed1fe94).
- Added metadata-only plugin discovery, registry and hooks: [f6d51b3](https://github.com/manbir6-ux/84pad-core/commit/f6d51b35ef84ecd17695adc0239fa0748dd37672).
- Added installation preflight command: [ccbf991](https://github.com/manbir6-ux/84pad-core/commit/ccbf99170d55621fc7a3fa0e6ffeba58a4dcb5c0).
- Added owner-only plugin state UI and feature tests: [219ce12](https://github.com/manbir6-ux/84pad-core/commit/219ce128d075866f956281fd65561531c1acca4f).
- CI on latest feature code was **pending** when inspected; no permission to declare C03–C15 complete. Core 0.1 is **unfinished**.
- **Next action:** check most recent CI logs, fix any red tests, validate `84pad:doctor`, CLI installation once-only, login security and plugin status tests. Then assess issue completion individually.

### Verified build and remaining acceptance (2026-10-21)
- Latest reviewed source commit: [88d37254](https://github.com/manbir6-ux/84pad-core/commit/88d37254ea901e1490e0b6ba138c294a480ae4bc).
- [GitHub Actions CI run 39606241862](https://github.com/manbir6-ux/84pad-core/actions/runs/39606241862) **completed SUCCESS** on PHP 8.3 and 8.4, including automated PHP/Laravel test steps.
- This is **automated CI verification**, not a full cPanel production install, browser compatibility test or security approval.
- Next unfinished work: close C02 after secret-hygiene audit, then validate one-time owner setup on new MySQL, expand roles and policies, build signed and executable plugin lifecycle, safe database evolution, core updater with verified backup/recovery and release acceptance.
- No background work is assumed; continue from open issues and current repo state on the next authorized session.

## Archived work log

### 2026-10-22
- Completed C02 initial credential hygiene: strengthened `.gitignore`, added `scripts/check_tracked_secrets.py`, and enforced it in Core CI. [Run 39605702869](https://github.com/manbir6-ux/84pad-core/actions/runs/39605702869) passed on PHP 8.3 and PHP 8.4. Issue #2 closed; path-name scanning is not comprehensive secret-content scanning.
- C03 read-only preflight command has landed in a parallel Core commit but still needs its own acceptance tests. C04/C05/C07 have initial previews, not production releases.
- Added `.github/workflows/core-ci.yml` on `core/C01-laravel-foundation` ([commit 632df90](https://github.com/manbir6-ux/84pad-core/commit/632df90b5cf87fef53ff9ca103a6f4cdfde4c895)).
- [GitHub Actions run 39605060743](https://github.com/manbir6-ux/84pad-core/actions/runs/39605060743) **PASSED** on PHP 8.3 and 8.4: Composer validation/install, environment setup, Laravel bootstrap/routes, SQLite migrations, PHPUnit tests, and PHP lint.
- Foundation now has model/config/migration/owner CLI/auth/control-studio **development previews**; installed on CI runners only. No cPanel production or UI browser acceptance tests done.
- Opened [draft PR #17](https://github.com/manbir6-ux/84pad-core/pull/17) for review; do NOT merge as a production release. Next focus: C02 config/secrets; C03+ require separate acceptance and security tests.


### 2026-10-21
- Verified GitHub repo `manbir6-ux/84pad-core` exists, remains **private**, and connector has push access.
- Previous commit created initial `README.md` with agreed Core 0.1 scope.
- Opened C01–C15 as individually testable GitHub issues.
- Added this persistent checkpoint and companion workflow.
- Added initial Laravel 12 source scaffold to `core/C01-laravel-foundation` (23 changed files): Composer manifest, bootstrapping, routing, basic page, test stubs, safe example environment and runtime directory stubs.
- Compared feature branch with `main`: 1 commit ahead, 23 changed files; the foundation is **not merged**.
- PHP 8.4.24 is present locally; Composer, mbstring and SQLite driver are missing in this runtime, so Laravel install and app tests remain **unverified**.
- Initial scaffold remains incomplete for full Laravel conventions (e.g. config and user model/migrations); expand foundation and verify before closing C01.
- **No production-ready Core, installer, login, plugin manager or successful Laravel runtime tests recorded yet.**

## Session resume instructions

1. Read `docs/PROGRESS.md`, `docs/WORKFLOW.md`, and current README.
2. Inspect current repository files, open issues and recent commits; don't assume this markdown is fully synchronized.
3. Pick the smallest uncompleted issue whose prerequisites are met. Start at **#1** unless fresh verified progress says otherwise.
4. Use a feature branch named `core/CXX-description` where possible; avoid overwriting user changes.
5. Add implementation + tests. Record exact executed validation commands and outputs in issue and/or commit.
6. Close an issue **only after** its checkboxes pass. Update this file with link to commit/PR and explicit next issue.
7. If interrupted, leave an issue comment containing: files changed, current branch/commit, validations performed, remaining steps and blockers.
8. Never claim background execution. Work resumes only when a user starts another authorized session.

## Frozen scope decisions

- Core remains lightweight, with Laravel 12, install/auth/permissions, Control Studio, plugin lifecycle/hooks, basic recovery boundaries.
- SSL God, Email, File Manager, Backup and other site functionality are **separate optional plugins** (not hard-coded Core).
- GitHub stays private for now; plugin development and central update/release server are later stages.
- Secrets, production backups and private keys must never go into source control.

## Handoff template

```md
Date/time & timezone:
Active issue:
Branch / latest commit:
Files actually changed:
What was verified (exact commands):
What failed / needs validation:
What is NOT done:
Next smallest action:
```
