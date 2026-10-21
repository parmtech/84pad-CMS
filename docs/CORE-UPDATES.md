# Signed Core update inspection — development preview

84PAD has a **non-installing** release inspector. It authenticates a ZIP against pinned Ed25519 release public keys, checks a small root `update.json` manifest, detects unsafe archive paths/symlinks and evaluates Core/PHP version compatibility. It **does not** unpack or modify any app files, database tables or plugin states.

## Bundle contract

The local ZIP contains `update.json` at its root, plus the files intended for a future signed updater. Example:

```json
{"version":"0.2.0","requires_core":"0.1.0","requires_php":"8.2.0"}
```

The ZIP must be signed by a trusted release publisher, using an Ed25519 detached signature over the binary payload `UTF8("84PAD_CORE_UPDATE_V1\n") || SHA256(zip_bytes)`. Configure the publisher's **public** signing key as base64 in the host's untracked `PAD_RELEASE_PUBLIC_KEYS` setting. Never expose private keys to 84PAD or check them into Git.

## Inspection CLI (no modifications)

```bash
php artisan 84pad:update-inspect /safe/download/84pad-core-update.zip BASE64_SIGNATURE_HERE
```

This command rejects an absent/untrusted/tampered signature, ZIP entries with path traversal/hidden path components, duplicate paths, symlinks, oversized/decompression-risk bundles, invalid manifests, downgrades and unsupported required versions. A successful check only means metadata/path/signature acceptance, **not that applying the update is safe or complete**.

## Not yet implemented

- No automatic downloading or central release server.
- No installer that writes the verified files.
- No database migration orchestration, maintenance-mode switch, off-host backup, rollback or post-upgrade smoke tests.
- No live cPanel staging/release acceptance.

These steps must be designed and tested before any production auto-update capability is enabled. See `docs/PROGRESS.md` and GitHub issue C14.