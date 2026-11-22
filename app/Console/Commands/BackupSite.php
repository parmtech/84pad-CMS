<?php

namespace App\Console\Commands;

use App\Support\Backup\EncryptedSiteBackup;
use Illuminate\Console\Command;
use Throwable;

final class BackupSite extends Command
{
    protected $signature = '84pad:site-backup
        {--verify= : Read-only authentication check of an encrypted backup}
        {--inspect= : Read-only recovery inventory of an encrypted backup}';
    protected $description = 'Create or verify an encrypted private site export (restore is not implemented)';

    public function handle(EncryptedSiteBackup $backup): int
    {
        try {
            $verify = $this->option('verify');
            $inspect = $this->option('inspect');
            if (is_string($verify) && $verify !== '' && is_string($inspect) && $inspect !== '') {
                throw new \RuntimeException('Choose either --verify or --inspect, not both.');
            }
            if (is_string($inspect) && $inspect !== '') {
                $summary = $backup->inspect($inspect);
                if ($summary === null) {
                    $this->error('Encrypted backup inspection failed (wrong key, corruption or invalid archive).');
                    return self::FAILURE;
                }
                $this->info('Encrypted backup authenticated. No files or database records were changed.');
                $this->line('Created (UTC): '.($summary['created_utc'] ?? 'not recorded'));
                $this->line('Database driver: '.($summary['database_driver'] ?? 'not recorded'));
                $this->line('File count: '.$summary['files'].'; bytes: '.$summary['bytes']);
                $this->line('Database tables: '.$summary['tables'].'; rows: '.$summary['rows']);
                $this->warn('Recovery inventory only: database and file restoration are NOT implemented.');
                return self::SUCCESS;
            }
            if (is_string($verify) && $verify !== '') {
                if (! $backup->verify($verify)) {
                    $this->error('Encrypted backup verification failed (wrong key, corruption or invalid archive).');
                    return self::FAILURE;
                }
                $this->info('Encrypted backup verified. No files or database records were changed.');
                return self::SUCCESS;
            }

            $this->warn('This is an encrypted RECOVERY EXPORT, not an automatic full-site restore.');
            $this->warn('Take the site offline for a consistent media/database capture and keep your encryption key separately.');
            $result = $backup->create();
            $this->info('Encrypted site export created and authenticated.');
            $this->line('File count: '.$result['files']);
            $this->line('File bytes: '.$result['bytes']);
            $this->line('Database tables: '.$result['tables'].'; rows: '.$result['rows']);
            $this->line('SHA-256: '.$result['sha256']);
            $this->line('Private file: '.$result['path']);
            $this->warn('Move this encrypted file off-host. Do not lose PAD_BACKUP_KEY.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Site export failed: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
