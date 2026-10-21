<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings\CpanelGateway;
use App\Support\Settings\CpanelPermissions;
use App\Support\Settings\CoreSettings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

final class CpanelGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $owner=User::query()->create([
            'name'=>'Owner','email'=>'owner@example.test',
            'password'=>'a-secure-test-password','role'=>'owner',
        ]);
        DB::table('core_installations')->insert([
            'id'=>1,'owner_user_id'=>$owner->id,'created_at'=>now(),
        ]);
        return $owner;
    }

    public function test_gateway_rejects_unsupported_operations_and_ungranted_plugin_access(): void
    {
        $gateway=app(CpanelGateway::class);
        try {
            $gateway->call('demo-widget','php.exec',['code'=>'anything']);
            $this->fail('An arbitrary API operation must never be accepted.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Unsupported cPanel operation.',$e->getMessage());
        }

        $this->expectException(AuthorizationException::class);
        $gateway->call('demo-widget','email.accounts.list');
    }

    public function test_even_granted_plugin_cannot_list_arbitrary_system_files(): void
    {
        $owner=$this->owner();
        $this->actingAs($owner)->post('/dashboard/plugins/demo-widget/state',[
            'action'=>'activate',
        ])->assertRedirect();
        $settings=app(CoreSettings::class);
        $settings->saveCpanel('cpanel.example.com','user','very-strong-cpanel-token-123456');
        $settings->setSwitch('cpanel.integration_enabled',true);
        app(CpanelPermissions::class)->grant('demo-widget','files.read',true);

        $gateway=app(CpanelGateway::class);
        $this->expectException(InvalidArgumentException::class);
        // Valid cPanel access does not imply arbitrary system file access.
        $gateway->call('demo-widget','files.directory.list',[
            'dir'=>'../../etc',
        ]);
    }

    public function test_revoking_global_access_denies_api_before_any_network_activity(): void
    {
        $this->actingAs($this->owner())->post('/dashboard/plugins/demo-widget/state',[
            'action'=>'activate',
        ])->assertRedirect();
        $settings=app(CoreSettings::class);
        $settings->saveCpanel('cpanel.example.com','user','very-strong-cpanel-token-123456');
        $settings->setSwitch('cpanel.integration_enabled',true);
        app(CpanelPermissions::class)->grant('demo-widget','email.read',true);
        $settings->setSwitch('cpanel.integration_enabled',false);

        $this->expectException(AuthorizationException::class);
        app(CpanelGateway::class)->call('demo-widget','email.accounts.list');
    }
}
