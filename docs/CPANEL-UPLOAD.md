# C22 — cPanel public_html first-run installer (staging preview)

**Status: Implemented in review branch. Local package assembled and static checks performed; GitHub CI, real cPanel PHP-ZIP extraction, MySQL, production domain login and backup/rollback acceptance are NOT verified. No production deployment authorized.**

## Owner experience (English interface)

1. Choose a **new, empty, dedicated website document root** and enable HTTPS (cPanel SSL). Set PHP 8.3/8.4, with zip, pdo_mysql, mbstring, OpenSSL, XML, tokenizer, fileinfo. Do not upload into an existing production site's folder.
2. Upload and extract the `84PAD-cPanel-Public-Upload-STAGING.zip` contents **directly in public_html**. The ZIP contains an entry point, one-time deployment script, verified bundled runtime ZIP, restrictive `.htaccess`, and upload instructions. **This is a Core installation package, never a native plugin.**
3. Navigate to `https://YOUR_DOMAIN/`: initial entry extracts the runtime to a **sibling folder outside public_html**, named `84pad-core-app`. It copies only Laravel `public/` files into the website root, removes the bundled download, and reloads the URL to open the first-run hosting wizard.
4. In the wizard, choose **Manual MySQL** (existing newly created empty database and dedicated user) or **Automatic cPanel API** (one-time API token creates new database/user). Enter host credentials only in the HTTPS installer.
5. **No manually typed setup token.** Each browser session receives a random File Manager challenge filename `allow-84pad-<24 hex>.txt`. To prove ownership, create an empty file with exactly that name in `84pad-core-app/storage/app/private/` (outside public_html). The marker is never in web-readable storage. Click Initialize using the **same browser**.
6. PHP checks CSRF, the session-specific private marker, HTTPS, runtime, empty database, and exclusive `.env` creation. It **generates** the real 32-byte `APP_KEY` and random internal `PAD_SETUP_TOKEN` automatically, and transfers Owner-only permission via `Secure; HttpOnly; SameSite=Strict` cookie limited to `/setup` (30-minute expiry). Deletes the proof file, then redirects to the Owner setup page.
7. Enter the Owner name, email and unique 12+-character password; Core runs its migrations and creates one Owner only. Then sign in via `/login`.
8. After success the installer permanently closes its setup paths and normal independent plugins remain optional.

### Why there is a one-time **file creation** despite no setup key

An entirely public, no-authentication Owner creation wizard would let the **first internet visitor** take over a newly deployed site, even before the real owner opens it. The per-session private File Manager marker is a server-ownership check that requires **no copied password or manually typed 32-character key**. The core does not use cPanel tokens after initial setup unless the owner separately enables encrypted cPanel access.

### Safety boundaries

- Never extract Laravel's `vendor/`, `app/`, `bootstrap/`, `storage/`, or `.env` into the public document root.
- Extraction to `../84pad-core-app` refuses existing target, existing public website files, symlinks/unsafe archive member names, checksum mismatches and occupied configuration. The inner archive is checked before extraction.
- Require HTTPS and a working server session; do not weaken Host, APP_KEY, database, CSRF, Owner, or password protections for convenience.
- The Owner setup browser cookie is **not** Laravel-encrypted because the pre-Laravel standalone PHP bootstrap mints it; `bootstrap/app.php` excludes that specific cookie from Laravel's encrypted-cookie handler. It is randomly generated, HTTPS-only, HttpOnly, path-limited, time-limited and validated against server-private config.
- Distribution bundle must be constructed from a source commit with production-only Composer runtime dependencies and a SHA-256 pinned inside the first-run deploy script. The ZIP upload must not contain `.env`, passwords, tokens, private file-manager challenge or GitHub secrets.
- On a failed extraction, stop rather than overwrite existing directories/files. Partially created files may require manual **disposable staging host** cleanup; never delete an existing website or database as an automatic retry.
- If Owner setup cookie expires after generating `.env` but before creating Owner, first-run recovery needs a separately verified procedure; do not simply disable the cookie authorization check.
- Domains whose document root is nested inside another domain's public root require host review to ensure the sibling private application folder is truly outside **all** served document roots.
- **No verified real cPanel/HTTPS/DB acceptance**, no production readiness claim.

## Follow-up tests (mandatory before release)

- PHP 8.3/8.4 cPanel/LiteSpeed `ZipArchive` path-validation and archive extraction acceptance.
- Writable folder permissions, relative private folder path, direct JS/CSS asset loading, 404 on internal archive, no external source leakage.
- Manual empty database and automatic cPanel UAPI provisioning against throwaway cPanel account.
- Proof-file per-browser authorization, replay prevention, empty-database protection, CSRF, bad cookie, cookie expiry, concurrent installs and installation marker after Owner.
- Authentication, refresh, sign-out and direct `/login` on HTTPS, hostname vs www and expected `APP_URL`.
- Composer vendor consistency, stale Laravel config-cache prevention, public uploads, plugin signed updates/recovery, backup and rollback checks.
- CI failures on C20/C21 must be diagnosed and green checks established before publication to permanent stable download links.

## Source of truth

The source entry point and PHP/Laravel wizard logic are in `public/index.php`, `public/84pad-bootstrap.php`, `app/Http/Controllers/Auth/FirstRunController.php`, `bootstrap/app.php`, and `resources/views/auth/first-run.blade.php` on review branch `core/C22-public-html-upload`. Production download links and release pointers are independently specified in `docs/RELEASE-DOWNLOADS.md`.
