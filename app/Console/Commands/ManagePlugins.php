<?php
namespace App\Console\Commands;

use App\Support\Plugins\PluginRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ManagePlugins extends Command
{
    protected $signature = '84pad:plugins {action=list : list|activate|deactivate} {slug?}';
    protected $description = 'Inspect and control local plugins (activation may execute PHP)';

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
                    $p['enabled']?'active':'disabled'];
            }
            $this->table(['Plugin','Version','Compatible','State'],$rows);
            return self::SUCCESS;
        }

        if (! in_array($action,['activate','deactivate'],true) || ! (string)$this->argument('slug')) {
            $this->error('Usage: 84pad:plugins list | activate SLUG | deactivate SLUG');
            return self::INVALID;
        }
        try {$plugins->setEnabled((string)$this->argument('slug'),$action==='activate');}
        catch (\InvalidArgumentException $e) {$this->error('Plugin not found/compatible.');return self::FAILURE;}
        $this->warn('Only activate PHP plugins from developers you trust. Active code runs with the hosting account privileges.');
        $this->info('Plugin state saved.');
        return self::SUCCESS;
    }
}
