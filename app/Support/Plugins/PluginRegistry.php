<?php
namespace App\Support\Plugins;

use Illuminate\Support\Facades\DB;

final class PluginRegistry
{
    public function __construct(private readonly PluginCatalog $catalog) {}

    public function states(): array
    {
        $registered = DB::table('core_plugins')->get()->keyBy('slug');
        $all = [];
        foreach ($this->catalog->all() as $slug => $manifest) {
            $all[$slug] = $manifest + [
                'compatible'=>$this->catalog->compatible($manifest),
                'enabled'=>(bool)($registered[$slug]->enabled ?? false),
                'removable'=>is_file(base_path('plugins/'.$slug.'/.84pad-signed-upload')),

            ];
        }
        return $all;
    }

    public function setEnabled(string $slug, bool $enabled): void
    {
        $manifest = $this->catalog->all()[$slug] ?? null;
        if (! $manifest || ! $this->catalog->compatible($manifest)) {
            throw new \InvalidArgumentException('Unknown or incompatible plugin.');
        }
        DB::table('core_plugins')->updateOrInsert(
            ['slug'=>$slug],
            ['version'=>$manifest['version'],'enabled'=>$enabled,'updated_at'=>now()]
        );
        // Metadata only. No arbitrary third-party PHP code is loaded or executed here.
    }
}
