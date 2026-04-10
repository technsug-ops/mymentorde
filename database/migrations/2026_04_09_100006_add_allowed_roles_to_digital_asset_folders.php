<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_asset_folders', function (Blueprint $table): void {
            // null = herkes (DAM izni olan tüm roller); array = sadece bu rollerdeki kullanıcılar görür.
            // Örn: ["manager", "marketing_admin"]
            $table->json('allowed_roles')->nullable()->after('is_system');
        });
    }

    public function down(): void
    {
        Schema::table('digital_asset_folders', function (Blueprint $table): void {
            $table->dropColumn('allowed_roles');
        });
    }
};
