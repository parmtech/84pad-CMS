<?php

namespace App\Support\Plugins;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class PluginQuarantine
{
    public function uninstall(string $slug): void
    {
        if (! preg_match('/^[a-z][a-z0-9-]{1,63}$/D', $slug)) {
            throw new RuntimeException('Invalid plugin identifier.');
        }

        $root = base_path('plugins');
        $plugin = $root.DIRECTORY_SEPARATOR.$slug;

        if (! is_dir($root) || is_link($root) || ! is_dir($plugin)
            || is_link($plugin)
            || ! is_file($plugin.'/.84pad-signed-upload')
            || is_link($plugin.'/.84pad-signed-upload')) {
            throw new RuntimeException('Only signed uploaded plugins can be uninstalled this way.');
        }

        $quarantine = storage_path('app/private/plugin-quarantine');
        File::ensureDirectoryExists($quarantine, 0700);
        $destination = $quarantine.DIRECTORY_SEPARATOR
            .$slug.'-'.date('YmdHis').'-'.bin2hex(random_bytes(8));

        // Disable first; if filesystem move fails the plugin stays installed,
        // but its executable code is no longer loaded. No plugin data deleted.
        DB::table('core_plugins')->updateOrInsert(
            ['slug' => $slug],
            ['version' => '0.1.0','enabled' => false,'updated_at' => now()]
        );

        if (! rename($plugin, $destination)) {
            throw new RuntimeException('Could not move plugin to safe quarantine.');
        }
    }
}
