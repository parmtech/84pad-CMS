<?php

namespace App\Support\Plugins;

use App\Support\Hooks\HookBus;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class PluginRuntime
{
    /** @var array<string,bool> */
    private array $booted = [];

    public function boot(HookBus $hooks, PluginRegistry $registry): void
    {
        foreach ($registry->states() as $plugin) {
            if (! $plugin['enabled'] || ! $plugin['compatible']) {
                continue;
            }

            $slug = $plugin['id'];
            if (isset($this->booted[$slug])) {
                continue;
            }
            $this->booted[$slug] = true;

            // Activation is an explicit owner-granted execution permission.
            // PHP plugins execute with the hosting account's full privileges.
            $root = realpath(base_path('plugins'));
            $directory = realpath(base_path('plugins/'.$slug));
            $entry = $directory ? realpath($directory.'/bootstrap.php') : false;

            if ($root === false || $directory === false || $entry === false
                || ! str_starts_with($directory, $root.DIRECTORY_SEPARATOR)
                || ! str_starts_with($entry, $directory.DIRECTORY_SEPARATOR)
                || is_link($directory.'/bootstrap.php')
                || ! is_file($entry) || filesize($entry) > 262144) {
                // A metadata-only plugin is legitimate and needs no PHP bootstrap.
                continue;
            }

            try {
                $bootstrap = (static function (string $path): mixed {
                    return require $path;
                })($entry);

                if (! is_callable($bootstrap)) {
                    throw new RuntimeException('Plugin bootstrap must return a callable.');
                }
                $bootstrap($hooks);
            } catch (Throwable $e) {
                // Do not expose untrusted error strings to site visitors.
                Log::error('84PAD plugin boot failed', ['plugin' => $slug]);
            }
        }
    }
}
