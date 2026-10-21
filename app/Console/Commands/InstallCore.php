<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InstallCore extends Command
{
    protected $signature = '84pad:install {--name= : First owner name} {--email= : First owner email}';
    protected $description = 'Create the first 84PAD owner once (local CLI only)';

    public function handle(): int
    {
        if (! config('app.key')) {
            $this->error('Set APP_KEY with php artisan key:generate before installing.');
            return self::FAILURE;
        }

        // Never mutate the database until all installation prerequisites pass.
        if ($this->call('84pad:doctor') !== self::SUCCESS) {
            $this->error('Installation preflight failed. No migrations were attempted.');
            return self::FAILURE;
        }

        try {
            $this->call('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            $this->error('Migrations failed. Check server-side logs.');
            return self::FAILURE;
        }

        if (! Schema::hasTable('users') || ! Schema::hasTable('core_installations')) {
            $this->error('Core database schema is incomplete.');
            return self::FAILURE;
        }

        if (DB::table('core_installations')->exists() || User::query()->where('role', 'owner')->exists()) {
            $this->warn('Already installed or owner exists; no changes made.');
            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Owner display name')));
        $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('Owner email'))));
        $password = $this->secret('Owner password (12 or more characters)');

        if (mb_strlen($name) < 2 || mb_strlen($name) > 120
            || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255
            || ! is_string($password) || strlen($password) < 12 || strlen($password) > 1024) {
            $this->error('Invalid owner details; password must be 12-1024 characters.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($name, $email, $password): void {
                // Atomic sentinel protects against two simultaneous installers.
                $inserted = DB::table('core_installations')->insertOrIgnore([
                    'id' => 1, 'owner_user_id' => null, 'created_at' => now(),
                ]);
                if ($inserted !== 1 || User::query()->where('role', 'owner')->exists()) {
                    throw new \RuntimeException('Already installed');
                }

                $owner = User::query()->create([
                    'name' => $name, 'email' => $email, 'role' => 'owner',
                    'password' => Hash::make($password),
                ]);
                DB::table('core_installations')->where('id', 1)->update(['owner_user_id' => $owner->id]);
            }, 3);
        } catch (Throwable $e) {
            $this->error('Owner setup failed. No new owner was installed.');
            return self::FAILURE;
        } finally {
            unset($password);
        }

        $this->info('84PAD installed. Use /login to sign in.');
        return self::SUCCESS;
    }
}
