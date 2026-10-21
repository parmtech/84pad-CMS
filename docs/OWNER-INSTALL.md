# Owner installation — development CLI

The first-owner CLI installer has passed automated SQLite and MySQL tests, including concurrent MySQL first-owner attempts. **84PAD is not approved for live production use.** A browser-based installation wizard is tracked separately in [issue #18](https://github.com/manbir6-ux/84pad-core/issues/18).

## Fresh installation on a development host

1. Install compatible PHP extensions and Composer dependencies; copy `.env.example` to an untracked `.env`.
2. Configure an empty test MySQL/SQLite database and set `APP_KEY` with `php artisan key:generate`.
3. Run `php artisan 84pad:doctor`; fix failed prerequisites before any migration.
4. Run `php artisan 84pad:install --name="Site Owner" --email="owner@example.test"`.
5. Enter a strong password into the **hidden prompt** (at least 12 characters); it must not be placed in command arguments.
6. Open `/login` over HTTPS with production debug mode disabled when testing behind a correctly configured host.

## Headless CI/password managers

The CLI also supports `--password-stdin`, reading a single password line from standard input instead of command arguments. Use this only with trusted local stdin or your deployment secret manager. Do **not** hard-code real passwords into shell scripts, GitHub Actions YAML, logs, terminal history, or the repository. The GitHub race tests use **dummy throwaway CI-only passwords**.

## What has actually been tested

- [Core CI run #39609985107](https://github.com/manbir6-ux/84pad-core/actions/runs/39609985107): PHP 8.3/8.4 Composer install, routes, migrations, PHPUnit and syntax checks.
- [MySQL run #39609985317](https://github.com/manbir6-ux/84pad-core/actions/runs/39609985317): MySQL 8 migrations/test suite and two concurrent first-owner CLI processes. Exactly one installer succeeded, with one linked owner and one installation marker.
- Invalid password and repeat-installation tests also pass.

## What remains

This is still **CLI-only**; no first-run public web installer, no tested shared-host deployment, no emergency recovery release, and no complete plugin execution engine. Do not publish a production installation yet.
