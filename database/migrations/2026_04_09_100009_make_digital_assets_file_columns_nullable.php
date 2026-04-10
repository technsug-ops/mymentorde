<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Link asset'lerinde dosya metadata'sı yok (mime_type, extension, disk, path).
        // Bu kolonları nullable yapıyoruz ki source_type='link' kayıtları sorunsuz eklenebilsin.
        Schema::table('digital_assets', function (Blueprint $table): void {
            $table->string('mime_type', 120)->nullable()->change();
            $table->string('extension', 20)->nullable()->change();
            $table->string('disk', 20)->nullable()->change();
            $table->string('path', 500)->nullable()->change();
            $table->string('original_filename', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('digital_assets', function (Blueprint $table): void {
            $table->string('mime_type', 120)->nullable(false)->change();
            $table->string('extension', 20)->nullable(false)->change();
            $table->string('disk', 20)->default('local')->nullable(false)->change();
            $table->string('path', 500)->nullable(false)->change();
            $table->string('original_filename', 255)->nullable(false)->change();
        });
    }
};
