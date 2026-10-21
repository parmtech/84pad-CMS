<?php

namespace Tests\Feature;

use App\Support\Plugins\PluginPackageInstaller;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

final class PluginUploadLimitTest extends TestCase
{
    private function signedZip(string $payload): array
    {
        if (! class_exists(ZipArchive::class) || ! function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('ZIP and Sodium required.');
        }
        $path=tempnam(sys_get_temp_dir(),'pad-limit-test-');
        $zip=new ZipArchive();
        $this->assertSame(true,$zip->open($path,ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('plugin.json',json_encode([
            'id'=>'large-test-plugin',
            'name'=>'Large Test Plugin',
            'version'=>'0.1.0',
            'requires_core'=>'0.1.0',
        ]));
        $zip->addFromString('assets/data.bin',$payload);
        $zip->close();

        $keypair=sodium_crypto_sign_keypair();
        Config::set('84pad.plugin_public_keys',[
            base64_encode(sodium_crypto_sign_publickey($keypair)),
        ]);
        $signature=base64_encode(sodium_crypto_sign_detached(
            "84PAD_PLUGIN_ZIP_V1\n".hash_file('sha256',$path,true),
            sodium_crypto_sign_secretkey($keypair)
        ));
        return [$path,$signature];
    }

    public function test_default_plugin_zip_limit_is_128_mebibytes(): void
    {
        $this->assertSame(128 * 1024 * 1024,config('84pad.plugin_zip_max_bytes'));
        $this->assertSame(512 * 1024 * 1024,config('84pad.plugin_extracted_max_bytes'));
    }

    public function test_configured_upload_limit_is_enforced_in_signed_zip_installer(): void
    {
        [$path,$signature]=$this->signedZip(random_bytes(6000));
        try {
            $this->assertGreaterThan(1024,filesize($path));
            Config::set('84pad.plugin_zip_max_bytes',1024);
            $this->expectException(RuntimeException::class);
            app(PluginPackageInstaller::class)->install(
                new UploadedFile($path,'plugin.zip','application/zip',null,true),
                $signature
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_zip_bomb_like_ratio_is_rejected_even_when_signed(): void
    {
        [$path,$signature]=$this->signedZip(str_repeat('a',3000000));
        try {
            $this->expectException(RuntimeException::class);
            app(PluginPackageInstaller::class)->install(
                new UploadedFile($path,'plugin.zip','application/zip',null,true),
                $signature
            );
        } finally {
            @unlink($path);
        }
    }
}
