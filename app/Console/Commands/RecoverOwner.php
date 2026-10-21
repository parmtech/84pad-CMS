<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class RecoverOwner extends Command
{
    protected $signature = '84pad:owner-recover {--password-stdin : Read the new password from stdin}';
    protected $description = 'Recover the installed Owner password from trusted server CLI access';

    public function handle(): int
    {
        try {
            if (! Schema::hasTable('core_installations')
                || ! Schema::hasTable('users')) {
                $this->error('84PAD is not installed.');
                return self::FAILURE;
            }
            $marker = DB::table('core_installations')->where('id', 1)->first();
            if (! $marker || ! $marker->owner_user_id) {
                $this->error('A valid installed Owner could not be found.');
                return self::FAILURE;
            }
            $owner = User::query()
                ->whereKey($marker->owner_user_id)
                ->where('role', 'owner')
                ->first();
            if (! $owner) {
                $this->error('Owner not found.');
                return self::FAILURE;
            }
        } catch (Throwable $e) {
            $this->error('Owner recovery preflight failed.');
            return self::FAILURE;
        }

        $password = $this->option('password-stdin')
            ? (defined('STDIN') ? fgets(STDIN, 2049) : false)
            : $this->secret('New owner password (at least 12 characters)');

        if ($this->option('password-stdin') && is_string($password)) {
            $password = rtrim($password, "\r\n");
        }
        if (! is_string($password) || strlen($password) < 12 || strlen($password) > 1024) {
            $this->error('Password must be 12-1024 characters.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($owner, $password): void {
                $owner->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // DB sessions can be revoked immediately; file sessions are
                // rejected by the auth.session password-hash middleware.
                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $owner->getKey())->delete();
                }
            }, 3);
        } catch (Throwable $e) {
            $this->error('Owner recovery failed; check protected server logs.');
            return self::FAILURE;
        } finally {
            unset($password);
        }

        $this->info('Owner credentials rotated. Sign in with the new password.');
        return self::SUCCESS;
    }
}
