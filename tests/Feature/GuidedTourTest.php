<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GuidedTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_owner_gets_first_login_guided_tour_and_can_replay_it(): void
    {
        $owner=User::query()->create([
            'name'=>'Owner','email'=>'owner@example.test',
            'password'=>'very-long-owner-password','role'=>'owner',
        ]);
        DB::table('core_installations')->insert([
            'id'=>1,'owner_user_id'=>$owner->id,'created_at'=>now(),
        ]);
        $this->actingAs($owner)->get('/dashboard')
            ->assertOk()
            ->assertSee('data-tour-auto="1"',false)
            ->assertSee('js/84pad-tour.js');

        $this->post('/dashboard/tour/complete')
            ->assertOk()->assertJson(['completed'=>true]);

        $this->assertDatabaseHas('core_user_preferences',[
            'user_id'=>$owner->id,'tour_completed'=>1,
        ]);
        $this->get('/dashboard')->assertSee('data-tour-auto="0"',false);
        $this->get('/dashboard?tour=1')->assertSee('data-tour-auto="1"',false);
        $this->get('/dashboard/settings?tour=1')->assertOk()
            ->assertSee('data-tour-page="settings"',false)
            ->assertSee('data-tour-auto="1"',false);
    }

    public function test_guest_and_nonowner_cannot_mark_tour_complete(): void
    {
        $this->post('/dashboard/tour/complete')->assertRedirect('/login');

        $viewer=User::query()->create([
            'name'=>'Viewer','email'=>'viewer@example.test',
            'role'=>'viewer','password'=>'very-long-viewer-password',
        ]);
        $this->actingAs($viewer)->post('/dashboard/tour/complete')->assertForbidden();
        $this->assertDatabaseCount('core_user_preferences',0);
    }

    public function test_guided_tour_assets_exist_and_have_all_controls(): void
    {
        $script=file_get_contents(public_path('js/84pad-tour.js'));
        $this->assertIsString($script);
        foreach (['Skip','Back','Next','Finish','Escape','settings-cpanel','settings-permissions'] as $item) {
            $this->assertStringContainsString($item,$script);
        }
    }
}
