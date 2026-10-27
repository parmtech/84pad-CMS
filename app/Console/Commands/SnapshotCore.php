<?php

namespace App\Console\Commands;

use App\Support\Updates\CoreSnapshot;
use App\Support\Updates\CoreSnapshotRestoreException;
use Illuminate\Console\Command;
use RuntimeException;

final class SnapshotCore extends Command
{
    protected $signature = '84pad:core-snapshot {--verify= : Verify an existing source-only Core snapshot ZIP} {--restore= : Restore files from a source-only Core snapshot ZIP} {--sha256= : Expected SHA-256 digest for a restore} {--yes : Confirm the source restore without an interactive prompt}';
    protected $description = 'Create, verify, or restore a private Core source-only recovery archive (no database or vendor)';

    public function handle(CoreSnapshot $snapshot): int
    {
        $maintenancePath = storage_path('framework/down');
        $maintenanceOwned = false;
        $safeToResume = true;

        try {
            $verifyPath = $this->option('verify');
            $restorePath = $this->option('restore');
            if (is_string($verifyPath) && $verifyPath !== ''
                && is_string($restorePath) && $restorePath !== '') {
                $this->error('Choose either --verify or --restore, not both.');
                return self::FAILURE;
            }

            if (is_string($restorePath) && $restorePath !== '') {
                $sha256 = $this->option('sha256');
                if (! is_string($sha256) || $sha256 === '') {
                    $this->error('A separately recorded --sha256 digest is required for restore.');
                    return self::FAILURE;
                }
                if (! preg_match('/^[a-f0-9]{64}$/iD', $sha256)
                    || ! $snapshot->verify($restorePath)
                    || ! hash_equals(strtolower($sha256), strtolower((string) hash_file('sha256', $restorePath)))) {
                    $this->error('Snapshot path, SHA-256, or integrity check is invalid.');
                    return self::FAILURE;
                }
                if (! $this->option('yes')
                    && ! $this->confirm('Restore the source files in this snapshot? Database, vendor, plugins, uploads, and .env will not be restored.', false)) {
                    $this->warn('Restore cancelled. No files were changed.');
                    return self::FAILURE;
                }

                if (file_exists($maintenancePath) || is_link($maintenancePath)) {
                    $this->error('The application is already in maintenance mode. Resolve that state before restoring.');
                    return self::FAILURE;
                }

                $rollback = $snapshot->create();
                $this->line('Pre-restore safety snapshot: '.$rollback['path']);
                $this->line('Pre-restore SHA-256: '.$rollback['sha256']);
                $downStatus = $this->call('down');
                $maintenanceOwned = is_file($maintenancePath) && ! is_link($maintenancePath);
                if ($downStatus !== self::SUCCESS || ! $maintenanceOwned) {
                    $this->error('Could not put the application into maintenance mode. No source files were restored.');
                    return self::FAILURE;
                }

                try {
                    $result = $snapshot->restore($restorePath, $sha256, $rollback);
                } catch (CoreSnapshotRestoreException $e) {
                    $safeToResume = $e->automaticRecoveryCompleted;
                    throw $e;
                }

                $upStatus = $this->call('up');
                $maintenanceOwned = false;
                if ($upStatus !== self::SUCCESS || file_exists($maintenancePath)) {
                    $this->warn('Source restore completed, but maintenance mode may still be active. Check the site before reopening it.');
                    return self::FAILURE;
                }
                $this->info('Verified source snapshot restored for '.$result['files'].' files.');
                $this->line('Safety snapshot: '.$result['rollback_path']);
                $this->line('Safety snapshot SHA-256: '.$result['rollback_sha256']);
                $this->warn('This restores only files listed in the snapshot. Files added later and all excluded site data remain in place.');
                return self::SUCCESS;
            }

            if ($this->option('sha256') || $this->option('yes')) {
                $this->error('--sha256 and --yes can only be used with --restore.');
                return self::FAILURE;
            }

            if (is_string($verifyPath) && $verifyPath !== '') {
                if (! $snapshot->verify($verifyPath)) {
                    $this->error('Snapshot verification failed.');
                    return self::FAILURE;
                }
                $this->info('Snapshot passed integrity verification. No files were restored.');
                return self::SUCCESS;
            }

            $result = $snapshot->create();
            $this->info('Private Core source snapshot created and verified.');
            $this->line('Files: '.$result['files']);
            $this->line('Bytes: '.$result['bytes']);
            $this->line('SHA-256: '.$result['sha256']);
            $this->line('Saved to: '.$result['path']);
            $this->warn('This is SOURCE ONLY: back up databases, vendor, uploaded files, installed plugins and .env separately before updates.');
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            if ($maintenanceOwned && $safeToResume) {
                if ($this->call('up') !== self::SUCCESS || file_exists($maintenancePath)) {
                    $this->warn('Automatic recovery was available, but the site remains in maintenance mode. Inspect the source files before reopening it.');
                }
            } elseif ($maintenanceOwned) {
                $this->warn('The site remains in maintenance mode because automatic source recovery was incomplete.');
            }
        }
        return self::SUCCESS;
    }
}

