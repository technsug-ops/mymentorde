<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * document_upload_tokens'i polymorphic hale getir.
 *
 * Mevcut: guest_application_id, target_student_id (her use-case için ayrı kolon)
 * Yeni:   target_type (string, 'guest_application' | 'student' | 'user' | 'dealer' | ...)
 *         target_id   (string)
 *
 * Bu sayede gelecek modüller (HR onboarding, dealer, mentor başvuru, vb.)
 * yeni kolon eklemeden mevcut altyapıyı kullanabilir.
 *
 * Eski kolonlar geriye uyum için saklanır; yeni kod polymorphic kullanır.
 * Backfill ile mevcut satırlar yeni kolonlara da yazılır.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('document_upload_tokens')) return;

        Schema::table('document_upload_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('document_upload_tokens', 'target_type')) {
                $table->string('target_type', 60)->nullable()->after('guest_application_id');
            }
            if (!Schema::hasColumn('document_upload_tokens', 'target_id')) {
                $table->string('target_id', 64)->nullable()->after('target_type');
                $table->index(['target_type', 'target_id'], 'dut_target_idx');
            }
        });

        // Backfill: mevcut guest_application_id ve target_student_id satırlarını
        // yeni polymorphic alanlara yaz.
        DB::table('document_upload_tokens')
            ->whereNotNull('guest_application_id')
            ->whereNull('target_type')
            ->update([
                'target_type' => 'guest_application',
                'target_id'   => DB::raw('CAST(guest_application_id AS CHAR)'),
            ]);

        DB::table('document_upload_tokens')
            ->whereNotNull('target_student_id')
            ->whereNull('target_type')
            ->update([
                'target_type' => 'student',
                'target_id'   => DB::raw('target_student_id'),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('document_upload_tokens')) return;

        Schema::table('document_upload_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('document_upload_tokens', 'target_id')) {
                $table->dropIndex('dut_target_idx');
                $table->dropColumn(['target_type', 'target_id']);
            }
        });
    }
};
