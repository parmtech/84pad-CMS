<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class RecoverOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovery_fails_when_core_is_not_installed(): void
    {
        $this->artisan('84pad:owner-recover')->assertExitCode(1);
    }

    public function test_local_cli_recovery_rotates_only_the_installed_owner_password(): void
    {
        $owner = User::query()->create([
            'name'=>'Owner','email'=>'owner@example.test',
            'role'=>'owner','password'=>'old-long-password',
        ]);
        $viewer = User::query()->create([
            'name'=>'Viewer','email'=>'viewer@example.test',
            'role'=>'viewer','password'=>'viewer-original-password',
        ]);
        DB::table('core_installations')->insert([
            'id'=>1,'owner_user_id'=>$owner->id,'created_at'=>now(),
        ]);

        $this->artisan('84pad:owner-recover')
            ->expectsQuestion('New owner password (at least 12 characters)', 'new-secure-password-123')
            ->assertExitCode(0);

        $this->assertTrue(Hash::check('new-secure-password-123', $owner->fresh()->password));
        $this->assertFalse(Hash::check('old-long-password', $owner->fresh()->password));
        $this->assertTrue(Hash::check('viewer-original-password', $viewer->fresh()->password));
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('core_installations', 1);
    }

    public function test_weak_recovery_password_does_not_change_owner(): void
    {
        $owner = User::query()->create([
            'name'=>'Owner','email'=>'owner@example.test',
            'role'=>'owner','password'=>'old-long-password',
        ]);
        DB::table('core_installations')->insert([
            'id'=>1,'owner_user_id'=>$owner->id,'created_at'=>now(),
        ]);

        $this->artisan('84pad:owner-recover')
            ->expectsQuestion('New owner password (at least 12 characters)', 'short')
            ->assertExitCode(1);

        $this->assertTrue(Hash::check('old-long-password', $owner->fresh()->password));
    }
}
