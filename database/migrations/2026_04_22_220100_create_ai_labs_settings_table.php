<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_labs_settings', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('company_id')->unique(); // company başına 1 satır

            // Davranış modu
            $t->enum('default_mode', ['strict', 'hybrid'])->default('hybrid');
            // strict = sadece kaynak ("bilmiyorum"), hybrid = havuz dışı fallback + uyarı

            // Provider tercihi
            $t->enum('primary_provider', ['gemini', 'claude', 'openai'])->default('gemini');

            // Günlük limit (Gold tier default değerleri)
            $t->unsignedInteger('daily_limit_student')->default(50);
            $t->unsignedInteger('daily_limit_guest')->default(20);

            // İçerik üretici (Phase 4 — ileride)
            $t->boolean('content_generator_enabled')->default(false);
            $t->unsignedInteger('monthly_doc_limit')->default(10);

            // Response mode dağılım kontrolü (analytics için referans)
            $t->unsignedInteger('questions_this_month')->default(0);
            $t->unsignedInteger('docs_this_month')->default(0);
            $t->date('period_reset_date')->nullable();

            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_labs_settings');
    }
};
