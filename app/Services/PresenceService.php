<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAvailabilitySchedule;
use App\Models\UserAwayPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * PresenceService
 *
 * Kullanıcı online/away/offline durumunu hesaplar ve cache'ler.
 *
 * Öncelik sırası:
 *  1. Aktif away_period varsa → "away" (kendi mesajıyla)
 *  2. Şu an çalışma saatleri dışındaysa → "offline"
 *  3. Son 3 dakikada istek attıysa → "online"
 *  4. Son 10 dakikada istek attıysa → "away"
 *  5. Aksi hâlde → "offline"
 */
class PresenceService
{
    private const ONLINE_THRESHOLD_SECONDS  = 3 * 60;   // 3 dakika
    private const AWAY_THRESHOLD_SECONDS    = 10 * 60;  // 10 dakika
    private const CACHE_TTL_SECONDS         = 60;        // 1 dakika cache

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Kullanıcının tam presence bilgisini döndür (cache'li).
     * [status, label, color, away_message, away_until]
     */
    public static function getPresence(User $user): array
    {
        return Cache::remember(
            "presence_{$user->id}",
            self::CACHE_TTL_SECONDS,
            fn () => self::computePresence($user)
        );
    }

    /**
     * Birden fazla kullanıcı için toplu presence — N+1 önlemek için.
     * @param  int[]  $userIds
     * @return array<int, array>  userId => presence array
     */
    public static function getBulkPresence(array $userIds): array
    {
        $result = [];
        foreach ($userIds as $id) {
            $cached = Cache::get("presence_{$id}");
            if ($cached !== null) {
                $result[$id] = $cached;
            }
        }

        $missing = array_diff($userIds, array_keys($result));
        if (!empty($missing)) {
            $users = User::whereIn('id', $missing)
                ->with(['awayPeriods' => fn ($q) => $q->active(), 'availabilitySchedules' => fn ($q) => $q->where('is_active', true)])
                ->get();

            foreach ($users as $user) {
                $presence = self::computePresence($user);
                Cache::put("presence_{$user->id}", $presence, self::CACHE_TTL_SECONDS);
                $result[$user->id] = $presence;
            }
        }

        return $result;
    }

    /**
     * Kullanıcı request attığında çağrılır. Presence'ı günceller.
     */
    public static function heartbeat(User $user): void
    {
        $now = now();

        // DB'yi her requestte güncelleme — 30 saniyede bir yeterli
        if (!Cache::has("presence_hb_{$user->id}")) {
            // Observer/event tetiklememek için raw DB update kullan
            DB::table('users')->where('id', $user->id)->update([
                'last_activity_at' => $now,
                'presence_status'  => 'online',
            ]);
            Cache::put("presence_hb_{$user->id}", true, 30);
            // DB güncellendi — presence cache'ini yenile
            Cache::forget("presence_{$user->id}");
        }
    }

    /**
     * Kullanıcı logout olduğunda offline yap.
     */
    public static function setOffline(User $user): void
    {
        DB::table('users')->where('id', $user->id)->update(['presence_status' => 'offline']);
        Cache::forget("presence_{$user->id}");
        Cache::forget("presence_hb_{$user->id}");
    }

    /**
     * Aktif away period varsa döndür.
     */
    public static function getActiveAwayPeriod(User $user): ?UserAwayPeriod
    {
        return UserAwayPeriod::where('user_id', $user->id)->active()->first();
    }

    /**
     * Bu an kullanıcı çalışma saatleri içinde mi?
     */
    public static function isWithinWorkingHours(User $user): bool
    {
        $schedules = UserAvailabilitySchedule::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) {
            return true; // Schedule tanımlı değilse her zaman müsait sayılır
        }

        $tz       = $schedules->first()->timezone ?? 'Europe/Berlin';
        $now      = now()->setTimezone($tz);
        $dayOfWeek = (int) $now->dayOfWeek; // 0=Pazar, 6=Cumartesi
        $timeNow   = $now->format('H:i:s');

        $todaySchedule = $schedules->firstWhere('day_of_week', $dayOfWeek);

        if (!$todaySchedule) {
            return false; // Bugün için schedule yok → çalışma günü değil
        }

        return $timeNow >= $todaySchedule->start_time && $timeNow <= $todaySchedule->end_time;
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private static function computePresence(User $user): array
    {
        // 1. Aktif away period?
        $awayPeriod = UserAwayPeriod::where('user_id', $user->id)->active()->first();
        if ($awayPeriod) {
            return self::buildPresence(
                'away',
                $awayPeriod->away_message ?: 'Şu an müsait değilim',
                $awayPeriod->away_until,
                (string) $awayPeriod->id
            );
        }

        // 2. Çalışma saatleri dışında mı?
        $schedules = UserAvailabilitySchedule::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($schedules->isNotEmpty()) {
            $tz        = $schedules->first()->timezone ?? 'Europe/Berlin';
            $now       = now()->setTimezone($tz);
            $dayOfWeek = (int) $now->dayOfWeek;
            $timeNow   = $now->format('H:i:s');
            $today     = $schedules->firstWhere('day_of_week', $dayOfWeek);

            if (!$today || $timeNow < $today->start_time || $timeNow > $today->end_time) {
                // Çalışma saati dışı — bir sonraki açılış saatini bul
                $nextOpen = self::findNextOpenTime($schedules, $tz);
                return self::buildPresence('offline', 'Çalışma saatleri dışında', $nextOpen);
            }
        }

        // 3. Son aktivite zamanına bak
        $lastActivity = $user->last_activity_at;
        if (!$lastActivity) {
            return self::buildPresence('offline', 'Çevrimdışı');
        }

        $secondsAgo = now()->diffInSeconds($lastActivity, false);
        $secondsAgo = abs($secondsAgo);

        if ($secondsAgo <= self::ONLINE_THRESHOLD_SECONDS) {
            return self::buildPresence('online', 'Çevrimiçi');
        }

        if ($secondsAgo <= self::AWAY_THRESHOLD_SECONDS) {
            return self::buildPresence('away', 'Az önce aktifti');
        }

        return self::buildPresence('offline', 'Çevrimdışı');
    }

    private static function buildPresence(
        string $status,
        string $label,
        ?Carbon $awayUntil = null,
        ?string $awayPeriodId = null
    ): array {
        $colors = [
            'online'  => '#16a34a',
            'away'    => '#d97706',
            'busy'    => '#dc2626',
            'offline' => '#9ca3af',
        ];

        return [
            'status'        => $status,
            'label'         => $label,
            'color'         => $colors[$status] ?? '#9ca3af',
            'away_until'    => $awayUntil?->toIso8601String(),
            'away_until_fmt'=> $awayUntil?->format('d.m.Y H:i'),
            'away_period_id'=> $awayPeriodId,
        ];
    }

    private static function findNextOpenTime($schedules, string $tz): ?Carbon
    {
        $now       = now()->setTimezone($tz);
        $dayOfWeek = (int) $now->dayOfWeek;

        // Önümüzdeki 7 günü kontrol et
        for ($i = 1; $i <= 7; $i++) {
            $checkDay = ($dayOfWeek + $i) % 7;
            $schedule = $schedules->firstWhere('day_of_week', $checkDay);
            if ($schedule) {
                return $now->copy()->addDays($i)->setTimeFromTimeString($schedule->start_time);
            }
        }

        return null;
    }
}
