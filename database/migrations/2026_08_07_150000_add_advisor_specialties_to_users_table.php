<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Danışmanın uzmanlık etiketleri — bir kişide birden fazla olabilir.
 *
 * ── NEDEN YENİ ALAN ─────────────────────────────────────────────────────
 * `senior_type` TEK değer tutuyor ve zaten iki işi birden görüyor: atama
 * eşleşmesi ile fiyat/komisyon kademesi (bkz. CommissionResolver —
 * junior|mid|senior|expert). Üstüne uzmanlık yüklemek o karışıklığı
 * büyütürdü.
 *
 * `expertise_tags` ise serbest metin ve randevu sayfasında HERKESE AÇIK.
 * Atama mantığını ona bağlamak, pazarlama amaçlı yazılan bir etiketin
 * öğrenci dağıtımını sessizce değiştirmesi demekti.
 *
 * Bu yüzden ayrı ve denetimli bir alan: yalnızca bilinen etiketler
 * (bkz. User::ADVISOR_SPECIALTIES).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('advisor_specialties')->nullable()->after('senior_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('advisor_specialties');
        });
    }
};
