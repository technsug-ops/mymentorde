<?php

namespace Database\Seeders;

use App\Models\GermanyCity;
use Illuminate\Database\Seeder;

/**
 * Almanya şehir verilerini config/germany_cities.php'den DB'ye aktarır.
 *
 * Çalıştır: php artisan db:seed --class=GermanyCitySeeder
 */
class GermanyCitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = config('germany_cities', []);

        foreach ($cities as $slug => $city) {
            // Üst seviye scalar alanları çıkar, geri kalanı 'data'ya koy
            $topLevel = [
                'slug'       => $slug,
                'name'       => $city['name'] ?? $slug,
                'state'      => $city['state'] ?? null,
                'emoji'      => $city['emoji'] ?? null,
                'cost_index' => $city['cost_index'] ?? 3,
                'is_active'  => true,
            ];

            $dataFields = array_diff_key($city, array_flip(['slug', 'name', 'state', 'emoji', 'cost_index']));

            GermanyCity::updateOrCreate(
                ['slug' => $slug],
                array_merge($topLevel, ['data' => $dataFields])
            );
        }

        GermanyCity::clearCache();

        $this->command->info('GermanyCity: ' . count($cities) . ' şehir aktarıldı.');
    }
}
