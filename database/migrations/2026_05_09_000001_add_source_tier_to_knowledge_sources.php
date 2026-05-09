<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Knowledge sources için 2-tier kategorilendirme:
 *   - institutional: Kurumsal kaynaklar (uni-assist, DAAD, ASD, resmi kurumlar)
 *   - web: Web tabanlı kaynaklar (blog, danışmanlık firmaları, influencer'lar)
 *
 * AI cevabında: önce kurumsal taranır + ismiyle kaynaklanır.
 * Web kaynakları "MentorDE Kütüphanesi" olarak grupp lanır, dış firma adı sızdırmaz.
 *
 * Backward compat: mevcut kayıtlar tier'a heuristic atanır:
 *   - type='url' → web (dış link genelde web kaynağı)
 *   - type='pdf' veya 'document' → institutional (yüklenmiş resmi belge varsayımı)
 *   - type='text' veya 'image' → institutional (manuel girilmiş genelde resmi)
 * Manager'lar daha sonra UI'dan tier'ı düzeltebilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('knowledge_sources')) return;

        if (!Schema::hasColumn('knowledge_sources', 'source_tier')) {
            Schema::table('knowledge_sources', function (Blueprint $t) {
                // ENUM yerine string + check — bazı KAS MySQL versiyonlarında ENUM problem çıkarıyor
                $t->string('source_tier', 16)->default('institutional')->after('category');
                $t->index(['company_id', 'source_tier'], 'ks_company_tier_idx');
            });

            // Mevcut kayıtlar için heuristic atama
            DB::table('knowledge_sources')
                ->where('type', 'url')
                ->update(['source_tier' => 'web']);
            // Diğer tipler default 'institutional' olarak kalır (column default)
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('knowledge_sources') && Schema::hasColumn('knowledge_sources', 'source_tier')) {
            Schema::table('knowledge_sources', function (Blueprint $t) {
                $t->dropIndex('ks_company_tier_idx');
                $t->dropColumn('source_tier');
            });
        }
    }
};
