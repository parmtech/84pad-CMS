<?php

namespace Tests\Feature;

use App\Support\Updates\CoreSnapshot;
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
}
