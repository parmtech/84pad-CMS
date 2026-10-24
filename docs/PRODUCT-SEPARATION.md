# 84PAD — Strict Product Separation: Release Server vs Core CMS

**Decision approved by owner (2026-10-24).** Do not combine these as a single product or installation ZIP.

## Product A: 84PAD Release Server (separate PHP application/repository)

- A private hosting release manager, ideally deployed to an independent dedicated update host (e.g. a future `updates.84pad.com` subdomain).
- Owns Owner login, product catalog, versioned immutable artifacts, approved stable release pointers, SHA-256/signature metadata, and permanent download endpoints. It accepts **uploads of Core, plugins and themes as files**, but **never extracts, installs or executes them**.
- Permanent links on that host, when deployed: `/download/core`, `/download/plugins/{slug}`, `/download/themes/{slug}`.
- Separate product codebase, deployment and version history from Core. The website can maintain branded stable URLs `https://84pad.com/download/...` and redirect to the release service; the public site must integrate these links separately.
- Owner review/approval and signing gates: no staging, draft or failing CI build can silently replace a stable pointer. Preserve previously published versions for rollback.
- A standalone **staging prototype** `84PAD-Release-Server-STAGING.zip` was locally authored on 2026-10-24, but **not committed to the Core repository and not deployed**. It uses PHP 8.3+, ZIP, Sodium, private disk storage (no MySQL), password-protected upload UI, cPanel File Manager first-run proof, per-product draft-to-stable approval and immutable/versioned downloads. A distinct repository is needed before ongoing development.

## Product B: 84PAD Core CMS (this repository)

- Independent self-hosted modular CMS users install on **their own** hosting. Contains a host installer and Owner login, Control Studio, authentication/security, plugin lifecycle and versioned extensibility, not central release hosting.
- Core's end-user cPanel setup script is **not the central release server**. The Core artifact may include a one-time installation wizard; that does not turn it into the update-server app.
- Core is uploaded to the Release Server **as an immutable ZIP artifact** with its own version (`84pad-core-vX.Y.Z.zip`). The Release Server must not run Core PHP when processing this file.
- A local `84PAD-Core-CMS-cPanel-STAGING.zip` preview exists as a separate artifact, copied from the earlier C22 staging upload package. This is **not release approved**; its CI/live install gates and Core update policy remain pending.

## Product C: independent plugins and themes

- Each has its own slug, semantic version, minimum Core version, supported contracts and native signed `.pad` file. Files should be stored as immutable `<slug>-v<version>.pad`.
- The Release Server stores/publishes packages; installed Core validates signatures and handles local installation/update/activation. One plugin release must not overwrite the Core or another plugin.
- Core CMS packages are **not valid native plugin `.pad` packages** and cannot be installed through the Plugin Manager.

## Explicit status & next tasks

- **Implemented locally (prototype only):** independent staging Release Server and separate Core CMS staging packages. ZIP integrity and PHP syntax checks passed. A local test copy of release server (PHP ZIP extension preflight intentionally bypassed in that *test copy only*) verified private proof rejection/acceptance, owner setup, admin session, draft isolation, stable pointer redirect, metadata and hash tamper rejection.
- **Not tested:** release-server ZIP uploads against an actual PHP ZIP extension, owner-trusted signing workflow, real cPanel deployment, HTTPS sessions, live domain DNS, marketing website redirects, MySQL/hosting Core setup or CI for the standalone Release Server. Both are **not production approved**.
- **Future repository:** Create a separate `84pad-release-server` GitHub repository; do not permanently add this service to Laravel Core. Add CI, signed release publishing and hosting acceptance there, then independently deploy after approval.
- **Permanent product requirement:** maintain unchanged website download links (see [RELEASE-DOWNLOADS.md](RELEASE-DOWNLOADS.md)), and never auto-promote an unapproved build.

## 2026-10-24 — Separate Release Server PHP 8.2 security-patched compatibility

- Owner encountered a website error saying the update host uses PHP 8.2 and requiring 8.3/8.4. Inspection of the separate PHP Release Server prototype showed no code requiring PHP 8.3 language features; its runtime gate had been unnecessarily strict.
- An independent local **staging** update was built, NOT added to this Laravel Core repository and NOT deployed to the server: `84PAD-Release-Server-PHP82-Security-Patched-STAGING.zip` (complete separate release server) and `84PAD-Release-Server-PHP82-Safe-Update-PATCH.zip` (existing server's index/lib/readme only).
- Security gate checks minimum **security-patched** PHP builds issued 2026-10-21: 8.2.35, 8.3.36, 8.4.27, 8.5.12. Older versions fail closed rather than bypassing critical remote-code-execution fixes. PHP 8.2 security support ends on **2026-12-31**; migrating to 8.4.27+ is recommended. PHP official security notes: https://www.php.net/archive/2026.php#2026-10-21-5.
- ZIP and Sodium remain independent prerequisites for respective upload/signature verification operations; never bypass signature verification. Owner login and existing verified downloads can run without ZIP/Sodium if the PHP security version gate passes.
- Verified locally: PHP 8.4 syntax lint, 10 minimum-version matrix cases, structure of requirements checks, ZIP structural/integrity validation (5-file complete / 3-file patch). No native PHP 8.2 interpreter available for compatibility execution; no cPanel/live-host test; production approval **pending**.
- **Server-only change:** do not package the Release Server as an 84PAD plugin, do not silently merge it into Core CMS and do not modify the Core's own PHP hosting requirements based on this separate server patch.


## 2026-10-24 — Standalone Release Server password-only recovery patch

- Release Server auth update authored and packaged **separately from Core**: `84PAD-Release-Server-Password-Only-UPDATE.zip` (four-file existing-host patch) and `84PAD-Release-Server-Password-Only-STAGING.zip` (full six-file preview). Kept in local artifacts; **not in a dedicated GitHub repository and not deployed**.
- Sign-in now requires only Owner password, not email/username. Existing stored password hash in private `84pad-release-data/owner.json` remains unchanged. No user-supplied chat password or password hash was bundled. Private first-run proof remains required.
- Owner admin panel has Change Password. Forgot Password is accessible from login and sends email **only to fixed `info@parmtech.au`**, with no user-supplied email address; fixed 15-minute one-use SHA-256-only token, serialized consumption, 3-per-hour email request throttle and cross-session invalidation on password rotation. A trusted HTTPS recovery origin is provided at first install or added in Owner admin settings for an existing host.
- Local verification: PHP 8.4 lint on `index.php`/`lib.php`; full and update ZIP integrity/path checks, no embedded shared password; HTTP integration in a disposable **test-only PHP copy**: first owner creation, password-only valid/invalid login, change password, other-session invalidation, mocked mail() delivery to fixed recipient, one-time email reset, new password login, and unchanged release index. Test harness bypassed the patched-PHP runtime gate and secure-cookie flag **only in test files** to exercise local HTTP; distributed production PHP retains both protections. No live host SMTP delivery or cPanel PHP 8.2 acceptance; **staging only**.
- Next: create independent `84pad-release-server` repository, commit the standalone server source and tests there, verify real HTTPS Mail/cPanel delivery and security settings, then approve and deploy. The Core repository stores only this cross-project integration checkpoint, not release-server PHP code.
