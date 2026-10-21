<?php
namespace App\Support\Plugins;

final class PluginCatalog
{
    /** @return array<string, array{id:string,name:string,version:string,requires_core:string}> */
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
            if (! is_array($data) || ($data['id'] ?? null) !== $slug
                || ! is_string($data['name'] ?? null) || ! is_string($data['version'] ?? null)
                || ! is_string($data['requires_core'] ?? null)
                || strlen($data['name']) > 100
                || ! preg_match('/^\d+\.\d+\.\d+$/D',$data['version'])
                || ! preg_match('/^\d+\.\d+\.\d+$/D',$data['requires_core'])) continue;
            $found[$slug] = [
                'id'=>$slug,'name'=>$data['name'],'version'=>$data['version'],
                'requires_core'=>$data['requires_core'],
            ];
        }
        ksort($found);
        return $found;
    }

    public function compatible(array $plugin): bool
    {
        return version_compare('0.1.0', $plugin['requires_core'], '>=');
    }
}
