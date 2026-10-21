<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('core_plugin_schema_history', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64);
            $table->unsignedInteger('revision');
            $table->string('table_name', 64);
            $table->char('checksum', 64);
            $table->timestamps();
            $table->unique(['slug', 'revision', 'table_name'], 'core_plugin_schema_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_plugin_schema_history');
    }
};
