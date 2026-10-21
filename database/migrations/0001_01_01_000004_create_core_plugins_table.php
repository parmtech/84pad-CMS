<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('core_plugins', function(Blueprint $t): void {
            $t->string('slug',64)->primary();$t->string('version',32);$t->boolean('enabled')->default(false);$t->timestamps();
        });
    }
    public function down(): void {Schema::dropIfExists('core_plugins');}
};
