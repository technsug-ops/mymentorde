<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UniMatch UTM tracking — marketing kanal attribution.
 *
 * Şu an sadece "source" var (utm_source). UTM'in tam seti:
 *  - utm_source     (source zaten var, alias)
 *  - utm_medium     (cpc/email/social/organic)
 *  - utm_campaign   (summer_2026/post_apply/...)
 *  - utm_content    (a/b test variant, ad_id)
 *  - utm_term       (keyword, paid arama)
 *
 * Funnel dashboard'da "Hangi kampanya kaç lead getirdi" kırılımı.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('uni_match_responses')) return;

        Schema::table('uni_match_responses', function (Blueprint $table) {
            foreach (['utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $col) {
                if (! Schema::hasColumn('uni_match_responses', $col)) {
                    $table->string($col, 100)->nullable()->after('source');
                }
            }
            // Indexes for funnel filter performance
            if (! Schema::hasColumn('uni_match_responses', 'utm_campaign')) return;
            try { $table->index('utm_campaign', 'umr_utm_campaign_idx'); } catch (\Throwable $e) { /* zaten varsa ignore */ }
            try { $table->index('utm_medium', 'umr_utm_medium_idx'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('uni_match_responses')) return;

        Schema::table('uni_match_responses', function (Blueprint $table) {
            foreach (['utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $col) {
                if (Schema::hasColumn('uni_match_responses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
