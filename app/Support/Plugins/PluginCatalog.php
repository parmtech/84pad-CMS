<?php
namespace App\Support\Plugins;

use App\Support\Settings\CpanelPermissions;

final class PluginCatalog
{
    /** @return array<string, array{id:string,name:string,version:string,requires_core:string,cpanel_capabilities:list<string>}> */
    public function all(): array
    {
        $root = base_path('plugins');
        if (! is_dir($root) || is_link($root)) return [];
        $found = [];
        foreach (glob($root.'/*/plugin.json') ?: [] as $file) {
            $dir = dirname($file);
            if (is_link($dir) || is_link($file) || ! is_file($file) || filesize($file) > 16384) continue;
            $slug = basename($dir);
            if (! preg_match('/^[a-z][a-z0-9-]{1,63}$/D', $slug)) continue;
            try {$data = json_decode((string)file_get_contents($file),true,16,JSON_THROW_ON_ERROR);}
            catch (\JsonException $e) {continue;}
            $manifest = $this->normalizeManifest($data, $slug);
            if ($manifest === null) continue;
            $found[$slug] = $manifest;
        }
        ksort($found);
        return $found;
    }

    /** @return array{id:string,name:string,version:string,requires_core:string,cpanel_capabilities:list<string>}|null */
    public function normalizeManifest(mixed $data, ?string $expectedSlug = null): ?array
    {
        if (! is_array($data)
            || ! is_string($data['id'] ?? null)
            || ! preg_match('/^[a-z][a-z0-9-]{1,63}$/D', $data['id'])
            || ($expectedSlug !== null && $data['id'] !== $expectedSlug)
            || ! is_string($data['name'] ?? null)
            || trim($data['name']) === ''
            || strlen($data['name']) > 100
            || ! is_string($data['version'] ?? null)
            || ! preg_match('/^\d+\.\d+\.\d+$/D', $data['version'])
            || ! is_string($data['requires_core'] ?? null)
            || ! preg_match('/^\d+\.\d+\.\d+$/D', $data['requires_core'])) {
            return null;
        }

        $capabilities = $data['cpanel_capabilities'] ?? [];
        if (! is_array($capabilities) || ! array_is_list($capabilities)
            || count($capabilities) > count(CpanelPermissions::CAPABILITIES)) {
            return null;
        }
        foreach ($capabilities as $capability) {
            if (! is_string($capability) || ! isset(CpanelPermissions::CAPABILITIES[$capability])) {
                return null;
            }
        }
        if (count(array_unique($capabilities)) !== count($capabilities)) {
            return null;
        }

        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'version' => $data['version'],
            'requires_core' => $data['requires_core'],
            'cpanel_capabilities' => $capabilities,
        ];
    }

    public function compatible(array $plugin): bool
    {
        return isset($plugin['requires_core'])
            && is_string($plugin['requires_core'])
            && version_compare((string) config('84pad.core_version', '0.1.0'), $plugin['requires_core'], '>=');
    }
}
