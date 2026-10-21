<?php

namespace App\Support\Plugins;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

final class PluginSchemaManager
{
    public function __construct(private readonly PluginCatalog $catalog) {}

    /** @return array{created:int,skipped:int} */
    public function apply(string $slug): array
    {
        $manifest = $this->catalog->all()[$slug] ?? null;
        if ($manifest === null || ! $this->catalog->compatible($manifest)) {
            throw new RuntimeException('Unknown or incompatible plugin.');
        }
        if (! Schema::hasTable('core_plugin_schema_history')) {
            throw new RuntimeException('Core plugin-schema ledger has not been migrated.');
        }

        $file = base_path('plugins/'.$slug.'/schema.json');
        if (is_link($file) || ! is_file($file)
            || filesize($file) === false || filesize($file) > 32768) {
            throw new RuntimeException('Missing or oversized plugin schema definition.');
        }

        try {
            $schema = json_decode((string) file_get_contents($file), true, 16, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new RuntimeException('Invalid plugin schema JSON.');
        }

        $revision = $schema['revision'] ?? null;
        $tables = $schema['tables'] ?? null;
        if (! is_array($schema) || ! is_int($revision)
            || $revision < 1 || $revision > 10000
            || ! is_array($tables) || ! array_is_list($tables)
            || count($tables) < 1 || count($tables) > 16) {
            throw new RuntimeException('Invalid plugin schema structure.');
        }

        // Pre-validate the ENTIRE plan before any DDL, rather than partially
        // creating tables and only then encountering an invalid later entry.
        $tablePlans = [];
        $usedNames = [];
        $prefix = 'padp_'.str_replace('-', '_', $slug).'_';

        foreach ($tables as $definition) {
            if (! is_array($definition)) {
                throw new RuntimeException('Invalid plugin table definition.');
            }
            $name = $definition['name'] ?? null;
            $columns = $definition['columns'] ?? null;
            if (! is_string($name) || strlen($name) > 64
                || ! str_starts_with($name, $prefix)
                || ! preg_match('/^padp_[a-z0-9_]+$/D', $name)
                || isset($usedNames[$name]) || ! is_array($columns)
                || ! array_is_list($columns) || count($columns) < 1
                || count($columns) > 24) {
                throw new RuntimeException('Plugin tables must use a unique namespaced table name.');
            }
            $usedNames[$name] = true;
            $usedColumns = ['id' => true];
            foreach ($columns as $column) {
                if (! is_array($column)) {
                    throw new RuntimeException('Invalid plugin schema column.');
                }
                $columnName = $column['name'] ?? null;
                $type = $column['type'] ?? null;
                $nullable = $column['nullable'] ?? false;
                if (! is_string($columnName) || strlen($columnName) > 48
                    || ! preg_match('/^[a-z][a-z0-9_]*$/D', $columnName)
                    || isset($usedColumns[$columnName])
                    || ! is_string($type)
                    || ! in_array($type, ['string','text','integer','unsignedBigInteger','boolean','timestamp'], true)
                    || ! is_bool($nullable)) {
                    throw new RuntimeException('Unsafe or unsupported plugin schema column.');
                }
                if ($type === 'string') {
                    $length = $column['length'] ?? 191;
                    if (! is_int($length) || $length < 1 || $length > 191) {
                        throw new RuntimeException('String column length out of bounds.');
                    }
                } elseif (array_key_exists('length', $column)) {
                    throw new RuntimeException('Column length is not supported for this type.');
                }
                $usedColumns[$columnName] = true;
            }

            $tablePlans[] = [
                'name' => $name,
                'columns' => $columns,
                'checksum' => hash('sha256', json_encode(
                    ['revision' => $revision, 'table' => $definition],
                    JSON_THROW_ON_ERROR
                )),
            ];
        }

        $created = 0;
        $skipped = 0;
        foreach ($tablePlans as $plan) {
            $criteria = [
                'slug' => $slug,
                'revision' => $revision,
                'table_name' => $plan['name'],
            ];
            $record = DB::table('core_plugin_schema_history')->where($criteria)->first();
            $tableExists = Schema::hasTable($plan['name']);

            if ($record !== null) {
                if (! hash_equals($record->checksum, $plan['checksum']) || ! $tableExists) {
                    throw new RuntimeException('Previously installed plugin schema was altered.');
                }
                $skipped++;
                continue;
            }
            // Fail closed on existing unrelated tables, even if their
            // names share the slug prefix. Never adopt or overwrite them.
            if ($tableExists) {
                throw new RuntimeException('Plugin table already exists without matching history.');
            }

            Schema::create($plan['name'], function (Blueprint $table) use ($plan): void {
                $table->bigIncrements('id');
                foreach ($plan['columns'] as $col) {
                    $name = $col['name'];
                    $field = match ($col['type']) {
                        'string' => $table->string($name, $col['length'] ?? 191),
                        'text' => $table->text($name),
                        'integer' => $table->integer($name),
                        'unsignedBigInteger' => $table->unsignedBigInteger($name),
                        'boolean' => $table->boolean($name),
                        'timestamp' => $table->timestamp($name),
                    };
                    if ($col['nullable'] ?? false) {
                        $field->nullable();
                    }
                }
            });

            // MySQL DDL commits implicitly. On failure between CREATE and
            // ledger insertion we abort next run, rather than altering a
            // table of unknown provenance. Operator review is then required.
            DB::table('core_plugin_schema_history')->insert(
                $criteria + [
                    'checksum' => $plan['checksum'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
