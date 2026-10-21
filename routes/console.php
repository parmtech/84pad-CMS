<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('84pad:status', function (): void {
    $this->info('84PAD Core: foundation only. Installer/auth/plugins are not implemented.');
})->purpose('Show current foundation status');
