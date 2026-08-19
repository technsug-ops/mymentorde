<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kurumsal (şablonlu) site yetkisi — iş modelinden BAĞIMSIZ.
 *
 * Bugüne kadar "hangi siteyi görürsün" sorusunun cevabı `dealer_type_code`'du:
 * yalnız b2b_partner çok-bölümlü şablon sitesini alıyordu. Ama o kolon aynı
 * zamanda komisyon kademesini, sözleşme kategorisini ve KPI gruplarını da
 * belirliyor — bir freelance danışmana site açmak için tipini değiştirmek
 * kazancını bozardı.
 *
 * `site_mode` bu iki kararı ayırır. null = bugünkü davranış (tipine göre),
 * 'partner' = tipi ne olursa olsun kurumsal site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (!Schema::hasColumn('dealers', 'site_mode')) {
                $table->string('site_mode', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (Schema::hasColumn('dealers', 'site_mode')) {
                $table->dropColumn('site_mode');
            }
        });
    }
};
