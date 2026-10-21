# Plugin schema changes — additive v0.1

84PAD keeps plugin data separate from Core migrations. The first implementation supports **explicit, additive, create-only schema updates**. It never automatically executes plugin PHP migrations or drops/overwrites production tables.

## Plugin schema.json

Optional `schema.json` in a plugin directory:

```json
{"revision":1,"tables":[{"name":"padp_example_plugin_entries","columns":[{"name":"title","type":"string","length":150},{"name":"active","type":"boolean"},{"name":"notes","type":"text","nullable":true}]}]}
```

Replace `example_plugin` with the plugin slug converted from dashes to underscores. Each new table must start with `padp_<plugin_slug>_`. The column `id` is always created automatically. Limits: 16 tables/revision, 24 columns/table, 32 KiB schema JSON.

Allowed types: `string` (max 191), `text`, `integer`, `unsignedBigInteger`, `boolean`, `timestamp`. No raw SQL, table/column drop, or column modification.

## Explicit installation

On an installed staging host with a verified off-host database backup run:

```bash
php artisan 84pad:plugin-schema example-plugin
```

CLI asks for confirmation. Trusted CI/controlled deployment can use `--yes` after backup. The `core_plugin_schema_history` ledger records slug, revision, table name and checksum. Repeat applications skip already-applied matching definitions. Changed checksums or pre-existing tables without history are rejected.

**MySQL DDL limitation:** DDL implicitly commits. Multi-table schema application is not all-or-nothing. If a crash occurs between creating a table and its history record, later runs refuse to adopt/overwrite the table; an administrator must reconcile it. There is no automatic migration rollback.

## Lifecycle boundaries

- Schema changes never run on plugin upload, activation or page visits.
- Quarantining/uninstalling a plugin does not drop its database tables.
- Restored plugin code is disabled by default, and its schema history is retained.
- Arbitrary future data transformations, destructive migrations, signed plugin version upgrades, and automated rollback remain unimplemented.

Treat Core as a staging preview, not a production release.