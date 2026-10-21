<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $user = User::query()->create([
            'name' => 'Owner', 'email' => 'owner@example.test',
            'role' => 'owner', 'password' => 'original-strong-password',
        ]);
        DB::table('core_installations')->insert([
            'id' => 1, 'owner_user_id' => $user->id, 'created_at' => now(),
        ]);
        return $user;
    }

    public function test_guest_cannot_access_security_screen(): void
    {
        $this->get('/account/security')->assertRedirect('/login');
    }

    public function test_owner_can_rotate_password_with_current_password_and_confirmation(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->get('/account/security')->assertOk();
        $this->post('/account/security', [
            'current_password' => 'original-strong-password',
            'password' => 'another-long-secure-password',
            'password_confirmation' => 'another-long-secure-password',
        ])->assertRedirect('/dashboard');
        $owner->refresh();

        $this->assertTrue(Hash::check('another-long-secure-password', $owner->password));
        $this->assertFalse(Hash::check('original-strong-password', $owner->password));
    }

    public function test_incorrect_current_password_never_changes_stored_password(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post('/account/security', [
            'current_password' => 'incorrect-current-password',
            'password' => 'another-long-secure-password',
            'password_confirmation' => 'another-long-secure-password',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('original-strong-password', $owner->fresh()->password));
    }

    public function test_viewer_cannot_rotate_owner_password(): void
    {
        $viewer = User::query()->create([
            'name' => 'Viewer', 'email' => 'viewer@example.test',
            'role' => 'viewer', 'password' => 'viewer-strong-password',
        ]);
        $this->actingAs($viewer)->get('/account/security')->assertForbidden();
        $this->post('/account/security', [
            'current_password' => 'viewer-strong-password',
            'password' => 'viewer-new-strong-password',
            'password_confirmation' => 'viewer-new-strong-password',
        ])->assertForbidden();
        $this->assertTrue(Hash::check('viewer-strong-password', $viewer->fresh()->password));
    }
}
