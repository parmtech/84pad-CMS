# C01 Laravel foundation — status and local setup

**Status:** source scaffold implemented on feature branch; runtime tests are **not** verified.
No installer, authentication, plugin engine or Control Studio are implemented.

## Prerequisites
- PHP 8.2+ with Laravel-required extensions (including mbstring, PDO and database driver)
- Composer 2.x
- SQLite database driver for local development, or MySQL/MariaDB
- Git; Node and npm may be added when Vite assets are introduced

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan serve
```

Configure `DB_DATABASE` to the actual absolute SQLite path or use MySQL in your local `.env` before running migrations.
The baseline page uses file sessions and does not require a database connection.

## Verify

```bash
php artisan --version
php artisan route:list
php artisan test
```

Never expose the scaffold as a finished production CMS. In production set `APP_DEBUG=false`, serve **only** the `public/` directory, and never commit `.env` or any secret.
