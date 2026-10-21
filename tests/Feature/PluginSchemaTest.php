<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class PluginSchemaTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $directory = base_path('plugins/schema-test');
        File::ensureDirectoryExists($directory);
        file_put_contents($directory.'/plugin.json', json_encode([
            'id' => 'schema-test',
            'name' => 'Schema Test Plugin',
            'version' => '0.1.0',
            'requires_core' => '0.1.0',
        ], JSON_THROW_ON_ERROR));
        $this->writeSchema();

        $owner = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'strong-password-for-testing',
            'role' => 'owner',
        ]);
        DB::table('core_installations')->insert([
            'id' => 1,
            'owner_user_id' => $owner->id,
            'created_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('padp_schema_test_entries');
        File::deleteDirectory(base_path('plugins/schema-test'));
        parent::tearDown();
    }

    private function writeSchema(?string $name = null, ?string $type = null): void
    {
        file_put_contents(base_path('plugins/schema-test/schema.json'), json_encode([
            'revision' => 1,
            'tables' => [[
                'name' => $name ?? 'padp_schema_test_entries',
                'columns' => [[
                    'name' => 'label',
                    'type' => $type ?? 'string',
                    'length' => 120,
                    'nullable' => false,
                ]],
            ]],
        ], JSON_THROW_ON_ERROR));
    }

    public function test_additive_plugin_schema_is_explicit_and_idempotent(): void
    {
        $this->assertFalse(Schema::hasTable('padp_schema_test_entries'));

        $this->artisan('84pad:plugin-schema', [
            'slug' => 'schema-test',
            '--yes' => true,
        ])->assertExitCode(0);

        $this->assertTrue(Schema::hasTable('padp_schema_test_entries'));
        $this->assertDatabaseHas('core_plugin_schema_history', [
            'slug' => 'schema-test',
            'revision' => 1,
            'table_name' => 'padp_schema_test_entries',
        ]);
        DB::table('padp_schema_test_entries')->insert(['label' => 'keep-me']);

        $this->artisan('84pad:plugin-schema', [
            'slug' => 'schema-test',
            '--yes' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('core_plugin_schema_history', 1);
        $this->assertDatabaseHas('padp_schema_test_entries', ['label' => 'keep-me']);
    }

    public function test_drifted_schema_does_not_change_existing_tables(): void
    {
        $this->artisan('84pad:plugin-schema', [
            'slug' => 'schema-test',
            '--yes' => true,
        ])->assertExitCode(0);

        // A new revision/file must be explicitly reviewed; silently
        // changing a previously applied revision is never permitted.
        $this->writeSchema('padp_schema_test_entries', 'integer');
        $this->artisan('84pad:plugin-schema', [
            'slug' => 'schema-test',
            '--yes' => true,
        ])->assertExitCode(1);

        $this->assertTrue(Schema::hasTable('padp_schema_test_entries'));
        $this->assertDatabaseCount('core_plugin_schema_history', 1);
    }

    public function test_invalid_table_namespace_cannot_touch_core_users(): void
    {
        $this->writeSchema('users');
        $this->artisan('84pad:plugin-schema', [
            'slug' => 'schema-test',
            '--yes' => true,
        ])->assertExitCode(1);

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('core_plugin_schema_history', 0);
    }

    public function test_existing_plugin_prefixed_table_is_not_adopted_without_history(): void
    {
        Schema::create('padp_schema_test_entries', function (Blueprint $table): void {
            $table->bigIncrements('id');
        });

        $this->artisan('84pad:plugin-schema', [
            'slug' => 'schema-test',
            '--yes' => true,
        ])->assertExitCode(1);

        $this->assertTrue(Schema::hasTable('padp_schema_test_entries'));
        $this->assertDatabaseCount('core_plugin_schema_history', 0);
    }
}
