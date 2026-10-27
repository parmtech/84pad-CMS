<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('core_cpanel_grants', function (Blueprint $table): void {
            $table->char('manifest_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('core_cpanel_grants', function (Blueprint $table): void {
            $table->dropColumn('manifest_hash');
        });
    }
};
