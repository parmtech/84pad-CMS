# Experimental plugin request and scheduler bridge (0.1)

This is a proposed generic Core contract on development branch `plugin/backup-v0.1`, not an approved/stable released API. Requires Core review/owner approval.

- An activated compatible plugin may register callback on `core.plugin_request` via `bootstrap.php`.
- The hook receives `['slug'=>string, 'action'=>string, 'request'=>Illuminate\\Http\\Request, 'response'=>null]`. The plugin must check the slug and return the payload with a Symfony Response assigned to `response`. Owner-only GET/POST route `/dashboard/extensions/{slug}/{action?}` is CSRF-protected by Laravel web middleware.
- Plugins may register `core.schedule_tick`. Called every minute by `php artisan 84pad:plugins:tick` when the runtime switch permits. Plugins must protect their own work with file locks and ensure idempotency.
- Configure the system cron to run `php artisan schedule:run` every minute from the installed Core directory. Without working cron, automated jobs do not run. Request-time scheduling and hidden external calls are intentionally absent.
- The hook interface is not a sandbox: trusted activated PHP plugins run with the full hosting account privilege. Owner-only checks are mandatory. No Core/other plugin tables may be altered as a side effect.
- Review on PHP 8.3/8.4; test with MySQL/MariaDB, Laravel route caching, plugin activation switches and uninstalled-first-run before merging.

This contract is a temporary adapter. A formal versioned extension provider contract should replace it before Core 1.0.
