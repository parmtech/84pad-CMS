<?php

namespace App\Console\Commands;

use App\Support\Backup\EncryptedSiteBackup;
use Illuminate\Console\Command;
use Throwable;

final class BackupSite extends Command
{
    protected $signature = '84pad:site-backup {--verify= : Read-only authentication check of an encrypted backup}';
    protected $description = 'Create or verify an encrypted private site export (restore is not implemented)';

    public function handle(EncryptedSiteBackup $backup): int
    {
        try {
            $verify = $this->option('verify');
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
