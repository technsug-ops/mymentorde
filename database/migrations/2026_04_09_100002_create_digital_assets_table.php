<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_assets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->string('name', 200);
            $table->string('original_filename', 255);
            $table->string('mime_type', 120);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('disk', 20)->default('local');
            $table->string('path', 500);
            $table->string('thumbnail_path', 500)->nullable();
            // image | video | audio | document | archive | other
            $table->string('category', 20)->default('other');
            $table->json('tags')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->string('legacy_source', 50)->nullable();
            $table->unsignedBigInteger('legacy_source_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'folder_id']);
            $table->index(['company_id', 'category']);
            $table->index(['legacy_source', 'legacy_source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_assets');
    }
};
