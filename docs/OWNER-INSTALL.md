# First-owner installation (development preview)

Owner setup is a **CLI-only** command. No public web registration route exists.

1. Install PHP extensions, Composer dependencies and create untracked `.env`.
2. Set `APP_KEY` via `php artisan key:generate`.
3. Configure database and ensure it is empty or its migration history is compatible.
4. Run `php artisan 84pad:install --name="Site Owner" --email="owner@example.com"`.
5. Enter a unique strong password in the hidden prompt (minimum 12 characters).
6. Visit `/login` over HTTPS in a properly configured web server.

The command migrates the database and records a one-time Core installation row. It never accepts a password command-line flag. Reinstall attempts fail; do **not** manually clear the marker to bypass this protection.

**LIMITATION:** This implementation has not been exercised against a live Laravel runtime yet. For the initial release, setup only on a **new empty database**, never an existing production site. Restoration and full authorization/policy testing are pending. Do not deploy to public production until tests pass.
