<?php

use App\Support\Hooks\HookBus;

// A first-party example. This code runs only after the owner explicitly
// activates demo-widget. In real installations, treat PHP plugins as trusted
// software with full server privileges.
return static function (HookBus $hooks): void {
    $hooks->listen('core.dashboard_widgets', static function (array $widgets): array {
        $widgets[] = [
            'title' => 'Demo plugin running',
            'description' => 'An installed 84PAD plugin supplied this widget through a Core hook.',
        ];
        return $widgets;
    });
};
