<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_sources', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->nullable()->index();

            $t->string('title', 200);
            $t->string('type', 20);                   // pdf | url | text
            $t->string('category', 80)->nullable();   // örn "vize", "uni-assist", "sperrkonto"

            // İçerik kaynağı — type'a göre biri dolu olur
            $t->string('file_path', 500)->nullable();     // storage/app/private/ai-labs/{company}/...
            $t->string('url', 500)->nullable();
            $t->longText('content_markdown')->nullable(); // url fetch veya düz metin burada cache'lenir

            // Gemini File API upload cache
            $t->string('gemini_file_id', 200)->nullable();
            $t->string('gemini_file_uri', 500)->nullable();
            $t->timestamp('gemini_uploaded_at')->nullable();
            $t->string('content_hash', 64)->nullable();   // SHA256 — değişti mi kontrol

            // Hedef kitle
            $t->enum('target_audience', ['student', 'guest', 'both'])->default('both');

            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('citation_count')->default(0); // kaç kez citation'da kullanıldı
            $t->timestamp('last_used_at')->nullable();

            $t->unsignedBigInteger('created_by_user_id')->nullable()->index();

            $t->timestamps();

            $t->index(['company_id', 'is_active']);
            $t->index(['company_id', 'target_audience']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_sources');
    }
};
