<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faz 1+ — Canonical programs tablosunu Expatrio detail endpoint'inin
 * tüm field'larıyla zenginleştir.
 *
 * Detail endpoint'te olan, search'te olmayan alanlar:
 *  - infoText (uzun açıklama, markdown)
 *  - qualificationRequirements (giriş koşulları)
 *  - languageRequirements (dil sertifikası gereksinimleri)
 *  - requiredDocuments (gereken belgeler listesi)
 *  - applicationFee (non-EU başvuru ücreti)
 *  - costPerSemester (yaşam gideri tahmini)
 *
 * 13K program × detail sync sonrası bu field'lar dolacak.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->text('description')->nullable()->after('subjects')
                ->comment('Programın uzun açıklama metni (Expatrio infoText, markdown)');
            $table->text('qualification_requirements')->nullable()->after('description')
                ->comment('Giriş/kabul koşulları (önceki diploma, GPA, ön şart)');
            $table->text('language_requirements')->nullable()->after('qualification_requirements')
                ->comment('Dil sertifikası gereksinimleri (TestDaF, IELTS, vb.)');
            $table->text('required_documents')->nullable()->after('language_requirements')
                ->comment('Başvuru için gereken belgeler listesi (program-spesifik)');
            $table->unsignedInteger('application_fee_eur')->nullable()->after('tuition_eur_per_semester')
                ->comment('Non-EU başvuru ücreti (€) — varsa');
            $table->unsignedInteger('cost_per_semester_eur')->nullable()->after('application_fee_eur')
                ->comment('Sömestr başına yaşam gideri tahmini (€) — Expatrio costPerSemester');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'qualification_requirements',
                'language_requirements',
                'required_documents',
                'application_fee_eur',
                'cost_per_semester_eur',
            ]);
        });
    }
};
