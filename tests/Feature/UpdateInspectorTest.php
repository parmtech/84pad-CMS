<?php

namespace Tests\Feature;

use App\Support\Updates\UpdateInspector;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

final class UpdateInspectorTest extends TestCase
{
    private function signedArchive(
        bool $unsafePath = false,
        string $version = '0.2.0'
    ): array {
        if (! class_exists(ZipArchive::class)
            || ! function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('ZIP and sodium are required.');
        }

        $path = tempnam(sys_get_temp_dir(), '84pad-inspector-');
        $this->assertNotFalse($path);
        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($path, ZipArchive::OVERWRITE | ZipArchive::CREATE));
        $zip->addFromString('update.json', json_encode([
            'version' => $version,
            'requires_core' => '0.1.0',
            'requires_php' => '8.2.0',
        ], JSON_THROW_ON_ERROR));
        $zip->addFromString(
            $unsafePath ? '../escape.php' : 'app/Support/Version.php',
            '<?php return "0.2.0";'
        );
        $zip->close();

        $pair = sodium_crypto_sign_keypair();
        Config::set('84pad.release_public_keys', [
            base64_encode(sodium_crypto_sign_publickey($pair)),
        ]);
        $signature = base64_encode(sodium_crypto_sign_detached(
            "84PAD_CORE_UPDATE_V1\n".hash_file('sha256', $path, true),
            sodium_crypto_sign_secretkey($pair)
        ));

        return [$path, $signature];
    }

    public function test_valid_signed_update_inspects_version_without_installing(): void
    {
        [$path, $signature] = $this->signedArchive();
        try {
            $report = app(UpdateInspector::class)->inspect($path, $signature);
            $this->assertSame('0.2.0', $report['version']);
            $this->assertSame('0.1.0', $report['requires_core']);
            $this->assertSame(2, $report['files']);

            $this->artisan('84pad:update-inspect', [
                'path' => $path, 'signature' => $signature,
            ])->assertExitCode(0);
        } finally {
            @unlink($path);
        }
    }

    public function test_safely_rejects_a_signed_archive_with_path_traversal(): void
    {
        [$path, $signature] = $this->signedArchive(true);
        try {
            $this->expectException(RuntimeException::class);
            app(UpdateInspector::class)->inspect($path, $signature);
        } finally {
            @unlink($path);
        }
    }

    public function test_safely_rejects_old_or_same_version_update(): void
    {
        [$path, $signature] = $this->signedArchive(false, '0.1.0');
        try {
            $this->expectException(RuntimeException::class);
            app(UpdateInspector::class)->inspect($path, $signature);
        } finally {
            @unlink($path);
        }
    }

    public function test_signature_is_required_even_when_zip_looks_valid(): void
    {
        [$path, $signature] = $this->signedArchive();
        Config::set('84pad.release_public_keys', []);
        try {
            $this->expectException(RuntimeException::class);
            app(UpdateInspector::class)->inspect($path, $signature);
        } finally {
            @unlink($path);
        }
    }
}
