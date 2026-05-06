<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bug fix: 'support' page'i guest icin kapaliydi (404 NOT FOUND).
 *
 * Hizmetler sayfasindan ticket olusturulduktan sonra Laravel
 * route('guest.tickets')'a redirect ediyor; bu route page.visible:support
 * middleware'i ile korunuyor → role_page_visibility'de explicit false ise
 * 404 doner.
 *
 * Tum company'lerde guest+support is_visible=false satirlarini siler →
 * PageAccess::visible() default-true mantigina doner.
 *
 * Manager bu sayfayi yine UI uzerinden kapatabilir.
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
            ->where('page_key', 'support')
            ->where('is_visible', false)
            ->delete();

        if (Schema::hasTable('companies')) {
            DB::table('companies')->select('id')->get()->each(function ($c) {
                \Illuminate\Support\Facades\Cache::forget("page_visibility:{$c->id}");
            });
        }
    }

    public function down(): void
    {
        // Geri alma yok — bu sadece "yanlislikla kapali" durumu temizliyor
    }
};
