# Signed Core update inspection — development preview

84PAD has a **non-installing** release inspector. It authenticates a ZIP against pinned Ed25519 release public keys, checks a small root `update.json` manifest, detects unsafe archive paths/symlinks and evaluates Core/PHP version compatibility. It **does not** unpack or modify any app files, database tables or plugin states.

## Bundle contract

The local ZIP contains `update.json` at its root, plus the files intended for a future signed updater. Example:

```json
{"version":"0.2.0","requires_core":"0.1.0","requires_php":"8.3.0"}
```

The ZIP must be signed by a trusted release publisher, using an Ed25519 detached signature over the binary payload `UTF8("84PAD_CORE_UPDATE_V1\n") || SHA256(zip_bytes)`. Configure the publisher's **public** signing key as base64 in the host's untracked `PAD_RELEASE_PUBLIC_KEYS` setting. Never expose private keys to 84PAD or check them into Git.

## Inspection CLI (no modifications)

```bash
php artisan 84pad:update-inspect /safe/download/84pad-core-update.zip BASE64_SIGNATURE_HERE
```

This command rejects an absent/untrusted/tampered signature, ZIP entries with path traversal/hidden path components, duplicate paths, symlinks, oversized/decompression-risk bundles, invalid manifests, downgrades and unsupported required versions. A successful check only means metadata/path/signature acceptance, **not that applying the update is safe or complete**.

## Core source snapshot and limited source restore (not a site backup)

`php artisan 84pad:core-snapshot` builds a local private ZIP with a per-file SHA-256 manifest from selected Core source directories; runtime secrets, `.env`, upload/storage folders, plugins and `vendor` are excluded. The command verifies the saved archive before reporting a SHA-256 digest and its location. To inspect a stored archive: `php artisan 84pad:core-snapshot --verify=/absolute/path/to/snapshot.zip`.

To restore the source files recorded in a snapshot, provide its SHA-256 digest from a separately stored record:

```bash
php artisan 84pad:core-snapshot --restore=/absolute/path/to/snapshot.zip --sha256=RECORDED_SHA256
```

The command asks for confirmation. For an explicitly unattended restore, add `--yes`. It first creates and verifies a fresh pre-restore source snapshot, then enters maintenance mode, stages and checks all selected files, and replaces them. If replacement fails, it attempts to restore the pre-restore source snapshot; when that automatic recovery is incomplete, the application remains in maintenance mode for operator recovery.

**Limitations:** Restore replaces only files listed in the selected snapshot. Files added since that snapshot remain. Database, installed plugin files, uploads, dependencies, hosting settings and `.env` are not restored. Take a full site backup and schedule a maintenance window before using this source-only recovery command. Preserve the selected snapshot's SHA-256 digest outside the server to detect archive replacement.
## Not yet implemented

- No automatic downloading or central release server.
- No signed release installer or updater that applies inspected update bundles.
- No database migration orchestration, integrated database backup/rollback, off-host backup, or post-upgrade smoke tests.
- No live cPanel staging/release acceptance.

These steps must be designed and tested before any production auto-update capability is enabled. See `docs/PROGRESS.md` and GitHub issue C14.

