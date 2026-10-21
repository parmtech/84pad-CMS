<?php

namespace App\Console\Commands;

use App\Support\Updates\UpdateInspector;
use Illuminate\Console\Command;
use RuntimeException;

final class InspectCoreUpdate extends Command
{
    protected $signature = '84pad:update-inspect {path : Local ZIP file path} {signature : Base64 detached Ed25519 signature}';
    protected $description = 'Check signature, archive paths and compatibility without installing the update';

    public function handle(UpdateInspector $inspector): int
    {
        try {
            $result = $inspector->inspect(
                (string) $this->argument('path'),
                (string) $this->argument('signature')
            );
        } catch (RuntimeException $e) {
            $this->error('Update rejected. Ensure the artifact is trusted and compatible.');
            return self::FAILURE;
        }

        $this->info('Verified signed update: '.$result['version']);
        $this->line('Requires Core: '.$result['requires_core']);
        $this->line('Requires PHP: '.$result['requires_php']);
        $this->line('ZIP entries: '.$result['files']);
        $this->warn('Inspection only: no files, services, databases or plugin states were changed.');
        return self::SUCCESS;
    }
}
