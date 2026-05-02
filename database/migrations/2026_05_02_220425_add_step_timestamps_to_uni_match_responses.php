<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UniMatch wizard funnel analytics için step bazlı timestamp.
 *
 * Format: {"step_1": "2026-05-02T22:04:11Z", "step_2": "...", ...}
 * Drop-off analizi:
 *   - Hangi step'te kaldı (current_step zaten var ama timestamp eklemek
 *     "30 sn'mi 5 dakika mı düşündü" sorusunu cevaplar)
 *   - Step'ler arası süre ortalaması
 *   - "Result'a ulaştı vs converted" funnel
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uni_match_responses')) return;

        Schema::table('uni_match_responses', function (Blueprint $table) {
            if (! Schema::hasColumn('uni_match_responses', 'step_timestamps')) {
                $table->json('step_timestamps')->nullable()->after('answers');
            }
            if (! Schema::hasColumn('uni_match_responses', 'result_viewed_at')) {
                $table->timestamp('result_viewed_at')->nullable()->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('uni_match_responses')) return;

        Schema::table('uni_match_responses', function (Blueprint $table) {
            if (Schema::hasColumn('uni_match_responses', 'step_timestamps')) {
                $table->dropColumn('step_timestamps');
            }
            if (Schema::hasColumn('uni_match_responses', 'result_viewed_at')) {
                $table->dropColumn('result_viewed_at');
            }
        });
    }
};
