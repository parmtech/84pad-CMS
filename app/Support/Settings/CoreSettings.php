<?php

namespace App\Support\Settings;

use App\Models\Page;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class CoreSettings
{
    public const SWITCHES = [
        'cpanel.integration_enabled' => false,
        'plugins.runtime_enabled' => true,
        'plugins.signed_uploads_enabled' => true,
        'ui.animations_enabled' => true,
    ];

    private function read(string $key): ?string
    {
        $stored = DB::table('core_settings')->where('key', $key)->value('value');
        return is_string($stored) ? $stored : null;
    }

    private function put(string $key, string $value): void
    {
        DB::table('core_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
    }

    public function enabled(string $key): bool
    {
        if (! array_key_exists($key, self::SWITCHES)) {
            throw new \InvalidArgumentException('Unknown Core switch.');
        }
        return ($this->read($key) ?? (self::SWITCHES[$key] ? '1' : '0')) === '1';
    }

    public function setSwitch(string $key, bool $enabled): void
    {
        if (! array_key_exists($key, self::SWITCHES)) {
            throw new \InvalidArgumentException('Unknown Core switch.');
        }
        $this->put($key, $enabled ? '1' : '0');
    }

    public function siteSettings(): array
    {
        return [
            'title' => $this->read('site.title') ?? (string) config('app.name', '84PAD'),
            'canonical_url' => rtrim($this->read('site.canonical_url') ?? (string) config('app.url', 'http://localhost'), '/'),
            'locale' => $this->read('site.locale') ?? (string) config('app.locale', 'en'),
            'timezone' => $this->read('site.timezone') ?? (string) config('app.timezone', 'UTC'),
            'homepage_page_id' => $this->read('site.homepage_page_id'),
        ];
    }

    public function homepagePage(): ?Page
    {
        try {
            if (! Schema::hasTable('core_settings') || ! Schema::hasTable('pages')) {
                return null;
            }

            $id = $this->read('site.homepage_page_id');
            if (! is_string($id) || ! ctype_digit($id)) {
                return null;
            }

            return Page::query()->whereKey((int) $id)->where('status', 'published')->first();
        } catch (Throwable) {
            // The public welcome page remains available before installation
            // and while the site database is unavailable.
            return null;
        }
    }

    public function saveSiteSettings(
        string $title,
        string $canonicalUrl,
        string $locale,
        string $timezone,
        ?int $homepagePageId = null
    ): void {
        DB::transaction(function () use ($title, $canonicalUrl, $locale, $timezone, $homepagePageId): void {
            $this->put('site.title', $title);
            $this->put('site.canonical_url', rtrim($canonicalUrl, '/'));
            $this->put('site.locale', $locale);
            $this->put('site.timezone', $timezone);
            if ($homepagePageId === null) {
                DB::table('core_settings')->where('key', 'site.homepage_page_id')->delete();
            } else {
                $this->put('site.homepage_page_id', (string) $homepagePageId);
            }
        });
    }

    /** A status view never returns the encrypted or plaintext API token. */
    public function cpanelStatus(): array
    {
        return [
            'host' => $this->read('cpanel.host') ?? '',
            'username' => $this->read('cpanel.username') ?? '',
            'configured' => ($this->read('cpanel.api_token') ?? '') !== '',
            'enabled' => $this->enabled('cpanel.integration_enabled'),
        ];
    }

    public function saveCpanel(string $host, string $username, ?string $token): void
    {
        // No URL, username@host, custom port, path, IP literal or local host.
        $host = strtolower(trim($host));
        if (strlen($host) > 253 || strlen($host) < 4
            || ! preg_match('/^(?=.{4,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/D', $host)
            || preg_match('/(?:^|\.)(?:localhost|local|internal|test|invalid)$/D', $host)
            || filter_var($host, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Use a valid public cPanel server hostname only.');
        }
        if (! preg_match('/^[a-zA-Z0-9_-]{1,64}$/D', $username)) {
            throw new \InvalidArgumentException('Invalid cPanel username.');
        }
        if ($token !== null && (strlen($token) < 16 || strlen($token) > 4096
            || preg_match('/[\x00-\x20\x7f]/', $token))) {
            throw new \InvalidArgumentException('Invalid cPanel API token.');
        }

        DB::transaction(function () use ($host, $username, $token): void {
            $this->put('cpanel.host', $host);
            $this->put('cpanel.username', $username);
            if ($token !== null) {
                $this->put('cpanel.api_token', Crypt::encryptString($token));
            }
        });
    }

    /** Only call from Core's trusted gateway; NEVER pass to Blade/plugins. */
    public function cpanelCredentialForGateway(): ?array
    {
        $state = $this->cpanelStatus();
        if (! $state['configured'] || ! $state['enabled']) {
            return null;
        }

        $encrypted = $this->read('cpanel.api_token');
        return [
            'host' => $state['host'],
            'username' => $state['username'],
            'token' => Crypt::decryptString($encrypted),
        ];
    }

    public function disconnectCpanel(): void
    {
        DB::transaction(function (): void {
            DB::table('core_settings')->whereIn('key', [
                'cpanel.host', 'cpanel.username', 'cpanel.api_token',
            ])->delete();
            $this->setSwitch('cpanel.integration_enabled', false);
            DB::table('core_cpanel_grants')->update(['enabled' => false, 'updated_at' => now()]);
        });
    }
}

