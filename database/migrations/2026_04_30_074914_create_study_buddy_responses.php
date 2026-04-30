<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faz 2 — Discovery Wizard cevap kayıtları.
 *
 * İZOLASYON: COMPANY-SCOPED (memory: feedback_data_feature_isolation.md).
 * - Bayi A'nın aday öğrenci wizard cevapları, bayi B ile asla paylaşılmaz
 * - BelongsToCompany trait + global scope ile cross-tenant sızıntı yok
 * - company_id, request domain'ine göre middleware'de resolve edilir
 *   (default 1 — tek-tenant deploy'larda)
 *
 * Anonymous funnel: kullanıcı login değilken doldurabilir.
 * - session_token UUID — browser'da localStorage + DB tracking
 * - completed_at + converted_to_guest_id alanları lifecycle takibi için
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('study_buddy_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->uuid('session_token')->unique()->comment('Browser ↔ DB lookup key (localStorage + URL)');
            $table->json('answers')->nullable()->comment('Tüm cevaplar — adım adım birikir');
            $table->unsignedTinyInteger('current_step')->default(1)->comment('1..N — wizard ilerlemesi');
            $table->unsignedTinyInteger('total_steps')->default(25);
            $table->json('recommendations')->nullable()->comment('RecommendationEngine çıktısı — top 10 canonical program ID + match score');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_active_at')->nullable();

            // Conversion takibi
            $table->unsignedBigInteger('converted_to_guest_id')->nullable()->index()->comment('Wizard sonrası guest_application kaydına dönüşürse FK');
            $table->timestamp('converted_at')->nullable();

            // Tracking & analytics
            $table->string('source', 60)->nullable()->comment('utm_source, referrer kısa');
            $table->string('referrer', 500)->nullable();
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamps();

            $table->index(['company_id', 'completed_at'], 'sbr_company_completed_idx');
            $table->index(['company_id', 'converted_to_guest_id'], 'sbr_company_conv_idx');
            $table->index('last_active_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_buddy_responses');
    }
};
