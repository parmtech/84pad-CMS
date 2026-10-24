<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Read-only shared-host login diagnostics. Never prints secret values,
 * usernames, email addresses, hashes, session cookies or database DSNs.
 */
final class DiagnoseLogin extends Command
{
    protected $signature = '84pad:auth-doctor';
    protected $description = 'Diagnose Core login, Owner, HTTPS and session configuration without disclosing secrets';

    public function handle(): int
    {
        $url = (string) config('app.url', '');
        $production = config('app.env') === 'production';
        $session = (string) config('session.driver', '');
        $storagePath = (string) config('session.files', '');
        $key = (string) config('app.key', '');
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));
        $domain = strtolower(ltrim((string) config('session.domain', ''), '.'));
        $sameDomain = $domain === '' || ($host !== '' && (
            $host === $domain || str_ends_with($host, '.'.$domain)
        ));
        $keyBody = str_starts_with($key, 'base64:')
            ? base64_decode(substr($key, 7), true) : $key;
        $validKey = is_string($keyBody) && strlen($keyBody) >= 32;

        $ready = [
            'APP_KEY length and format' => $validKey,
            'HTTPS APP_URL in production' => ! $production || str_starts_with($url, 'https://'),
            'APP_DEBUG disabled in production' => ! $production || ! (bool) config('app.debug'),
            'Session driver supported' => in_array($session, ['file', 'database', 'redis'], true),
            'Session cookie domain matches APP_URL' => $sameDomain,
            'File sessions directory writable' => $session !== 'file'
                || (is_dir($storagePath) && is_writable($storagePath)),
        ];
        try {
            $ready['Database reachable'] = DB::connection()->getPdo() !== null;
            $ready['Installed Owner account present'] = DB::table('core_installations')
                ->join('users', 'users.id', '=', 'core_installations.owner_user_id')
                ->where('core_installations.id', 1)
                ->where('users.role', 'owner')
                ->exists();
            if ($session === 'database') {
                $ready['Session database table available'] = \Illuminate\Support\Facades\Schema::hasTable(
                    (string) config('session.table', 'sessions')
                );
            }
        } catch (Throwable $e) {
            $ready['Database reachable'] = false;
            $ready['Installed Owner account present'] = false;
            if ($session === 'database') $ready['Session database table available'] = false;
        }

        $this->table(['Login requirement', 'Status'],
            array_map(static fn ($k, $value): array => [$k, $value ? 'PASS' : 'FAIL'],
                array_keys($ready), array_values($ready)));

        if ($production && config('session.secure') !== true) {
            $this->warn('Consider SESSION_SECURE_COOKIE=true when the public site uses HTTPS.');
        }
        $this->line('If login loops: verify APP_URL, SESSION_DOMAIN, cookie settings, session directory, HTTPS proxy and a stable APP_KEY.');
        $this->line('Do not share .env, cookies, passwords or API tokens when reporting failures.');

        if (in_array(false, $ready, true)) {
            $this->error('Login preflight failed; correct the failing server configuration.');
            return self::FAILURE;
        }
        $this->info('Basic login prerequisites passed. This is not a live browser/session acceptance test.');
        return self::SUCCESS;
    }
}
