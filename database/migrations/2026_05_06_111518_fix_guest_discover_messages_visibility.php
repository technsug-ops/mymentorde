<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bug fix: 'discover' ve 'messages' page'leri guest rolü için kapalıydı (404 alınıyordu).
 *
 * Tüm şirketlerde role_page_visibility tablosunda guest+discover ve guest+messages
 * kayıtlarını SİLER → PageAccess::visible() default-true mantığına döner → page'ler açılır.
 *
 * Manager bu sayfaları yine UI üzerinden kapatabilir; bu migration sadece "yanlışlıkla
 * kapalı kalmışları" temizler.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('role_page_visibility')) {
            return;
        }

        DB::table('role_page_visibility')
            ->where('role', 'guest')
            ->whereIn('page_key', ['discover', 'messages'])
            ->where('is_visible', false)
            ->delete();

        // Cache invalidation — tüm company_id'ler için cache temizle
        $companyIds = DB::table('role_page_visibility')
            ->select('company_id')
            ->distinct()
            ->pluck('company_id');
        foreach ($companyIds as $cid) {
            \Illuminate\Support\Facades\Cache::forget("page_visibility:{$cid}");
        }
        // Aktif companies için de ek temizlik
        if (Schema::hasTable('companies')) {
            DB::table('companies')->select('id')->get()->each(function ($c) {
                \Illuminate\Support\Facades\Cache::forget("page_visibility:{$c->id}");
            });
        }
    }

    public function down(): void
    {
        // Geri alma yok — bu sadece "yanlışlıkla kapalı" durumu temizliyor
    }
};
