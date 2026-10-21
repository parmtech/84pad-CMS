<?php
namespace Tests\Feature;

use App\Models\User;
use App\Support\Hooks\HookBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PluginStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_mark_demo_plugin_enabled(): void
    {
        $owner=User::query()->create(['name'=>'Owner','email'=>'owner@example.test','password'=>'long-test-password','role'=>'owner']);
        DB::table('core_installations')->insert(['id'=>1,'owner_user_id'=>$owner->id,'created_at'=>now()]);
        $this->actingAs($owner)->post('/dashboard/plugins/demo-widget/state',['action'=>'activate'])->assertRedirect();
        $this->assertDatabaseHas('core_plugins',['slug'=>'demo-widget','enabled'=>1]);
    }

    public function test_owner_can_disable_a_previously_enabled_plugin(): void
    {
        $owner=User::query()->create([
            'name'=>'Owner','email'=>'owner@example.test',
            'password'=>'long-test-password','role'=>'owner',
        ]);
        DB::table('core_installations')->insert([
            'id'=>1,'owner_user_id'=>$owner->id,'created_at'=>now(),
        ]);
        $this->actingAs($owner)
            ->post('/dashboard/plugins/demo-widget/state',['action'=>'activate'])
            ->assertRedirect();
        $this->assertDatabaseHas('core_plugins',['slug'=>'demo-widget','enabled'=>1]);

        $this->post('/dashboard/plugins/demo-widget/state',['action'=>'deactivate'])
            ->assertRedirect();
        $this->assertDatabaseHas('core_plugins',['slug'=>'demo-widget','enabled'=>0]);
    }

    public function test_unknown_plugin_cannot_be_activated(): void
    {
        $owner=User::query()->create([
            'name'=>'Owner','email'=>'owner@example.test',
            'password'=>'long-test-password','role'=>'owner',
        ]);
        DB::table('core_installations')->insert([
            'id'=>1,'owner_user_id'=>$owner->id,'created_at'=>now(),
        ]);
        $this->actingAs($owner)
            ->post('/dashboard/plugins/not-installed/state',['action'=>'activate'])
            ->assertRedirect()
            ->assertSessionHasErrors('plugin');
        $this->assertDatabaseMissing('core_plugins',['slug'=>'not-installed']);
    }

    public function test_active_demo_plugin_renders_widget_and_deactivation_hides_it(): void
    {
        $owner=User::query()->create([
            'name'=>'Owner','email'=>'owner@example.test',
            'password'=>'long-test-password','role'=>'owner',
        ]);
        DB::table('core_installations')->insert([
            'id'=>1,'owner_user_id'=>$owner->id,'created_at'=>now(),
        ]);

        $this->actingAs($owner)->get('/dashboard')
            ->assertOk()->assertDontSee('Demo plugin running');
        $this->post('/dashboard/plugins/demo-widget/state', ['action'=>'activate'])
            ->assertRedirect();
        $this->get('/dashboard')->assertOk()->assertSee('Demo plugin running');

        $this->post('/dashboard/plugins/demo-widget/state', ['action'=>'deactivate'])
            ->assertRedirect();
        $this->get('/dashboard')
            ->assertOk()->assertDontSee('Demo plugin running');
    }

    public function test_failing_dashboard_hook_does_not_lock_out_owner(): void
    {
        $owner=User::query()->create([
            'name'=>'Owner','email'=>'owner@example.test',
            'password'=>'long-test-password','role'=>'owner',
        ]);
        DB::table('core_installations')->insert([
            'id'=>1,'owner_user_id'=>$owner->id,'created_at'=>now(),
        ]);
        app(HookBus::class)->listen('core.dashboard_widgets', static function (): never {
            throw new \RuntimeException('A plugin failed unexpectedly');
        });

        $this->actingAs($owner)->get('/dashboard')->assertOk()
            ->assertDontSee('A plugin failed unexpectedly');
    }

    public function test_viewer_cannot_change_plugin_state(): void
    {
        $viewer=User::query()->create(['name'=>'Viewer','email'=>'viewer@example.test','password'=>'long-test-password','role'=>'viewer']);
        $this->actingAs($viewer)->post('/dashboard/plugins/demo-widget/state',['action'=>'activate'])->assertForbidden();
        $this->assertDatabaseMissing('core_plugins',['slug'=>'demo-widget']);
    }
}
