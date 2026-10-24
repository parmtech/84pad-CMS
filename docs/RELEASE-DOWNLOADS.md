# 84PAD Stable Download URLs & Release Publishing Contract

**Status:** Product rule approved in conversation, **documented only**; public 84pad.com release router/server is not connected or deployed from this repository. Implement the router in the **separate 84PAD website/release service**, not as a permanent Core capability. The Core/plugins keep independent versions. Follow these rules when that service is created.

## Non-negotiable public download behavior

A public website's primary download link **never changes** when a new approved stable version appears. A new release changes server-side **release resolution**, not the link embedded in the landing page, documentation, QR code, marketing material or email.

Examples of **planned URL contract, NOT currently verified as live**:

| Product/channel | Permanent website URL | Resolves to |
| --- | --- | --- |
| 84PAD Core stable | `https://84pad.com/download/core` | Latest **approved stable Core installer ZIP**, not a plugin ZIP |
| 84PAD Pages stable | `https://84pad.com/download/plugins/pages` | Latest approved native, signed Pages plugin `.pad` |
| 84PAD Builder stable | `https://84pad.com/download/plugins/builder` | Latest approved native, signed Builder plugin `.pad` |
| 84PAD Backup stable | `https://84pad.com/download/plugins/backup` | Latest approved native, signed Backup plugin `.pad` |

The canonical slug must come from each signed `plugin.json`, not a user-uploaded filename. The examples above are route design, not an assertion about deployed product names or active releases. Do not advertise a download for unreleased products.

Each version also gets an **immutable, version-specific artifact URL** under private/controlled storage or a verified release CDN: e.g. `releases/core/1.0.0/84pad-core-v1.0.0.zip` and `releases/plugins/builder/1.2.0/builder-v1.2.0.pad`. These versioned objects must never be overwritten in place; they enable reproducibility, offline verification, rollback and direct pinning for compatibility.

## Release index and resolution

Maintain an authoritative, atomic server-side release index keyed by:
- product identity (Core or plugin slug),
- platform/build variant if applicable,
- release channel (stable, beta, nightly),
- approved version (strict SemVer),
- immutable artifact storage key,
- byte length, SHA-256 digest, required Core version for plugins,
- signing key identity and artifact/package signature,
- publication/approval status and timestamp.

**Default public links resolve only to the latest *approved stable* compatible release.** No successful CI? No public update. A draft GitHub PR, development branch, arbitrary uploaded ZIP, unverified cPanel staging package or unapproved build must never become a stable download.

For future download endpoints:
1. Validate product slug and channel against a strict allowlist/catalog. Never resolve arbitrary user-provided paths, filenames, locations or URLs.
2. Read the published release-index **snapshot**. If missing/not approved, return a friendly `404`/unavailable page; do not silently serve dev builds.
3. Verify the published object exists, matches its SHA-256 and approved signed metadata (prefer an immutable object store/CDN and signed publication pipeline). Reject/alert on a mismatch.
4. Respond with a temporary **302 or 307** redirect to the immutable versioned HTTPS artifact, or securely stream it from private storage when signed URL routing is not supported. Never use permanent 301/308 for a moving `latest` URL.
5. Set `Cache-Control: no-store` (or an intentionally tiny, purged TTL) on the **moving selector/redirect** and use immutable long cache headers only on the **versioned artifact**. CDN and browser caching must not pin the selector to old releases.
6. Set the versioned filename in `Content-Disposition` when streaming (e.g. `84pad-core-v1.0.0.zip` or `builder-v1.2.0.pad`). The marketing/site URL remains unchanged.
7. Serve HTTPS only. Log package ID, version, anonymous request event and success/failure without secret credentials or personally identifying download data. Apply abuse-rate limits suitable for shared hosting.

Do not assume that a numeric greatest version is safe: release approval, compatibility and channel restrictions win. Rollbacks change only the **stable index pointer** to a previously approved, still verified immutable artifact. Existing version-specific links continue to work. Core/package download types must not be mixed: a full Core ZIP is *not* a `.pad` plugin.

For the planned automatic plugin updater, publish a machine-readable **version metadata endpoint** alongside the stable download and signed artifact. Clients compare installed version, min Core version, dependencies, expected checksum and signature **before** download/activation. No silent downgrade, forced migration or automatic activation of untrusted plugin PHP. If release keys rotate, support planned key rollover and revocation policy.

## Controlled publishing workflow

1. Build separate Core and plugin distributions using reproducible CI.
2. Validate PHP/MySQL and signed package contract; test cPanel install/update/recovery, archive integrity and version compatibility.
3. Record approval by the release owner (explicit approval before production deployment).
4. Upload artifact to **new immutable path**; verify length and SHA-256 after upload.
5. Sign/publish metadata and atomically change only the correct product+channel pointer.
6. Smoke-test stable URL twice: `HEAD`/`GET` resolve to approved version and displayed download filename agrees. Verify old version-specific URL remains available.
7. If broken, immediately revert the pointer to the previous approved stable artifact; preserve logs and user data.

**Never edit public website download button URLs for routine product updates.**

## Implementation boundary and test status

- The **84pad.com website/release service** owns the selector route, publishing index, HTTPS/CDN/cache headers, object storage and signed distribution.
- 84PAD **Core** owns version checking and signed Core updater/recovery foundation, not the website's release hosting.
- Each 84PAD **plugin** keeps its own version, signed `.pad` artifact and independently compatible release history.
- The website source, release-server credentials, signing keys, hosted files and live domain deployment were **not available in the connected GitHub repositories** when this contract was written on 2026-10-24.
- **Status: planned/not deployed.** There are no GitHub Releases in `manbir6-ux/84pad-core` at this checkpoint. Do not claim `/download/core` or `/download/plugins/...` works before real domain, storage and server acceptance tests pass.

## Acceptance checks before launch

- Stable URL unchanged across v1.0.0 → v1.0.1 and resolves to new signed stable release.
- Explicit v1.0.0 immutable URL still returns exact original SHA-256.
- Beta/nightly/draft releases never affect stable download.
- Rollback switches stable selector back without modifying any versioned artifact.
- Plugin `.pad` archive is not served by a Core URL and vice versa.
- Invalid product slug and path traversal are blocked.
- Expired/missing/mismatched artifacts fail closed, with usable user-facing errors.
- Content-Disposition uses verified artifact name/version and HTTPS cache rules are correct.
- User data and installed plugin data remain untouched by website release publication.
