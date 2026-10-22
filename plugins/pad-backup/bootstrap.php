<?php
declare(strict_types=1);

use App\Support\Hooks\HookBus;
use Pad\Backup\BackupService;
use Pad\Backup\BackupPanel;

require_once __DIR__.'/src/BackupService.php';
require_once __DIR__.'/src/BackupPanel.php';

return static function (HookBus $hooks): void {
    $hooks->listen('core.dashboard_widgets', static function (array $widgets): array {
        $widgets[] = [
            'title' => '84PAD Backup',
            'description' => 'Encrypted full-site snapshots · automatic 60-day cleanup · manual import/export',
            'url' => '/dashboard/extensions/pad-backup',
        ];
        return $widgets;
    });

    $hooks->listen('core.plugin_request', static function (array $payload): array {
        if (($payload['slug'] ?? '') !== 'pad-backup') return $payload;
        $payload['response'] = (new BackupPanel(new BackupService()))->handle(
            $payload['request'], (string) $payload['action']
        );
        return $payload;
    });

    $hooks->listen('core.schedule_tick', static function (mixed $utc): void {
        (new BackupService())->tick();
    });
};
