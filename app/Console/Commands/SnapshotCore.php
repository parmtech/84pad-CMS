<?php

namespace App\Console\Commands;

use App\Support\Updates\CoreSnapshot;
use Illuminate\Console\Command;
use RuntimeException;

final class SnapshotCore extends Command
{
    protected $signature = '84pad:core-snapshot {--verify= : Verify an existing source-only Core snapshot ZIP}';
    protected $description = 'Create or verify a private Core source-only recovery archive (no database or vendor)';

    public function handle(CoreSnapshot $snapshot): int
    {
        try {
            $verifyPath = $this->option('verify');
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
            $this->error('Snapshot failed. No update was attempted.');
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
