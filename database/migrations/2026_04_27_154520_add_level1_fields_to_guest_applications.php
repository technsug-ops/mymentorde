<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 3-Level Form Hiyerarşisi versiyonlama kolonu.
 *
 * Level 0 = Apply Form (public, /apply) — mevcut kolonlar yeter
 * Level 1 = Aday Öğrenci (/registration/form, 6 wizard) — yeni 11 field
 * Level 2 = Öğrenci (/student/full-registration, 8 wizard) — mevcut 85 field
 *
 * KARAR: Yeni Level 1 field'ları mevcut Level 2 ile aynı pattern'de
 * `registration_form_draft` JSON içinde saklanır. Bu migration sadece form
 * tamamlanma seviyesini izleyen versiyonlama kolonu ekler.
 *
 * Cumulative subset: Level N field'ları aynı JSON'da yaşar, Level N+1'de
 * pre-fill mekanizması (mevcut) otomatik gösterir.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            $table->string('registration_form_level', 30)->default('level_1_pending')
                ->after('registration_form_submitted_at')
                ->comment('level_1_pending / level_1_done / level_2_pending / level_2_done');

            $table->index('registration_form_level', 'idx_guest_apps_form_level');
        });

        // Backfill — mevcut guest'leri doğru level'a işaretle
        DB::statement("
            UPDATE guest_applications
            SET registration_form_level = CASE
                WHEN registration_form_submitted_at IS NOT NULL THEN 'level_2_done'
                WHEN registration_form_draft IS NOT NULL AND registration_form_draft != '' THEN 'level_2_pending'
                ELSE 'level_1_pending'
            END
        ");
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table): void {
            $table->dropIndex('idx_guest_apps_form_level');
            $table->dropColumn('registration_form_level');
        });
    }
};
