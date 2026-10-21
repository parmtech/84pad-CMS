# 84PAD Plugin contract — Core 0.1 development preview

84PAD Core keeps website capabilities in separate, optional plugins. Plugins under `plugins/<slug>/` use `plugin.json` with `id`, `name`, `version`, `requires_core`. Valid slugs use lowercase letters, digits and dashes.

## Trusted local plugin lifecycle

1. Owner installs a signed ZIP through Control Studio, or a trusted administrator stages a reviewed plugin directly under `plugins/`.
2. The Core validates the manifest and registers the plugin as **disabled**.
3. Owner activates it explicitly from Control Studio or trusted CLI.
4. If `bootstrap.php` exists, activation allows it to run on the Control Studio dashboard. The PHP file **must return a callable** accepting `App\Support\Hooks\HookBus`. Hooks can add dashboard widgets via `core.dashboard_widgets`.
5. Deactivation stops the plugin's bootstrap on subsequent dashboard requests.

**SECURITY:** PHP plugins are full-trust server-side code, not sandboxed. Activation may execute arbitrary PHP with the hosting account's privileges. Only install/activate reviewed plugins from publishers you trust. ZIP signature validity authenticates the publisher key; it does **not** prove the publisher code is harmless. The runtime catches errors where possible but cannot isolate all PHP side effects.

## Signed ZIP package

The ZIP contains `plugin.json` and optional `bootstrap.php` at the **archive root**, plus assets. No parent paths, hidden paths, symlinks or duplicate archive paths are allowed. Limits: ZIP <= 20 MiB, uncompressed <= 50 MiB, <= 1024 entries.

Owner supplies a base64-encoded detached Ed25519 signature over exactly:

```
UTF8("84PAD_PLUGIN_ZIP_V1\n") || SHA256(zip_file_binary_bytes)
```

`PAD_PLUGIN_PUBLIC_KEYS` is a comma-separated list of base64-encoded Ed25519 publisher **public** keys configured only in the host's untracked `.env` / environment. Without a pinned key, uploaded packages are rejected. Publisher **private** keys must never be uploaded to the CMS, included in a plugin or committed to GitHub.

## Safe uninstall (quarantine)

Only ZIPs installed through the trusted signed installer can be uninstalled in the UI. The Owner must type the exact plugin slug. Uninstall first disables the plugin and then moves its directory into private `storage/app/private/plugin-quarantine/` instead of immediately deleting files. Database tables and user data are deliberately **retained**. Bundled Core/demo plugins cannot be removed by this upload-uninstall flow.

This is a reversible staging mechanism, not a fully tested automated rollback. Owners can now restore matching signed-upload snapshots through the Control Studio Recovery vault. Restoration requires exact plugin ID confirmation and keeps the plugin disabled. It refuses an already occupied installed plugin destination. Automated Core/plugin database rollback remains future work. Quarantine storage should be considered sensitive and included in the site's recovery plan.

## Current limits

- No central marketplace, auto-update, dependency resolution or automatic migration rollback yet. Signed-upload quarantine uninstall **and guarded restoration** are implemented.
- Plugin execution currently integrates with Control Studio dashboard hooks, not arbitrary public-facing pages.
- A first-party demo lives in `plugins/demo-widget/` and is covered by automated activation/deactivation tests.
- Explicit create-only plugin schema changes use `schema.json` and the `84pad:plugin-schema` CLI. They are recorded in a checksum ledger and never execute on upload/activation; see [Plugin schema contract](../docs/PLUGIN-SCHEMA.md). Destructive changes, automatic rollback and central distribution are still pending.

The public 84PAD website and SSL God/Email/File Manager/Backup plugins are deferred until the Core is stabilized.
