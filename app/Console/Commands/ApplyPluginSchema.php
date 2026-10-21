<?php

namespace App\Console\Commands;

use App\Support\Plugins\PluginSchemaManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

final class ApplyPluginSchema extends Command
{
    protected $signature = '84pad:plugin-schema {slug : Installed plugin ID} {--yes : Confirm create-only DDL on a verified backup}';
    protected $description = 'Explicitly create new plugin-owned tables without deleting existing data';

    public function handle(PluginSchemaManager $manager): int
    {
        try {
            $installed = Schema::hasTable('core_installations')
                && DB::table('core_installations')->where('id', 1)
                    ->whereNotNull('owner_user_id')->exists();
        } catch (Throwable $e) {
            $installed = false;
        }
        if (! $installed) {
            $this->error('Install 84PAD Core before applying plugin schemas.');
            return self::FAILURE;
        }

        if (! $this->option('yes') && ! $this->confirm(
            'Create new plugin-owned tables? Review your backup first; MySQL DDL is not transactional.',
            false
        )) {
            $this->warn('No schema changes performed.');
            return self::FAILURE;
        }

        try {
            $result = $manager->apply((string) $this->argument('slug'));
        } catch (RuntimeException $e) {
            // Log detail only to protected application logs; avoid exposing
            // sensitive database identifiers/connection strings to CLI users.
            $this->error('Plugin schema rejected or could not be completed. Inspect server logs and schema ledger.');
            return self::FAILURE;
        }

        $this->info('Plugin schema created: '.$result['created'].'; already applied: '.$result['skipped'].'.');
        return self::SUCCESS;
    }
}
