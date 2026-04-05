<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Dana Yıldız E2E Test Seederi
 *
 * E2EUserSeeder (standart test kullanıcıları) +
 * DanaRealisticJourneySeeder (Dana'nın 155 günlük gerçekçi serüveni)
 *
 * Kullanım:
 *   php artisan migrate:fresh --seed --seeder=DanaE2ESeeder --env=testing --force
 */
class DanaE2ESeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            E2EUserSeeder::class,
            DanaRealisticJourneySeeder::class,
        ]);
    }
}
