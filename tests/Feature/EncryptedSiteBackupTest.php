<?php

namespace Tests\Feature;

use App\Support\Backup\EncryptedSiteBackup;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class EncryptedSiteBackupTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $temporaryDirectories = [];
    private ?string $previousKey = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousKey = config('84pad.backup_key');
        config()->set('84pad.backup_key', base64_encode(random_bytes(32)));
    }

    protected function tearDown(): void
    {
        config()->set('84pad.backup_key', $this->previousKey);
        foreach ($this->temporaryDirectories as $path) {
            File::deleteDirectory($path);
        }
        parent::tearDown();
    }

    private function fixture(): array
    {
        $prefix = storage_path('framework/testing/site-export-'.bin2hex(random_bytes(7)));
        $root = $prefix.'/site';
        $privateBackup = $prefix.'/offsite';
        $this->temporaryDirectories[] = $prefix;
        foreach (['app', 'vendor', 'plugins', 'public', 'storage/app/public', 'storage/app/private'] as $folder) {
            File::ensureDirectoryExists($root.'/'.$folder);
        }
        file_put_contents($root.'/artisan', "<?php echo 'fixture';\n");
        file_put_contents($root.'/composer.json', '{}');
        file_put_contents($root.'/vendor/autoload.php', "<?php\n");
        file_put_contents($root.'/.env', "APP_KEY=test-private-secret-84pad\n");
        file_put_contents($root.'/app/sample.php', "<?php\n");
        file_put_contents($root.'/plugins/demo.txt', 'plugin fixture');
        file_put_contents($root.'/storage/app/public/photo.txt', 'private-photo-bytes');
        file_put_contents($root.'/storage/app/private/owner.txt', 'private-owner-bytes');
        return [new EncryptedSiteBackup($root, $privateBackup), $root, $privateBackup];
    }

    public function test_encrypted_site_backup_verifies_without_exposing_filenames_or_secrets(): void
    {
        [$backup] = $this->fixture();
        $result = $backup->create();

        $this->assertFileExists($result['path']);
        $this->assertTrue($backup->verify($result['path']));
        $this->assertGreaterThanOrEqual(7, $result['files']);
        $this->assertGreaterThan(0, $result['tables']);
        $this->assertGreaterThan(0, $result['rows']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $result['sha256']);
        $encrypted = file_get_contents($result['path']);
        $this->assertStringNotContainsString('test-private-secret-84pad', $encrypted);
        $this->assertStringNotContainsString('private-owner-bytes', $encrypted);
        $this->assertStringNotContainsString('vendor/autoload.php', $encrypted);
        $this->artisan('84pad:site-backup', ['--verify' => $result['path']])->assertExitCode(0);
    }

    public function test_tampering_and_wrong_encryption_key_are_rejected(): void
    {
        [$backup] = $this->fixture();
        $result = $backup->create();
        $this->assertTrue($backup->verify($result['path']));

        $correct = config('84pad.backup_key');
        config()->set('84pad.backup_key', base64_encode(random_bytes(32)));
        $this->assertFalse($backup->verify($result['path']));
        config()->set('84pad.backup_key', $correct);

        $encrypted = file_get_contents($result['path']);
        $offset = (int) floor(strlen($encrypted) / 2);
        $encrypted[$offset] = chr(ord($encrypted[$offset]) ^ 1);
        file_put_contents($result['path'], $encrypted);
        $this->assertFalse($backup->verify($result['path']));
        $this->artisan('84pad:site-backup', ['--verify' => $result['path']])->assertExitCode(1);
    }

    public function test_no_encryption_key_means_no_backup_is_created(): void
    {
        [$backup, , $dir] = $this->fixture();
        config()->set('84pad.backup_key', '');
        try {
            $backup->create();
            $this->fail('A recovery export must fail closed without a secret key.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('PAD_BACKUP_KEY', $e->getMessage());
        }
        $this->assertFalse(is_dir($dir));
    }

    public function test_unexpected_source_symlink_is_refused_without_publishing_backup(): void
    {
        if (! function_exists('symlink')) {
            $this->markTestSkipped('Symlinks are unavailable.');
        }
        [$backup, $root, $dir] = $this->fixture();
        $link = $root.'/plugins/unsafe.txt';
        if (! @symlink($root.'/.env', $link)) {
            $this->markTestSkipped('Cannot create symlink fixture.');
        }
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Symlink in site backup input.');
            $backup->create();
        } finally {
            @unlink($link);
            $this->assertEmpty(glob($dir.'/*.padbackup') ?: []);
        }
    }

    public function test_production_backup_requires_maintenance_mode(): void
    {
        if (file_exists(storage_path('framework/down')) || is_link(storage_path('framework/down'))) {
            $this->markTestSkipped('Maintenance mode is already active.');
        }
        [$backup] = $this->fixture();
        $previous = $this->app['env'];
        $this->app['env'] = 'production';
        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('requires maintenance mode');
            $backup->create();
        } finally {
            $this->app['env'] = $previous;
        }
    }
}
