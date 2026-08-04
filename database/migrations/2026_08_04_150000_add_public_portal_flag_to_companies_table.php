<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Bu şirket, partner firmaların ORTAK GİRİŞ KAPISI mı?"
 *
 * Sorun: partner firmanın başvuru linki panelde `url()` ile üretiliyordu, o da
 * o an gezinilen host'u alıyor. Platform sahibi panel.mentorde.com'da olduğu
 * için partnere verilecek adres `panel.mentorde.com/apply/{firma}` çıkıyordu —
 * white-label sözü URL'in kendisinde bozuluyordu.
 *
 * Link artık şu sırayla üretilir:
 *   1. Firmanın KENDİ domaini (primary_domain) varsa o
 *   2. Yoksa portal olarak işaretlenmiş şirketin domaini (yourgermanuni.com)
 *   3. O da yoksa mevcut host (eski davranış)
 *
 * Bayrak veri tabanında tutuluyor, .env'de değil: KAS'ta konsol yok, bu ayarın
 * panelden değiştirilebilmesi gerek.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('companies', 'is_public_portal')) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->boolean('is_public_portal')->default(false)->after('primary_domain');
            });
        }

        // Nötr portal kaydı bu migration serisinde oluşturulmuştu
        // (2026_08_04_130000_seed_yourgermanuni_portal_company) — işaretle.
        DB::table('companies')
            ->where('code', 'yourgermanuni')
            ->whereNotNull('primary_domain')
            ->update(['is_public_portal' => true]);
    }

    public function down(): void
    {
        if (!Schema::hasColumn('companies', 'is_public_portal')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('is_public_portal');
        });
    }
};
