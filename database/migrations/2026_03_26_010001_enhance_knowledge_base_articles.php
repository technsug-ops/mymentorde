<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table): void {
            if (!Schema::hasColumn('knowledge_base_articles', 'helpful_count')) {
                $table->unsignedInteger('helpful_count')->default(0)->after('view_count');
            }
            if (!Schema::hasColumn('knowledge_base_articles', 'source_url')) {
                $table->string('source_url', 500)->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('knowledge_base_articles', 'original_filename')) {
                $table->string('original_filename')->nullable()->after('source_url');
            }
            if (!Schema::hasColumn('knowledge_base_articles', 'media_type')) {
                $table->string('media_type', 30)->nullable()->after('original_filename');
            }
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table): void {
            $table->dropColumnIfExists('helpful_count');
        });
    }
};
