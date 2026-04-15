<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Almanya şehirlerini config/germany_cities.php'den germany_cities tablosuna aktarır.
 *
 * Neden data migration: Local'de GermanyCitySeeder var ama
 * DatabaseSeeder'a kayıtlı değildi; prod'da `php artisan db:seed` çalıştırılsa
 * bile şehirler yüklenmiyordu. panel.mentorde.com/guest/city/berlin 404 dönüyordu.
 *
 * Idempotent: mevcut kayıtları updateOrInsert ile günceller.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('germany_cities')) {
            return;
        }

        $cities = config('germany_cities', []);
        if (empty($cities)) {
            return;
        }

        $now = now();

        foreach ($cities as $slug => $city) {
            $topLevel = [
                'slug'       => (string) $slug,
                'name'       => (string) ($city['name'] ?? $slug),
                'state'      => $city['state'] ?? null,
                'emoji'      => $city['emoji'] ?? null,
                'cost_index' => (int) ($city['cost_index'] ?? 3),
                'is_active'  => true,
            ];

            $dataFields = array_diff_key(
                $city,
                array_flip(['slug', 'name', 'state', 'emoji', 'cost_index'])
            );

            DB::table('germany_cities')->updateOrInsert(
                ['slug' => $topLevel['slug']],
                array_merge($topLevel, [
                    'data'       => json_encode($dataFields, JSON_UNESCAPED_UNICODE),
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }

        // Cache invalidation — model içinde GermanyCity::allAsConfig() 24h cache yapıyor
        try {
            \Illuminate\Support\Facades\Cache::forget('germany_cities_all');
        } catch (\Throwable $e) {
            // Cache driver yoksa sessizce geç
        }
    }

    public function down(): void
    {
        // No-op: veri silme (rollback gereken durumlarda manuel)
    }
};
