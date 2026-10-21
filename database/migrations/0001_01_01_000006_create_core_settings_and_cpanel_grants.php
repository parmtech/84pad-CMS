<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('core_settings', function (Blueprint $table): void {
            $table->string('key', 100)->primary();
            $table->longText('value');
            $table->timestamps();
        });
        Schema::create('core_cpanel_grants', function (Blueprint $table): void {
            $table->string('plugin_slug', 64);
            $table->string('capability', 32);
            $table->boolean('enabled')->default(false);
            $table->timestamps();
            $table->primary(['plugin_slug', 'capability'], 'core_cpanel_grant_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_cpanel_grants');
        Schema::dropIfExists('core_settings');
    }
};
