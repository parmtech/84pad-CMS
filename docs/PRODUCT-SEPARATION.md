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
