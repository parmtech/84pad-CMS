<?php

namespace Tests\Feature;

use App\Console\Commands\InstallCore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class OwnerInstallerTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_owner_installs_only_once(): void
    {
        $this->artisan('84pad:install', [
            '--name' => 'Initial Owner',
            '--email' => 'owner@example.test',
        ])
            ->expectsQuestion('Owner password (12 or more characters)', 'safe-test-password-12345')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.test',
            'role' => 'owner',
        ]);
        $this->assertDatabaseCount('core_installations', 1);

        // A second install must fail without creating another user or owner.
        $this->artisan('84pad:install', [
            '--name' => 'Another Owner',
            '--email' => 'another@example.test',
        ])->assertExitCode(1);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('core_installations', 1);
    }

    public function test_failed_migration_exit_status_does_not_start_owner_creation(): void
    {
        // A migration may return failure without throwing a PHP exception.
        // It must stop before any Owner prompts or installation marker writes.
        Config::set('app.key', 'base64:'.base64_encode(str_repeat('K', 32)));
        $command = Mockery::mock(InstallCore::class)->makePartial();
        $command->shouldReceive('call')->once()
            ->with('84pad:doctor')->andReturn(0);
        $command->shouldReceive('call')->once()
            ->with('migrate', ['--force' => true])->andReturn(1);
        $command->shouldReceive('error')->once()
            ->with('Migrations failed. Check server-side logs.');

        $this->assertSame(1, $command->handle());
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('core_installations', 0);
    }

    public function test_short_password_does_not_create_an_owner_or_installation_marker(): void
    {
        $this->artisan('84pad:install', [
            '--name' => 'Initial Owner',
            '--email' => 'owner@example.test',
        ])
            ->expectsQuestion('Owner password (12 or more characters)', 'short')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('core_installations', 0);
    }
    public function test_insecure_production_preflight_blocks_owner_installation(): void
    {
        Config::set('app.env', 'production');
        Config::set('app.url', 'http://insecure.example.test');
        Config::set('app.debug', true);

        $this->artisan('84pad:install', [
            '--name' => 'Initial Owner',
            '--email' => 'owner@example.test',
        ])->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('core_installations', 0);
    }

}
