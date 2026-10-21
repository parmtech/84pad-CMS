<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class DoctorCore extends Command
{
    protected $signature = '84pad:doctor';
    protected $description = 'Check the host before installing 84PAD';

    public function handle(): int
    {
        $requirements = [
            'PHP >= 8.2' => PHP_VERSION_ID >= 80200,
            'mbstring' => extension_loaded('mbstring'),
            'PDO' => extension_loaded('pdo'),
            'OpenSSL' => extension_loaded('openssl'),
            'Tokenizer' => extension_loaded('tokenizer'),
            'XML' => extension_loaded('xml'),
            'Storage writable' => is_writable(storage_path()),
            'Bootstrap cache writable' => is_writable(base_path('bootstrap/cache')),
            'APP_KEY configured' => (bool) config('app.key'),
            'Production URL HTTPS' => config('app.env') !== 'production'
                || str_starts_with((string) config('app.url'), 'https://'),
            'Production debug disabled' => config('app.env') !== 'production'
                || ! (bool) config('app.debug'),
        ];

        $driver = (string) config('database.default');
        $driverReady = match ($driver) {
            'sqlite' => extension_loaded('pdo_sqlite'),
            'mysql' => extension_loaded('pdo_mysql'),
            default => false,
        };
        $requirements['Database driver: '.$driver] = $driverReady;

        try {
            $requirements['Database reachable'] = $driverReady
                && DB::connection()->getPdo() !== null;
        } catch (Throwable $e) {
            $requirements['Database reachable'] = false;
        }

        $rows = [];
        foreach ($requirements as $name => $passed) {
            $rows[] = [$name, $passed ? 'PASS' : 'FAIL'];
        }
        $this->table(['Check', 'Status'], $rows);

        if (in_array(false, $requirements, true)) {
            $this->error('Preflight failed; fix failed requirements before installation.');
            return self::FAILURE;
        }

        $this->info('Host preflight passed; this is not a security audit.');
        return self::SUCCESS;
    }
}
