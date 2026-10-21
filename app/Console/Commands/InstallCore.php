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
    protected $signature = '84pad:install {--name= : The first owner name} {--email= : The first owner email}';
    protected $description = 'Securely install 84PAD with a single owner (CLI only)';

    public function handle(): int
    {
        if (! config('app.key')) {
            $this->error('APP_KEY is missing. Run php artisan key:generate first.');
            return self::FAILURE;
        }

        // Migrations are idempotent; the owner and installation marker are created atomically.
        try {
            $this->call('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            $this->error('Database migrations failed. Check your server log.');
            return self::FAILURE;
        }

        if (! Schema::hasTable('users') || ! Schema::hasTable('core_installations')) {
            $this->error('Core database schema is incomplete.');
            return self::FAILURE;
        }

        if (DB::table('core_installations')->exists() || User::query()->where('role', 'owner')->exists()) {
            $this->warn('Installation is already completed or an owner already exists. No changes made.');
            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Owner display name')));
        $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('Owner email'))));
        $password = $this->secret('Create owner password (not saved to terminal history)');

        if (mb_strlen($name) < 2 || mb_strlen($name) > 120
            || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255
            || ! is_string($password) || strlen($password) < 12 || strlen($password) > 1024) {
            $this->error('Invalid owner name/email or password. Password must be 12-1024 characters.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($name, $email, $password): void {
                // Lock a single known row; competing installers cannot both become owners.
                $won = DB::table('core_installations')->insertOrIgnore([
                    'id' => 1,
                    // owner_user_id must exist: create owner in same transaction, then insert marker.
                    // User row is locked by unique email and marker prevents duplicate install.
                    'owner_user_id' => 0,
                    'created_at' => now(),
                ]);
                if ($won !== 1) {
                    throw new \RuntimeException('Core already installed');
                }
                if (User::query()->where('role', 'owner')->exists()) {
                    throw new \RuntimeException('Owner already exists');
                }
                $owner = User::query()->create([
                    'name' => $name, 'email' => $email,
                    'password' => Hash::make($password), 'role' => 'owner',
                ]);
                DB::table('core_installations')->where('id', 1)->update(['owner_user_id' => $owner->id]);
            }, 3);
        } catch (Throwable $e) {
            // Error message kept generic to avoid leaking database or credentials.
            $this->error('Owner setup was not completed. No additional owner was installed.');
            return self::FAILURE;
        } finally {
            unset($password);
        }

        $this->info('84PAD owner installed. Use /login to sign in.');
        return self::SUCCESS;
    }
}
