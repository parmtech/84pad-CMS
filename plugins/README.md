# 84PAD Plugin contract (preview)

Manually reviewed plugins have `plugins/<slug>/plugin.json` with `id`, `name`, `version`, `requires_core`. This phase scans JSON metadata only; it deliberately **does not run any plugin PHP**.

- `php artisan 84pad:plugins list` displays validated metadata.
- `php artisan 84pad:plugins activate demo-widget` records a database activation marker.
- `php artisan 84pad:plugins deactivate demo-widget` clears the marker.

Future work: audited entrypoint loading, capabilities and permissions, dependency validation, signatures, tested install/update/uninstall, and hook registration. An activation marker is **not** a working plugin.
