<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('84pad:status', function (): void {
    $this->info('84PAD Core 0.1 — development preview, NOT production ready.');
    $this->line('Foundation: Laravel 12, Owner auth and Control Studio.');
    $this->line('Plugins: local signed ZIP install, activation/hook loading, quarantine uninstall.');
    $this->line('Updates: Ed25519 verification only; updater and rollback not built.');

    try {
        $installed = Schema::hasTable('core_installations')
            && DB::table('core_installations')->where('id', 1)->whereNotNull('owner_user_id')->exists();
        $this->line('Installed Owner: '.($installed ? 'yes' : 'not yet'));
    } catch (\Throwable $e) {
        $this->warn('Installation database: not available.');
    }
})->purpose('Show installed Core state and remaining release limitations');
