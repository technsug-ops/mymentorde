<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table): void {
            $table->string('source_url')->nullable()->after('body_en');
            // 'pdf' | 'video' | 'link' | null (auto-detect)
            $table->string('media_type', 16)->nullable()->after('source_url');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table): void {
            $table->dropColumn(['source_url', 'media_type']);
        });
    }
};
