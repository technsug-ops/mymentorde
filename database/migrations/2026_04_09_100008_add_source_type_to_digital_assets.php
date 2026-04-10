<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_assets', function (Blueprint $table): void {
            // 'file' = klasik dosya yüklemesi (varsayılan)
            // 'link' = harici URL referansı (Google Drive, YouTube, vb.)
            $table->string('source_type', 16)->default('file')->after('uuid');
            $table->string('external_url', 1000)->nullable()->after('source_type');

            $table->index('source_type');
        });

        // Mevcut tüm kayıtlar otomatik 'file' (default) olduğu için ekstra UPDATE gerekmez.
    }

    public function down(): void
    {
        Schema::table('digital_assets', function (Blueprint $table): void {
            $table->dropIndex(['source_type']);
            $table->dropColumn(['source_type', 'external_url']);
        });
    }
};
