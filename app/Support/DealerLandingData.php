<?php

namespace App\Support;

use App\Models\GuestApplication;
use App\Models\User;
use Carbon\Carbon;

/**
 * Satış ortağı landing + bayi mini-site için ortak sayaç (counter) verisi.
 * Daha önce routes/web.php /satis-ortagi closure'ında gömülüydü; mini-site ile
 * paylaşmak için buraya çıkarıldı (tek kaynak).
 */
class DealerLandingData
{
    public static function counters(): array
    {
        $cfg = config('dealer_landing');

        $growthStart = Carbon::parse($cfg['growth_start_date'])->startOfDay();
        $today       = Carbon::today();
        $daysElapsed = max(0, (int) $growthStart->diffInDays($today));

        $dailyGrowth = $cfg['daily_growth'];

        $computeDailyIncrement = function (string $dateStr, string $key, array $dist): int {
            $seed = crc32($dateStr . ':' . $key);
            $roll = $seed % 100;
            if ($roll < $dist['skip_pct']) return 0;
            if ($roll < $dist['skip_pct'] + $dist['single_pct']) return 1;
            return 2;
        };

        $growth = ['sellers' => 0, 'applications' => 0, 'students' => 0, 'commissions_eur' => 0];
        $commissionRange = $dailyGrowth['commissions_eur_per_application'] ?? [180, 380];

        for ($i = 0; $i <= $daysElapsed; $i++) {
            $dateStr = $growthStart->copy()->addDays($i)->toDateString();

            $appInc = $computeDailyIncrement($dateStr, 'applications', $dailyGrowth['applications']);
            $growth['applications'] += $appInc;
            $growth['sellers']  += $computeDailyIncrement($dateStr, 'sellers', $dailyGrowth['sellers']);
            $growth['students'] += $computeDailyIncrement($dateStr, 'students', $dailyGrowth['students']);

            if ($appInc > 0) {
                for ($j = 0; $j < $appInc; $j++) {
                    $commSeed = crc32($dateStr . ':comm:' . $j);
                    $growth['commissions_eur'] += $commissionRange[0] + ($commSeed % ($commissionRange[1] - $commissionRange[0]));
                }
            }
        }

        return [
            'sellers' => (int) $cfg['historical_sellers']
                + $growth['sellers']
                + User::query()->withoutGlobalScopes()
                    ->whereIn('role', ['dealer'])
                    ->where('is_active', true)
                    ->count(),
            'applications' => (int) $cfg['historical_applications']
                + $growth['applications']
                + GuestApplication::query()->withoutGlobalScopes()->count(),
            'students' => (int) $cfg['historical_students']
                + $growth['students']
                + User::query()->withoutGlobalScopes()
                    ->where('role', 'student')
                    ->count(),
            'commissions_eur' => (int) $cfg['historical_commissions_eur'] + $growth['commissions_eur'],
        ];
    }
}
