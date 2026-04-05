<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_media_library', function (Blueprint $table): void {
            $table->id();
            $table->string('file_name');
            $table->string('file_url', 500);
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('file_type');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt_text')->nullable();
            $table->json('tags')->nullable();
            $table->json('used_in_content_ids')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('file_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_media_library');
    }
};
