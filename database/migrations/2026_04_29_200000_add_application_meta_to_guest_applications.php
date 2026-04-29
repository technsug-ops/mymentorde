<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uni-Assist + Hochschulstart + Vize başvurusu sırasında manager'ın
 * doldurduğu (öğrencide olmayan) extra alanlar için generic JSON kolonu.
 *
 * Örnekler:
 *  - hochschulstart_bid (BID — 12 hane)
 *  - hochschulstart_ban (BAN — 6 hane)
 *  - eu_eea_marriage (Ja/Nein)
 *  - is_refugee (true/false)
 *  - schulabschluss_type (Anerkannt/Anderes)
 *  - feststellungspruefung_done
 *  - studienkolleg_attended
 *
 * Şema kirletmeyen pattern — gelecek tüm Application Guide
 * (Uni-Assist + Vize + Sperrkonto vb.) bu kolonu kullanır.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('guest_applications', function (Blueprint $table) {
            $table->json('application_meta')->nullable()->after('selected_extra_services');
        });
    }

    public function down(): void
    {
        Schema::table('guest_applications', function (Blueprint $table) {
            $table->dropColumn('application_meta');
        });
    }
};
