<?php
namespace App\Console\Commands;

use App\Support\Plugins\PluginRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ManagePlugins extends Command
{
    protected $signature = '84pad:plugins {action=list : list|activate|deactivate} {slug?}';
    protected $description = 'Inspect/mark plugins (no untrusted code execution)';

    public function handle(PluginRegistry $plugins): int
    {
        if (! Schema::hasTable('core_installations') || ! Schema::hasTable('core_plugins')
            || ! DB::table('core_installations')->where('id',1)->exists()) {
            $this->error('Install 84PAD and run migrations first.');
            return self::FAILURE;
        }

        $action = (string)$this->argument('action');
        if ($action === 'list') {
            $rows = [];
            foreach ($plugins->states() as $p) {
                $rows[] = [$p['id'],$p['version'],$p['compatible']?'yes':'no',
                    $p['enabled']?'marked enabled':'disabled'];
            }
            $this->table(['Plugin','Version','Compatible','Activation marker'],$rows);
            return self::SUCCESS;
        }

        if (! in_array($action,['activate','deactivate'],true) || ! (string)$this->argument('slug')) {
            $this->error('Usage: 84pad:plugins list | activate SLUG | deactivate SLUG');
            return self::INVALID;
        }
        try {$plugins->setEnabled((string)$this->argument('slug'),$action==='activate');}
        catch (\InvalidArgumentException $e) {$this->error('Plugin not found/compatible.');return self::FAILURE;}
        $this->info('State saved. Executable plugin lifecycle is not yet available.');
        return self::SUCCESS;
    }
}
