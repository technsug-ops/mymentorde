<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UniMatch favori program ID'leri.
 * Kullanıcı sonuç sayfasında 3'e kadar program "favorile"yebilir.
 * PDF export favori-only modunda çalışabilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uni_match_responses')) return;
        Schema::table('uni_match_responses', function (Blueprint $table) {
            if (! Schema::hasColumn('uni_match_responses', 'favorite_program_ids')) {
                $table->json('favorite_program_ids')->nullable()->after('lead_captured_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('uni_match_responses')) return;
        Schema::table('uni_match_responses', function (Blueprint $table) {
            if (Schema::hasColumn('uni_match_responses', 'favorite_program_ids')) {
                $table->dropColumn('favorite_program_ids');
            }
        });
    }
};
