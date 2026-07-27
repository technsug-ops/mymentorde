<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner Frontend F1 — operasyon partner (b2b_partner) çok-bölümlü öğrenci-lead sitesi.
 * dealer-landing (recruiting) ayrı kalır; b2b_partner ayrı partner-site.blade ile render edilir.
 * Hepsi nullable → mevcut bayiler/mini-siteler etkilenmez (addon-bağımsız).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            $cols = [
                // Hizmet kartları: [{title, desc, icon}] — boşsa MentorDE default hizmetleri
                'site_services'   => fn () => $table->json('site_services')->nullable(),
                // İstatistik rozetleri: [{value, label}]
                'site_stats'      => fn () => $table->json('site_stats')->nullable(),
                // Ekip/danışman kartları: [{name, title, photo}]
                'site_team'       => fn () => $table->json('site_team')->nullable(),
                // İletişim adresi (ofis)
                'site_address'    => fn () => $table->string('site_address', 300)->nullable(),
                // "MentorDE Yetkili Partneri" güven rozeti göster
                'site_show_badge' => fn () => $table->boolean('site_show_badge')->default(true),
            ];
            foreach ($cols as $name => $make) {
                if (!Schema::hasColumn('dealers', $name)) {
                    $make();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            foreach (['site_services', 'site_stats', 'site_team', 'site_address', 'site_show_badge'] as $col) {
                if (Schema::hasColumn('dealers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
