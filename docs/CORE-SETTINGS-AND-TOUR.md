# 84PAD Core Settings · cPanel · Guided Tour

## Owner Settings

After installing 84PAD, sign in as the Owner and open **Dashboard → Settings**. The Core is fully navigable without any cPanel account or API key; cPanel integration is **OFF** by default. Owner can control four independent switches: cPanel access, plugin runtime, signed plugin upload and UI animations.

## Central cPanel API credential

Enter the cPanel server **hostname** (for example `premium214.web-hosting.com`, not an HTTP URL or private IP), cPanel username, and cPanel API token. The token is stored encrypted using Laravel `APP_KEY` and **never displayed again**. Leave the API token field blank when editing the host/username to retain the current secret. Losing/changing APP_KEY without migration prevents decryption. Configure HTTPS and back up application configuration safely.

After saving credentials, explicitly switch cPanel integration ON. The Core never requires this for login, normal dashboard access, or unrelated plugins. A Save operation confirms only that the encrypted token is saved; it does not prove the remote API server accepted it.

### Plugin permissions

Each installed plugin receives a separate per-capability **deny-by-default** setting. Available labels include `email.read`, `email.manage`, `ssl.read`, `ssl.manage`, `files.read`, `files.manage`, `backup.read`, and `backup.create`. Effective access also requires an activated plugin, a saved token, and the global cPanel switch ON. Disconnecting cPanel clears the credential and revokes all grants.

The Core's preliminary `CpanelGateway` supports only a **fixed, read-only** set of UAPI operations (email-account list, installed SSL host list, file directory list constrained below `public_html`, and backup archive list). It sends HTTPS requests over port 2083 with TLS verification and a pinned public DNS address, disables redirects and does not expose the token through the client browser. Write/manage operations need future narrowly scoped methods and real cPanel compatibility tests. No live hosting API connection is verified yet.

**Important security limitation:** Signed PHP plugins run with the host account's PHP privileges and are *not sandboxed*. Core-level grant checks protect cooperative plugins using the gateway; they do not prevent intentionally malicious PHP from accessing host files, network or the database. Install trusted, reviewed plugins only.

## Interactive tutorial

On an Owner's first visit to Control Studio, the arrow/highlight Guided Tour runs automatically. It explains Overview, optional SSL/Email/File/Backup modules, plugin install, account security, and continues into Settings for cPanel credentials, feature switches, plugin grants, recovery and disconnect.

Users can choose **Next**, **Back**, **Skip**, or **Finish**. Completion is persisted per Owner in `core_user_preferences`. Under **Settings → Training**, the walkthrough can be replayed at any time. Tour text is local; it never sends analytics or credentials to a third party. Reduced-motion controls apply to the interactive overlay.

## Plugin upload size

- Maximum signed ZIP: **128 MiB** (134,217,728 bytes).
- Maximum uncompressed ZIP contents: **512 MiB** (536,870,912 bytes).
- Maximum ZIP file entries: **5000**. Extreme per-file compression ratios over 500:1 are rejected.
- The package still requires the existing trusted Ed25519 publisher signature, path safety checks and explicit Owner activation.
- On cPanel PHP settings, raise `upload_max_filesize` to at least `128M`, set `post_max_size` larger (for example `160M`), and ensure disk space/timeouts permit staging and extraction. Host plan limits may prevent uploads; the application cannot override hosting restrictions.

## Current release stage

These systems are part of **Core development** and require GitHub Actions tests and actual shared-hosting security/compatibility acceptance before public production use. SSL God, Email, File Manager and Backup are still independent future plugins and are not bundled functional Core services.