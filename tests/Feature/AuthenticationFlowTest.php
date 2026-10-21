<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_uninstalled_login_is_unavailable(): void
    {
        $this->get('/login')->assertStatus(503);
    }

    public function test_guest_cannot_see_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_owner_can_sign_in_then_logout(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'testing-password-secure',
            'role' => 'owner',
        ]);
        DB::table('core_installations')->insert(['id' => 1, 'owner_user_id' => $owner->id, 'created_at' => now()]);

        $this->post('/login', ['email' => 'owner@example.test', 'password' => 'testing-password-secure'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($owner);
        $this->get('/dashboard')->assertOk();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_non_owner_cannot_see_control_studio(): void
    {
        $viewer = User::query()->create([
            'name'=>'Viewer', 'email'=>'viewer@example.test',
            'password'=>'testing-password-secure', 'role'=>'viewer',
        ]);
        $this->actingAs($viewer)->get('/dashboard')->assertForbidden();
    }
}
