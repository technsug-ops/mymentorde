<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aktivite günlüğü: silme YOK → arşivleme. archived_at dolu olan kayıtlar
 * varsayılan listede görünmez (arşiv görünümünde görünür). Kayıt asla
 * fiziksel silinmez (denetim/izlenebilirlik).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_notes', function (Blueprint $table): void {
            if (!Schema::hasColumn('internal_notes', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('attachments');
                $table->string('archived_by', 191)->nullable()->after('archived_at');
                $table->index('archived_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('internal_notes', function (Blueprint $table): void {
            if (Schema::hasColumn('internal_notes', 'archived_at')) {
                $table->dropIndex(['archived_at']);
                $table->dropColumn(['archived_at', 'archived_by']);
            }
        });
    }
};
