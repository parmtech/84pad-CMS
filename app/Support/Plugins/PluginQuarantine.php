<?php

namespace App\Support\Plugins;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class PluginQuarantine
{
    public function __construct(private readonly PluginCatalog $catalog) {}

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
        $manifest = $this->catalog->all()[$slug] ?? null;
        if (! $manifest) {
            throw new RuntimeException('Plugin manifest no longer valid.');
        }
        DB::table('core_plugins')->updateOrInsert(
            ['slug' => $slug],
            ['version' => $manifest['version'],'enabled' => false,'updated_at' => now()]
        );

        if (! rename($plugin, $destination)) {
            throw new RuntimeException('Could not move plugin to safe quarantine.');
        }
    }

    /**
     * @return array<int, array{snapshot:string,slug:string,version:string}>
     */
    public function snapshots(): array
    {
        $root = storage_path('app/private/plugin-quarantine');
        if (! is_dir($root) || is_link($root)) {
            return [];
        }

        $available = [];
        foreach (File::directories($root) as $directory) {
            $name = basename($directory);
            $slug = $this->snapshotSlug($name);
            if ($slug === null || is_link($directory)
                || ! is_file($directory.'/.84pad-signed-upload')
                || is_link($directory.'/.84pad-signed-upload')) {
                continue;
            }

            $manifest = $this->snapshotManifest($directory, $slug);
            if ($manifest === null) {
                continue;
            }
            $available[] = [
                'snapshot' => $name,
                'slug' => $slug,
                'version' => $manifest['version'],
            ];
        }
        usort($available, static fn (array $a, array $b): int =>
            strcmp($b['snapshot'], $a['snapshot'])
        );
        return $available;
    }

    public function restore(string $snapshot): string
    {
        $slug = $this->snapshotSlug($snapshot);
        if ($slug === null) {
            throw new RuntimeException('Invalid quarantine snapshot.');
        }

        $quarantineRoot = storage_path('app/private/plugin-quarantine');
        $pluginRoot = base_path('plugins');
        $source = $quarantineRoot.DIRECTORY_SEPARATOR.$snapshot;
        $destination = $pluginRoot.DIRECTORY_SEPARATOR.$slug;

        if (is_link($quarantineRoot) || ! is_dir($quarantineRoot)
            || is_link($pluginRoot) || ! is_dir($pluginRoot)
            || ! is_dir($source) || is_link($source)
            || ! is_file($source.'/.84pad-signed-upload')
            || is_link($source.'/.84pad-signed-upload')
            || file_exists($destination) || is_link($destination)) {
            throw new RuntimeException('Snapshot is missing or the destination is occupied.');
        }
        $manifest = $this->snapshotManifest($source, $slug);
        if ($manifest === null || ! $this->catalog->compatible($manifest)) {
            throw new RuntimeException('Snapshot manifest invalid or incompatible.');
        }

        // A restored plugin always starts disabled, even if it was active
        // when initially quarantined. Never run PHP in this method.
        DB::table('core_plugins')->updateOrInsert(
            ['slug' => $slug],
            ['version' => $manifest['version'], 'enabled' => false, 'updated_at' => now()]
        );

        if (! rename($source, $destination)) {
            throw new RuntimeException('Could not restore the quarantined plugin.');
        }

        return $slug;
    }

    private function snapshotSlug(string $snapshot): ?string
    {
        if (strlen($snapshot) > 110 || ! preg_match(
            '/^([a-z][a-z0-9-]{1,63})-\d{14}-[a-f0-9]{16}$/D',
            $snapshot,
            $parts
        )) {
            return null;
        }
        return $parts[1];
    }

    /** @return array{id:string,version:string,requires_core:string}|null */
    private function snapshotManifest(string $directory, string $slug): ?array
    {
        $path = $directory.'/plugin.json';
        if (is_link($path) || ! is_file($path) || filesize($path) > 16384) {
            return null;
        }
        try {
            $manifest = json_decode((string) file_get_contents($path), true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return null;
        }

        if (! is_array($manifest) || ($manifest['id'] ?? null) !== $slug
            || ! is_string($manifest['version'] ?? null)
            || ! is_string($manifest['requires_core'] ?? null)
            || ! preg_match('/^\d+\.\d+\.\d+$/D', $manifest['version'])
            || ! preg_match('/^\d+\.\d+\.\d+$/D', $manifest['requires_core'])) {
            return null;
        }
        return $manifest;
    }

}
