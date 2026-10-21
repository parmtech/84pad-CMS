# Secure first-run web installer (development preview)

This is a **preconfigured-host installer**, not yet a complete upload-and-click installer. Do not deploy to an untrusted/public production hostname until the deployment/security acceptance phase passes.

## Host preparation (cPanel, local server or staging)

1. Upload the application and install Composer dependencies, serving **only** the Laravel `public/` directory.
2. Copy `.env.example` to the untracked `.env` on the host. Configure `DB_*`, `APP_KEY`, `APP_URL` and HTTPS. Run `php artisan key:generate` on a trusted development host, or provide a valid generated APP_KEY through secure host configuration.
3. Generate a cryptographically random secret **at least 32 characters** and set `PAD_SETUP_TOKEN` in the untracked host environment. Never put this secret in Git, screenshots, browser URLs, emails, or CLI command arguments. When absent, GET/POST `/setup` both return 404.
4. Visit `https://your-domain.example/setup` and fill in the private token and Owner credentials. HTTPS and `APP_DEBUG=false` are required when APP_ENV=production.
5. The server checks prerequisites, applies **non-destructive Laravel migrations**, then uses a transaction and unique installation marker for the Owner.
6. Visit `/login` and log in with the new Owner account. Once installed, both setup routes are locked.

### Protections

- Setup secret is checked using `hash_equals`; incorrect secret returns a generic 403.
- Setup POST uses Laravel web/CSRF middleware and is rate limited.
- Absent setup secret, existing Owner or users, or an installed marker block setup.
- DB/host errors are returned as generic responses; passwords and tokens are not flashed back to users.
- Browser setup does **not** ask the user to reveal cPanel API keys or passwords to ChatGPT.
- Local plugins are separate and are not required during installation.

### Known limitations

- `.env` and `APP_KEY` are still preconfigured by the host owner. A safe standalone PHP bootstrap screen for DB credential creation and Composer-free package distribution is a future improvement.
- Installation against an existing production database is not supported; use a dedicated empty DB and a full off-host backup.
- Concurrency is verified for the CLI installer with MySQL; browser session and real shared-host tests remain necessary.
- Successful automated CI does **not** mean this software has passed penetration testing or can be promoted to production.

Check `docs/PROGRESS.md` for the latest automated test results and outstanding acceptance steps.
