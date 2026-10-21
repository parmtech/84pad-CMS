<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Plugins\PluginCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

final class SignedPluginInstallTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('plugins/upload-test'));
        parent::tearDown();
    }

    private function owner(): User
    {
        $owner = User::query()->create([
            'name' => 'Owner', 'email'=>'owner@example.test',
            'role'=>'owner', 'password'=>'owner-super-secure-password',
        ]);
        DB::table('core_installations')->insert([
            'id'=>1,'owner_user_id'=>$owner->id,'created_at'=>now(),
        ]);
        return $owner;
    }

    private function package(bool $unsafe = false): array
    {
        if (! class_exists(ZipArchive::class) || ! function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('ZIP or sodium extension unavailable');
        }

        $temp = tempnam(sys_get_temp_dir(), 'pad-plugin');
        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($temp, ZipArchive::OVERWRITE | ZipArchive::CREATE));
        $zip->addFromString('plugin.json', json_encode([
            'id'=>'upload-test','name'=>'Uploaded Test Plugin',
            'version'=>'0.1.0','requires_core'=>'0.1.0',
        ], JSON_THROW_ON_ERROR));
        $zip->addFromString('bootstrap.php', <<<'PHP'
<?php
return static function (\App\Support\Hooks\HookBus $hooks): void {
    $hooks->listen('core.dashboard_widgets', static function (array $widgets): array {
        $widgets[] = ['title'=>'Signed upload works','description'=>'From a signed plugin'];
        return $widgets;
    });
};
PHP
        );
        if ($unsafe) {
            $zip->addFromString('../escape.php', '<?php echo "unsafe";');
        }
        $zip->close();

        $keys = sodium_crypto_sign_keypair();
        $public = sodium_crypto_sign_publickey($keys);
        $secret = sodium_crypto_sign_secretkey($keys);
        Config::set('84pad.plugin_public_keys', [base64_encode($public)]);
        $signature = base64_encode(sodium_crypto_sign_detached(
            "84PAD_PLUGIN_ZIP_V1\n".hash_file('sha256', $temp, true),
            $secret
        ));
        return [$temp, $signature];
    }

    public function test_trusted_signed_plugin_installs_disabled_then_runs_on_activation(): void
    {
        $owner = $this->owner();
        [$path, $signature] = $this->package();
        try {
            $this->actingAs($owner)
                ->post('/dashboard/plugins/install', [
                    'package' => new UploadedFile($path,'plugin.zip','application/zip',null,true),
                    'signature' => $signature,
                ])->assertRedirect('/dashboard');

            $this->assertArrayHasKey('upload-test', app(PluginCatalog::class)->all());
            $this->assertDatabaseMissing('core_plugins', ['slug'=>'upload-test','enabled'=>1]);

            $this->get('/dashboard')->assertDontSee('Signed upload works');
            $this->post('/dashboard/plugins/upload-test/state',['action'=>'activate'])
                ->assertRedirect();
            $this->get('/dashboard')->assertOk()->assertSee('Signed upload works');
        } finally {
            @unlink($path);
        }
    }

    public function test_missing_trusted_key_rejects_plugin_without_writing_it(): void
    {
        [$path, $signature] = $this->package();
        Config::set('84pad.plugin_public_keys', []);
        try {
            $this->actingAs($this->owner())->post('/dashboard/plugins/install', [
                'package' => new UploadedFile($path,'plugin.zip','application/zip',null,true),
                'signature' => $signature,
            ])->assertSessionHasErrors('plugin');
            $this->assertFalse(is_dir(base_path('plugins/upload-test')));
        } finally {
            @unlink($path);
        }
    }

    public function test_signed_path_traversal_archive_is_still_rejected(): void
    {
        [$path, $signature] = $this->package(true);
        try {
            $this->actingAs($this->owner())->post('/dashboard/plugins/install', [
                'package' => new UploadedFile($path,'plugin.zip','application/zip',null,true),
                'signature' => $signature,
            ])->assertSessionHasErrors('plugin');
            $this->assertFalse(is_dir(base_path('plugins/upload-test')));
        } finally {
            @unlink($path);
        }
    }
}
