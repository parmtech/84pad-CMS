<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Hooks\HookBus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HookBus::class, fn () => new HookBus());
    }

    public function boot(): void
    {
        Gate::define('core.view-dashboard', fn (User $user): bool =>
            in_array($user->role, ['owner', 'admin'], true)
        );
        Gate::define('core.manage-plugins', fn (User $user): bool =>
            $user->role === 'owner'
        );
        Gate::define('core.manage-security', fn (User $user): bool =>
            $user->role === 'owner'
        );
    }
}
