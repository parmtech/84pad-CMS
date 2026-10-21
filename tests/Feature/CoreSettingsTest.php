<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings\CpanelPermissions;
use App\Support\Settings\CoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $owner = User::query()->create([
            'name'=>'Owner', 'email'=>'owner@example.test',
            'password'=>'a-long-test-password','role'=>'owner',
        ]);
        DB::table('core_installations')->insert([
            'id'=>1, 'owner_user_id'=>$owner->id, 'created_at'=>now(),
        ]);
        return $owner;
    }

    public function test_site_is_functional_without_cpanel_api_token(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->get('/dashboard')->assertOk();
        $this->get('/dashboard/settings')->assertOk()
            ->assertSee('Optional · Not configured');
        $this->assertFalse(app(CoreSettings::class)->enabled('cpanel.integration_enabled'));
        $this->assertFalse(app(CpanelPermissions::class)->can('demo-widget', 'ssl.read'));

        $this->post('/dashboard/settings/switch', [
            'setting'=>'cpanel.integration_enabled','enabled'=>1,
        ])->assertSessionHasErrors('setting');
        $this->assertFalse(app(CoreSettings::class)->enabled('cpanel.integration_enabled'));
    }

    public function test_owner_saves_token_encrypted_without_reflecting_plaintext_in_ui(): void
    {
        $owner=$this->owner();
        $token='super-private-cpanel-api-token-strong-98765';
        $this->actingAs($owner)->post('/dashboard/settings/cpanel', [
            'host'=>'premium214.web-hosting.com',
            'username'=>'hosting_user',
            'api_token'=>$token,
        ])->assertRedirect('/dashboard/settings');

        $stored=DB::table('core_settings')->where('key','cpanel.api_token')->value('value');
        $this->assertNotSame($token,$stored);
        $this->assertSame($token,Crypt::decryptString($stored));
        $this->get('/dashboard/settings')->assertOk()
            ->assertDontSee($token)
            ->assertSee('Token stored securely');

        $this->post('/dashboard/settings/cpanel', [
            'host'=>'premium214.web-hosting.com',
            'username'=>'hosting_user',
            'api_token'=>'',
        ])->assertRedirect('/dashboard/settings');
        $this->assertSame($stored,DB::table('core_settings')->where('key','cpanel.api_token')->value('value'));
    }

    public function test_invalid_api_token_is_never_flashed_into_session(): void
    {
        $this->actingAs($this->owner())
            ->post('/dashboard/settings/cpanel', [
                'host'=>'premium214.web-hosting.com',
                'username'=>'hosting_user',
                'api_token'=>'SECRET-FLASH-MUST-NEVER-BE-SAVED-'.str_repeat('x',4100),
            ])->assertSessionHasErrors('api_token');
        $this->assertArrayNotHasKey('api_token',session()->getOldInput());
        $this->assertDatabaseMissing('core_settings',['key'=>'cpanel.api_token']);
    }

    public function test_permissions_require_token_global_switch_plugin_activation_and_grant(): void
    {
        $owner=$this->owner();
        $this->actingAs($owner);
        $settings=app(CoreSettings::class);
        $permissions=app(CpanelPermissions::class);

        $settings->saveCpanel('cpanel.example.com','cpuser','very-secure-api-token-123456789');
        $this->assertFalse($permissions->can('demo-widget','email.read'));
        $this->post('/dashboard/settings/switch', [
            'setting'=>'cpanel.integration_enabled','enabled'=>1,
        ])->assertRedirect('/dashboard/settings');

        $this->post('/dashboard/settings/permission', [
            'plugin_slug'=>'demo-widget','capability'=>'email.read','enabled'=>1,
        ])->assertRedirect('/dashboard/settings');
        $this->assertFalse($permissions->can('demo-widget','email.read'));

        $this->post('/dashboard/plugins/demo-widget/state',[
            'action'=>'activate',
        ])->assertRedirect();
        $this->assertTrue($permissions->can('demo-widget','email.read'));
        $this->assertFalse($permissions->can('demo-widget','ssl.manage'));

        $this->post('/dashboard/settings/disconnect',[
            'confirm'=>'DISCONNECT',
        ])->assertRedirect('/dashboard/settings');
        $this->assertFalse($permissions->can('demo-widget','email.read'));
        $this->assertDatabaseMissing('core_settings',['key'=>'cpanel.api_token']);
        $this->get('/dashboard')->assertOk();
    }

    public function test_non_owner_cannot_view_or_change_settings(): void
    {
        $viewer=User::query()->create([
            'name'=>'Viewer','email'=>'viewer@example.test',
            'password'=>'secure-viewer-password','role'=>'viewer',
        ]);
        $this->actingAs($viewer)->get('/dashboard/settings')->assertForbidden();
        $this->post('/dashboard/settings/cpanel',[
            'host'=>'cpanel.example.com','username'=>'user','api_token'=>'reallylongapikey',
        ])->assertForbidden();
    }

    public function test_unknown_settings_or_capabilities_are_rejected(): void
    {
        $this->actingAs($this->owner())
            ->post('/dashboard/settings/switch', ['setting'=>'unknown.toggle','enabled'=>1])
            ->assertSessionHasErrors('setting');
        $this->post('/dashboard/settings/permission', [
            'plugin_slug'=>'demo-widget', 'capability'=>'everything.root','enabled'=>1,
        ])->assertSessionHasErrors('permissions');
    }
}
