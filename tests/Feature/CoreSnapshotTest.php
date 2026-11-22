<?php

namespace Tests\Feature;

use App\Support\Updates\CoreSnapshot;
use App\Support\Updates\CoreSnapshotRestoreException;
use Tests\TestCase;
use ZipArchive;

final class CoreSnapshotTest extends TestCase
{
    /** @var array<int,string> */
    private array $snapshots = [];

    protected function tearDown(): void
    {
        foreach ($this->snapshots as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    public function test_snapshot_is_verified_and_does_not_capture_runtime_secrets(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZIP required');
        }
        $snapshot = app(CoreSnapshot::class);
        $result = $snapshot->create();
        $this->snapshots[] = $result['path'];

        $this->assertTrue($snapshot->verify($result['path']));
        $this->assertGreaterThan(10, $result['files']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $result['sha256']);

        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($result['path'], ZipArchive::RDONLY));
        try {
            $this->assertNotFalse($zip->locateName('snapshot.json'));
            $this->assertNotFalse($zip->locateName('source/artisan'));
            $this->assertNotFalse($zip->locateName('source/public/index.php'));
            $this->assertFalse($zip->locateName('source/.env'));
            $this->assertFalse($zip->locateName('source/vendor/autoload.php'));
            $this->assertFalse($zip->locateName('source/storage/logs/laravel.log'));
            $this->assertFalse($zip->locateName('source/public/storage'));
        } finally {
            $zip->close();
        }
    }

    public function test_snapshot_tampering_is_detected_before_any_restore(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZIP required');
        }
        $snapshot = app(CoreSnapshot::class);
        $result = $snapshot->create();
        $this->snapshots[] = $result['path'];

        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($result['path']));
        try {
            $this->assertTrue($zip->addFromString('source/artisan', 'tampered!'));
        } finally {
            $zip->close();
        }

        $this->assertFalse($snapshot->verify($result['path']));
        $this->artisan('84pad:core-snapshot', [
            '--verify' => $result['path'],
        ])->assertExitCode(1);
    }

    public function test_snapshot_cli_creates_new_archive_without_changing_database(): void
    {
        $root = storage_path('app/private/core-snapshots');
        $previous = glob($root.'/core-*.zip') ?: [];
        $this->artisan('84pad:core-snapshot')->assertExitCode(0);
        $created = array_values(array_diff(glob($root.'/core-*.zip') ?: [], $previous));
        $this->assertCount(1, $created);
        $this->snapshots[] = $created[0];
        $this->assertTrue(app(CoreSnapshot::class)->verify($created[0]));
    }

    public function test_restore_reports_failed_recovery_when_private_staging_is_unsafe(): void
    {
        if (! class_exists(ZipArchive::class) || ! function_exists('symlink')) {
            $this->markTestSkipped('ZIP and symlink support are required');
        }

        $staging = storage_path('app/private/core-restore-staging');
        if (file_exists($staging) || is_link($staging)) {
            $this->markTestSkipped('The restore staging directory must be unused');
        }

        $snapshot = app(CoreSnapshot::class);
        $result = $snapshot->create();
        $root = storage_path('app/private/core-snapshots');
        $before = glob($root.'/core-*.zip') ?: [];
        $outside = storage_path('app/private/restore-staging-probe-'.bin2hex(random_bytes(6)));
        $this->assertTrue(mkdir($outside, 0700));

        try {
            if (! @symlink($outside, $staging)) {
                $this->markTestSkipped('Cannot create the symlink safety fixture');
            }

            try {
                $snapshot->restore($result['path'], $result['sha256']);
                $this->fail('Unsafe staging must prevent restore.');
            } catch (CoreSnapshotRestoreException $e) {
                $this->assertFalse($e->automaticRecoveryCompleted);
                $this->assertStringContainsString('automatic recovery was incomplete', $e->getMessage());
            }
        } finally {
            if (is_link($staging)) {
                @unlink($staging);
            }
            @rmdir($outside);
            foreach (array_diff(glob($root.'/core-*.zip') ?: [], $before) as $archive) {
                @unlink($archive);
            }
            @unlink($result['path']);
        }
    }
}
