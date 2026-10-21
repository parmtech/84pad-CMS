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
        foreach (DB::table('core_cpanel_grants')->get() as $row) {
            if (isset(self::CAPABILITIES[$row->capability])) {
                $result[$row->plugin_slug][$row->capability] = (bool) $row->enabled;
            }
        }
        return $result;
    }

    public function grant(string $slug, string $capability, bool $enabled): void
    {
        if (! isset(self::CAPABILITIES[$capability])
            || ! preg_match('/^[a-z][a-z0-9-]{1,63}$/D', $slug)
            || ! isset($this->catalog->all()[$slug])) {
            throw new InvalidArgumentException('Unknown plugin or cPanel capability.');
        }

        DB::table('core_cpanel_grants')->updateOrInsert(
            ['plugin_slug' => $slug, 'capability' => $capability],
            ['enabled' => $enabled, 'updated_at' => now()]
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
        if (! isset($installed[$slug])) {
            return false;
        }
        if (! DB::table('core_plugins')->where('slug', $slug)->where('enabled', true)->exists()) {
            return false;
        }
        return DB::table('core_cpanel_grants')
            ->where('plugin_slug', $slug)
            ->where('capability', $capability)
            ->where('enabled', true)->exists();
    }
}
