# 84PAD First-run Hosting Wizard (C20 — Staging)

> Current implementation is **staging only**. A public installer release requires live cPanel/MySQL testing, signed packaging, security review and rollback acceptance.

## Before installing

- Use PHP 8.3 or 8.4 with Laravel-required extensions, including PDO MySQL, OpenSSL, mbstring; cPanel automatic provisioning additionally needs PHP cURL.
- Serve **only** the application's \`public/\` directory on HTTPS. Keep \`.env\`, \`vendor/\`, \`storage/\` and source code outside the web root.
- Extract the approved **vendor-bundled staging distribution**, not the source-only repository ZIP.
- In the hosting File Manager create \`storage/app/private/first-run-token\` outside \`public/\`. Paste at least 32 random characters. Never share them in ChatGPT or GitHub. Ensure private storage is writable.
- Visit \`https://yourdomain.example/84pad-bootstrap.php\` (not \`/admin\`). This URL returns 404 without the token file, after an existing \`.env\`, or after setup is disabled.
- Use a new database dedicated to 84PAD. Installation into a nonempty database is blocked.

## Wizard: choose one connection method

### Option A — Manual database (works without cPanel API)

1. In any compatible hosting control panel create a **new MySQL/MariaDB database** and database user.
2. Assign database privileges to that user. Database and user names may be automatically prefixed with the hosting account name.
3. In the wizard choose **Manual MySQL**, enter the full database name, username, password, hostname (usually \`localhost\`) and port (usually \`3306\`).
4. Enter your private first-run token and continue. The bootstrap confirms the database is empty before writing \`.env\`.

### Option B — Automatic cPanel API database provisioning

1. Sign in to your hosting's **cPanel**. Go to **Security → Manage API Tokens → Create**. Copy the new token. [Official cPanel token documentation](https://api.docs.cpanel.net/cpanel/tokens).
2. Enter the **cPanel server hostname** shown in your HTTPS cPanel login URL, without \`https://\` or \`:2083\`. Provide the cPanel account username (not an email address) and API token. If the hostname fails TLS verification or is private/reserved, the request is refused.
3. Pick **new** database and database-user suffixes, plus a random database-user password (16+ characters). The wizard uses cPanel UAPI over verified **HTTPS port 2083** to create only the database, user and grant privileges.
4. Enter the database host provided by hosting (commonly localhost) and your private first-run token; the script checks the newly created database is empty.
5. In hosts that restrict cPanel tokens, cURL, outbound 2083 traffic, database creation, or UAPI functions, choose Manual setup instead.
6. Do not reuse existing database/user suffixes. **If a provisioning step fails after partial creation, resources may remain in cPanel.** Check MySQL Databases before retrying. No destructive automatic cleanup is attempted.

Important: the UAPI token is **never written to \`.env\` or the installation database** by this wizard. After Owner creation, cPanel connectivity is an *optional* separate encrypted Core Settings feature. Permission grants are deny-by-default per active plugin. Unrelated Core features work without a cPanel token.

## The remaining steps

1. If connection and database emptiness checks succeed, the bootstrap creates \`.env\` with a private APP_KEY, securely and exclusively, then redirects to \`/setup\`.
2. Create the Owner name, email and password (12+ characters). The Owner step runs preflight checks and non-destructive migrations under an exclusive lock.
3. Log in via \`/login\`. The Owner sees the Control Studio Guided Tour. The setup seed file is removed where permissions allow; bootstrap is disabled automatically once \`.env\` exists. Remove \`public/84pad-bootstrap.php\` after successful installation as defence in depth.

## Security and known limitations

- No API connection or user credential is required for manual setup. Never request full root/WHM credentials.
- The bootstrap checks an out-of-webroot setup token, uses CSRF and attempt limits, requires direct HTTPS, forbids overwriting \`.env\`, checks empty DB and never displays private DB connection errors.
- Automatic cPanel provisioning uses an allowlisted set of three UAPI operations only, no arbitrary endpoint/URL, TLS peer/host verification, pinned validated public IPv4 DNS and no redirects. Hostnames with private or reserved DNS results are rejected (use manual setup if cPanel is accessible only internally).
- cPanel MySQL names/permissions and API capabilities vary by provider. **No live cPanel account has been tested yet**.
- The initial private token must be created before the browser wizard is available, to prevent an unauthorised visitor from claiming an uninstalled website.
- Composer dependencies must be included in the staged distribution; this feature does not resolve full backup/rollback, production signing, cPanel/live acceptance or automated Core updates.

## Test status

Automated checks cover existing token/HTTP/.env guards, manual MySQL first-run on disposable CI database, wizard rendering and invalid cPanel-destination rejection. Actual cPanel API database provisioning against a live staging hosting account remains a separate required release gate. Only report test passes when corresponding CI runs are confirmed.
