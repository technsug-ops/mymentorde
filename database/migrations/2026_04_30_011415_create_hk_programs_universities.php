<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hochschulkompass (HK) program kataloğu için boş iskelet.
 *
 * Şu an HK API entegrasyonu yapılmamış — sadece tablolar oluşturulur.
 * Gelecekte (3-6 ay) HochschulkompassCatalogAdapter implementasyonu
 * yazılırken bu tablolar kullanılır.
 *
 * Schema Expatrio'ya benzer ama HK-spesifik alanlar var:
 *  - hk_id     : HK'nın kendi numerik veya UUID identifier'ı
 *  - nc_value  : Numerus Clausus değeri (Almanya'ya özgü kontenjan göstergesi)
 *  - application_deadline_summer/winter : HK resmi deadlines
 *
 * Stratejik amaç: Expatrio yedek/fallback olarak HK kullanılabilsin,
 * 7,000+ ek Almanca program kapsama girsin.
 */
return new class extends Migration {
    public function up(): void
    {
        // ── Hochschulkompass üniversite kataloğu (boş iskelet) ──
        Schema::create('hk_universities', function (Blueprint $table) {
            $table->string('id', 64)->primary()->comment('HK kendi identifier');
            $table->string('name', 255);
            $table->string('city', 120)->nullable();
            $table->string('state', 60)->nullable()->comment('Bundesland — Bayern, NRW, vb.');
            $table->string('type', 40)->nullable()->comment('Universität, Fachhochschule, Kunsthochschule vb.');
            $table->boolean('is_public')->nullable()->comment('Devlet üni mi?');
            $table->unsignedInteger('program_count')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        // ── Hochschulkompass program kataloğu (boş iskelet) ──
        Schema::create('hk_programs', function (Blueprint $table) {
            $table->string('id', 64)->primary()->comment('HK kendi identifier');
            $table->string('university_id', 64)->index();
            $table->string('university_name', 255)->index()->comment('Denormalize search hızı');
            $table->string('course_name', 255)->index();
            $table->string('degree_specification', 120)->nullable();
            $table->string('location', 120)->nullable();
            $table->json('languages')->nullable();
            $table->json('study_fields')->nullable();
            $table->json('subjects')->nullable();
            $table->unsignedSmallInteger('semester_count')->nullable();
            $table->unsignedInteger('tuition_fees_per_semester')->nullable();
            // ── HK-spesifik alanlar ──
            $table->date('application_deadline_summer')->nullable();
            $table->date('application_deadline_winter')->nullable();
            $table->string('nc_value', 30)->nullable()->comment('Numerus Clausus (örn. 1.8, "frei", "NC")');
            $table->string('admission_type', 60)->nullable()->comment('zulassungsfrei / NC / Eignungsprüfung');
            $table->json('data')->nullable()->comment('Tam HK API response');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->foreign('university_id')->references('id')->on('hk_universities')->cascadeOnDelete();
            $table->index(['degree_specification', 'university_id'], 'hk_progs_degree_uni_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hk_programs');
        Schema::dropIfExists('hk_universities');
    }
};
