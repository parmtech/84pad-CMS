<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class FirstRunWebInstallerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('84pad.setup_token', str_repeat('Q', 40));
    }

    public function test_migration_failure_exit_code_never_creates_first_owner(): void
    {
        Artisan::shouldReceive('call')
            ->once()->with('84pad:doctor')->andReturn(0);
        Artisan::shouldReceive('call')
            ->once()->with('migrate', [
                '--force' => true, '--no-interaction' => true,
            ])->andReturn(1);

        $this->post('/setup', [
            'setup_token' => str_repeat('Q', 40),
            'name' => 'First Owner',
            'email' => 'owner@example.test',
            'password' => 'a-very-long-secret-passphrase',
            'password_confirmation' => 'a-very-long-secret-passphrase',
        ])->assertRedirect()->assertSessionHasErrors('setup');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('core_installations', 0);
    }

    public function test_locked_installer_does_not_run_migrations_or_create_an_owner(): void
    {
        $path = storage_path('framework/84pad-first-run.lock');
        $file = fopen($path, 'c');
        $this->assertNotFalse($file);
        $this->assertTrue(flock($file, LOCK_EX | LOCK_NB));

        try {
            $this->post('/setup', [
                'setup_token' => str_repeat('Q', 40),
                'name' => 'First Owner',
                'email' => 'owner@example.test',
                'password' => 'a-very-long-secret-passphrase',
                'password_confirmation' => 'a-very-long-secret-passphrase',
            ])->assertRedirect()->assertSessionHasErrors('setup');

            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('core_installations', 0);
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    public function test_setup_is_disabled_without_a_configured_secret(): void
    {
        Config::set('84pad.setup_token', '');
        $this->get('/setup')->assertNotFound();
    }

    public function test_owner_setup_requires_correct_secret_and_never_creates_an_owner_on_failure(): void
    {
        $this->get('/setup')->assertOk();

        $this->post('/setup', [
            'setup_token' => 'incorrect',
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'a-very-long-secret-passphrase',
            'password_confirmation' => 'a-very-long-secret-passphrase',
        ])->assertForbidden();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('core_installations', 0);
    }

    public function test_web_setup_creates_exactly_one_owner_then_disables_installer(): void
    {
        $this->post('/setup', [
            'setup_token' => str_repeat('Q', 40),
            'name' => 'First Owner',
            'email' => 'owner@example.test',
            'password' => 'a-very-long-secret-passphrase',
            'password_confirmation' => 'a-very-long-secret-passphrase',
        ])->assertRedirect('/login');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['email'=>'owner@example.test','role'=>'owner']);
        $owner = User::query()->firstOrFail();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('a-very-long-secret-passphrase', $owner->password));
        $this->assertDatabaseHas('core_installations', ['id'=>1,'owner_user_id'=>$owner->id]);

        $this->get('/setup')->assertNotFound();
        $this->post('/setup', ['setup_token' => str_repeat('Q', 40)])->assertNotFound();
    }

    public function test_existing_users_block_first_run_without_destroying_data(): void
    {
        User::query()->create([
            'name'=>'Existing User','email'=>'existing@example.test',
            'password'=>'existing-long-password','role'=>'viewer',
        ]);

        $this->get('/setup')->assertNotFound();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('core_installations', 0);
    }

    public function test_production_http_or_debug_mode_rejects_setup(): void
    {
        Config::set('app.env', 'production');
        Config::set('app.debug', true);
        $this->get('/setup')->assertNotFound();
        Config::set('app.debug', false);
        $this->get('/setup')->assertNotFound();
    }
}
