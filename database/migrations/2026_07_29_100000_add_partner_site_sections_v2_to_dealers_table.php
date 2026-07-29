<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner sitesi bölüm alanları — 2. tur (dışarıda hazırlanan 10 şablonun ihtiyacı).
 *
 *  site_packages     JSON  [{name, tag, desc, items[], featured}] — destek paketleri
 *  site_package_note string paket bölümünün altındaki açıklama satırı
 *  site_faq          JSON  [{q, a}] — sıkça sorulan sorular
 *  site_universities JSON  ["TU München", ...] — öğrencilerin yerleştiği üniversiteler
 *
 * Hepsi nullable: boş = bölüm sitede hiç basılmaz (uydurma paket/fiyat/üniversite
 * listesi üretilmez). Addon-bağımsız — mevcut siteler bu alanlar boşken de çalışır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            if (!Schema::hasColumn('dealers', 'site_packages')) {
                $table->json('site_packages')->nullable();
            }
            if (!Schema::hasColumn('dealers', 'site_package_note')) {
                $table->string('site_package_note', 300)->nullable();
            }
            if (!Schema::hasColumn('dealers', 'site_faq')) {
                $table->json('site_faq')->nullable();
            }
            if (!Schema::hasColumn('dealers', 'site_universities')) {
                $table->json('site_universities')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table): void {
            foreach (['site_packages', 'site_package_note', 'site_faq', 'site_universities'] as $col) {
                if (Schema::hasColumn('dealers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
