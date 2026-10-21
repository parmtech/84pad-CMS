# Owner installation — unverified development code

This is a **CLI-only** first-owner installer. There is deliberately no public signup flow.

1. Install Composer, Laravel PHP extensions and configure the untracked `.env`.
2. Set APP_KEY with `php artisan key:generate`.
3. Configure a **new empty** database. Production deployment is not approved.
4. Run `php artisan 84pad:install --name="Site Owner" --email="owner@example.com"`.
5. Enter password in the hidden prompt (minimum 12 characters).
6. Sign in at `/login` using HTTPS when in a secured environment.

The setup transaction inserts a unique installation marker and then the owner. Repeated installer attempts fail without adding another owner.

**Known limitation:** Composer install, integration tests, rollback and fresh installation have not been executed in this session. Treat the entire build as development-only, not production-ready.
