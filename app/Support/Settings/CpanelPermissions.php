<?php

namespace App\Support\Settings;

use App\Support\Plugins\PluginCatalog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CpanelPermissions
{
    public const CAPABILITIES = [
        'email.read' => 'Read email accounts',
        'email.manage' => 'Manage email accounts',
        'ssl.read' => 'Read certificates',
        'ssl.manage' => 'Manage SSL certificates',
        'files.read' => 'Browse site files',
        'files.manage' => 'Modify site files',
        'backup.read' => 'List available backups',
        'backup.create' => 'Create backups',
    ];

    public function __construct(
        private readonly CoreSettings $settings,
        private readonly PluginCatalog $catalog
    ) {}

    /** @return array<string, array<string,bool>> */
    public function grants(): array
    {
        $result = [];
        $plugins = $this->catalog->all();
        foreach (DB::table('core_cpanel_grants')->get() as $row) {
            $plugin = $plugins[$row->plugin_slug] ?? null;
            if (isset(self::CAPABILITIES[$row->capability]) && $plugin !== null
                && $this->catalog->compatible($plugin)
                && in_array($row->capability, $plugin['cpanel_capabilities'], true)) {
                $result[$row->plugin_slug][$row->capability] = (bool) $row->enabled
                    && is_string($row->manifest_hash)
                    && hash_equals($row->manifest_hash, self::manifestScopeHash($plugin));
            }
        }
        return $result;
    }

    public function grant(string $slug, string $capability, bool $enabled): void
    {
        $plugin = $this->catalog->all()[$slug] ?? null;
        if (! isset(self::CAPABILITIES[$capability])
            || ! preg_match('/^[a-z][a-z0-9-]{1,63}$/D', $slug)
            || $plugin === null
            || ! $this->catalog->compatible($plugin)
            || ! in_array($capability, $plugin['cpanel_capabilities'], true)) {
            throw new InvalidArgumentException('Unknown plugin or cPanel capability.');
        }

        DB::table('core_cpanel_grants')->updateOrInsert(
            ['plugin_slug' => $slug, 'capability' => $capability],
            ['enabled' => $enabled, 'manifest_hash' => self::manifestScopeHash($plugin), 'updated_at' => now()]
        );
    }

    public function can(string $slug, string $capability): bool
    {
        if (! isset(self::CAPABILITIES[$capability])
            || ! $this->settings->cpanelStatus()['configured']
            || ! $this->settings->enabled('cpanel.integration_enabled')) {
            return false;
        }
        $installed = $this->catalog->all();
        $plugin = $installed[$slug] ?? null;
        if ($plugin === null || ! $this->catalog->compatible($plugin)
            || ! in_array($capability, $plugin['cpanel_capabilities'], true)) {
            return false;
        }
        if (! DB::table('core_plugins')->where('slug', $slug)->where('enabled', true)->exists()) {
            return false;
        }
        $grant = DB::table('core_cpanel_grants')
            ->where('plugin_slug', $slug)
            ->where('capability', $capability)
            ->where('enabled', true)->first();

        return $grant !== null && is_string($grant->manifest_hash)
            && hash_equals($grant->manifest_hash, self::manifestScopeHash($plugin));
    }

    /** @param array{id:string,cpanel_capabilities:list<string>} $plugin */
    public static function manifestScopeHash(array $plugin): string
    {
        $capabilities = $plugin['cpanel_capabilities'];
        sort($capabilities, SORT_STRING);

        return hash('sha256', $plugin['id']."\n".implode("\n", $capabilities));
    }
}
