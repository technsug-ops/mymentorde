<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_builder_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('doc_type', 30);
            $table->string('language', 5)->default('de');
            $table->json('section_order');    // ["greeting", "intro", "body_1", "closing"]
            $table->json('section_templates'); // { "greeting": "Sehr geehrte...", ... }
            $table->json('variables')->nullable(); // desteklenen değişkenler
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->string('created_by', 191)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'doc_type', 'language'], 'idx_company_type_lang');
            $table->index('company_id');
            $table->index(['doc_type', 'language']);

            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_builder_templates');
    }
};
