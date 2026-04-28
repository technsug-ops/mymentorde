<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * document_upload_tokens'a target_student_id kolonu ekle.
 *
 * Token artık iki tür sahibi destekler:
 * - guest_application_id (aday öğrenci — GuestApplication.id)
 * - target_student_id (öğrenci — string, Document.student_id ile aynı format)
 *
 * Mutually exclusive: bir token tek tür sahibe ait olur.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('document_upload_tokens')) return;
        if (Schema::hasColumn('document_upload_tokens', 'target_student_id')) return;

        Schema::table('document_upload_tokens', function (Blueprint $table) {
            $table->string('target_student_id', 64)->nullable()->after('guest_application_id');
            $table->string('target_display_name', 190)->nullable()->after('target_student_id');
            $table->index('target_student_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('document_upload_tokens')) return;
        Schema::table('document_upload_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('document_upload_tokens', 'target_student_id')) {
                $table->dropIndex(['target_student_id']);
                $table->dropColumn(['target_student_id', 'target_display_name']);
            }
        });
    }
};
