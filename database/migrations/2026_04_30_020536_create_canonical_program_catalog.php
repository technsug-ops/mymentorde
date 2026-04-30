<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faz 1 — Canonical Program Catalog (kaynak-bağımsız).
 *
 * 4 yeni tablo:
 *  1. universities       — canonical Almanya üniversiteleri (kaynak-bağımsız)
 *  2. programs           — canonical program kataloğu (kaynak-bağımsız)
 *  3. program_source_links — canonical ↔ source (Expatrio/HK) eşleştirmeleri
 *  4. program_change_logs  — kaynak diff yakalama, manager dashboard için
 *
 * STRATEJİ — Source-Resilient Pattern:
 *  - Expatrio/HK schema'sı değişirse canonical tablolar bozulmaz
 *  - Kaynak değişikliği detection ile manager dashboard'a uyarı düşer
 *  - Manuel curation öncelikli (manager dokunduğu kayıt re-sync ile bozulmaz)
 *
 * İZOLASYON KURALI (feedback_data_feature_isolation.md):
 *  - Bu 4 tablo HEPSI SHARED (company_id YOK) — referans veri.
 *  - Tüm company'ler ortak okur, cross-tenant veri sızıntısı yok
 *    (zaten public Almanya program kataloğu).
 *  - Smart matching tabloları (acceptance_records, profile_vectors) ileride
 *    company-scoped olacak — sonraki migration'da.
 */
return new class extends Migration {
    public function up(): void
    {
        // ── 1) universities (canonical) ─────────────────────────────
        Schema::create('universities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('city', 120)->nullable();
            $table->string('state', 80)->nullable()->comment('Bundesland — Bayern, NRW, vb.');
            $table->string('type', 60)->nullable()->comment('Universität, Fachhochschule, Kunsthochschule, Sonstiges');
            $table->boolean('is_public')->nullable()->comment('Devlet üni mi?');
            $table->string('image_path', 500)->nullable();
            $table->json('metadata')->nullable()->comment('Source-spesifik ek bilgiler — denormalize sorgu için değil');
            $table->boolean('is_manually_curated')->default(false)->comment('Manager dokunduysa true — re-sync override etmez');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('name');
            $table->index(['city', 'is_active'], 'universities_city_active_idx');
        });

        // ── 2) programs (canonical) ─────────────────────────────────
        Schema::create('programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('university_id');
            $table->string('university_name_cached', 255)->comment('Denormalize search hızı için');
            $table->string('course_name', 255);
            $table->string('degree_type', 30)->nullable()->comment('bachelor|master|phd|studienkolleg|sprachkurs|ausbildung|other');
            $table->string('degree_specification', 120)->nullable()->comment('"Bachelor of Arts (B.A.)" gibi orijinal hali');
            $table->string('language', 20)->nullable()->comment('de|en|both|other');
            $table->json('languages_raw')->nullable()->comment('Orijinal liste (English, German vb.)');
            $table->string('location', 120)->nullable();
            $table->unsignedSmallInteger('duration_semesters')->nullable();
            $table->unsignedInteger('tuition_eur_per_semester')->nullable();
            $table->date('application_deadline_summer')->nullable();
            $table->date('application_deadline_winter')->nullable();
            $table->string('admission_type', 40)->nullable()->comment('zulassungsfrei|nc|eignungspruefung|other');
            $table->string('nc_value', 30)->nullable()->comment('"1.8", "frei", "NC" — string çünkü "frei" gibi non-numeric değerler var');
            $table->json('study_fields')->nullable()->comment('Akademik alanlar — normalize edilmiş set');
            $table->json('subjects')->nullable()->comment('Konu/spesifik alan listesi');
            $table->unsignedTinyInteger('quality_score')->default(50)->comment('0-100, hangi field\'lar dolu (eksik bilgi puanı)');
            $table->json('metadata')->nullable()->comment('Source-bağımsız extra alan');
            $table->boolean('is_manually_curated')->default(false)->comment('Manager dokunduysa true');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('university_id')->references('id')->on('universities')->cascadeOnDelete();
            $table->index('course_name');
            $table->index('university_name_cached');
            $table->index(['degree_type', 'is_active'], 'programs_degree_active_idx');
            $table->index(['language', 'is_active'], 'programs_lang_active_idx');
        });

        // ── 3) program_source_links (canonical ↔ source) ────────────
        Schema::create('program_source_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('program_id');
            $table->string('source', 30)->comment('expatrio|hk|manual');
            $table->string('external_id', 100)->comment('Source\'un kendi ID\'si (Expatrio UUID veya HK kodu)');
            $table->json('raw_data')->nullable()->comment('Kaynaktan gelen tam veri — diff için snapshot');
            $table->string('checksum', 64)->nullable()->comment('SHA256 of raw_data — change detection');
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_primary')->default(false)->comment('Birden fazla kaynak çakışırsa hangisi öncelikli');
            $table->timestamps();

            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $table->unique(['source', 'external_id'], 'psl_source_external_uq');
            $table->index('program_id');
        });

        // ── 4) program_change_logs (audit + manager dashboard) ──────
        Schema::create('program_change_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('program_id')->nullable()->comment('null = silinmiş program');
            $table->string('source', 30);
            $table->string('field_changed', 80)->comment('course_name, deadline_winter, tuition vb.');
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->string('severity', 20)->default('info')->comment('info|warning|critical');
            $table->timestamp('detected_at')->useCurrent();
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reviewer_action', 30)->nullable()->comment('accepted|rejected|manual_override|ignored');
            $table->text('reviewer_note')->nullable();
            $table->timestamps();

            $table->index(['severity', 'reviewed_at'], 'pcl_severity_reviewed_idx');
            $table->index(['program_id', 'detected_at'], 'pcl_program_detected_idx');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_change_logs');
        Schema::dropIfExists('program_source_links');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('universities');
    }
};
