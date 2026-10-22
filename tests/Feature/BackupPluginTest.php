<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Pad\Backup\BackupService;
use Tests\TestCase;
use ZipArchive;

require_once __DIR__.'/../../plugins/pad-backup/src/BackupService.php';

final class BackupPluginTest extends TestCase
{
    use RefreshDatabase;

    private ?string $previousSettings = null;
    private array $previousFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $dir = storage_path('app/private/pad-backups');
        $settings = $dir.'/settings.json';
        $this->previousSettings = is_file($settings) ? (string)file_get_contents($settings) : null;
        $this->previousFiles = is_dir($dir) ? glob($dir.'/*') ?: [] : [];
    }

    protected function tearDown(): void
    {
        $dir = storage_path('app/private/pad-backups');
        foreach (array_diff(glob($dir.'/*') ?: [], $this->previousFiles) as $path) {
            if (is_file($path)) @unlink($path);
        }
        if ($this->previousSettings === null) {
            @unlink($dir.'/settings.json');
        } else {
            file_put_contents($dir.'/settings.json', $this->previousSettings);
        }
        parent::tearDown();
    }

    private function owner(): User
    {
        $owner = User::query()->create([
            'name'=>'Backup Owner','email'=>'backup-owner@example.test',
            'password'=>'long-test-password','role'=>'owner',
        ]);
        DB::table('core_installations')->insert([
            'id'=>1,'owner_user_id'=>$owner->id,'created_at'=>now(),
        ]);
        return $owner;
    }

    public function test_backup_panel_requires_owner_and_active_plugin(): void
    {
        $owner = $this->owner();
        $this->get('/dashboard/extensions/pad-backup')->assertRedirect('/login');
        $this->actingAs($owner)->get('/dashboard/extensions/pad-backup')->assertNotFound();
        $this->post('/dashboard/plugins/pad-backup/state',['action'=>'activate'])->assertRedirect();
        $this->get('/dashboard/extensions/pad-backup')->assertOk()->assertSee('Backup');
        $this->post('/dashboard/plugins/pad-backup/state',['action'=>'deactivate'])->assertRedirect();
        $this->get('/dashboard/extensions/pad-backup')->assertNotFound();
    }

    public function test_owner_can_set_schedule_without_cpanel_api(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post('/dashboard/plugins/pad-backup/state',['action'=>'activate'])->assertRedirect();
        $this->post('/dashboard/extensions/pad-backup/settings',['frequency'=>'hourly'])->assertRedirect();
        $this->assertSame('hourly',(new BackupService())->settings()['frequency']);
        $this->assertSame(60,(new BackupService())->settings()['retention_days']);
        $this->post('/dashboard/extensions/pad-backup/settings',['frequency'=>'every second'])->assertSessionHasErrors('frequency');
    }

    public function test_invalid_portable_import_does_not_execute_or_install(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post('/dashboard/plugins/pad-backup/state',['action'=>'activate'])->assertRedirect();
        $file = UploadedFile::fake()->createWithContent('bad.zip','not a backup');
        $this->post('/dashboard/extensions/pad-backup/import',['archive'=>$file])
            ->assertRedirect('/dashboard/extensions/pad-backup');
        $this->assertCount(0,(new BackupService())->list());
    }

    public function test_expired_snapshots_are_pruned_even_when_schedule_is_off(): void
    {
        $service = new BackupService();
        $service->configure('off');
        $id = 'b20200101000000-0123456789abcdef';
        file_put_contents($service->directory().'/'.$id.'.84bk','dummy');
        file_put_contents($service->directory().'/'.$id.'.json',json_encode([
            'id'=>$id,'created_at'=>time()-61*86400,'reason'=>'manual',
        ]));
        $service->tick();
        $this->assertFileDoesNotExist($service->directory().'/'.$id.'.84bk');
    }

    public function test_mysql_backup_is_verified_and_portable_zip_has_expected_contents(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || !class_exists(ZipArchive::class)) {
            $this->markTestSkipped('Requires MySQL and ZIP extensions.');
        }
        $service = new BackupService();
        $created = $service->backup();
        $this->assertTrue($service->verify($created['id']));
        $this->assertSame(64,strlen($created['sha256']));
        $export = $service->portableExport($created['id']);
        try {
            $zip = new ZipArchive();
            $this->assertSame(true,$zip->open($export));
            $this->assertNotFalse($zip->locateName('site/database.sql'));
            $this->assertNotFalse($zip->locateName('site/.env'));
            $this->assertNotFalse($zip->locateName('site/plugins/pad-backup/plugin.json'));
            $zip->close();

            $uploaded = new UploadedFile($export, 'portable.zip', 'application/zip', null, true);
            $imported = $service->portableImport($uploaded);
            $this->assertSame('imported', $imported['reason']);
            $this->assertTrue($service->verify($imported['id']));
            // Changing any ciphertext byte must invalidate the stored digest.
            file_put_contents($service->directory().'/'.$created['id'].'.84bk', 'altered', FILE_APPEND);
            $this->assertFalse($service->verify($created['id']));
        } finally {
            @unlink($export);
        }
    }
}
