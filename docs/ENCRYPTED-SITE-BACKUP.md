# Encrypted site export - development preview

**Status:** Creation and read-only verification are implemented on the Core development branch. **Automatic full-site restoration is NOT implemented. This does not make Core production ready.**

## Scope and threat model

The command exports installed Core source files, Composer dependencies, plugin code, public/private media and the private environment file. It also captures MySQL/SQLite base-table CREATE statements and row values as authenticated encrypted records.

- Site file selection: app, bootstrap (excluding runtime cache), config, database (excluding live SQLite files), resources, routes, public, plugins, vendor, storage/app/public, storage/app/private, and relevant root files including .env and composer.lock when present.
- This is a **logical** database export, not a complete server-level dump. Views, triggers, routines, grants and server settings are omitted; standalone SQLite indexes are also omitted. Restore cannot be considered ready until these gaps are covered.
- Prior backup files, temporary restore data and caches are excluded. Unexpected symlinks are rejected. The standard public/storage symlink is skipped because its content is backed up from storage/app/public instead.
- Caps: 100,000 files, 4 GiB file payload, and bounded individual record sizes.
- Protection: PHP Sodium secretstream XChaCha20-Poly1305. Content, private configuration and filenames are encrypted. The application does not create a plaintext SQL dump or plaintext archive on disk. Verification authenticates and hashes without extracting or executing any content.

## Operator instructions - development preview only

1. Generate 32 unpredictable bytes locally with PHP (example command: php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'). Keep the base64 value off-host in a secure password vault. Losing it makes exports unreadable. Never share the key in Git, logs, command arguments or chat.
2. Add PAD_BACKUP_KEY to the *private untracked* .env. Clear an old cached configuration with php artisan config:clear if necessary.
3. For a consistent backup, first enter maintenance mode (php artisan down) and pause external writers or scheduled processes.
4. Run php artisan 84pad:site-backup on the installed host. The CLI creates a private encrypted .padbackup file in storage/app/private/site-backups, verifies it and prints its SHA-256 and path.
5. Run php artisan 84pad:site-backup --verify=/absolute/path/to/file.padbackup for read-only authentication. Copy the encrypted export off-host using the owner's secure method. Keep an independent SHA-256 record and the decryption key separately.
6. Resume normal operation only after operator review (php artisan up).

The key is never provided as a command-line argument. Missing or malformed keys cause fail-closed behavior. No remote transfer or recurring backups are configured.

## Still blocking release

- A **tested, safe restore** covering databases, users, plugins, uploads, dependencies and private configuration on a disposable host.
- Recovery validation for table views/triggers, file permissions, concurrent writes, MySQL consistency, and large sites.
- Off-host encrypted disaster-recovery rehearsal, key escrow and recovery runbook.
- Signed update application/rollback, pinned Composer dependency lock, cPanel fresh install, browser QA and independent security review.

The development export is **not** the one final release archive. No CI push automatically publishes a distribution package.
